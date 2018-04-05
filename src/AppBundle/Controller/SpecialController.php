<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Content;
use AppBundle\Entity\ContentCombination;
use AppBundle\Entity\ContentParameter;
use AppBundle\Entity\Image;
use AppBundle\Entity\Tag;
use AppBundle\Template\ContentBase;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SpecialController extends Controller {
    /**
     * @Route("/splash.php", name="splashExoClicks")
     */
    public function exoClickFallBack() {
        return new JsonResponse(['time'=>time()]);
    }
    /**
     * @Route("/gallery.php", name="galleryPHP")
     */
    public function galleryPHPAction() {
        return $this->redirectToRoute('homepage');
    }
    /**
     * @Route("/popular", name="popularPage")
     */
    public function popularAction(Request $request) {
        // Build New Search Repository
        $em = $this->get('doctrine')->getEntityManager();

        // Load NEW Content
        /** @var $qb ArrayCollection */
        $qb = $em->createQueryBuilder()
                 ->select('c,t,u,cp,cm')
                 ->from('AppBundle:ContentParameter','cp')
                 ->leftJoin('cp.contentObj','c')
                 ->leftJoin('cp.userObj','u')
                 ->leftJoin('c.thumbnailObj','t')
                 ->leftJoin('c.contentMeta','cm')
                 ->where('cp.isBulk = 0')
                 ->andWhere('cp.isSFW = 1')
                 ->andWhere('cp.isPrivate = 0')
                 ->orderBy('cp.timestamp','DESC')
                 ->setMaxResults(24)
                 ->getQuery()->getResult();

        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
            'content' => $qb,
            'e' => 'error string'
        ]);
    }

    /**
     * @Route("/img/t/0/0/0/0/0.jpeg", name="mainImg_zero")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function hookerImageAction(Request $request) {
        $dir = $this->getParameter('tool')['images']['parameter'][0]['dir'] ?? FALSE;
        if(!$dir)
            throw $this->createNotFoundException('DIR not found!');

        /** @var $em EntityManager */
        $em = $this->get('doctrine')->getEntityManager();
        $res = $em->createQueryBuilder()
           ->select('i')
            ->from('AppBundle:Image','i')
            ->where('i.mime = :mime')
            ->setParameter('mime','jpeg')
            ->orderBy('i.ID','ASC')
            ->setMaxResults('20')
            ->getQuery()->getResult();

        if(!$res){
            throw $this->createNotFoundException('Image Not Found!');
        }

        foreach($res as $image){
            /** @var $image Image */
            $file = $dir.'/'.$image->getURL().'.'.$image->getMime();

            if(file_exists($file)){
                $type = 'image/jpeg';
                header('Content-Type:'.$type);
                header('Content-Length: ' . filesize($file));
                readfile($file);
                exit;
                copy($file,$dir.'/0/0/0/0/0.jpeg');
                return $this->redirectToRoute('mainImg_zero');
            }
        }

        throw $this->createNotFoundException('No Images found!');
    }

    /**
     * @Route("/ix/loc/ui/rt", name="martinsRandomPostsAsJSON")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function martinsRandomPostsAsJSONAction(Request $request) {
        $i = 0;
        $response = [];
        $counter = $request->query->getInt('amount',10);

        $em = $this->get('doctrine')->getEntityManager();

        // Load NEW Content
        /** @var $qb ArrayCollection */
        $qb = $em->createQueryBuilder()
                 ->select('c,cp')
                 ->from('AppBundle:Content', 'c')
                 ->leftJoin('c.parameterObj', 'cp')
                 //->leftJoin('cp.userObj', 'u')
                 //->leftJoin('c.thumbnailObj', 't')
                 //->leftJoin('c.contentMeta', 'cm')
                 ->where('cp.isBulk = 0')
                 ->andWhere('cp.type < 10')
                 ->andWhere('cp.isSFW = 1')
                 ->andWhere('cp.isPrivate = 0')
                 ->orderBy('cp.timestamp', 'DESC')
                 ->setMaxResults(500)
                 ->getQuery()->getResult();

        $result = $qb;
        /** @var $result Content[] $z */
        $z = count($result);
        shuffle($result);

        while($i < $counter && $i < $z){
            $response[] = $result[$i]->getBasedID().'/'.$result[$i]->getLink();
            $i++;
        }

        return new \Symfony\Component\HttpFoundation\Response(implode('|',$response),200);
    }
}