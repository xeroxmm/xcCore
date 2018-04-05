<?php
namespace AppBundle\Tools\Advertisement;

use AppBundle\Entity\Tag;
use AppBundle\Tools\Supply\Content;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

class DatabaseTie {
    private $em;
    private $offsetPage = 1;

    /** @var null|ArrayCollection */
    private $rawReferrer = NULL;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }
    public function addReferrerOfClient(ArrayCollection $rawReferrer){
        $this->rawReferrer = $rawReferrer;
    }
    public function loadAdvertisementPlugsByTags(array $tagArr, int $count = 5, int $page = 1, array &$content){
        $this->offsetPage = $page;
        /** @var Tag[] $tagArr */
        $ids = []; $result = [];
        foreach($tagArr as $tag)
            $ids[] = $tag->getID();
        //echo count($tagArr)."_____".implode(',', $ids)."_____";

        $andReferrerString = '';
        if($this->rawReferrer !== null && !$this->rawReferrer->isEmpty()){
            $tX = [];
            foreach($this->rawReferrer->getValues() as $value){
                //echo $value.' -> ';
                $tX[] = $value;
                $andReferrerString .= ' AND lo.externURL NOT LIKE \'%'.$value.'%\'';
            }
            if(empty($tX))
                $andReferrerString = '';
        }

        if(!empty($ids)) {
            $query = $this->em->createQueryBuilder()
                ->select('c,ce,lo,t')
                ->from('AppBundle:Content', 'c')
                ->leftJoin('c.parameterObj', 'cp')
                ->leftJoin('c.elementList', 'ce')
                ->leftJoin('ce.linkObj', 'lo')
                ->leftJoin('c.tagArray', 'ct')
                ->leftJoin('c.thumbnailObj','t')
                ->where('cp.isPrivate = 0')
                ->andWhere('((cp.type > 50 AND cp.type < 60) OR cp.type = 32)')
                ->andWhere('lo.ID > 0'.$andReferrerString)
                ->andWhere('ct.ID IN (:ids)')
                ->setParameter('ids', implode(',', $ids))
                ->distinct(TRUE)
                ->orderBy('c.ID', 'DESC')
                ->setFirstResult(($this->offsetPage - 1) * $count)
                ->setMaxResults($count)
                ->getQuery();
            //echo "<!--".$query->getSQL()."-->";
            $result = $query->useResultCache(TRUE, 72000)
                            ->useQueryCache(TRUE)
                            ->getResult();

            $ids = [];
            if($result){
                /** @var $r \AppBundle\Entity\Content*/
                foreach($result as $r){
                    $content[$r->getID()] = $r;
                    $ids[] = $r->getID();
                    /*foreach($r->getElementList() as $a)
                        echo $a->getLinkObj()->getExternURL(). " -> ";*/
                }
            }
        }
        //echo "__".count($result)."__".$count."__;;__";
        if(count($content) < $count){
            $query = $this->em->createQueryBuilder()
                              ->select('c,ce,lo,t')
                              ->from('AppBundle:Content', 'c')
                              ->leftJoin('c.parameterObj', 'cp')
                              ->leftJoin('c.elementList', 'ce')
                              ->leftJoin('ce.linkObj', 'lo')
                              ->leftJoin('c.thumbnailObj','t')
                              ->where('cp.isPrivate = 0')
                              ->andWhere('((cp.type > 50 AND cp.type < 60) OR cp.type = 32)')
                              ->andWhere('lo.ID > 0'.$andReferrerString)
                              ->andWhere('c.ID NOT IN (:ids)')
                              ->setParameter('ids', implode(',', $ids))
                              ->distinct(TRUE)
                              ->setFirstResult(($this->offsetPage - 1) * $count)
                              ->setMaxResults($count - count($content))
                              ->getQuery();
            //echo $query->getSQL();
            $result = $query->useResultCache(TRUE, 72000)
                            ->useQueryCache(TRUE)
                            ->getResult();

            if($result){
                /** @var $r \AppBundle\Entity\Content*/
                foreach($result as $r){
                    $content[$r->getID()] = $r;
                    /*foreach($r->getElementList() as $a)
                        echo $a->getLinkObj()->getExternURL(). " -> ";*/
                }
            }
        }
    }
}