<?php

namespace AppBundle\Tools\Advertisement;

use AppBundle\AppBundle;
use AppBundle\Safety\Types\Content;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;

class ContentDeliver {
    private $types;
    private $em;

    function __construct(EntityManagerInterface $em) {
        //$this->type = $advertisementType;
        $this->types = [];
        $this->em    = $em;
    }

    function setTypesToLoad(array $int) {
        $this->types = [];
        foreach ($int as $item) {
            if (Content::isAdvertisement($item))
                $this->types[] = $item;
        }
    }

    function loadRandomAds(int $amount):ContentInjectContainer {
        if (empty($this->types))
            return new ContentInjectContainer();

        $qb = $this->em->createQueryBuilder()
                       ->select('c,cp')
                       ->from('AppBundle:Content', 'c')
                       ->leftJoin('c.parameterObj', 'cp')
                        ->where('cp.isPrivate = 0');
        $string = [];
        for ($i = 0; $i < count($this->types); $i++) {
            $string[] = 'cp.type = ' . $this->types[$i];
        }

        $qb->andWhere('('.implode(' OR ',$string).')');
        $qb = $qb->setMaxResults(1000)
                 ->getQuery()
                 ->useResultCache(true, 60000)
                 ->getResult();

        if(!$qb || count($qb) < 1)
            return new ContentInjectContainer();

        shuffle($qb);
        $ads = [];
        for($i = 0; $i < min($amount,count($qb)); $i++){
            $ads[] = $qb[$i];
        }
        /** @var $ads \AppBundle\Entity\Content[] */
        $qb = $this->em->createQueryBuilder()
            ->select('c,t,cc,l')
            ->from('AppBundle:Content','c')
            ->leftJoin('c.thumbnailObj','t')
            ->leftJoin('c.collectionObj','cc')
            ->leftJoin('cc.linkObj','l')->where('c.ID = '.$ads[0]->getID());

        unset($ads[0]);
        foreach($ads as $c){
            $qb->orWhere('c.ID = '.$c->getID());
        }
        $res = $qb->getQuery()
                  ->useResultCache(true, 60000)
                  ->getResult();

        return new ContentInjectContainer( $res );
    }
}