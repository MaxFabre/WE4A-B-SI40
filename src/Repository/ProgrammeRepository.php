<?php

namespace App\Repository;

use App\Entity\Programme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Programme>
 */
class ProgrammeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Programme::class);
    }

    //    /**
    //     * @return Programme[] Returns an array of Programme objects
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

    //    public function findOneBySomeField($value): ?Programme
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    // src/Repository/ProgrammeRepository.php

    public function findByFilmTitle(string $direction = 'ASC') //Comme on peux pas trier avec un FindBy sur une clef étrangère, on doit créer la query manuellemetn avec une fonction
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.film', 'f')
            ->addSelect('f')
            ->orderBy('f.title', $direction)
            ->getQuery()
            ->getResult();
    }

    public function findByFilmByDate(int $filmId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT p.id, p.date, l.name AS lang_name
        FROM programme AS p
        INNER JOIN lang AS l ON l.id = p.lang_id
        WHERE p.film_id = :id
        ORDER BY p.date ASC";

        return $conn->fetchAllAssociative($sql, ['id' => $filmId]);
    }
}
