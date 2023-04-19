<?php
declare(strict_types = 1);

namespace FlavioHeleno\ProjectSkel\Application\Actions\Web;

use FlavioHeleno\ProjectSkel\Application\Actions\AbstractAction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

final class IndexAction extends AbstractAction {
  private Twig $twig;

  public function __construct(Twig $twig) {
    $this->twig = $twig;
  }

  public function __invoke(
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args = []
  ): ResponseInterface {
    return $this->respondWithHtml(
      $response,
      $this->twig->fetch('web/index.twig', ['timestamp' => time()])
    );
  }
}
