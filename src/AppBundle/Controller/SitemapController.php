<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Content;
use AppBundle\Entity\Tag;
use AppBundle\Entity\User;
use AppBundle\Entity\UserSecurity;
use AppBundle\Tools\Sitemap\ContentContainer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends Controller {
    private $sitemapEntries = 5000;

    /**
     * @Route("/sitemap/standard_catalog.xml", name="sitemap_standard_all")
     */
    public function sitemapStandardAllAction(Request $request) {
        /** @var $em EntityManager */
        $contentArray = [];
        $em           = $this->get('doctrine')->getEntityManager();
        $qb           = $em->createQueryBuilder()
                           ->select('cp')
                           ->from('AppBundle:ContentParameter', 'cp')
                           ->where('cp.isPrivate = 0')
                           ->andWhere('cp.type <= 10')
                           ->orderBy('cp.ID', 'ASC')
                           ->getQuery()->getResult();

        if ($qb) {
            $amount = count($qb);
            $pages  = (int)($amount / $this->sitemapEntries) + 1;

            $contentArray = array_fill(0, $pages, TRUE);
        }

        return $this->render('sitemap/standard_all.xml.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
            'content' => $contentArray,
            'urlBase' => $this->generateUrl('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'mainLastMod' => date('Y-m-d', 15000 * ((int)(time() / 15000)))
        ]);
    }

    /**
     * @Route("/sitemap/standard_{id}.xml", name="sitemap_standard_sub")
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function sitemapStandardSubAction(Request $request, $id) {
        if ($id < 1)
            throw $this->createNotFoundException('Sitemap Not Found');

        $id           = (int)$id;
        $contentArray = [];

        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getEntityManager();

        if ($id == 1) {
            // all information about page
            // Startpage
            $startPage = new ContentContainer($this->generateUrl('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL) . '');
            $startPage->setFrequency('hourly');
            $startPage->setLastmod(1000 * ((int)(time() / 1000)));
            $startPage->setPriority(0.8);

            $contentArray[] = $startPage;

            // Tags
            // load Tags from Database including urls
            $qb = $em->createQueryBuilder()
                     ->select('t')
                     ->from('AppBundle:Tag', 't')
                     ->orderBy('t.count', 'DESC')
                     ->getQuery()->getResult();

            if ($qb) {
                foreach ($qb as $tag) {
                    /** @var $tag Tag */
                    $entry = new ContentContainer($this->generateUrl('pageTag', ['tagSlug' => $tag->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL) . '');
                    $entry->setFrequency('daily');
                    $entry->setLastmod(7600 * ((int)(time() / 7600)));
                    $entry->setPriority(0.5);

                    $contentArray[] = $entry;
                }
            }
        } else {
            // all content
            $startOffsetPage = $id - 2;

            $qb = $em->createQueryBuilder()
                     ->select('c,cp')
                     ->from('AppBundle:Content', 'c')
                     ->leftJoin('c.parameterObj', 'cp')
                     ->where('cp.isPrivate = 0')
                     ->andWhere('cp.type <= 10')
                     ->orderBy('c.ID', 'ASC')
                     ->setFirstResult($this->sitemapEntries * $startOffsetPage)
                     ->setMaxResults($this->sitemapEntries)
                     ->getQuery()->getResult();
            if ($qb) {
                foreach ($qb as $content) {
                    /** @var $content Content */
                    $route = (($content->getParameterObj()->getType() == \AppBundle\Safety\Types\Content::TYPE_IMAGE) ? 'pageImage' : (($content->getParameterObj()->getType() == \AppBundle\Safety\Types\Content::TYPE_VIDEO) ? 'pageVideo' : 'pageCollection')); ///i/{baseID}/{titleSlug}
                    $url   = $this->generateUrl($route, ['baseID' => $content->getBasedID(), 'titleSlug' => $content->getLink()], UrlGeneratorInterface::ABSOLUTE_URL);

                    $entry = new ContentContainer($url . '');
                    $entry->setFrequency('monthly');
                    $entry->setLastmod($content->getParameterObj()->getTimestamp());
                    $entry->setPriority(0.3);

                    $contentArray[] = $entry;
                }
            } else
                throw $this->createNotFoundException('Sitemap Not Found');
        }

        return $this->render('sitemap/standard_sub.xml.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
            'content' => $contentArray
        ]);
    }

    /**
     * @Route("/robots.txt", name="robotsTXT")
     */

    public function robotsTXTAction() {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');
        $response->sendHeaders();

        return $this->render('sitemap/robots.txt.twig', [], $response);
    }
}