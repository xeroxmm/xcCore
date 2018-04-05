<?php
namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="content_meta")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class ContentMeta {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Content", inversedBy="contentMeta")
     * @ORM\JoinColumn(name="cID", referencedColumnName="ID", onDelete="CASCADE")
     */
    protected $contentObj;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $srcURL;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $hoster;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $subDomain;

    /**
     * @ORM\Column(type="integer")
     */
    protected $mediaIn;

    function __construct() {
        $this->mediaIn = 1;
    }

    public function setMediaIn(int $count){ $this->mediaIn = $count; }
    public function getMediaIn():int { return $this->mediaIn; }

    /**
     * @return integer
     */
    public function getID() {
        return $this->ID;
    }


    /**
     * @return Content
     */
    public function getContentObj():Content {
        return $this->contentObj;
    }

    /**
     * @param Content $contentObj
     */
    public function setContentObj(Content $contentObj) {
        $this->contentObj = $contentObj;
    }

    /**
     * @return string
     */
    public function getSrcURL():string {
        return $this->srcURL;
    }

    /**
     * @param string $srcURL
     */
    public function setSrcURL(string $srcURL) {
        $this->srcURL = $srcURL;
    }

    /**
     * @return string
     */
    public function getHoster():string {
        return $this->hoster;
    }

    /**
     * @param string $hoster
     */
    public function setHoster($hoster) {
        $this->hoster = $hoster;
    }

    /**
     * @return string
     */
    public function getSubDomain():string {
        return $this->subDomain;
    }

    /**
     * @param string $subDomain
     */
    public function setSubDomain(string $subDomain) {
        $this->subDomain = $subDomain;
    }
}