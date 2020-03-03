<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\ObjectbaseException;

class ObjectbaseRepository extends BaseRepository
{
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

    public function create($objectbase)
    {
        #createFunction
    }

    public function update($objectbase, $data)
    {
        #updateFunction
    }

    public function delete(int $objectbaseId)
    {
        $query = 'DELETE FROM `objectbase` WHERE `id` = :id';
        $statement = $this->getDb()->prepare($query);
        $statement->bindParam('id', $objectbaseId);
        $statement->execute();
    }
}
