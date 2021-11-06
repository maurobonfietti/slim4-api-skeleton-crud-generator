<?php

namespace App\Command;

class CrudGeneratorService
{
    private $entity;

    private $entityUpper;

    public function generateCrud($db, $entity)
    {
        $this->entity = $entity;
        $this->entityUpper = ucfirst($this->entity);
        $entity = new CrudGeneratorEntity();
        $entity->getParamsAndFields($db, $this->entity);
        $this->updateRoutes();
        $this->updateRepository();
        $this->updateServices();
        $this->generateControllerFiles();
        $this->updateExceptions();
        $this->updateServices2();
        $this->updateRepository2();
        $this->updateRepository3($entity);
        $this->generateIntegrationTests($entity);
    }

    private function getBaseInsertQueryFunction($entity)
    {
        // Get Base Query For Insert Function and return this Mock Code...
        return '$query = \'INSERT INTO `'.$this->entity.'` ('.$entity->list1.') VALUES ('.$entity->list2.')\';
        $statement = $this->getDb()->prepare($query);
        '.$entity->list3.'
        $statement->execute();

        return $this->checkAndGet((int) $this->getDb()->lastInsertId());';
    }

    private function getBaseUpdateQueryFunction($entity)
    {
        // Get Base Query For Update Function and return this Mock Code...
        return $entity->list5.'

        $query = \'UPDATE `'.$this->entity.'` SET '.$entity->list4.' WHERE `id` = :id\';
        $statement = $this->getDb()->prepare($query);
        '.$entity->list3.'
        $statement->execute();

        return $this->checkAndGet((int) $'.$this->entity.'->id);';
    }

    private function updateRoutes()
    {
        $routes = '
$app->get(\'/'.$this->entity.'\', App\Controller\\'.$this->entityUpper.'\GetAll::class);
$app->post(\'/'.$this->entity.'\', App\Controller\\'.$this->entityUpper.'\Create::class);
$app->get(\'/'.$this->entity.'/{id}\', App\Controller\\'.$this->entityUpper.'\GetOne::class);
$app->put(\'/'.$this->entity.'/{id}\', App\Controller\\'.$this->entityUpper.'\Update::class);
$app->delete(\'/'.$this->entity.'/{id}\', App\Controller\\'.$this->entityUpper.'\Delete::class);
';
        $file = __DIR__ . '/../../../../../src/App/Routes.php';
        $content = file_get_contents($file);
        $content.= $routes;
        file_put_contents($file, $content);
    }

    private function updateRepository()
    {
        $repository = '
$container[\''.$this->entity.'_repository\'] = static function (Pimple\Container $container): App\Repository\\'.$this->entityUpper.'Repository {
    return new App\Repository\\'.$this->entityUpper.'Repository($container[\'db\']);
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
$container[\''.$this->entity.'_service\'] = static function (Pimple\Container $container): App\Service\\'.$this->entityUpper.'Service {
    return new App\Service\\'.$this->entityUpper.'Service($container[\''.$this->entity.'_repository\']);
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
        @mkdir(__DIR__ . '/../../../../../src/Exception');
        copy($source, $target);
        $this->replaceFileContent($target);
    }

    private function updateServices2()
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseService.php';
        $target = __DIR__ . '/../../../../../src/Service/' . $this->entityUpper . 'Service.php';
        @mkdir(__DIR__ . '/../../../../../src/Service');
        copy($source, $target);
        $this->replaceFileContent($target);
    }

    private function updateRepository2()
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseRepository.php';
        $target = __DIR__ . '/../../../../../src/Repository/' . $this->entityUpper . 'Repository.php';
        @mkdir(__DIR__ . '/../../../../../src/Repository');
        copy($source, $target);
        $this->replaceFileContent($target);
    }

    private function updateRepository3($entity)
    {
        $target = __DIR__ . '/../../../../../src/Repository/' . $this->entityUpper . 'Repository.php';

        $entityRepository = file_get_contents($target);
        $repositoryData = preg_replace("/".'#createFunction'."/", $this->getBaseInsertQueryFunction($entity), $entityRepository);
        file_put_contents($target, $repositoryData);

        $entityRepositoryUpdate = file_get_contents($target);
        $repositoryDataUpdate = preg_replace("/".'#updateFunction'."/", $this->getBaseUpdateQueryFunction($entity), $entityRepositoryUpdate);
        file_put_contents($target, $repositoryDataUpdate);
    }

    private function generateIntegrationTests($entity)
    {
        $source = __DIR__ . '/../Command/TemplateBase/ObjectbaseTest.php';
        $target = __DIR__ . '/../../../../../tests/integration/' . $this->entityUpper . 'Test.php';
        copy($source, $target);
        $entityTests = file_get_contents($target);
        $testsData1 = preg_replace("/".'Objectbase'."/", $this->entityUpper, $entityTests);
        $testsData2 = preg_replace("/".'objectbase'."/", $this->entity, $testsData1);
        $testsData3 = preg_replace("/".'#postParams'."/", $entity->list6, $testsData2);
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
