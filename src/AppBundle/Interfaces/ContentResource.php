<?php
namespace AppBundle\Interfaces;

interface ContentResource {
    public function getFingerprint():?string;
    public function getType():int;
    public function getColourprint():?string;
    public function getWidth():?int;
    public function getHeight():?int;
    public function getLengthString():?string;
    public function getLength():?int;
    public function getThumbnailLinkURL(string $postfix = ''):?string;
    public function getOriginalLinkURL():?string;
    public function getSourceLinkURL():?string;
    public function getEmbedString():?string;
}