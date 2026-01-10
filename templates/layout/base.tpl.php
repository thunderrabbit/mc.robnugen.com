<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content=""/>
    <title><?= $page_title ?? 'MarbleTrack3' ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/menu.css">
    <link rel="stylesheet" href="/css/mc.css">
</head>
<body>
    <?php if (!isset($hide_navbar) || !$hide_navbar): ?>
    <div class="NavBar">
        <a href="/">View Site</a> |
        <div class="dropdown">
            <a href="/profile/">Profile ▾</a>
            <div class="dropdown-menu">
                <a href="/logout/">Logout</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="PageWrapper">
        <?= $page_content ?>
    </div>
</body>
</html>

