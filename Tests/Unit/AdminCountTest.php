<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

class AdminCountTest extends Unit
{
    private function render(string $layout, int $admin_count, bool $try_to_replace = false): string
    {
        $config = new \Config();
        $config->app_path = dirname(__DIR__, 2);

        $page = new \Template(config: $config, admin_count: $admin_count);
        $page->setTemplate($layout);
        $page->set('page_content', '<p>inner</p>');
        if ($try_to_replace) {
            $page->set('admin_count', 99);
        }

        return $page->grabTheGoods();
    }

    public function testSetCannotReplaceTheConstructorsCount(): void
    {
        $html = $this->render('layout/admin_base.tpl.php', 3, true);

        $this->assertStringContainsString('<span class="AdminCount" aria-hidden="true">3</span>', $html);
        $this->assertStringNotContainsString('99', $html);
    }

    public function testAdminWithPendingMigrationsSeesChipAndLabel(): void
    {
        $html = $this->render('layout/admin_base.tpl.php', 3);

        $this->assertStringContainsString('aria-label="Admin Site, 3 waiting for an admin"', $html);
        $this->assertStringContainsString('class="AdminCount"', $html);
    }

    public function testAdminWithNoPendingMigrationsSeesPlainLink(): void
    {
        $html = $this->render('layout/admin_base.tpl.php', 0);

        $this->assertStringContainsString('<a href="/admin/">Admin Site</a>', $html);
        $this->assertStringNotContainsString('AdminCount', $html);
        $this->assertStringNotContainsString('aria-label', $html);
    }

    public function testPlainUserLayoutNeverShowsACount(): void
    {
        $html = $this->render('layout/base.tpl.php', 3);

        $this->assertStringNotContainsString('AdminCount', $html);
        $this->assertStringNotContainsString('/admin/', $html);
    }

    public function testLoggedOutLayoutNeverShowsACount(): void
    {
        $html = $this->render('layout/welcome_base.tpl.php', 3);

        $this->assertStringNotContainsString('AdminCount', $html);
        $this->assertStringNotContainsString('/admin/', $html);
    }
}
