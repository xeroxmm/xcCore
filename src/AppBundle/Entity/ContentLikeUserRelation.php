<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="content_like_user_relation")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 * @UniqueEntity(
 *     fields={"uID", "cID"},
 *     errorPath="cID",
 *     message="This content ID is already in use on that user."
 * )
 */
class ContentLikeUserRelation {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="contentLikeUserRelationObj")
     * @ORM\JoinColumn(name="uID", referencedColumnName="ID", onDelete="CASCADE", nullable=false)
     */
    protected $userObj;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Content", inversedBy="contentLikeUserRelationObj")
     * @ORM\JoinColumn(name="cID", referencedColumnName="ID", onDelete="CASCADE", nullable=false)
     */
    protected $contentObj;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=3)
     */
    protected $maxLikeValue;
    /**
     * @ORM\Column(type="decimal", precision=5, scale=3)
     */
    protected $likeValueTime;
    /**
     * @ORM\Column(type="integer")
     */
    protected $likeValueLike;
    /**
     * @ORM\Column(type="integer")
     */
    protected $likeValueLove;
    /**
     * @ORM\Column(type="integer")
     */
    protected $timeLastActive;

    public function __construct() {
        $this->maxLikeValue = 0;
        $this->likeValueTime = 0;
        $this->likeValueLike = 0;
        $this->likeValueLove = 0;
        $this->timeLastActive = time();
    }

    /**
     * @return mixed
     */
    public function getID() {
        return $this->ID;
    }

    /**
     * @param mixed $ID
     */
    public function setID($ID) {
        $this->ID = $ID;
    }

    /**
     * @return mixed
     */
    public function getUserObj() {
        return $this->userObj;
    }

    /**
     * @param mixed $userObj
     */
    public function setUserObj($userObj) {
        $this->userObj = $userObj;
    }

    /**
     * @return mixed
     */
    public function getContentObj() {
        return $this->contentObj;
    }

    /**
     * @param mixed $contentObj
     */
    public function setContentObj($contentObj) {
        $this->contentObj = $contentObj;
    }

    /**
     * @return mixed
     */
    public function getMaxLikeValue() {
        return $this->maxLikeValue;
    }

    /**
     * @param mixed $maxLikeValue
     */
    public function setMaxLikeValue($maxLikeValue) {
        $this->maxLikeValue = $maxLikeValue;
    }

    /**
     * @return mixed
     */
    public function getLikeValueTime() {
        return $this->likeValueTime;
    }

    /**
     * @param mixed $likeValueTime
     */
    public function setLikeValueTime($likeValueTime) {
        $this->likeValueTime = $likeValueTime;
    }

    /**
     * @return int
     */
    public function getLikeValueLike() {
        return $this->likeValueLike;
    }

    /**
     * @param bool $likeValueLike
     */
    public function setLikeValueLike(?bool $likeValueLike) {
        $this->likeValueLike = $likeValueLike === NULL ? 0 : ($likeValueLike ? 2 : -1);
    }
    public function setMaxLikeValueByLike(bool $liked){
        $this->maxLikeValue = $liked ? 2 : -1;
    }
    public function setMaxLikeValueByLove(){
        $this->maxLikeValue = 3;
    }
    /**
     * @return mixed
     */
    public function getLikeValueLove() {
        return $this->likeValueLove;
    }

    /**
     * @param mixed $likeValueLove
     */
    public function setLikeValueLove(bool $likeValueLove) {
        $this->likeValueLove = $likeValueLove ? 3 : 0;
    }

    /**
     * @return mixed
     */
    public function getTimeLastActive() {
        return $this->timeLastActive;
    }

    /**
     * @param mixed $timeLastActive
     */
    public function setTimeLastActive($timeLastActive) {
        $this->timeLastActive = $timeLastActive;
    }
}