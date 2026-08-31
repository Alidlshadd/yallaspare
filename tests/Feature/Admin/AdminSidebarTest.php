<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin sidebar's shape.
 *
 * Twenty-nine links is a lot to hold in one list, and the list had drifted:
 * stock work was split between "Analytics" and "Catalog", two sections held a
 * single link each, and four icons were used twice. None of that breaks a
 * page, so nothing was failing — it just made the panel hard to read.
 */
class AdminSidebarTest extends TestCase
{
    use RefreshDatabase;

    private const SECTIONS = ['Operations', 'Catalog', 'Stock', 'Marketing', 'Reports', 'Administration'];

    public function test_a_super_admin_sees_every_section_and_link(): void
    {
        $html = $this->sidebarFor($this->superAdmin());

        foreach (self::SECTIONS as $section) {
            $this->assertStringContainsString(
                '<span>'.$section.'</span>',
                $html,
                "The “{$section}” section is missing."
            );
        }

        foreach ([
            'Dashboard', 'Orders Management', 'Returns &amp; Refunds', 'Product Requests',
            'Products', 'Categories', 'Product Brands', 'Vehicle Finder', 'Customer Reviews',
            'Inventory', 'Bulk Stock', 'Dead Stock', 'Purchase Planning',
            'Coupon Management', 'Discount Rules', 'Email Center', 'Popups', 'Inbound WhatsApp',
            'Progress Center', 'Revenue', 'Site Analytics', 'Search Insights', 'WAYL Payments',
            'Dealers', 'Users', 'Settings', 'Shipping', 'Activity Logs',
        ] as $label) {
            $this->assertStringContainsString(
                '<span class="admin-nav-label">'.$label.'</span>',
                $html,
                "The “{$label}” link is missing."
            );
        }

        $this->assertSame(29, substr_count($html, 'class="admin-nav-link'), 'Expected 29 links in the sidebar.');
    }

    public function test_no_section_heading_is_left_standing_over_nothing(): void
    {
        // An administrator who can only work on orders should see the one
        // section that holds something for them, and no empty headings above
        // links they are not allowed to open.
        $orders = $this->adminWith([User::PERMISSION_ORDERS_MANAGE]);

        $html = $this->sidebarFor($orders);

        foreach ($this->sectionsIn($html) as $section => $linkCount) {
            $this->assertGreaterThan(
                0,
                $linkCount,
                "The “{$section}” heading is shown with no links beneath it."
            );
        }
    }

    public function test_every_icon_belongs_to_one_link_only(): void
    {
        // Two links wearing the same icon is the reason the list was hard to
        // scan: Dashboard and Site Analytics, Inventory and Dead Stock,
        // Categories and Bulk Stock, Product Brands and Coupon Management.
        $html = $this->sidebarFor($this->superAdmin());

        preg_match_all('/data-ph="([a-z0-9-]+)"/', $html, $matches);

        $icons = $matches[1];
        $duplicates = array_keys(array_filter(array_count_values($icons), static fn (int $n): bool => $n > 1));

        $this->assertSame([], $duplicates, 'These icons are used by more than one link: '.implode(', ', $duplicates));
        $this->assertCount(29, $icons, 'Every link should carry an icon.');

        // One family, one geometry. A stray Font Awesome glyph in the panel
        // would put two drawing styles side by side.
        $nav = $this->between($html, '<nav class="admin-nav', '</nav>');
        $this->assertStringNotContainsString('class="fa', $nav, 'The navigation should carry Phosphor icons only.');

        // Each icon ships both weights so the current page can wear the
        // heavier one without another request.
        $this->assertSame(29, substr_count($nav, 'ph-regular'));
        $this->assertSame(29, substr_count($nav, 'ph-fill'));
    }

    public function test_the_accent_marks_the_current_page_and_nothing_else(): void
    {
        // Orange used to sit on section headings, divider lines, hover icons and
        // a sweep animation as well. It now has one job.
        $html = $this->sidebarFor($this->superAdmin());

        $this->assertStringNotContainsString('admin-nav-sweep', $html, 'The sweep animation should be gone.');
        $this->assertStringContainsString('.admin-shell .admin-nav-link.is-active::before', $html);
        $this->assertStringNotContainsString('color: #ffb27a;', $html, 'Section headings should no longer be orange.');
    }

    /**
     * How many links sit under each section heading.
     *
     * @return array<string, int>
     */
    private function sectionsIn(string $html): array
    {
        // The response arrives with its whitespace collapsed, so this splits on
        // the headings themselves rather than reading line by line.
        $nav = $this->between($html, '<nav class="admin-nav', '</nav>');
        $chunks = preg_split(
            '/<div class="admin-nav-section"[^>]*><span>([^<]+)<\/span><\/div>/',
            $nav,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        ) ?: [];

        $counts = [];

        // chunk 0 is whatever preceded the first heading; the rest alternate
        // heading, body, heading, body.
        for ($i = 1; $i < count($chunks); $i += 2) {
            $counts[trim($chunks[$i])] = substr_count($chunks[$i + 1] ?? '', 'class="admin-nav-link');
        }

        return $counts;
    }

    private function between(string $haystack, string $start, string $end): string
    {
        $from = strpos($haystack, $start);
        $this->assertNotFalse($from, 'The sidebar navigation is missing entirely.');

        $to = strpos($haystack, $end, $from);
        $this->assertNotFalse($to);

        return substr($haystack, $from, $to - $from);
    }

    private function sidebarFor(User $admin): string
    {
        return (string) $this->actingAs($admin)
            ->withSession(['admin_2fa.verified_user_id' => $admin->id])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->forceFill(['role' => User::ROLE_SUPER_ADMIN])->save();

        return $admin;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function adminWith(array $permissions): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->forceFill([
            'role' => User::ROLE_ADMIN,
            'permissions' => array_merge([User::PERMISSION_DASHBOARD_VIEW], $permissions),
        ])->save();

        return $admin;
    }
}
