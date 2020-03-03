<?php

declare(strict_types=1);

namespace App\Controller\Objectbase;

class Update extends Base
{
    public function __invoke($request, $response, array $args)
    {
        $input = $request->getParsedBody();
        $objectbase = $this->getObjectbaseService()->update($input, (int) $args['id']);

        $payload = json_encode($objectbase);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
