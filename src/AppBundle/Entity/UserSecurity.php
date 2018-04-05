<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_security")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class UserSecurity {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\User", inversedBy="securityObj")
     * @ORM\JoinColumn(name="uID", referencedColumnName="ID", onDelete="CASCADE", nullable=false)
     */
    protected $userObj;
    /**
     * @ORM\Column(type="string")
     */
    protected $pwHash;

    /**
     * @ORM\Column(type="string",length=50)
     */
    protected $apiKey;

    /**
     * @ORM\Column(type="string",length=50)
     */
    protected $apiHash;

    /**
     * Get iD
     *
     * @return integer
     */
    public function getID() {
        return $this->ID;
    }

    /**
     * Set pwHash
     *
     * @param string $pwHash
     *
     * @return UserSecurity
     */
    public function setPwHash($pwHash) {
        $this->pwHash = $pwHash;

        return $this;
    }

    /**
     * Get pwHash
     *
     * @return string
     */
    public function getPwHash() {
        return $this->pwHash;
    }

    /**
     * Set userObj
     *
     * @param \AppBundle\Entity\User $userObj
     *
     * @return UserSecurity
     */
    public function setUserObj(\AppBundle\Entity\User $userObj) {
        $this->userObj = $userObj;

        return $this;
    }

    /**
     * Get userObj
     *
     * @return \AppBundle\Entity\User
     */
    public function getUserObj() {
        return $this->userObj;
    }

    public function setApiKey(string $key) { $this->apiKey = $key; }
    public function getApiKey() { return $this->apiKey; }

    public function setApiPassword(string $password){ $this->apiHash = $password; }
    public function getApiPassword() { return $this->apiHash; }
}
