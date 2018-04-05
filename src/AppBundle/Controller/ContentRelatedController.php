<?php

namespace AppBundle\Controller;

use AppBundle\API\APICommunicator;
use AppBundle\Entity\Content;
use AppBundle\Entity\ContentCombination;
use AppBundle\Entity\ContentParameter;
use AppBundle\Entity\Tag;
use AppBundle\Security\User\Anonym\SessionHandler;
use AppBundle\Security\User\Cookie\GenericHandler;
use AppBundle\Template\AdvertisementContainer;
use AppBundle\Template\ContentBase;
use AppBundle\Tools\Advertisement\ContentDeliver;
use AppBundle\Tools\Advertisement\ContentInjectContainer;
use AppBundle\Tools\Arbitrage\ConfigContainer;
use AppBundle\Tools\Arbitrage\CookieChecker;
use AppBundle\Tools\Supply\Cookies;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class ContentRelatedController extends Controller {
    private $client;
    private $limit = FALSE;
    private $limitLooped = FALSE;

    public function getLimitRelated(): int {
        if ($this->limit === FALSE)
            $this->limit = $this->container->hasParameter('template') ? $this->getParameter('template')['elementCounter']['related'] ?? 12 : 12;

        return $this->limit;
    }

    public function getLimitLooped(): int {
        if ($this->limitLooped === FALSE)
            $this->limitLooped = $this->container->hasParameter('template') ? $this->getParameter('template')['elementCounter']['looped'] ?? 12 : 12;

        return $this->limitLooped;
    }

    public function __construct(RequestStack $requestStack, EntityManagerInterface $em) {
        $session      = new Session();
        $this->client = new SessionHandler($session);
        if ($this->client->isNewSession()) {
            $cookieHandler = new GenericHandler($requestStack->getCurrentRequest()->cookies, $em);
            if ($cookieHandler->isCookieInDB()) {
                // user has got SESSION in DataBase
                $this->client->buildUser($cookieHandler->getUserID());
            } else {
                // complete NEW SESSION 4 USER
                $nothingToDo = TRUE;
            }
        } else {
            // user has got SESSION
            $nothingToDo = TRUE;
        }
    }

    /**
     * @Route("/interncrawl/{baseID}/{titleSlug}", name="pageInternCrawl", defaults={"titleSlug":""})
     * @param Request $request
     * @param string $baseID
     * @return JsonResponse
     */
    public function indexCrawlAction(Request $request, string $baseID) {
        $get = $request->get('intern', FALSE);
        $r   = new APICommunicator();
        if ($get !== 'radegast') {
            $r->setError()->noCredentials();
            return $r->doResponse();
        }
        $ID = (int)base_convert($baseID, 36, 10);
        $em = $this->get('doctrine')->getEntityManager();

        $qb = $em->createQueryBuilder()
                 ->select('c,t')
                 ->from('AppBundle:Content', 'c')
                 ->leftJoin('c.thumbnailObj', 't')
                 ->where('c.ID = ' . $ID)
                 ->getQuery()->getOneOrNullResult();

        if (!$qb) {
            // FORCE 404
            $r->setError()->noDatabaseFin();
            return $r->doResponse();
        }
        /** @var $qb Content */
        $r->setPayload()->asString($request->getScheme() . '://' . $request->getHttpHost() . '/img/f/' . $qb->getThumbnailObj()->getURL() . '.' . $qb->getThumbnailObj()->getMime());

        return $r->doResponse();
    }

    /**
     * @Route("/i/{baseID}/", name="pageImageFalseSlash")
     * @param string $baseID
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexImageActionFalseSlash(string $baseID, Request $request) {
        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getManager();
        $res = $em->find('AppBundle:Content',base_convert($baseID,36,10));

        if(!$res || $res->getParameterObj()->getType() != \AppBundle\Safety\Types\Content::TYPE_IMAGE)
            throw $this->createNotFoundException("Image Post >> " . $request->getRequestUri() . ' << not found');

        return $this->redirectToRoute('pageImage', ['baseID' => $baseID, 'titleSlug' => strlen($res->getLink() > 0) ? $res->getLink() : ($this->get('service_container')->hasParameter('image_empty_title_manifest') ? $this->getParameter('image_empty_title_manifest') : 'image-') . base_convert($baseID,36,8), 'sortSlug' => ''], 302);
    }

    /**
     * @Route("/i/{baseID}/{titleSlug}/{sortSlug}", name="pageImage", defaults={"titleSlug":"","sortSlug"=""}, requirements={"sortSlug"="_vb||popular"})
     * @param Request $request
     * @param string $baseID
     * @param null|string $titleSlug
     * @return Response
     */
    public function indexImageAction(Request $request, string $baseID, ?string $titleSlug) {
        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getManager();
        $contentSupply = new \AppBundle\Tools\Supply\Content($em, $this->getLimitRelated(), $this->client, $request);
        $contentSupply->setContextContentImage();
        $contentSupply->setContentIDBased($baseID);
        $contentSupply->setAllowAdvertisement(TRUE);

        if (!$contentSupply->isValidContext())
            throw $this->createNotFoundException("Image Post >> " . $request->getRequestUri() . ' << not found');

        $iTitle = strlen($contentSupply->getContextObjectRAW()->getLink()) > 0 ? $contentSupply->getContextObjectRAW()->getLink() : ($this->get('service_container')->hasParameter('image_empty_title_manifest') ? $this->getParameter('image_empty_title_manifest') : 'image-') . base_convert($baseID,36,8);
        if($titleSlug !== $iTitle ?? '_cc_x_')
            return $this->redirectToRoute('pageImage', ['baseID' => $baseID, 'titleSlug' => $iTitle, 'sortSlug' => ''], 302);

        if ($this->client) {
            $this->client->setContentBaseID((int)base_convert($baseID, 36, 10));
        }

        $contentSupply->finalize();

        $adContainer = new AdvertisementContainer($em, $request);
        $adContainer->addReferrerOfClient($this->client->getRawReferrer());
        foreach($contentSupply->getPostContent()->getTagArrayCollection() as $tag)
            $adContainer->addTag($tag);

        return $this->render('content/image.html.twig', [
            'contentSupply' => $contentSupply,
            'client' => $this->client ?? NULL,
            'advertisementContainer' => $adContainer->loadElements( 10 )
        ]);
    }

    /**
     * @Route("/i_old/{baseID}/{titleSlug}/{sortSlug}", name="pageImageOld", defaults={"titleSlug":"", "sortSlug" : ""})
     * @param Request $request
     * @param string $baseID
     * @param string $titleSlug
     * @param null|string $sortSlug
     * @return Response
     */
    public function indexIAction(Request $request, string $baseID, ?string $titleSlug, ?string $sortSlug) {
        Cookies::setByPopularSlug($sortSlug, $request->cookies);

        $checker = new CookieChecker($request->cookies);
        if ($request->query->get('i', '0') != '1' && $checker->isSendToAdnetworkEvent() && $this->container->hasParameter('arbitrage')) {
            $arbitrage = new ConfigContainer($this->getParameter('arbitrage'));
            if ($arbitrage->isEnabled()) {
                $response = new Response();
                $response->headers->clearCookie('_leyka');
                $network = $arbitrage->getNetworkObjectByID($checker->getAdnetworkID());

                return $this->render('arbitrage/redirectToAdnetwork.html.twig', ['network' => $network->getNetworkName(), 'id' => $network->getNetworkID()], $response);
            }
        }
        $ID     = (int)base_convert($baseID, 36, 10);
        $em     = $this->get('doctrine')->getEntityManager();
        $pValue = max($request->query->getInt('page', 0), 1);
        $filter = $request->query->getInt('filter', 0);

        // Load NEW Content
        /** @var $qb Content */
        $qb = $em->createQueryBuilder()
                 ->select('c,cp,cm,ce,ct,u,i')
                 ->from('AppBundle:Content', 'c')
                 ->leftJoin('c.parameterObj', 'cp')
                 ->leftJoin('c.contentMeta', 'cm')
                 ->leftJoin('c.elementList', 'ce')
                 ->leftJoin('c.thumbnailObj', 'ct')
                 ->leftJoin('cp.userObj', 'u')
                 ->leftJoin('ce.imageObj', 'i')
                 ->where('c.ID = ' . $ID . ' AND cp.type = ' . \AppBundle\Safety\Types\Content::TYPE_IMAGE)
                 ->getQuery()->getOneOrNullResult();

        if (!$qb) {
            // FORCE 404
            throw $this->createNotFoundException('This image page does not exist :-(');
        } else if ($titleSlug != $qb->getLink() && !empty($qb->getLink()) && strlen($qb->getLink()) > 2) {
            //die($qb->getLink());
            return $this->redirectToRoute('pageImage', array('titleSlug' => $qb->getLink(), 'baseID' => $baseID));
        }

        /** @var $qb2 Content */
        $qb2 = $em->createQueryBuilder()
                  ->select('c,t')
                  ->from('AppBundle:Content', 'c')
                  ->leftJoin('c.tagArray', 't')
                  ->where('c.ID = ' . $ID)
                  ->getQuery()->getOneOrNullResult();

        /** @var $qb Content */
        $cnt = new ContentBase($qb->getID(), \AppBundle\Safety\Types\Content::TYPE_IMAGE, $em, $request);
        $cnt->setTitle($qb->getTitle());
        $cnt->setDescription($qb->getDescription() ?? '');
        $cnt->setDateByTimestamp($qb->getParameterObj()->getTimestamp());
        $cnt->setThumbURL('/img/t' . $qb->getImagePath());
        $cnt->setThumbURLMed('/img/m/' . $qb->getImagePath());
        $cnt->setImageFull('/img/f/' . $qb->getImagePath());
        $cnt->setThumbURLMedAbsolute('//' . $_SERVER['HTTP_HOST'] . '/img/f/' . $qb->getImagePath());
        $cnt->setWidth($qb->getResolutionOfLargestImage()[0]);
        $cnt->setHeight($qb->getLargestImageObj()->getDimY());
        $cnt->setCanonicalURL($qb->getFullURL());
        $cnt->setLinkURL($qb->getLink());
        //$cnt->setFileSize($qb->getContentMetaObj()->getFileSize());

        // TODO add UserObject to query
        $cnt->setUser($qb->getParameterObj()->getUserObj());

        $cnt->setTags($qb2->getTagArray()->toArray());

        // Query the content around this post (7)
        $rightResultAmount = 2;
        $linkedContent     = [];
        $result            = $em->createQueryBuilder()
                                ->select('c,ct')
                                ->from('AppBundle:Content', 'c')
                                ->leftJoin('c.parameterObj', 'cp')
                                ->leftJoin('c.thumbnailObj', 'ct')
                                ->where('c.ID > ' . $ID . ' AND cp.type = ' . \AppBundle\Safety\Types\Content::TYPE_IMAGE)
                                ->andWhere('cp.isPrivate = 0')
                                ->orderBy('c.ID', 'ASC')
                                ->setMaxResults($rightResultAmount)
                                ->getQuery()->getResult();

        if (count($result) < $rightResultAmount)
            $rightResultAmount += ($rightResultAmount - count($result));
        $rightResultAmount += 1;

        $result2 = $em->createQueryBuilder()
                      ->select('c,ct')
                      ->from('AppBundle:Content', 'c')
                      ->leftJoin('c.parameterObj', 'cp')
                      ->leftJoin('c.thumbnailObj', 'ct')
                      ->where('c.ID < ' . $ID . ' AND cp.type = ' . \AppBundle\Safety\Types\Content::TYPE_IMAGE)
                      ->andWhere('cp.isPrivate = 0')
                      ->orderBy('c.ID', 'DESC')
                      ->setMaxResults($rightResultAmount)
                      ->getQuery()->getResult();

        if ($result) {
            rsort($result);
            $linkedContent = $result;

            $cnt->setPreviousContentInList($result[count($result) - 1]);
        }

        $qb->setFlagCurrent(TRUE);
        $linkedContent[] = $qb;

        if ($result2) {
            $linkedContent = array_merge($linkedContent, $result2);
            $cnt->setNextContentInList($result2[0]);
        }

        $titleTag    = '';
        $titleTagArr = [];
        if ($cnt->getTags()[0] ?? FALSE) {
            $titleTag = ' - tagged with: ';
            // $titleTag = ' - a' . (in_array('' . substr($cnt->getTags()[0]->getLabel(), 0, 1), ['a', 'e', 'i', 'o', 'u']) ? 'n ' : ' ') . $cnt->getTags()[0]->getLabel() . ' Image';
            for ($i = 0; $i < min(3, count($cnt->getTags())); $i++) {
                $titleTagArr[] = $cnt->getTags()[$i]->getLabel();
            }
            $titleTag .= implode(', ', $titleTagArr);
        }

        // Do specific things do identify client
        if ($this->client) {
            $identifier = $this->client->getUniquePageIdentifierByRawID($ID);
            $client     = $this->client->getVoteObject($ID);
        }

        $ads = new ContentDeliver($em);
        $ads->setTypesToLoad([\AppBundle\Safety\Types\Content::TYPE_ADVERTISEMENT_NET_INTERN, \AppBundle\Safety\Types\Content::TYPE_ADVERTISEMENT_TRADE]);

        $qbPop = new \AppBundle\Tools\Supply\Content($em);
        $qbPop->setLimit($this->limit);
        $qbPop->setContentIDtoSkip($qb->getID());
        if ($filter == 1) {
            $qbPop->setOffset($this->limit * ($pValue - 1));
        }
        //$qbPop->setOnlyImages();

        return $this->render('content/image.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
            'content' => $cnt,
            'linkedcontent' => $linkedContent,
            'descriptionString' => 'Picture: ' . $cnt->getTitle() . $titleTag,
            'jsIdentifier' => $identifier ?? NULL,
            'clientVotes' => $client ?? NULL,
            'popular' => $qbPop,
            'ads' => $ads->loadRandomAds(5),
            'thisURL' => $cnt->getCanonicalURL()
        ]);
    }

    /**
     * @Route("/c/{baseID}/{titleSlug}/{contentCat}/{sortSlug}", name="pageCollection", defaults={"titleSlug":"", "sortSlug" : "","contentCat":""}, requirements={"sortSlug":"|popular|related","contentCat":"|images|videos|gifs|popular|related"})
     * @param Request $request
     * @param string $baseID
     * @param string $titleSlug
     * @param string $sortSlug
     * @return Response
     */
    public function indexCollectionPageAction(Request $request, string $baseID, string $titleSlug, string $sortSlug) {
        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getManager();

        $content = new \AppBundle\Tools\Supply\Content($em, $this->getLimitRelated(), $this->client, $request);
        $content->setContentIDBased($baseID);
        //$content->setOnlyCollections();
        $content->setContextContentCollection();
        $content->setAllowAdvertisement(TRUE);

        if (!$content->isValidContext())
            throw $this->createNotFoundException('The Collection >>' . $request->getRequestUri() . '<< does not exist');

        $iTitle = strlen($content->getContextObjectRAW()->getLink()) > 0 ? $content->getContextObjectRAW()->getLink() : 'nice-collection-' . base_convert($baseID,36,8);
        if($titleSlug !== $iTitle ?? '_cc_x_')
            return $this->redirectToRoute('pageCollection', ['baseID' => $baseID, 'titleSlug' => $iTitle, 'sortSlug' => ''], 302);

        if ($this->client) {
            $this->client->setContentBaseID((int)base_convert($baseID, 36, 10));
        }

        return $this->render('content/collection.html.twig', [
            'contentSupply' => $content->loadElementsInSubCategories()->finalize(),
            'client' => $this->client ?? NULL,
        ]);
    }

    /**
     * @Route("/t/{tagSlug}/{tagCat}/{sortSlug}",
     *     name="pageTag",
     *     defaults={"tagCat"="_all","sortSlug" : ""},
     *     requirements={"tagCat":"_all|images|videos|gifs|popular|collections","sortSlug":"|popular"})
     * @param Request $request
     * @param string $tagSlug
     * @param string $sortSlug
     * @return Response
     */
    public function indexTagAction(Request $request, string $tagSlug, string $tagCat, string $sortSlug) {
        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getManager();

        $content = new \AppBundle\Tools\Supply\Content($em, $this->getLimitLooped(), $this->client, $request);
        $content->setTagSlug($tagSlug);
        $content->setContextListTags();
        $content->setAllowAdvertisement(TRUE);

        /*if($this->container->hasParameter('template_loop_content_amount') && $this->getParameter('template_loop_content_amount') > 12)
            $content->setLimit( $this->getParameter('template_loop_content_amount') );
*/
        if (!$content->isValidContext())
            throw $this->createNotFoundException('The tag >>' . $tagSlug . '<< does not exist');

        if(FALSE !== ($changeRoute = $content->isFilterAllEmpty())) {
            return $this->redirectToRoute('pageTag', ['tagSlug' => $tagSlug, 'tagCat' => $changeRoute, 'sortSlug' => $sortSlug], 302);
        }

        $adContainer = new AdvertisementContainer($em, $request);
        $adContainer->addReferrerOfClient($this->client->getRawReferrer());

        $adContainer->addTag($content->getTagObject());

        return $this->render('content/tag.html.twig', [
            'contentSupply' => $content->loadElementsInSubCategories()->finalize(),
            'advertisementContainer' => $adContainer->loadElements( 10 )
            ]
        );
    }

    /**
     * @Route("/t_old/{tagSlug}", name="pageTagOld")
     * @param Request $request
     * @param string $tagSlug
     * @return Response
     */
    public function indexTAction(Request $request, string $tagSlug) {
        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getManager();

        $page = max($request->query->getInt('page', 1), 1);

        $content = new \AppBundle\Tools\Supply\Content($em, $this->getLimitRelated(), $this->client, $request);
        $content->setTagSlug($tagSlug);
        $content->setTypeList();
        $content->setOffsetByPage($page);
        $content->setAllowAdvertisement(TRUE);
        $content->finalize();
        die();
        return $this->render('content/tag.html.twig', [
                                                        'contentSupply' => $content
                                                    ]
        );
    }

    /**
     * @Route("/u/{userSlug}/{sortSlug}", name="pageUser", defaults={"sortSlug" : ""})
     * @param Request $request
     * @param string $userSlug
     * @param string $sortSlug
     * @return Response
     */
    public function indexUAction(Request $request, string $userSlug, string $sortSlug) {
        Cookies::setByPopularSlug($sortSlug, $request->cookies);

        $em = $this->get('doctrine')->getEntityManager();

        // Load

        // Load NEW Content
        /** @var $qb Content[] */
        $qb = $em->createQueryBuilder()
                 ->select('c,cp,cm,ct,u,cg')
                 ->from('AppBundle:Content', 'c')
                 ->leftJoin('c.parameterObj', 'cp')
                 ->leftJoin('c.contentMeta', 'cm')
                 ->leftJoin('c.thumbnailObj', 'ct')
                 ->leftJoin('cp.userObj', 'u')
                 ->leftJoin('c.tagArray', 'cg')
                 ->where('u.username = :un')
                 ->andWhere('cp.isPrivate = 0')
                 ->setParameter('un', $userSlug)
                 ->orderBy('cp.timestamp', 'DESC')
                 ->getQuery()->getResult();

        if (!$qb) {
            // FORCE 404
            throw $this->createNotFoundException('The user page "' . $userSlug . '" does not exist :-(');
        }

        $cnt = new ContentBase(0, 0, NULL, $request);
        $cnt->setTitle($userSlug);
        $cnt->loadInfoByContentEntityArray($qb, 0);

        return $this->render('content/tag.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
            'content' => $cnt,
            'thisURL' => $this->generateUrl('pageUser', ['userSlug' => $userSlug], 1)
        ]);
    }

    /**
     * @Route("/v/{baseID}/", name="pageVideoFalseSlash")
     * @param string $baseID
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexVideoActionFalseSlash(string $baseID, Request $request) {
        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getManager();
        $res = $em->find('AppBundle:Content',base_convert($baseID,36,10));

        if(!$res || $res->getParameterObj()->getType() != \AppBundle\Safety\Types\Content::TYPE_VIDEO)
            throw $this->createNotFoundException("Video Post >> " . $request->getRequestUri() . ' << not found');

        return $this->redirectToRoute('pageVideo', [
            'baseID' => $baseID,
            'titleSlug' => $res->getLink(),
            'sortSlug' => ''
        ], 302);
    }

    /**
     * @Route("/v/{baseID}/{titleSlug}/{sortSlug}", name="pageVideo", defaults={"titleSlug":"","sortSlug"=""}, requirements={"sortSlug"="_vb||popular"})
     * @param Request $request
     * @param string $baseID
     * @param string $titleSlug
     * @param string $sortSlug
     * @return Response
     */
    public function indexVAction(Request $request, string $baseID, string $titleSlug, string $sortSlug) {
        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getManager();

        $contentSupply = new \AppBundle\Tools\Supply\Content($em, $this->getLimitRelated(), $this->client, $request);
        $contentSupply->setContextContentVideo();
        $contentSupply->setContentIDBased($baseID);
        $contentSupply->setAllowAdvertisement(TRUE);

        if (!$contentSupply->isValidContext())
            throw $this->createNotFoundException("Video Post >> " . $request->getRequestUri() . ' << not found');

        if(strlen($contentSupply->getContextObjectRAW()->getLink()) > 0 && $titleSlug !== $contentSupply->getContextObjectRAW()->getLink() ?? '_cc_x_')
            return $this->redirectToRoute('pageVideo', ['baseID' => $baseID, 'titleSlug' => $contentSupply->getContextObjectRAW()->getLink(), 'sortSlug' => ''], 302);


        if ($this->client) {
            $this->client->setContentBaseID((int)base_convert($baseID, 36, 10));
        }

        return $this->render('content/video.html.twig', [
            'contentSupply' => $contentSupply->finalize(),
            'client' => $this->client ?? NULL,
        ]);
    }

    /**
     * @Route("/g/{baseID}/", name="pageGifFalseSlash")
     * @param string $baseID
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexGifActionFalseSlash(string $baseID, Request $request) {
        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getManager();
        $res = $em->find('AppBundle:Content',base_convert($baseID,36,10));

        if(!$res || $res->getParameterObj()->getType() != \AppBundle\Safety\Types\Content::TYPE_GIF)
            throw $this->createNotFoundException("GIF Post >> " . $request->getRequestUri() . ' << not found');

        return $this->redirectToRoute('pageGIFs', ['baseID' => $baseID, 'titleSlug' => $res->getLink(), 'sortSlug' => ''], 302);
    }

    /**
     * @Route("/g/{baseID}/{titleSlug}/{sortSlug}", name="pageGIFs", defaults={"titleSlug":"","sortSlug"=""}, requirements={"sortSlug"="_vb||popular"})
     * @param Request $request
     * @param string $baseID
     * @param string $titleSlug
     * @param string $sortSlug
     * @return Response
     */
    public function indexGifAction(Request $request, string $baseID, string $titleSlug, string $sortSlug) {
        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getManager();

        $contentSupply = new \AppBundle\Tools\Supply\Content($em, $this->getLimitRelated(), $this->client, $request);
        $contentSupply->setContextContentGif();
        $contentSupply->setContentIDBased($baseID);
        $contentSupply->setAllowAdvertisement(TRUE);

        if (!$contentSupply->isValidContext())
            throw $this->createNotFoundException("GIF Post >> " . $request->getRequestUri() . ' << not found');

        if(strlen($contentSupply->getContextObjectRAW()->getLink()) > 0 && $titleSlug !== $contentSupply->getContextObjectRAW()->getLink() ?? '_cc_x_')
            return $this->redirectToRoute('pageGIFs', ['baseID' => $baseID, 'titleSlug' => $contentSupply->getContextObjectRAW()->getLink(), 'sortSlug' => ''], 302);


        if ($this->client) {
            $this->client->setContentBaseID((int)base_convert($baseID, 36, 10));
        }

        $contentSupply->finalize();
        $adContainer = new AdvertisementContainer($em, $request);
        foreach($contentSupply->getPostContent()->getTagArrayCollection() as $tag)
            $adContainer->addTag($tag);

        return $this->render('content/video.html.twig', [
            'contentSupply' => $contentSupply,
            'client' => $this->client ?? NULL,
            'advertisementContainer' => $adContainer->loadElements( 10 )
        ]);
    }
}
