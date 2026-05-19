<?php

namespace App\Repository;

use App\Entity\DoctorAvailability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DoctorAvailability>
 */
class DoctorAvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DoctorAvailability::class);
    }

    /**
    * @return DoctorAvailability[] 
    */
    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.date BETWEEN :start AND :end')
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->orderBy('a.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Doctor IDs marked available=true for the given calendar date (typically "today").
     *
     * @return list<int>
     */
    public function findAvailableDoctorIdsForDate(\DateTimeInterface $date): array
    {
        $day = $date->format('Y-m-d');

        /** @var DoctorAvailability[] $availabilities */
        $availabilities = $this->createQueryBuilder('a')
            ->join('a.doctor', 'd')->addSelect('d')
            ->andWhere('a.date = :day')
            ->andWhere('a.available = :yes')
            ->setParameter('day', $day)
            ->setParameter('yes', true)
            ->getQuery()
            ->getResult();

        $ids = [];
        foreach ($availabilities as $row) {
            $doctor = $row->getDoctor();
            if ($doctor && null !== $doctor->getId()) {
                $ids[] = $doctor->getId();
            }
        }

        return array_values(array_unique($ids));
    }
}


