<?php
/**
 * Auto-login to PHPMyAdmin
 * This page creates an auto-submitting form that logs into PHPMyAdmin
 * using credentials from classes/Config.php
 */

require_once(__DIR__ . '/../classes/Config.php');

$config = new Config();

// PHPMyAdmin login endpoint
$phpmyadminUrl = 'https://west1-phpmyadmin.dreamhost.com/signon.php?lang=en';
$serverName = 'eich.robnugen.com';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging into PHPMyAdmin...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Logging into PHPMyAdmin...</h1>
        <div class="spinner"></div>
        <p>Please wait while we redirect you...</p>
    </div>

    <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($phpmyadminUrl); ?>">
        <input type="hidden" name="pma_username" value="<?php echo htmlspecialchars($config->dbUser); ?>">
        <input type="hidden" name="pma_password" value="<?php echo htmlspecialchars($config->dbPass); ?>">
        <input type="hidden" name="pma_servername" value="<?php echo htmlspecialchars($serverName); ?>">
        <input type="hidden" name="server" value="1">
        <input type="hidden" name="lang" value="en">
    </form>

    <script>
        // Auto-submit the form when the page loads
        window.onload = function() {
            document.getElementById('loginForm').submit();
        };
    </script>
</body>
</html>
