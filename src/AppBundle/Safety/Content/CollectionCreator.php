<?php
namespace AppBundle\Safety\Content;

use AppBundle\Crawler\Crawler;
use AppBundle\Entity\ContentMeta;
use AppBundle\Entity\Image;
use AppBundle\Entity\User;
use AppBundle\Safety\Types\Content;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class CollectionCreator extends ItemCreator {
    function __construct(EntityManagerInterface $em) {
        parent::__construct($em);
        $this->type = Content::TYPE_COLLECTION;
    }
    public function storeInDB(User $user, DataParser $dataParser, $notUsed = NULL, $notUsed2 = NULL):bool {
        // store content meta in DB
        if(!$this->createContentMeta( $dataParser ))
            return ($this->error = 'cnt meta') == 1;

        if(!$this->buildParameterClass( $dataParser, $user ))
            return ($this->error = 'cnt prm') == 1;

        // store content info in db
        // $dataParser->setTitle($cwl->getSubCrawl()->getTitle());
        $this->buildContentInfo( $dataParser );

        // store Tags in DB
        $this->buildTags( $dataParser );

        // check if status OKAY
        return $this->status = TRUE;
    }
    public function addImageAsThumbnail(Image $image){
        $db = $this->em;

        $this->contentObj->setThumbnailObj( $image );

        $db->persist( $this->contentObj );
        $db->flush();
    }
    private function createContentMeta( DataParser $dataParser ){
        try {
            $m = new ContentMeta();
            $m->setContentObj( $this->contentObj );
            $m->setMediaIn( 1 );

            $this->em->persist($m);
            $this->em->flush();
        } catch(Exception $e){
            return FALSE;
        }
        return TRUE;
    }
}