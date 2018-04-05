<?php
namespace AppBundle\Safety\Content;

use AppBundle\Crawler\Crawler;
use AppBundle\Entity\ContentCombination;
use AppBundle\Entity\ContentMeta;
use AppBundle\Entity\Image;
use AppBundle\Entity\Tag;
use AppBundle\Entity\User;
use AppBundle\Safety\Types\Content;
use AppBundle\Tools\Image\ImageManipulator;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\VarDumper\Cloner\Data;

class ImageCreator extends ItemCreator {
    /** @var $imageObj Image */
    private $imageObj;
    /** @var $imageOrigin ImageManipulator */
    private $imageOrigin;

    private $newTitle = NULL;
    private $newDescription = NULL;

    function __construct(EntityManagerInterface $em, Content $content = NULL) {
        parent::__construct($em, $content);
        // NOW WE HAVE A NEW CONTENT OB IN PARENT CLASS WE CAN DEAL WITH

        $this->type = Content::TYPE_IMAGE;
    }

    public function storeInDBOnlyRaw(User $user, DataParser $dataParser, Crawler $cwl, array $param):bool {
		// create content Entry -> is global ==> EXTENDED CLASS!!!

		// load image info
		$this->imageOrigin = $ii = $this->createImageOnDisk($cwl,$param);
		if(!$ii->getStatus())
			return FALSE;

		// store image info in DB
		if(!$this->createImageObj( $dataParser ))
			return FALSE;

		$this->status = TRUE;

		return $this->status;
	}

    public function storeInDBOnlyImageInformation(Crawler $cwl, array $param, ?Image $image, bool $onlyPath = FALSE):bool{
        if(!$onlyPath)
            $img = $this->processRawImageEntity($cwl, $param);
        else
            $img = $this->processRawImageEntityStoreOnlyNewPath($cwl, $param, $image);

        if(!$img->getStatus()){
            echo $img->error;
            return FALSE;
        }

        return TRUE;
    }
    public function storeInDB(User $user, DataParser $dataParser, Crawler $cwl, array $param):bool{
        // create content Entry -> is global ==> EXTENDED CLASS!!!

        // do raw Image processing
        $img = $this->processRawImageEntity($cwl, $param);

        if(!$img->getStatus()){
            echo $img->error;
            return FALSE;
        }

        if($this->lastTImg !== FALSE && ($this->lastTImg->getTime() >= time() - 1200 || !$img->isDoubledImageAllowed()))
            return FALSE;

        // store image in combination Table at position 1
		if(!$this->createImageInCombination( 1 ))
			return FALSE;

        // store content meta in DB
        if(!$this->createContentMeta( $img , $dataParser))
            return FALSE;

        // store content parameter in DB
        if(!$this->buildParameterClass( $dataParser, $user, $img ))
            return FALSE;

        // store content info in db
        $this->buildContentInfo( $dataParser );

        // store Tags in DB
        $this->buildTags( $dataParser );

        // check if status OKAY
        $this->status = TRUE;

        // return status

        return $this->status;
    }
    private function createImageObj(DataParser $dp){
        try {
            $this->imageObj = new Image();
            $this->imageObj->setMime($this->imageOrigin->getMimeString());
            $this->imageObj->setDimX($this->imageOrigin->getDimension()[0]);
            $this->imageObj->setDimY($this->imageOrigin->getDimension()[1]);
            $this->imageObj->setURL( $dp->getUrl() );

            if (!empty($this->newTitle))
                $this->imageObj->setFingerprint($this->newTitle);

            if (!empty($this->newDescription))
                $this->imageObj->setColourprint($this->newDescription);

            $this->rawImageEntity = &$this->imageObj;
            $this->em->persist($this->rawImageEntity);
            $this->em->flush();
        } catch(Exception $e){
            return FALSE;
        }

        return TRUE;
    }
    private function createImageInCombination(int $pos){
		try {
			$cc = new ContentCombination();
			$cc->setContentObj($this->contentObj);
			$cc->setImageObj($this->rawImageEntity);
			$cc->setPosition( $pos );

			$this->em->persist($cc);

			$this->em->flush();
		} catch (Exception $e){
			return FALSE;
		}
		return TRUE;
	}
    private function createContentMeta(ImageManipulator $im, DataParser $dp){
        try {
            $m = new ContentMeta();
            $m->setContentObj( $this->contentObj );
            //$m->setMime( $im->getMimeString() );
            $m->setSrcURL( $dp->getUrl() );

            if(!empty($dp->getSource()))
                $m->setSubDomain( $dp->getSource() );
            //$m->setFileSize( $im->getFileSize() );

            $this->em->persist($m);
            $this->em->flush();
        } catch (Exception $e){
            // print_r($e);
            return FALSE;
        }

        return TRUE;
    }
}