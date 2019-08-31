<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class CrudGeneratorCommand extends Command
{
    const COMMAND_VERSION = '0.0.9';

    private $entity;

    private $entityNameUpper;

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
        $output->writeln('Starting!');
        $this->entity = $input->getArgument('entity');
        $this->entityNameUpper = ucfirst($this->entity);
        $output->writeln('Generate Endpoints For New Entity: ' . $this->entity);
        $this->generateCrud();
        $output->writeln('Script Finish ;-)');
    }

    protected function generateCrud()
    {
        // Get Entity Fields.
        $db = $this->container->get('db');
        $query = "DESC `$this->entity`";
        $statement = $db->prepare($query);
        $statement->execute();
        $fields = $statement->fetchAll();

        // Get Insert and Update Functions, using each fields of the entity.
        $repositoryFunctions = $this->getRepositoryFunctions($fields);
        $insertQueryFunction = $repositoryFunctions[0];
        $updateQueryFunction = $repositoryFunctions[1];

        // Add and Update Routes.
        $this->updateRoutes();

        // Add and Update Repository.
        $this->updateRepository();

        // Add and Update Services.
        $this->updateServices();

        // Generate Controller Files.
        $this->generateControllerFiles();

        // Replace and Update Exceptions.
        $this->updateExceptions();

        // Replace and Update Services.
        $this->updateServices2();

        // Replace and Update Repository.
        $this->updateRepository2();

        // Replace and Update Repository with Insert and Update Query Functions.
        $this->updateRepository3($insertQueryFunction, $updateQueryFunction);

        // Create Integration Tests for new endpoints.
        $this->generateIntegrationTests($repositoryFunctions[2]);
    }

    private function getRepositoryFunctions($fields)
    {
        // Get Dynamic Params and Fields List.
        $paramList = '';
        $paramList2 = '';
        $paramList3 = '';
        $paramList4 = '';
        $paramList5 = '';
        $paramList6 = '';
        foreach ($fields as $field) {
            $paramList.= sprintf("`%s`, ", $field['Field']);
            $paramList2.= sprintf(":%s, ", $field['Field']);
            $paramList3.= sprintf('$statement->bindParam(\'%s\', $%s->%s);%s', $field['Field'], $this->entity, $field['Field'], PHP_EOL);
            $paramList3.= sprintf("%'\t1s", '');
            if ($field['Field'] != 'id') {
                $paramList4.= sprintf("`%s` = :%s, ", $field['Field'], $field['Field']);
                $paramList5.= sprintf("if (isset(\$data->%s)) { $%s->%s = \$data->%s; }%s", $field['Field'], $this->entity, $field['Field'], $field['Field'], PHP_EOL);
                $paramList5.= sprintf("%'\t1s", '');
                if ($field['Null'] == "NO" && strpos($field['Type'], 'varchar') !== false) {
                    $paramList6.= sprintf("'%s' => '%s',%s", $field['Field'], 'aaa', PHP_EOL);
                    $paramList6.= sprintf("%'\t2s", '');
                }
                if ($field['Null'] == "NO" && strpos($field['Type'], 'int') !== false) {
                    $paramList6.= sprintf("'%s' => %s,%s", $field['Field'], 1, PHP_EOL);
                    $paramList6.= sprintf("%'\t2s", '');
                }
            }
        }
        $fieldList = substr_replace($paramList, '', -2);
        $fieldList2 = substr_replace($paramList2, '', -2);
        $fieldList3 = substr_replace($paramList3, '', -2);
        $fieldList4 = substr_replace($paramList4, '', -2);
        $fieldList5 = substr_replace($paramList5, '', -2);
        $fieldList6 = substr_replace($paramList6, '', -3);

        // Get Base Query For Insert Function.
        $insertQueryFunction = '$query = \'INSERT INTO `'.$this->entity.'` ('.$fieldList.') VALUES ('.$fieldList2.')\';
        $statement = $this->getDb()->prepare($query);
        '.$fieldList3.'
        $statement->execute();

        return $this->checkAndGet'.$this->entityNameUpper.'((int) $this->getDb()->lastInsertId());';

        // Get Base Query For Update Function.
        $updateQueryFunction = ''.$fieldList5.'

        $query = \'UPDATE `'.$this->entity.'` SET '.$fieldList4.' WHERE `id` = :id\';
        $statement = $this->getDb()->prepare($query);
        '.$fieldList3.'
        $statement->execute();

        return $this->checkAndGet'.$this->entityNameUpper.'((int) $'.$this->entity.'->id);';

        return [$insertQueryFunction, $updateQueryFunction, $fieldList6];
    }

    private function updateRoutes()
    {
        $routes = '
$app->group("/'.$this->entity.'", function () use ($app) {
    $app->get("", "App\Controller\\'.$this->entityNameUpper.'\GetAll");
    $app->get("/[{id}]", "App\Controller\\'.$this->entityNameUpper.'\GetOne");
    $app->post("", "App\Controller\\'.$this->entityNameUpper.'\Create");
    $app->put("/[{id}]", "App\Controller\\'.$this->entityNameUpper.'\Update");
    $app->delete("/[{id}]", "App\Controller\\'.$this->entityNameUpper.'\Delete");
});
';
        $file = __DIR__ . '/../../../../../src/App/Routes.php';
        $content = file_get_contents($file);
        $content.= $routes;
        file_put_contents($file, $content);
    }

    private function updateRepository()
    {
        $repository = '
$container["'.$this->entity.'_repository"] = function (ContainerInterface $container): App\Repository\\'.$this->entityNameUpper.'Repository {
    return new App\Repository\\'.$this->entityNameUpper.'Repository($container->get("db"));
};
';
        $file = __DIR__ . '/../../../../../src/App/Repositories.php';
        $repositoryContent = file_get_contents($file);
        $repositoryContent.= $repository;
        file_put_contents($file, $repositoryContent);
    }

    private function updateServices()
    {
        $service = '
$container["'.$this->entity.'_service"] = function (ContainerInterface $container): App\Service\\'.$this->entityNameUpper.'Service {
    return new App\Service\\'.$this->entityNameUpper.'Service($container->get("'.$this->entity.'_repository"));
};
';
        $file = __DIR__ . '/../../../../../src/App/Services.php';
        $serviceContent = file_get_contents($file);
        $serviceContent.= $service;
        file_put_contents($file, $serviceContent);
    }

    private function generateControllerFiles()
    {
        // Copy CRUD Template.
        $source = __DIR__ . '/../Command/TemplateBase/Objectbase';
        $target = __DIR__ . '/../../../../../src/Controller/' . $this->entityNameUpper;
        shell_exec("cp -r $source $target");

        // Replace CRUD Controller Template for New Entity.
        $base = $target . '/Base.php';
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityNameUpper/g' $base");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $base");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityNameUpper/g' $target/Create.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target/Create.php");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityNameUpper/g' $target/Delete.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target/Delete.php");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityNameUpper/g' $target/GetAll.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target/GetAll.php");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityNameUpper/g' $target/GetOne.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target/GetOne.php");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityNameUpper/g' $target/Update.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target/Update.php");

        // Remove Any Temp Files.
        shell_exec("rm -f $target/*.bkp");
    }

    private function updateExceptions()
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseException.php';
        $target = __DIR__ . '/../../../../../src/Exception/' . $this->entityNameUpper . 'Exception.php';
        shell_exec("cp $source $target");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityNameUpper/g' $target");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target");
        shell_exec("rm -f $target.bkp");
    }

    private function updateServices2()
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseService.php';
        $target = __DIR__ . '/../../../../../src/Service/' . $this->entityNameUpper . 'Service.php';
        shell_exec("cp $source $target");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityNameUpper/g' $target");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target");
        shell_exec("rm -f $target.bkp");
    }

    private function updateRepository2()
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseRepository.php';
        $target = __DIR__ . '/../../../../../src/Repository/' . $this->entityNameUpper . 'Repository.php';
        shell_exec("cp $source $target");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityNameUpper/g' $target");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target");
        shell_exec("rm -f $target.bkp");
    }

    private function updateRepository3($insertQueryFunction, $updateQueryFunction)
    {
        $target = __DIR__ . '/../../../../../src/Repository/' . $this->entityNameUpper . 'Repository.php';

        $entityRepository = file_get_contents($target);
        $repositoryData = preg_replace("/".'#createFunction'."/", $insertQueryFunction, $entityRepository);
        file_put_contents($target, $repositoryData);

        $entityRepositoryUpdate = file_get_contents($target);
        $repositoryDataUpdate = preg_replace("/".'#updateFunction'."/", $updateQueryFunction, $entityRepositoryUpdate);
        file_put_contents($target, $repositoryDataUpdate);
    }

    private function generateIntegrationTests($postParams)
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseTest.php';
        $target = __DIR__ . '/../../../../../tests/integration/' . $this->entityNameUpper . 'Test.php';
        shell_exec("cp $source $target");
        $entityTests = file_get_contents($target);
        $testsData1 = preg_replace("/".'Objectbase'."/", $this->entityNameUpper, $entityTests);
        $testsData2 = preg_replace("/".'objectbase'."/", $this->entity, $testsData1);
        $testsData3 = preg_replace("/".'#postParams'."/", $postParams, $testsData2);
        file_put_contents($target, $testsData3);
    }
}
