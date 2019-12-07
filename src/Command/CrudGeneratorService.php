<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;

class CrudGeneratorService extends Command
{
    private $entity;

    private $entityUpper;

    private $fields;

    private $insertQueryFunction;

    private $updateQueryFunction;

    private $postParams;

    private $list1, $list2, $list3, $list4, $list5, $list6;

    public function generateCrud($db, $entity)
    {
        $this->entity = $entity;
        $this->entityUpper = ucfirst($this->entity);
        $this->fields = $this->getEntityFields($db);
        $this->getRepositoryFunctions();
        $this->updateRoutes();
        $this->updateRepository();
        $this->updateServices();
        $this->generateControllerFiles();
        $this->updateExceptions();
        $this->updateServices2();
        $this->updateRepository2();
        $this->updateRepository3();
        $this->generateIntegrationTests();
    }

    private function getEntityFields($db)
    {
        $query = "DESC `$this->entity`";
        $statement = $db->prepare($query);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function getRepositoryFunctions()
    {
        // Get Dynamic Params and Fields List.
        foreach ($this->fields as $field) {
            $this->getFieldsList($field);
        }
        $fields1 = substr_replace($this->list1, '', -2);
        $fields2 = substr_replace($this->list2, '', -2);
        $fields3 = substr_replace($this->list3, '', -2);
        $fields4 = substr_replace($this->list4, '', -2);
        $fields5 = substr_replace($this->list5, '', -9);
        $this->postParams = substr_replace($this->list6, '', -3);
        $this->getBaseInsertQueryFunction($fields1, $fields2, $fields3);
        $this->getBaseUpdateQueryFunction($fields3, $fields4, $fields5);
    }

    private function getFieldsList($field)
    {
        $this->list1.= sprintf("`%s`, ", $field['Field']);
        $this->list2.= sprintf(":%s, ", $field['Field']);
        $this->list3.= sprintf('$statement->bindParam(\'%s\', $%s->%s);%s', $field['Field'], $this->entity, $field['Field'], PHP_EOL);
        $this->list3.= sprintf("%'\t1s", '');
        if ($field['Field'] != 'id') {
            $this->list4.= sprintf("`%s` = :%s, ", $field['Field'], $field['Field']);
            $this->list5.= sprintf("if (isset(\$data->%s)) { $%s->%s = \$data->%s; }%s", $field['Field'], $this->entity, $field['Field'], $field['Field'], PHP_EOL);
            $this->list5.= sprintf("        %s", '');
            if ($field['Null'] == "NO" && strpos($field['Type'], 'varchar') !== false) {
                $this->list6.= sprintf("'%s' => '%s',%s", $field['Field'], 'aaa', PHP_EOL);
                $this->list6.= sprintf("%'\t2s", '');
            }
            if ($field['Null'] == "NO" && strpos($field['Type'], 'int') !== false) {
                $this->list6.= sprintf("'%s' => %s,%s", $field['Field'], 1, PHP_EOL);
                $this->list6.= sprintf("%'\t2s", '');
            }
        }
    }

    private function getBaseInsertQueryFunction($fields1, $fields2, $fields3)
    {
        // Get Base Query For Insert Function.
        $this->insertQueryFunction = '$query = \'INSERT INTO `'.$this->entity.'` ('.$fields1.') VALUES ('.$fields2.')\';
        $statement = $this->getDb()->prepare($query);
        '.$fields3.'
        $statement->execute();

        return $this->checkAndGet'.$this->entityUpper.'((int) $this->getDb()->lastInsertId());';
        // End Mock Code...
    }

    private function getBaseUpdateQueryFunction($fields3, $fields4, $fields5)
    {
        // Get Base Query For Update Function.
        $this->updateQueryFunction = ''.$fields5.'

        $query = \'UPDATE `'.$this->entity.'` SET '.$fields4.' WHERE `id` = :id\';
        $statement = $this->getDb()->prepare($query);
        '.$fields3.'
        $statement->execute();

        return $this->checkAndGet'.$this->entityUpper.'((int) $'.$this->entity.'->id);';
        // End Mock Code...
    }

    private function updateRoutes()
    {
        $routes = '
$app->get("/'.$this->entity.'", "App\Controller\\'.$this->entityUpper.'\GetAll");
$app->get("/'.$this->entity.'/[{id}]", "App\Controller\\'.$this->entityUpper.'\GetOne");
$app->post("/'.$this->entity.'", "App\Controller\\'.$this->entityUpper.'\Create");
$app->put("/'.$this->entity.'/[{id}]", "App\Controller\\'.$this->entityUpper.'\Update");
$app->delete("/'.$this->entity.'/[{id}]", "App\Controller\\'.$this->entityUpper.'\Delete");
';
        $file = __DIR__ . '/../../../../../src/App/Routes.php';
        $content = file_get_contents($file);
        $content.= $routes;
        file_put_contents($file, $content);
    }

    private function updateRepository()
    {
        $repository = '
$container["'.$this->entity.'_repository"] = function ($container): App\Repository\\'.$this->entityUpper.'Repository {
    return new App\Repository\\'.$this->entityUpper.'Repository($container["db"]);
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
$container["'.$this->entity.'_service"] = function ($container): App\Service\\'.$this->entityUpper.'Service {
    return new App\Service\\'.$this->entityUpper.'Service($container["'.$this->entity.'_repository"]);
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
        $this->rcopy($source, $target);

        // Replace CRUD Controller Template for New Entity.
        $this->replaceFileContent($target . '/Base.php');
        $this->replaceFileContent($target . '/Create.php');
        $this->replaceFileContent($target . '/Delete.php');
        $this->replaceFileContent($target . '/GetAll.php');
        $this->replaceFileContent($target . '/GetOne.php');
        $this->replaceFileContent($target . '/Update.php');
    }

    private function replaceFileContent($target)
    {
        $content1 = file_get_contents($target);
        $content2 = preg_replace("/".'Objectbase'."/", $this->entityUpper, $content1);
        $content3 = preg_replace("/".'objectbase'."/", $this->entity, $content2);
        file_put_contents($target, $content3);
    }

    private function updateExceptions()
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseException.php';
        $target = __DIR__ . '/../../../../../src/Exception/' . $this->entityUpper . 'Exception.php';
        copy($source, $target);
        $content = file_get_contents($target);
        $content2 = preg_replace("/".'Objectbase'."/", $this->entityUpper, $content);
        $content3 = preg_replace("/".'objectbase'."/", $this->entity, $content2);
        file_put_contents($target, $content3);
    }

    private function updateServices2()
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseService.php';
        $target = __DIR__ . '/../../../../../src/Service/' . $this->entityUpper . 'Service.php';
        copy($source, $target);
        $content = file_get_contents($target);
        $content2 = preg_replace("/".'Objectbase'."/", $this->entityUpper, $content);
        $content3 = preg_replace("/".'objectbase'."/", $this->entity, $content2);
        file_put_contents($target, $content3);
    }

    private function updateRepository2()
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseRepository.php';
        $target = __DIR__ . '/../../../../../src/Repository/' . $this->entityUpper . 'Repository.php';
        copy($source, $target);
        $content = file_get_contents($target);
        $content2 = preg_replace("/".'Objectbase'."/", $this->entityUpper, $content);
        $content3 = preg_replace("/".'objectbase'."/", $this->entity, $content2);
        file_put_contents($target, $content3);
    }

