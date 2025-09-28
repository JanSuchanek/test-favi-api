<?php
declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// bootEnv exists on recent Symfony versions; call directly
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Ensure DATABASE_URL is set for tests (fallback to local sqlite file)
if (!getenv('DATABASE_URL') && !isset($_ENV['DATABASE_URL'])) {
    $db = 'sqlite:///' . dirname(__DIR__) . '/var/data.db';
    putenv('DATABASE_URL=' . $db);
    $_ENV['DATABASE_URL'] = $db;
    $_SERVER['DATABASE_URL'] = $db;
}
