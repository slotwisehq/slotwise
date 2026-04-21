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
            $tenantId = TenantContext::current()?->id;
            if ($model->getAttribute('tenant_id') === null && $tenantId !== null) {
                $model->setAttribute('tenant_id', $tenantId);
            }
        });
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
