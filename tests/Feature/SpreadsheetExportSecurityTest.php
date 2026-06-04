<?php

namespace Tests\Feature;

use App\Exports\UsersExport;
use App\Support\SpreadsheetSanitizer;
use Tests\TestCase;

class SpreadsheetExportSecurityTest extends TestCase
{
    public function test_malicious_values_are_exported_as_plain_text(): void
    {
        $this->assertSame("'=WEBSERVICE(\"https://evil.test\")", SpreadsheetSanitizer::cell('=WEBSERVICE("https://evil.test")'));
        $this->assertSame("'+SUM(1,1)", SpreadsheetSanitizer::cell('+SUM(1,1)'));
        $this->assertSame("'-10+cmd", SpreadsheetSanitizer::cell('-10+cmd'));
        $this->assertSame("'@evil", SpreadsheetSanitizer::cell('@evil'));
        $this->assertSame("'\t=CMD()", SpreadsheetSanitizer::cell("\t=CMD()"));
        $this->assertSame('hello Bcc: victim@example.test', SpreadsheetSanitizer::cell("hello\r\nBcc: victim@example.test"));
    }

    public function test_user_export_neutralizes_formula_cells(): void
    {
        $export = new UsersExport();
        $row = $export->map((object) [
            'id' => 1,
            'name' => '=WEBSERVICE("https://evil.test")',
            'email' => 'attacker@example.test',
            'phone' => '+SUM(1,1)',
            'role' => 'user',
            'dealer_status' => 'inactive',
            'dealer_discount' => 0,
            'email_verified_at' => null,
            'locale_preference' => "en\r\n=CMD()",
            'created_at' => null,
        ]);

        $this->assertSame("'=WEBSERVICE(\"https://evil.test\")", $row[1]);
        $this->assertSame("'+SUM(1,1)", $row[3]);
        $this->assertSame('en =CMD()', $row[8]);
    }
}
