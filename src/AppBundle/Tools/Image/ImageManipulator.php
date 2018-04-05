<?php

namespace AppBundle\Tools\Image;

use Imagine\Filter\Basic\Thumbnail;
use Imagine\Gmagick\Imagine;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Point;
use Imagine\Image\PointInterface;

class ImageManipulator {
	/** @var ImageManipulatorParameter[] */
	private $parameter;
	private $filePath;
	private $fileName;
	private $fileSize;
	private $status;
	public  $error;
	private $mimeString;
	/** @var null | BoxInterface */
	private $dimension;
    private $fingerprint;

    private $allowImageOnDouble;

	private $sizePOST;
	private $sizeCURL;

	function __construct() {
		$this->parameter = [];
		$this->dimension = NULL;
		$this->status = FALSE;
		$this->error = '';
		$this->mimeString = NULL;
		$this->fileSize = 1;
		$this->fingerprint = '';
		$this->sizeCURL = 13;
		$this->sizePOST = 10;
		$this->allowImageOnDouble = FALSE;
	}

	function isDoubledImageAllowed():bool {
	    return $this->allowImageOnDouble;
    }

	function getStatus(): bool {
		return $this->status;
	}

	function setFilePathOrigin(string $path) {
		$this->filePath = $path;
	}

	function setFileNameNew(string $name) {
		$this->fileName = $name;
	}

	function getParameterObj() {
		return $this->parameter;
	}

	function buildParameterByConfig(array $config) {
		foreach ($config['images']['parameter'] as $p) {
			$param = new ImageManipulatorParameter();
			$param->setBundled($p['dimX'], $p['dimY'], $p['dir'], $p['postfix']);
			$this->parameter[] = $param;
		}

		if (isset($config['upload']) && isset($config['upload']['size_max'])) {
			$this->sizePOST = (int)$config['upload']['size_max']['post'];
			$this->sizeCURL = (int)$config['upload']['size_max']['curl'];
		}
		if (isset($config['content'])){
		    $this->allowImageOnDouble = (bool)($config['content']['allowDoubled'] ?? 0);
        }
	}

