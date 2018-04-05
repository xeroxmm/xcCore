<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_security_email")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class UserSecurityEmail {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\User", inversedBy="securityEmailObj")
     * @ORM\JoinColumn(name="uID", referencedColumnName="ID", onDelete="CASCADE", nullable=false)
     */
    protected $userObj;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $email;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $password;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isPrimary;

    /**
     * @ORM\Column(type="integer")
     */
    protected $failedLogin;
    /**
     * @ORM\Column(type="integer")
     */
    protected $timeLastFailedLogin;

    public function __construct() {

    }

    /**
     * @return mixed
     */
    public function getID() {
        return $this->ID;
    }

    /**
     * @param mixed $ID
     */
    public function setID($ID) {
        $this->ID = $ID;
    }

    /**
     * @return mixed
     */
    public function getUserObj() {
        return $this->userObj;
    }

    /**
     * @param mixed $userObj
     */
    public function setUserObj($userObj) {
        $this->userObj = $userObj;
    }

    /**
     * @return mixed
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password) {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getisPrimary() {
        return $this->isPrimary;
    }

    /**
     * @param mixed $isPrimary
     */
    public function setIsPrimary($isPrimary) {
        $this->isPrimary = $isPrimary;
    }

    /**
     * @return mixed
     */
    public function getFailedLogin() {
        return $this->failedLogin;
    }

    /**
     * @param mixed $failedLogin
     */
    public function setFailedLogin($failedLogin) {
        $this->failedLogin = $failedLogin;
    }

    /**
     * @return mixed
     */
    public function getTimeLastFailedLogin() {
        return $this->timeLastFailedLogin;
    }

    /**
     * @param mixed $timeLastFailedLogin
     */
    public function setTimeLastFailedLogin($timeLastFailedLogin) {
        $this->timeLastFailedLogin = $timeLastFailedLogin;
    }

}