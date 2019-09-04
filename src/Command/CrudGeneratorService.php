<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;

class CrudGeneratorService extends Command
{
    private $entity;

    private $entityUpper;

    public function generateCrud($db, $entity)
    {
        $this->entity = $entity;
        $this->entityUpper = ucfirst($this->entity);
        $fields = $this->getEntityFields($db);
        $repositoryFunctions = $this->getRepositoryFunctions($fields);
        $this->updateRoutes();
        $this->updateRepository();
        $this->updateServices();
        $this->generateControllerFiles();
        $this->updateExceptions();
        $this->updateServices2();
        $this->updateRepository2();
        $this->updateRepository3($repositoryFunctions[0], $repositoryFunctions[1]);
        $this->generateIntegrationTests($repositoryFunctions[2]);
    }

    private function getEntityFields($db)
    {
        $query = "DESC `$this->entity`";
        $statement = $db->prepare($query);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function getRepositoryFunctions($fields)
    {
        // Get Dynamic Params and Fields List.
        $list1 = $list2 = $list3 = $list4 = $list5 = $list6 = '';
        foreach ($fields as $field) {
            $fieldsList = $this->getFieldsList($field);
            $list1.= $fieldsList[0];
            $list2.= $fieldsList[1];
            $list3.= $fieldsList[2];
            $list4.= $fieldsList[3];
            $list5.= $fieldsList[4];
            $list6.= $fieldsList[5];
        }
        $fields1 = substr_replace($list1, '', -2);
        $fields2 = substr_replace($list2, '', -2);
        $fields3 = substr_replace($list3, '', -2);
        $fields4 = substr_replace($list4, '', -2);
        $fields5 = substr_replace($list5, '', -9);
        $fields6 = substr_replace($list6, '', -3);

        // Get Base Query For Insert Function.
        $insertQueryFunction = '$query = \'INSERT INTO `'.$this->entity.'` ('.$fields1.') VALUES ('.$fields2.')\';
        $statement = $this->getDb()->prepare($query);
        '.$fields3.'
        $statement->execute();

        return $this->checkAndGet'.$this->entityUpper.'((int) $this->getDb()->lastInsertId());';

        // Get Base Query For Update Function.
        $updateQueryFunction = ''.$fields5.'

        $query = \'UPDATE `'.$this->entity.'` SET '.$fields4.' WHERE `id` = :id\';
        $statement = $this->getDb()->prepare($query);
        '.$fields3.'
        $statement->execute();

        return $this->checkAndGet'.$this->entityUpper.'((int) $'.$this->entity.'->id);';

        return [$insertQueryFunction, $updateQueryFunction, $fields6];
    }

    private function getFieldsList($field)
    {
        $list1 = sprintf("`%s`, ", $field['Field']);
        $list2 = sprintf(":%s, ", $field['Field']);
        $list3 = sprintf('$statement->bindParam(\'%s\', $%s->%s);%s', $field['Field'], $this->entity, $field['Field'], PHP_EOL);
        $list3.= sprintf("%'\t1s", '');
        if ($field['Field'] != 'id') {
            $list4 = sprintf("`%s` = :%s, ", $field['Field'], $field['Field']);
            $list5 = sprintf("if (isset(\$data->%s)) { $%s->%s = \$data->%s; }%s", $field['Field'], $this->entity, $field['Field'], $field['Field'], PHP_EOL);
            $list5.= sprintf("        %s", '');
            if ($field['Null'] == "NO" && strpos($field['Type'], 'varchar') !== false) {
                $list6 = sprintf("'%s' => '%s',%s", $field['Field'], 'aaa', PHP_EOL);
                $list6.= sprintf("%'\t2s", '');
            }
            if ($field['Null'] == "NO" && strpos($field['Type'], 'int') !== false) {
                $list6 = sprintf("'%s' => %s,%s", $field['Field'], 1, PHP_EOL);
                $list6.= sprintf("%'\t2s", '');
            }
        }

        return [$list1, $list2, $list3, $list4, $list5, $list6];
    }

    private function updateRoutes()
    {
        $routes = '
$app->group("/'.$this->entity.'", function () use ($app) {
    $app->get("", "App\Controller\\'.$this->entityUpper.'\GetAll");
    $app->get("/[{id}]", "App\Controller\\'.$this->entityUpper.'\GetOne");
    $app->post("", "App\Controller\\'.$this->entityUpper.'\Create");
    $app->put("/[{id}]", "App\Controller\\'.$this->entityUpper.'\Update");
    $app->delete("/[{id}]", "App\Controller\\'.$this->entityUpper.'\Delete");
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
$container["'.$this->entity.'_repository"] = function (ContainerInterface $container): App\Repository\\'.$this->entityUpper.'Repository {
    return new App\Repository\\'.$this->entityUpper.'Repository($container->get("db"));
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
$container["'.$this->entity.'_service"] = function (ContainerInterface $container): App\Service\\'.$this->entityUpper.'Service {
    return new App\Service\\'.$this->entityUpper.'Service($container->get("'.$this->entity.'_repository"));
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
        $target = __DIR__ . '/../../../../../src/Controller/' . $this->entityUpper;
        shell_exec("cp -r $source $target");

        // Replace CRUD Controller Template for New Entity.
        $base = $target . '/Base.php';
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityUpper/g' $base");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $base");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityUpper/g' $target/Create.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target/Create.php");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityUpper/g' $target/Delete.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target/Delete.php");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityUpper/g' $target/GetAll.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target/GetAll.php");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityUpper/g' $target/GetOne.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target/GetOne.php");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityUpper/g' $target/Update.php");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target/Update.php");

        // Remove Any Temp Files.
        shell_exec("rm -f $target/*.bkp");
    }

    private function updateExceptions()
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseException.php';
        $target = __DIR__ . '/../../../../../src/Exception/' . $this->entityUpper . 'Exception.php';
        shell_exec("cp $source $target");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityUpper/g' $target");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target");
        shell_exec("rm -f $target.bkp");
    }

    private function updateServices2()
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseService.php';
        $target = __DIR__ . '/../../../../../src/Service/' . $this->entityUpper . 'Service.php';
        shell_exec("cp $source $target");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityUpper/g' $target");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target");
        shell_exec("rm -f $target.bkp");
    }

    private function updateRepository2()
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseRepository.php';
        $target = __DIR__ . '/../../../../../src/Repository/' . $this->entityUpper . 'Repository.php';
        shell_exec("cp $source $target");
        shell_exec("sed -i .bkp -e 's/Objectbase/$this->entityUpper/g' $target");
        shell_exec("sed -i .bkp -e 's/objectbase/$this->entity/g' $target");
        shell_exec("rm -f $target.bkp");
    }

    private function updateRepository3($insertQueryFunction, $updateQueryFunction)
    {
        $target = __DIR__ . '/../../../../../src/Repository/' . $this->entityUpper . 'Repository.php';

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
        $target = __DIR__ . '/../../../../../tests/integration/' . $this->entityUpper . 'Test.php';
        shell_exec("cp $source $target");
        $entityTests = file_get_contents($target);
        $testsData1 = preg_replace("/".'Objectbase'."/", $this->entityUpper, $entityTests);
        $testsData2 = preg_replace("/".'objectbase'."/", $this->entity, $testsData1);
        $testsData3 = preg_replace("/".'#postParams'."/", $postParams, $testsData2);
        file_put_contents($target, $testsData3);
    }
}
