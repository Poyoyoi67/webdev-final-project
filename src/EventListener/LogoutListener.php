<?php

namespace App\EventListener;

use App\Service\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        if ($user) {
            $username = method_exists($user, 'getUsername') ? $user->getUsername() : $user->getUserIdentifier();
            $roles = $user->getRoles();
            $primaryRole = 'ROLE_USER';
            if (in_array('ROLE_ADMIN', $roles)) {
                $primaryRole = 'ROLE_ADMIN';
            } elseif (in_array('ROLE_STAFF', $roles)) {
                $primaryRole = 'ROLE_STAFF';
            }
            $targetData = sprintf('Username: %s, Role: %s', $username, $primaryRole);
            $this->activityLogger->log('user_logout', sprintf('User %s logged out', $username), $targetData);
        }
    }
}

