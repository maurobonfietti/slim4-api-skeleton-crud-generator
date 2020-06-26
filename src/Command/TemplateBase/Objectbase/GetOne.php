<?php

declare(strict_types=1);

namespace App\Controller\Objectbase;

use App\Helper\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetOne extends Base
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $objectbase = $this->getObjectbaseService()->getOne((int) $args['id']);

        return JsonResponse::withJson($response, json_encode($objectbase));
    }
}
