<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_security_control")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class UserSecurityControl {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\User", inversedBy="securityControlObj")
     * @ORM\JoinColumn(name="uID", referencedColumnName="ID", onDelete="CASCADE", nullable=false)
     */
    protected $userObj;
    /**
     * @ORM\Column(type="integer")
     */
    protected $failedLoginAmount;
    /**
     * @ORM\Column(type="integer")
     */
    protected $timeLastFailedLogin;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isActive;
    /**
     * @ORM\Column(type="boolean")
     */
    protected $isBlocked;
    /**
     * @ORM\Column(type="boolean")
     */
    protected $isDeleted;
    /**
     * @ORM\Column(type="boolean")
     */
    protected $isBot;
    /**
     * @ORM\Column(type="boolean")
     */
    protected $isGeneric;

    public function __construct() {
        $this->isGeneric = TRUE;
        $this->isActive = TRUE;
        $this->isBlocked = FALSE;
        $this->isDeleted = FALSE;
        $this->isBot = FALSE;
        $this->failedLoginAmount = 0;
        $this->timeLastFailedLogin = 0;
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
    public function setUserObj(User $userObj) {
        $this->userObj = $userObj;
    }

    /**
     * @return mixed
     */
    public function getFailedLoginAmount() {
        return $this->failedLoginAmount;
    }

    /**
     * @param mixed $failedLoginAmount
     */
    public function setFailedLoginAmount($failedLoginAmount) {
        $this->failedLoginAmount = $failedLoginAmount;
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

    /**
     * @return mixed
     */
    public function getisActive() {
        return $this->isActive;
    }

    /**
     * @param mixed $isActive
     */
    public function setIsActive($isActive) {
        $this->isActive = $isActive;
    }

    /**
     * @return mixed
     */
    public function getisBlocked() {
        return $this->isBlocked;
    }

    /**
     * @param mixed $isBlocked
     */
    public function setIsBlocked($isBlocked) {
        $this->isBlocked = $isBlocked;
    }

    /**
     * @return mixed
     */
    public function getisDeleted() {
        return $this->isDeleted;
    }

    /**
     * @param mixed $isDeleted
     */
    public function setIsDeleted($isDeleted) {
        $this->isDeleted = $isDeleted;
    }

    /**
     * @return mixed
     */
    public function getisBot() {
        return $this->isBot;
    }

    /**
     * @param mixed $isBot
     */
    public function setIsBot($isBot) {
        $this->isBot = $isBot;
    }

    /**
     * @return mixed
     */
    public function getisGeneric() {
        return $this->isGeneric;
    }

    /**
     * @param mixed $isGeneric
     */
    public function setIsGeneric($isGeneric) {
        $this->isGeneric = $isGeneric;
    }
}