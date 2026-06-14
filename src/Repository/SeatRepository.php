<?php

namespace App\Repository;

use App\Entity\Seat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Seat>
 */
class SeatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seat::class);
    }

    //    /**
    //     * @return Seat[] Returns an array of Seat objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Seat
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    // src/Repository/SeatRepository.php
    public function findByRoomIdWithRelations(int $roomId): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.room', 'r')->addSelect('r')
            ->leftJoin('s.reservations', 'res')->addSelect('res') // si besoin
            ->andWhere('r.id = :roomId')
            ->setParameter('roomId', $roomId)
            ->orderBy('s.number', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
