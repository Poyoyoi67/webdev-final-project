<?php

namespace App\Repository;

use App\AppointmentStatus;
use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    /**
     * Returns a map of status => count. When a list of statuses is provided,
     * missing statuses are returned with zero to make rendering straightforward.
     */
    public function countByStatuses(array $statuses = []): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.status AS status', 'COUNT(a.id) AS count')
            ->groupBy('a.status');

        if (!empty($statuses)) {
            $qb->andWhere('a.status IN (:statuses)')
                ->setParameter('statuses', $statuses);
        }

        $results = $qb->getQuery()->getArrayResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        if (!empty($statuses)) {
            foreach ($statuses as $status) {
                $counts[$status] = $counts[$status] ?? 0;
            }
        }

        return $counts;
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Appointment[]
     */
    public function findByPatientIdentifier(string $patientIdentifier): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.doctor', 'd')->addSelect('d')
            ->leftJoin('a.service', 's')->addSelect('s')
            ->andWhere('a.patientName = :patient')
            ->setParameter('patient', $patientIdentifier)
            ->orderBy('a.appointmentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Appointment[]
     */
    public function findAllOrderedForStaff(): array
    {
        $appointments = $this->createQueryBuilder('a')
            ->leftJoin('a.doctor', 'd')->addSelect('d')
            ->leftJoin('a.service', 's')->addSelect('s')
            ->getQuery()
            ->getResult();

        usort($appointments, static function (Appointment $a, Appointment $b): int {
            $aPending = AppointmentStatus::isPending($a->getStatus());
            $bPending = AppointmentStatus::isPending($b->getStatus());
            if ($aPending !== $bPending) {
                return $aPending ? -1 : 1;
            }

            return ($a->getAppointmentDate() ?? new \DateTime()) <=> ($b->getAppointmentDate() ?? new \DateTime());
        });

        return $appointments;
    }

    public function hasConfirmedBookingForPatient(string $patientIdentifier): bool
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.patientName = :patient')
            ->andWhere('a.status = :confirmed')
            ->setParameter('patient', $patientIdentifier)
            ->setParameter('confirmed', 'confirmed')
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    //    /**
    //     * @return Appointment[] Returns an array of Appointment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Appointment
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
