<?php

namespace App\Repository;

use App\Entity\Link;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Link>
 *
 * @method Link|null find($id, $lockMode = null, $lockVersion = null)
 * @method Link|null findOneBy(array $criteria, array $orderBy = null)
 * @method Link[]    findAll()
 * @method Link[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Link::class);
    }
    
    public function getUsersLinks($user, $searchQuery, $order, $linksPerPage, $offset)
    {
        $rA = [];

        $eb = $this->createQueryBuilder('l')
            ->where('l.User = :user');
        if ($searchQuery != '') {
            $eb->andWhere('l.alias LIKE :query OR l.url LIKE :query')
                ->setParameter('query', '%'.$searchQuery.'%');
        }
        $eb->setParameter('user', $user)
            ->setFirstResult($offset)
            ->setMaxResults($linksPerPage);
        foreach ($order AS $ok => $ov) {
            $eb->orderBy('l.'.$ok, $ov);
        }
        $result = $eb->getQuery()->getResult();

        $number = $offset;
        foreach ($result AS $link) {
            ++ $number;
            $rA[] = [
                'id' => $link->getId(),
                'number' => $number,
                'status' => $link->getStatus(),
                'alias' => $link->getAlias(),
                'url'   => $link->getUrl(),
                'clicks' => $link->getClicks(),
                ];
        }
        return $rA;
    }

    public function getAmount($user, $searchQuery)
    {
        $eb = $this->createQueryBuilder('l')
            ->where('l.User = :user');
        if ($searchQuery != '') {
            $eb->andWhere('l.alias LIKE :query OR l.url LIKE :query')
                ->setParameter('query', '%'.$searchQuery.'%');
        }
        $eb->setParameter('user', $user);

        $result = $eb->getQuery()->getResult();

        return count($result);
    }

//    /**
//     * @return Links[] Returns an array of Links objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Links
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
