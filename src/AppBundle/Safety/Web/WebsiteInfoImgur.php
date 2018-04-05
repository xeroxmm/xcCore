<?php
namespace AppBundle\Safety\Web;

use AppBundle\Safety\Types\Content;

class WebsiteInfoImgur extends WebsiteInfoStock {
    private $websiteContent = '';

    function __construct(string $websiteContent, string $url) {
        $this->websiteContent = $websiteContent;
        $this->parseThumbnail( $url );
    }

    private function parseThumbnail(string $fromURL){
        $this->thumbnailURL = str_replace('.gifv','h.jpg', $fromURL);
        $this->overwriteType = Content::TYPE_GIF;
    }
}