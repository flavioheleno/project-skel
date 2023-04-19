<?php
declare(strict_types = 1);

use FlavioHeleno\ProjectSkel\Application\Actions\API\IndexAction as APIIndexAction;
use FlavioHeleno\ProjectSkel\Application\Actions\Web\IndexAction as WebIndexAction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Teapot\StatusCode\Http;

return static function (App $app): void {
  /**
   * Generic CORS
   */
  $app->options(
    '/{routes:.*}',
    function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
      // CORS Pre-Flight OPTIONS Request Handler
      return $response;
    }
  );

  $app->redirect('/', '/web', Http::TEMPORARY_REDIRECT);
  $app->get('/web', WebIndexAction::class);
  $app->get('/api', APIIndexAction::class);

  /**
   * Generic catch-all 404
   */
  $app->map(
    [
      'GET',
      'POST',
      'PUT',
      'DELETE',
      'PATCH'
    ],
    '/{routes:.+}',
    function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
      throw new HttpNotFoundException(
        $request,
        sprintf(
          'Route not found: "%s"',
          (string)$request->getUri()
        )
      );
    }
  );
};
