<div class="PagePanel">
    What's up <?= $username ?>? <br />
</div>
<h1>Welcome to the MarbleTrack3 Admin Dashboard</h1>
<p>This page can show numbers of workers, parts, snippets, etc</p>

<div class="admin-tools">
    <h3>Database Tools</h3>
    <p><a href="/phpmyadmin-login.php" target="_blank">🔗 Quick Login to PHPMyAdmin</a></p>
</div>

<?php
if ($has_pending_migrations) {
        echo "<h3>Pending DB Migrations</h3>";
        echo "<a href='/admin/migrate_tables.php'>Click here to migrate tables</a>";
    }
?>

<div class="fix">
    <p>Sentimental version: <?= $site_version ?></p>
</div>
