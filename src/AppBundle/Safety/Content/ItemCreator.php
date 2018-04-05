<?php

namespace AppBundle\Safety\Content;

use AppBundle\Crawler\Crawler;
use AppBundle\Entity\Content;
use AppBundle\Entity\ContentCombination;
use AppBundle\Entity\ContentParameter;
use AppBundle\Entity\Image;
use AppBundle\Entity\Tag;
use AppBundle\Entity\User;
use AppBundle\Tools\Image\ImageManipulator;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\DatabaseObjectExistsException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

class ItemCreator {
    protected $contentObj;
    protected $contentParameterObj;
    protected $status;
    protected $type = 0;

    protected $em;

    /** @var null | Image */
    protected $rawImageEntity = NULL;
    protected $error          = '';
    protected $entityID       = 0;

    private $girlsSnapChat = [];
    private $girlsPorn = [];
    private $csvPath = '';

    function __construct(EntityManagerInterface $em, Content $content = NULL) {
        $this->em     = $em;
        $this->status = FALSE;
        $this->csvPath = dirname(__FILE__,5).DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR;

        if ($content === NULL) {
            $this->contentObj = new Content();

            $this->em->persist($this->contentObj);
            $this->em->flush();
        } else {
            $this->contentObj = $content;
        }
    }

    public function getError(): string {
        return $this->error;
    }

