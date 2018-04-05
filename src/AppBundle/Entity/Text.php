<?php

namespace AppBundle\Entity;

use AppBundle\Interfaces\ContentResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="raw_text")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class Text implements ContentResource {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentCombination", mappedBy="textObj")
     */
    protected $contentCombObj;

    /**
     * @ORM\Column(type="integer")
     */
    protected $type;

    /**
     * @ORM\Column(type="text")
     */
    protected $description;

    public function getFingerprint():?string {
        // TODO: Implement getTitle() method.
    }

    public function getType():int { return $this->type ?? 0; }

    public function getColourprint():?string {
        // TODO: Implement getDescription() method.
    }

    public function getWidth():?int {
        // TODO: Implement getWidth() method.
    }

    public function getHeight():?int {
        // TODO: Implement getHeight() method.
    }

    public function getLengthString():?string {
        // TODO: Implement getLengthString() method.
    }

    public function getLength():?int {
        // TODO: Implement getLength() method.
    }

    public function getThumbnailLinkURL(string $postfix = ''):?string {
        // TODO: Implement getThumbnailLinkURL() method.
    }

    public function getOriginalLinkURL():?string {
        // TODO: Implement getOriginalLinkURL() method.
    }

    public function getSourceLinkURL():?string {
        // TODO: Implement getSourceLinkURL() method.
    }

    public function getEmbedString():?string {
        return NULL;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->contentCombObj = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set title
     *
     * @param integer $type
     *
     * @return Text
     */
    public function setType(int $type) {
        $this->type = $type;

        return $this;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Text
     */
    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * Add contentCombObj
     *
     * @param \AppBundle\Entity\ContentCombination $contentCombObj
     *
     * @return Text
     */
    public function addContentCombObj(\AppBundle\Entity\ContentCombination $contentCombObj) {
        $this->contentCombObj[] = $contentCombObj;

        return $this;
    }

    /**
     * Remove contentCombObj
     *
     * @param \AppBundle\Entity\ContentCombination $contentCombObj
     */
    public function removeContentCombObj(\AppBundle\Entity\ContentCombination $contentCombObj) {
        $this->contentCombObj->removeElement($contentCombObj);
    }

    /**
     * Get contentCombObj
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContentCombObj() {
        return $this->contentCombObj;
    }
}
