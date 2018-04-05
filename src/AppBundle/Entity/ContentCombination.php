<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="content_combination")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class ContentCombination {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $title;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Content", inversedBy="elementList")
     * @ORM\JoinColumn(name="cID", referencedColumnName="ID", onDelete="CASCADE", nullable=false)
     */
    protected $contentObj;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Image", inversedBy="contentCombObj")
     * @ORM\JoinColumn(name="imgID", referencedColumnName="ID", onDelete="CASCADE")
     */
    protected $imageObj;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Text", inversedBy="contentCombObj")
     * @ORM\JoinColumn(name="txtID", referencedColumnName="ID", onDelete="CASCADE")
     */
    protected $textObj;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Link", inversedBy="contentCombObj")
     * @ORM\JoinColumn(name="linkID", referencedColumnName="ID", onDelete="CASCADE")
     */
    protected $linkObj;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Video", inversedBy="contentCombObj")
     * @ORM\JoinColumn(name="vidID", referencedColumnName="ID", onDelete="CASCADE")
     */
    protected $videoObj;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Content", inversedBy="collectionObj")
     * @ORM\JoinColumn(name="ccID", referencedColumnName="ID", onDelete="SET NULL", nullable=true)
     */
    protected $childContentObj;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $position;

    public function setTitle(string $title) {
        $this->title = $title;
    }

    public function getTitle():?string {
        return $this->title;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set position
     *
     * @param integer $position
     *
     * @return ContentCombination
     */
    public function setPosition($position) {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition() {
        return $this->position;
    }

    /**
     * Set contentObj
     *
     * @param \AppBundle\Entity\Content $contentObj
     *
     * @return ContentCombination
     */
    public function setContentObj(\AppBundle\Entity\Content $contentObj) {
        $this->contentObj = $contentObj;

        return $this;
    }

    public function setSubContentObj(Content $contentObj) {
        $this->childContentObj = $contentObj;
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
     * Get subContentObj
     *
     * @return \AppBundle\Entity\Content
     */
    public function getSubContentObj() {
        return $this->childContentObj;
    }

    /**
     * Set imageObj
     *
     * @param \AppBundle\Entity\Image $imageObj
     *
     * @return ContentCombination
     */
    public function setImageObj(\AppBundle\Entity\Image $imageObj = null) {
        $this->imageObj = $imageObj;

        return $this;
    }

    /**
     * Get imageObj
     *
     * @return \AppBundle\Entity\Image
     */
    public function getImageObj() {
        return $this->imageObj;
    }

    /**
     * Set textObj
     *
     * @param \AppBundle\Entity\Text $textObj
     *
     * @return ContentCombination
     */
    public function setTextObj(\AppBundle\Entity\Text $textObj = null) {
        $this->textObj = $textObj;

        return $this;
    }

    /**
     * Get textObj
     *
     * @return \AppBundle\Entity\Text
     */
    public function getTextObj() {
        return $this->textObj;
    }

    /**
     * Set linkObj
     *
     * @param \AppBundle\Entity\Link $linkObj
     *
     * @return ContentCombination
     */
    public function setLinkObj(\AppBundle\Entity\Link $linkObj = null) {
        $this->linkObj = $linkObj;

        return $this;
    }

    /**
     * Get linkObj
     *
     * @return \AppBundle\Entity\Link
     */
    public function getLinkObj() {
        return $this->linkObj;
    }

    /**
     * Set videoObj
     *
     * @param \AppBundle\Entity\Video $videoObj
     *
     * @return ContentCombination
     */
    public function setVideoObj(\AppBundle\Entity\Video $videoObj = null) {
        $this->videoObj = $videoObj;

        return $this;
    }

    /**
     * Get videoObj
     *
     * @return \AppBundle\Entity\Video
     */
    public function getVideoObj() {
        return $this->videoObj;
    }
}
