<?php

declare(strict_types=1);

namespace App\Controller\Objectbase;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class Update extends Base
{
    /**
     * @param array<string> $args
     */
    public function __invoke(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $input = (array) $request->getParsedBody();
        $objectbase = $this->getObjectbaseService()->update($input, (int) $args['id']);

        return $response->withJson($objectbase);
    }
}
