<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $roles = $request->request->get('roles', []);
            
            if ($username) {
                $user->setUsername($username);
            }
            if (is_array($roles)) {
                $user->setRoles($roles);
            }
            
            $entityManager->flush();
            
            $targetData = sprintf('User ID: %d, Username: %s, Roles: %s', 
                $user->getId(),
                $user->getUsername(),
                implode(', ', $user->getRoles())
            );
            $logger->log('user_updated', sprintf('User #%d updated (%s)', $user->getId(), $user->getUsername()), $targetData);

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $userId = $user->getId();
            $username = $user->getUsername();
            $targetData = sprintf('User ID: %d, Username: %s', $userId, $username);
            $entityManager->remove($user);
            $entityManager->flush();
            $logger->log('user_deleted', sprintf('User #%d deleted (%s)', $userId, $username), $targetData);
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}

