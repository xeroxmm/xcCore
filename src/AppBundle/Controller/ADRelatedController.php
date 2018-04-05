<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Content;
use AppBundle\Entity\ContentCombination;
use AppBundle\Entity\ContentParameter;
use AppBundle\Entity\Image;
use AppBundle\Entity\Tag;
use AppBundle\Template\ContentBase;
use AppBundle\Tools\Arbitrage\ConfigContainer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ADRelatedController extends Controller {
    /**
     * @Route("/leave", name="ad_net_entry")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function popularAction(Request $request) {
        $time = $request->query->get('id',0);

        if(time() - $time > 60)
            return $this->redirectToRoute('homepage');

        // Load Random Post
        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getEntityManager();
        $res = $em->createQueryBuilder()
                  ->select('cp')
            ->from('AppBundle:ContentParameter', 'cp')
            ->where('cp.isPrivate = 0')->andWhere('cp.type = 1')
            ->setMaxResults(500)->getQuery()->getResult();

        // get Random Post ID
        if(!$res){
            $ID = 0;
            $slug = '';
        } else {
            /** @var $rnd ContentParameter */
            $rnd = $res[mt_rand(0,count($res)-1)];
            $ID = $rnd->getContentObj()->getBasedID();
            $slug = $rnd->getContentObj()->getLink();
        }

        // CookieTest if user is here again
        $cookieTestFailed = $request->cookies->get('_leyka','0') == '1';

        $arbitrageTest = $this->container->hasParameter('arbitrage') ? $this->getParameter('arbitrage') : FALSE;

        if($cookieTestFailed)
            return $this->redirectToRoute('pageImage',['baseID' => $ID, 'titleSlug' => $slug, 'i'=>'1']);
        else if(!$arbitrageTest)
            return $this->redirectToRoute('pageImage',['baseID' => $ID, 'titleSlug' => $slug, 'i'=>'2']);
        else if(!($arbitrage = new ConfigContainer($this->getParameter('arbitrage')))->isEnabled())
            return $this->redirectToRoute('pageImage',['baseID' => $ID, 'titleSlug' => $slug, 'i'=>'3']);

        // set entry cookie
        $nString = $arbitrage->getRandomNetworkID();
        $cookieL = new Cookie('_leyka','1',time() + 60);
        $cookieV = new Cookie('_levchenko', $nString, time() + 60);

        $response = new Response();
        $response->headers->setCookie($cookieL);
        $response->headers->setCookie($cookieV);

        return $this->render('arbitrage/redirectToImage.html.twig',['baseID' => $ID, 'slug' => $slug], $response);

        //return $response;
    }
}