<?php

namespace App\Service;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class ActivityLogger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    public function log(string $action, ?string $details = null, ?string $targetData = null): void
    {
        $log = new ActivityLog();
        $log->setAction($action);
        $log->setDetails($details);
        $log->setTargetData($targetData);

        $user = $this->security->getUser();
        if ($user instanceof UserInterface) {
            $log->setUsername($user->getUserIdentifier());

            // Get user role (primary role)
            $roles = $user->getRoles();
            if (!empty($roles)) {
                // Get the highest role (ROLE_ADMIN > ROLE_STAFF > ROLE_USER)
                $primaryRole = 'ROLE_USER';
                if (in_array('ROLE_ADMIN', $roles)) {
                    $primaryRole = 'ROLE_ADMIN';
                } elseif (in_array('ROLE_STAFF', $roles)) {
                    $primaryRole = 'ROLE_STAFF';
                }
                $log->setRole($primaryRole);
            }
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}


