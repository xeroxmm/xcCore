<?php
namespace AppBundle\Template;

use AppBundle\Interfaces\ContentResource;
use Doctrine\Common\Collections\ArrayCollection;

class ContentContainer {
    private $containerArray;
    private $init;

    function __construct(){
        $this->init = TRUE;
        $this->containerArray = new ArrayCollection();
    }

    function addContent(ContentResource $content) {$this->containerArray->add( $content ); }

    /** @return ContentResource[] */
    function getContentArray():array { return $this->containerArray->toArray(); }
}