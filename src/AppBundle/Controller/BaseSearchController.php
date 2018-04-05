<?php
namespace AppBundle\Controller;

use AppBundle\Security\User\Anonym\SessionHandler;
use AppBundle\Security\User\Cookie\GenericHandler;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class BaseSearchController extends Controller {
    private $client;
    private $limit = FALSE;

    public function getLimit(): int {
        if ($this->limit === FALSE)
            $this->limit = $this->container->hasParameter('template') ? $this->getParameter('template')['elementCounter']['related'] ?? 12 : 12;

        return $this->limit;
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
     * @Route("/search", name="searchPageMain")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexSearchAction(Request $request) {
        $search = $request->request->get('search');
        if(empty($search))
            $this->redirectToRoute('searchPageQuery',["slug" => 'new', "contentCat" => 'all']);

        return $this->redirectToRoute('searchPageQuery',["slug" => urlencode($search), "contentCat" => 'all']);
    }

    /**
     * @Route("/search/{contentCat}/{slug}", name="searchPageQuery", requirements={"contentCat":"all|images|videos|gifs|popular|related"} )
     * @param Request $request
     * @param string $slug
     * @param string $contentCat
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function customSearchAction(Request $request, string $slug, string $contentCat) {
        $queryVar = explode('+', $slug);
        for($i = 0; $i < count($queryVar); $i++)
            if(empty($queryVar[$i]))
                unset($queryVar[$i]);
            else
                $queryVar[$i] = urldecode($queryVar[$i]);

        $queryVar = array_values($queryVar);
        if(count($queryVar) > 15)
            for($i = 15; $i < count($queryVar); $i++)
                if(empty($queryVar[$i]))
                    unset($queryVar[$i]);
        $queryVar = array_values($queryVar);

        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getManager();

        $content = new \AppBundle\Tools\Supply\Content($em, $this->getLimit(), $this->client, $request);
        $content->setContextListSearch( $queryVar );
        $content->setAllowAdvertisement(TRUE);

        return $this->render('content/searchResults.html.twig', [
            'contentSupply' => $content->loadElementsInSubCategories()->finalize(),
            'client' => $this->client ?? NULL,
            'searchterm' => implode(' ', $queryVar)
        ]);
    }
}