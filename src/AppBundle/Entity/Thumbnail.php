<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="image_thumbnail")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class Thumbnail {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $URL;
    /**
     * @ORM\Column(type="string")
     */
    protected $alt;
    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    protected $mime;
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Content", mappedBy="thumbnailObj")
     */
    protected $contentObj;

    /**
     * Constructor
     */
    public function __construct() {
        $this->contentObj = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function setMime(string $mime) {
        $this->mime = $mime;
    }

    public function getMime(): string {
        return $this->mime;
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
     * Set uRL
     *
     * @param string $uRL
     *
     * @return Thumbnail
     */
    public function setURL($uRL) {
        $this->URL = $uRL;

        return $this;
    }

    /**
     * Get uRL
     *
     * @return string
     */
    public function getURL() {
        return $this->URL;
    }

    /**
     * Set alt
     *
     * @param string $alt
     *
     * @return Thumbnail
     */
    public function setAlt($alt) {
        $this->alt = $alt;

        return $this;
    }

    /**
     * Get alt
     *
     * @return string
     */
    public function getAlt() {
        return $this->alt;
    }

    /**
     * Add contentObj
     *
     * @param \AppBundle\Entity\Content $contentObj
     *
     * @return Thumbnail
     */
    public function addContentObj(\AppBundle\Entity\Content $contentObj) {
        $this->contentObj[] = $contentObj;

        return $this;
    }

    /**
     * Remove contentObj
     *
     * @param \AppBundle\Entity\Content $contentObj
     */
    public function removeContentObj(\AppBundle\Entity\Content $contentObj) {
        $this->contentObj->removeElement($contentObj);
    }

    /**
     * Get contentObj
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContentObj() {
        return $this->contentObj;
    }
}
