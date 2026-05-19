<?php

namespace App\Service;

use App\Model\ContactMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ContactNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire(env: 'CONTACT_MAIL_TO')]
        private string $contactMailTo,
        #[Autowire(env: 'CONTACT_NOTIFY_EMAIL')]
        private string $contactNotifyEmail,
        #[Autowire(env: 'CONTACT_MAIL_FROM_EMAIL')]
        private string $contactFromEmail,
        #[Autowire(env: 'MAILER_FROM_ADDRESS')]
        private string $mailerFromAddress,
        #[Autowire(env: 'CONTACT_MAIL_FROM_NAME')]
        private string $contactFromName,
        #[Autowire(env: 'MAILER_FROM_NAME')]
        private string $mailerFromName,
        #[Autowire(env: 'CONTACT_BRAND_NAME')]
        private string $brandName,
    ) {
    }

    public function send(ContactMessage $message): void
    {
        $to = trim($this->contactMailTo) !== '' ? trim($this->contactMailTo) : trim($this->contactNotifyEmail);
        if ($to === '') {
            throw new \RuntimeException('No inbox email: set CONTACT_MAIL_TO or CONTACT_NOTIFY_EMAIL.');
        }

        $fromAddr = trim($this->contactFromEmail) !== '' ? trim($this->contactFromEmail) : trim($this->mailerFromAddress);
        if ($fromAddr === '') {
            throw new \RuntimeException('No sender email: set CONTACT_MAIL_FROM_EMAIL or MAILER_FROM_ADDRESS.');
        }

        $fromName = trim($this->contactFromName) !== '' ? trim($this->contactFromName) : trim($this->mailerFromName);

        $email = (new TemplatedEmail())
            ->from(new Address($fromAddr, $fromName !== '' ? $fromName : 'Health Center'))
            ->to(new Address($to))
            ->replyTo(new Address($message->email, $message->name))
            ->subject(sprintf('[%s] %s', $this->brandName, $message->subject))
            ->htmlTemplate('emails/contact_notification.html.twig')
            ->textTemplate('emails/contact_notification.txt.twig')
            ->context([
                'name' => $message->name,
                'contactEmail' => $message->email,
                'message' => $message->message,
            ]);

        $this->mailer->send($email);
    }
}
