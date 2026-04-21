<?php

namespace App\Tenant;

use App\Models\Tenant;

class TenantContext
{
    private static ?Tenant $current = null;

    public static function set(?Tenant $tenant): void
    {
        self::$current = $tenant;
    }

    public static function current(): ?Tenant
    {
        return self::$current;
    }
}
