<?php

namespace App\Controller;

use App\Entity\Doctor;
use App\Form\DoctorType;
use App\Repository\DoctorRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/doctor')]
final class DoctorController extends AbstractController
{
    #[Route(name: 'app_doctor_index', methods: ['GET'])]
    public function index(DoctorRepository $doctorRepository): Response
    {
        return $this->render('doctor/index.html.twig', [
            'doctors' => $doctorRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_doctor_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $doctor = new Doctor();
        $form = $this->createForm(DoctorType::class, $doctor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($doctor);
            $entityManager->flush();
            $targetData = sprintf('Doctor ID: %d, Name: %s, Specialization: %s, Email: %s, Contact: %s', 
                $doctor->getId(), 
                $doctor->getName(),
                $doctor->getSpecialization(),
                $doctor->getEmail(),
                $doctor->getContactNumber()
            );
            $logger->log('doctor_created', sprintf('Doctor #%d created (%s)', $doctor->getId(), $doctor->getName()), $targetData);

            return $this->redirectToRoute('app_doctor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('doctor/new.html.twig', [
            'doctor' => $doctor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_doctor_show', methods: ['GET'])]
    public function show(Doctor $doctor): Response
    {
        return $this->render('doctor/show.html.twig', [
            'doctor' => $doctor,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_doctor_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Doctor $doctor, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $form = $this->createForm(DoctorType::class, $doctor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $targetData = sprintf('Doctor ID: %d, Name: %s, Specialization: %s, Email: %s, Contact: %s', 
                $doctor->getId(), 
                $doctor->getName(),
                $doctor->getSpecialization(),
                $doctor->getEmail(),
                $doctor->getContactNumber()
            );
            $logger->log('doctor_updated', sprintf('Doctor #%d updated (%s)', $doctor->getId(), $doctor->getName()), $targetData);

            return $this->redirectToRoute('app_doctor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('doctor/edit.html.twig', [
            'doctor' => $doctor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_doctor_delete', methods: ['POST'])]
    public function delete(Request $request, Doctor $doctor, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        if ($this->isCsrfTokenValid('delete'.$doctor->getId(), $request->getPayload()->getString('_token'))) {
            $doctorId = $doctor->getId();
            $doctorName = $doctor->getName();
            $targetData = sprintf('Doctor ID: %d, Name: %s', $doctorId, $doctorName);
            $entityManager->remove($doctor);
            $entityManager->flush();
            $logger->log('doctor_deleted', sprintf('Doctor #%d deleted (%s)', $doctorId, $doctorName), $targetData);
        }

        return $this->redirectToRoute('app_doctor_index', [], Response::HTTP_SEE_OTHER);
    }
}
