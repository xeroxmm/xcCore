<?php

namespace AppBundle\API;

class APIContentObj {
    private $data;

    function __construct() {
        $this->data = [
            'title' => NULL,
            'ID' => NULL,
            'cID' => NULL,
            'slug' => NULL,
            'src' => NULL
        ];
    }

    function setTitle(string $title) {
        $this->data['title'] = $title;
    }

    function setID(int $ID) {
        $this->data['ID'] = $ID;
    }

    function setContentID(string $cID) {
        $this->data['cID'] = $cID;
    }

    function setSlug(string $slug) {
        $this->data['slug'] = $slug;
    }

    function setSource(string $src){ $this->data['src'] = $src; }

    function toArray(): array {
        foreach ($this->data as $k => $v)
            if (empty($v))
                unset($this->data[$k]);

        return $this->data;
    }
}