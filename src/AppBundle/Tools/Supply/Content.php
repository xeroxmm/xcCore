<?php

namespace AppBundle\Tools\Supply;

use AppBundle\Entity\ContentParameter;
use AppBundle\Entity\Tag;
use AppBundle\Interfaces\ContentEntityMini;
use AppBundle\Safety\Types\Posts;
use AppBundle\Security\User\Anonym\SessionHandler;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class Content {
    private $em;
    /** @var null | \AppBundle\Entity\Content | Tag */
    private $result;
    private $resultRelated;
    private $resultPopular;
    /** @var  int[] */
    private $resultPopularIDs;
    /** @var null | ContentEntityMini[] */
    private $resultStream;
    private $resultStreamLengthVisible = 2;
    private $resultStreamLengthMinimum = 6;
    /** @var  int[] */
    private $resultRelatedOnlyContentIDs;
    private $typeGeneral;
    private $typeSQL;
    private $tagSlug;
    private $typeContext;
    private $typeContent;

    private $offset;
    private $limit;
    private $cIDtoSkip;

    private $baseID;
    /** @var null | SessionHandler */
    private $client;
    private $useAdvertisement;
    private $page;
    private $hasMoreResultsThanLimit;
    private $isPopularContext;
    private $isRelatedContext;
    private $isLatestContext;
    private $requestURL;
    private $requestURLBased;
    private $isValidRequest;
    private $isValidContext;
    private $elementsInFilter;
    /** @var  $subContentElement null | \AppBundle\Entity\Content */
    private $subContentElement;

    /** @var Tag */
    private $contextObjectTag;
    /** @var \AppBundle\Entity\Content */
    private $contextObjectContent;
    private $queryVars;
    private $tagObject = NULL;

    public function __construct(EntityManagerInterface $em, int $parameterLimit, SessionHandler $client, Request $request) {
        $this->em                          = $em;
        $this->typeGeneral                 = Posts::TYPE_LIST_BASE;
        $this->typeContext                 = \AppBundle\Safety\Types\Content::TYPE_INFO;
        $this->typeSQL                     = '< 10';
        $this->typeContent                 = \AppBundle\Safety\Types\Content::TYPE_INFO;
        $this->tagSlug                     = FALSE;
        $this->offset                      = 0;
        $this->limit                       = $parameterLimit;
        $this->result                      = NULL;
        $this->baseID                      = 0;
        $this->cIDtoSkip                   = 0;
        $this->resultRelated               = NULL;
        $this->resultPopular               = NULL;
        $this->resultStream                = NULL;
        $this->resultRelatedOnlyContentIDs = [];
        $this->client                      = $client;
        $this->useAdvertisement            = FALSE;
        $this->page                        = 1;
        $this->hasMoreResultsThanLimit     = FALSE;
        $this->isPopularContext            = FALSE;
        $this->isRelatedContext            = FALSE;
        $this->isLatestContext             = FALSE;
        $this->isValidRequest              = TRUE;
        $this->subContentElement           = NULL;
        $this->requestURLBased             = '';
        $this->isValidContext              = NULL;
        $this->requestURL                  = $this->analyzeURI($request);
        $this->elementsInFilter            = [
            \AppBundle\Safety\Types\Content::TYPE_INFO => -1,
            \AppBundle\Safety\Types\Content::TYPE_IMAGE => -1,
            \AppBundle\Safety\Types\Content::TYPE_GIF => -1,
            \AppBundle\Safety\Types\Content::TYPE_VIDEO => -1,
            \AppBundle\Safety\Types\Content::TYPE_COLLECTION => -1,
            \AppBundle\Safety\Types\Content::TYPE_LINK => -1
        ];
        $this->queryVars                   = [];
    }

    public function getCanonicalURL() {
        switch ($this->typeContext) {
            case \AppBundle\Safety\Types\Content::TYPE_COLLECTION:
                return $this->getCanonicalURLOfSubContent();
                break;
            case \AppBundle\Safety\Types\Content::TYPE_IMAGE:
            case \AppBundle\Safety\Types\Content::TYPE_INFO:
            case \AppBundle\Safety\Types\Content::TYPE_GIF:
            case \AppBundle\Safety\Types\Content::TYPE_VIDEO:
            case \AppBundle\Safety\Types\Content::TYPE_LINK:
            default:
                return $this->getPostContent()->getCanonicalURL();
                break;
        }
    }

    public function setContextContentImage() {
        $this->typeContext = \AppBundle\Safety\Types\Content::TYPE_IMAGE;
        //$this->typeSQL     = '= ' . $this->typeContext;
        $this->typeGeneral = Posts::TYPE_POST;
    }

    public function setContextContentVideo() {
        $this->typeContext = \AppBundle\Safety\Types\Content::TYPE_VIDEO;
        //$this->typeSQL     = '= ' . $this->typeContext;
        $this->typeGeneral = Posts::TYPE_POST;
    }

    public function setContextContentGif() {
        $this->typeContext = \AppBundle\Safety\Types\Content::TYPE_GIF;
        //$this->typeSQL     = '= ' . $this->typeContext;
        $this->typeGeneral = Posts::TYPE_POST;
    }

    public function setContextContentCollection() {
        $this->typeContext = \AppBundle\Safety\Types\Content::TYPE_COLLECTION;
        //$this->typeSQL     = '= ' . $this->typeContext;
        $this->typeGeneral = Posts::TYPE_LIST_COLLECTION;
    }

    public function setContextListTags() {
        $this->typeContext = \AppBundle\Safety\Types\Content::TYPE_INFO;
        //$this->typeSQL     = '< 10';
        $this->typeGeneral = Posts::TYPE_LIST_TAG;
    }

    public function setContextListMain() {
        $this->typeContext = \AppBundle\Safety\Types\Content::TYPE_INFO;
        //$this->typeSQL     = '< 10';
        $this->typeGeneral = Posts::TYPE_LIST_BASE;
    }

    public function setContextListSearch(array $queryVars) {
        $this->typeContext = \AppBundle\Safety\Types\Content::TYPE_INFO;
        //$this->typeSQL     = '< 10';
        $this->typeGeneral = Posts::TYPE_LIST_SEARCH;
        $this->queryVars   = $queryVars;
    }

    public function setContextContentLink() {
        $this->typeContext = \AppBundle\Safety\Types\Content::TYPE_LINK;
        //$this->typeSQL     = '= ' . $this->typeContext;
        $this->typeGeneral = Posts::TYPE_POST;
    }

    private function analyzeURI(Request $request) {
        $this->setOffsetByPage(max($request->query->getInt('page', 1), 1));
        $temp = explode('/', $_SERVER['REDIRECT_URL']);

        if (in_array($temp[count($temp) - 1], ['_popular']) && $temp[count($temp) - 1] == $temp[count($temp) - 2] ?? '') {
            $this->isValidRequest = FALSE;
            return $this->requestURL = '';
        }

        if ($temp[count($temp) - 1] == 'popular') {
            $this->isPopularContext = TRUE;
            unset($temp[count($temp) - 1]);
        } else if ($temp[count($temp) - 1] == 'related') {
            $this->isRelatedContext = TRUE;
            unset($temp[count($temp) - 1]);
        } else {
            $this->isLatestContext = TRUE;
        }

        $isSearch = FALSE;
        if(($temp[1] ?? FALSE) == 'search'){
            $isSearch = TRUE;
            $s2 = $temp[2] ?? FALSE;
        } else
            $s2 = $temp[count($temp) - 1] ?? FALSE;
        if ($s2 == 'videos') {
            $this->setOnlyVideos();
        } else if ($s2 == 'images') {
            $this->setOnlyImages();
        } else if ($s2 == 'gifs') {
            $this->setOnlyGifs();
        } else if ($s2 == 'collections') {
            $this->setOnlyCollections();
        }

        $this->requestURL = implode('/', $temp);
        if (!$isSearch && in_array($s2, ['videos', 'images', 'gifs', 'collections']))
            unset($temp[count($temp) - 1]);
        $this->requestURLBased = implode('/', $temp);

        if (empty($this->requestURLBased))
            $this->requestURLBased = '/';

        return $this->requestURL;
    }

    public function setOnlyImages() {
        $this->typeSQL     = '= ' . \AppBundle\Safety\Types\Content::TYPE_IMAGE;
        $this->typeContent = \AppBundle\Safety\Types\Content::TYPE_IMAGE;
        $this->typeGeneral = Posts::TYPE_POST;
    }

    public function setOnlyVideos() {
        $this->typeSQL     = '= ' . \AppBundle\Safety\Types\Content::TYPE_VIDEO;
        $this->typeContent = \AppBundle\Safety\Types\Content::TYPE_VIDEO;
        $this->typeGeneral = Posts::TYPE_POST;
    }

    public function setOnlyGifs() {
        $this->typeSQL     = '= ' . \AppBundle\Safety\Types\Content::TYPE_GIF;
        $this->typeContent = \AppBundle\Safety\Types\Content::TYPE_GIF;
        $this->typeGeneral = Posts::TYPE_POST;
    }

    public function setOnlyCollections() {
        $this->typeSQL     = '= ' . \AppBundle\Safety\Types\Content::TYPE_COLLECTION;
        $this->typeContent = \AppBundle\Safety\Types\Content::TYPE_COLLECTION;
        $this->typeGeneral = Posts::TYPE_POST;
    }

    public function setOnlyGif() {
        $this->typeSQL     = '= ' . \AppBundle\Safety\Types\Content::TYPE_GIF;
        $this->typeContent = \AppBundle\Safety\Types\Content::TYPE_GIF;
        $this->typeGeneral = Posts::TYPE_POST;
    }

    public function setTypeList() {
        $this->typeGeneral = Posts::TYPE_LIST_TAG;
    }

    public function setLimit(int $limit) {
        $this->limit = $limit;
    }

    public function setTagSlug(string $slug) {
        $this->tagSlug     = $slug;
        $this->typeGeneral = Posts::TYPE_LIST_TAG;
    }

    public static function getBasedIDby10Base(int $ID): string {
        return base_convert($ID, 10, 36);
    }

    public static function getIDbyStringBase(string $ID): int {
        return (int)base_convert($ID, 36, 10);
    }

    public function setContentIDBased(string $baseID) {
        $this->baseID    = self::getIDbyStringBase($baseID);
        $this->cIDtoSkip = $this->baseID;
    }

    public function isValidContext(): bool {
        if ($this->isValidContext === NULL)
            $this->loadMainContextObjectByType();

        return (bool)($this->isValidContext);
    }

    public function setClient(SessionHandler $client) {
        $this->client = $client;
    }

    public function getContextObjectRAW():?\AppBundle\Entity\Content {
        return $this->contextObjectContent;
    }

    public function getPostContent():?\AppBundle\Entity\Content {
        if (!$this->isValidRequest)
            return NULL;
        $this->contentQueryFromShadowList();
        return $this->contextObjectContent;
    }

    /** @return \AppBundle\Entity\Content[] */
    public function baseContent(): array {
        return $this->contentQueryFromShadowList();
    }

    public function getTagContent():?Tag {
        if (!$this->isValidRequest)
            return NULL;

        return $this->result === NULL ? $this->tagQueryFromShadowList() : $this->result;
    }

    public function getCollectionContent():?\AppBundle\Entity\Content {
        if (!$this->isValidRequest)
            return NULL;

        return $this->result === NULL ? $this->collectionQueryFromShadowList() : $this->result;
    }

    public function hasMoreElements(bool $useFilter = TRUE): bool {
        return $this->hasMoreResultsThanLimit && (!$useFilter || ($this->getFilterCountRaw() > 0 && $this->getFilterCountRaw() != $this->limit * $this->page));
    }

    public function getNextPage() {
        return $this->page + 1;
    }

    public function getID(): int {
        return $this->baseID;
    }

    public function setContentIDtoSkip(int $cID) {
        $this->cIDtoSkip = $cID;
    }

    public function setOffset(int $offset) {
        $this->offset = $offset;
    }

    public function setOffsetByPage(int $page) {
        $this->page = $page;
        $this->setOffset(($page - 1) * $this->limit);
    }

    public function setAllowAdvertisement(bool $status) {
        $this->useAdvertisement = $status;
    }

    public function setPopularContext(bool $status) {
        $this->isPopularContext = $status;
    }

    public function isPopularContext(): bool {
        return $this->isPopularContext;
    }

    public function isLatestContext(): bool {
        return $this->isLatestContext;
    }

    public function isRelatedContext(): bool {
        return $this->isRelatedContext;
    }

    public function getFilterContext(): string {
        switch ($this->typeContent) {
            case \AppBundle\Safety\Types\Content::TYPE_IMAGE:
                return 'images';
            case \AppBundle\Safety\Types\Content::TYPE_GIF:
                return 'GIFs';
            case \AppBundle\Safety\Types\Content::TYPE_VIDEO:
                return 'videos';
            case \AppBundle\Safety\Types\Content::TYPE_COLLECTION:
                return 'collections';
            case \AppBundle\Safety\Types\Content::TYPE_LINK:
                return 'links';
            default:
                return 'posts';
        }
    }

    public function isTypeContextWithPagination(): bool {
        return
            $this->isTypeContextCollection() ||
            $this->isTypeContextSearch() ||
            $this->isTypeContextTag() ||
            $this->isTypeContextBase();
    }
    public function isTypeContextTag():bool{
        return $this->typeGeneral === Posts::TYPE_LIST_TAG;
    }
    public function isTypeContextBase():bool{
        return $this->typeGeneral === Posts::TYPE_LIST_BASE;
    }
    public function isTypeContextCollection(): bool {
        return $this->typeContext === \AppBundle\Safety\Types\Content::TYPE_COLLECTION;
    }
    public function isTypeContextSearch():bool{
        return $this->typeGeneral === Posts::TYPE_LIST_SEARCH;
    }
    public function isFilterContextImage(): bool {
        return $this->typeContent == \AppBundle\Safety\Types\Content::TYPE_IMAGE;
    }

    public function isFilterContextGif(): bool {
        return $this->typeContent == \AppBundle\Safety\Types\Content::TYPE_GIF;
    }

    public function isFilterContextCollection(): bool {
        return $this->typeContent == \AppBundle\Safety\Types\Content::TYPE_COLLECTION;
    }

    public function isFilterContextVideo(): bool {
        return $this->typeContent == \AppBundle\Safety\Types\Content::TYPE_VIDEO;
    }

    public function isFilterContextAll(): bool {
        return !$this->isFilterContextImage() && !$this->isFilterContextVideo() && !$this->isFilterContextGif() && !$this->isFilterContextCollection();
    }

    /**
     * @param string $url
     */
    public function setThisURL(string $url) {
        $this->requestURL = $url;
    }

    /**
     * @param null|string $onEmptyRequest
     * @return string
     * @internal param null|string $implement
     */
    public function getThisURL(?string $onEmptyRequest = ''): string {
        if (empty($this->requestURL))
            return $onEmptyRequest;

        return rtrim($this->requestURL, '/');
    }

    public function getFilterURL(string $sub = ''): string {
        $reqURL = $this->requestURLBased;

        if($this->typeGeneral == Posts::TYPE_LIST_SEARCH){
            $reqURL = str_replace(['/all/','/images/','/videos/','/gifs/','/categories/'],'/'.(empty($sub) ? 'all/' : $sub.'/'), $reqURL);
            return rtrim($reqURL, '/');
        }

        if (empty($sub))
            return empty(($k = rtrim($reqURL, '/') . ($this->isPopularContext() ? '/popular' : ($this->isRelatedContext() ? '/related' : '')))) ? '/' : $k;
        else
            return rtrim($reqURL, '/') . '/' . $sub . ($this->isPopularContext() ? '/popular' : ($this->isRelatedContext() ? '/related' : ''));
    }

    public function getPopularContent(int $offset, int $limit) {
        $this->offset = $offset;
        $this->limit  = $limit;

        if ($this->tagSlug !== FALSE)
            return ($this->tagQuery()->getContentObjArray() ?? NULL);
        else
            return $this->standardQuery();
    }

    /** @return \AppBundle\Entity\Content[] */
    public function getRelatedContentEntities() {
        $this->contentQueryFromShadowList();

        return $this->resultRelated;
    }

    /** @return \AppBundle\Entity\Content[] */
    public function getPopularContentEntities() {
        return $this->resultPopular !== NULL ? $this->resultPopular : $this->getPopularContent($this->offset, $this->limit);
    }

    /** @return ContentEntityMini[] */
    public function getStreamContentEntities() {
        return $this->resultStream !== NULL ? $this->resultStream : $this->getStreamContent();
    }

    /** @return bool */
    public function hasStreamHistory(): bool {
        return $this->client->getStream()->hasPreviousItem();
    }

    /** @return ContentEntityMini */
    public function getStreamHistoryPreviousItem(): ContentEntityMini {
        return $this->client->getStream()->getPreviousItem() ?? new ContentStreamMiniEntity();
    }

    /** @return bool */
    public function streamList(): bool {
        return count($this->client->getStream()->getMainList(1)) > 0;
    }

    /** @return ContentEntityMini */
    public function getStreamListNextItem(): ContentEntityMini {
        return $this->client->getStream()->getMainList(1)[0] ?? new ContentStreamMiniEntity();
    }

    private function loadMainContextObjectByType() {
        if ($this->typeGeneral == Posts::TYPE_POST || $this->typeGeneral == Posts::TYPE_LIST_COLLECTION)
            $this->loadMainContextObjectByTypeAsPost();
        else if ($this->typeGeneral == Posts::TYPE_LIST_TAG)
            $this->loadMainContextObjectByTypeAsTagList();
        else if ($this->typeGeneral == Posts::TYPE_LIST_BASE)
            $this->loadMainContextObjectByTypeAsMainList();
        else
            $this->isValidContext = FALSE;
    }

    private function loadMainContextObjectByTypeAsPost() {
        $query = $this->em->createQueryBuilder()
                          ->select('c,cp,cm,ct,ta,tm')
                          ->from('AppBundle:Content', 'c')
                          ->leftJoin('c.parameterObj', 'cp')
                          ->leftJoin('c.contentMeta', 'cm')
                          ->leftJoin('c.thumbnailObj', 'ct')
                          ->leftJoin('c.tagArray', 'ta')
                          ->leftJoin('ta.tagMeta', 'tm')
                          ->where('c.ID = :cparam')
                          ->andWhere('cp.isPrivate = 0')
                          ->andWhere('cp.type = ' . $this->typeContext)
                          ->setParameter('cparam', $this->baseID)
                          ->getQuery()
                          ->useQueryCache(TRUE)
                          ->useResultCache(TRUE, 1200)
                          ->getOneOrNullResult();

        if (!$query) {
            $this->isValidContext = FALSE;
            return;
        }

        $q = $this->em->createQueryBuilder()
                      ->select('c,t,cm')
                      ->from('AppBundle:Content', 'c')
                      ->leftJoin('c.parameterObj', 'cp')
                      ->leftJoin('c.contentMeta', 'cm')
                      ->leftJoin('c.elementList', 'cU')
                      ->leftJoin('cU.childContentObj', 'cc')
                      ->leftJoin('c.thumbnailObj', 't')
                      ->where('cc.ID = ' . $this->baseID)
                      ->andWhere('cp.isPrivate = 0')
                      ->andWhere('cp.type = ' . \AppBundle\Safety\Types\Content::TYPE_COLLECTION)
                      ->getQuery();

        //echo $q->getSQL(); die();

        $q = $q->useResultCache(TRUE, 72000)
               ->useQueryCache(TRUE)
               ->getResult();

        /** @var $query \AppBundle\Entity\Content */
        if (!empty($q))
            $query->setCollectionObj($q);

        $this->contextObjectContent = $query;
        $this->isValidContext       = TRUE;
    }

    public function getFilterCountRaw(): int {;
        return $this->elementsInFilter[$this->typeContent];
    }

    public function getFilterCount(): int {
        return $this->getRoundedCountValue($this->elementsInFilter[$this->typeContent], 1000);
    }

    /**
     * @return bool | string
     */
    public function isFilterAllEmpty() {
        if ($this->typeContent == \AppBundle\Safety\Types\Content::TYPE_INFO && !($a = $this->getElementsInCategories('all'))) {
            if ($this->getElementsInCategories('' . \AppBundle\Safety\Types\Content::TYPE_IMAGE))
                return 'images';
            else if ($this->getElementsInCategories('' . \AppBundle\Safety\Types\Content::TYPE_GIF))
                return 'gifs';
            else if ($this->getElementsInCategories('' . \AppBundle\Safety\Types\Content::TYPE_COLLECTION))
                return 'collections';
            else if ($this->getElementsInCategories('' . \AppBundle\Safety\Types\Content::TYPE_VIDEO))
                return 'videos';
            else if ($this->getElementsInCategories('' . \AppBundle\Safety\Types\Content::TYPE_LINK))
                return 'links';
            else
                return 'new';
        } else {
            return FALSE;
        }
    }

    public function loadElementsInSubCategories(): Content {
        $this->getElementsInCategories();
        $this->getElementsInCategories('images');
        $this->getElementsInCategories('videos');
        $this->getElementsInCategories('gifs');
        $this->getElementsInCategories('collections');
        $this->getElementsInCategories('links');

        return $this;
    }

    public function getElementsInCategories(string $slug = 'all'): int {
        $maxRes = 1000;
        switch ($slug) {
            case 'images':
            case '' . \AppBundle\Safety\Types\Content::TYPE_IMAGE:
                $filter = \AppBundle\Safety\Types\Content::TYPE_IMAGE;
                $type   = '= ' . \AppBundle\Safety\Types\Content::TYPE_IMAGE;
                break;
            case 'videos':
            case '' . \AppBundle\Safety\Types\Content::TYPE_VIDEO:
                $filter = \AppBundle\Safety\Types\Content::TYPE_VIDEO;
                $type   = '= ' . \AppBundle\Safety\Types\Content::TYPE_VIDEO;
                break;
            case 'gifs':
            case '' . \AppBundle\Safety\Types\Content::TYPE_GIF:
                $filter = \AppBundle\Safety\Types\Content::TYPE_GIF;
                $type   = '= ' . \AppBundle\Safety\Types\Content::TYPE_GIF;
                break;
            case 'collections':
            case '' . \AppBundle\Safety\Types\Content::TYPE_COLLECTION:
                $filter = \AppBundle\Safety\Types\Content::TYPE_COLLECTION;
                $type   = '= ' . \AppBundle\Safety\Types\Content::TYPE_COLLECTION;
                break;
            case 'links':
            case '' . \AppBundle\Safety\Types\Content::TYPE_LINK:
                $filter = \AppBundle\Safety\Types\Content::TYPE_LINK;
                $type   = '= ' . \AppBundle\Safety\Types\Content::TYPE_LINK;
                break;
            case 'all':
            default:
                $filter = \AppBundle\Safety\Types\Content::TYPE_INFO;
                $type   = '< 10';
                break;
        }

        if ($this->elementsInFilter[$filter] > -1)
            return $this->elementsInFilter[$filter];

        if ($this->typeGeneral == Posts::TYPE_LIST_TAG) {
            $query = $this->em->createQueryBuilder()
                              ->select('c.ID')
                              ->from('AppBundle:Content', 'c')
                              ->leftJoin('c.parameterObj', 'cp')
                              ->leftJoin('c.tagArray', 't')
                              ->where('t.slug = :tslug')
                              ->setParameter('tslug', $this->tagSlug);

            if ($slug == 'all')
                $query = $query->andWhere('cp.isBulk = 0');

        } else if ($this->typeContext == \AppBundle\Safety\Types\Content::TYPE_COLLECTION) {
            $query = $this->em->createQueryBuilder()
                              ->select('c.ID')
                              ->from('AppBundle:Content', 'c')
                              ->leftJoin('c.parameterObj', 'cp')
                              ->leftJoin('c.collectionObj', 'ce')
                              ->leftJoin('ce.contentObj', 'cU')
                              ->where('cU.ID = ' . $this->baseID);
        } else if ($this->typeGeneral == Posts::TYPE_LIST_SEARCH) {
            $whereString = 'c.title LIKE :param_Z';
            $i           = 0;

            $query = $this->em->createQueryBuilder()
                              ->select('c.ID, COUNT(c.ID) AS cnt')
                              ->from('AppBundle:Content', 'c')
                              ->leftJoin('c.parameterObj', 'cp')
                              ->leftJoin('c.tagArray', 't');
            if ($this->isFilterContextAll())
                $query = $query->andWhere('cp.isBulk = 0');

            foreach ($this->queryVars as $var) {
                $whereString .= ' OR t.label = :param_' . $i;
                $query       = $query->setParameter('param_' . $i, $var);
                $i++;
            }
            $query = $query->andWhere($whereString)->setParameter('param_Z', '%' . implode(' ', $this->queryVars) . '%');
            $query = $query
                ->orderBy('cnt', 'DESC')
                ->setFirstResult($this->offset)
                ->setMaxResults($this->limit)
                ->groupBy('c.ID');

            if ($slug == 'all')
                $query = $query->andWhere('cp.isBulk = 0');
        } else if($this->typeGeneral == Posts::TYPE_LIST_BASE){
            $query = $this->em->createQueryBuilder()
                              ->select('c.ID')
                              ->from('AppBundle:Content', 'c')
                              ->leftJoin('c.parameterObj', 'cp')->where('c.ID > 1');
        } else {
            return $this->elementsInFilter[$filter] = -2;
        }

        $query = $query->andWhere('cp.type ' . $type)
                       ->andWhere('cp.isPrivate = 0')
                       ->setMaxResults($maxRes);
        $query = $query->getQuery();
        //echo $query->getSQL();

        $query = $query->useQueryCache(TRUE)
                       ->useResultCache(TRUE, 7200)
                       ->getResult();

        $i = count($query);

        $this->elementsInFilter[$filter] = $i;
        return $this->getRoundedCountValue($i, $maxRes);
    }

    private function getRoundedCountValue(int $i, int $maxRes) {
        switch ($i) {
            case $i < 10 || $i == $maxRes;
                break;
            case $i < 100:
                $i = (int)($i / 10) * 10;
                break;
            case $i < 500:
                $i = (int)($i / 25) * 25;
                break;
            case $i < 1000:
                $i = (int)($i / 50) * 50;
                break;
        }
        return $i;
    }

    private function loadMainContextObjectByTypeAsTagList() {
        /** @var Tag $query */
        $query = $this->em->createQueryBuilder()
                          ->select('t,tm, tc')
                          ->from('AppBundle:Tag', 't')
                          ->leftJoin('t.tagMeta', 'tm')
                          ->leftJoin('t.tagChildObject', 'tc')
                          ->where('t.slug = :tparam')
                          ->andWhere('t.count > 0')
                          ->setParameter('tparam', $this->tagSlug)
                          ->getQuery()
                          ->useQueryCache(TRUE)
                          ->useResultCache(TRUE, 600)
                          ->getOneOrNullResult();

        if (!$query) {
            $this->isValidContext = FALSE;
            return;
        }

        $this->tagObject = $query;
        $this->contextObjectTag = $query;
        $this->isValidContext   = TRUE;
    }
    public function getTagObject():?Tag{
        return $this->tagObject;
    }
    private function standardQuery() {
        return $this->resultPopular = $this->em->createQueryBuilder()
                                               ->select('c,t,cp,cm')
                                               ->from('AppBundle:Content', 'c')
                                               ->leftJoin('c.parameterObj', 'cp')
                                               ->leftJoin('c.thumbnailObj', 't')
                                               ->leftJoin('c.contentMeta', 'cm')
                                               ->where('cp.isBulk = 0')
                                               ->andWhere('cp.type ' . $this->typeSQL)
                                               ->andWhere('cp.isSFW = 1')
                                               ->andWhere('cp.isPrivate = 0')
                                               ->andWhere('c.ID ' . ($this->cIDtoSkip > 0 ? '!= ' . $this->cIDtoSkip : ' > 0'))
                                               ->orderBy($this->isPopularContext ? 'cp.score' : 'cp.timestamp', 'DESC')
                                               ->setFirstResult($this->offset)
                                               ->setMaxResults($this->limit)
                                               ->getQuery()
                                               ->useQueryCache(TRUE)
                                               ->useResultCache(TRUE, 600)
                                               ->getResult();
    }

    /** @return \AppBundle\Entity\Content[] */
    private function contentQueryFromShadowList(): array {
        if ($this->client->getStream()->getShadowListLength() < 1)
            return [];

        // create ordered List from ShadowList
        $orderedList = [];
        $p           = [$this->offset, $this->limit + 1];
        $tList       = array_slice($this->client->getStream()->getShadowList(), $p[0], $p[1]);

        foreach ($tList as $id) {
            $orderedList['_' . $id] = NULL;
        }

        $whereExpression = 'c.ID = ' . implode(' OR c.ID = ', $tList);

        // load Content from Database
        /** @var $query \AppBundle\Entity\Content[] */
        $query = $this->em->createQueryBuilder()
                          ->select('c,ct, cp')
                          ->from('AppBundle:Content', 'c')
                          ->leftJoin('c.parameterObj', 'cp')
                          ->leftJoin('c.thumbnailObj', 'ct')
                          ->where($whereExpression)
                          ->andWhere('cp.isPrivate = 0')
                          ->getQuery()
                          ->useQueryCache(TRUE)
                          ->useResultCache(TRUE, 1200)
                          ->getResult();

        // parse Result to orderedList
        if (!$query) {
            return [];
        }

        $lastID = 0;
        foreach ($query as $item) {
            $orderedList['_' . $item->getID()] = $item;
        }

        foreach($orderedList as $k => $v){
            //echo " _ " . $k;
            if($v === NULL){
                //echo " <---- | ";
                unset($orderedList[$k]);
            }
        }

        if (count($query) > $this->limit) {
            if(count($orderedList) == count($query)) {
                end($orderedList);         // move the internal pointer to the end of the array
                $key = key($orderedList);
                unset($orderedList[$key]);
                reset($orderedList);
            }
            $this->hasMoreResultsThanLimit = TRUE;
        }

        $this->resultRelated = $orderedList;

        return $this->resultRelated;
    }

    private function collectionQueryFromShadowList():?\AppBundle\Entity\Content {
        if (!($this->contextObjectContent->getID() ?? 0) || $this->client->getStream()->getShadowListLength() < 1) {
            $this->contextObjectContent->setElementList([]);
            return $this->contextObjectContent;
        }

        // create ordered List from ShadowList
        $orderedList = [];
        $tList       = array_slice($this->client->getStream()->getShadowList(), $this->offset, $this->limit + 1);

        foreach ($tList as $id) {
            $orderedList['_' . $id] = NULL;
        }

        $whereExpression = 'c.ID = ' . implode(' OR c.ID = ', $tList);

        // load Content from Database
        /** @var $query \AppBundle\Entity\Content[] */
        $query = $this->em->createQueryBuilder()
                          ->select('c,ct, cp')
                          ->from('AppBundle:Content', 'c')
                          ->leftJoin('c.parameterObj', 'cp')
                          ->leftJoin('c.thumbnailObj', 'ct')
                          ->where($whereExpression)
                          ->andWhere('cp.isPrivate = 0')
                          ->getQuery()
                          ->useQueryCache(TRUE)
                          ->useResultCache(TRUE, 1200)
                          ->getResult();

        // parse Result to orderedList
        if (!$query) {
            $this->contextObjectContent->setElementList([]);
            return $this->contextObjectContent;
        }

        $lastID = 0;
        foreach ($query as $item) {
            $orderedList['_' . $item->getID()] = $item;
        }

        if (count($query) > $this->limit) {
            end($orderedList);         // move the internal pointer to the end of the array
            $key = key($orderedList);
            unset($orderedList[$key]);
            reset($orderedList);

            $this->hasMoreResultsThanLimit = TRUE;
        }

        if ($this->useAdvertisement) {
            $orderedList = $this->fillInAdvertisementEntitiesForTags($orderedList);
        }

        $this->contextObjectContent->setCollectionObj($orderedList);

        return $this->result = $this->contextObjectContent;
    }

    private function tagQueryFromShadowList():?Tag {
        if (!($this->contextObjectTag->getID() ?? 0) || $this->client->getStream()->getShadowListLength() < 1) {
            $this->contextObjectTag->setContentObjArray([]);
            return $this->contextObjectTag;
        }

        // create ordered List from ShadowList
        $orderedList = [];
        $tList       = array_slice($this->client->getStream()->getShadowList(), $this->offset, $this->limit + 1);

        foreach ($tList as $id) {
            $orderedList['_' . $id] = NULL;
        }

        $whereExpression = 'c.ID = ' . implode(' OR c.ID = ', $tList);

        // load Content from Database
        /** @var $query \AppBundle\Entity\Content[] */
        $query = $this->em->createQueryBuilder()
                          ->select('c,ct, cp')
                          ->from('AppBundle:Content', 'c')
                          ->leftJoin('c.parameterObj', 'cp')
                          ->leftJoin('c.thumbnailObj', 'ct')
                          ->where($whereExpression)
                          ->andWhere('cp.isPrivate = 0')
                          ->getQuery()
                          ->useQueryCache(TRUE)
                          ->useResultCache(TRUE, 1200)
                          ->getResult();

        // parse Result to orderedList
        if (!$query) {
            $this->contextObjectTag->setContentObjArray([]);
            return $this->contextObjectTag;
        }

        $lastID = 0;
        foreach ($query as $item) {
            $orderedList['_' . $item->getID()] = $item;
        }

        if (count($query) > $this->limit) {
            end($orderedList);         // move the internal pointer to the end of the array
            $key = key($orderedList);
            unset($orderedList[$key]);
            reset($orderedList);

            $this->hasMoreResultsThanLimit = TRUE;
        }

        if ($this->useAdvertisement) {
            $orderedList = $this->fillInAdvertisementEntitiesForTags($orderedList);
        }

        $this->contextObjectTag->setContentObjArray($orderedList);

        $this->result = $this->contextObjectTag;
        return $this->result;
    }

    private function tagQuery():?Tag {
        /** @var \AppBundle\Entity\Content[] $res */
        $res = $this->em->createQueryBuilder()
                        ->select('c,cp,cm,ct,tm,t')
                        ->from('AppBundle:Content', 'c')
                        ->leftJoin('c.tagArray', 't')
                        ->leftJoin('t.tagMeta', 'tm')
                        ->leftJoin('c.parameterObj', 'cp')
                        ->leftJoin('c.contentMeta', 'cm')
                        ->leftJoin('c.thumbnailObj', 'ct')
                        ->where('t.slug = :tslug')
                        ->andWhere('cp.type ' . $this->typeSQL)
                        ->andWhere('cp.isPrivate = 0')
                        ->setParameter('tslug', $this->tagSlug)
                        ->orderBy($this->isPopularContext ? 'cp.score' : 'cp.timestamp', 'DESC')
                        ->setFirstResult($this->offset)
                        ->setMaxResults($this->limit + 1)
                        ->getQuery()
                        ->useResultCache(TRUE, 600)
                        ->useQueryCache(TRUE)
                        ->getResult();

        if (!$res)
            return $this->result = NULL;

        $tag = new Tag($res[0]->getTagArray()[0]->getLabel(), $res[0]->getTagArray()[0]->getSlug());
        $tag->setMetaObj($res[0]->getTagArray()[0]->getMetaObj());
        $tag->setContentObjArray($res);

        $this->result = $tag;

        //print_r($tag->getContentObjArrayCollection()->count());
        //die('fh jksdhafjk hsdjkh fhjksdh jkfhjksdh jkfhsdjkh kjfhsdjk ');
        $num = count($this->result->getContentObjArray());
        if ($num > $this->limit) {
            $this->hasMoreResultsThanLimit = TRUE;
            $this->result->getContentObjArrayCollection()->remove($num - 1);
        }

        return $this->result;
    }

    private function startPageQuery() {
        // Load NEW Content
        /** @var $qb ArrayCollection */
        $qb = $this->em->createQueryBuilder()
                       ->select('c,t,u,cp,cm')
                       ->from('AppBundle:Content', 'c')
                       ->leftJoin('c.parameterObj', 'cp')
            //->leftJoin('cp.userObj', 'u')
                       ->leftJoin('c.thumbnailObj', 't')
                       ->leftJoin('c.contentMeta', 'cm')
                       ->where('cp.isBulk = 0')
                       ->andWhere('cp.type < 10')
                       ->andWhere('cp.isSFW = 1')
                       ->andWhere('cp.isPrivate = 0')
                       ->orderBy('cp.timestamp', 'DESC')
                       ->setFirstResult($this->offset)
                       ->setMaxResults($this->limit)
                       ->getQuery()
                       ->useResultCache(TRUE, 600)
                       ->useQueryCache(TRUE)
                       ->getResult();
    }

    /**
     * @return \AppBundle\Entity\Content[]
     */
    private function tagXQuery(): array {
        $this->result;
        return [];
    }

    private function postQuery(): \AppBundle\Entity\Content {
        $query = $this->em->createQueryBuilder()
                          ->select('c,cp,cm,ce,ct,i,t,tm')
                          ->from('AppBundle:Content', 'c')
                          ->leftJoin('c.parameterObj', 'cp')
                          ->leftJoin('c.contentMeta', 'cm')
                          ->leftJoin('c.elementList', 'ce')
                          ->leftJoin('c.thumbnailObj', 'ct');
        if ($this->typeContent == \AppBundle\Safety\Types\Content::TYPE_VIDEO)
            $query->leftJoin('ce.videoObj', 'i');
        else
            $query->leftJoin('ce.imageObj', 'i');

        $query = $query->leftJoin('c.tagArray', 't')
                       ->leftJoin('t.tagMeta', 'tm')
                       ->where('c.ID = ' . $this->baseID . ' AND cp.type ' . $this->typeSQL)
                       ->getQuery()
                       ->useQueryCache(TRUE)
                       ->useResultCache(TRUE, 600);

        $this->result = $query->getOneOrNullResult();
        return $this->result;
    }

    /** @return int[] */
    private function baseQueryIDs(): array {
        $IDs = $this->em
            ->createQueryBuilder()
            ->select('c.ID')
            ->from('AppBundle:ContentParameter', 'cp')
            ->leftJoin('cp.contentObj', 'c')
            ->where('cp.isPrivate = 0');

        if ($this->isFilterContextAll())
            $IDs = $IDs->andWhere('cp.isBulk = 0');

        if (
            $this->typeContent == \AppBundle\Safety\Types\Content::TYPE_IMAGE ||
            $this->typeContent == \AppBundle\Safety\Types\Content::TYPE_GIF ||
            $this->typeContent == \AppBundle\Safety\Types\Content::TYPE_VIDEO ||
            $this->typeContent == \AppBundle\Safety\Types\Content::TYPE_COLLECTION ||
            $this->typeContent == \AppBundle\Safety\Types\Content::TYPE_LINK
        )
            $IDs = $IDs->andWhere('cp.type = ' . $this->typeContent);

        if ($this->isPopularContext)
            $IDs = $IDs->orderBy('cp.score', 'DESC');
        else
            $IDs = $IDs->orderBy('cp.timestamp', 'DESC');

        $IDs = $IDs->setMaxResults(1500)
                   ->getQuery()
                   ->useQueryCache(TRUE)
                   ->useResultCache(TRUE, 600)
                   ->getArrayResult();

        $this->resultPopularIDs = [];
        /** @var $id array */
        foreach ($IDs as $id)
            $this->resultPopularIDs[] = $id['ID'];

        return $this->resultPopularIDs;
    }

    /**
     * @return int[]
     */
    private function queryContentIDsByRelationSearchVars(): array {
        $list  = [];
        if(empty($this->queryVars))
            return $list;

        $whereString = 'c.title LIKE :param_Z';
        $i = 0;

        $qb = $this->em->createQueryBuilder()
                       ->select('c.ID, COUNT(c.ID) AS cnt')
                       ->from('AppBundle:Content', 'c')
            ->leftJoin('c.parameterObj', 'cp')
            ->leftJoin('c.tagArray', 't')
            ->where('cp.type ' . $this->typeSQL)
            ->andWhere('cp.isPrivate = 0');
        if ($this->isFilterContextAll())
            $qb = $qb->andWhere('cp.isBulk = 0');

        foreach($this->queryVars as $var){
            $whereString .= ' OR t.label = :param_'.$i;
            //$whereString .= ' OR t.label = :param_'.$i;
            $qb = $qb->setParameter('param_'.$i, $var);
            $i++;
        }
        $qb = $qb->andWhere($whereString)->setParameter('param_Z','%'.implode(' ',$this->queryVars).'%');
        $qb = $qb
                 ->orderBy('cnt', 'DESC')
                 ->setFirstResult(0)
                 ->setMaxResults(1500)
                 ->groupBy('c.ID')
                 ->getQuery()
                 /*->getSQL(); die();*/
                 ->useQueryCache(TRUE)
                 ->useResultCache(TRUE, 7200)
                 ->getArrayResult();

        /** @var $qb array */
        if (!$qb) {
            return $list;
        }

        foreach ($qb as $content) {
            $list[] = $content['ID'];
            //print_r($content);
        }
        //print_r($list);

        return $list;
    }
    /**
     * @return int[]
     */
    private function queryContentIDsByRelationTagID(): array {
        $list  = [];
        $tagID = $this->contextObjectTag->getID() ?? 0;

        if (!$tagID)
            return $list;

        $qb = $this->em->createQueryBuilder()
                       ->select('c.ID')
                       ->from('AppBundle:Content', 'c')
                       ->leftJoin('c.tagArray', 't')
                       ->leftJoin('c.parameterObj', 'cp')
                       ->where('t.ID = ' . $tagID)
                       ->andWhere('cp.type ' . $this->typeSQL)
                       ->andWhere('cp.isPrivate = 0');

        if ($this->isFilterContextAll())
            $qb = $qb->andWhere('cp.isBulk = 0');

        $qb = $qb->orderBy($this->isPopularContext ? 'cp.score' : 'cp.timestamp', 'DESC')
                 ->setFirstResult(0)
                 ->setMaxResults(1500)
                 ->getQuery();
        //echo $qb->getSQL();
        $qb = $qb->useQueryCache(TRUE)
                 ->useResultCache(TRUE, 600, 'muddler')
                 ->getArrayResult();
        //qb = $qb->getResult();
        //echo var_dump($qb);
        /** @var $qb array */
        if (!$qb) {
            return $list;
        }

        /** @var $content Content*/
        foreach ($qb as $content) {
            $list[] = $content['ID'];
        }

        return $list;
    }

    /**
     * @return int[]
     */
    private function queryContentIDsByRelationCollectionID(): array {
        if ($this->isRelatedContext)
            return $this->getRelatedContentIDs();

        $list         = [];
        $collectionID = $this->contextObjectContent->getID() ?? 0;

        if (!$collectionID || $this->contextObjectContent->getParameterObj()->getType() != \AppBundle\Safety\Types\Content::TYPE_COLLECTION)
            return $list;

        $qb = $this->em->createQueryBuilder()
                       ->select('c.ID')
                       ->from('AppBundle:Content', 'c')
                       ->leftJoin('c.collectionObj', 'ce')
                       ->leftJoin('c.parameterObj', 'cp')
                       ->leftJoin('ce.contentObj', 'cU')
                       ->where('cU.ID = ' . $collectionID)
                       ->andWhere('cp.type ' . $this->typeSQL)
                       ->andWhere('cp.isPrivate = 0');

        $qb = $qb->orderBy($this->isPopularContext ? 'cp.score' : 'cp.timestamp', 'DESC')
                 ->setFirstResult(0)
                 ->setMaxResults(1500)
                 ->getQuery();
        //echo $qb->getSQL();
        $qb = $qb->useQueryCache(TRUE)
                 ->useResultCache(TRUE, 600)
                 ->getArrayResult();

        /** @var $qb array */
        if (!$qb) {
            return $list;
        }

        foreach ($qb as $content) {
            $list[] = $content['ID'];
        }

        return $list;
    }

    /**
     * @param int [] $IDs
     * @return ContentEntityMini[]
     */
    private function getContentEntitiesByIDsMiniList(array $IDs): array {
        $listOrdered = [];
        if (empty($IDs))
            return [];

        foreach ($IDs as $id) {
            $listOrdered[$id] = TRUE;
        }

        $whereExpression = 'c.ID = ' . implode(' OR c.ID = ', $IDs);

        $res = $this->em->createQueryBuilder()
                        ->select('c,ct,cp')
                        ->from('AppBundle:Content', 'c')
                        ->leftJoin('c.parameterObj', 'cp')
                        ->leftJoin('c.thumbnailObj', 'ct')
                        ->where($whereExpression)
                        ->andWhere('cp.isPrivate = 0')
                        ->getQuery()
                        ->useQueryCache(TRUE)
                        ->useResultCache(TRUE, 1800)
                        ->getResult();

        $list = [];
        /** @var $r \AppBundle\Entity\Content */
        foreach ($res as $r) {
            $a = new ContentStreamMiniEntity();
            $listOrdered[$r->getID()] = $a->constructFromEntity($r);
        }
        foreach($listOrdered as $k => $l)
            if(is_bool($l))
                unset($listOrdered[$k]);

        $list = array_values($listOrdered);

        return !empty($list) ? $list : [];
    }

    /** @return int[] */
    private function getRelatedContentIDs(): array {
        if (!($this->contextObjectContent->getID() ?? 0))
            return [];

        $contentGoal         = 500;
        $contentCount        = 0;
        $contentIDsInUseList = [];

        foreach ($this->contextObjectContent->getTagArraySortedByCountASC() as $tag) {
            if ($contentCount >= $contentGoal)
                break;

            $result = $this->em->createQueryBuilder()
                               ->select('c.ID, cp.score')
                               ->from('AppBundle:Tag', 't')
                               ->leftJoin('t.contentObjArray', 'c')
                               ->leftJoin('c.parameterObj', 'cp')
                               ->where('cp.isPrivate = 0')
                               ->andWhere('cp.isBulk = 0')
                               ->andWhere('t.ID = ' . $tag->getID())
                               ->andWhere(($this->typeContent == \AppBundle\Safety\Types\Content::TYPE_INFO) ? 'cp.type < 10' : 'cp.type = ' . $this->typeContent)
                               ->orderBy($this->isPopularContext ? 'cp.score' : 'cp.timestamp', 'DESC')
                               ->setMaxResults($contentGoal * 2)
                               ->getQuery()
                               ->useQueryCache(TRUE)
                               ->useResultCache(TRUE, 600)
                               ->getResult();

            foreach ($result as $res) {
                if ($res['ID'] == $this->baseID)
                    continue;
                else if (!isset($contentIDsInUseList[$res['ID']]))
                    $contentIDsInUseList[$res['ID']] = 1;
                else if ($this->isPopularContext)
                    $contentIDsInUseList[$res['ID']] += $res['score'] + 0.25;
                else
                    $contentIDsInUseList[$res['ID']]++;
            }

            $contentCount = count($contentIDsInUseList);
        }
        // sort the array by values
        arsort($contentIDsInUseList);

        $list = [];
        foreach ($contentIDsInUseList as $k => $null) {
            $list[] = $k;
        }

        return $this->resultRelatedOnlyContentIDs = $list;
    }

    /** @return Content[] */
    private function getRelatedContent(): array {
        $list = array_slice($this->resultRelatedOnlyContentIDs, $this->limit, $this->offset);
        print_r($list);
        die();
    }

    private function hasQualifiedPostQuery(): bool {
        if (!$this->result && !$this->postQuery())
            return FALSE;

        return TRUE;
    }

    /** @return ContentStreamMiniEntity[] */
    private function getStreamContent(): array {
        if (!$this->hasQualifiedPostQuery())
            return [];

        // at this Moment, the stream should be build already

        return ($this->resultStream = $this->client->getStream()->getMainList(3));
    }

    /** @return Content */
    public function finalize(): Content {
        $this->finalizeAsContentPostType();
        $this->finalizeAsContentListType();

        $this->client->saveStream();

        return $this;
    }

    private function finalizeAsContentPostType() {
        if ($this->typeGeneral !== Posts::TYPE_POST || !$this->isValidContext())
            return;

        $this->result = $this->contextObjectContent;
        // Testing if client provides Content stream
        if (!$this->client->getStream()->hasShadowList()) {
            $this->fillStreamLikeNewUser();
        }

        // Fill in New ShadowList by Related Content
        $list = $this->getRelatedContentIDs();

        // check if Post is the previous one
        if ($this->isPostIDInMainList($this->result->getID())) {
            // nice just swap entries to history
            $this->swapMainListEntriesToHistoryList($this->result->getID());
            $this->testLengthOfMainListAndReloadOnDemand();
        } else if ($this->isPostIDPreviousOne(2)) {
            // Last one will be pushed back to MainStream
            $post = $this->client->getStream()->getHistoryLast(1);
            $this->client->getStream()->addMainEntryAtBegin($post);
            $this->client->getStream()->removeHistoryEntryLast();
            // check if Post is one of the following MainPosts
        } else if ($this->isPostIDInShadowList($this->result->getID())) {
            $this->client->getStream()->clearMainList();
            $this->client->getStream()->clearQueueList();
            $this->client->getStream()->addHistoryEntry((new ContentStreamMiniEntity())->constructFromEntity($this->result));

            $this->useShadowListAsNewQueueList($this->result->getID());
        }

        $this->client->getStream()->setShadowList($list);
    }

    /**
     * @param int
     * @return bool
     */
    private function isPostIDPreviousOne(int $pos = 1): bool {
        return $this->client->getStream()->getHistoryLast($pos)->getID() == $this->result->getID();
    }

    public function finalizeAsContentListType() {
        if ($this->typeGeneral !== Posts::TYPE_LIST_TAG &&
            $this->typeGeneral !== Posts::TYPE_LIST_BASE &&
            $this->typeGeneral !== Posts::TYPE_LIST_COLLECTION &&
            $this->typeGeneral !== Posts::TYPE_LIST_SEARCH
        )
            return;

        if ($this->typeGeneral == Posts::TYPE_LIST_TAG)
            $list = $this->queryContentIDsByRelationTagID();
        else if ($this->typeGeneral == Posts::TYPE_LIST_COLLECTION)
            $list = $this->queryContentIDsByRelationCollectionID();
        else if($this->typeGeneral == Posts::TYPE_LIST_SEARCH)
            $list = $this->queryContentIDsByRelationSearchVars();
        else
            $list = $this->baseQueryIDs();

        $this->client->getStream()->clearMainList();
        $this->client->getStream()->clearQueueList();
        $this->client->getStream()->setShadowList($list);
    }

    private function fillStreamLikeNewUser() {
        $this->client->getStream()->clearMainList();
        $this->client->getStream()->clearQueueList();

        // Do the full job ...
        // Load ShadowList -> in general... PopularStream
        $popularIDs = empty($this->resultPopularIDs) ? $this->baseQueryIDs() : $this->resultPopularIDs;

        // shadowList is equal to QueueList
        // set QueueList and ShadowList
        $this->client->getStream()
                     ->setQueueList($popularIDs);

        $this->testLengthOfMainListAndReloadOnDemand();
        // HistoryList is empty or not, who cares -_- - add this post to history
        $this->client->getStream()->addHistoryEntry(
            (new ContentStreamMiniEntity())->constructFromEntity($this->result)
        );
    }

    /**
     * @param int $id
     * @return bool
     */
    private function isPostIDInMainList(int $id): bool {
        $list = $this->client->getStream()->getMainList($this->resultStreamLengthVisible);
        foreach ($list as $item) {
            if ($item->getID() == $id)
                return TRUE;
        }
        return FALSE;
    }

    /**
     * @param int $id
     * @return bool
     */
    private function isPostIDInShadowList(int $id): bool {
        return in_array($id, $this->client->getStream()->getShadowList());
    }

    /** @param int $id */
    private function swapMainListEntriesToHistoryList(int $id) {
        $list = $this->client->getStream()->getMainList($this->resultStreamLengthVisible);
        foreach ($list as $item) {
            $this->client->getStream()->removeMainListEntry($item);
            $this->client->getStream()->addHistoryEntry($item);
            if ($item->getID() == $id)
                break;
        }
    }

    private function testLengthOfMainListAndReloadOnDemand() {
        if ($this->client->getStream()->getMainListLength() < $this->resultStreamLengthMinimum) {
            // reload 10 or less (the max) from QueList
            $list = $this->client->getStream()->getQueueList(10);
            $this->client->getStream()->removeQueListEntries($list);
            $newContentEntities = $this->getContentEntitiesByIDsMiniList($list);
            $this->client->getStream()->addMainEntries($newContentEntities);
        }
    }

    /** @param int $id */
    private function useShadowListAsNewQueueList(int $id) {
        $itemList = [];
        $firstRun = TRUE;

        foreach ($this->client->getStream()->getShadowList() as $item) {
            if ($firstRun && $item != $id)
                continue;
            else if ($item == $id) {
                $firstRun = FALSE;
            } else {
                // These are all "Next Items" -> first of all we collect them, afterwards we are going to put them on the "QueueList"
                $itemList[] = $item;
            }
        }

        if (count($itemList) > 0) {
            $this->client->getStream()->setQueueList($itemList);
            $list = $this->client->getStream()->getQueueList(12);
            $this->client->getStream()->addMainEntries($this->getContentEntitiesByIDsMiniList($list));
            $this->client->getStream()->removeQueListEntries($list);
        }
    }

    private function fillInAdvertisementEntitiesForTags(array $orderedList) {
        $orderedList = array_values($orderedList);
        $mediaOffset = (count($orderedList) % 4) % 4;

        if ($mediaOffset == 0 && count($orderedList) >= 4)
            $mediaOffset = 4;
        else if (count($orderedList) < 4)
            $mediaOffset = 4 - count($orderedList);

        if ($this->typeContent != \AppBundle\Safety\Types\Content::TYPE_INFO)
            $where = 'cp.type = ' . ($this->typeContent + 50);
        else
            $where = '((cp.type > 50 AND cp.type < 60) OR cp.type = 32)';

        $res = $this->em->createQueryBuilder()
                        ->select('c,cp,cc,cl,ct')
                        ->from('AppBundle:Content', 'c')
                        ->leftJoin('c.thumbnailObj', 'ct')
                        ->leftJoin('c.parameterObj', 'cp')
                        ->leftJoin('c.elementList', 'cc')
                        ->leftJoin('cc.linkObj', 'cl')
                        ->leftJoin('c.tagArray','tag')
                        ->where($where . (!empty($this->tagSlug) ? ' AND tag.slug = \''.$this->tagSlug.'\'' : ''))
                        ->andWhere('cp.isPrivate = 0')
                        ->andWhere('cp.isBulk = 0')
                        ->orderBy($this->isPopularContext ? 'cp.score' : 'cp.timestamp', 'DESC')
                        ->setMaxResults($mediaOffset)
                        ->setFirstResult($mediaOffset * ($this->page - 1))
                        ->distinct( TRUE )
                        ->getQuery();
        // echo $res->getSQL();
        $res = $res->useQueryCache(TRUE)
                        ->useResultCache(TRUE, 86400)
                        ->getResult();
        /** @var $res \AppBundle\Entity\Content[] */
        if (!$res)
            return $orderedList;

        $sTimestamp = (int)(time() / 3600) + substr(crc32($this->contextObjectTag->getLabel()), 0, 1);
        $pos1       = $sTimestamp % 3;
        $j          = 0;

        $arr  = [];
        $cArr = $orderedList;

        if ((count($cArr) + count($res)) % 4 != 0)
            return $cArr;

        for ($i = 0; $i < count($cArr) + count($res); $i++) {
            if ($i == $pos1 && isset($res[$j])) {
                $res[$j]->setExtern(TRUE);
                $arr[] = $res[$j];
                $pos1  += 2;
                $j++;
            }
            if (isset($cArr[$i]))
                $arr[] = $cArr[$i];
        }
        //echo "< XC ".(count($cArr))." -- ".count($res)." -- ".count($arr)." >";die();
        return $arr;
    }

    private function hasSubContentElement(): bool {
        return $this->subContentElement !== NULL;
    }

    private function getCanonicalURLOfSubContent(): string {
        $returnString = '';
        if ($this->hasSubContentElement()) {
            $returnString = $this->subContentElement->getCanonicalURL();
        } else
            $returnString = $this->getPostContent()->getCanonicalURL();

        return $returnString;
    }
}