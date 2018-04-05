<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="content_distiller_view_framed")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 * @UniqueEntity(
 *     fields={"ID", "cID"},
 *     errorPath="distillerEntity",
 *     message="This frame is already in use on that content obj."
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class ContentViewDistillerFramed {
    const TIME_FRAME_HOURS_HOURLY      = 1;
    const TIME_FRAME_HOURS_DAILY_ONE   = 24;
    const TIME_FRAME_HOURS_DAILY_THREE = 72;
    const TIME_FRAME_HOURS_WEEKLY      = 168;
    const TIME_FRAME_HOURS_MONTHLY     = 720;

    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Content", inversedBy="contentViewDistillerFramed")
     * @ORM\JoinColumn(name="cID", referencedColumnName="ID", onDelete="CASCADE", nullable=false)
     */
    protected $contentObj;
    /**
     * @ORM\Column(type="integer")
     */
    protected $timeFrameSeconds;
    /**
     * @ORM\Column(type="integer")
     */
    protected $timestampFramed;
    /**
     * @ORM\Column(type="integer")
     */
    protected $timestamp;
    /**
     * @ORM\Column(type="integer")
     */
    protected $views;

    public function __construct() {
        $this->timestamp = time();
        $this->views     = 0;
    }

    public function setTimestampFramedByFrameSizedInSeconds(int $timeFrameInSeconds) {
        $this->timeFrameSeconds = $timeFrameInSeconds;
        $this->timestampFramed  = ((int)(time() / $timeFrameInSeconds)) * $timeFrameInSeconds;
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
    public function getTimeFrameSeconds() {
        return $this->timeFrameSeconds;
    }

    /**
     * @param mixed $timeFrameSeconds
     */
    public function setTimeFrameSeconds($timeFrameSeconds) {
        $this->timeFrameSeconds = $timeFrameSeconds;
    }

    /**
     * @return mixed
     */
    public function getTimestampFramed() {
        return $this->timestampFramed;
    }

    /**
     * @param mixed $timestampFramed
     */
    public function setTimestampFramed($timestampFramed) {
        $this->timestampFramed = $timestampFramed;
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
    public function getViews() {
        return $this->views;
    }

    /**
     * @param mixed $views
     */
    public function setViews($views) {
        $this->views = $views;
    }
    /**
     * @ORM\PrePersist
     */
    public function increaseViewByOne(){
        $this->views = $this->views + 1;
    }
}