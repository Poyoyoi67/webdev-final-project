<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        #[Autowire(env: 'MAILER_FROM_ADDRESS')]
        private string $fromEmail,
        #[Autowire(env: 'MAILER_FROM_NAME')]
        private string $fromName,
    ) {
    }

    /**
     * Generate a unique verification token
     */
    public function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Send verification email to user
     */
    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        $fromAddr = trim($this->fromEmail);
        if ($fromAddr === '') {
            throw new \RuntimeException('MAILER_FROM_ADDRESS is empty. Set it in .env to an address verified in Brevo (Senders).');
        }

        $fromLabel = trim($this->fromName) !== '' ? trim($this->fromName) : 'Health Center';

        $email = (new TemplatedEmail())
            ->from(new Address($fromAddr, $fromLabel))
            ->to(new Address((string) $user->getEmail()))
            ->subject('Please verify your email address')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Verify a token and mark user as verified
     */
    public function verifyToken(string $token): ?User
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return null;
        }

        // Mark user as verified
        $user->setIsVerified(true);
        $user->setVerificationToken(null); // Clear the token

        $this->entityManager->flush();

        return $user;
    }

    /**
     * Check if a user needs verification
     */
    public function needsVerification(User $user): bool
    {
        return !$user->isVerified();
    }
}