<?php

namespace AppBundle\Controller;

use AppBundle\API\APICommunicator;
use AppBundle\Crawler\Crawler;
use AppBundle\Entity\Content;
use AppBundle\Entity\ContentCombination;
use AppBundle\Entity\ContentMeta;
use AppBundle\Entity\ContentParameter;
use AppBundle\Entity\Tag;
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
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class APIContentInfoController
 * @Route("/api")
 * @Method({"POST"})
 */
class APIContentInfoController extends Controller {
    /**
     * @Route("/content/info/tag/all", name="apiInfoOfAllTags")
     * @param Request $request
     * @return JsonResponse
     */
    public function infoAllTagsAction(Request $request) {
        $eCom = new APICommunicator();
        $em   = $this->getDoctrine();

        $io = new DataParser();
        $io->parseByRequestObj($request);
        $io->setType(\AppBundle\Safety\Types\Content::TYPE_INFO);

        // CHECK USER SECURITY
        /** @var $userSecurities UserSecurity */
        $userSecurities = $this->getDoctrine()->getRepository('AppBundle:UserSecurity')->findOneBy(
            ['apiKey' => $io->getApiUserName(), 'apiHash' => $io->getApiUserPw()]
        );

        $headers = FALSE;

        do {
            if (!$io->isReadyForAPI()) {
                $eCom->setError()->noPayload();
                break;
            }

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }

            $qb = $em->getEntityManager()->createQueryBuilder()
                     ->select('t,m')
                     ->from('AppBundle:Tag', 't')
                     ->leftJoin('t.tagMeta', 'm')
                     ->orderBy('t.label')
                     ->setMaxResults(10000)
                     ->getQuery()->getResult();

            $res = [];
            if (!$qb)
                $res = [];
            else {
                /** @var $q Tag */
                foreach ($qb as $q) {
                    if (empty($q->getSlug()))
                        continue;

                    $res[] = [
                        'ID' => $q->getID(),
                        'label' => $q->getLabel(),
                        'slug' => $q->getSlug(),
                        'count' => $q->getCount(),
                        'descr' => $q->getMetaObj() ? $q->getMetaObj()->getDescription() : ''
                    ];
                }
            }

            $headers = TRUE;
            $eCom->setPayload()->asArray($res);
        } while (FALSE);

        $rp = new JsonResponse($eCom->toArray(), $eCom->getCode());
        if ($headers)
            $rp->headers->set('Access-Control-Allow-Origin', 'http://chemnitz.offer-paradise.pw');

