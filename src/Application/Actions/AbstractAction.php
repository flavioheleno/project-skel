<?php
declare(strict_types = 1);

namespace FlavioHeleno\ProjectSkel\Application\Actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode\Http;

abstract class AbstractAction {
  abstract public function __invoke(
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args = []
  ): ResponseInterface;

  public function respondWithRedirect(
    ResponseInterface $response,
    string $url,
    int $statusCode = Http::FOUND
  ): ResponseInterface {
    return $response
      ->withHeader('Location', $url)
      ->withStatus($statusCode);
  }

  public function respondWithData(
    ResponseInterface $response,
    array $data,
    int $statusCode = Http::OK
  ): ResponseInterface {
    $json = ['status' => true];
    if (array_is_list($data)) {
      $json['list'] = $data;
    } else {
      $json['data'] = $data;
    }

    $response->getBody()->write(
      json_encode(
        $json,
        JSON_UNESCAPED_SLASHES | JSON_BIGINT_AS_STRING | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
      )
    );

    return $response
      ->withHeader('Content-Type', 'application/json; charset=utf-8')
      ->withStatus($statusCode);
  }

  public function respondWithError(
    ResponseInterface $response,
    array $data,
    int $statusCode = Http::BAD_REQUEST
  ): ResponseInterface {
    $json = ['status' => false];
    if (array_is_list($data)) {
      $json['list'] = $data;
    } else {
      $json['data'] = $data;
    }

    $response->getBody()->write(
      json_encode(
        $json,
        JSON_UNESCAPED_SLASHES | JSON_BIGINT_AS_STRING | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
      )
    );

    return $response
      ->withHeader('Content-Type', 'application/json; charset=utf-8')
      ->withStatus($statusCode);
  }

  public function respondWithHtml(
    ResponseInterface $response,
    string $html,
    int $statusCode = Http::OK
  ): ResponseInterface {
    $response->getBody()->write($html);

    return $response
      ->withHeader('Content-Type', 'text/html; charset=utf-8')
      ->withStatus($statusCode);
  }
}
