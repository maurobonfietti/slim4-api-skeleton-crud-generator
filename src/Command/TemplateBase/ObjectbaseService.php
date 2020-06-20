<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ObjectbaseException;
use App\Repository\ObjectbaseRepository;

final class ObjectbaseService
{
    protected $objectbaseRepository;

    public function __construct(ObjectbaseRepository $objectbaseRepository)
    {
        $this->objectbaseRepository = $objectbaseRepository;
    }

    protected function checkAndGet(int $objectbaseId)
    {
        return $this->objectbaseRepository->checkAndGet($objectbaseId);
    }

    public function getAll(): array
    {
        return $this->objectbaseRepository->getAll();
    }

    public function getOne(int $objectbaseId)
    {
        return $this->checkAndGet($objectbaseId);
    }

    public function create(array $input)
    {
        $objectbase = json_decode(json_encode($input), false);

        return $this->objectbaseRepository->create($objectbase);
    }

    public function update(array $input, int $objectbaseId)
    {
        $objectbase = $this->checkAndGet($objectbaseId);
        $data = json_decode(json_encode($input), false);

        return $this->objectbaseRepository->update($objectbase, $data);
    }

    public function delete(int $objectbaseId): void
    {
        $this->checkAndGet($objectbaseId);
        $this->objectbaseRepository->delete($objectbaseId);
    }
}
