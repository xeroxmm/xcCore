<?php
namespace AppBundle\Interfaces;

interface ContentEntityMini {
    public function getID():int;
    public function getTitle():string;
    public function getLink():string;
    public function getThumbnailURL():string;
    public function toIndexedArray():array;
}