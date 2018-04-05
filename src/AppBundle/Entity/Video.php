<?php

namespace AppBundle\Entity;

use AppBundle\Interfaces\ContentResource;
use AppBundle\Template\VideoHosterType;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="raw_video")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class Video implements ContentResource {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentCombination", mappedBy="videoObj")
     */
    protected $contentCombObj;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $title;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;

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

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $embedURL;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $hoster;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $length;

	/**
	 * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Image", inversedBy="videoObj")
	 * @ORM\JoinColumn(name="iID", referencedColumnName="ID", onDelete="SET NULL", nullable=true)
	 */
    protected $imageObj;

    public function getFingerprint():?string {
        return $this->title;
    }

    public function getType(): int {
        return $this->type;
    }

    public function getColourprint():?string {
        return $this->description;
    }

    public function getWidth():?int {
        return $this->dimX;
    }

    public function getHeight():?int {
        return $this->dimY;
    }

    public function getLengthString():?string {
        $hour = (int)($this->length / 3600);
        $min = (int)(($this->length - $hour * 3600) / 60);
        $sec = (int)($this->length - $hour * 3600 - $min * 60);

        $string = '';
        if ($hour > 0)
            $string = $hour . 'h ';
        if ($min > 0)
            $string .= $min . 'm ';
        if ($sec > 0)
            $string .= $min . 's ';

        return trim($string);
    }

    public function getLength():?int {
        return $this->length;
    }

    public function getThumbnailLinkURL(string $postfix = ''):?string {
        $string = $this->URL . $postfix . $this->mime;

        return $string;
    }

    public function getOriginalLinkURL():?string {
        return $this->URL;
    }

    public function getSourceLinkURL():?string {
        return NULL;
    }

    public function getEmbedString(string $objID = ""):?string {
        switch ($this->hoster) {
            case VideoHosterType::HOSTER_TYPE_YOUTUBE:
                return '<iframe ' . (!empty($objID)?'id="'.$objID.'"':"") . ' src="https://www.youtube.com/embed/' . $this->embedURL . '?enablejsapi=1&widgetid=1" allowfullscreen="true"></iframe>';
                break;
            case VideoHosterType::HOSTER_TYPE_GFYCAT:
                //return "<iframe " . (!empty($objID)?'id="'.$objID.'"':"") . " src='https://gfycat.com/ifr/{$this->embedURL}' frameborder='0' scrolling='no' width='100%' height='100%' style='position:absolute;top:0;left:0;' allowfullscreen></iframe>";
                return '<video ' . (!empty($objID)? 'id="' . $objID . '"':"") . ' style="height: 100%;" class="post" poster="//thumbs.gfycat.com/' . $this->embedURL . '-mobile.jpg" preload="auto" autoplay="autoplay" muted="muted" loop="loop" webkit-playsinline=""><source src="//giant.gfycat.com/' . $this->embedURL . '.mp4" type="video/mp4"></video>';

                break;
            case VideoHosterType::HOSTER_TYPE_IMGUR_GIFV:
                //return '<blockquote class="imgur-embed-pub" lang="en" data-id=""><a href="//imgur.com/'.$this->embedURL.'">'.$this->title.'</a></blockquote><script async src="//s.imgur.com/min/embed.js" charset="utf-8"></script>';
                return '<video ' . (!empty($objID)? 'id="' . $objID . '"':"") . ' style="height: 100%;" class="post" poster="//i.imgur.com/' . $this->embedURL . 'h.jpg" preload="auto" autoplay="autoplay" muted="muted" loop="loop" webkit-playsinline=""><source src="//i.imgur.com/' . $this->embedURL . '.mp4" type="video/mp4"></video>';
                break;
            default:
                return ''.$this->hoster;
                break;
        }
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->contentCombObj = new \Doctrine\Common\Collections\ArrayCollection();
        $this->dimX = 1280;
        $this->dimY = 720;
        $this->mime = 'e';
    }

    /**
     * Get iD
     *
     * @return integer
     */
    public function getID()
    {
        return $this->ID;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Video
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Video
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set dimX
     *
     * @param integer $dimX
     *
     * @return Video
     */
    public function setDimX($dimX)
    {
        $this->dimX = $dimX;

        return $this;
    }

    /**
     * Get dimX
     *
     * @return integer
     */
    public function getDimX()
    {
        return $this->dimX;
    }

    /**
     * Set dimY
     *
     * @param integer $dimY
     *
     * @return Video
     */
    public function setDimY($dimY)
    {
        $this->dimY = $dimY;

        return $this;
    }

    /**
     * Get dimY
     *
     * @return integer
     */
    public function getDimY()
    {
        return $this->dimY;
    }

    /**
     * Set uRL
     *
     * @param string $uRL
     *
     * @return Video
     */
    public function setURL($uRL)
    {
        $this->URL = $uRL;

        return $this;
    }

    /**
     * Get uRL
     *
     * @return string
     */
    public function getURL()
    {
        return $this->URL;
    }

    /**
     * Set mime
     *
     * @param string $mime
     *
     * @return Video
     */
    public function setMime($mime)
    {
        $this->mime = $mime;

        return $this;
    }

    /**
     * Get mime
     *
     * @return string
     */
    public function getMime()
    {
        return $this->mime;
    }

    /**
     * Set embedURL
     *
     * @param string $embedURL
     *
     * @return Video
     */
    public function setEmbedURL($embedURL)
    {
        $this->embedURL = $embedURL;

        return $this;
    }

    /**
     * Get embedURL
     *
     * @return string
     */
    public function getEmbedURL()
    {
        return $this->embedURL;
    }

    /**
     * Set hoster
     *
     * @param integer $hoster
     *
     * @return Video
     */
    public function setHoster($hoster)
    {
        $this->hoster = $hoster;

        return $this;
    }

    /**
     * Get hoster
     *
     * @return integer
     */
    public function getHoster()
    {
        return $this->hoster;
    }

    /**
     * Set length
     *
     * @param integer $length
     *
     * @return Video
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Add contentCombObj
     *
     * @param \AppBundle\Entity\ContentCombination $contentCombObj
     *
     * @return Video
     */
    public function addContentCombObj(\AppBundle\Entity\ContentCombination $contentCombObj)
    {
        $this->contentCombObj[] = $contentCombObj;

        return $this;
    }

    /**
     * Remove contentCombObj
     *
     * @param \AppBundle\Entity\ContentCombination $contentCombObj
     */
    public function removeContentCombObj(\AppBundle\Entity\ContentCombination $contentCombObj)
    {
        $this->contentCombObj->removeElement($contentCombObj);
    }

    /**
     * Get contentCombObj
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContentCombObj()
    {
        return $this->contentCombObj;
    }

    public function setImageRaw( Image $obj) { $this->imageObj = $obj; }
}
