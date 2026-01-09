<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

$debugLevel = intval(value: $_GET['debug']) ?? 0;
if($debugLevel > 0) {
    echo "<pre>Debug Level: $debugLevel</pre>";
}

if($is_logged_in->isLoggedIn()){
    // Logged in - show main site homepage
    $page = new \Template(config: $config);
    if ($is_logged_in->isAdmin()) {
        $page->setTemplate("layout/admin_base.tpl.php");
    } else {
        $page->setTemplate("layout/base.tpl.php");
    }
    $page->set("page_title", "Minecraft Coordinate Visualizer");
    $page->set("username", $is_logged_in->getLoggedInUsername());
    $page->set("site_version", SENTIMENTAL_VERSION);

    // Get the inner content
    $inner_page = new \Template(config: $config);
    $inner_page->setTemplate("index.tpl.php");
    $inner_page->set("username", $is_logged_in->getLoggedInUsername());
    $inner_page->set("site_version", SENTIMENTAL_VERSION);
    $page->set("page_content", $inner_page->grabTheGoods());

    $page->echoToScreen();
    exit;
} else {
    // Not logged in - show welcome page
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Minecraft Coordinate Visualizer</title>';
    echo '<style>';
    echo 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; line-height: 1.6; }';
    echo 'h1 { color: #2c3e50; margin-bottom: 10px; }';
    echo '.subtitle { color: #7f8c8d; margin-top: 0; font-size: 1.1em; }';
    echo '.description { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 30px 0; }';
    echo '.features { margin: 20px 0; }';
    echo '.features li { margin: 10px 0; }';
    echo '.cta { margin: 30px 0; }';
    echo '.btn { display: inline-block; padding: 12px 24px; margin: 10px 10px 10px 0; text-decoration: none; border-radius: 6px; font-weight: 500; transition: all 0.2s; }';
    echo '.btn-primary { background: #3498db; color: white; }';
    echo '.btn-primary:hover { background: #2980b9; }';
    echo '.btn-secondary { background: #95a5a6; color: white; }';
    echo '.btn-secondary:hover { background: #7f8c8d; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<h1>🎮 Minecraft Coordinate Visualizer</h1>';
    echo '<p class="subtitle">Visualize your Minecraft coordinates in interactive 3D</p>';
    echo '<div class="description">';
    echo '<p><strong>Transform your Minecraft coordinates into stunning 3D visualizations!</strong></p>';
    echo '<p>This tool helps you:</p>';
    echo '<ul class="features">';
    echo '<li>📍 Plot coordinates in an interactive 3D space</li>';
    echo '<li>🎨 Color-code different locations and paths</li>';
    echo '<li>🔗 Connect points to show paths and tunnels</li>';
    echo '<li>🗺️ Visualize chunk claims (mine vs. unavailable)</li>';
    echo '<li>💾 Save and load your coordinate sets</li>';
    echo '</ul>';
    echo '</div>';
    echo '<div class="cta">';
    echo '<a href="/login/" class="btn btn-primary">Log In</a>';
    echo '<a href="/login/register.php" class="btn btn-secondary">Create Account</a>';
    echo '</div>';
    echo '<p style="color: #7f8c8d; font-size: 0.9em;">Free to get started • No email or credit card required</p>';
    echo '</body>';
    echo '</html>';
    exit;
}
