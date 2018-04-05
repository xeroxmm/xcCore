<?php
namespace AppBundle\Tools\Advertisement;

use AppBundle\Interfaces\AdvertisementResource;

class Plug implements AdvertisementResource {
    private $link;
    private $hosterName;
    private $title;
    private $description;
    private $imageURL;
    private $serverAddress;

    public function __construct(string $link, string $title, string $description, string $imageURL, string $serverA) {
        $this->link = $link;
        $this->title = $title;
        $this->description = $description;
        $this->imageURL = $imageURL;
        $this->serverAddress = $serverA;

        $temp = explode("//", $link, 2);
        $temp = $temp[1] ?? $link;
        $temp = explode('/', $temp, 2);
        $temp = $temp[0] ?? 'n/a';
        $temp = explode('.', $temp);
        if(count($temp)>1){
            $this->hosterName = $temp[count($temp)-2].'.'.$temp[count($temp)-1];
        } else
            $this->hosterName = 'n/a';
    }

    public function getLink(): string {
        return $this->link;
    }

    public function getHosterName(): string {
        return $this->hosterName;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getImageURLAbsolute(): string {
        return $this->serverAddress . $this->imageURL;
    }

    public function getImageURLRelative(): string {
        return $this->imageURL;
    }
}