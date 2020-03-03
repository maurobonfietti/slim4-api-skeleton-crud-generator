<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class CrudGeneratorCommand extends Command
{
    const COMMAND_VERSION = '0.7.0';

    public function __construct($app)
    {
        parent::__construct();
        $this->container = $app->getContainer();
    }

    protected function configure()
    {
        $this->setName('api:generate:endpoints')
            ->setDescription('Given an entity, autogenerate a simple CRUD/REST endpoints.')
            ->setHelp('This command generate RESTful endpoints, to manage any entity. Version: ' . self::COMMAND_VERSION)
            ->addArgument(
                'entity',
                InputArgument::REQUIRED,
                'Enter the name for the entity or table, to generate endpoints.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = $this->container->get('db');
        $entity = $input->getArgument('entity');
        $output->writeln('OK - Generated endpoints for entity: ' . $entity);
        $generator = new CrudGeneratorService();
        $generator->generateCrud($db, $entity);
    }
}
