<?php
namespace AppBundle\Tools\Supply;

use AppBundle\Interfaces\ContentEntityMini;

class ContentStreamMiniEntity implements ContentEntityMini{
    private $ID        = 0;
    private $linkThumb = '';
    private $link      = '';
    private $title     = '';

    public function constructFromArray(array $sessionInfo): ContentStreamMiniEntity{
        $this->ID        = ((int)$sessionInfo[0] ?? 0);
        $this->linkThumb = $sessionInfo[1] ?? '';
        $this->link      = $sessionInfo[2] ?? '';
        $this->title     = $sessionInfo[3] ?? '';

        return $this;
    }
    public function toIndexedArray(): array {
        return [$this->ID, $this->linkThumb, $this->link, $this->title];
    }

    public function constructFromEntity(\AppBundle\Entity\Content $content): ContentStreamMiniEntity{
        $this->ID        = $content->getID();
        $this->linkThumb = $content->getThumbnailObj()->getThumbnailLinkURL();
        $this->link      = $content->getFullURL();
        $this->title     = $content->getTitle();

        return $this;
    }

    public function getID(): int {
        return $this->ID;
    }

    public function getTitle():string {
        return $this->title;
    }

    public function getLink():string {
        return $this->link;
    }

    public function getThumbnailURL():string {
        return $this->linkThumb;
    }
}