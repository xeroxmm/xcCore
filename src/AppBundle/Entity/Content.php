<?php

namespace AppBundle\Entity;

use AppBundle\Interfaces\ContentEntityMini;
use AppBundle\Tools\Image\ImageManipulator;
use AppBundle\Tools\Image\ImageManipulatorCalculator;
use AppBundle\Tools\Image\ImageManipulatorParameter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use function PHPSTORM_META\type;

/**
 * @ORM\Entity
 * @ORM\Table(name="content_index", options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class Content implements ContentEntityMini {
    private $isCurrentInList = FALSE;
    private $isExtern        = FALSE;

    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\Column(type="string", nullable=true,options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
     */
    protected $title;
    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $link;
    /**
     * @ORM\Column(type="string", nullable=true,options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
     */
    protected $description;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentCombination", mappedBy="contentObj")
     */
    protected $elementList;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentParameter", mappedBy="contentObj")
     * @var $parameterObj PersistentCollection
     */
    protected $parameterObj;
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentMeta", mappedBy="contentObj")
     */
    protected $contentMeta;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Image", inversedBy="contentObj")
     * @ORM\JoinColumn(name="thumbID", referencedColumnName="ID", onDelete="SET NULL", nullable=true)
     *
     */
    protected $thumbnailObj;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentCombination", mappedBy="childContentObj")
     */
    protected $collectionObj;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentLikeUserRelation", mappedBy="contentObj")
     */
    protected $contentLikeUserRelationObj;
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentViewUserRelation", mappedBy="contentObj")
     */
    protected $contentViewUserRelationObj;
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentViewDistillerGlobal", mappedBy="contentObj")
     */
    protected $contentViewDistillerGlobal;
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentLikeDistiller", mappedBy="contentObj")
     */
    protected $contentLikeDistiller;
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentViewDistillerFramed", mappedBy="contentObj")
     */
    protected $contentViewDistillerFramed;

    /**
     * Many Users have Many Groups.
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Tag", inversedBy="contentObjArray")
     * @ORM\JoinTable(name="nexus_content_tag",
     *      joinColumns={@ORM\JoinColumn(name="cID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tID", referencedColumnName="ID")}
     *      )
     */
    protected $tagArray;

    function __construct() {
        $this->elementList                = new ArrayCollection();
        $this->tagArray                   = new ArrayCollection();
        $this->isCurrentInList            = FALSE;
        $this->contentLikeUserRelationObj = new ArrayCollection();
        $this->contentViewUserRelationObj = new ArrayCollection();
        $this->contentViewDistillerGlobal = new ArrayCollection();
        $this->contentLikeDistiller       = new ArrayCollection();
        $this->contentViewDistillerFramed = new ArrayCollection();
        $this->collectionObj              = new ArrayCollection();
    }


    /**
     * Get iD
     *
     * @return integer
     */
    public function getID(): int {
        return $this->ID ?? 0;
    }

    public function setID(int $id) {
        $this->ID = $id;
    }

    /** @return Tag[] */
    public function getTagArray(): array {
        return $this->tagArray->toArray();
    }

    /** @return PersistentCollection|ArrayCollection */
    public function getTagArrayCollection() {
        return $this->tagArray;
    }

    public function addTag(Tag $t) {
        $this->tagArray->add($t);
    }

    public function getBasedID(): string {
        return base_convert($this->ID ?? 0, 10, 36);
    }

    public function getImagePath(string $postfix = ''): string {
        $s = '';
        /** @var $this ->thumbnailObj Image */
        if (!$this->thumbnailObj || !$this->getThumbnailObj()->getURL())
            return $s;

        return $this->getThumbnailObj()->getURL() . ($postfix ?? '') . '.' . $this->getThumbnailObj()->getMime();
    }

    public function getFullURL(): string {
        if ($this->isExtern)
            return $this->link;

        $type = $this->getParameterObj()->getTypeURL();
        $id   = $this->getBasedID();
        $slug = $this->getLink() ?? 'empty';

        if (empty($type))
            return '/';

        return $type . '/' . $id . ((!empty($slug)) ? '/' . $slug : '');
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Content
     */
    public function setTitle($title) {
        if (empty($title))
            return $this;

        $this->title = substr($title, 0, 250);

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string {
        return $this->title ?? '';
    }

    /**
     * Set link
     *
     * @param string $link
     *
     * @return Content
     */
    public function setLink($link) {
        $this->link = $link;

        return $this;
    }

    public function setExtern(bool $status) {
        $this->isExtern = $status;
    }

    public function isExtern(): bool {
        return $this->isExtern;
    }

    public function isVideo(): bool {
        return $this->parameterObj->get(0)->getType() == \AppBundle\Safety\Types\Content::TYPE_VIDEO;
    }

    public function isImage(): bool {
        return $this->parameterObj->get(0)->getType() == \AppBundle\Safety\Types\Content::TYPE_IMAGE;
    }

    public function isGif(): bool {
        return $this->parameterObj->get(0)->getType() == \AppBundle\Safety\Types\Content::TYPE_GIF;
    }

    public function isCollection(): bool {
        return $this->parameterObj->get(0)->getType() == \AppBundle\Safety\Types\Content::TYPE_COLLECTION;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink(): string {
        return $this->link ?? '';
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Content
     */
    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Add elementList
     *
     * @param \AppBundle\Entity\ContentCombination $elementList
     *
     * @return Content
     */
    public function addElementList(\AppBundle\Entity\ContentCombination $elementList) {
        $this->elementList[] = $elementList;

        return $this;
    }

    /**
     * Remove elementList
     *
     * @param \AppBundle\Entity\ContentCombination $elementList
     */
    public function removeElementList(\AppBundle\Entity\ContentCombination $elementList) {
        $this->elementList->removeElement($elementList);
    }

    /**
     * Get elementList
     *
     * @return ContentCombination[]
     */
    public function getElementList() {
        return $this->elementList->toArray();
    }

    /**
     * @param Content[] $list
     */
    public function setElementList(array $list) {
        $this->elementList = new ArrayCollection($list);
    }
    public function isChildOfContent(){
        return $this->collectionObj->count() > 0;
    }
    /** @return ContentCombination */
    public function getFirstContentCombinationElement(): ContentCombination {
        return $this->elementList->get(0) ?? new ContentCombination();
    }

    public function getResolutionOfLargestImage(): array {
        $arr = [0, 0];
        $this->elementList->first();

        if ($this->elementList->count() < 1)
            return $arr;

        do {
            /** @var $c ContentCombination */
            $c = $this->elementList->current();

            $w = $c->getImageObj()->getDimX() ?? 0;
            $h = $c->getImageObj()->getDimY();

            $arr = [max($arr[0], $w), max($arr[1], $h)];
        } while ($this->elementList->next());

        return $arr;
    }

    public function setFlagCurrent(bool $isCurrentInList = TRUE) {
        $this->isCurrentInList = $isCurrentInList;
    }

    public function isCurrentElement(): bool {
        return $this->isCurrentInList;
    }

    public function getLargestImageObj(): Image {
        $x = 0;
        $n = new Image();
        $this->elementList->first();

        if ($this->elementList->count() < 1)
            return $n;

        do {
            /** @var $c ContentCombination */
            $c = $this->elementList->current();
            if (!$c->getImageObj())
                continue;

            $w = $c->getImageObj()->getDimX() ?? 0;
            if ($w > $x)
                $n = $c->getImageObj();

        } while ($this->elementList->next());

        return $n;
    }

    public function getVideoObj(): Video {
        $n = new Video();
        $this->elementList->first();

        if ($this->elementList->count() < 1)
            return $n;

        do {
            /** @var $c ContentCombination */
            $c = $this->elementList->current();

            $w = $c->getVideoObj() ?? NULL;
            if ($w !== NULL) {
                $n = $c->getVideoObj();
            }
        } while ($this->elementList->next());

        return $n;
    }

    /**
     * Add parameterObj
     *
     * @param \AppBundle\Entity\ContentParameter $parameterObj
     *
     * @return Content
     */
    public function addParameterObj(\AppBundle\Entity\ContentParameter $parameterObj) {
        $this->parameterObj = $parameterObj;

        return $this;
    }

    /**
     * Remove parameterObj
     *
     * @param \AppBundle\Entity\ContentParameter $parameterObj
     */
    public function removeParameterObj(\AppBundle\Entity\ContentParameter $parameterObj) {
        $this->parameterObj->removeElement($parameterObj);
    }

    /**
     * Get parameterObj
     *
     * @return ContentParameter
     */
    public function getParameterObj() {
        return $this->parameterObj->get(0);
    }

    /**
     * Set thumbnailObj
     *
     * @param \AppBundle\Entity\Image $thumbnailObj
     *
     * @return Content
     */
    public function setThumbnailObj(Image $thumbnailObj) {
        $this->thumbnailObj = $thumbnailObj;

        return $this;
    }

    /**
     * Get thumbnailObj
     *
     * @return \AppBundle\Entity\Image
     */
    public function getThumbnailObj() {
        return $this->thumbnailObj;
    }

    /**
     * Set contentMeta
     *
     * @param \AppBundle\Entity\ContentMeta $contentMeta
     *
     * @return Content
     */
    public function setContentMetaObj(\AppBundle\Entity\ContentMeta $contentMeta) {
        $this->contentMeta = $contentMeta;

        return $this;
    }

    /**
     * Get ContentMeta
     *
     * @return \AppBundle\Entity\ContentMeta
     */
    public function getContentMetaObj(): ContentMeta {
        return $this->contentMeta[0] ?? new ContentMeta();
    }

    /**
     * Set ContentParameter
     *
     * @param \AppBundle\Entity\ContentParameter
     *
     * @return Content
     */
    public function setContentParameterObj(\AppBundle\Entity\ContentParameter $parameterObj) {
        $this->parameterObj = $parameterObj;

        return $this;
    }

    /**
     * Get ContentMeta
     *
     * @return \AppBundle\Entity\ContentParameter
     */
    public function getContentParameterObj(): ContentParameter {
        return $this->parameterObj[0];
    }

    public function getCanonicalURL() {
        return (stripos($_SERVER['SERVER_PROTOCOL'],'https') === true || !defined(SETTING_PREVENT_SSL) ? 'https' : 'http').'://' . $_SERVER['HTTP_HOST'] . $this->getFullURL();
    }

    public function getServerURL() {
        return '//' . $_SERVER['SERVER_NAME'] . '/';
    }

    /** @return Tag[] */
    public function getTagArraySortedByCountASC(): array {
        $tempTags = [];
        $tags     = [];
        if (!$this->tagArray)
            return $tempTags;

        foreach ($this->tagArray as $tag) {
            if (!isset($tempTags[$tag->getCount()]))
                $tempTags[$tag->getCount()] = [];

            $tempTags[$tag->getCount()][] = $tag;
        }
        ksort($tempTags);

        foreach ($tempTags as $tagArr) {
            foreach ($tagArr as $tag)
                $tags[] = $tag;
        }

        return $tags;
    }

    public function getThumbnailURL(): string {
        if ($this->getThumbnailObj())
            return $this->getThumbnailObj()->getThumbnailLinkURL();

        return '';
    }

    public function toIndexedArray(): array {
        return [$this->ID, $this->getThumbnailURL(), $this->link, $this->title];
    }

    /** @return ContentCombination[] */
    public function getCollectionObj(){
        return $this->collectionObj->toArray();
    }

    /**
     * @param int $i
     * @return Content[]
     */
    public function getSubContentObj( int $i = 0){
        if($i == 0 || $i < 0)
            return $this->collectionObj->toArray();

        return $this->collectionObj->slice(0, $i);
    }
    public function setCollectionObj(array $list){
        $this->collectionObj = new ArrayCollection( $list );
    }
}
