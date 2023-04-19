<?php
declare(strict_types = 1);

use Mezzio\Session\SessionMiddleware;
use Mezzio\Session\SessionPersistenceInterface;
use Middlewares\AccessLog;
use Middlewares\Uuid;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RKA\Middleware\IpAddress;
use Slim\App;
use Slim\HttpCache\Cache;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return static function (App $app): void {
  $container = $app->getContainer();

  // Add Routing Middleware
  $app->addRoutingMiddleware();

  // Add Body Parsing Middleware
  $app->addBodyParsingMiddleware();

  $app
    ->add(new ContentLengthMiddleware())
    ->add(new Cache('private', 0, true))
    ->add(
      (new AccessLog($container->get(LoggerInterface::class)))
        ->format(AccessLog::FORMAT_COMBINED)
        ->ipAttribute('ipAddr')
        ->context(
          static function (ServerRequestInterface $request, ResponseInterface $response): array {
            return [
              'request-id' => $request->getHeaderLine('X-Request-Id'),
              'key' => $request->getAttribute('session')->get('key')
            ];
          }
        )
    )
    ->add(
      (new Uuid())
        ->header('X-Request-Id')
    )
    ->add(
      new SessionMiddleware(
        $container->get(SessionPersistenceInterface::class)
      )
    )
    ->add(
      TwigMiddleware::createFromContainer(
        $app,
        Twig::class
      )
    )
    ->add(
      new IpAddress(
        true,
        [],
        'ipAddr',
        [
          'X-Real-IP',
          'Forwarded',
          'X-Forwarded',
          'X-Forwarded-For',
          'X-Cluster-Client-Ip',
          'Client-Ip',
          'CF-Connecting-IP',
          'True-Client-IP'
        ]
      )
    );
};
