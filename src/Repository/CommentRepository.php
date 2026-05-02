<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Comment::class);
    }

    public function findByFilters(string $sort, string $visible): array {
        $qb = $this->createQueryBuilder('c');

        //Test sur la visibilité des commentaires:
        if ($visible !== 'all') {
            $qb->andWhere('c.is_visible = :visible')->setParameter('visible', $visible);
        }

        //Récupération du type de tri:
        [$field, $order] = explode('_', $sort);
        $order = strtoupper($order);

        //Vérification des champs:
        $allowedFields = [
            'id' => 'c.id',
            'date' => 'c.created_at',
            'title' => 'c.title',
        ];
        if (isset($allowedFields[$field])) {
            $qb->orderBy($allowedFields[$field], $order);
        }

        //Retour des résultats:
        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Comment[] Returns an array of Comment objects
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

    //    public function findOneBySomeField($value): ?Comment
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
