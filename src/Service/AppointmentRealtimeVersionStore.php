<?php

namespace App\Service;

/**
 * Lightweight version marker used by the SSE stream.
 * Any appointment mutation bumps the version so connected dashboards can refresh.
 */
final class AppointmentRealtimeVersionStore
{
    private string $versionFile;

    public function __construct(string $projectDir)
    {
        $this->versionFile = $projectDir.'/var/appointment_realtime.version';
    }

    public function getVersion(): int
    {
        if (!is_file($this->versionFile)) {
            $this->writeVersion(time());
        }

        $raw = @file_get_contents($this->versionFile);
        if ($raw === false) {
            return time();
        }

        return max(1, (int) trim($raw));
    }

    public function bump(): int
    {
        $version = time();
        $this->writeVersion($version);

        return $version;
    }

    private function writeVersion(int $version): void
    {
        $dir = \dirname($this->versionFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        @file_put_contents($this->versionFile, (string) $version, LOCK_EX);
    }
}

