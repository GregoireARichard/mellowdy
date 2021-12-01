<?php

namespace App\Repository;

use App\Entity\MellowUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MellowUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method MellowUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method MellowUser[]    findAll()
 * @method MellowUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MellowUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MellowUser::class);
    }

    // /**
    //  * @return MellowUser[] Returns an array of daMellowUser objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MellowUser
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
