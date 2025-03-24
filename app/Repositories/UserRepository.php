<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    public function find(int $id): ?User
    {
        return User::where('id', $id)->first();
    }

    public function findAll(): Collection
    {
        return User::all();
    }
}
