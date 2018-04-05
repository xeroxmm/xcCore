<?php
namespace AppBundle\API;

class APIInfo {
    private $content;

    function __construct() {
        $this->content = [];
    }
    function setKeyValue(string $key, $value){
        $this->content[$key] = $value;
    }
    function setValue($value){
        $this->content[] = $value;
    }
    function getContent():array {
        return $this->content;
    }
}