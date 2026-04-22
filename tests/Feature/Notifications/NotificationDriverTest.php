<?php

use App\Models\Appointment;
use App\Notification\Contracts\NotificationDriver;
use App\Notification\Drivers\MailDriver;
use App\Notification\Drivers\NullDriver;
use App\Notification\Exceptions\InvalidNotificationEventException;

// ─── Container binding ────────────────────────────────────────────────────────

it('resolves MailDriver from the container in a non-test environment', function () {
    expect(app(NotificationDriver::class))->toBeInstanceOf(MailDriver::class);
});

// ─── NullDriver ───────────────────────────────────────────────────────────────

it('NullDriver::send() does nothing and never throws', function () {
    $driver = new NullDriver;

    expect(fn () => $driver->send(
        new Appointment, // un-persisted; NullDriver ignores it
        'booking.confirmed',
    ))->not->toThrow(Throwable::class);
});

// ─── InvalidNotificationEventException ───────────────────────────────────────

it('InvalidNotificationEventException carries the event name', function () {
    $exception = new InvalidNotificationEventException('foo.bar');

    expect($exception->getMessage())->toContain('foo.bar');
});
