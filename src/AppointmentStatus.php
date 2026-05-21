<?php

namespace App;

/**
 * Single source of truth for appointment statuses used in reports, dashboard, and forms.
 */
final class AppointmentStatus
{
    public const PENDING = 'pending';
    public const FINISHED = 'finished';
    public const CONFIRMED = 'confirmed';
    public const CANCELLED = 'cancelled';

    /** Statuses shown in reports and staff/admin dashboard analytics. */
    public const TRACKED = [
        self::FINISHED,
        self::CONFIRMED,
        self::CANCELLED,
    ];

    /** Labels for staff appointment edit form. */
    public const CHOICES = [
        'Pending (awaiting approval)' => self::PENDING,
        'Finished' => self::FINISHED,
        'Confirmed' => self::CONFIRMED,
        'Cancelled' => self::CANCELLED,
    ];

    public static function isPending(?string $status): bool
    {
        return self::PENDING === $status;
    }
}
