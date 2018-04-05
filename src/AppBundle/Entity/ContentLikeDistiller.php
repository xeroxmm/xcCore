<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="content_distiller_like_continuous")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 * @UniqueEntity(
 *     fields={"timestamp", "cID"},
 *     errorPath="cID",
 *     message="This content ID is already in use on that timestamp."
 * )
 */
class ContentLikeDistiller {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Content", inversedBy="contentLikeDistiller")
     * @ORM\JoinColumn(name="cID", referencedColumnName="ID", onDelete="CASCADE", nullable=false)
     */
    protected $contentObj;
    /**
     * @ORM\Column(type="integer")
     */
    protected $timestamp;
    /**
     * @ORM\Column(type="decimal", precision=9, scale=4)
     */
    protected $valueOld;
    /**
     * @ORM\Column(type="decimal", precision=9, scale=4)
     */
    protected $valueNew;
    /**
     * @ORM\Column(type="decimal", precision=9, scale=4)
     */
    protected $valueReal;
    /**
     * @ORM\Column(type="decimal", precision=9, scale=4)
     */
    protected $deltaOldToNew;

    public function __construct() {
        $this->timestamp     = time();
        $this->valueOld      = 0.0001;
        $this->valueNew      = 0.0001;
        $this->valueReal     = 0.0001;
        $this->deltaOldToNew = 0.0001;
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
    public function getContentObj() {
        return $this->contentObj;
    }

    /**
     * @param mixed $contentObj
     */
    public function setContentObj($contentObj) {
        $this->contentObj = $contentObj;
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
    public function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;
    }

    /**
     * @return mixed
     */
    public function getValueOld() {
        return $this->valueOld;
    }

    /**
     * @param mixed $valueOld
     */
    public function setValueOld($valueOld) {
        $this->valueOld = $valueOld;
    }

    /**
     * @return mixed
     */
    public function getValueNew() {
        return $this->valueNew;
    }

    /**
     * @param mixed $valueNew
     */
    public function setValueNew($valueNew) {
        $this->valueNew = $valueNew;
    }

    /**
     * @return mixed
     */
    public function getValueReal() {
        return $this->valueReal;
    }

    /**
     * @param mixed $valueReal
     */
    public function setValueReal($valueReal) {
        $this->valueReal = $valueReal;
    }

    /**
     * @return mixed
     */
    public function getDeltaOldToNew() {
        return $this->deltaOldToNew;
    }

    /**
     * @param mixed $deltaOldToNew
     */
    public function setDeltaOldToNew($deltaOldToNew) {
        $this->deltaOldToNew = $deltaOldToNew;
    }
}