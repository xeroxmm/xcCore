<?php

namespace AppBundle\Controller;

use AppBundle\API\APICommunicator;
use AppBundle\Crawler\Crawler;
use AppBundle\Entity\Content;
use AppBundle\Entity\ContentCombination;
use AppBundle\Entity\ContentMeta;
use AppBundle\Entity\ContentParameter;
use AppBundle\Entity\Thumbnail;
use AppBundle\Entity\UserSecurity;
use AppBundle\Entity\Video;
use AppBundle\Safety\Content\CollectionCreator;
use AppBundle\Safety\Content\DataParser;
use AppBundle\Safety\Content\ImageCreator;
use AppBundle\Safety\Content\MiniContainer;
use AppBundle\Safety\Content\VideoCreator;
use AppBundle\Template\ContentBase;
use AppBundle\Template\ContentType;
use AppBundle\Tools\Image\ImageManipulator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class APIRelatedController
 * @Route("/api")
 * @Method({"POST"})
 */
class APIRelatedController extends Controller {
    /**
     * @Route("/content/create/image/url", name="apiContentCreateImageURL")
     */
    public function ccIUAction(Request $request) {
        $eCom = new APICommunicator();
        $em   = $this->getDoctrine();

        do {
            // CHECK IF CREDENTIALS ARE GIVEN
            $io = new DataParser();
            $io->parseByRequestObj($request);
            $io->setType(\AppBundle\Safety\Types\Content::TYPE_IMAGE);

            if (!$io->isReadyForAPI()) {
                $eCom->setError()->noPayload();
                break;
            }
            // CHECK USER SECURITY
            /** @var $userSecurities UserSecurity */
            $userSecurities = $this->getDoctrine()->getRepository('AppBundle:UserSecurity')->findOneBy(['apiKey' => $io->getApiUserName(), 'apiHash' => $io->getApiUserPw()]);

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }

            // PARSE URL
            $crw = new Crawler($io->getUrl());

            // try to save image in tmp and try to copy image to specific folder on HDD
            if (!$crw->crwImage($this->getParameter('tool')['images']['tmp_dir'] . '')) {
                $eCom->setError()->wrongURL($io->getUrl());
                break;
            }
            $eCom->setPayload()->contentRelated()->setSource($io->getUrl());

            $create = new ImageCreator($em->getEntityManager());
            $s      = $create->storeInDB(
                $userSecurities->getUserObj(),
                $io,
                $crw,
                $this->getParameter('tool')
            );

            if ($s === FALSE) {
                $eCom->setError()->noContentID();
                break;
            }

            $eCom->setPayload()->contentRelated()->setTitle($io->getTitle());
            // set status true
            break;
        } while (FALSE);

