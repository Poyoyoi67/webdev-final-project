<?php

namespace App\Controller;

use App\Entity\Service;
use App\Form\ServiceType;
use App\Repository\ServiceRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/service')]
final class ServiceController extends AbstractController
{
    #[Route(name: 'app_service_index', methods: ['GET'])]
    public function index(ServiceRepository $serviceRepository): Response
    {
        return $this->render('service/index.html.twig', [
            'services' => $serviceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_service_new', methods: ['GET', 'POST'])]
    #[Route('/new', name: 'app_service_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
     $this->denyCrudForRegularUsers();

     $service = new Service();
     $form = $this->createForm(ServiceType::class, $service);
     $form->handleRequest($request);

     if ($form->isSubmitted() && $form->isValid()) {
         $entityManager->persist($service);
         $entityManager->flush();
            $targetData = sprintf('Service ID: %d, Name: %s, Price: %s, Duration: %d minutes', 
                $service->getId(), 
                $service->getName(),
                $service->getPrice(),
                $service->getDuration()
            );
            $logger->log('service_created', sprintf('Service #%d created (%s)', $service->getId(), $service->getName()), $targetData);

            return $this->redirectToRoute('app_service_index');
        }

     return $this->render('service/new.html.twig', [
            'form' => $form,
     ]);
    }


    #[Route('/{id}', name: 'app_service_show', methods: ['GET'])]
    public function show(Service $service): Response
    {
        return $this->render('service/show.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_service_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Service $service, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyCrudForRegularUsers();

        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $targetData = sprintf('Service ID: %d, Name: %s, Price: %s, Duration: %d minutes', 
                $service->getId(), 
                $service->getName(),
                $service->getPrice(),
                $service->getDuration()
            );
            $logger->log('service_updated', sprintf('Service #%d updated (%s)', $service->getId(), $service->getName()), $targetData);

            return $this->redirectToRoute('app_service_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('service/edit.html.twig', [
            'service' => $service,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_service_delete', methods: ['POST'])]
    public function delete(Request $request, Service $service, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyCrudForRegularUsers();

        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->getPayload()->getString('_token'))) {
            $serviceId = $service->getId();
            $serviceName = $service->getName();
            $targetData = sprintf('Service ID: %d, Name: %s', $serviceId, $serviceName);
            $entityManager->remove($service);
            $entityManager->flush();
            $logger->log('service_deleted', sprintf('Service #%d deleted (%s)', $serviceId, $serviceName), $targetData);
        }

        return $this->redirectToRoute('app_service_index', [], Response::HTTP_SEE_OTHER);
    }


    private function denyCrudForRegularUsers(): void
    {
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You only have view access.');
        }
    }
}
