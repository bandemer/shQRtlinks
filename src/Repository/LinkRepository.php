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
    
    public function getUsersLinks($user, $searchQuery, $order, $linksPerPage, $offset, $filter)
    {
        $rA = [];

        $eb = $this->createQueryBuilder('l')
            ->where('l.User = :user');
        if ($searchQuery != '') {
            $eb->andWhere('l.alias LIKE :query OR l.url LIKE :query')
                ->setParameter('query', '%'.$searchQuery.'%');
        }
        if ($filter['filter_is_active']) {
            if ($filter['only_active_links']) {
                $eb->andWhere('l.status = 1');
            }
            if ($filter['only_inactive_links']) {
                $eb->andWhere('l.status = 0');
            }
            if ($filter['only_my_favs']) {
                $eb->leftJoin('l.users', 'mn');
            }
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

            if ($filter['filter_is_active'] AND $filter['only_my_favs']) {
                if ($link->getUsers()->contains($user)) {
                    ++ $number;
                    $tempArray = [
                        'id'        => $link->getId(),
                        'number'    => $number,
                        'status'    => $link->getStatus(),
                        'alias'     => $link->getAlias(),
                        'url'       => $link->getUrl(),
                        'clicks'    => $link->getClicks(),
                        'fav'       => 1,
                        ];
                    $rA[] = $tempArray;
                }
            } else {
                ++ $number;
                $fav = 0;
                if ($link->getUsers()->contains($user)) {
                    $fav = 1;
                }
                $tempArray = [
                    'id'        => $link->getId(),
                    'number'    => $number,
                    'status'    => $link->getStatus(),
                    'alias'     => $link->getAlias(),
                    'url'       => $link->getUrl(),
                    'clicks'    => $link->getClicks(),
                    'fav'       => $fav,
                ];
                $rA[] = $tempArray;
            }
        }
        return $rA;
    }

    public function getAmount($user, $searchQuery, $filter)
    {
        $eb = $this->createQueryBuilder('l')
            ->where('l.User = :user');
        if ($searchQuery != '') {
            $eb->andWhere('l.alias LIKE :query OR l.url LIKE :query')
                ->setParameter('query', '%'.$searchQuery.'%');
        }
        if ($filter['filter_is_active']) {
            if ($filter['only_active_links']) {
                $eb->andWhere('l.status = 1');
            }
            if ($filter['only_inactive_links']) {
                $eb->andWhere('l.status = 0');
            }
        }
        $eb->setParameter('user', $user);

        $result = $eb->getQuery()->getResult();

        if ($filter['filter_is_active'] AND $filter['only_my_favs']) {
            foreach ($result AS $rk => $r) {
                if (!$r->getUsers()->contains($user)) {
                    unset($result[$rk]);
                }
            }
            $result = array_values($result);
        }
        return count($result);
    }

}
