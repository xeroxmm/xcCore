<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tag_meta")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class TagMeta {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Tag", inversedBy="tagMeta")
     * @ORM\JoinColumn(name="tID", referencedColumnName="ID", onDelete="CASCADE")
     */
    protected $tagObj;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $title;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Image", inversedBy="tagObj")
     * @ORM\JoinColumn(name="thumbID", referencedColumnName="ID", onDelete="SET NULL", nullable=true)
     *
     */
    protected $thumbnailObj;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Tag", inversedBy="tagChildObject")
     * @ORM\JoinColumn(name="tPID", referencedColumnName="ID", onDelete="CASCADE", nullable=true)
     */
    protected $parentTagObj;

    public function __construct() {

    }

    /**
     * @return integer
     */
    public function getID():?int {
        return $this->ID;
    }

    /**
     * @param integer $ID
     */
    public function setID(int $ID) {
        $this->ID = $ID;
    }

    /**
     * @return Tag
     */
    public function getTagObj():?Tag {
        return $this->tagObj;
    }

    /**
     * @param Tag $tagObj
     */
    public function setTagObj(Tag $tagObj) {
        $this->tagObj = $tagObj;
    }

    /**
     * @return null|string
     */
    public function getDescription():?string {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description) {
        $this->description = $description;
    }

    /**
     * @return null|string
     */
    public function getTitle():?string {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title) {
        $this->title = $title;
    }

    /**
     * @return null|Image
     */
    public function getThumbnailObj():?Image {
        return $this->thumbnailObj;
    }

    /**
     * @param Image $thumbnailObj
     */
    public function setThumbnailObj(Image $thumbnailObj) {
        $this->thumbnailObj = $thumbnailObj;
    }

    /**
     * @return null|Tag
     */
    public function getParentTagObj():?Tag {
        return $this->parentTagObj;
    }

    /**
     * @param Tag $parentTagObj
     */
    public function setParentTagObj(Tag $parentTagObj) {
        $this->parentTagObj = $parentTagObj;
    }

}