    private function updateRepository3()
    {
        $target = __DIR__ . '/../../../../../src/Repository/' . $this->entityUpper . 'Repository.php';

        $entityRepository = file_get_contents($target);
        $repositoryData = preg_replace("/".'#createFunction'."/", $this->insertQueryFunction, $entityRepository);
        file_put_contents($target, $repositoryData);

        $entityRepositoryUpdate = file_get_contents($target);
        $repositoryDataUpdate = preg_replace("/".'#updateFunction'."/", $this->updateQueryFunction, $entityRepositoryUpdate);
        file_put_contents($target, $repositoryDataUpdate);
    }

    private function generateIntegrationTests()
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseTest.php';
        $target = __DIR__ . '/../../../../../tests/integration/' . $this->entityUpper . 'Test.php';
        copy($source, $target);
        $entityTests = file_get_contents($target);
        $testsData1 = preg_replace("/".'Objectbase'."/", $this->entityUpper, $entityTests);
        $testsData2 = preg_replace("/".'objectbase'."/", $this->entity, $testsData1);
        $testsData3 = preg_replace("/".'#postParams'."/", $this->postParams, $testsData2);
        file_put_contents($target, $testsData3);
    }

    private function rcopy($source, $dest)
    {
        $dir = opendir($source);
        @mkdir($dest);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (is_dir($source . '/' . $file)) {
                recurse_copy($source . '/' . $file, $dest . '/' . $file);
            } else {
                copy($source . '/' . $file, $dest . '/' . $file);
            }
        }
        closedir($dir);
    }
}
