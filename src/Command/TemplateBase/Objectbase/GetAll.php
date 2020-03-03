<?php

declare(strict_types=1);

namespace App\Controller\Objectbase;

class GetAll extends Base
{
    public function __invoke($request, $response)
    {
        $objectbases = $this->getObjectbaseService()->getAll();

        $payload = json_encode($objectbases);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
