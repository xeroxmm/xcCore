<?php

namespace AppBundle\Entity;

use AppBundle\Interfaces\ContentResource;
use AppBundle\Template\ContentType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="raw_image")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 *
 */
class Image implements ContentResource {
    private $type = ContentType::TYPE_IMAGE;

    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;

    /** @ORM\Column(type="smallint", nullable=false, name="hIdent") */
    protected $hosterID;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentCombination", mappedBy="imageObj")
     */
    protected $contentCombObj;

    /**
     * @ORM\Column(type="string", nullable=true, unique=true)
     */
    protected $fingerprint;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $colourprint;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $dimX;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $dimY;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $URL;

    /**
     * @ORM\Column(type="string", nullable=false, length=5)
     */
    protected $mime;

    /** @ORM\Column(type="boolean", nullable=false) */
    protected $isSFW;

    /** @ORM\OneToMany(targetEntity="AppBundle\Entity\Video", mappedBy="imageObj") */
    protected $videoObj;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Content", mappedBy="thumbnailObj")
     */
    protected $contentObj;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\TagMeta", mappedBy="thumbnailObj")
     */
    protected $tagObj;

    /** @ORM\Column(type="integer", nullable=true) */
    protected $time;

    /** @ORM\Column(type="integer", nullable=false) */
    protected $filesize;

    /**
     * Constructor
     */
    public function __construct() {
        $this->contentCombObj = new ArrayCollection();
        $this->tagObj         = new ArrayCollection();
        $this->contentObj     = new ArrayCollection();

        $this->isSFW    = TRUE;
        $this->hosterID = 1;
        $this->time     = time();
        $this->filesize = 0;
    }

    public function getTime() {
        return $this->time;
    }

    public function setContentObj(Content $obj) {
        $this->contentObj = $obj;
    }

    public function getContentObj(): Content {
        return $this->getContentObj();
    }

    public function setSFW(bool $s) {
        $this->isSFW = $s;
    }

    public function isSFW(): bool {
        return $this->isSFW;
    }

    public function getFingerprint():?string {
        return $this->fingerprint;
    }

    public function getType(): int {
        return $this->type;
    }

    public function getColourprint():?string {
        return $this->colourprint;
    }

    public function getWidth():?int {
        return $this->dimX;
    }

    public function getHeight():?int {
        return $this->dimY;
    }

    public function getLengthString():?string {
        return NULL;
    }

    public function getLength():?int {
        return NULL;
    }

    public function getThumbnailLinkURL(string $postfix = ''):?string {
        $string = $this->URL . $postfix . '.' . $this->mime;

        return $string;
    }

    public function getOriginalLinkURL():?string {
        return $this->URL;
    }

    public function getSourceLinkURL():?string {
        return NULL;
    }

    public function getEmbedString():?string {
        return NULL;
    }

    public function getFilesize(): int {
        return $this->filesize;
    }

    public function setFilesize(int $size) {
        $this->filesize = $size;
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
     * @param string $fingerprint
     *
     * @return Image
     */
    public function setFingerprint($fingerprint) {
        $this->fingerprint = $fingerprint;

        return $this;
    }

    /**
     * Set description
     *
     * @param string $colourprint
     *
     * @return Image
     */
    public function setColourprint($colourprint) {
        $this->colourprint = $colourprint;

        return $this;
    }

    /**
     * Set dimX
     *
     * @param integer $dimX
     *
     * @return Image
     */
    public function setDimX($dimX) {
        $this->dimX = $dimX;

        return $this;
    }

    /**
     * Get dimX
     *
     * @return integer
     */
    public function getDimX() {
        return $this->dimX;
    }

    /**
     * Set dimY
     *
     * @param integer $dimY
     *
     * @return Image
     */
    public function setDimY($dimY) {
        $this->dimY = $dimY;

        return $this;
    }

    /**
     * Get dimY
     *
     * @return integer
     */
    public function getDimY() {
        return $this->dimY;
    }

    /**
     * Set uRL
     *
     * @param string $uRL
     *
     * @return Image
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
     * Set mime
     *
     * @param string $mime
     *
     * @return Image
     */
    public function setMime($mime) {
        $this->mime = strtolower($mime);

        return $this;
    }

    /**
     * Get mime
     *
     * @return string
     */
    public function getMime() {
        return $this->mime;
    }

    /**
     * Add contentCombObj
     *
     * @param \AppBundle\Entity\ContentCombination $contentCombObj
     *
     * @return Image
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
