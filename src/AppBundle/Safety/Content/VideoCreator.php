<?php

namespace AppBundle\Safety\Content;

use AppBundle\Crawler\Crawler;
use AppBundle\Entity\ContentCombination;
use AppBundle\Entity\ContentMeta;
use AppBundle\Entity\Image;
use AppBundle\Entity\User;
use AppBundle\Entity\Video;
use AppBundle\Safety\Types\Content;
use AppBundle\Safety\Web\URLParser;
use AppBundle\Safety\Web\WebsiteInfoGfycat;
use AppBundle\Tools\Image\ImageManipulator;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class VideoCreator extends ItemCreator {
    /** @var $imageObj Image */
    private $imageObj;
    /** @var $imageOrigin ImageManipulator */
    private $imageOrigin;
    /** @var $videoObj Video */
    private $videoObj;

    private $newTitle       = NULL;
    private $newDescription = NULL;

    function __construct(EntityManagerInterface $em) {
        parent::__construct($em);
        $this->type = Content::TYPE_VIDEO;
    }

    public function storeInDB(User $user, DataParser $dataParser, Crawler $cwl, array $param): bool {
        // do raw Image processing
        if($cwl->getSubCrawl()->hasOverwrittenType())
            $dataParser->setType( $cwl->getSubCrawl()->getOverwrittenType() );

        $img = $this->processRawImageEntity($cwl, $param);

        /*
	    // save image thumbnail
		$this->imageOrigin = $this->createImageOnDisk($cwl, $param);
		if(!$this->imageOrigin->getStatus())
			return ($this->error = 'img to disk') == 1;

		// store Image Raw in DB
		if(!$this->createImageObj( $dataParser ))
			return ($this->error = 'img to db') == 1;
        */
        // store Video Raw in DB
        if (!$this->createVideoObj($dataParser, $cwl))
            return FALSE;

        // store Video in combination Table at position 1
        if (!$this->createVideoInCombination(1))
            return FALSE;

        // store content meta in DB
        if (!$this->createContentMeta($dataParser))
            return ($this->error = 'cnt meta') == 1;

        if (!$this->buildParameterClass($dataParser, $user))
            return ($this->error = 'cnt prm') == 1;

        // store content info in db
        $dataParser->setTitle($dataParser->getTitle() ?? $cwl->getSubCrawl()->getTitle());
        $this->buildContentInfo($dataParser);

        // store Tags in DB
        $this->buildTags($dataParser->addTags($cwl->getSubCrawl()->getTags()));

        // check if status OKAY
        $this->status = TRUE;

        // return status
        return $this->status;
    }

    private function createImageObj(DataParser $dp) {
        try {
            $this->imageObj = new Image();
            $this->imageObj->setMime($this->imageOrigin->getMimeString());
            $this->imageObj->setDimX($this->imageOrigin->getDimension()[0]);
            $this->imageObj->setDimY($this->imageOrigin->getDimension()[1]);
            $this->imageObj->setURL('nope');
            // SRC HOSTER etc ->  $dp->getUrl() );

            if (!empty($this->newTitle))
                $this->imageObj->setFingerprint($this->newTitle);

            if (!empty($this->newDescription))
                $this->imageObj->setColourprint($this->newDescription);

            $this->em->persist($this->imageObj);
        } catch (Exception $e) {
            return FALSE;
        }

        return TRUE;
    }

    private function createVideoObj(DataParser $dp, Crawler $cwl) {
        try {
            $url = new URLParser($dp->getUrl());

            if($cwl->getSubCrawl()->isGfyCat ?? FALSE) {
                /** @var $v WebsiteInfoGfycat*/
                $v = $cwl->getSubCrawl();
                $v->setRealID($url);
            }

            $this->videoObj = new Video();
            $this->videoObj->setEmbedURL($url->getDomainSpecificID());
            $this->videoObj->setHoster($url->getHoster());
            $this->videoObj->setLength($cwl->getSubCrawl()->getLength());
            $this->videoObj->setURL($dp->getUrl());
            $this->videoObj->setImageRaw($this->rawImageEntity);

            $title = ''; //echo $dp->getTitle() . ' ___ ' . $cwl->getSubCrawl()->getTitle() . ' ___ ';
            if (!empty($dp->getTitle()))
                $title = $dp->getTitle();
            else if (!empty($cwl->getSubCrawl()->getTitle()))
                $title = $cwl->getSubCrawl()->getTitle();

            if (!empty($title))
                $this->videoObj->setTitle($title);

            if (!empty($cwl->getSubCrawl()->getDescription()))
                $this->videoObj->setDescription($cwl->getSubCrawl()->getDescription());

            $this->em->persist($this->videoObj);
            $this->em->flush();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return FALSE;
        }

        return TRUE;
    }

    private function createVideoInCombination(int $pos) {
        try {
            $cc = new ContentCombination();
            $cc->setContentObj($this->contentObj);
            $cc->setVideoObj($this->videoObj);
            $cc->setPosition($pos);

            $this->em->persist($cc);

            $this->em->flush();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return FALSE;
        }
        return TRUE;
    }

    private function createContentMeta(DataParser $dp) {
        try {
            $url = new URLParser($dp->getUrl());

            $m = new ContentMeta();
            $m->setContentObj($this->contentObj);
            $m->setSrcURL($dp->getUrl());
            $m->setHoster($url->getHoster());

            $this->em->persist($m);
            $this->em->flush();
        } catch (Exception $e) {
            return FALSE;
        }
        return TRUE;
    }

    public function getRawVideoEntity(): Video {
        return $this->videoObj;
    }
}