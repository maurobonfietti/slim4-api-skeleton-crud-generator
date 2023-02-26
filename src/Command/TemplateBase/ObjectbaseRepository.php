<?php

declare(strict_types=1);

namespace App\Repository;

final class ObjectbaseRepository
{
    private \PDO $database;

    public function __construct(\PDO $database)
    {
        $this->database = $database;
    }

    public function getDb(): \PDO
    {
        return $this->database;
    }

    public function checkAndGet(int $objectbaseId): object
    {
        $query = 'SELECT * FROM `objectbase` WHERE `id` = :id';
        $statement = $this->getDb()->prepare($query);
        $statement->bindParam('id', $objectbaseId);
        $statement->execute();
        $objectbase = $statement->fetchObject();
        if (! $objectbase) {
            throw new \Exception('Objectbase not found.', 404);
        }

        return $objectbase;
    }

    public function getAll(): array
    {
        $query = 'SELECT * FROM `objectbase` ORDER BY `id`';
        $statement = $this->getDb()->prepare($query);
        $statement->execute();

        return (array) $statement->fetchAll();
    }

    public function create(object $objectbase): object
    {
        #createFunction
    }

    public function update(object $objectbase, object $data): object
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
