<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/activity-log')]
final class ActivityLogController extends AbstractController
{
    #[Route(name: 'app_activity_log_index', methods: ['GET'])]
    public function index(ActivityLogRepository $activityLogRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $logs = $activityLogRepository->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult();

        return $this->render('activity_log/index.html.twig', [
            'logs' => $logs,
        ]);
    }
}


