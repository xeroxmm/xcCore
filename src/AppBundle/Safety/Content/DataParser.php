<?php

namespace AppBundle\Safety\Content;

use AppBundle\Entity\Content;
use AppBundle\Template\ContentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\VarDumper\Cloner\Data;

class DataParser {
    private $title            = '';
    private $description      = '';
    private $apiUserName      = '';
    private $apiUserPw        = '';
    private $url              = '';
    private $type             = 0;
    private $tags             = [];
    private $jsonString       = '';
    private $jsonObj          = [];
    private $embedID          = '';
    private $isBulk           = FALSE;
    private $childOf          = 0;
    private $forcedSource     = '';
    private $forcedLinkIntern = FALSE;

    function parseByRequestObj(Request $request) {
        $this->apiUserName = $request->request->get('user', '');
        $this->apiUserPw   = $request->request->get('hash', '');
        $this->url         = $request->request->get('url', '');
        $this->title       = $request->request->get('title', '');
        $this->description = $request->request->get('description', '');
        foreach (explode(',', $request->request->get('tags', '')) as $t)
            $this->tags[$t] = $t;
        $this->jsonString       = $request->request->get('data', '{}');
        $this->jsonObj          = (($s = @json_decode($this->jsonString)) !== NULL) ? $s : [];
        $this->type             = $request->request->get('type', 0);
        $this->childOf          = $request->request->get('partof', 0);
        $this->forcedSource     = $request->request->get('source', '');
        $this->forcedLinkIntern = $request->request->get('link', FALSE);
    }

    function parseByMiniContainer(MiniContainer $container) {
        $this->url         = $container->url ?? '';
        $this->title       = $container->title ?? '';
        $this->description = $container->description ?? '';
        foreach (explode(',', $container->tags ?? '') as $t)
            $this->tags[$t] = $t;
    }

    function getChildOf():int{ return $this->childOf; }

    function setBulk(bool $is){ $this->isBulk = $is;}
    function getBulk():bool{ return $this->isBulk; }

    function setType(int $type) {
        $this->type = $type;
    }

    function getJsonString() {
        return $this->jsonString;
    }
    public function setTitleIfEmpty(string $title = ''){
        if(!empty($this->title) || empty($title))
            return;

        $this->title = $title;
    }
    function isReadyForAPI(): bool {
        switch ($this->type) {
            case \AppBundle\Safety\Types\Content::TYPE_INFO:
                return !empty($this->apiUserPw) &&
                       !empty($this->apiUserName);
            case \AppBundle\Safety\Types\Content::TYPE_VIDEO:
            case \AppBundle\Safety\Types\Content::TYPE_IMAGE:
            case \AppBundle\Safety\Types\Content::TYPE_ADVERTISEMENT_NET_EXTERN:
            case \AppBundle\Safety\Types\Content::TYPE_ADVERTISEMENT_NET_INTERN:
            case \AppBundle\Safety\Types\Content::TYPE_ADVERTISEMENT_STORE:
            case \AppBundle\Safety\Types\Content::TYPE_ADVERTISEMENT_TRADE:
            case \AppBundle\Safety\Types\Content::TYPE_ADVERTISEMENT_NET_EXTERN_COLLECTION:
            case \AppBundle\Safety\Types\Content::TYPE_ADVERTISEMENT_NET_EXTERN_GIF:
            case \AppBundle\Safety\Types\Content::TYPE_ADVERTISEMENT_NET_EXTERN_IMAGE:
            case \AppBundle\Safety\Types\Content::TYPE_ADVERTISEMENT_NET_EXTERN_LINK:
            case \AppBundle\Safety\Types\Content::TYPE_ADVERTISEMENT_NET_EXTERN_VIDEO:
                return
                    !empty($this->apiUserPw) &&
                    !empty($this->apiUserName) &&
                    !empty($this->url);
            case \AppBundle\Safety\Types\Content::TYPE_COLLECTION:
                return
                    !empty($this->apiUserPw) &&
                    !empty($this->apiUserName) &&
                    !empty($this->jsonObj);
            default:
                return FALSE;
        }
    }

    function addTags(array $arr): DataParser {
        foreach ($arr as $a)
            $this->tags[$a] = $a;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getApiUserName(): string {
        return $this->apiUserName;
    }

    /**
     * @return string
     */
    public function getApiUserPw(): string {
        return $this->apiUserPw;
    }

    /**
     * @return string
     */
    public function getUrl(): string {
        return $this->url;
    }
    public function getSource():string{
        return $this->forcedSource;
    }
    public function getType(): int {
        return $this->type;
    }

    public function getTags(): array {
        return $this->tags;
    }

    public function getEmbedID(): string {

    }

    public function setTitle(string $title = '') {
        $this->title = (!empty($title)) ? $title : $this->title;
    }

    public function getData() {
        return $this->jsonObj;
    }
    public function hasForcedLink():bool{
        return !($this->forcedLinkIntern === FALSE);
    }
    public function getForcedLink():string {
        return (is_string($this->forcedLinkIntern) ? $this->forcedLinkIntern : $this->url);
    }
}