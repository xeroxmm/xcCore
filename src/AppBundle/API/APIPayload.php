<?php

namespace AppBundle\API;

class APIPayload {
    private $content;

    const payloadContentSlug = 'c';

    function __construct() {
        $this->content = new APIContentObj;
    }

    function contentRelated(): APIContentObj {
        return $this->content;
    }
    function asString(string $payload){ $this->content = $payload;}
    function asBool(bool $status){ $this->content = $status; }
    function toArray(): array {
        $a = [];
        //if (!empty($this->content->toArray()))
        //    $a[self::payloadContentSlug] = $this->content->toArray();

        if(is_string($this->content))
            $a[self::payloadContentSlug] = $this->content;
        else if(is_bool($this->content))
            $a[] = $this->content;
        else if(is_array($this->content))
            $a = $this->content;

        return $a;
    }
    function getSimple(){
        return $this->content;
    }
    function asArray(array $array){
        $this->content = $array;
    }
}