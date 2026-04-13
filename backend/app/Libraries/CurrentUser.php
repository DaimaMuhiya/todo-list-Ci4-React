<?php

namespace App\Libraries;

final class CurrentUser
{
    private static ?int $userId = null;

    private static ?string $role = null;

    public static function set(int $userId, string $role): void
    {
        self::$userId = $userId;
        self::$role   = $role;
    }

    public static function clear(): void
    {
        self::$userId = null;
        self::$role   = null;
    }

    public static function id(): ?int
    {
        return self::$userId;
    }

    public static function role(): ?string
    {
        return self::$role;
    }

    public static function isAdmin(): bool
    {
        return self::$role === 'admin';
    }
}
