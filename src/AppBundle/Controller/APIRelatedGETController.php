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
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class APIRelatedController
 * @Route("/api")
 * @Method({"GET"})
 */
class APIRelatedGETController extends Controller {
    /**
     * @Route("/content/info/tag", name="apiContentTagRelated")
     */
    public function cITagAction(Request $request) {
        $eCom = new APICommunicator();
        $em   = $this->getDoctrine();

        $tagID  = (int)$request->query->get('tagID') ?? 0;
        $length = 52;

        $thisPage = max($request->query->getInt('page', 1), 1);
        $filter = $request->query->get('filter',0);

        /** @var $qb Content[] */
        $qb = $em->getManager()->createQueryBuilder()
                 ->select('c,t,u,cp,cm,ta')
                 ->from('AppBundle:Content', 'c')
                 ->leftJoin('c.parameterObj', 'cp')
                 ->leftJoin('cp.userObj', 'u')
                 ->leftJoin('c.thumbnailObj', 't')
                 ->leftJoin('c.contentMeta', 'cm')
                 ->leftJoin('c.tagArray', 'ta')
                 ->where('cp.isBulk = 0')
                 ->andWhere('cp.isSFW = 1')
                 ->andWhere('cp.isPrivate = 0')
                 ->andWhere('ta.ID = ' . $tagID);

        if($filter == 1)
            $qb->orderBy('cp.score','DESC');
        else
            $qb->orderBy('cp.timestamp', 'DESC');

        $qb = $qb->setFirstResult($length * ($thisPage - 1))
                 ->setMaxResults($length)
                 ->getQuery()->getResult();

        if(!isset($qb[0]) || !($qb[0]->getTagArray() ?? FALSE)){
            $eCom->setError()->noDatabaseFin();
            return new JsonResponse($eCom->toArray(), $eCom->getCode());
        }

        $tag_0 = $qb[0]->getTagArray()->get(0);

        $cnt = new ContentBase(0, 0, NULL, $request);
        $cnt->setTitle($tag_0->getLabel());
        $cnt->setMediaIn($tag_0->getCount());
        $cnt->loadInfoByContentEntityArray($qb, 0);

        if ($qb) {
            $nextPaginationPage = (count($qb) == $length) ? $thisPage + 1 : NULL;

            /** @var $result Content */
            //print_r($result->getTagArray()->toArray());

            $eCom->setPayload()->asString($this->renderView('/content/tagLoop.html.twig', [
                'tagID' => $tagID,
                'content' => $cnt->getSubContent(), 'nextPaginationPage' => $nextPaginationPage,
                'filter' => $filter
            ]));
        } else
            $eCom->setError()->noPayload();

        return new JsonResponse($eCom->toArray(), $eCom->getCode());
    }

    /**
     * @Route("/content/info/related", name="apiContentInfoRelated")
     */
    public function cIRAction(Request $request) {
        $eCom = new APICommunicator();
        $em   = $this->getDoctrine();

        $contentID = (int)$request->query->get('cID') ?? 0;
        $startPage = max((int)($request->query->get('page') ?? 1), 1);
        $length    = 52;

        if($request->query->get('filter',0) == 1){
            return $response = $this->forward('AppBundle:APIRelatedGET:cIB', array(
                'request'  => $request,
            ));
        }

        if ($contentID > 0) {
            $result = $em->getManager()->createQueryBuilder()
                         ->select('c,t')
                         ->from('AppBundle:Content', 'c')
                         ->leftJoin('c.tagArray', 't')
                         ->where('c.ID = ' . $contentID)
                         ->getQuery()->getOneOrNullResult();
            /** @var $result Content */
            //print_r($result->getTagArray()->toArray());

            $nextPaginationPage = NULL; // $nextPaginationPage = (count($result) == $length) ? $thisPage + 1 : NULL;

            if ($result) {
                $cnt = new ContentBase($contentID, \AppBundle\Safety\Types\Content::TYPE_IMAGE, $em->getEntityManager(), $request);
                $cnt->setTags($result->getTagArray());
                $nmbr               = count($cnt->getRelatedContentEntities($startPage));
                $nextPaginationPage = ($nmbr == $length) ? $startPage + 1 : NULL;

                $eCom->setPayload()->asString($this->renderView('/content/relatedLoop.html.twig', ['content' => $cnt, 'nextPaginationPage' => $nextPaginationPage]));
            } else
                $eCom->setError()->noPayload();
        } else
            $eCom->setError()->noContentID();

        return new JsonResponse($eCom->toArray(), $eCom->getCode());
    }

    /**
     * @Route("/content/info/base", name="apiContentBaseRelated")
     */
    public function cIBAction(Request $request) {
        $eCom = new APICommunicator();
        $em   = $this->getDoctrine();

        $length   = 52;
        $thisPage = max($request->query->getInt('page', 0), 1);
        $filter = $request->query->getInt('filter', 0);

        $es = $em->getManager();
        $es = $es->createQueryBuilder()->select('c,t,u,cp,cm')
                 ->from('AppBundle:Content', 'c')
                 ->leftJoin('c.parameterObj', 'cp')
                 ->leftJoin('cp.userObj', 'u')
                 ->leftJoin('c.thumbnailObj', 't')
                 ->leftJoin('c.contentMeta', 'cm')
                 ->where('cp.isBulk = 0')
                 ->andWhere('cp.isSFW = 1')
                 ->andWhere('cp.isPrivate = 0');

            if($filter == 1)
                $es->orderBy('cp.score','DESC');
            else
                $es->orderBy('cp.timestamp', 'DESC');

        $qb = $es->setFirstResult($length * ($thisPage - 1))
                 ->setMaxResults($length)
                 ->getQuery()->getResult();

        /** @var $qb ArrayCollection */
        $nextPaginationPage = (count($qb) == $length) ? $thisPage + 1 : NULL;

        /** @var $result Content */
        //print_r($result->getTagArray()->toArray());
        if ($qb) {
            $eCom->setPayload()->asString(
                $this->renderView('/content/baseLoop.html.twig',
                                  ['content' => $qb,
                                   'nextPaginationPage' => $nextPaginationPage,
                                   'filter' => $filter
                                  ])
            );
        } else
            $eCom->setError()->noPayload();

        return new JsonResponse($eCom->toArray(), $eCom->getCode());
    }

    /**
     * @Route("/info/status", name="apiInfoStatus")
     */
    public function ISAction() {
        $eCom = new APICommunicator();

        try {
            $apiUser = $this->getDoctrine()->getManager()->find('AppBundle:UserSecurity', 1);
            $status  = ($apiUser->getApiKey() == 'xeroxmm' && $apiUser->getApiPassword() == '123456dfgdfgdfgdfg653656tegh');
        } catch (\Exception $exception) {
            $status = FALSE;
        }
        $eCom->setPayload()->asBool($status);
        return new JsonResponse($eCom->simpleResponse(), $eCom->getCode());
    }
}