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

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command;
use Interim\Temporary;
use Symfony\Component\Console\Application;

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

$dependencyFactory = DependencyFactory::fromEntityManager(
  new ConfigurationArray(
    [
      'migrations_paths' => [
        'Project\\Skel\\Db' => __ROOT__ . '/db'
      ],
      'all_or_nothing'          => true,
      'transactional'           => true,
      'check_database_platform' => true,
      'organize_migrations'     => 'none'
    ]
  ),
  new ExistingEntityManager(
    $container->get(EntityManagerInterface::class)
  )
);

$app = new Application('Doctrine Migrations');
$app->setCatchExceptions(true);

$app->addCommands(
  [
    new Command\CurrentCommand($dependencyFactory),
    new Command\DiffCommand($dependencyFactory),
    new Command\DumpSchemaCommand($dependencyFactory),
    new Command\ExecuteCommand($dependencyFactory),
    new Command\GenerateCommand($dependencyFactory),
    new Command\LatestCommand($dependencyFactory),
    new Command\ListCommand($dependencyFactory),
    new Command\MigrateCommand($dependencyFactory),
    new Command\RollupCommand($dependencyFactory),
    new Command\StatusCommand($dependencyFactory),
    new Command\SyncMetadataCommand($dependencyFactory),
    new Command\UpToDateCommand($dependencyFactory),
    new Command\VersionCommand($dependencyFactory)
  ]
);

$app->run();
