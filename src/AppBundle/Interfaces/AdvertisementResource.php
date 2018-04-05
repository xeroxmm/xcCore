<?php
namespace AppBundle\Interfaces;

interface AdvertisementResource {
    public function getLink():string;
    public function getHosterName():string;
    public function getTitle():string;
    public function getDescription():string;
    public function getImageURLAbsolute():string;
    public function getImageURLRelative():string;
}