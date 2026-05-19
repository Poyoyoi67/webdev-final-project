<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        EmailVerificationService $emailVerificationService,
        AuthorizationCheckerInterface $authorizationChecker,
        LoggerInterface $logger,
    ): Response
    {
        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_account_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            // default new signups to regular user; staff/admin managed separately
            $user->setRoles(['ROLE_USER']);

            $verificationToken = $emailVerificationService->generateVerificationToken();
            $user->setVerificationToken($verificationToken);
            $user->setIsVerified(false);

            $entityManager->persist($user);
            $entityManager->flush();

            $verificationUrl = $this->generateUrl(
                'app_verify_email',
                ['token' => $verificationToken],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            try {
                $emailVerificationService->sendVerificationEmail($user, $verificationUrl);
                $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');
            } catch (TransportExceptionInterface $e) {
                $logger->error('Verification email transport failed', [
                    'user' => $user->getEmail(),
                    'exception' => $e,
                ]);
                $msg = 'Your account was created, but we could not send the verification email. Confirm MAILER_DSN (Brevo SMTP) and that MAILER_FROM_ADDRESS is a verified sender in Brevo.';
                if ($this->getParameter('kernel.debug')) {
                    $msg .= ' '.$e->getMessage();
                }
                $this->addFlash('error', $msg);
            } catch (\Throwable $e) {
                $logger->error('Verification email failed', [
                    'user' => $user->getEmail(),
                    'exception' => $e,
                ]);
                $msg = 'Your account was created, but we could not send the verification email.';
                if ($this->getParameter('kernel.debug')) {
                    $msg .= ' '.$e->getMessage();
                }
                $this->addFlash('error', $msg);
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
