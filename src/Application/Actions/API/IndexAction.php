<?php
declare(strict_types = 1);

namespace FlavioHeleno\ProjectSkel\Application\Actions\API;

use FlavioHeleno\ProjectSkel\Application\Actions\AbstractAction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class IndexAction extends AbstractAction {
  public function __invoke(
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args = []
  ): ResponseInterface {
    return $this->respondWithData(
      $response,
      [
        'message' => 'Welcome to FlavioHeleno\ProjectSkel [API].',
        'timestamp' => time()
      ]
    );
  }
}
