<?php

namespace App\Commands\Utils;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class GenerateCrudEntityCommand extends Command
{
    const COMMAND_VERSION = '0.0.4.local';

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

        // Get Entity Name.
        $entityName = $input->getArgument('entity');
        $entityNameUpper = ucfirst($entityName);
        $output->writeln('Generate Endpoints For New Entity: ' . $entityName);

        // Get Entity Fields.
        $db = $this->container->get('db');
        $query = "DESC `$entityName`";
        $statement = $db->prepare($query);
        $statement->execute();
        $fields = $statement->fetchAll();
//        var_dump($fields); exit;

        // Get Insert and Update Functions, using each fields of the entity.
        $repositoryFunctions = $this->getRepositoryFunctions($fields, $entityName, $entityNameUpper);
        $insertQueryFunction = $repositoryFunctions[0];
        $updateQueryFunction = $repositoryFunctions[1];
//        var_dump($repositoryFunctions[2]); exit;

        // Add and Update Routes.
        $this->updateRoutes($entityName);

        // Add and Update Repository.
        $this->updateRepository($entityName);

        // Add and Update Services.
        $this->updateServices($entityName);

        // Copy CRUD Template.
        $source = __DIR__ . '/../../Commands/SourceCode/Objectbase';
        $target = __DIR__ . '/../../../../../../src/Controller/' . ucfirst($entityName);
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

        // Replace and Update Exceptions
        $source = __DIR__ . '/../../Commands/SourceCode/ObjectbaseException.php';
        $target = __DIR__ . '/../../../../../../src/Exception/' . ucfirst($entityName). 'Exception.php';
        shell_exec("cp $source $target");
        shell_exec("sed -i .bkp -e 's/Objectbase/$entityNameUpper/g' $target");
        shell_exec("sed -i .bkp -e 's/objectbase/$entityName/g' $target");
        shell_exec("rm -f $target.bkp");

        // Replace and Update Services.
        $source = __DIR__ . '/../../Commands/SourceCode/ObjectbaseService.php';
        $target = __DIR__ . '/../../../../../../src/Service/' . ucfirst($entityName). 'Service.php';
        shell_exec("cp $source $target");
        shell_exec("sed -i .bkp -e 's/Objectbase/$entityNameUpper/g' $target");
        shell_exec("sed -i .bkp -e 's/objectbase/$entityName/g' $target");
        shell_exec("rm -f $target.bkp");

        // Replace and Update Repository.
        $source = __DIR__ . '/../../Commands/SourceCode/ObjectbaseRepository.php';
        $target = __DIR__ . '/../../../../../../src/Repository/' . ucfirst($entityName). 'Repository.php';
        shell_exec("cp $source $target");
        shell_exec("sed -i .bkp -e 's/Objectbase/$entityNameUpper/g' $target");
        shell_exec("sed -i .bkp -e 's/objectbase/$entityName/g' $target");
        shell_exec("rm -f $target.bkp");

        // Replace and Update Repository with Insert Query Function.
        $entityRepository = file_get_contents($target);
        $repositoryData = preg_replace("/".'#createFunction'."/", $insertQueryFunction, $entityRepository);
        file_put_contents($target, $repositoryData);

        // Replace and Update Repository with Update Query Function.
        $entityRepositoryUpdate = file_get_contents($target);
        $repositoryDataUpdate = preg_replace("/".'#updateFunction'."/", $updateQueryFunction, $entityRepositoryUpdate);
        file_put_contents($target, $repositoryDataUpdate);


        // Create Integration Tests for new endpoints...
        $source = __DIR__ . '/../../Commands/SourceCode/ObjectbaseTest.php';
        $target = __DIR__ . '/../../../../../../tests/integration/' . ucfirst($entityName). 'Test.php';
        shell_exec("cp $source $target");
        shell_exec("sed -i .bkp -e 's/Objectbase/$entityNameUpper/g' $target");
        shell_exec("sed -i .bkp -e 's/objectbase/$entityName/g' $target");
        shell_exec("rm -f $target.bkp");

        // Create Integration Tests for new endpoints...
        $entityTests = file_get_contents($target);
        $testsData = preg_replace("/".'#postParams'."/", $repositoryFunctions[2], $entityTests);
        file_put_contents($target, $testsData);


        $output->writeln('Script Finish ;-)');
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
//                var_dump($field);
//                exit;
                if ($field['Null'] == "NO" && strpos($field['Type'], 'varchar') !== false) {
                    $paramList6.= sprintf("'%s' => '%s',%s", $field['Field'], '', PHP_EOL);
                    $paramList6.= sprintf("%'\t2s", '');   
                }
                if ($field['Null'] == "NO" && strpos($field['Type'], 'int') !== false) {
                    $paramList6.= sprintf("'%s' => %s,%s", $field['Field'], 1, PHP_EOL);
                    $paramList6.= sprintf("%'\t2s", '');   
                }
//                if ($field['Null'] == "NO" && $field['Type'] == "tinyint(1)") {
//                    $paramList6.= sprintf("'%s' => %s,%s", $field['Field'], 1, PHP_EOL);
//                    $paramList6.= sprintf("%'\t2s", '');   
//                }
            }
        }
        $fieldList = substr_replace($paramList, '', -2);
        $fieldList2 = substr_replace($paramList2, '', -2);
        $fieldList3 = substr_replace($paramList3, '', -2);
        $fieldList4 = substr_replace($paramList4, '', -2);
        $fieldList5 = substr_replace($paramList5, '', -2);
        $fieldList6 = substr_replace($paramList6, '', -3);
//        var_dump($fieldList6); exit;

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

    private function updateRoutes($entityName)
    {
        $routes = '
$app->group("/'.$entityName.'", function () use ($app) {
    $app->get("", "App\Controller\\'.ucfirst($entityName).'\GetAll");
    $app->get("/[{id}]", "App\Controller\\'.ucfirst($entityName).'\GetOne");
    $app->post("", "App\Controller\\'.ucfirst($entityName).'\Create");
    $app->put("/[{id}]", "App\Controller\\'.ucfirst($entityName).'\Update");
    $app->delete("/[{id}]", "App\Controller\\'.ucfirst($entityName).'\Delete");
});
';
        $file = __DIR__ . '/../../../../../../src/App/Routes.php';
//        var_dump($file); exit;
        $content = file_get_contents($file);
        $content.= $routes;
        file_put_contents($file, $content);
    }

    private function updateRepository($entityName)
    {
        $repository = '
$container["'.$entityName.'_repository"] = function (ContainerInterface $container): App\Repository\\'.ucfirst($entityName).'Repository {
    return new App\Repository\\'.ucfirst($entityName).'Repository($container->get("db"));
};
';
        $file = __DIR__ . '/../../../../../../src/App/Repositories.php';
        $repositoryContent = file_get_contents($file);
        $repositoryContent.= $repository;
        file_put_contents($file, $repositoryContent);
    }

    private function updateServices($entityName)
    {
        $service = '
$container["'.$entityName.'_service"] = function (ContainerInterface $container): App\Service\\'.ucfirst($entityName).'Service {
    return new App\Service\\'.ucfirst($entityName).'Service($container->get("'.$entityName.'_repository"));
};
';
        $file = __DIR__ . '/../../../../../../src/App/Services.php';
        $serviceContent = file_get_contents($file);
        $serviceContent.= $service;
        file_put_contents($file, $serviceContent);
    }
}
