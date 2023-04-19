<?php
declare(strict_types = 1);

use DI\ContainerBuilder;
use Interim\Temporary;
use Slim\Factory\AppFactory;

define('__ROOT__', dirname(__DIR__));
define('__BIN__', __ROOT__ . '/bin');
define('__CONF__', __ROOT__ . '/config');

require_once __ROOT__ . '/vendor/autoload.php';

// default PHP_ENV to "prod"
if (isset($_ENV['PHP_ENV']) === false) {
  $_ENV['PHP_ENV'] = 'prod';
}

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

if ($_ENV['PHP_ENV'] === 'prod') {
  $containerBuilder
    ->enableCompilation(Temporary::getDirectory() . '/cache')
    ->writeProxiesToFile(true, Temporary::getDirectory() . '/proxies');
}

// Set up settings
$settings = require_once __CONF__ . '/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require_once __CONF__ . '/dependencies.php';
$dependencies($containerBuilder);

// Set up custom repositories
// $repositories = require __CONF__ . '/repositories.php';
// $repositories($containerBuilder);

// Set up processors (handlers and listeners)
// $processors = require __CONF__ . '/processors.php';
// $processors($containerBuilder);

// Set up services
// $services = require __CONF__ . '/services.php';
// $services($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Register messages (commands and events)
// $messages = require __CONF__ . '/messages.php';
// $messages($container);

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();

// Register middleware
$middleware = require_once __CONF__ . '/middleware.php';
$middleware($app);

// Register routes
$routes = require_once __CONF__ . '/routes.php';
$routes($app);

if ($_ENV['PHP_ENV'] === 'prod') {
  $routeCollector = $app->getRouteCollector();
  $routeCollector->setCacheFile(Temporary::getDirectory() . '/routes');
}

$app->run();
