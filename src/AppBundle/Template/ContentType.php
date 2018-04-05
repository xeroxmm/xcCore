<?php
namespace AppBundle\Template;

class ContentType {
    private $typeInt;
    private $typeString;

    const TYPE_IMAGE = 1;
    const TYPE_VIDEO = 2;
    const TYPE_COLLECTION = 3;

    function __construct(int $type){
        $this->typeInt = $type;
        $this->buildTypeString();
    }

    private function buildTypeString(){
        $type = 'error';
        switch($this->typeInt){
            case 1:
                $type = 'image';
                break;
            case 2:
                $type = 'video';
                break;
            case 3:
                $type = 'collection';
                break;
        }
        $this->typeString = $type;
    }

    public function getTypeAsString():string{ return $this->typeString; }
    public function getType():int { return $this->typeInt; }
}