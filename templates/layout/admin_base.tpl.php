<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content=""/>
    <title><?= $page_title ?? 'MarbleTrack3 Admin' ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/menu.css">
    <link rel="stylesheet" href="/css/mc.css">
</head>
<body>
    <div class="NavBar">
        <a href="/">View Site</a> |
        <?php $menu_admin_count = (int) ($admin_count ?? 0); ?>
        <?php if ($menu_admin_count > 0) : ?>
            <a href="/admin/" aria-label="Admin Site, <?= $menu_admin_count ?> waiting for an admin">Admin Site
                <span class="AdminCount" aria-hidden="true"><?= $menu_admin_count ?></span></a> |
        <?php else : ?>
            <a href="/admin/">Admin Site</a> |
        <?php endif; ?>
        <a href="/admin/workers">Workers</a> |
        <div class="dropdown">
            <a href="/profile/">Profile ▾</a>
            <div class="dropdown-menu">
                <a href="/logout/">Logout</a>
            </div>
        </div>
    </div>
    <div class="PageWrapper">
        <?= $page_content ?>
    </div>
</body>
</html>
