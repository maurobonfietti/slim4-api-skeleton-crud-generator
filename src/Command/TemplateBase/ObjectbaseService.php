<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ObjectbaseRepository;

final class ObjectbaseService
{
    private ObjectbaseRepository $objectbaseRepository;

    public function __construct(ObjectbaseRepository $objectbaseRepository)
    {
        $this->objectbaseRepository = $objectbaseRepository;
    }

    public function checkAndGet(int $objectbaseId): object
    {
        return $this->objectbaseRepository->checkAndGet($objectbaseId);
    }

    public function getAll(): array
    {
        return $this->objectbaseRepository->getAll();
    }

    public function getOne(int $objectbaseId): object
    {
        return $this->checkAndGet($objectbaseId);
    }

    public function create(array $input): object
    {
        $objectbase = json_decode((string) json_encode($input), false);

        return $this->objectbaseRepository->create($objectbase);
    }

    public function update(array $input, int $objectbaseId): object
    {
        $objectbase = $this->checkAndGet($objectbaseId);
        $data = json_decode((string) json_encode($input), false);

        return $this->objectbaseRepository->update($objectbase, $data);
    }

    public function delete(int $objectbaseId): void
    {
        $this->checkAndGet($objectbaseId);
        $this->objectbaseRepository->delete($objectbaseId);
    }
}
