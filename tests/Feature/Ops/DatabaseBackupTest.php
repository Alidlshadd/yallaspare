<?php

namespace Tests\Feature\Ops;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    private string $backupDirectory;

    private string $databaseFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupDirectory = storage_path('framework/testing/ops-backups');
        $this->databaseFile = storage_path('framework/testing/ops-source.sqlite');

        File::ensureDirectoryExists(dirname($this->databaseFile));
        File::deleteDirectory($this->backupDirectory);

        config(['ops.backup.directory' => $this->backupDirectory]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupDirectory);
        File::delete($this->databaseFile);

        parent::tearDown();
    }

    public function test_a_backup_is_written_and_holds_the_database(): void
    {
        $this->useFileDatabase();
        DB::statement('CREATE TABLE parts (id INTEGER PRIMARY KEY, name TEXT)');
        DB::statement("INSERT INTO parts (name) VALUES ('brake pad')");

        $this->artisan('db:backup')->assertSuccessful();

        $backups = glob($this->backupDirectory.DIRECTORY_SEPARATOR.'*.sql.gz') ?: [];
        $this->assertCount(1, $backups, 'Exactly one backup should have been written.');

        // A file that exists is not a backup. This one has to still contain
        // the row that was in the database when it was taken.
        $this->assertStringContainsString('brake pad', (string) gzdecode((string) file_get_contents($backups[0])));
    }

    public function test_the_uncompressed_dump_is_not_left_behind(): void
    {
        $this->useFileDatabase();

        $this->artisan('db:backup')->assertSuccessful();

        $this->assertSame([], glob($this->backupDirectory.DIRECTORY_SEPARATOR.'*.sql') ?: []);
    }

    public function test_backups_past_the_retention_window_are_removed(): void
    {
        $this->useFileDatabase();
        File::ensureDirectoryExists($this->backupDirectory);

        $stale = $this->backupDirectory.DIRECTORY_SEPARATOR.'old_20260101_000000.sql.gz';
        $recent = $this->backupDirectory.DIRECTORY_SEPARATOR.'recent_20260801_000000.sql.gz';
        File::put($stale, 'x');
        File::put($recent, 'x');
        touch($stale, now()->subDays(30)->getTimestamp());
        touch($recent, now()->subDay()->getTimestamp());

        $this->artisan('db:backup --keep-days=14')->assertSuccessful();

        $this->assertFileDoesNotExist($stale);
        $this->assertFileExists($recent);
    }

    public function test_an_in_memory_database_reports_that_it_cannot_be_backed_up(): void
    {
        // The default in the test suite, and a real mistake to make on a
        // server: it must say so rather than write an empty file and call it
        // a backup.
        $this->artisan('db:backup')->assertFailed();

        $this->assertSame([], glob($this->backupDirectory.DIRECTORY_SEPARATOR.'*') ?: []);
    }

    public function test_a_missing_mysqldump_fails_loudly_instead_of_writing_nothing(): void
    {
        // The failure mode that matters most: a server where mysqldump is not
        // installed must report it, not exit quietly and leave the shop
        // believing it has backups.
        config([
            'database.connections.ops_mysql_test' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'nothing_here',
                'username' => 'user',
                'password' => 'secret',
            ],
            'database.default' => 'ops_mysql_test',
            'ops.backup.mysqldump' => 'mysqldump-that-does-not-exist',
        ]);

        $this->artisan('db:backup')->assertFailed();

        // And no half-written file is left looking like a backup.
        $this->assertSame([], glob($this->backupDirectory.DIRECTORY_SEPARATOR.'*') ?: []);
    }

    private function useFileDatabase(): void
    {
        File::put($this->databaseFile, '');

        config([
            'database.connections.ops_backup_test' => [
                'driver' => 'sqlite',
                'database' => $this->databaseFile,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'database.default' => 'ops_backup_test',
        ]);

        DB::purge('ops_backup_test');
    }
}
