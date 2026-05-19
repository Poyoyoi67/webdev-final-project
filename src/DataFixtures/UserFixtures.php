<?php

namespace App\DataFixtures;


use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; 

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin123@gmail.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsVerified(true);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'adminpassword'));
        $manager->persist($admin);

        $staff = new User();
        $staff->setEmail('staff123@gmail.com');
        $staff->setRoles(['ROLE_STAFF']);
        $staff->setIsVerified(true);
        $staff->setPassword($this->passwordHasher->hashPassword($staff, 'staffpassword'));
        $manager->persist($staff);

        $manager->flush();
    }
}
