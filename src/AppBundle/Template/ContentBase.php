<?php

namespace AppBundle\Template;

use AppBundle\Entity\Content;
use AppBundle\Entity\ContentCombination;
use AppBundle\Entity\Image;
use AppBundle\Entity\Tag;
use AppBundle\Entity\User;
use AppBundle\Safety\Types\Hoster;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class ContentBase {
    private $title               = '';
    private $description         = '';
    private $relativeURL         = '';
    private $thumbURL            = '';
    private $user;
    private $date;
    private $type;
    private $ID;
    private $basedID             = 'fff';
    private $canonicalURL        = '/';
    private $imagePath           = '';
    private $thumbAlt            = '';
    private $thumbURLMed         = '';
    private $thumbURLMedAbsolute = '';
    private $fullURL             = '';
    private $linkURL             = '';
    private $srcURL              = '';
    private $srcHoster           = '';
    private $fileSize            = 0;
    private $mediaIn             = 0;
    /** @var Tag[] */
    private $tags       = [];
    private $width      = 0;
    private $height     = 0;
    private $length     = 0;
    private $embedID    = '';
    private $subContent = [];
    private $request;
    /** @var null | ArrayCollection */
    private $relatedContentList = NULL;
    /** @var bool | EntityManager */
    private $em = FALSE;

    private $nextContentInList     = FALSE;
    private $previousContentInList = FALSE;

    function __construct(int $dbID, int $contentType = 1, EntityManager $entityManager = NULL, Request $request) {
        $this->ID      = $dbID;
        $this->type    = new ContentType($contentType);
        $this->date    = new \DateTime();
        $this->user    = 1;
        $this->em      = $entityManager ?? FALSE;
        $this->request = $request;
    }

    public function getMediaIn(): int {
        return $this->mediaIn;
    }

    public function getContentObj() {
        return $this;
    }

    public function getImagePath() {
        //return $this->getThumbURL();
        return $this->imagePath;
    }

    public function setMediaIn(int $mediaIn) {
        $this->mediaIn = $mediaIn;
    }

    public function getNextContentInList() {
        return $this->nextContentInList;
    }

    public function getPreviousContentInList() {
        return $this->previousContentInList;
    }

    public function setNextContentInList(Content $list) {
        $this->nextContentInList = $list;
    }

    public function setPreviousContentInList(Content $list) {
        $this->previousContentInList = $list;
    }

    function loadTagsByContentEntity(Content $content) {
        $this->tags = $content->getTagArray()->toArray();
    }

    function loadInfoByContentEntityArray(array $arrayCollection, int $depth = 1) {
        $col = new ArrayCollection($arrayCollection);
        $this->loadInfoByContentEntityCollection($col, $depth);
    }

    function loadInfoByContentEntityCollection(Collection $arrayCollection, int $depth = 1) {
        if ($arrayCollection->count() < 1)
            return;
        $arrayCollection->first();
        do {
            /** @var $s Content */
            $s = $arrayCollection->current();

            // child things are set
            if (is_a($s, 'AppBundle\Entity\Content')) {
                $base = new ContentBase(
                    $s->getID() ?? 0,
                    $s->getParameterObj()->getType() ?? 1,
                    NULL,
                    $this->request
                );
                $base->loadInfoByContentEntity($s, $depth);
                $this->subContent[] = $base;
            }
        } while ($arrayCollection->next());
    }
    public function setLinkURL(?string $url){
        $this->linkURL = $url ?? 'picture';
    }

    function loadInfoByContentEntity(Content $content, int $depth = 8) {
        $this->setTitle($content->getTitle() ?? 'Title not provided');
        $this->setDescription($content->getDescription() ?? '');
        $this->setCanonicalURL($content->getFullURL());
        $this->linkURL = $content->getLink();

        $this->setThumbURL('/img/t/' . $content->getImagePath());
        $this->setThumbURLMed('/img/m/' . $content->getImagePath());
        $this->setImageFull('/img/f/' . $content->getImagePath());
        $this->imagePath = $content->getImagePath();
        $this->basedID   = $content->getBasedID();

        if ($depth > 0) {
            $this->setWidth($content->getLargestImageObj()->getDimX());
            $this->setHeight($content->getLargestImageObj()->getDimY());
        }

        if ($content->getParameterObj()) {
            $this->setDateByTimestamp($content->getParameterObj()->getTimestamp());
            $this->setUser($content->getParameterObj()->getUserObj());
        }
        if ($depth > 0 && $content->getElementList()->count() == 1) {
            /** @var $c Image */
            $c = $content->getElementList()->first();
            if (method_exists($c, 'getFilesize'))
                $this->setFileSize($c->getFilesize());
        }
        if ($depth > 0) {
            $depth--;
            $this->parseChildContent($content->getElementList(), $depth);
        }
    }

    private function parseChildContent(Collection $arrayCollection, int $depth = 8) {
        if ($arrayCollection->count() < 1)
            return;
        $arrayCollection->first();
        do {
            /** @var $s ContentCombination */
            $s = $arrayCollection->current();

            // child things are set
            if ($s->getSubContentObj()) {
                $base = new ContentBase(
                    $s->getSubContentObj()->getID() ?? 0,
                    $s->getSubContentObj()->getParameterObj()->getType() ?? 1,
                    NULL,
                    $this->request
                );
                $z    = $s->getSubContentObj();

                $base->loadInfoByContentEntity($z, $depth);
                $this->subContent[] = $base;
            }
        } while ($arrayCollection->next());
    }

    private function buildRelatedContentList(int $startIndex = 0, int $length = 50) {
        if ($this->relatedContentList !== NULL)
            return;

        $this->relatedContentList = new ArrayCollection();

        if (!$this->em)
            return;

        // get Tags Sorted by global Count
        $tempTags = [];
        foreach ($this->tags as $tag) {
            if (!isset($tempTags[$tag->getCount()]))
                $tempTags[$tag->getCount()] = [];

            $tempTags[$tag->getCount()][] = $tag;
        }
        ksort($tempTags);

        // build and do database query until content goal reached
        $contentGoal         = 500;
        $contentCount        = 0;
        $contentIDsInUseList = [];

        foreach ($tempTags as $tagContainer) {
            foreach ($tagContainer as $tag) {
                if ($contentCount >= $contentGoal)
                    break 2;
                /** @var $tag Tag */
                $query  = $this->em->createQuery("SELECT c.ID FROM AppBundle:Content c JOIN c.tagArray t JOIN c.parameterObj p WHERE p.isPrivate = 0 AND t.ID = " . $tag->getID())->setMaxResults($contentGoal);
                $result = $query->getResult();

                foreach ($result as $res) {
                    if ($res['ID'] == $this->ID)
                        continue;
                    else if (!isset($contentIDsInUseList[$res['ID']]))
                        $contentIDsInUseList[$res['ID']] = 1;
                    else
                        $contentIDsInUseList[$res['ID']]++;
                }

                $contentCount = count($contentIDsInUseList);
            }
        }

        // sort the array by values
        arsort($contentIDsInUseList);

        // new contentArray to perform Database Queries
        $contentIDs = [];
        foreach ($contentIDsInUseList as $contentIDKey => $null) {
            $contentIDs[] = $contentIDKey;
        }

        // Read Content BY ID
        if (!isset($contentIDs[$startIndex]))
            return;

        $orArrayToCatchIDs = array_slice($contentIDs, $startIndex, $length);
        $qbString          = 'c.ID = ' . implode(' OR c.ID = ', $orArrayToCatchIDs);

        $qb = $this->em->createQueryBuilder()
                       ->select('c')
                       ->from('AppBundle:Content', 'c')
                       ->where($qbString)
                       ->getQuery()
                       ->useResultCache(true, 600)->getResult();

        if (!$qb)
            return;

        foreach ($qb as $sub) {
            /** @var $sub Content */
            $contentIDsInUseList[$sub->getID()] = $sub;
        }
        foreach ($contentIDsInUseList as $k => $int) {
            if (is_int($contentIDsInUseList[$k]))
                unset($contentIDsInUseList[$k]);
        }
        //echo '<br />'.count($contentIDsInUseList);

        $this->relatedContentList = new ArrayCollection(array_values($contentIDsInUseList));
    }

    /**
     * @param int $startPage
     * @param int $length
     * @return array
     */
    function getRelatedContentEntities(int $startPage = 1, int $length = 52): array {
        if ($this->request->query->getInt('filter', 0) != 0)
            $startPage = 1;
        //$startPage = max($this->request->query->getInt('page',1),1);
        $this->buildRelatedContentList(($startPage - 1) * $length, $length);

        return $this->relatedContentList->toArray();
    }

    function getRelatedContentEntitiesForcedStartPage(int $startPage = 1, int $length = 52): array {
        $this->buildRelatedContentList(($startPage - 1) * $length, $length);

        return $this->relatedContentList->toArray();
    }

    /** @return ContentBase | null */
    public function getFirst():?ContentBase {
        return $this->subContent[0] ?? NULL;
    }

    /** @return ContentBase[] */
    function getSubContent(): array {
        return $this->subContent;
    }

    public function getID() {
        return $this->ID;
    }

    /** @param $tags Tag[] */
    function setTags(array $tags = []) {
        $this->tags = $tags;
    }

    function addTag(Tag $tag) {
        $this->tags[] = $tag;
    }

    /** @return array | Tag[] */
    function getTags(): array {
        return $this->tags;
    }

    function hasTags(): bool {
        return count($this->tags) > 0;
    }

    function getWidth(): int {
        return $this->width;
    }

    function setWidth(?int $w) {
        $this->width = $w ?? 0;
    }

    function getHeight(): int {
        return $this->height;
    }

    function setHeight(?int $h) {
        $this->height = $h ?? 0;
    }

    function setTitle(?string $title = '') {
        $this->title = $title ?? '';
    }

    function getTitle(): string {
        return $this->title ?? '';
    }

    function setDescription(string $description = '') {
        $this->description = $description;
    }

    function getDescription(): string {
        return $this->description;
    }

    function setRelativeURL(string $url = '') {
        $this->relativeURL = $url;
    }

    function getRelativeURL(): string {
        return $this->relativeURL;
    }

    function setThumbURL(string $url = '') {
        $this->thumbURL = $url;
    }

    function getThumbURL(): string {
        return $this->thumbURL;
    }

    function ThumbURLwithoutLeadingSlash(): string {
        return ltrim($this->thumbURL, '/');
    }

    function getThumbURLMed(): string {
        return $this->thumbURLMed;
    }

    function setThumbURLMed(string $url) {
        $this->thumbURLMed = $url;
    }

    function setImageFull(string $url) {
        $this->fullURL = $url;
    }

    function setThumbURLMedAbsolute(string $url) {
        $this->thumbURLMedAbsolute = $url;
    }

    function getThumbURLMedAbsolute(): string {
        return $this->thumbURLMedAbsolute;
    }

    function getImageFull(): string {
        return $this->fullURL;
    }

    function setThumbAlt(string $alt) {
        $this->thumbAlt = $alt;
    }

    function getThumbAlt(): string {
        return $this->thumbAlt;
    }

    function setCanonicalURL(string $cURL) {
        $this->canonicalURL = $cURL;
    }

    function getCanonicalURL(): string {
        return $this->canonicalURL;
    }

    function getFullURL(): string {
        return $this->getCanonicalURL();
    }

    function setFileSize(int $size) {
        $this->fileSize = $size;
    }

    function getFileSize(): string {
        return number_format($this->fileSize / 1024, 2);
    }

    function setSrcURL(string $url) {
        $this->srcURL = $url;
    }

    function getSrcURL(): string {
        return $this->srcURL;
    }

    function setSrcHoster(string $hosterName) {
        $this->srcHoster = $hosterName;
    }

    function getSrcHoster(): string {
        return $this->srcHoster;
    }

    function setUser(User $user) {
        $this->user = $user;
    }

    function getUser() {
        if ($this->user === 1) return new User();
        else return $this->user;
    }

    function setDate(\DateTime $date) {
        $this->date = $date;
    }

    function setDateByTimestamp(int $timestamp) {
        $this->date->setTimestamp($timestamp);
    }

    function getDate(): \DateTime {
        return $this->date;
    }

    function setLength(int $sec) {
        $this->length = $sec;
    }

    function getLengthInSec(): int {
        return $this->length;
    }

    function getLengthString(): string {
        $t = 'n/a';
        $h = (int)($this->length / 3600);
        $m = (int)(($this->length - $h * 3600) / 60);
        $s = (int)($this->length - $h * 3600 - $m * 60);

        $r = '';
        if ($h > 0)
            $r .= $h . ' h ';
        if ($m > 0)
            $r .= $m . ' min ';
        if ($s > 0)
            $r .= $s . 's';

        return (empty($r)) ? $t : $r;
    }

    function setEmbedID(?string $ID) {
        $this->embedID = $ID ?? '';
    }

    function getEmbedID(): string {
        return $this->embedID;
    }

    function getBasedID(): string {
        return $this->basedID;
    }

    function getEmbedString(): string {
        $s = '';
        switch ($this->srcHoster) {
            case Hoster::TYPE_YOUTUBE:
                $s = '<iframe width="700" height="394" src="https://www.youtube.com/embed/' . $this->embedID . '" frameborder="0" allowfullscreen></iframe>';
                break;
            case Hoster::TYPE_GFYCAT:
                $s = "<div style='position:relative;padding-bottom:54%'><iframe src='https://gfycat.com/ifr/{$this->embedID}' frameborder='0' scrolling='no' width='100%' height='100%' style='position:absolute;top:0;left:0' allowfullscreen></iframe></div>";
                break;
        }
        return $s;
    }

    function getDBID(): int {
        return $this->ID;
    }

    function getType(): ContentType {
        return $this->type;
    }

    function getLinkSlugOfTitle(): string {
        return $this->linkURL;
    }
}