<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

/**
 * Shared version counter in MySQL so all Railway requests see the same value.
 * Any appointment mutation bumps the version; dashboards poll and refresh in place.
 */
final class AppointmentRealtimeVersionStore
{
    private const STATE_KEY = 'appointments_version';

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function getVersion(): int
    {
        $this->ensureRow();

        $value = $this->connection->fetchOne(
            'SELECT state_value FROM realtime_state WHERE state_key = ?',
            [self::STATE_KEY],
        );

        if ($value === false || $value === null) {
            return time();
        }

        return max(1, (int) $value);
    }

    public function bump(): int
    {
        $version = time();
        $this->ensureRow();

        $this->connection->executeStatement(
            'UPDATE realtime_state SET state_value = ? WHERE state_key = ?',
            [(string) $version, self::STATE_KEY],
        );

        return $version;
    }

    private function ensureRow(): void
    {
        $exists = $this->connection->fetchOne(
            'SELECT 1 FROM realtime_state WHERE state_key = ?',
            [self::STATE_KEY],
        );

        if ($exists !== false) {
            return;
        }

        try {
            $this->connection->insert('realtime_state', [
                'state_key' => self::STATE_KEY,
                'state_value' => (string) time(),
            ]);
        } catch (\Throwable) {
            // Another request may have inserted the row first.
        }
    }
}
