<?php

declare(strict_types=1);

namespace App\Controller\Objectbase;

use App\Lib\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class Delete extends Base
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->getObjectbaseService()->delete((int) $args['id']);

        return JsonResponse::withJson($response, '', 204);
    }
}
