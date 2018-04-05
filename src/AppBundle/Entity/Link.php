<?php
namespace AppBundle\Entity;

use AppBundle\Interfaces\ContentResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="raw_link")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class Link {
    private $domain = FALSE;
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentCombination", mappedBy="linkObj")
     */
    protected $contentCombObj;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $externURL;

    /**
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * @ORM\Column(type="string")
     */
    protected $description;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->contentCombObj = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set externURL
     *
     * @param string $externURL
     *
     * @return Link
     */
    public function setExternURL($externURL)
    {
        $this->externURL = $externURL;

        return $this;
    }

    /**
     * Get externURL
     *
     * @return string
     */
    public function getExternURL()
    {
        return $this->externURL;
    }

    public function getDomainName(){
        if(!$this->domain){
            $temp = explode('//',$this->getExternURL(),2);
            $temp = explode('/',$temp[1],2);
            $temp = explode('.',$temp[0]);
            $this->domain = $temp[count($temp)-2] ?? 'EXTERN';
        }
        return $this->domain;
    }
    /**
     * Set title
     *
     * @param string $title
     *
     * @return Link
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
     * @return Link
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Add contentCombObj
     *
     * @param \AppBundle\Entity\ContentCombination $contentCombObj
     *
     * @return Link
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
}
