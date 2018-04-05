<?php
namespace AppBundle\Template;

class VoteObject {
    private $likeScore;
    private $loveScore;

    public function __construct() {
        $this->likeScore = 0;
        $this->loveScore = 0;
    }
    public function setLikeAndLoveScore(int $like, int $love){
        $this->likeScore = $like;
        $this->loveScore = $love;
    }
    public function isLiked():bool{
        return $this->likeScore > 0;
    }
    public function isDisLiked():bool{
        return $this->likeScore < 0;
    }
    public function isLoved():bool{
        return $this->loveScore > 0;
    }
}