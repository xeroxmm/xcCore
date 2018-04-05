<?php

namespace AppBundle\Safety\Content;

use AppBundle\Crawler\Crawler;
use AppBundle\Entity\ContentCombination;
use AppBundle\Entity\ContentMeta;
use AppBundle\Entity\ContentParameter;
use AppBundle\Entity\Image;
use AppBundle\Entity\Link;
use AppBundle\Entity\User;
use AppBundle\Safety\Types\Content;
use AppBundle\Safety\Web\URLParser;
use AppBundle\Tools\Image\ImageManipulator;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class LinkCreator extends ItemCreator {
    /** @var $imageObj Image */
    protected $imageObj;
    /** @var $imageOrigin ImageManipulator */
    protected $imageOrigin;
    /** @var $linkObj Link */
    protected $linkObj;

    protected $newTitle       = NULL;
    protected $newDescription = NULL;

    function __construct(EntityManagerInterface $em) {
        parent::__construct($em);
        $this->type = Content::TYPE_LINK;
    }

    public function storeInDB(User $user, DataParser $dataParser, Crawler $cwl, array $param): bool {
        // do raw Image processing
        $img = $this->processRawImageEntity($cwl, $param);

        // create LINK in Database
        if (!$this->createLinkObj($dataParser))
            return FALSE;

        // store Video in combination Table at position 1
        if (!$this->createLinkInCombination(1))
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

    private function createLinkObj(DataParser $dp) {
        try {
            $this->linkObj = new Link();
            $this->linkObj->setTitle($dp->getTitle());
            $this->linkObj->setDescription($dp->getDescription());
            $this->linkObj->setExternURL($dp->getForcedLink());

            $this->em->persist($this->linkObj);
            $this->em->flush();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return FALSE;
        }

        return TRUE;
    }
    private function createLinkInCombination(int $pos) {
        try {
            $cc = new ContentCombination();
            $cc->setContentObj($this->contentObj);
            $cc->setLinkObj($this->linkObj);
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
    protected function buildParameterClass(DataParser $dp, User $user, ImageManipulator $img = NULL): bool {
        try {
            $cp = new ContentParameter();
            $cp->setContentObj($this->contentObj);
            $cp->setType($dp->getType());
            $cp->setUserObj($user);
            $cp->setIsBulk( $dp->getBulk() );
            $cp->setTimestamp(time());

            if($img !== NULL && $img->isGIF())
                $cp->setIsGif( TRUE );

            $this->contentParameterObj = $cp;

            $this->em->persist($this->contentParameterObj);
            $this->em->flush();
        } catch (Exception $e) {
            return FALSE;
        }
        return TRUE;
    }
}