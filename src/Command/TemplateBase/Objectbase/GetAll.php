<?php

declare(strict_types=1);

namespace App\Controller\Objectbase;

use App\CustomResponse as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetAll extends Base
{
    public function __invoke(Request $request, Response $response): Response
    {
        $objectbases = $this->getObjectbaseService()->getAll();

        return $response->withJson($objectbases);
    }
}
