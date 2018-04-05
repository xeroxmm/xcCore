<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ContentParameter;
use AppBundle\Safety\Types\Content;
use AppBundle\Security\User\Anonym\SessionHandler;
use AppBundle\Security\User\Cookie\GenericHandler;
use AppBundle\Template\ContentContainer;
use AppBundle\Tools\Advertisement\ContentDeliver;
use AppBundle\Tools\Supply\Cookies;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class BaseMainController extends Controller {
    private $limitLooped = FALSE;
    private $limit = FALSE;
    private $client;

    public function __construct(RequestStack $requestStack = NULL) {
        $session      = new Session();
        $this->client = new SessionHandler($session);

        if($requestStack === NULL)
            return;
        $this->client->addReferrer( $requestStack );
        /*if ($this->client->isNewSession()) {
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
        }*/
    }

    public function getLimitRelated():int{
        if($this->limit === FALSE)
            $this->limit = $this->container->hasParameter('template') ? $this->getParameter('template')['elementCounter']['related'] ?? 12 : 12;

        return $this->limit;
    }

    public function getLimitLooped(): int {
        if ($this->limitLooped === FALSE)
            $this->limitLooped = $this->container->hasParameter('template') ? $this->getParameter('template')['elementCounter']['looped'] ?? 12 : 12;

        return $this->limitLooped;
    }

    /**
     * @Route("/{postType}/{sortSlug}", name="homepageImagesPopular", defaults={"sortSlug" = "", "postType" = ""}, requirements={"postType"="images|gifs|collections", "sortSlug" = "popular"})
     * @param Request $request
     * @param String $sortSlug
     * @param null|string $postType
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexImagesAction(Request $request, ?string $sortSlug, ?string $postType) {
        if(empty($sortSlug)&&empty($postType))
            return $this->indexAction($request);

        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getManager();

        $pValue = max($request->query->getInt('page', 0), 1);

        $content = new \AppBundle\Tools\Supply\Content($em, $this->getLimitLooped(), new SessionHandler(new Session()), $request);
        $content->setContextListMain();
        $content->setAllowAdvertisement(TRUE);
        $content->setLimit( 36 );

        return $this->render('default/index.html.twig', [
            'contentSupply' => $content->finalize(),
            'content' => $sortSlug,
            'postType' => $postType,
            'nextPage' => $pValue + 1,
            'thisURL' => '/'.$postType. (!empty($sortSlug) ? '/' . $sortSlug : ''),
            'isSemi' => TRUE
        ]);
    }

    /**
     * @Route("/popular", name="homepageSorted")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexSortedAction(Request $request) {
        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getManager();

        $pValue = max($request->query->getInt('page', 0), 1);

        $content = new \AppBundle\Tools\Supply\Content($em, $this->getLimitLooped(), new SessionHandler(new Session()), $request);
        $content->setContextListMain();
        $content->setAllowAdvertisement(TRUE);
        $content->setLimit(36);

        return $this->render('default/index.html.twig', [
            'contentSupply' => $content->finalize(),
            'content' => FALSE,
            'nextPage' => $pValue + 1,
            'postType' => 'popular',
            'thisURL' => '/popular',
            'isSemi' => TRUE
        ]);
    }

    /**
     * @Route("/", name="homepage")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request) {
        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getManager();

        $pValue = max($request->query->getInt('page', 0), 1);

        $content = new \AppBundle\Tools\Supply\Content($em, $this->getLimitLooped(), new SessionHandler(new Session()), $request);
        $content->setContextListMain();
        $content->setAllowAdvertisement(TRUE);

        if($this->container->hasParameter('template_loop_content_amount') && $this->getParameter('template_loop_content_amount') > 12)
            $content->setLimit( $this->getParameter('template_loop_content_amount') );

        return $this->render('default/index.html.twig', [
            'contentSupply' => $content->finalize(),
            'content' => FALSE,
            'postType' => 'home',
            'nextPage' => $pValue + 1,
            'thisURL' => rtrim($this->generateUrl('homepage',[], 1),'/'),
            'semi' => FALSE
        ]);
    }

    /**
     * @Route("/debugX", name="debugX")
     */
    public function debugXAction() {
        // require_once '/usr/local/lsws/SITES/creezi.s.com/cmd/content/collectionPinger.php';

        return new JsonResponse([]);
    }
}
