<?php

namespace App\Controller;

use App\Service\AppointmentRealtimeVersionStore;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class RealtimeController extends AbstractController
{
    #[Route('/realtime/appointments/stream', name: 'app_realtime_appointments_stream', methods: ['GET'])]
    public function appointmentStream(AppointmentRealtimeVersionStore $versionStore): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $response = new StreamedResponse(function () use ($versionStore): void {
            @ini_set('zlib.output_compression', '0');
            @ini_set('output_buffering', '0');
            @ini_set('implicit_flush', '1');

            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $lastVersion = $versionStore->getVersion();
            echo "event: ready\n";
            echo 'data: {"version":'.$lastVersion."}\n\n";
            @flush();

            // Keep stream open ~45 seconds; EventSource reconnects automatically.
            for ($i = 0; $i < 45; ++$i) {
                if (connection_aborted()) {
                    return;
                }

                $currentVersion = $versionStore->getVersion();
                if ($currentVersion !== $lastVersion) {
                    $lastVersion = $currentVersion;
                    echo "event: appointment-update\n";
                    echo 'data: {"version":'.$currentVersion."}\n\n";
                    @flush();
                } elseif ($i % 15 === 0) {
                    echo "event: ping\n";
                    echo "data: {}\n\n";
                    @flush();
                }

                sleep(1);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}

