<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_sessions")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class UserSessions {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="sessionObj")
     * @ORM\JoinColumn(name="uID", referencedColumnName="ID", onDelete="CASCADE", nullable=false)
     */
    protected $userObj;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $sessionCookieID;
    /**
     * @ORM\Column(type="string", length=10)
     */
    protected $sessionID;

    /**
     * @ORM\Column(type="integer")
     */
    protected $timeTillAlive;

    /**
     * @ORM\Column(type="integer")
     */
    protected $startTime;
    /**
     * @ORM\Column(type="integer")
     */
    protected $endTime;
    /**
     * @ORM\Column(type="boolean")
     */
    protected $isActive;

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
    public function getSessionID() {
        return $this->sessionID;
    }

    /**
     * @param mixed $sessionID
     */
    public function setSessionID($sessionID) {
        $this->sessionID = $sessionID;
    }

    /**
     * @return User
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
    public function getSessionCookieID() {
        return $this->sessionCookieID;
    }

    /**
     * @param mixed $sessionCookieID
     */
    public function setSessionCookieID($sessionCookieID) {
        $this->sessionCookieID = $sessionCookieID;
    }

    /**
     * @return mixed
     */
    public function getTimeTillAlive() {
        return $this->timeTillAlive;
    }

    /**
     * @param mixed $timeTillAlive
     */
    public function setTimeTillAlive($timeTillAlive) {
        $this->timeTillAlive = $timeTillAlive;
    }

    /**
     * @return mixed
     */
    public function getStartTime() {
        return $this->startTime;
    }

    /**
     * @param mixed $startTime
     */
    public function setStartTime($startTime) {
        $this->startTime = $startTime;
    }

    /**
     * @return mixed
     */
    public function getEndTime() {
        return $this->endTime;
    }

    /**
     * @param mixed $endTime
     */
    public function setEndTime($endTime) {
        $this->endTime = $endTime;
    }
}