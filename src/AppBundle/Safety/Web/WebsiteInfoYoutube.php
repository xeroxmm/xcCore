<?php
namespace AppBundle\Safety\Web;

class WebsiteInfoYoutube extends WebsiteInfoStock {
    private $websiteContent = '';

    function __construct( string $websiteContent ) {
        $this->websiteContent = $websiteContent;
        $this->parseDescription();
        $this->parseLength();
        $this->parseTags();
        $this->parseThumbnail();
        $this->parseViews();
        $this->parseTitle();
    }
    private function parseTitle(){
        $matches = ['s'=>''];
        preg_match('/<meta property="og:title" content="(?P<s>.*)">/', $this->websiteContent, $matches);
        if(!empty($matches['s']))
            $this->title = $matches['s'];
    }
    private function parseDescription(){
        $matches = ['s'=>''];
        preg_match('/<meta property="og:description" content="(?P<s>.*)">/', $this->websiteContent, $matches);
        if(!empty($matches['s']))
            $this->description = $matches['s'];
    }
    private function parseLength(){
        $matches = ['s'=>''];
        preg_match('/"length_seconds":"(?P<s>.*)"/', $this->websiteContent, $matches);
        if(!empty($matches['s']))
            $this->length = (int)$matches['s'];
    }
    private function parseTags(){
        $matches = ['s'=>[]];
        preg_match_all('/<meta property="og:video:tag" content="(?P<s>.*)">/', $this->websiteContent, $matches);
        if(count($matches['s']) > 0)
            $this->tags = $matches['s'];
    }
    private function parseThumbnail(){
        $matches = ['s'=>''];
        preg_match('/<meta property="og:image" content="(?P<s>.*)">/', $this->websiteContent, $matches);

        if(!empty($matches['s']))
            $this->thumbnailURL = $matches['s'];
    }
    private function parseViews(){
        $matches = ['s'=>''];
        preg_match('/<meta itemprop="interactionCount" content="(?P<s>.*)">/', $this->websiteContent, $matches);
        if(!empty($matches['s']))
            $this->views = (int)$matches['s'];
    }
}