<?php

namespace App\Controller;

use App\Form\ContactMessageType;
use App\Model\ContactMessage;
use App\Service\ContactNotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    private string $contactInbox;
    private string $contactPublicEmail;

    public function __construct(
        #[Autowire(env: 'CONTACT_MAIL_TO')]
        string $contactMailTo,
        #[Autowire(env: 'CONTACT_NOTIFY_EMAIL')]
        string $contactNotifyEmail,
        #[Autowire(env: 'CONTACT_PUBLIC_EMAIL')]
        string $contactPublicEmail,
        #[Autowire('%app.contact.phone%')]
        private string $clinicPhoneParam,
        #[Autowire('%app.contact.address%')]
        private string $clinicAddressParam,
        #[Autowire('%app.contact.hours%')]
        private string $clinicHoursParam,
        #[Autowire('%app.contact.facebook_url%')]
        private string $facebookUrl,
        #[Autowire('%app.contact.instagram_url%')]
        private string $instagramUrl,
        #[Autowire('%app.contact.twitter_url%')]
        private string $twitterUrl,
        private LoggerInterface $logger,
    ) {
        $notify = trim($contactNotifyEmail);
        $this->contactInbox = trim($contactMailTo) !== '' ? trim($contactMailTo) : $notify;
        $this->contactPublicEmail = trim($contactPublicEmail) !== '' ? trim($contactPublicEmail) : $notify;
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(Request $request, ContactNotificationService $contactNotificationService): Response
    {
        $data = new ContactMessage();
        $form = $this->createForm(ContactMessageType::class, $data);
        $form->handleRequest($request);

        $mailConfigured = trim($this->contactInbox) !== '';

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$mailConfigured) {
                $this->addFlash('error', 'The contact form is not configured yet. Set CONTACT_MAIL_TO or CONTACT_NOTIFY_EMAIL in .env, or check config/packages/contact_page.yaml.');
            } else {
                try {
                    $contactNotificationService->send($data);
                    $this->addFlash('success', 'Your message was sent. We will reply as soon as we can.');
                } catch (\Throwable $e) {
                    $this->logger->error('Contact form email failed', [
                        'exception' => $e,
                        'message' => $e->getMessage(),
                    ]);
                    $hint = 'Your message could not be sent. Check that MAILER_DSN uses a valid Brevo SMTP key, and that CONTACT_MAIL_FROM_EMAIL is a verified sender in Brevo. You can also call the clinic using the number on this page.';
                    if ($this->getParameter('kernel.debug')) {
                        $hint .= ' Details: '.$e->getMessage();
                    }
                    $this->addFlash('error', $hint);
                }

                return $this->redirectToRoute('app_contact');
            }
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
            'mailConfigured' => $mailConfigured,
            'publicContactEmail' => trim($this->contactPublicEmail),
            'clinicPhone' => trim($this->clinicPhoneParam),
            'clinicAddress' => trim($this->clinicAddressParam),
            'clinicHours' => trim($this->clinicHoursParam),
            'facebookUrl' => trim($this->facebookUrl),
            'instagramUrl' => trim($this->instagramUrl),
            'twitterUrl' => trim($this->twitterUrl),
        ]);
    }
}
