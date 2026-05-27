<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\GoogleIdTokenVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mobile/patient')]
final class MobilePatientAuthController extends AbstractController
{
    #[Route('/google-login', name: 'api_mobile_patient_google_login', methods: ['POST'])]
    public function googleLogin(
        Request $request,
        GoogleIdTokenVerifier $tokenVerifier,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return $this->json(['message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $idToken = (string) ($payload['idToken'] ?? '');

        try {
            $googleUser = $tokenVerifier->verify($idToken);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        } catch (\RuntimeException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $email = $googleUser['email'];
        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user !== null) {
            if (\in_array('ROLE_ADMIN', $user->getRoles(), true) || \in_array('ROLE_STAFF', $user->getRoles(), true)) {
                return $this->json([
                    'message' => 'Staff and admin accounts must sign in on the web portal, not this patient app.',
                ], Response::HTTP_FORBIDDEN);
            }
        } else {
            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_USER']);
            $user->setIsVerified(true);
            $user->setPassword($passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));
            if ($googleUser['picture']) {
                $user->setProfilePicture($googleUser['picture']);
            }
            $entityManager->persist($user);
        }

        $entityManager->flush();

        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }
}
