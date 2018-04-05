<?php
// src/AppBundle/Repository/UserRepository.php
namespace AppBundle\Repository;

use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository implements UserLoaderInterface {
    public function loadUserByUsername($username) {
        return $this->createQueryBuilder('u')
                    ->leftJoin('u.securityEmailObj','us')
                    ->where('u.username = :username OR us.email = :email')
                    ->setParameter('username', $username)
                    ->setParameter('email', $username)
                    ->getQuery()
                    ->getOneOrNullResult();
    }
}