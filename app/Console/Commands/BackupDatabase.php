<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Take a dump of the database and keep the recent ones.
 *
 * There was a PowerShell script doing this, which could only ever run on the
 * machine it was written on and was never wired to anything. This runs where
 * the shop runs.
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--keep-days= : Override how many days of dumps to keep}';

    protected $description = 'Write a compressed dump of the database and prune old ones';

    public function handle(): int
    {
        $directory = (string) config('ops.backup.directory');

        if (! is_dir($directory) && ! @mkdir($directory, 0750, true) && ! is_dir($directory)) {
            $this->error("Cannot create the backup directory: {$directory}");

            return self::FAILURE;
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        $target = rtrim($directory, '/\\').DIRECTORY_SEPARATOR
            .$this->databaseLabel($connection->getDatabaseName()).'_'
            .Carbon::now()->format('Ymd_His').'.sql';

        $result = match ($driver) {
            'mysql', 'mariadb' => $this->dumpMysql($connection->getConfig(), $target),
            'sqlite' => $this->dumpSqlite((string) $connection->getDatabaseName(), $target),
            default => $this->unsupportedDriver($driver),
        };

        if ($result !== self::SUCCESS) {
            @unlink($target);

            return $result;
        }

        $compressed = $this->compress($target);

        if ($compressed === null) {
            $this->error('The dump was written but could not be compressed: '.$target);

            return self::FAILURE;
        }

        $this->info('Backup written: '.$compressed.' ('.$this->humanSize(filesize($compressed) ?: 0).')');
        $this->info('Pruned '.$this->prune($directory).' expired backup(s).');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function dumpMysql(array $config, string $target): int
    {
        $binary = trim((string) config('ops.backup.mysqldump')) ?: 'mysqldump';

        // The password goes in a file only this process can read, never on the
        // command line, where every other user on the box could read it out of
        // the process list.
        $credentials = $this->writeCredentialsFile((string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''));

        if ($credentials === null) {
            $this->error('Could not write the temporary credentials file.');

            return self::FAILURE;
        }

        try {
            $process = new Process([
                $binary,
                '--defaults-extra-file='.$credentials,
                '--host='.((string) ($config['host'] ?? '127.0.0.1')),
                '--port='.((string) ($config['port'] ?? 3306)),
                '--single-transaction',
                '--quick',
                '--routines',
                '--events',
                '--triggers',
                '--result-file='.$target,
                (string) ($config['database'] ?? ''),
            ]);

            $process->setTimeout((float) config('ops.backup.timeout', 900));
            $process->run();

            if (! $process->isSuccessful()) {
                $this->error('mysqldump failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));

                return self::FAILURE;
            }
        } catch (\Throwable $exception) {
            $this->error('mysqldump could not be run: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            @unlink($credentials);
        }

        // A dump cut short by a dropped connection still exits zero sometimes.
        // mysqldump signs off with this line, so its absence means the file is
        // not a backup, whatever its size says.
        if (! $this->endsWithDumpMarker($target)) {
            $this->error('The dump is incomplete — mysqldump did not finish writing it.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * SQLite keeps the whole database in one file, so a copy is the dump.
     */
    private function dumpSqlite(string $database, string $target): int
    {
        if ($database === ':memory:' || $database === '') {
            $this->error('An in-memory SQLite database has nothing to back up.');

            return self::FAILURE;
        }

        if (! is_file($database)) {
            $this->error("The SQLite database file is missing: {$database}");

            return self::FAILURE;
        }

        if (! @copy($database, $target)) {
            $this->error('Could not copy the SQLite database file.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Gzip the dump in a stream, so a large database is never held in memory,
     * and drop the uncompressed copy once it is safely written.
     */
    private function compress(string $target): ?string
    {
        $compressed = $target.'.gz';
        $source = @fopen($target, 'rb');
        $sink = $source === false ? false : @gzopen($compressed, 'wb9');

        if ($source === false || $sink === false) {
            if ($source !== false) {
                fclose($source);
            }

            return null;
        }

        while (! feof($source)) {
            $chunk = fread($source, 1024 * 512);

            if ($chunk === false || gzwrite($sink, $chunk) === false) {
                fclose($source);
                gzclose($sink);
                @unlink($compressed);

                return null;
            }
        }

        fclose($source);
        gzclose($sink);
        @unlink($target);

        return $compressed;
    }

    private function endsWithDumpMarker(string $target): bool
    {
        $size = @filesize($target);

        if ($size === false || $size === 0) {
            return false;
        }

        $handle = @fopen($target, 'rb');

        if ($handle === false) {
            return false;
        }

        fseek($handle, max(0, $size - 512));
        $tail = (string) fread($handle, 512);
        fclose($handle);

        return str_contains($tail, 'Dump completed');
    }

    /**
     * @return int how many files were removed
     */
    private function prune(string $directory): int
    {
        $keepDays = max(1, (int) ($this->option('keep-days') ?: config('ops.backup.keep_days', 14)));
        $cutoff = Carbon::now()->subDays($keepDays)->getTimestamp();
        $removed = 0;

        foreach (glob(rtrim($directory, '/\\').DIRECTORY_SEPARATOR.'*.sql.gz') ?: [] as $file) {
            if ((filemtime($file) ?: 0) < $cutoff && @unlink($file)) {
                $removed++;
            }
        }

        return $removed;
    }

    private function writeCredentialsFile(string $username, string $password): ?string
    {
        $path = tempnam(sys_get_temp_dir(), 'ysdump');

        if ($path === false) {
            return null;
        }

        @chmod($path, 0600);

        $written = @file_put_contents(
            $path,
            "[client]\nuser=\"{$username}\"\npassword=\"{$password}\"\n"
        );

        if ($written === false) {
            @unlink($path);

            return null;
        }

        return $path;
    }

    private function databaseLabel(?string $database): string
    {
        $label = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $database);

        return $label === '' || $label === null ? 'database' : $label;
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        return $bytes >= 1024 ? round($bytes / 1024).' KB' : $bytes.' B';
    }

    private function unsupportedDriver(string $driver): int
    {
        $this->error("No backup support for the '{$driver}' driver.");

        return self::FAILURE;
    }
}
