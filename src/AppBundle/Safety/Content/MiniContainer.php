<?php
namespace AppBundle\Safety\Content;

use AppBundle\Entity\User;
use AppBundle\Safety\Types\Content;
use Symfony\Component\Routing\Router;

class MiniContainer {
    public $contentID;
    public $title;
    public $description;
    public $user;
    public $type;
    public $url;
    public $tags;
    public $subType;
    public $siteurl;

    function __construct(\stdClass $data, User $user) {
        $this->title       = $data->title ?? NULL;
        $this->description = $data->description ?? NULL;
        $this->type        = $data->type ?? NULL;
        $this->url         = $data->url ?? NULL;
        $this->tags        = $data->tags ?? NULL;
        $this->subType     = $data->subType ?? NULL;
        $this->user        = $data->user ?? $user->getID();
        $this->contentID   = $data->ID ?? NULL;

        $this->siteurl = '';
    }

    public function setUser(int $userID){ $this->user = $userID; }
    public function setID(int $ID){ $this->contentID = $ID; }

    public function hasMinimalContentCredentials():bool{
        return $this->url && ( $this->type == Content::TYPE_IMAGE || $this->type == Content::TYPE_VIDEO );
    }

    public function setSiteURL(Router $r){
        $this->siteurl = 'http://' . $_SERVER["HTTP_HOST"] . $r->generate('apiContentAddURL');
    }
}