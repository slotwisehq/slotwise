<?php

namespace App\Tenant\Scopes;

use App\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = TenantContext::current();

        if ($tenant !== null) {
            $builder->where($model->getTable().'.tenant_id', $tenant->id);
        }
    }
}
