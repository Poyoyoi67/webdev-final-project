<?php

namespace App\Controller;

use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profile')]
final class ProfileController extends AbstractController
{
    #[Route(name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $pictureFile = $form->get('profilePicture')->getData();
            if ($pictureFile) {
                $safeFilename = $slugger->slug(pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();
                try {
                    $pictureFile->move($this->getParameter('kernel.project_dir').'/public/uploads/avatars', $newFilename);
                    $user->setProfilePicture('/uploads/avatars/'.$newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Could not upload profile picture.');
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}

