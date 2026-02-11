<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\SchemaTool;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/.env')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG'] ?? ('prod' !== $_SERVER['APP_ENV'])) {
    umask(0000);
    Debug::enable();
}

$env = $_SERVER['APP_ENV'] ?? 'dev';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? true);

$kernel = new Kernel($env, $debug);
$request = Request::createFromGlobals();
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "<!DOCTYPE html><html><head><title>FitSense - DB Fix</title><style>
    body { font-family: sans-serif; background: #111; color: #eee; padding: 20px; line-height: 1.6; }
    pre { background: #222; padding: 15px; border-radius: 8px; border: 1px solid #444; overflow-x: auto; color: #38BDF8; }
    .success { color: #4ADE80; font-weight: bold; border: 1px solid #4ADE80; padding: 10px; border-radius: 4px; }
    .error { color: #FB7185; font-weight: bold; border: 1px solid #FB7185; padding: 10px; border-radius: 4px; }
    h1 { color: #A78BFA; }
</style></head><body>";
echo "<h1>FitSense - Database Schema Fix</h1>";

try {
    $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();
    $schemaTool = new SchemaTool($entityManager);
    
    echo "<p>Analyzing entity metadata and database schema...</p>";
    
    $sqls = $schemaTool->getUpdateSchemaSql($metadatas, true);
    
    if (empty($sqls)) {
        echo "<div class='success'>Schema is already up to date!</div>";
    } else {
        echo "<h3>Executing the following SQL:</h3>";
        echo "<pre>" . implode(";\n", $sqls) . "</pre>";
        
        $schemaTool->updateSchema($metadatas, true);
        echo "<div class='success'>Success! Schema updated successfully.</div>";
        echo "<p>Number of statements executed: " . count($sqls) . "</p>";
    }
} catch (\Exception $e) {
    echo "<div class='error'>Error updating schema:</div>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "\n\nTrace:\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr><p>Please inform the assistant once this page shows 'Success'. Then DELETE this file (<code>public/db_fix.php</code>) for security.</p>";
echo "</body></html>";