    protected function buildContentInfo(DataParser $dp): bool {
        try {
            $this->contentObj->setTitle($dp->getTitle());
            $this->contentObj->setDescription($dp->getDescription());
            $this->contentObj->setLink($this->getLinkSlugByTitle($dp->getTitle()));

            $this->em->persist($this->contentObj);
            $this->em->flush();

            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    protected function getLinkSlugByTitle(string $string): string {
        return ContentHarmonize::getSlugOfString($string);
    }

    protected function buildParameterClass(DataParser $dp, User $user, ImageManipulator $img = NULL): bool {
        try {
            $cp = new ContentParameter();
            $cp->setContentObj($this->contentObj);
            $cp->setType($dp->getType());
            $cp->setUserObj($user);
            $cp->setIsBulk($dp->getBulk());
            $cp->setTimestamp(time());

            if ($img !== NULL && $img->isGIF())
                $cp->setIsGif(TRUE);

            $this->contentParameterObj = $cp;

            if(empty($dp->getTitle()) || strlen(trim($dp->getTitle())) < 2)
                $cp->setIsPrivate( TRUE );

            $this->em->persist($this->contentParameterObj);
            $this->em->flush();
        } catch (Exception $e) {
            return FALSE;
        }
        return TRUE;
    }

    public function rewind(): bool {
        $ID = $this->contentObj->getID();

        /* TODO DELETE ALL occurrences of this contentID */

        return FALSE;
    }

    /** @var bool | Image */
    protected $lastTImg = FALSE;

    protected function processRawImageEntityStoreOnlyNewPath(Crawler $crw, array $parameter, Image $image): ImageManipulator {
        // create fingerprint of image
        $img = new ImageManipulator();
        $img->setFilePathOrigin($crw->getFilePath());
        $img->buildParameterByConfig($parameter);
        try {
            $img->getFingerprint();
        } catch (\Exception $exception) {
            echo $crw->getSubCrawl()->getThumbnailURL();
            die();
        }
        $this->rawImageEntity = $image;

        // check fingerprint in DB
        $img->setFileNameNew($this->rawImageEntity->getID());
        $img->manipulate();

        // SAVE new IMAGE OBJ URL
        $this->rawImageEntity->setURL($img->getImageStorePath());
        $this->rawImageEntity->setMime( $img->getMimeString() );
        $this->rawImageEntity->setFingerprint( $img->getFingerprint() );
        $this->rawImageEntity->setDimX( $img->getDimension()->getWidth() );
        $this->rawImageEntity->setDimY( $img->getDimension()->getHeight() );
        $this->rawImageEntity->setFilesize( $img->getFileSize() );

        $this->em->persist($this->rawImageEntity);
        $this->em->flush();

        $this->setThumbOnContent();

        // return STATUS
        return $img;
    }

    protected function processRawImageEntity(Crawler $crw, array $parameter): ImageManipulator {
        // create fingerprint of image
        $img = new ImageManipulator();
        $img->setFilePathOrigin($crw->getFilePath());
        $img->buildParameterByConfig($parameter);
        try {
            $img->getFingerprint();
        } catch (\Exception $exception) {
            echo $crw->getSubCrawl()->getThumbnailURL();
            die();
        }
        $tIMG     = NULL;
        $newImage = FALSE;
        // check fingerprint in DB
        $em = $this->em;
        $em->transactional(
            function (EntityManager $em) use ($img, &$tIMG, &$newImage) {
                $tIMG = $em->createQueryBuilder()
                           ->select('i')
                           ->from('AppBundle:Image', 'i')
                           ->where('i.fingerprint = :prt')
                           ->setParameter(':prt', $img->getFingerprint())
                           ->getQuery()->setLockMode(LockMode::PESSIMISTIC_WRITE)->getOneOrNullResult();

                /** @var $t Image */
                if (!$tIMG) {
                    $tIMG = new Image();
                    $tIMG->setFingerprint($img->getFingerprint());
                    $tIMG->setDimX($img->getDimension()->getWidth());
                    $tIMG->setDimY($img->getDimension()->getHeight());
                    $tIMG->setURL('_');
                    $tIMG->setmime($img->getMimeString());
                    $em->persist($tIMG);
                    $newImage = TRUE;

                    $this->lastTImg = FALSE;
                } else
                    $this->lastTImg = $tIMG;
            }
        );
        // create RawImageEntity()
        $this->rawImageEntity = $tIMG;

        // check if new Image has to be cropped
        if ($newImage && $this->rawImageEntity) {
            $img->setFileNameNew($this->rawImageEntity->getID());
            $img->manipulate();

            // SAVE new IMAGE OBJ URL
            $this->rawImageEntity->setURL($img->getImageStorePath());
            $this->rawImageEntity->setMime( $img->getMimeString() );
            $this->rawImageEntity->setFingerprint( $img->getFingerprint() );
            $this->rawImageEntity->setDimX( $img->getDimension()->getWidth() );
            $this->rawImageEntity->setDimY( $img->getDimension()->getHeight() );
            $this->rawImageEntity->setFilesize( $img->getFileSize() );

            $em->persist($this->rawImageEntity);
            $em->flush();
        }

        $this->setThumbOnContent();

        // return STATUS
        return $img;
    }

    protected function setThumbOnContent(): bool {
        if (!$this->rawImageEntity)
            return FALSE;

        $em = $this->em;
        $this->contentObj->setThumbnailObj($this->rawImageEntity);
        $em->persist($this->contentObj);
        $em->flush();

        return TRUE;
    }

    protected function createImageOnDisk(Crawler $crw, array $parameter): ImageManipulator {
        $img = new ImageManipulator();
        $img->setFilePathOrigin($crw->getFilePath());
        $img->setFileNameNew($this->contentObj->getID());
        $img->buildParameterByConfig($parameter);

        $img->buildFingerprint();

        $img->manipulate();

        return $img;
    }

    protected function buildTags(DataParser $dp) {
        /** @var $t Tag[] */
        $t  = [];
        $em = $this->em;

        $stored = [];
        $tagsToChange = [];
        $tagsToStore = [];
        $tagsInDP = $dp->getTags();

        //
        //  TAG condensation by file
        //
        $fileName = $this->csvPath.'tags.csv';
        if(file_exists($fileName)){
            $lines = file($fileName, FILE_IGNORE_NEW_LINES);
            foreach($lines as $line) {
                $temp = explode(";", $line, 99);
                if (empty($temp[0]))
                    continue;
                foreach ($temp as $key => $value) {
                    if ($key && !empty($value)) {
                        $tagsToChange[$this->getLinkSlugByTitle($value)] = $temp[0];
                    }
                    //  Check Title to increase TAGs condensation by file
                    if(!empty($dp->getTitle()) && stristr(
                        str_replace('-','',$this->getLinkSlugByTitle($dp->getTitle())),
                        str_replace('-','',$this->getLinkSlugByTitle($value))
                                                  ) !== FALSE
                    ){
                        $tagsInDP[] = $temp[0];
                    }
                }
            }
        }

        $this->checkTitleOnSnapChatGirls($dp->getTitle(), $tagsInDP);
        $this->checkTitleOnPornStarGirls($dp->getTitle(), $tagsInDP);

        foreach($tagsInDP as $tag){
            if(isset($tagsToChange[$this->getLinkSlugByTitle( $tag )]))
                $tagsToStore[$this->getLinkSlugByTitle( $tagsToChange[$this->getLinkSlugByTitle( $tag )] )] = $tagsToChange[$this->getLinkSlugByTitle( $tag )];
            else
                $tagsToStore[$this->getLinkSlugByTitle( $tag )] = $tag;

        }

        foreach ($tagsToStore as $sluggedTag => $tag) {
            $slug = $sluggedTag;
            if(empty($slug) || strlen($slug) < 2)
                continue;

            if (isset($stored[$slug]))
                continue;

            $stored[$slug] = 1;

            $em->transactional(
                function (EntityManager $em) use ($slug, $tag, &$t) {
                    $tagE = $em->createQueryBuilder()
                               ->select('t')
                               ->from('AppBundle:Tag', 't')
                               ->where('t.slug = :slug')
                               ->setParameter(':slug', $slug)
                               ->getQuery()
                               ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                               ->getOneOrNullResult();
                    /** @var $t Tag */
                    if (!$tagE) {
                        $x = new Tag(html_entity_decode(trim($tag)), $slug);
                        $em->persist($x);
                        $t[] = $x;
                    } else {
                        $em->createQuery('UPDATE AppBundle\Entity\Tag t SET t.count = t.count + 1 WHERE t.slug = \'' . $slug . '\'')
                           ->execute();
                        $t[] = $tagE;
                    }
                }
            );
        }

        if (count($t) > 0) {
            foreach ($t as $tag) {
                $this->contentObj->addTag($tag);
            }
            $this->em->persist($this->contentObj);
            $this->em->flush();
        }
    }

    public function getRawImageEntity():?Image {
        return $this->rawImageEntity;
    }

    public function addCombinationElement(ItemCreator $item) {
        $cc = new ContentCombination();
        $cc->setContentObj($this->contentObj);

        if ($this->type == \AppBundle\Safety\Types\Content::TYPE_COLLECTION) {
            $cc->setSubContentObj($item->getEntity());
        } else {
            if ($item->getType() == \AppBundle\Safety\Types\Content::TYPE_IMAGE)
                $cc->setImageObj($item->getRawImageEntity());
            else if ($item->getType() == \AppBundle\Safety\Types\Content::TYPE_VIDEO) {
                /** @var $item VideoCreator */
                $cc->setVideoObj($item->getRawVideoEntity());
            } else
                return;
        }

        $pos = 1;
        $e   = $this->em->getRepository('AppBundle:ContentCombination')->findOneBy(['contentObj' => $this->contentObj, 'position' => 'DESC']);
        if ($e)
            $pos = $e->getPosition() + 1;

        $cc->setPosition($pos);

        $this->em->persist($cc);
        $this->em->flush();
    }

    public function getType(): int {
        return $this->type;
    }

    public function getEntity(): Content {
        return $this->contentObj;
    }

    private function checkTitleOnSnapChatGirls(?string $getTitle, ?array &$tagsInDP) {
        if($this->girlsSnapChat === NULL || $getTitle === NULL || empty($getTitle) || !is_array($tagsInDP))
            return;

        if(empty($this->girlsSnapChat)){
            if(!file_exists($fileName = $this->csvPath.'snapchat.dat')){
                $this->girlsSnapChat = NULL;
                return;
            }
            $this->girlsSnapChat = file($fileName, FILE_IGNORE_NEW_LINES);
        }

        $found = FALSE;
        foreach($this->girlsSnapChat as $girl){
            if(stristr($getTitle,$girl) !== FALSE) {
                $found      = TRUE;
                $tagsInDP[] = $girl;
            }
        }
        if($found)
            $tagsInDP[] = 'snapchat';
    }

    private function checkTitleOnPornStarGirls(?string $getTitle, ?array &$tagsInDP) {
        if($this->girlsPorn === NULL || $getTitle === NULL || empty($getTitle) || !is_array($tagsInDP))
            return;

        if(empty($this->girlsPorn)){
            if(!file_exists($fileName = $this->csvPath.'pornstars.dat')){
                $this->girlsPorn = NULL;
                return;
            }
            $this->girlsPorn = file($fileName, FILE_IGNORE_NEW_LINES);
        }

        $found = FALSE;
        $superTitle = str_replace('-','', $this->getLinkSlugByTitle($getTitle));

        foreach($this->girlsPorn as $girl){
            $temp = explode(' ',$girl);
            $girlA = str_replace('-','', $this->getLinkSlugByTitle($girl));
            $girlB = str_replace('-','', $this->getLinkSlugByTitle(implode(' ',array_reverse($temp))));

            if(stristr($superTitle, $girlA) !== FALSE || stristr($superTitle, $girlB) !== FALSE) {
                $found      = TRUE;
                $tagsInDP[] = $girl;
            }
        }
        if($found)
            $tagsInDP[] = 'porn star';
    }
}