<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

// Sample page - no authentication required, anonymous users can try the visualizer
$page = new \Template(config: $config);
$page->setTemplate("layout/base.tpl.php");
$page->set("page_title", "Try It - Minecraft Coordinate Visualizer");
$page->set("hide_navbar", true); // Hide navbar for anonymous users

// Get the inner content
$inner_page = new \Template(config: $config);
$inner_page->setTemplate("index.tpl.php");
$inner_page->set("username", "Guest");
$inner_page->set("is_sample_mode", true); // Flag to disable save/load functionality
$inner_page->set("site_version", SENTIMENTAL_VERSION);
$page->set("page_content", $inner_page->grabTheGoods());

$page->echoToScreen();
exit;
