<?php

namespace App\Tenant\Concerns;

use App\Models\Tenant;
use App\Tenant\Scopes\TenantScope;
use App\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (self $model): void {
            if ($model->tenant_id === null) {
                $model->tenant_id = TenantContext::current()?->id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
