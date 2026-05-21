<?php

namespace App\Controller;

use App\Entity\DoctorAvailability;
use App\Repository\DoctorAvailabilityRepository;
use App\Repository\DoctorRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/availability')]
final class AvailabilityController extends AbstractController
{
    #[Route(name: 'app_availability_index', methods: ['GET'])]
    public function index(
        Request $request,
        DoctorRepository $doctorRepository,
        DoctorAvailabilityRepository $availabilityRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $dateParam = $request->query->get('date') ?? (new \DateTime())->format('Y-m-d');
        $selectedDate = new \DateTime($dateParam);

        $availabilities = $availabilityRepository->findBy(['date' => $selectedDate]);
        $availabilityMap = [];
        foreach ($availabilities as $availability) {
            $availabilityMap[$availability->getDoctor()->getId()] = $availability;
        }

        return $this->render('availability/index.html.twig', [
            'doctors' => $doctorRepository->findAll(),
            'selectedDate' => $selectedDate,
            'availabilityMap' => $availabilityMap,
        ]);
    }

    #[Route('/set', name: 'app_availability_set', methods: ['POST'])]
    public function set(
        Request $request,
        DoctorRepository $doctorRepository,
        DoctorAvailabilityRepository $availabilityRepository,
        EntityManagerInterface $entityManager,
        ActivityLogger $logger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $doctorId = $request->request->getInt('doctor_id');
        $dateString = $request->request->get('date');
        $available = $request->request->getBoolean('available');

        $doctor = $doctorRepository->find($doctorId);
        if (!$doctor || !$dateString) {
            $this->addFlash('error', 'Invalid doctor or date.');
            return $this->redirectToRoute('app_availability_index');
        }

        $date = new \DateTime($dateString);
        $availability = $availabilityRepository->findOneBy([
            'doctor' => $doctor,
            'date' => $date,
        ]);

        if (!$availability) {
            $availability = new DoctorAvailability();
            $availability->setDoctor($doctor);
            $availability->setDate($date);
            $entityManager->persist($availability);
        }

        $availability->setAvailable($available);
        $entityManager->flush();

        $targetData = sprintf('Doctor ID: %d, Name: %s, Date: %s, Available: %s', 
            $doctor->getId(),
            $doctor->getName(),
            $date->format('Y-m-d'),
            $available ? 'Yes' : 'No'
        );
        $logger->log(
            'doctor_availability_updated',
            sprintf('Doctor %s marked %s for %s', $doctor->getName(), $available ? 'available' : 'unavailable', $date->format('Y-m-d')),
            $targetData
        );

        return $this->redirectToRoute('app_availability_index', ['date' => $date->format('Y-m-d')]);
    }

    #[Route('/add-schedule', name: 'app_availability_add_schedule', methods: ['POST'])]
    public function addSchedule(
        Request $request,
        DoctorRepository $doctorRepository,
        DoctorAvailabilityRepository $availabilityRepository,
        EntityManagerInterface $entityManager,
        ActivityLogger $logger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $doctorId = $request->request->getInt('doctor_id');
        $startDateString = $request->request->get('start_date');
        $endDateString = $request->request->get('end_date');
        $available = $request->request->getBoolean('available', true);

        $doctor = $doctorRepository->find($doctorId);
        if (!$doctor || !$startDateString) {
            $this->addFlash('error', 'Invalid doctor or date.');
            return $this->redirectToRoute('app_availability_index');
        }

        $startDate = (new \DateTime($startDateString))->setTime(0, 0, 0);
        $endDate = $endDateString ? (new \DateTime($endDateString))->setTime(0, 0, 0) : clone $startDate;

        if ($endDate < $startDate) {
            $this->addFlash('error', 'End date must be after start date.');
            return $this->redirectToRoute('app_availability_index');
        }

        $currentDate = clone $startDate;
        $schedulesAdded = 0;

        while ($currentDate <= $endDate) {
            $dateForLookup = clone $currentDate;
            $availability = $availabilityRepository->findOneBy([
                'doctor' => $doctor,
                'date' => $dateForLookup,
            ]);

            if (!$availability) {
                $availability = new DoctorAvailability();
                $availability->setDoctor($doctor);
                $availability->setDate($dateForLookup);
                $entityManager->persist($availability);
            }

            $availability->setAvailable($available);
            $schedulesAdded++;

            $currentDate->modify('+1 day');
        }

        $entityManager->flush();

        $targetData = sprintf('Doctor ID: %d, Name: %s, Date Range: %s to %s, Available: %s, Days: %d', 
            $doctor->getId(),
            $doctor->getName(),
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $available ? 'Yes' : 'No',
            $schedulesAdded
        );
        $logger->log(
            'doctor_schedule_added',
            sprintf('Schedule added for Doctor %s from %s to %s (%d days)', 
                $doctor->getName(), 
                $startDate->format('Y-m-d'), 
                $endDate->format('Y-m-d'),
                $schedulesAdded
            ),
            $targetData
        );

        $this->addFlash('success', sprintf('Schedule added successfully for %d day(s).', $schedulesAdded));
        return $this->redirectToRoute('app_availability_index', ['date' => $startDate->format('Y-m-d')]);
    }
}


