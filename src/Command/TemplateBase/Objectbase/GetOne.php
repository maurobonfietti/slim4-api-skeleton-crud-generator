<?php

declare(strict_types=1);

namespace App\Controller\Objectbase;

class GetOne extends Base
{
    public function __invoke($request, $response, array $args)
    {
        $objectbase = $this->getObjectbaseService()->getObjectbase((int) $args['id']);

        $payload = json_encode($objectbase);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
