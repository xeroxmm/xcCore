<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_fingerprint_session")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class UserFingerprintSession {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="fingerprintSessionObj")
     * @ORM\JoinColumn(name="uID", referencedColumnName="ID", onDelete="CASCADE", nullable=false)
     */
    protected $userObj;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\FingerprintInfo", inversedBy="userFingerprintSession")
     * @ORM\JoinColumn(name="fpID", referencedColumnName="ID", onDelete="CASCADE", nullable=false)
     */
    protected $fingerprintObj;

    /**
     * @ORM\Column(type="string", length=32)
     */
    protected $IP;
    /**
     * @ORM\Column(type="integer")
     */
    protected $timeLastUsed;

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
    public function getFingerprintObj() {
        return $this->fingerprintObj;
    }

    /**
     * @param mixed $fingerprintObj
     */
    public function setFingerprintObj(FingerprintInfo $fingerprintObj) {
        $this->fingerprintObj = $fingerprintObj;
    }

    /**
     * @return mixed
     */
    public function getIP() {
        return $this->IP;
    }

    /**
     * @param mixed $IP
     */
    public function setIP($IP) {
        $this->IP = $IP;
    }

    /**
     * @return mixed
     */
    public function getTimeLastUsed() {
        return $this->timeLastUsed;
    }

    /**
     * @param mixed $timeLastUsed
     */
    public function setTimeLastUsed($timeLastUsed) {
        $this->timeLastUsed = $timeLastUsed;
    }
}