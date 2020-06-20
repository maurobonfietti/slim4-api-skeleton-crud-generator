<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\ObjectbaseException;

final class ObjectbaseRepository
{
    protected $database;

    protected function getDb(): \PDO
    {
        return $this->database;
    }

    public function __construct(\PDO $database)
    {
        $this->database = $database;
    }

    public function checkAndGet(int $objectbaseId)
    {
        $query = 'SELECT * FROM `objectbase` WHERE `id` = :id';
        $statement = $this->getDb()->prepare($query);
        $statement->bindParam('id', $objectbaseId);
        $statement->execute();
        $objectbase = $statement->fetchObject();
        if (empty($objectbase)) {
            throw new ObjectbaseException('Objectbase not found.', 404);
        }

        return $objectbase;
    }

    public function getAll(): array
    {
        $query = 'SELECT * FROM `objectbase` ORDER BY `id`';
        $statement = $this->getDb()->prepare($query);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(object $objectbase)
    {
        #createFunction
    }

    public function update(object $objectbase, object $data)
    {
        #updateFunction
    }

    public function delete(int $objectbaseId): void
    {
        $query = 'DELETE FROM `objectbase` WHERE `id` = :id';
        $statement = $this->getDb()->prepare($query);
        $statement->bindParam('id', $objectbaseId);
        $statement->execute();
    }
}
