<?php
declare(strict_types = 1);

use DI\ContainerBuilder;
use FlavioHeleno\ProjectSkel\Application\Settings\Settings;
use FlavioHeleno\ProjectSkel\Application\Settings\SettingsInterface;
use Interim\Temporary;

return static function (ContainerBuilder $containerBuilder): void {
  $containerBuilder->addDefinitions(
    [
      SettingsInterface::class => static function (): SettingsInterface {
        return new Settings(
          [
            'cache' => [
              'enabled' => in_array(PHP_SAPI, ['cli', 'cli-server'], true) === false,
              'apcu' => [
                'enabled' => extension_loaded('apcu') && apcu_enabled()
              ],
              'redis' => [
                'enabled' => extension_loaded('redis') && isset($_ENV['REDIS_DSN']),
                'dsn' => $_ENV['REDIS_DSN'] ?? ''
              ]
            ],
            'db' => [
              // Enables or disables Doctrine metadata caching
              // for either performance or convenience during development.
              'dev-mode' => ($_ENV['PHP_ENV'] === 'dev'),
              // Path where Doctrine will cache the processed metadata
              // when "dev-mode" is false.
              'cache-dir' => Temporary::getDirectory() . '/cache',
              // List of paths where Doctrine will search for metadata.
              'metadata-dirs' => [__ROOT__ . '/src/Domain'],
              // The parameters Doctrine needs to connect to your database.
              // These parameters depend on the driver (for instance the 'pdo_sqlite' driver
              // needs a 'path' parameter and doesn't use most of the ones shown in this example).
              'connection' => [
                'driver' => 'pdo_pgsql',
                'host' => $_ENV['POSTGRES_HOST'] ?? 'localhost',
                'port' => $_ENV['POSTGRES_PORT'] ?? 5432,
                'dbname' => $_ENV['POSTGRES_DB'] ?? 'postgres',
                'user' => $_ENV['POSTGRES_USER'] ?? 'postgres',
                'password' => $_ENV['POSTGRES_PASSWORD'] ?? ''
              ]
            ],
            'hashids' => [
              'salt' => 'f243feb983a8c0856e1b',
              'length' => 10
            ],
            'paths' => [
              'cache' => Temporary::getDirectory() . '/cache',
              'log' => Temporary::getDirectory() . '/log',
              'report' => Temporary::getDirectory() . '/reports',
              'session' => Temporary::getDirectory() . '/session',
              'storage' => Temporary::getDirectory() . '/storage',
              'upload' => Temporary::getDirectory() . '/uploads'
            ],
            'slim' => [
              // Returns a detailed HTML page with error details and
              // a stack trace. Should be disabled in production.
              'displayErrorDetails' => ($_ENV['PHP_ENV'] === 'dev'),
              // Whether to display errors on the internal PHP log or not.
              'logErrors' => true,
              // If true, display full errors with message and stack trace on the PHP log.
              // If false, display only "Slim Application Error" on the PHP log.
              // Doesn't do anything when "logErrors" is false.
              'logErrorDetails' => true
            ]
          ]
        );
      }
    ]
  );
};
