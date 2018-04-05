<?php

namespace AppBundle\Security\User\Cookie;

use AppBundle\Entity\UserSessions;
use AppBundle\Safety\Types\Cookie;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

class GenericHandler {
    protected $em;
    protected $cookie;

    /** @var  mixed */
    private $lastQueryResult;

    public function __construct(ParameterBag $cookie, EntityManagerInterface $em) {
        $this->em = $em;
        $this->cookie = $cookie;
    }

    public function isCookieInDB():bool {
        if(!$this->cookie->has(Cookie::TYPE_KEY_COOKIE_ID) || !$this->cookie->has(Cookie::TYPE_KEY_SESSION_ID)) {
            $this->lastQueryResult = NULL;
            return FALSE;
        }

        //Do the Database stuff
        $query = $this->getQueryBuilder();
        $res = $query->select('us')
        ->from('AppBundle:UserSessions','us')
        ->where('us.sessionCookieID = :cSID')
        ->andWhere('us.sessionID = :sID')
        ->setParameter(':cSID', $this->cookie->get(Cookie::TYPE_KEY_COOKIE_ID,'_'))
        ->setParameter(':sID', $this->cookie->get(Cookie::TYPE_KEY_SESSION_ID,'_'))
        ->getQuery()->getOneOrNullResult();

        $this->lastQueryResult = $res;

        if(!$res){
            $this->cookie->remove(Cookie::TYPE_KEY_COOKIE_ID);
            $this->cookie->remove(Cookie::TYPE_KEY_SESSION_ID);
        }

        return (bool)$res;
    }
    public function getUserID():int{
        if(!$this->lastQueryResult)
            return 0;
        /** @var $res UserSessions */
        $res = $this->lastQueryResult;
        return $res->getUserObj()->getID();
    }
    private function getQueryBuilder():QueryBuilder{
        return $this->em->createQueryBuilder();
    }
}