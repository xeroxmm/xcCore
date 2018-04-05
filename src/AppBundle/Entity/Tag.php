<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tag_index")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class Tag {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /** @ORM\Column(type="string", length=50) */
    protected $label;
    /** @ORM\Column(type="string", length=50, unique=true) */
    protected $slug;
    /** @ORM\Column(type="integer") */
    protected $count;
    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\TagMeta", mappedBy="tagObj")
     */
    protected $tagMeta;
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\TagMeta", mappedBy="parentTagObj")
     */
    protected $tagChildObject;

    /**
     * Many Users have Many Groups.
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Content", mappedBy="tagArray")
     * @ORM\JoinTable(name="nexus_content_tag",
     *      joinColumns={@ORM\JoinColumn(name="tID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="cID", referencedColumnName="ID")}
     *      )
     */
    protected $contentObjArray;

    function __construct(string $label = '', string $slug = '') {
        $this->label        = substr($label, 0, 40);
        $this->slug         = substr($slug, 0, 45);
        $this->count        = 1;
        $this->ID           = 0;
        $this->contentObjArray = new ArrayCollection();
    }
    function initializeWithZeroCounter(){
        $this->count = 0;
    }
    function getMetaObjOrEmptyOne():TagMeta{
        return $this->tagMeta ?? new TagMeta();
    }

    function getMetaObj():?TagMeta {
        return $this->tagMeta;
    }
    function setMetaObj(?TagMeta $obj){
        if(!$obj)
            return;

        $this->tagMeta = $obj;
    }
    function getLabel() {
        return $this->label;
    }

    function setLabel(string $l) {
        $this->label = $l;
    }

    function getSlug() {
        return $this->slug;
    }

    function setSlug(string $s) {
        $this->slug = $s;
    }

    function getID(): int {
        return $this->ID;
    }

    function getCount(): int {
        return $this->count;
    }
    /** @return Content[] */
    function getContentObjArray(){
        return $this->contentObjArray->toArray();
    }
    /** @return Collection */
    function getContentObjArrayCollection(){
        return $this->contentObjArray;
    }
    function getContentObjArraysFirstEntry():Content{
        return $this->contentObjArray->get(0) ?? new Content();
    }
    /**
     * @param Content[] $arr
     */
    function setContentObjArray(array $arr){
        $this->contentObjArray = new ArrayCollection($arr);
    }
}