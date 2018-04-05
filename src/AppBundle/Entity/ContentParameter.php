<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="content_parameter")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class ContentParameter {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $isPrivate;
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $isSFW;
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $isNSFW;
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $isNSFL;
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $isBulk;
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $timestamp;
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $type;
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $viewsGlobal;
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $viewsFramedDaily;
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $likesGlobal;
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $disLikesGlobal;
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $lovesGlobal;
    /**
     * @ORM\Column(type="decimal", nullable=false, precision=9, scale=4)
     */
    protected $score;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="contentParameter")
     * @ORM\JoinColumn(name="userID", referencedColumnName="ID", onDelete="CASCADE")
     */
    protected $userObj;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Content", inversedBy="parameterObj")
     * @ORM\JoinColumn(name="cID", referencedColumnName="ID", onDelete="CASCADE")
     */
    protected $contentObj;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isGif;

    function __construct() {
        $this->timestamp = time();
        $this->isPrivate = 0;
        $this->isBulk    = 0;
        $this->isNSFL    = 0;
        $this->isSFW     = 1;
        $this->isNSFW    = 0;
        $this->isGif     = 0;

        $this->viewsGlobal    = 0;
        $this->disLikesGlobal = 0;
        $this->likesGlobal    = 0;
        $this->lovesGlobal    = 0;

        $this->viewsFramedDaily = 0;
        $this->score            = 0.0000;
    }

    function getTypeURL(): string {
        $s = \AppBundle\Safety\Types\Content::getTypeString($this->type ?? 0);
        if ($s === NULL)
            return '';
        else
            return '/' . $s;
    }

    function getFullURL(): string {
        return $this->getContentObj()->getFullURL();
    }

    /**
     * Get iD
     *
     * @return integer
     */
    public function getID() {
        return $this->ID;
    }

    /**
     * Set isPrivate
     *
     * @param boolean $isPrivate
     *
     * @return ContentParameter
     */
    public function setIsPrivate($isPrivate) {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    /**
     * Get isPrivate
     *
     * @return boolean
     */
    public function getIsPrivate() {
        return $this->isPrivate;
    }

    /**
     * Set isSFW
     *
     * @param boolean $isSFW
     *
     * @return ContentParameter
     */
    public function setIsSFW($isSFW) {
        $this->isSFW = $isSFW;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsGif() {
        return $this->isGif;
    }

    /**
     * @param mixed $isGif
     */
    public function setIsGif($isGif) {
        $this->isGif = $isGif;
    }

    /**
     * Get isSFW
     *
     * @return boolean
     */
    public function getIsSFW() {
        return $this->isSFW;
    }

    /**
     * Set isNSFW
     *
     * @param boolean $isNSFW
     *
     * @return ContentParameter
     */
    public function setIsNSFW($isNSFW) {
        $this->isNSFW = $isNSFW;

        return $this;
    }

    /**
     * Get isNSFW
     *
     * @return boolean
     */
    public function getIsNSFW() {
        return $this->isNSFW;
    }

    /**
     * Set isNSFL
     *
     * @param boolean $isNSFL
     *
     * @return ContentParameter
     */
    public function setIsNSFL($isNSFL) {
        $this->isNSFL = $isNSFL;

        return $this;
    }

    /**
     * Get isNSFL
     *
     * @return boolean
     */
    public function getIsNSFL() {
        return $this->isNSFL;
    }

    /**
     * Set isBulk
     *
     * @param boolean $isBulk
     *
     * @return ContentParameter
     */
    public function setIsBulk($isBulk) {
        $this->isBulk = $isBulk;

        return $this;
    }

    /**
     * Get isBulk
     *
     * @return boolean
     */
    public function getIsBulk() {
        return $this->isBulk;
    }

    /**
     * Set timestamp
     *
     * @param integer $timestamp
     *
     * @return ContentParameter
     */
    public function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return integer
     */
    public function getTimestamp() {
        return $this->timestamp;
    }
    public function getDate(string $mask = "Y-m-d"):string {
        return date($mask, $this->timestamp);
    }
    /**
     * Set type
     *
     * @param integer $type
     *
     * @return ContentParameter
     */
    public function setType($type) {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set userObj
     *
     * @param \AppBundle\Entity\User $userObj
     *
     * @return ContentParameter
     */
    public function setUserObj(\AppBundle\Entity\User $userObj = null) {
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

    /**
     * Set contentObj
     *
     * @param \AppBundle\Entity\Content $contentObj
     *
     * @return ContentParameter
     */
    public function setContentObj(\AppBundle\Entity\Content $contentObj = null) {
        $this->contentObj = $contentObj;

        return $this;
    }

    /**
     * Get contentObj
     *
     * @return \AppBundle\Entity\Content
     */
    public function getContentObj() {
        return $this->contentObj;
    }

    /**
     * @ORM\PrePersist
     */
    public function incrementViewGlobal() {
        $this->viewsGlobal = $this->viewsGlobal + 1;
    }

    /**
     * @ORM\PrePersist
     */
    public function incrementViewFramedDaily() {
        $this->viewsFramedDaily = $this->viewsFramedDaily + 1;
    }

    public function setViewsFrameDaily(int $amount = 1) {
        $this->viewsFramedDaily = $amount;
    }

    /**
     * @param int $factor
     */
    public function incrementLikeValue(int $factor = 1) {
        $this->likesGlobal = $this->likesGlobal + 1 * $factor;
    }

    /**
     * @param int $factor
     */
    public function incrementDisLikeValue(int $factor = 1) {
        $this->disLikesGlobal = $this->disLikesGlobal + 1 * $factor;
    }
    /**
     * @param int $factor
     */
    public function incrementLoveValue(int $factor = 1){
        $this->lovesGlobal = $this->lovesGlobal + 1 * $factor;
    }
    public function setScore(float $score){
        $this->score = $score;
    }
    public function getScore():float {
        return $this->score;
    }
}
