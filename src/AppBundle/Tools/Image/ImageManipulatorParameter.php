<?php
namespace AppBundle\Tools\Image;

class ImageManipulatorParameter {
    private $toDimX;
    private $toDimY;
    private $toDimZ;
    private $postFix;

    private $cropTypePortrait;
    private $cropTypeLandscape;

    private $storeToPath;
    private $nameMethod;

    const TYPE_CROP_PORTRAIT_TOP = 1;
    const TYPE_CROP_LANDSCAPE_MIDDLE = 1;

    const TYPE_NAMES_SIMPLE = 1;

    function __construct() {
        $this->cropTypeLandscape = self::TYPE_CROP_LANDSCAPE_MIDDLE;
        $this->cropTypePortrait = self::TYPE_CROP_PORTRAIT_TOP;
        $this->nameMethod = self::TYPE_NAMES_SIMPLE;
        $this->postFix = NULL;
    }

    function setNameMethod(int $method){
        $this->nameMethod = $method;
    }

    function setCropPortrait(int $mode){ $this->cropTypePortrait = $mode; }
    function setLandscapePortrait(int $mode){ $this->cropTypeLandscape = $mode; }

    function setBundled(?int $dimX, ?int $dimY, string $storePath, ?string $postfix = NULL){
        $this->toDimX = $dimX;
        $this->toDimY = $dimY;
        $this->storeToPath = $storePath;
        $this->postFix = $postfix;
    }

    function getPathStore():string { return $this->storeToPath; }

    function getNewWidth():?int { return $this->toDimX; }
    function getNewHeight():?int { return $this->toDimY; }

    function isNoDimChange():bool{ return $this->toDimX === NULL && $this->toDimY === NULL; }
    function isLandscape():bool { return $this->toDimX !== NULL && $this->toDimY !== NULL && $this->toDimX >= $this->toDimY; }
    function isPortrait():bool { return $this->toDimX !== NULL && $this->toDimY !== NULL && $this->toDimX < $this->toDimY; }
    function isKeepRatio():bool { return $this->toDimX === NULL xor $this->toDimY === NULL; }

    function getPostFix():string { return $this->postFix ?? ''; }
}