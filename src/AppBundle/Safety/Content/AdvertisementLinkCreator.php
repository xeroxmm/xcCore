<?php

namespace AppBundle\Safety\Content;

use AppBundle\Safety\Types\Content;
use Doctrine\ORM\EntityManagerInterface;

class AdvertisementLinkCreator extends LinkCreator {
    function __construct(EntityManagerInterface $em, int $contentType = -1) {
        parent::__construct($em);
        $this->type = (!Content::isAdvertisement($contentType)) ? Content::TYPE_ADVERTISEMENT_NET_EXTERN : $contentType;
    }
    public function setTYPE_TRADE(){
        $this->type = Content::TYPE_ADVERTISEMENT_TRADE;
    }
    public function setTYPE_STORE(){
        $this->type = Content::TYPE_ADVERTISEMENT_STORE;
    }
    public function setTYPE_NET_INTERN(){
        $this->type = Content::TYPE_ADVERTISEMENT_NET_INTERN;
    }
    public function setTYPEbySlug(?string $slug){
        $this->type = Content::getAdvertisementIntBySlug( $slug );
    }
}