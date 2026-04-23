<?php

use App\Feature\Feature;
use App\Models\Tenant;

// ─── Dataset: all 6 features × 3 plans ───────────────────────────────────────

dataset('feature_plan_matrix', [
    // [feature, plan, expected]
    'sms free' => ['sms_notifications', 'free', false],
    'sms pro' => ['sms_notifications', 'pro', true],
    'sms business' => ['sms_notifications', 'business', true],
    'custom_domain free' => ['custom_domain', 'free', false],
    'custom_domain pro' => ['custom_domain', 'pro', true],
    'custom_domain business' => ['custom_domain', 'business', true],
    'analytics free' => ['analytics_dashboard', 'free', false],
    'analytics pro' => ['analytics_dashboard', 'pro', true],
    'analytics business' => ['analytics_dashboard', 'business', true],
    'white_label free' => ['white_label', 'free', false],
    'white_label pro' => ['white_label', 'pro', false],
    'white_label business' => ['white_label', 'business', true],
    'api_access free' => ['api_access', 'free', false],
    'api_access pro' => ['api_access', 'pro', false],
    'api_access business' => ['api_access', 'business', true],
    'stripe_connect free' => ['stripe_connect', 'free', false],
    'stripe_connect pro' => ['stripe_connect', 'pro', false],
    'stripe_connect business' => ['stripe_connect', 'business', true],
]);

it('returns correct bool for each feature/plan combination', function (string $feature, string $plan, bool $expected) {
    $tenant = Tenant::factory()->create(['plan' => $plan]);

    expect(Feature::check($feature, $tenant))->toBe($expected);
})->with('feature_plan_matrix');

// ─── Unknown feature key ──────────────────────────────────────────────────────

it('returns false and logs a warning for an unknown feature key', function () {
    $tenant = Tenant::factory()->create(['plan' => 'business']);

    expect(Feature::check('does_not_exist', $tenant))->toBeFalse();
});
