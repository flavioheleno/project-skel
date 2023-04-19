#!/usr/bin/env php
<?php
declare(strict_types = 1);

date_default_timezone_set('UTC');
setlocale(LC_ALL, 'en_US.UTF8');

// ensure correct absolute path
chdir(dirname($argv[0]));

define('__ROOT__', dirname(__DIR__));
define('__BIN__', __DIR__);
define('__CONF__', __ROOT__ . '/config');

require_once __ROOT__ . '/vendor/autoload.php';

use Composer\InstalledVersions;
use DI\ContainerBuilder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;

// default PHP_ENV to "prod"
if (isset($_ENV['PHP_ENV']) === false) {
  $_ENV['PHP_ENV'] = 'prod';
}

$version = $_ENV['VERSION'] ?? '';
if ($version === '') {
  $version = InstalledVersions::getVersion('flavioheleno/project-skel');
  if (str_starts_with(InstalledVersions::getPrettyVersion('flavioheleno/project-skel'), 'dev-')) {
    $version = sprintf(
      '%s-%s',
      substr(InstalledVersions::getPrettyVersion('flavioheleno/project-skel'), 4),
      substr(InstalledVersions::getReference('flavioheleno/project-skel'), 0, 7)
    );
  }
}

define('__VERSION__', $version);

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

// Set up repositories
// $repositories = require_once __CONF__ . '/repositories.php';
// $repositories($containerBuilder);

// Set up processors (handlers and listeners)
// $processors = require_once __CONF__ . '/processors.php';
// $processors($containerBuilder);

// Set up services
// $services = require_once __CONF__ . '/services.php';
// $services($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Register messages (commands and events)
$messages = require_once __CONF__ . '/messages.php';
$messages($container);

$app = new Application('project console', __VERSION__);
$app->setCatchExceptions(true);
$app->setCommandLoader(
  new FactoryCommandLoader(
    [
      SendEventCommand::getDefaultName() => static function () use ($container): SendEventCommand {
        return $container->get(SendEventCommand::class);
      }
    ]
  )
);

$app->run();
