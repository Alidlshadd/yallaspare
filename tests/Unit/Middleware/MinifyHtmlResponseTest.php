<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\MinifyHtmlResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class MinifyHtmlResponseTest extends TestCase
{
    private function process(string $html, string $contentType = 'text/html; charset=UTF-8'): string
    {
        $middleware = new MinifyHtmlResponse();
        $request = Request::create('/', 'GET');

        $response = $middleware->handle($request, function () use ($html, $contentType) {
            return new Response($html, 200, ['Content-Type' => $contentType]);
        });

        return (string) $response->getContent();
    }

    public function test_collapses_indentation_between_tags(): void
    {
        $out = $this->process("<div>\n    <span>hi</span>\n</div>");

        $this->assertStringNotContainsString("\n", $out);
        $this->assertStringNotContainsString('    ', $out);
        $this->assertStringContainsString('<span>hi</span>', $out);
    }

    public function test_preserves_script_contents_verbatim(): void
    {
        $js = "<script>\n    const x = 1; // keep this comment\n    foo();\n</script>";
        $out = $this->process("<div>\n  {$js}\n</div>");

        // The JS body — including its newlines and // comment — must survive,
        // otherwise ASI could merge statements and break behaviour.
        $this->assertStringContainsString("const x = 1; // keep this comment", $out);
        $this->assertStringContainsString("\n", $out); // newline inside script kept
    }

    public function test_preserves_pre_and_textarea_whitespace(): void
    {
        $out = $this->process("<pre>line1\n    line2</pre>");
        $this->assertStringContainsString("line1\n    line2", $out);

        $out2 = $this->process("<textarea>a\n\nb</textarea>");
        $this->assertStringContainsString("a\n\nb", $out2);
    }

    public function test_preserves_whitespace_inside_quoted_attribute_values(): void
    {
        $out = $this->process('<meta name="description" content="a    b    c">');
        $this->assertStringContainsString('content="a    b    c"', $out);
    }

    public function test_removes_html_comments(): void
    {
        $out = $this->process('<div><!-- internal note --><span>x</span></div>');
        $this->assertStringNotContainsString('internal note', $out);
        $this->assertStringContainsString('<span>x</span>', $out);
    }

    public function test_keeps_single_space_between_inline_elements(): void
    {
        // The space between inline elements is visible text; it must not vanish.
        $out = $this->process('<a>x</a> <a>y</a>');
        $this->assertStringContainsString('</a> <a>', $out);
    }

    public function test_does_not_touch_non_html_responses(): void
    {
        $json = '{ "a" :   1 }';
        $out = $this->process($json, 'application/json');
        $this->assertSame($json, $out);
    }
}
