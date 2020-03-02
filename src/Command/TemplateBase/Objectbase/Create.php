<?php

declare(strict_types=1);

namespace App\Controller\Objectbase;

class Create extends Base
{
    public function __invoke($request, $response)
    {
        $input = $request->getParsedBody();
        $objectbase = $this->getObjectbaseService()->createObjectbase($input);

        $payload = json_encode($objectbase);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}
