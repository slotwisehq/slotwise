<?php

namespace App\Notification\Exceptions;

use RuntimeException;

class InvalidNotificationEventException extends RuntimeException
{
    public function __construct(string $event)
    {
        parent::__construct("Unrecognised notification event: \"$event\".");
    }
}
