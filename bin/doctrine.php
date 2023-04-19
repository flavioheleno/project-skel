#!/usr/bin/env php
<?php
declare(strict_types = 1);

date_default_timezone_set('UTC');
setlocale(LC_ALL, 'en_US.UTF8');

// ensure correct absolute path
chdir(dirname($argv[0]));

define('__ROOT__', dirname(__DIR__));
define('__BIN__', __ROOT__ . '/bin');
define('__CONF__', __ROOT__ . '/config');

require_once __ROOT__ . '/vendor/autoload.php';

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Interim\Temporary;

// default PHP_ENV to "prod"
if (isset($_ENV['PHP_ENV']) === false) {
  $_ENV['PHP_ENV'] = 'prod';
}

if (is_file(__ROOT__ . '/.env')) {
  $dotenv = Dotenv\Dotenv::createImmutable(__ROOT__);
  $dotenv->safeLoad();
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

// Build PHP-DI Container instance
$container = $containerBuilder->build();

ConsoleRunner::run(
  new SingleManagerProvider($container->get(EntityManagerInterface::class)),
  []
);
