<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class CrudGeneratorCommand extends Command
{
    const COMMAND_VERSION = '0.0.8';

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
        $entityName = $input->getArgument('entity');
        $output->writeln('Generate Endpoints For New Entity: ' . $entityName);
        $this->generateCrud($entityName);
        $output->writeln('Script Finish ;-)');
    }

    protected function generateCrud($entityName)
    {
        $entityNameUpper = ucfirst($entityName);

        // Get Entity Fields.
        $db = $this->container->get('db');
        $query = "DESC `$entityName`";
        $statement = $db->prepare($query);
        $statement->execute();
        $fields = $statement->fetchAll();

        // Get Insert and Update Functions, using each fields of the entity.
        $repositoryFunctions = $this->getRepositoryFunctions($fields, $entityName, $entityNameUpper);
        $insertQueryFunction = $repositoryFunctions[0];
        $updateQueryFunction = $repositoryFunctions[1];

        // Add and Update Routes.
        $this->updateRoutes($entityName, $entityNameUpper);

        // Add and Update Repository.
        $this->updateRepository($entityName, $entityNameUpper);

        // Add and Update Services.
        $this->updateServices($entityName, $entityNameUpper);

        // Generate Controller Files.
        $this->generateControllerFiles($entityName, $entityNameUpper);

        // Replace and Update Exceptions.
        $this->updateExceptions($entityName, $entityNameUpper);

        // Replace and Update Services.
        $this->updateServices2($entityName, $entityNameUpper);

        // Replace and Update Repository.
        $this->updateRepository2($entityName, $entityNameUpper);

        // Replace and Update Repository with Insert and Update Query Functions.
        $this->updateRepository3($entityNameUpper, $insertQueryFunction, $updateQueryFunction);

        // Create Integration Tests for new endpoints.
        $this->generateIntegrationTests($entityName, $entityNameUpper, $repositoryFunctions[2]);
    }

    private function getRepositoryFunctions($fields, $entityName, $entityNameUpper)
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
            $paramList3.= sprintf('$statement->bindParam(\'%s\', $%s->%s);%s', $field['Field'], $entityName, $field['Field'], PHP_EOL);
            $paramList3.= sprintf("%'\t1s", '');
            if ($field['Field'] != 'id') {
                $paramList4.= sprintf("`%s` = :%s, ", $field['Field'], $field['Field']);
                $paramList5.= sprintf("if (isset(\$data->%s)) { $%s->%s = \$data->%s; }%s", $field['Field'], $entityName, $field['Field'], $field['Field'], PHP_EOL);
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
        $insertQueryFunction = '$query = \'INSERT INTO `'.$entityName.'` ('.$fieldList.') VALUES ('.$fieldList2.')\';
        $statement = $this->getDb()->prepare($query);
        '.$fieldList3.'
        $statement->execute();

        return $this->checkAndGet'.$entityNameUpper.'((int) $this->getDb()->lastInsertId());';

        // Get Base Query For Update Function.
        $updateQueryFunction = ''.$fieldList5.'

        $query = \'UPDATE `'.$entityName.'` SET '.$fieldList4.' WHERE `id` = :id\';
        $statement = $this->getDb()->prepare($query);
        '.$fieldList3.'
        $statement->execute();

        return $this->checkAndGet'.$entityNameUpper.'((int) $'.$entityName.'->id);';

        return [$insertQueryFunction, $updateQueryFunction, $fieldList6];
    }

    private function updateRoutes($entityName, $entityNameUpper)
    {
        $routes = '
$app->group("/'.$entityName.'", function () use ($app) {
    $app->get("", "App\Controller\\'.$entityNameUpper.'\GetAll");
    $app->get("/[{id}]", "App\Controller\\'.$entityNameUpper.'\GetOne");
    $app->post("", "App\Controller\\'.$entityNameUpper.'\Create");
    $app->put("/[{id}]", "App\Controller\\'.$entityNameUpper.'\Update");
    $app->delete("/[{id}]", "App\Controller\\'.$entityNameUpper.'\Delete");
});
';
        $file = __DIR__ . '/../../../../../src/App/Routes.php';
        $content = file_get_contents($file);
        $content.= $routes;
        file_put_contents($file, $content);
    }

    private function updateRepository($entityName, $entityNameUpper)
    {
        $repository = '
$container["'.$entityName.'_repository"] = function (ContainerInterface $container): App\Repository\\'.$entityNameUpper.'Repository {
    return new App\Repository\\'.$entityNameUpper.'Repository($container->get("db"));
};
';
        $file = __DIR__ . '/../../../../../src/App/Repositories.php';
        $repositoryContent = file_get_contents($file);
        $repositoryContent.= $repository;
        file_put_contents($file, $repositoryContent);
    }

    private function updateServices($entityName, $entityNameUpper)
    {
        $service = '
$container["'.$entityName.'_service"] = function (ContainerInterface $container): App\Service\\'.$entityNameUpper.'Service {
    return new App\Service\\'.$entityNameUpper.'Service($container->get("'.$entityName.'_repository"));
};
';
        $file = __DIR__ . '/../../../../../src/App/Services.php';
        $serviceContent = file_get_contents($file);
        $serviceContent.= $service;
        file_put_contents($file, $serviceContent);
    }

    private function generateControllerFiles($entityName, $entityNameUpper)
    {
        // Copy CRUD Template.
        $source = __DIR__ . '/../Command/TemplateBase/Objectbase';
        $target = __DIR__ . '/../../../../../src/Controller/' . $entityNameUpper;
        shell_exec("cp -r $source $target");

        // Replace CRUD Controller Template for New Entity.
        $base = $target . '/Base.php';
        shell_exec("sed -i .bkp -e 's/Objectbase/$entityNameUpper/g' $base");
        shell_exec("sed -i .bkp -e 's/objectbase/$entityName/g' $base");
        shell_exec("sed -i .bkp -e 's/Objectbase/$entityNameUpper/g' $target/Create.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$entityName/g' $target/Create.php");
        shell_exec("sed -i .bkp -e 's/Objectbase/$entityNameUpper/g' $target/Delete.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$entityName/g' $target/Delete.php");
        shell_exec("sed -i .bkp -e 's/Objectbase/$entityNameUpper/g' $target/GetAll.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$entityName/g' $target/GetAll.php");
        shell_exec("sed -i .bkp -e 's/Objectbase/$entityNameUpper/g' $target/GetOne.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$entityName/g' $target/GetOne.php");
        shell_exec("sed -i .bkp -e 's/Objectbase/$entityNameUpper/g' $target/Update.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$entityName/g' $target/Update.php");

        // Remove Any Temp Files.
        shell_exec("rm -f $target/*.bkp");
    }

    private function updateExceptions($entityName, $entityNameUpper)
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseException.php';
        $target = __DIR__ . '/../../../../../src/Exception/' . $entityNameUpper . 'Exception.php';
        shell_exec("cp $source $target");
        shell_exec("sed -i .bkp -e 's/Objectbase/$entityNameUpper/g' $target");
        shell_exec("sed -i .bkp -e 's/objectbase/$entityName/g' $target");
        shell_exec("rm -f $target.bkp");
    }

    private function updateServices2($entityName, $entityNameUpper)
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseService.php';
        $target = __DIR__ . '/../../../../../src/Service/' . $entityNameUpper . 'Service.php';
        shell_exec("cp $source $target");
        shell_exec("sed -i .bkp -e 's/Objectbase/$entityNameUpper/g' $target");
        shell_exec("sed -i .bkp -e 's/objectbase/$entityName/g' $target");
        shell_exec("rm -f $target.bkp");
    }

    private function updateRepository2($entityName, $entityNameUpper)
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseRepository.php';
        $target = __DIR__ . '/../../../../../src/Repository/' . $entityNameUpper . 'Repository.php';
        shell_exec("cp $source $target");
        shell_exec("sed -i .bkp -e 's/Objectbase/$entityNameUpper/g' $target");
        shell_exec("sed -i .bkp -e 's/objectbase/$entityName/g' $target");
        shell_exec("rm -f $target.bkp");
    }

    private function updateRepository3($entityNameUpper, $insertQueryFunction, $updateQueryFunction)
    {
        $target = __DIR__ . '/../../../../../src/Repository/' . $entityNameUpper . 'Repository.php';

        $entityRepository = file_get_contents($target);
        $repositoryData = preg_replace("/".'#createFunction'."/", $insertQueryFunction, $entityRepository);
        file_put_contents($target, $repositoryData);

        $entityRepositoryUpdate = file_get_contents($target);
        $repositoryDataUpdate = preg_replace("/".'#updateFunction'."/", $updateQueryFunction, $entityRepositoryUpdate);
        file_put_contents($target, $repositoryDataUpdate);
    }

    private function generateIntegrationTests($entityName, $entityNameUpper, $postParams)
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseTest.php';
        $target = __DIR__ . '/../../../../../tests/integration/' . $entityNameUpper . 'Test.php';
        shell_exec("cp $source $target");
        $entityTests = file_get_contents($target);
        $testsData1 = preg_replace("/".'Objectbase'."/", $entityNameUpper, $entityTests);
        $testsData2 = preg_replace("/".'objectbase'."/", $entityName, $testsData1);
        $testsData3 = preg_replace("/".'#postParams'."/", $postParams, $testsData2);
        file_put_contents($target, $testsData3);
    }
}
