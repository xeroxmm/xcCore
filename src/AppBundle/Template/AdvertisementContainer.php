<?php

namespace AppBundle\Template;

use AppBundle\Entity\Content;
use AppBundle\Entity\Tag;
use AppBundle\Interfaces\AdvertisementResource;
use AppBundle\Tools\Advertisement\DatabaseTie;
use AppBundle\Tools\Advertisement\Plug;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class AdvertisementContainer {
    /** @var AdvertisementResource[] */
    private $elements = [];
    private $next = 0;
    private $max = 0;

    private $databaseHook;
    /** @var Tag[] */
    private $tags = [];
    private $serverAddress;
    private $request;

    /** @var null|ArrayCollection */
    private $rawReferrer = NULL;

    public function __construct(EntityManagerInterface $em, Request $request, string $serverAddress = "") {
        $this->databaseHook = new DatabaseTie($em);
        $this->serverAddress = $serverAddress;
        $this->request = $request;
    }
    public function addTag(Tag $tag = NULL){
        if(!$tag) return;
        $this->tags[$tag->getID()] = $tag;
    }
    public function loadElements(int $number = 4):AdvertisementContainer{
        $contentC = []; $link = [];
        $this->databaseHook->loadAdvertisementPlugsByTags($this->tags, $number, $this->request->query->get('page',1), $contentC);

        /** @var $content Content*/
        foreach($contentC as $content){
            $element = new Plug(
                $content->getElementList()[0]->getLinkObj()->getExternURL(),
                $content->getTitle(),
                $content->getDescription(),
                $content->getThumbnailObj()->getThumbnailLinkURL(),
                $this->serverAddress);

            $this->elements[] = $element;
        }
        unset($contentC);
        shuffle($this->elements);
        return $this;
    }
    public function getNextElement():?AdvertisementResource {
        return $this->elements[($this->next++)] ?? NULL;
    }
    public function resetCounter(){
        $this->next = 0;
    }
    public function hasElements(int $number = 1){
        return count($this->elements) >= $number;
    }
    public function addReferrerOfClient(ArrayCollection $rawReferrer){
        $this->rawReferrer = $rawReferrer;
        $this->databaseHook->addReferrerOfClient($rawReferrer);
    }
}