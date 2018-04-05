<?php
namespace AppBundle\Safety\Web;

class WebsiteInfoMotherless extends WebsiteInfoStock {
    private $websiteContent = '';

    function __construct(string $websiteContent) {
        $this->websiteContent = $websiteContent;
        $this->parseThumbnail();
    }

    private function parseThumbnail(){
        $matches = ['s'=>''];
        preg_match('/property="og:image" content="(?P<s>[^"]+)"/', $this->websiteContent, $matches);

        if(!empty($matches['s']))
            $this->thumbnailURL = $matches['s'];
    }
}