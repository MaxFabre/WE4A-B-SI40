<?php

namespace App\Repository;

use App\Entity\CommentReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommentReport>
 */
class CommentReportRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, CommentReport::class);
    }

    public function findByComment(): array {
        $conn = $this->getEntityManager()->getConnection();

       $sql="
            SELECT C.id, U.username AS author, C.author_id, C.title, COUNT(R.ID) AS nb_reports
            FROM comment C
            JOIN comment_report R ON R.comment_id = C.id
            JOIN public.user U ON U.id = C.author_id
            GROUP BY C.id, U.username, C.title, R.is_active
            HAVING R.is_active=true
            ORDER BY nb_reports DESC;
            ";

        $resultSet = $conn->executeQuery($sql);
        return $resultSet->fetchAllAssociative();
    }

    public function findByFilters(string $sort, string $state): array {
        $qb = $this->createQueryBuilder('r');

        //Test sur les types de signalements demandés:
        if ($state !== 'all') {
            $qb->andWhere('r.statut = :state')->setParameter('state', $state);
        }

        //Jointure avec public.comment:
        $qb->join('r.comment', 'c');

        //Récupération du type de tri:
        [$field, $order] = explode('_', $sort);
        $order = strtoupper($order);

        //Vérification des champs:
        $allowedFields = [
            'id' => 'r.id',
            'date' => 'r.created_at',
            'title' => 'c.title',
        ];
        if (isset($allowedFields[$field])) {
            $qb->orderBy($allowedFields[$field], $order);
        }

        //Retour des résultats:
        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return CommentReport[] Returns an array of CommentReport objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CommentReport
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
