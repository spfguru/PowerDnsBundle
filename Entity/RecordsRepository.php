<?php
/**
 * This file is part of the SysEleven PowerDnsBundle.
 *
 * (c) SysEleven GmbH <http://www.syseleven.de/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author  M. Seifert <m.seifert@syseleven.de>
 * @package SysEleven\PowerDnsBundle\Entity
 */
namespace SysEleven\PowerDnsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use SysEleven\PowerDnsBundle\Form\Transformer\PtrTransformer;

/**
 * Repository Class PowerDns Records.
 *
 * @author  M. Seifert <m.seifert@syseleven.de>
 * @package SysEleven\PowerDnsBundle\Entity
 */
class RecordsRepository extends EntityRepository
{
    /**
     * Performs a query in records table.
     *
     * @param array $filter
     * @return array
     * @deprecated
     */
    public function findByFilter(array $filter = array())
    {
        return $this->findBySearch($filter)->getQuery()->getResult();
    }


    /**
     * @param array $filter
     * @param array $order
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findBySearch(array $filter = array(), array $order = array())
    {
        return $this->createSearchQuery($filter, $order)->getQuery()->getResult();
    }


    /**
     * @param array $filter
     * @param array $order
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createSearchQuery(array $filter = array(), array $order = array())
    {
        $allowed = array('id','name','name_exact','search','type','content','domain_id', 'managed');
        $filter = $this->_prepareDomainFilter($filter);

        $qb = $this->createQueryBuilder('r')
                   ->join('r.domain','d');

        foreach ($filter AS $k => $v) {
            if(!in_array($k, $allowed)) {
                continue;
            }

            if(!is_array($v) &&  0 == strlen($v)) {
                continue;
            }

            if(is_array($v) && 0 == count($v)) {
                continue;
            }

            if($k == 'search') {

                $or = $qb->expr()->orX();
                $or->add($qb->expr()->like('r.name',':search'));
                $or->add($qb->expr()->like('r.name',':search'));
                $or->add($qb->expr()->like($qb->expr()->concat('r.name',$qb->expr()->concat("'.'",'d.name')),':search'));
                $qb->setParameter('search','%'.$v.'%');

                if (filter_var($v, FILTER_VALIDATE_IP)) {
                    $or->add($qb->expr()->like('r.name',':search_transformed'));
                    $or->add($qb->expr()->like('r.content',':search_transformed'));

                    $tr = new PtrTransformer();
                    $vv = $tr->transform($v);
                    $qb->setParameter('search_transformed','%'.$vv.'%');
                }

                $qb->andWhere($or);
                continue;
            }

            if($k == 'type') {
                if(!is_array($v)) {
                    $v = array($v);
                }

                $use = array();
                foreach($v AS $vv) {
                    if(0 == strlen($vv)) {
                        continue;
                    }
                    $use[] = $vv;
                }

                if(0 == count($use)) {
                    continue;
                }

                $qb->andWhere($qb->expr()->in('r.type',':type'));
                $qb->setParameter('type',$use);
                continue;
            }

            if($k == 'id') {
                if(!is_array($v)) {
                    $v = array($v);
                }
                $intOptions = array(
                    "options"=>
                        array("min_range" => 0));
                $use = array();
                foreach($v AS $vv) {
                    if(!filter_var($vv, FILTER_VALIDATE_INT,$intOptions)) {
                        continue;
                    }

                    $use[] = $vv;
                }

                if(0 == count($use)) {
                    continue;
                }
                $qb->andWhere($qb->expr()->in('r.id',':id'));
                $qb->setParameter('id',$use);
                continue;
            }

            if ($k == 'name') {

                // Name is only exact when $v is an ip
                if (filter_var($v, FILTER_VALIDATE_IP)) {
                    $qb->andWhere($qb->expr()->eq('r.name',':name'));
                    $transformer = new PtrTransformer();
                    $name_transformed = $transformer->transform($v);
                    $qb->setParameter('name', $name_transformed);

                    continue;
                }

                $or = $qb->expr()->orX();
                $or->add($qb->expr()->like('r.name',':name'));
                $or->add($qb->expr()->like($qb->expr()->concat('r.name',$qb->expr()->concat("'.'",'d.name')),':name'));

                $qb->andWhere($or)->setParameter('name', '%'.$v.'%');

                continue;
            }

            if ($k == 'name_exact') {

                if (filter_var($v, FILTER_VALIDATE_IP)) {
                    $qb->andWhere($qb->expr()->eq('r.name',':name_exact'));
                    $transformer = new PtrTransformer();
                    $name_transformed = $transformer->transform($v);
                    $qb->setParameter('name_exact', $name_transformed);

                    continue;
                }

                $or = $qb->expr()->orX();
                $or->add($qb->expr()->eq('r.name',':name_exact'));
                $or->add($qb->expr()->eq($qb->expr()->concat('r.name',$qb->expr()->concat("'.'",'d.name')),':name_exact'));

                $qb->andWhere($or)->setParameter('name_exact', $v);

                continue;
            }


            if ($k == 'content') {
                $qb->andWhere($qb->expr()->like('r.content',':content'));
                $qb->setParameter('content', '%'.$v.'%');
                continue;
            }

            if ($k == 'domain_id') {
                if (!is_array($v)) {
                    $v = array($v);
                }
                $domains = array();
                foreach ($v AS $domain_id) {
                    if (!filter_var($domain_id, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)))) {
                        continue;
                    }

                    $domains[] = $domain_id;
                }

                if (0 == count($domains)) {
                    continue;
                }

                $qb->andWhere($qb->expr()->in('d.id', ':domain_id'));
                $qb->setParameter('domain_id', $domains);
                continue;
            }

            if ($k == 'managed') {
                if ($v == 0) {
                    $or = $qb->expr()->orX();
                    $or->add($qb->expr()->isNull('r.managed'));
                    $or->add($qb->expr()->eq('r.managed',':managed'));

                    $qb->andWhere($or);
                    $qb->setParameter('managed', 0);
                    continue;
                }

                if ($v == 1) {
                    $qb->andWhere($qb->expr()->eq('r.managed', ':managed'));
                    $qb->setParameter('managed', 1);
                    continue;
                }
            }
        }

        if (is_array($order) && 0 != count($order)) {
            $allowedOrder = array('domain' => 'd.id','name' => 'r.name', 'type' => 'r.type',
                                  'content' => 'r.content',
                                  'domain_name' => 'd.name');

            $use = array();
            foreach ($allowedOrder AS $k => $f) {
                if (!array_key_exists($k, $order)) {
                    continue;
                }

                $d = (strtolower($order[$k]) == 'asc')? 'asc':'desc';

                $qb->addOrderBy($f, $d);
            }
        }

        return $qb;
    }


    /**
     * Maps domain filter to domain_id
     *
     * @param array $filter
     *
     * @return array
     */
    protected function _prepareDomainFilter(array $filter = array())
    {
        if (!array_key_exists('domain', $filter)) {
            return $filter;
        }

        $useDomains = array();
        if (is_array($filter['domain']) || $filter['domain'] instanceof \Countable) {
            foreach ($filter['domain'] AS $domain) {
                if ($domain instanceof Domains) {
                    $useDomains[] = $domain->getId();
                } elseif (filter_var($domain, FILTER_VALIDATE_INT)) {
                    $useDomains[] = $domain;
                }
            }
        } else {
            if ($filter['domain'] instanceof Domains) {
                $useDomains[] = $filter['domain']->getId();
            } elseif (filter_var($filter['domain'], FILTER_VALIDATE_INT)) {
                $useDomains[] = $filter['domain'];
            }
        }

        if (0 != count($useDomains)) {
            if (array_key_exists('domain_id', $filter)) {
                if (!is_array($filter['domain_id'])) {
                    $filter['domain_id'] = array($filter['domain_id']);
                }
            } else {
                $filter['domain_id'] = array();
            }

            $filter['domain_id'] = array_merge($useDomains, $filter['domain_id']);
        }

        return $filter;
    }
}