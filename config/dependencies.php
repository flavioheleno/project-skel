<?php
declare(strict_types = 1);

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use FlavioHeleno\ProjectSkel\Application\Settings\SettingsInterface;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Serializer\ArraySerializer;
use Mezzio\Session\Cache\CacheSessionPersistence;
use Mezzio\Session\Ext\PhpSessionPersistence;
use Mezzio\Session\SessionPersistenceInterface;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

return static function (ContainerBuilder $containerBuilder): void {
  $containerBuilder->addDefinitions(
    [
      CacheItemPoolInterface::class => static function (ContainerInterface $container): CacheItemPoolInterface {
        $settings = $container->get(SettingsInterface::class);

        // disables cache by using a null adapter
        if ($settings->has('cache') === false || $settings->getBool('cache.enabled', false) === false) {
          return new NullAdapter();
        }

        $adapters = [
          new ArrayAdapter(
            // the default lifetime (in seconds) for cache items that do not define their
            // own lifetime, with a value 0 causing items to be stored indefinitely (i.e.
            // until the current PHP process finishes)
            defaultLifetime: 0,

            // if ``true``, the values saved in the cache are serialized before storing them
            storeSerialized: true,

            // the maximum lifetime (in seconds) of the entire cache (after this time, the
            // entire cache is deleted to avoid stale data from consuming memory)
            maxLifetime: 0,

            // the maximum number of items that can be stored in the cache. When the limit
            // is reached, cache follows the LRU model (least recently used items are deleted)
            maxItems: 0
          )
        ];

        if ($settings->has('cache.apcu') && $settings->getBool('cache.apcu.enabled', false)) {
          $adapters[] = new ApcuAdapter();
        }

        if ($settings->has('cache.redis') && $settings->getBool('cache.redis.enabled', false)) {
          $adapters[] = new RedisAdapter(
            RedisAdapter::createConnection($settings->getString('cache.redis.dsn'))
          );
        }

        return new ChainAdapter($adapters);
      },
      EntityManagerInterface::class => static function (ContainerInterface $container): EntityManagerInterface {
        $settings = $container->get(SettingsInterface::class);

        return EntityManager::create(
          $settings->getSection('db.connection'),
          ORMSetup::createAttributeMetadataConfiguration(
            $settings->getSection('db.metadata-dirs'),
            $settings->getBool('db.dev-mode'),
            null,
            null
          )
        );
      },
      FractalManager::class => static function (ContainerInterface $container): FractalManager {
        $fractalManager = new FractalManager();
        $fractalManager->setSerializer(new ArraySerializer());

        return $fractalManager;
      },
      HashidsInterface::class => static function (ContainerInterface $container): HashidsInterface {
        $settings = $container->get(SettingsInterface::class);

        return new Hashids(
          $settings->getString('hashids.salt', 'h45h1d5-f0r-fun'),
          $settings->getInt('hashids.length', 10)
        );
      },
      LoggerInterface::class => static function (ContainerInterface $container): LoggerInterface {
        $logger = new Logger('sso');

        if (PHP_SAPI !== 'cli') {
          $logger->pushProcessor(new WebProcessor());
        }

        $logger
          ->pushProcessor(new MemoryPeakUsageProcessor())
          ->pushProcessor(new MemoryUsageProcessor());

        // running in a container, logs go to stdout
        if (isset($_ENV['DOCKER']) === true) {
          $logger->pushHandler(
            new StreamHandler(
              'php://stdout',
              ($_ENV['PHP_ENV'] === 'prod' ? Logger::INFO : Logger::DEBUG)
            )
          );

          return $logger;
        }

        // running from cli-server (dev mode), logs go to error_log()
        if ($_ENV['PHP_ENV'] === 'dev' && PHP_SAPI === 'cli-server') {
          $logger->pushHandler(
            new ErrorLogHandler(
              ErrorLogHandler::SAPI,
              Logger::DEBUG
            )
          );

          return $logger;
        }

        // fallback, logs go to /path/to/logs/access.log
        $settings = $container->get(SettingsInterface::class);
        $logPath = $settings->getString('paths.log');
        if (is_dir($logPath) === false && mkdir($logPath, recursive: true) === false) {
          throw new RuntimeException('Failed to create log storage');
        }

        $logger->pushHandler(
          new StreamHandler(
            fopen("{$logPath}/access.log", 'a'),
            ($_ENV['PHP_ENV'] === 'prod' ? Logger::INFO : Logger::DEBUG)
          )
        );

        return $logger;
      },
      SessionPersistenceInterface::class => static function (ContainerInterface $container): SessionPersistenceInterface {
        $settings = $container->get(SettingsInterface::class);

        // disables cache by using PHP's session persistence
        if ($settings->has('cache') === false || $settings->getBool('cache.enabled', false) === false) {
          $sessionPath = $settings->getString('paths.session');
          if (is_dir($sessionPath) === false && mkdir($sessionPath, recursive: true) === false) {
            throw new RuntimeException('Failed to create session storage');
          }

          ini_set('session.name', 'sso-kahu-app');
          ini_set('session.cookie_secure', ($_ENV['PHP_ENV'] === 'prod'));
          ini_set('session.cookie_httponly', true);
          ini_set('session.cookie_samesite', 'Lax');
          ini_set('session.save_path', $sessionPath);

          return new PhpSessionPersistence(true);
        }

        return new CacheSessionPersistence(
          $container->get(CacheItemPoolInterface::class),
          cookieName: 'sso-kahu-app',
          cookieSecure: ($_ENV['PHP_ENV'] === 'prod'),
          cookieHttpOnly: true,
          // cookieSameSite: 'Strict'
        );
      },
      Twig::class => static function (ContainerInterface $container): Twig {
        $settings = $container->get(SettingsInterface::class);
        $cachePath = $settings->getString('paths.cache');
        if (is_dir($cachePath) === false && mkdir($cachePath, recursive: true) === false) {
          throw new RuntimeException('Failed to create cache storage');
        }

        return Twig::create(
          __ROOT__ . '/resources/views',
          [
            'cache' => ($_ENV['PHP_ENV'] === 'prod' ? $cachePath : false)
          ]
        );
      }
    ]
  );
};
