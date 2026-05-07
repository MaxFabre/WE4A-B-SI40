<?php

namespace App\Repository;

use App\Entity\Person;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Person>
 */
class PersonRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Person::class);
    }

    public function findAllPersonalities() {
        $conn = $this->getEntityManager()->getConnection();

        $sql='
            SELECT * FROM person
            WHERE id NOT IN (
                SELECT person_id FROM public.user
            );
            ';

        $resultSet = $conn->executeQuery($sql);
        return $resultSet->fetchAllAssociative(); }

    public function findAllPersonalitiesForm(): QueryBuilder {
        $sub = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('IDENTITY(u.person)')
            ->from(User::class, 'u');

        return $this->createQueryBuilder('p')
            ->where($this->createQueryBuilder('p')
                ->expr()
                ->notIn('p.id', $sub->getDQL())
            )
            ->orderBy('p.lastname', 'ASC');
    }

    //    /**
    //     * @return Person[] Returns an array of Person objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Person
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
