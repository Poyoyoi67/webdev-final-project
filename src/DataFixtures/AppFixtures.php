<?php

namespace App\DataFixtures;

use App\Entity\Doctor;
use App\Entity\DoctorAvailability;
use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $doctors = $this->createDoctors($manager);
        $this->createServices($manager, $doctors);
        $this->createAvailability($manager, $doctors);

        $manager->flush();
    }

    /**
     * @return list<Doctor>
     */
    private function createDoctors(ObjectManager $manager): array
    {
        $rows = [
            [
                'name' => 'Dr. Maria Santos',
                'specialization' => 'General Practice',
                'email' => 'maria.santos@health.com',
                'contactNumber' => '09171234501',
                'description' => 'Primary care, check-ups, and preventive health.',
            ],
            [
                'name' => 'Dr. James Rivera',
                'specialization' => 'Cardiology',
                'email' => 'james.rivera@health.com',
                'contactNumber' => '09171234502',
                'description' => 'Heart health assessments and follow-up care.',
            ],
            [
                'name' => 'Dr. Ana Cruz',
                'specialization' => 'Dermatology',
                'email' => 'ana.cruz@health.com',
                'contactNumber' => '09171234503',
                'description' => 'Skin consultations and treatment plans.',
            ],
        ];

        $doctors = [];
        foreach ($rows as $row) {
            $doctor = new Doctor();
            $doctor->setName($row['name']);
            $doctor->setSpecialization($row['specialization']);
            $doctor->setEmail($row['email']);
            $doctor->setContactNumber($row['contactNumber']);
            $doctor->setDescription($row['description']);
            $manager->persist($doctor);
            $doctors[] = $doctor;
        }

        return $doctors;
    }

    /**
     * @param list<Doctor> $doctors
     */
    private function createServices(ObjectManager $manager, array $doctors): void
    {
        $services = [
            [
                'name' => 'General Consultation',
                'price' => '800',
                'doctor' => $doctors[0]->getName(),
                'text' => 'Routine visit with a general practitioner for symptoms, prescriptions, and referrals.',
                'duration' => 30,
            ],
            [
                'name' => 'Cardiology Check-up',
                'price' => '1500',
                'doctor' => $doctors[1]->getName(),
                'text' => 'ECG review, blood pressure monitoring, and cardiovascular risk assessment.',
                'duration' => 45,
            ],
            [
                'name' => 'Dermatology Consultation',
                'price' => '1200',
                'doctor' => $doctors[2]->getName(),
                'text' => 'Evaluation of skin conditions with a personalized care plan.',
                'duration' => 40,
            ],
            [
                'name' => 'Annual Physical Exam',
                'price' => '2500',
                'doctor' => $doctors[0]->getName(),
                'text' => 'Comprehensive wellness screening including vitals and basic labs.',
                'duration' => 60,
            ],
        ];

        foreach ($services as $row) {
            $service = new Service();
            $service->setName($row['name']);
            $service->setPrice($row['price']);
            $service->setDoctor($row['doctor']);
            $service->setText($row['text']);
            $service->setDuration($row['duration']);
            $manager->persist($service);
        }
    }

    /**
     * @param list<Doctor> $doctors
     */
    private function createAvailability(ObjectManager $manager, array $doctors): void
    {
        $start = new \DateTimeImmutable('today');
        for ($i = 0; $i < 21; ++$i) {
            $day = $start->modify("+{$i} days");
            foreach ($doctors as $index => $doctor) {
                // Stagger availability so not every doctor is free every day (realistic demo).
                if (($i + $index) % 5 === 0) {
                    continue;
                }

                $availability = new DoctorAvailability();
                $availability->setDoctor($doctor);
                $availability->setDate($day);
                $availability->setAvailable(true);
                $manager->persist($availability);
            }
        }
    }
}
