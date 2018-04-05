<?php
namespace AppBundle\Template;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class ContentLoader {
    private $typeMain;
    private $res;
    private $em;

    function __construct(EntityManagerInterface $em, int $mainType){
        $this->typeMain = $mainType;
        $this->em = $em;
    }

    public function loadByBasedID(string $bID, int $base = 36){
        $ID = base_convert($bID, $base, 10);
        $this->loadByID( $ID );
    }
    public function loadByID(int $ID){
        $res = $this->em->createQueryBuilder()
                  ->select('c,cp,cm,ce,ct,u,cc,ccm,sct,ccp')
                  ->from('AppBundle:Content', 'c')
                  ->leftJoin('c.parameterObj', 'cp')
                  ->leftJoin('c.contentMeta', 'cm')
                  ->leftJoin('c.elementList', 'ce')
                  ->leftJoin('c.thumbnailObj', 'ct')
                  ->leftJoin('cp.userObj', 'u')
                  ->leftJoin('ce.childContentObj','cc')
                  ->leftJoin('cc.contentMeta','ccm')
                  ->leftJoin('cc.parameterObj','ccp')
                  ->leftJoin('cc.thumbnailObj','sct')
                  ->where('c.ID = ' . $ID)
                  ->andWhere('cp.type = ' . $this->typeMain)
                  ->getQuery()->getOneOrNullResult();
    }
}