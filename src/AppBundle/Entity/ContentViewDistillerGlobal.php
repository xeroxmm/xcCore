<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="content_distiller_view_global")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class ContentViewDistillerGlobal {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Content", inversedBy="contentViewDistillerGlobal")
     * @ORM\JoinColumn(name="cID", referencedColumnName="ID", onDelete="CASCADE", nullable=false, unique=true)
     */
    protected $contentObj;
    /**
     * @ORM\Column(type="integer")
     */
    protected $countTimeframe_0;
    /**
     * @ORM\Column(type="integer")
     */
    protected $countTimeframe_1;
    /**
     * @ORM\Column(type="integer")
     */
    protected $countTimeframe_2;
    /**
     * @ORM\Column(type="integer")
     */
    protected $countTimeframe_3;
    /**
     * @ORM\Column(type="integer")
     */
    protected $countTimeframe_4;
    /**
     * @ORM\Column(type="integer")
     */
    protected $countTimeframe_5;
    /**
     * @ORM\Column(type="integer")
     */
    protected $countTimeframe_6;
    /**
     * @ORM\Column(type="integer")
     */
    protected $countTimeframe_7;
    /**
     * @ORM\Column(type="integer")
     */
    protected $countTimeframe_8;

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
    public function getCountTimeframe0() {
        return $this->countTimeframe_0;
    }

    /**
     * @param mixed $countTimeframe_0
     */
    public function setCountTimeframe0($countTimeframe_0) {
        $this->countTimeframe_0 = $countTimeframe_0;
    }

    /**
     * @return mixed
     */
    public function getCountTimeframe1() {
        return $this->countTimeframe_1;
    }

    /**
     * @param mixed $countTimeframe_1
     */
    public function setCountTimeframe1($countTimeframe_1) {
        $this->countTimeframe_1 = $countTimeframe_1;
    }

    /**
     * @return mixed
     */
    public function getCountTimeframe2() {
        return $this->countTimeframe_2;
    }

    /**
     * @param mixed $countTimeframe_2
     */
    public function setCountTimeframe2($countTimeframe_2) {
        $this->countTimeframe_2 = $countTimeframe_2;
    }

    /**
     * @return mixed
     */
    public function getCountTimeframe3() {
        return $this->countTimeframe_3;
    }

    /**
     * @param mixed $countTimeframe_3
     */
    public function setCountTimeframe3($countTimeframe_3) {
        $this->countTimeframe_3 = $countTimeframe_3;
    }

    /**
     * @return mixed
     */
    public function getCountTimeframe4() {
        return $this->countTimeframe_4;
    }

    /**
     * @param mixed $countTimeframe_4
     */
    public function setCountTimeframe4($countTimeframe_4) {
        $this->countTimeframe_4 = $countTimeframe_4;
    }

    /**
     * @return mixed
     */
    public function getCountTimeframe5() {
        return $this->countTimeframe_5;
    }

    /**
     * @param mixed $countTimeframe_5
     */
    public function setCountTimeframe5($countTimeframe_5) {
        $this->countTimeframe_5 = $countTimeframe_5;
    }

    /**
     * @return mixed
     */
    public function getCountTimeframe6() {
        return $this->countTimeframe_6;
    }

    /**
     * @param mixed $countTimeframe_6
     */
    public function setCountTimeframe6($countTimeframe_6) {
        $this->countTimeframe_6 = $countTimeframe_6;
    }

    /**
     * @return mixed
     */
    public function getCountTimeframe7() {
        return $this->countTimeframe_7;
    }

    /**
     * @param mixed $countTimeframe_7
     */
    public function setCountTimeframe7($countTimeframe_7) {
        $this->countTimeframe_7 = $countTimeframe_7;
    }

    /**
     * @return mixed
     */
    public function getCountTimeframe8() {
        return $this->countTimeframe_8;
    }

    /**
     * @param mixed $countTimeframe_8
     */
    public function setCountTimeframe8($countTimeframe_8) {
        $this->countTimeframe_8 = $countTimeframe_8;
    }

    /**
     * @return mixed
     */
    public function getCountTimeframe9() {
        return $this->countTimeframe_9;
    }

    /**
     * @param mixed $countTimeframe_9
     */
    public function setCountTimeframe9($countTimeframe_9) {
        $this->countTimeframe_9 = $countTimeframe_9;
    }
    /**
     * @ORM\Column(type="integer")
     */
    protected $countTimeframe_9;

    public function __construct() {
        $this->countTimeframe_0 = 0;
        $this->countTimeframe_1 = 0;
        $this->countTimeframe_2 = 0;
        $this->countTimeframe_3 = 0;
        $this->countTimeframe_4 = 0;
        $this->countTimeframe_5 = 0;
        $this->countTimeframe_6 = 0;
        $this->countTimeframe_7 = 0;
        $this->countTimeframe_8 = 0;
        $this->countTimeframe_9 = 0;
    }
}