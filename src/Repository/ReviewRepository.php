<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function getAll(): array
    {
        return $this->findAll();
    }

    public function getReviewById(int $id): ?Review
    {
        return $this->find($id);
    }

    public function save(Review $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function update(Review $entity, bool $flush = false): void
    {
        $this->save($entity, $flush);
    }

    public function delete(int $id): void
    {
        $this->remove($this->find($id));
    }

    public function getStatistics(): array
    {
        return $this->createQueryBuilder('r')
                    ->select('
                        r.company_name, 
                        COUNT(r.rating) as count, 
                        AVG(r.rating) as average'
                    )
                    ->groupBy('r.company_name')
                    ->orderBy('average', 'DESC')
                    ->getQuery()
                    ->getResult();
    }

    //    /**
    //     * @return Review[] Returns an array of Review objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Review
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
