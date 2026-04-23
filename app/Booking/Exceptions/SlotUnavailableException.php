<?php

namespace App\Booking\Exceptions;

use Carbon\Carbon;
use RuntimeException;

class SlotUnavailableException extends RuntimeException
{
    public function __construct(
        public readonly Carbon $requestedAt,
        public readonly int $staffId,
        public readonly int $serviceId,
    ) {
        parent::__construct(
            "Slot at {$requestedAt->toIso8601String()} for staff #$staffId is unavailable.",
        );
    }
}