	function getFingerprint(): string {
		if (!empty($this->fingerprint))
			return $this->fingerprint;

		if (empty($this->filePath) || !file_exists($this->filePath))
			return $this->fingerprint = '';

		$w = 4;
		$h = 4;
		$imagine = new \Imagine\Gd\Imagine();
		$size = new Box($w, $h);
		$mode = ImageInterface::FILTER_UNDEFINED;

		$img = $imagine->open($this->filePath);
		unset($imagine);

		$imt = exif_imagetype($this->filePath);
		if ($imt)
			$this->mimeString = ltrim(strtolower(image_type_to_extension($imt)), '.');
		$this->dimension = $img->getSize();

		$lCount = count($img->layers());
		if ($lCount > 1) {
			$mid = (int)($lCount / 2);
			$img = $img->layers()->get(0);
		}
		$resized = $img->resize($size, $mode);
		unset($img);

		$this->fingerprint = '';
		// LOOP through Image
		for ($x = 0; $x < $w; $x++) {
			for ($y = 0; $y < $h; $y++) {
				$c = $resized->getColorAt(new Point($x, $y));
				$this->fingerprint .= substr('0' . dechex($c->getValue(ColorInterface::COLOR_RED)), -2) .
									  substr('0' . dechex($c->getValue(ColorInterface::COLOR_GREEN)), -2) .
									  substr('0' . dechex($c->getValue(ColorInterface::COLOR_BLUE)), -2);
			}
		}
		unset($resized);
		return $this->fingerprint;
	}
	function deleteRawImage(){
		if(file_exists($this->filePath))
			unlink($this->filePath);
	}
	private $isGIF = FALSE;
	public function isGIF():bool {
	    return $this->isGIF;
    }
	function manipulate():bool {
		if (empty($this->filePath) || !file_exists($this->filePath) || empty($this->fileName))
			return $this->setFalse('path not valid: ' . $this->filePath);

		if(filesize($this->filePath) > $this->sizeCURL * 1024 * 1024){
			return $this->setFalse('max filesize: '.$this->sizeCURL.'MB');
		}

		$img = new \Imagine\Imagick\Imagine();
		$img = $img->open($this->filePath);

		if ($this->dimension == NULL || $this->mimeString == NULL) {
			$this->dimension = $this->dimension ?? $img->getSize();
			$imt = exif_imagetype($this->filePath);
			if ($imt)
				$this->mimeString = ltrim(strtolower(image_type_to_extension($imt)), '.');
		}

        if(count($img->layers()) > 1 && strtolower($this->mimeString) == 'gif'){
            $this->isGIF = TRUE;
        }

		$hasAlpha = $this->mimeString != 'png' ? FALSE : TRUE;

		$options = [
			'jpeg_quality' => 90,
			'png_compression_level' => 8,
			'resampling-filter' => ImageInterface::FILTER_UNDEFINED,
			'flatten' => TRUE
		];
		$optionsS = [
			'jpeg_quality' => 90,
			'png_compression_level' => 8,
			'resampling-filter' => ImageInterface::FILTER_UNDEFINED,
			'flatten' => FALSE,
			'animated' => TRUE
		];
		// build Imagick-Image from filePath

        $rPath = '';
        $lPath = '';

		foreach ($this->parameter as $p) {
			/** @var $p ImageManipulatorParameter */
			$path = $p->getPathStore() . DIRECTORY_SEPARATOR . $this->getImageStorePath() . $p->getPostFix();

			if (!file_exists(dirname($path)))
				mkdir(dirname($path), 0775, TRUE);
            /** @var $img ImageInterface */
			$calc = new ImageManipulatorCalculator($this->dimension, $p);
			//echo "Layers: ".$img->layers()->count();
			if (!$p->isNoDimChange()) {
			    // check if it is gif
                if(count($img->layers()) > 1 && $p->isKeepRatio()){
                    // it is a gif, just copy it
                    copy($this->filePath, $path . '.' . strtolower($this->getMimeString()));
                    $lPath = $path . '.' . strtolower($this->getMimeString());
                } else {
                    if(count($img->layers()) > 1 && !$p->isKeepRatio()){
                        $imgT = $img->layers()->get(0);
                        //echo "got image ".$mid.' -> '.$imgT->getSize()->getWidth();
                    } else {
                        $imgT = $img->copy();
                    }
                //  TRY TO GET RIDE OF WATERMARK
                    $imgT->crop(new Point($imgT->getSize()->getWidth()*0.08, $imgT->getSize()->getHeight()*0.08), new Box($imgT->getSize()->getWidth()*0.84,$imgT->getSize()->getHeight()*0.84));
                    // check if ratio of thumb is smaller than ratio of original image
                    if ($calc->getRatioOld() < $calc->getRatioNew()) {
                        // start at top go to max right
                        $original = $imgT->copy();
                        $original->usePalette($imgT->palette());
                        $original->strip();

                        $squareOrig = $original->crop(new Point(0, 0), new Box($imgT->getSize()->getWidth(), $imgT->getSize()->getWidth() / ($calc->getNewWidth() / $calc->getNewHeight())));
                        $thumb      = $squareOrig->resize(new Box($calc->getNewWidth(), $calc->getNewHeight()), ImageInterface::FILTER_CUBIC);
                    } else {
                        $thumb = $imgT->thumbnail(new Box($calc->getNewWidth(), $calc->getNewHeight()), ImageInterface::THUMBNAIL_OUTBOUND, ImageInterface::FILTER_UNDEFINED);

                        if($thumb->getSize()->getHeight() < $calc->getNewHeight()){
                            $nHeight = $calc->getNewHeight();
                            $nWidth = $calc->getNewHeight() * ($thumb->getSize()->getWidth() / $thumb->getSize()->getHeight());
                            $thumb->resize(new Box($nWidth, $nHeight), ImageInterface::FILTER_CUBIC);
                        }
                    }

                    if ($p->isKeepRatio())
                        $thumb->save(
                            $path . '.' . strtolower($this->getMimeString()),
                            $optionsS
                        );
                    else
                        $thumb->save(
                            $path . '.' . strtolower($this->getMimeString()),
                            $options
                        );
                }
			}
			else {
                if(count($img->layers()) > 1){
                    copy($this->filePath, $path . '.' . strtolower($this->getMimeString()));
                    $rPath = $path . '.' . strtolower($this->getMimeString());
                } else {
                    $img->save(
                        $path . '.' . strtolower($this->getMimeString()),
                        $optionsS
                    );
                }
			}
			$this->fileSize = max($this->fileSize, filesize($path . '.' . strtolower($this->getMimeString())));
		}
		$this->deleteRawImage();

		if(!empty($lPath) && !empty($rPath)){
		    unlink($lPath);
		    link($rPath, $lPath);
        }

		return $this->setTrue();
	}

	public function getFileSize(): int {
		return $this->fileSize;
	}

	public function getMimeString() {
		return $this->mimeString;
	}

	public function getImageStorePath(): string {
		$m = 500;
		$i = (int)$this->fileName;
		$a = (int)($i / $m);
		$b = (int)($a / $m);
		$c = (int)($b / $m);
		$d = (int)($c / $m);

		$n = $i - (pow($m, 4) * $d) - (pow($m, 3) * $c) - (pow($m, 2) * $b) - ($m * $a);

		$l1 = base_convert($d, 10, 36);
		$l2 = base_convert($c, 10, 36);
		$l3 = base_convert($b, 10, 36);
		$l4 = base_convert($a, 10, 36);

		return $l1 . DIRECTORY_SEPARATOR . $l2 . DIRECTORY_SEPARATOR . $l3 . DIRECTORY_SEPARATOR . $l4 . DIRECTORY_SEPARATOR . $n;
	}

	public function getDimension(): BoxInterface {
		return $this->dimension ?? new Box(0, 0);
	}

	private function setTrue() {
		$this->status = TRUE;
		return TRUE;
	}

	private function setFalse($string = '') {
		$this->error = $string;
		$this->status = FALSE;
		return FALSE;
	}
}