        return $rp;
    }

    /**
     * @Route("/content/info/item/title", name="apiInfoOfOneItemTitle")
     * @param Request $request
     * @return JsonResponse
     */
    public function infoItemsOnTitleAction(Request $request) {
        $eCom = new APICommunicator();
        $em   = $this->getDoctrine();

        $io = new DataParser();
        $io->parseByRequestObj($request);
        $io->setType(\AppBundle\Safety\Types\Content::TYPE_INFO);

        $title = $request->request->get('title','');

        // CHECK USER SECURITY
        /** @var $userSecurities UserSecurity */
        $userSecurities = $this->getDoctrine()->getRepository('AppBundle:UserSecurity')->findOneBy(
            ['apiKey' => $io->getApiUserName(), 'apiHash' => $io->getApiUserPw()]
        );

        $headers        = TRUE;

        do {
            if (!$io->isReadyForAPI() || empty($title)) {
                $eCom->setError()->noPayload();
                break;
            }

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }

            $qbr = $em->getEntityManager()->createQueryBuilder()
                      ->select('c,cp,t')
                      ->from('AppBundle:Content', 'c')
                      ->leftJoin('c.parameterObj', 'cp')
                //->leftJoin('c.tagArray','ta')
                      ->leftJoin('c.thumbnailObj', 't')
                      ->where('c.title LIKE :param')
                      ->setParameter('param', '%'.$title.'%')
                      ->getQuery();

            $sql = $qbr->getSQL();
            $qbr = $qbr->getResult();

            /** @var $qbr Content[]*/
            $res = [];
            //$res[] = $sql;
            if (!$qbr) {
                $res = [];
            } else {
                foreach($qbr as $q) {
                    $tags = [];
                    /** @var $t Tag */
                    foreach ($q->getTagArray() as $t) {
                        $tags[] = [
                            'ID' => $t->getID(),
                            'label' => $t->getLabel(),
                            'slug' => $t->getSlug()
                        ];
                    }
                    $res[] = [
                        'ID' => $q->getID(),
                        'title' => $q->getTitle(),
                        'descr' => $q->getDescription(),
                        'time' => $q->getParameterObj()->getTimestamp(),
                        'private' => $q->getParameterObj()->getIsPrivate(),
                        'tags' => $tags,
                        'thumb' => $q->getThumbnailObj()->getThumbnailLinkURL(),
                        'baseID' => $q->getBasedID()
                    ];
                }
            }

            $headers = TRUE;
            $eCom->setPayload()->asArray($res);
        } while (FALSE);

        $rp = new JsonResponse($eCom->toArray(), $eCom->getCode());
        if ($headers)
            $rp->headers->set('Access-Control-Allow-Origin', 'http://chemnitz.offer-paradise.pw');

        return $rp;
    }

    /**
     * @Route("/content/info/item/linkid/{pid}", name="apiInfoOfOneItem")
     * @param Request $request
     * @param String $pid
     * @return JsonResponse
     */
    public function infoOneItemsAction(Request $request, String $pid = '') {
        $eCom = new APICommunicator();
        $em   = $this->getDoctrine();

        $io = new DataParser();
        $io->parseByRequestObj($request);
        $io->setType(\AppBundle\Safety\Types\Content::TYPE_INFO);

        // CHECK USER SECURITY
        /** @var $userSecurities UserSecurity */
        $userSecurities = $this->getDoctrine()->getRepository('AppBundle:UserSecurity')->findOneBy(
            ['apiKey' => $io->getApiUserName(), 'apiHash' => $io->getApiUserPw()]
        );
        $cID            = (int)base_convert($pid, 36, 10);
        $headers        = TRUE;

        do {
            if (!$io->isReadyForAPI()) {
                $eCom->setError()->noPayload();
                break;
            }

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }

            $qbr = $em->getEntityManager()->createQueryBuilder()
                      ->select('c,cp,t')
                      ->from('AppBundle:Content', 'c')
                      ->leftJoin('c.parameterObj', 'cp')
                //->leftJoin('c.tagArray','ta')
                      ->leftJoin('c.thumbnailObj', 't')
                      ->where('c.ID = ' . $cID)
                      ->getQuery();
            $sql = $qbr->getSQL();
            $qbr = $qbr->getOneOrNullResult();

            $res = [];
            //$res[] = $sql;
            if (!$qbr) {
                $res = [];
            } else {
                $tags = [];
                $q    = $qbr;
                /** @var $t Tag */
                foreach ($qbr->getTagArray() as $t) {
                    $tags[] = [
                        'ID' => $t->getID(),
                        'label' => $t->getLabel(),
                        'slug' => $t->getSlug()
                    ];
                }
                $res[] = [
                    'ID' => $q->getID(),
                    'title' => $q->getTitle(),
                    'descr' => $q->getDescription(),
                    'time' => $q->getParameterObj()->getTimestamp(),
                    'private' => $q->getParameterObj()->getIsPrivate(),
                    'tags' => $tags,
                    'thumb' => $q->getThumbnailObj()->getThumbnailLinkURL(),
                    'baseID' => $q->getBasedID()
                ];
            }

            $headers = TRUE;
            $eCom->setPayload()->asArray($res);
        } while (FALSE);

        $rp = new JsonResponse($eCom->toArray(), $eCom->getCode());
        if ($headers)
            $rp->headers->set('Access-Control-Allow-Origin', 'http://chemnitz.offer-paradise.pw');

        return $rp;
    }

    /**
     * @Route("/content/info/item/all/{filterSlug}", name="apiInfoOfAllItems", defaults={"filterSlug":""}, requirements={"filterSlug"="|onlyprivate|notitle|noimage"})
     * @param Request $request
     * @param string $filterSlug
     * @return JsonResponse
     */
    public function infoAllItemsAction(Request $request, string $filterSlug = '') {
        $eCom = new APICommunicator();
        $em   = $this->getDoctrine();

        $io = new DataParser();
        $io->parseByRequestObj($request);
        $io->setType(\AppBundle\Safety\Types\Content::TYPE_INFO);

        // CHECK USER SECURITY
        /** @var $userSecurities UserSecurity */
        $userSecurities = $this->getDoctrine()->getRepository('AppBundle:UserSecurity')->findOneBy(
            ['apiKey' => $io->getApiUserName(), 'apiHash' => $io->getApiUserPw()]
        );

        $filter = $request->request->get('filter', 0);
        $filter = 0;

        $results = $request->request->get('results', 50);
        $results = min(max(5, $results), 50);

        if ($filterSlug == 'noimage')
            $results *= 20;

        $page = $request->request->getInt('page', 1);
        $page = max($page, 1);

        $headers = FALSE;

        do {
            if (!$io->isReadyForAPI()) {
                $eCom->setError()->noPayload();
                break;
            }

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }
            $res = [];
            try {
                $qbr = $em->getEntityManager()->createQueryBuilder()
                          ->select('c,cp,t')
                          ->from('AppBundle:Content', 'c')
                          ->leftJoin('c.parameterObj', 'cp')
                          ->leftJoin('c.thumbnailObj', 't');

                if ($filterSlug != 'onlyprivate')
                    $qbr = $qbr->where('cp.isPrivate = 0');
                else
                    $qbr = $qbr->where('cp.isPrivate = 1');

                if ($filterSlug == 'notitle') {
                    $qbr = $qbr->where('c.title IS NULL')->orWhere('c.title = :pp')->setParameter('pp', "");
                }
                $qbr = $qbr->orderBy('cp.timestamp', 'DESC')
                           ->setMaxResults($results)
                           ->setFirstResult(($page - 1) * $results)
                           ->getQuery();
                $sql = $qbr->getSQL();
                $qbr = $qbr->getResult();


                //$res[] = $sql;
                if (!$qbr) {
                    $v = 1;
                } else {
                    /** @var $q Content */
                    foreach ($qbr as $q) {
                        $tags = [];
                        if ($filterSlug == 'noimage') {
                            $fName = $this->get('kernel')->getProjectDir() . "/" . $this->getParameter('asset_dir') . "/" . $this->getParameter('asset_smallImage_folder') . "/" . $q->getThumbnailObj()->getThumbnailLinkURL();
                            //echo $fName; die();
                            if (file_exists($fName))
                                continue;
                        }
                        if($q->getTagArray() ?? FALSE) {
                            /** @var $t Tag */
                            foreach ($q->getTagArray() as $t) {
                                $tags[] = [
                                    'ID' => $t->getID(),
                                    'label' => $t->getLabel(),
                                    'slug' => $t->getSlug()
                                ];
                            }
                        }
                        $res[] = [
                            'ID' => $q->getID(),
                            'title' => $q->getTitle(),
                            'descr' => $q->getDescription(),
                            'time' => $q->getParameterObj()->getTimestamp(),
                            'private' => $q->getParameterObj()->getIsPrivate(),
                            'tags' => $tags,
                            'thumb' => ($q->getThumbnailObj() ?? FALSE) ? "/" . $this->getParameter('asset_smallImage_folder') . "/" . $q->getThumbnailObj()->getThumbnailLinkURL() : "/_",
                            'baseID' => $q->getBasedID(),
                            'type' => $q->getParameterObj()->getType()
                        ];
                    }
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
            $headers = TRUE;
            $eCom->setPayload()->asArray($res);
        } while (FALSE);

        $rp = new JsonResponse($eCom->toArray(), $eCom->getCode());
        if ($headers)
            $rp->headers->set('Access-Control-Allow-Origin', 'http://chemnitz.offer-paradise.pw');

        return $rp;
    }
}