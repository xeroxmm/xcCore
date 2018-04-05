<?php
namespace AppBundle\Safety\Web;

use AppBundle\Safety\Types\Content;

class WebsiteInfoGfycat extends WebsiteInfoStock {
    private $websiteContent = '';
    public $isGfyCat = TRUE;

    function __construct(string $websiteContent) {
        $this->websiteContent = $websiteContent;
        $this->parseThumbnail();
    }

    private function parseThumbnail(){
        $matches = ['s'=>''];
        preg_match('/poster="(.*?)-poster.jpg/', $this->websiteContent, $matches);

        if(!empty($matches[1]) && stripos($matches[1], 'thumbs.gfycat.com') !== FALSE)
            $this->thumbnailURL = $matches[1].'-poster.jpg';

        $this->overwriteType = Content::TYPE_GIF;
    }
    public function setRealID(URLParser $urlObj){
        $matches = ['s'=>''];
        preg_match('/<source id="mp4Source" src=".*?com\/(.*?)\.mp4/', $this->websiteContent, $matches);

        $newID = '';
        if(!empty($matches[1]) && strtolower($urlObj->getDomainSpecificID()) == strtolower($matches[1]))
            $newID = $matches[1];

        if(!empty($newID))
            $urlObj->setDomainSpecificID( $newID );
    }
}