        return new JsonResponse($eCom->toArray(), $eCom->getCode());
    }

    /**
     * @Route("/content/create/video/url", name="apiContentCreateVideoURL")
     */
    public function ccVUAction(Request $request) {
        $eCom = new APICommunicator();
        $em   = $this->getDoctrine();

        do {
            // CHECK IF CREDENTIALS ARE GIVEN
            $io = new DataParser();
            $io->parseByRequestObj($request);
            $io->setType(\AppBundle\Safety\Types\Content::TYPE_VIDEO);

            if (!$io->isReadyForAPI()) {
                $eCom->setError()->noPayload();
                break;
            }
            // CHECK USER SECURITY
            /** @var $userSecurities UserSecurity */
            $userSecurities = $this->getDoctrine()->getRepository('AppBundle:UserSecurity')->findOneBy(['apiKey' => $io->getApiUserName(), 'apiHash' => $io->getApiUserPw()]);

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }

            // PARSE URL
            $crw = new Crawler($io->getUrl());

            // try to save image in tmp and try to copy image to specific folder on HDD
            if (!$crw->crwImage($this->getParameter('tool')['images']['tmp_dir'] . '')) {
                $eCom->setError()->wrongURL($io->getUrl());
                break;
            }
            $eCom->setPayload()->contentRelated()->setSource($io->getUrl());

            if($crw->isDoubledVideo($em->getEntityManager())) {
                $eCom->setError()->noDatabaseFin('dbl content');
                break;
            }

            $cvid = new VideoCreator($em->getEntityManager());
            if (!$cvid->storeInDB($userSecurities->getUserObj(),
                                  $io,
                                  $crw,
                                  $this->getParameter('tool'))) {
                $eCom->setError()->noDatabaseFin($cvid->getError());
                break;
            }

            if ($cvid === FALSE) {
                $eCom->setError()->noContentID();
                break;
            }

            $eCom->setPayload()->contentRelated()->setTitle($io->getTitle());
            // set status true
            break;
        } while (FALSE);

        return new JsonResponse($eCom->toArray(), $eCom->getCode());
    }

    /**
     * @Route("/content/add/url", name="apiContentAddURL")
     */
    public function caUAction(Request $request) {
        $eCom = new APICommunicator();
        $em   = $this->getDoctrine();

        do {
            // CHECK IF CREDENTIALS ARE GIVEN
            $io = new DataParser();
            $io->parseByRequestObj($request);
            $io->setType($io->getType() ?? 0);
            $io->setBulk(TRUE);

            if (!$io->isReadyForAPI() || $io->getChildOf() < 1) {
                $eCom->setError()->noPayload();
                break;
            }

            // CHECK USER SECURITY
            /** @var $userSecurities UserSecurity */
            $userSecurities = $em->getRepository('AppBundle:UserSecurity')->findOneBy(['apiKey' => $io->getApiUserName(), 'apiHash' => $io->getApiUserPw()]);

            if (!$userSecurities || $userSecurities->getUserObj()->getID() != 1) {
                $eCom->setError()->noCredentials();
                break;
            }

            // Check if Content Exists
            /** @var $qb ContentParameter */
            $qb = $em->getEntityManager()->createQueryBuilder()
                     ->select('cp,u,c')
                     ->from('AppBundle:ContentParameter', 'cp')
                     ->leftJoin('cp.userObj', 'u')
                     ->leftJoin('cp.contentObj', 'c')
                     ->where('cp.userObj = ' . $userSecurities->getUserObj()->getID())
                     ->andWhere('cp.contentObj = ' . $io->getChildOf())
                     ->getQuery()->getOneOrNullResult();

            if (!$qb) {
                $eCom->setError()->noDatabaseFin('failed content check - permission');
                break;
            }

            // PARSE URL
            $crw = new Crawler($io->getUrl());

            // try to save image in tmp and try to copy image to specific folder on HDD
            if (!$crw->crwImage($this->getParameter('tool')['images']['tmp_dir'] . '')) {
                $eCom->setError()->wrongURL($io->getUrl());
                break;
            }
            $eCom->setPayload()->contentRelated()->setSource($io->getUrl());

            if ($io->getType() == \AppBundle\Safety\Types\Content::TYPE_IMAGE) {
                $cvid = new ImageCreator($em->getEntityManager());
            } else if ($io->getType() == \AppBundle\Safety\Types\Content::TYPE_VIDEO) {
                $cvid = new VideoCreator($em->getEntityManager());
            } else {
                $eCom->setError()->noDatabaseFin('not supported type');
                break;
            }

            if (!$cvid->storeInDB($qb->getUserObj(),
                                  $io,
                                  $crw,
                                  $this->getParameter('tool'))) {
                $eCom->setError()->noDatabaseFin($cvid->getError());
                break;
            }

            if ($cvid === FALSE) {
                $eCom->setError()->noContentID();
                break;
            }

            // add combination
            $cc = new ContentCombination();
            $cc->setContentObj($qb->getContentObj());
            $pos = 1;
            $e   = $em->getEntityManager()->getRepository('AppBundle:ContentCombination')->findOneBy(['contentObj' => $qb->getContentObj()], ['position' => 'DESC']);
            if ($e)
                $pos = $e->getPosition() + 1;

            $cc->setPosition($pos);

            if ($qb->getType() == \AppBundle\Safety\Types\Content::TYPE_COLLECTION) {
                $cc->setSubContentObj($cvid->getEntity());
            } else {
                if ($cvid->getType() == \AppBundle\Safety\Types\Content::TYPE_IMAGE) {
                    $cc->setImageObj($cvid->getRawImageEntity());
                } else if ($cvid->getType() == \AppBundle\Safety\Types\Content::TYPE_VIDEO) {
                    $cc->setVideoObj($cvid->getRawVideoEntity());
                } else {
                    $eCom->setError()->noDatabaseFin('not supported type Xv');
                    break;
                }
            }

            $em->getEntityManager()->createQuery('UPDATE AppBundle:ContentMeta cm SET cm.mediaIn = cm.mediaIn + 1 WHERE cm.contentObj = ' . $qb->getContentObj()->getID())->execute();

            $em->getManager()->persist($cc);
            $em->getManager()->flush();

            $eCom->setPayload()->contentRelated()->setTitle($io->getTitle());
            break;
        } while (FALSE);

        return new JsonResponse($eCom->toArray(), $eCom->getCode());
    }

    /**
     * @Route("/content/create/collection/json", name="apiContentCreateCollectionJson")
     */
    public function ccCJAction(Request $request) {
        $eCom = new APICommunicator();
        $em   = $this->getDoctrine();

        do {
            // CHECK IF CREDENTIALS ARE GIVEN
            $io = new DataParser();
            $io->parseByRequestObj($request);
            $io->setType(\AppBundle\Safety\Types\Content::TYPE_COLLECTION);

            if (!$io->isReadyForAPI()) {
                $eCom->setError()->noPayload();
                break;
            }

            // CHECK USER SECURITY
            /** @var $userSecurities UserSecurity */
            $userSecurities = $em->getRepository('AppBundle:UserSecurity')->findOneBy(['apiKey' => $io->getApiUserName(), 'apiHash' => $io->getApiUserPw()]);

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }

            // check if enough payload and store it in an array
            $jsonArr = [];
            $i       = 0;
            $newSub  = NULL;
            if ($io->getData()) {
                foreach ($io->getData() as $data) {
                    $tx = new MiniContainer($data, $userSecurities->getUserObj());
                    $tx->setSiteURL($this->get('router'));
                    if ($tx->hasMinimalContentCredentials() && $i > 0)
                        $jsonArr[] = $tx;
                    else if ($tx->hasMinimalContentCredentials()) {
                        $newSub = $tx;
                        $i++;
                    }
                }
            }

            // get first image OR video as Thumbnail
            if (!$jsonArr && !$newSub) {
                $eCom->setError()->noDatabaseFin('failed payload check - noSub');
                break;
            } else if (!$jsonArr || count($jsonArr) < 2) {
                $eCom->setError()->noDatabaseFin('failed payload check - Minimum: 5 elements');
                break;
            }

            // try to create Content Object
            $col = new CollectionCreator($em->getEntityManager());
            $col->storeInDB($userSecurities->getUserObj(), $io);

            // try to create first image or video
            $firstElement = NULL;

            // PARSE URL
            $crw = new Crawler($newSub->url);
            if (!$crw->crwImage($this->getParameter('tool')['images']['tmp_dir'] . '')) {
                $eCom->setError()->wrongURL($io->getUrl());
                break;
            }

            $subIO = new DataParser();
            $subIO->parseByMiniContainer($newSub);
            if ($newSub->type == \AppBundle\Safety\Types\Content::TYPE_IMAGE) {
                $firstElement = new ImageCreator($em->getEntityManager());
            } else if ($newSub->type == \AppBundle\Safety\Types\Content::TYPE_VIDEO) {
                $firstElement = new VideoCreator($em->getEntityManager());
            } else {
                $eCom->setError()->wrongURL('type not supported: ' . $newSub->type);
                break;
            }
            $subIO->setType($newSub->type);
            $subIO->setBulk(TRUE);
            $firstElement->storeInDB($userSecurities->getUserObj(), $subIO, $crw, $this->getParameter('tool'));

            $col->addImageAsThumbnail($firstElement->getRawImageEntity());
            $col->addCombinationElement($firstElement);

            // save rest in file-DB
            // check if folder exists
            $wDir = $this->getParameter('tool')['images']['tmp_dir'] . '/collection/' . $col->getEntity()->getID();
            if (!file_exists($wDir))
                mkdir($wDir, 0775, TRUE);

            $fileName = '' . time() . '.' . mt_rand(1000, 9999) . '.';
            $postFix  = '.json';

            $i = 0;
            foreach ($jsonArr as $arr) {
                /** @var $arr MiniContainer */
                $i++;
                $fp = @fopen($wDir . '/' . $fileName . $i . $postFix, 'w');
                $arr->setID($col->getEntity()->getID());
                if ($fp) {
                    fwrite($fp, json_encode($arr) . "\n");
                    fclose($fp);
                }
            }
            // looks like done... yeah...
            $eCom->setPayload()->contentRelated()->setTitle($io->getTitle());
            break;
        } while (FALSE);

        return new JsonResponse($eCom->toArray(), $eCom->getCode());
    }
}