<?php

namespace Tests\Concerns;

use App\Models\Tenant;
use App\Tenant\TenantContext;

trait HasTenantContext
{
    protected Tenant $tenant;

    protected function setUpTenantContext(): void
    {
        $this->tenant = Tenant::factory()->create();

        TenantContext::set($this->tenant);
    }

    protected function tearDownTenantContext(): void
    {
        TenantContext::set(null);
    }
}
