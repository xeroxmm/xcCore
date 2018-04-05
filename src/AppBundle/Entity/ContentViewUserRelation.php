<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="content_view_user_relation")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 *
 * @UniqueEntity(
 *     fields={"uID", "cID"},
 *     errorPath="cID",
 *     message="This content ID is already in use on that user."
 * )
 */
class ContentViewUserRelation {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="contentViewUserRelationObj")
     * @ORM\JoinColumn(name="uID", referencedColumnName="ID", onDelete="CASCADE", nullable=false)
     */
    protected $userObj;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Content", inversedBy="contentViewUserRelationObj")
     * @ORM\JoinColumn(name="cID", referencedColumnName="ID", onDelete="CASCADE", nullable=false)
     */
    protected $contentObj;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2)
     */
    protected $factor;
    /**
     * @ORM\Column(type="integer")
     */
    protected $timestamp;

    public function __construct() {
        $this->timestamp = time();
        $this->factor = 0;
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
    public function getContentObj() {
        return $this->contentObj;
    }

    /**
     * @param mixed $contentObj
     */
    public function setContentObj(Content $contentObj) {
        $this->contentObj = $contentObj;
    }

    /**
     * @return mixed
     */
    public function getFactor() {
        return $this->factor;
    }

    /**
     * @param float $factor
     */
    public function setFactor(float $factor) {
        $this->factor = min(round($factor,2),9.99);
    }

    /**
     * @return mixed
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * @param mixed $timestamp
     */
    public function setTimestamp(int $timestamp) {
        $this->timestamp = $timestamp;
    }
}