<?php

namespace App\Controller;

use App\Service\AppointmentRealtimePayloadFactory;
use App\Service\AppointmentRealtimeVersionStore;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class RealtimeController extends AbstractController
{
    #[Route('/realtime/appointments/staff-dashboard', name: 'app_realtime_staff_dashboard', methods: ['GET'])]
    public function staffDashboard(AppointmentRealtimePayloadFactory $payloadFactory): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        return $this->json($payloadFactory->staffDashboard());
    }

    #[Route('/realtime/appointments/staff-list', name: 'app_realtime_staff_list', methods: ['GET'])]
    public function staffList(AppointmentRealtimePayloadFactory $payloadFactory): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        $canManage = $this->isGranted('ROLE_STAFF') || $this->isGranted('ROLE_ADMIN');

        return $this->json($payloadFactory->staffList($canManage));
    }

    #[Route('/realtime/appointments/my-bookings', name: 'app_realtime_my_bookings', methods: ['GET'])]
    public function myBookings(AppointmentRealtimePayloadFactory $payloadFactory): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($this->isGranted('ROLE_STAFF') || $this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $patientId = $this->getUser()?->getUserIdentifier() ?? '';

        return $this->json($payloadFactory->patientBookings($patientId));
    }

    #[Route('/realtime/appointments/version', name: 'app_realtime_appointments_version', methods: ['GET'])]
    public function version(AppointmentRealtimeVersionStore $versionStore): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->json(['version' => $versionStore->getVersion()]);
    }

    /** Legacy SSE endpoint (dashboards use JSON polling instead on Railway). */
    #[Route('/realtime/appointments/stream', name: 'app_realtime_appointments_stream', methods: ['GET'])]
    public function appointmentStream(AppointmentRealtimeVersionStore $versionStore): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $version = $versionStore->getVersion();
        $response = new StreamedResponse(static function () use ($version): void {
            echo "event: appointment-update\n";
            echo 'data: {"version":'.$version."}\n\n";
            @flush();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}

