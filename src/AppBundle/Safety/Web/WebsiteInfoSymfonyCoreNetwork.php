<?php
namespace AppBundle\Safety\Web;

class WebsiteInfoSymfonyCoreNetwork extends WebsiteInfoStock {
    private $websiteContent = '';

    function __construct(string $websiteContent) {
        $this->websiteContent = $websiteContent;
        $this->parseThumbnail();
    }

    private function parseThumbnail(){
        //print_r($this->websiteContent); die();
        $jsonObj = @json_decode($this->websiteContent);

        if(!isset($jsonObj->p) || !isset($jsonObj->p->c))
            return;

        $this->thumbnailURL = $jsonObj->p->c;
    }
}