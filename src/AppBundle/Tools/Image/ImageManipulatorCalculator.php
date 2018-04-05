<?php

namespace AppBundle\Tools\Image;

use Imagine\Image\BoxInterface;

class ImageManipulatorCalculator {
    private $img;
    private $prm;

    private $ratioNew;
    private $ratioOld;

    private $cropWidth;
    private $cropHeight;
    private $cropOffsetX;
    private $cropOffsetY;

    function __construct(BoxInterface $img, ImageManipulatorParameter $param){
        $this->img = $img;
        $this->prm = $param;

        $this->ratioNew = ($this->prm->getNewWidth() !== NULL && $this->prm->getNewHeight() !== NULL) ? $this->prm->getNewWidth() / $this->prm->getNewHeight() : $this->img->getWidth() / $this->img->getHeight();
        $this->ratioOld = $this->img->getWidth() / $this->img->getHeight();
    }
    function getRatioNew(){
        return $this->ratioNew;
    }
    function getRatioOld(){
        return $this->ratioOld;
    }
    function getCropWidth():int {
        if($this->cropWidth !== NULL)
            return $this->cropWidth;

        $int = $this->img->getWidth();

        if($this->ratioNew < $this->ratioOld){
            $int = (int)($this->img->getHeight() * $this->ratioNew);
        }

        return $this->cropWidth = $int;
    }
    function getCropHeight():int {
        if($this->cropHeight !== NULL)
            return $this->cropHeight;

        $int = $this->img->getHeight();

        if($this->ratioNew > $this->ratioOld){
            $int = (int)($this->img->getWidth() / $this->ratioNew);
        }

        return $this->cropHeight = $int;
    }
    function getCropOffsetX():int {
        if($this->cropOffsetX !== NULL)
            return $this->cropOffsetX;

        $int = 0;

        if($this->ratioOld > $this->ratioNew){
            $int = (int)(($this->img->getWidth() - $this->getCropWidth()) / 2);
        }

        return $this->cropOffsetX = $int;
    }
    function getCropOffsetY():int {
        if($this->cropOffsetY !== NULL)
            return $this->cropOffsetY;

        $int = 0;

        /*
        if($this->ratioOld < $this->ratioNew){
            $int = (int)(($this->img->getImageHeight() - $this->getCropHeight()) / 2);
        }
        */
        return $this->cropOffsetY = $int;
    }
    function getNewHeight():int {
        if ($this->prm->getNewHeight() == NULL && $this->prm->getNewWidth() == NULL)
            return $this->img->getHeight();

        if ($this->prm->isKeepRatio()){
            // Calculate height if height is null
            if($this->prm->getNewHeight() == NULL){
                $int = $this->img->getHeight();

                if($this->ratioOld > 0){
                    $int = min((int)($this->prm->getNewWidth() / $this->ratioOld), $this->img->getHeight());
                }

                return $int;
            } else {
                return $this->prm->getNewHeight();
            }
        } else {
            return $this->prm->getNewHeight();
        }
    }
    function getNewWidth():int {
        if ($this->prm->getNewHeight() == NULL && $this->prm->getNewWidth() == NULL)
            return $this->img->getWidth();

        if ($this->prm->isKeepRatio()){
            // Calculate height if height is null
            if($this->prm->getNewWidth() == NULL){
                $int = min((int)($this->prm->getNewHeight() * $this->ratioOld), $this->img->getWidth());

                return $int;
            } else {
                return $this->prm->getNewWidth();
            }
        } else {
            return $this->prm->getNewWidth();
        }
    }
}