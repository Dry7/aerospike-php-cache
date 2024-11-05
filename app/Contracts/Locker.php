<?php

namespace App\Contracts;

interface Locker
{
    public function acquire(int $userID, string $token): bool;

    public function release(int $userID, string $token): bool;
}
