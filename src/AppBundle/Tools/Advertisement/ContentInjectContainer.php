<?php
namespace AppBundle\Tools\Advertisement;

use AppBundle\Entity\Content;
use AppBundle\Entity\ContentCombination;

class ContentInjectContainer {
    /** @var Content[] */
    private $elements;
    private $positions;

    public function __construct(array $queryResults = []) {
        $this->elements = $queryResults;
        $this->positions = [3,5,10,16];
    }
    public function getLength():int{
        return count($this->elements);
    }
    public function isAdPosition(int $pos):bool{
        return in_array($pos, $this->positions);
    }
    public function getLink():string{
        /** @var $list ContentCombination[]*/
        $list = $this->elements[0]->getElementList()[0] ?? FALSE;
        if($list === FALSE)
            return $this->elements[0]->getLink();
        /** @var $list ContentCombination*/
        return $list->getLinkObj()->getExternURL();
    }
    public function getThumb():string{
        return $this->elements[0]->getThumbnailObj()->getThumbnailLinkURL(1);
    }
    public function getTitle():string {
        return $this->elements[0]->getTitle();
    }
    public function delete(){
        unset($this->elements[0]);
        $this->elements = array_values($this->elements);
    }
    public function getDomainURL():string {
        $link = @parse_url($this->getLink());
        if(isset($link['host']))
            return $link['host'];
        else return 'extern 2';
    }
}