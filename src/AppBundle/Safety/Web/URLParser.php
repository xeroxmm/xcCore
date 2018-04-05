<?php
namespace AppBundle\Safety\Web;

use AppBundle\Safety\Types\Hoster;

class URLParser {
    private $urlToCheck;

    private $isKnownSite = FALSE;
    private $isVideoSite = FALSE;
    private $isImageSite = FALSE;

    private $domain = '';
    private $hoster = 0;
    /** @var null | string */
    private $subdomain = NULL;
    /** @var null | string */
    private $domainSpecificID = NULL;

    function __construct(string $url){
        $this->urlToCheck = $url;
        if($this->__youtube()) return;
        if($this->__gfycat()) return;
        if($this->__imgurGIFV()) return;

        if($this->__motherless()) return;
        if($this->__pornhub()) return;

        if($this->__amateurboobgirls()) return;
        if($this->__blackelegancy()) return;
        if($this->__dreamlingerie()) return;
        if($this->__fapworld()) return;
        if($this->__gayspot()) return;
        if($this->__hentaistar()) return;
        if($this->__imgsmash()) return;
        if($this->__naughtyamateurgirls()) return;
        if($this->__naughtyjet()) return;
        if($this->__sexygirlsnet()) return;
        if($this->__thebestgirlsonline()) return;
        if($this->__tubi()) return;
        if($this->__upvoteselfies()) return;
        if($this->__wildgirlsonline()) return;
        if($this->__xgirlsnet()) return;
        if($this->__xxxgirlsnet()) return;
    }
    private function __youtube():bool{
        $matches = [];
        preg_match_all( '#^((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|v\/)?)([\w\-]+)(\S+)?$#' , $this->urlToCheck , $matches);

        if(!empty($matches[ 5 ]) && !empty($matches[ 3 ])){
            $this->isKnownSite = $this->isVideoSite = ($matches[ 3 ][0] == 'youtube.com' || $matches[ 3 ][0] == 'youtu.be');
            $this->domain = ($this->isVideoSite) ? 'youtube.com' : '';
            $this->hoster = ($this->isVideoSite) ? Hoster::TYPE_YOUTUBE : 0;
            $this->domainSpecificID = $matches[ 5 ][0];

            return TRUE;
        }
        return FALSE;
    }
    private function __imgurGIFV():bool{
        if(stripos($this->urlToCheck, 'i.imgur.com') !== FALSE && stripos($this->urlToCheck, '.gifv') !== FALSE){
            $this->isKnownSite = $this->isVideoSite = TRUE;
            $this->domain = 'i.imgur.com';
            $this->hoster = Hoster::TYPE_IMGUR_GIFV;

            $temp = explode('/', $this->urlToCheck);
            $temp = explode('.',$temp[ count($temp)-1 ]);
            $this->domainSpecificID = $temp[ 0 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __gfycat():bool{
        if(stripos($this->urlToCheck, 'gfycat.com') !== FALSE){
            $this->isKnownSite = $this->isVideoSite = TRUE;
            $this->domain = 'gfycat.com';
            $this->hoster = Hoster::TYPE_GFYCAT;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }

    private function __dreamlingerie():bool{
        if(stripos($this->urlToCheck, 'dream-lingerie.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'dream-lingerie.com';
            $this->hoster = Hoster::TYPE_DREAMLINGERIE;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __imgsmash():bool{
        if(stripos($this->urlToCheck, 'imgsmash.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'imgsmash.com';
            $this->hoster = Hoster::TYPE_IMGSMASH;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __amateurboobgirls():bool{
        if(stripos($this->urlToCheck, 'amateurboobgirls.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'amateurboobgirls.com';
            $this->hoster = Hoster::TYPE_AMATEURBOOBGIRLS;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __blackelegancy():bool{
        if(stripos($this->urlToCheck, 'blackelegancy.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'blackelegancy.com';
            $this->hoster = Hoster::TYPE_BLACKELEGANCY;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __fapworld():bool{
        if(stripos($this->urlToCheck, 'fapworld.co') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'fapworld.co';
            $this->hoster = Hoster::TYPE_FAPWORLD;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __gayspot():bool{
        if(stripos($this->urlToCheck, 'gayspot.top') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'gayspot.top';
            $this->hoster = Hoster::TYPE_GAYSPOT;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __hentaistar():bool{
        if(stripos($this->urlToCheck, 'hentaistar.co') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'hentaistar.co';
            $this->hoster = Hoster::TYPE_HENTAISTAR;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __naughtyamateurgirls():bool{
        if(stripos($this->urlToCheck, 'naughtyamateurgirls.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'naughtyamateurgirls.com';
            $this->hoster = Hoster::TYPE_NAUGHTYAMATEURGIRLS;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __naughtyjet():bool{
        if(stripos($this->urlToCheck, 'naughtyjet.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'naughtyjet.com';
            $this->hoster = Hoster::TYPE_NAUGHTYJET;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __sexygirlsnet():bool{
        if(stripos($this->urlToCheck, 'sexygirlsnet.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'sexygirlsnet.com';
            $this->hoster = Hoster::TYPE_SEXYGIRLSNET;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __thebestgirlsonline():bool{
        if(stripos($this->urlToCheck, 'thebestgirlsonline.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'thebestgirlsonline.com';
            $this->hoster = Hoster::TYPE_THEBESTGIRLSONLINE;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __tubi():bool{
        if(stripos($this->urlToCheck, 'tubi.site') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'tubi.site';
            $this->hoster = Hoster::TYPE_TUBI;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __upvoteselfies():bool{
        if(stripos($this->urlToCheck, 'upvoteselfies.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'upvoteselfies.com';
            $this->hoster = Hoster::TYPE_UPVOTESELFIES;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __wildgirlsonline():bool{
        if(stripos($this->urlToCheck, 'wildgirlsonline.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'wildgirlsonline.com';
            $this->hoster = Hoster::TYPE_WILDGIRLSONLINE;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __xgirlsnet():bool{
        if(stripos($this->urlToCheck, 'xgirlsnet.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'xgirlsnet.com';
            $this->hoster = Hoster::TYPE_XGIRLSNET;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __xxxgirlsnet():bool{
        if(stripos($this->urlToCheck, 'xxxgirlsnet.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'xxxgirlsnet.com';
            $this->hoster = Hoster::TYPE_XXXGIRLSNET;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __pornhub():bool{
        if(stripos($this->urlToCheck, 'pornhub.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'pornhub.com';
            $this->hoster = Hoster::TYPE_PORNHUB;

            $temp = explode('viewkey=', $this->urlToCheck);
            $temp = explode('&',$temp[ count($temp)-1 ]);
            $this->domainSpecificID = $temp[ 0 ];

            return TRUE;
        }
        return FALSE;
    }
    private function __motherless():bool{
        if(stripos($this->urlToCheck, 'motherless.com') !== FALSE){
            $this->isKnownSite = TRUE;
            $this->domain = 'motherless.com';
            $this->hoster = Hoster::TYPE_MOTHERLESS;

            $temp = explode('/', $this->urlToCheck);
            $this->domainSpecificID = $temp[ count($temp)-1 ];

            return TRUE;
        }
        return FALSE;
    }
    public function isNotExtension(array $ext):bool{
        $temp = explode('.', $this->urlToCheck);
        $inArr = in_array($temp[count($temp)-1],$ext);

        return !$inArr;
    }
    /**
     * @return bool
     */
    public function isKnownSite(): bool
    {
        return $this->isKnownSite;
    }

    /**
     * @return bool
     */
    public function isVideoSite(): bool
    {
        return $this->isVideoSite;
    }

    /**
     * @return bool
     */
    public function isImageSite(): bool
    {
        return $this->isImageSite;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @return int
     */
    public function getHoster(): int
    {
        return $this->hoster;
    }

    /**
     * @return null|string
     */
    public function getSubdomain()
    {
        return $this->subdomain;
    }

    /**
     * @return null|string
     */
    public function getDomainSpecificID()
    {
        return $this->domainSpecificID;
    }
    public function setDomainSpecificID(string $newID){
        $this->domainSpecificID = $newID;
    }

}