<?php

declare(strict_types=1);

namespace App\Controller\Objectbase;

use App\Helper\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class Create extends Base
{
    public function __invoke(Request $request, Response $response): Response
    {
        $input = (array) $request->getParsedBody();
        $objectbase = $this->getObjectbaseService()->create($input);

        return JsonResponse::withJson($response, (string) json_encode($objectbase), 201);
    }
}
