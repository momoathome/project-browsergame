<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use App\Repositories\UserRepository;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    public function find(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function findAll(): Collection
    {
        return $this->userRepository->findAll();
    }
}
