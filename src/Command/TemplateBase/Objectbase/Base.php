<?php

declare(strict_types=1);

namespace App\Controller\Objectbase;

use App\Service\ObjectbaseService;
use Pimple\Psr11\Container;

abstract class Base
{
    protected Container $container;

    protected ObjectbaseService $objectbaseService;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    protected function getObjectbaseService(): ObjectbaseService
    {
        return $this->container->get('objectbase_service');
    }
}
