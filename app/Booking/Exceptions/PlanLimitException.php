<?php

namespace App\Booking\Exceptions;

use RuntimeException;

class PlanLimitException extends RuntimeException
{
    public function __construct(
        public readonly string $limit,
        public readonly string $plan,
    ) {
        parent::__construct("Plan limit reached: $limit is not available on the $plan plan.");
    }
}
