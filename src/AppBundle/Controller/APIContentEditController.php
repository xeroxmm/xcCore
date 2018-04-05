<?php

namespace AppBundle\Controller;

use AppBundle\API\APICommunicator;
use AppBundle\Crawler\Crawler;
use AppBundle\Entity\Content;
use AppBundle\Entity\ContentCombination;
use AppBundle\Entity\ContentMeta;
use AppBundle\Entity\ContentParameter;
use AppBundle\Entity\Image;
use AppBundle\Entity\Tag;
use AppBundle\Entity\TagMeta;
use AppBundle\Entity\Thumbnail;
use AppBundle\Entity\UserSecurity;
use AppBundle\Entity\Video;
use AppBundle\Safety\Content\CollectionCreator;
use AppBundle\Safety\Content\ContentHarmonize;
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
 * Class APIContentInfoController
 * @Route("/api")
 * @Method({"POST"})
 */
class APIContentEditController extends Controller {
    /**
     * @Route("/content/edit/post/tag/delete", name="apiEditPostTagDelete")
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteTagFromPostAction(Request $request) {
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
        $tagSlug = ContentHarmonize::getSlugOfString( $request->request->get('tagname', ''));
        $postID = $request->request->get('postid', 0);

        do {
            if (!$io->isReadyForAPI()) {
                $eCom->setError()->noPayload();
                break;
            }

            if(empty($tagSlug) || $postID < 1){
                $eCom->setError()->noPayload();
                break;
            }

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }

            $qb = $em->getEntityManager()->createQueryBuilder()
                     ->select('c')
                     ->from('AppBundle:Content','c')
                     ->leftJoin('c.tagArray','t')
                     ->where('c.ID = :postID')
                     ->setParameter('postID',$postID)
                     ->getQuery()->getOneOrNullResult();

            $flush = false;
            if($qb){
                /** @var $qb Content*/
                foreach($qb->getTagArray() as $k => $t){
                    /** @var $t Tag */
                    if($t->getSlug() != $tagSlug)
                        continue;

                    $qb->getTagArrayCollection()->remove( $k );
                    $em->getManager()->flush();
                    $flush = true;
                    break;
                }
            }

            $headers = TRUE;
            $eCom->setPayload()->asBool( $flush );
        } while (FALSE);

        $rp = new JsonResponse($eCom->toArray(), $eCom->getCode());
        if($headers)
            $rp->headers->set('Access-Control-Allow-Origin','http://chemnitz.offer-paradise.pw');

        return $rp;
    }
    /**
     * @Route("/content/edit/post/tag/add", name="apiEditPostTagAdd")
     * @param Request $request
     * @return JsonResponse
     */
    public function addTagFromPostAction(Request $request) {
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
        $tagName = $request->request->get('tagname', '');
        $tagSlug = ContentHarmonize::getSlugOfString( $tagName );
        $postID = $request->request->get('postid', 0);

        do {
            if (!$io->isReadyForAPI()) {
                $eCom->setError()->noPayload();
                break;
            }

            if(empty($tagSlug) || $postID < 1){
                $eCom->setError()->noPayload();
                break;
            }

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }

            $qb = $em->getEntityManager()->createQueryBuilder()
                     ->select('c')
                     ->from('AppBundle:Content','c')
                     ->leftJoin('c.tagArray','t')
                     ->where('c.ID = :postID')
                     ->setParameter('postID',$postID)
                     ->getQuery()->getOneOrNullResult();

            $flush = false;
            if($qb){
                /** @var $qb Content*/

                $tagQb = $em->getEntityManager()->createQueryBuilder()
                            ->select('t')
                            ->from('AppBundle:Tag','t')
                            ->where('t.slug = :slug')
                            ->setParameter('slug',$tagSlug)
                            ->getQuery()->getOneOrNullResult();
                if(!$tagQb){
                    try {
                        $tagQb = new Tag($tagName, $tagSlug);
                        $em->getManager()->persist($tagQb);
                        $em->getManager()->flush();
                    } catch (\Exception $e){
                        echo $e->getMessage();
                        die();
                    }
                }

                $qb->getTagArrayCollection()->add( $tagQb );
                $em->getManager()->flush();
                $flush = true;
            }

            $headers = TRUE;
            $eCom->setPayload()->asBool( $flush );
        } while (FALSE);

        $rp = new JsonResponse($eCom->toArray(), $eCom->getCode());
        if($headers)
            $rp->headers->set('Access-Control-Allow-Origin','http://chemnitz.offer-paradise.pw');

        return $rp;
    }
    /**
     * @Route("/content/edit/post/title", name="apiEditPostTitle")
     * @param Request $request
     * @return JsonResponse
     */
    public function editTitleFromPostAction(Request $request) {
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
        $title = $request->request->get('title', '');
        $postID = $request->request->get('postid', 0);

        do {
            if (!$io->isReadyForAPI()) {
                $eCom->setError()->noPayload();
                break;
            }

            if(empty($title) || $postID < 1){
                $eCom->setError()->noPayload();
                break;
            }

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }

            $qb = $em->getEntityManager()->createQueryBuilder()
                     ->select('c')
                     ->from('AppBundle:Content','c')
                     ->leftJoin('c.tagArray','t')
                     ->where('c.ID = :postID')
                     ->setParameter('postID',$postID)
                     ->getQuery()->getOneOrNullResult();

            $flush = false;
            if($qb){
                /** @var $qb Content*/
                $qb->setTitle( $title );
                $qb->setLink( ContentHarmonize::getSlugOfString( $title ));

                $em->getManager()->flush();
                $flush = true;
            }

            $headers = TRUE;
            $eCom->setPayload()->asBool( $flush );
        } while (FALSE);

        $rp = new JsonResponse($eCom->toArray(), $eCom->getCode());
        if($headers)
            $rp->headers->set('Access-Control-Allow-Origin','http://chemnitz.offer-paradise.pw');

        return $rp;
    }
    /**
     * @Route("/content/edit/post/description", name="apiEditPostDescription")
     * @param Request $request
     * @return JsonResponse
     */
    public function editDescriptionFromPostAction(Request $request) {
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
        $description = $request->request->get('descr', '');
        $postID = $request->request->get('postid', 0);

        do {
            if (!$io->isReadyForAPI()) {
                $eCom->setError()->noPayload();
                break;
            }

            if(empty($description) || $postID < 1){
                $eCom->setError()->noPayload();
                break;
            }

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }

            $qb = $em->getEntityManager()->createQueryBuilder()
                     ->select('c')
                     ->from('AppBundle:Content','c')
                     ->leftJoin('c.tagArray','t')
                     ->where('c.ID = :postID')
                     ->setParameter('postID',$postID)
                     ->getQuery()->getOneOrNullResult();

            $flush = false;
            if($qb){
                /** @var $qb Content*/
                $qb->setDescription( $description );

                $em->getManager()->flush();
                $flush = true;
            }

            $headers = TRUE;
            $eCom->setPayload()->asBool( $flush );
        } while (FALSE);

        $rp = new JsonResponse($eCom->toArray(), $eCom->getCode());
        if($headers)
            $rp->headers->set('Access-Control-Allow-Origin','http://chemnitz.offer-paradise.pw');

        return $rp;
    }
    /**
     * @Route("/content/edit/post/private", name="apiEditPostPrivate")
     * @param Request $request
     * @return JsonResponse
     */
    public function editPrivateFromPostAction(Request $request) {
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
        $postID = $request->request->get('postid', 0);

        do {
            if (!$io->isReadyForAPI()) {
                $eCom->setError()->noPayload();
                break;
            }

            if($postID < 1){
                $eCom->setError()->noPayload();
                break;
            }

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }

            $qb = $em->getEntityManager()->createQueryBuilder()
                     ->select('c,p')
                     ->from('AppBundle:Content','c')
                     ->leftJoin('c.parameterObj','p')
                     ->where('c.ID = :postID')
                     ->setParameter('postID',$postID)
                     ->getQuery()->getOneOrNullResult();

            $flush = false;
            if($qb){
                /** @var $qb Content*/
                $qb->getParameterObj()->setIsPrivate( TRUE );

                $em->getManager()->flush();
                $flush = true;
            }

            $headers = TRUE;
            $eCom->setPayload()->asBool( $flush );
        } while (FALSE);

        $rp = new JsonResponse($eCom->toArray(), $eCom->getCode());
        if($headers)
            $rp->headers->set('Access-Control-Allow-Origin','http://chemnitz.offer-paradise.pw');

        return $rp;
    }
    /**
     * @Route("/content/edit/tag/change", name="apiEditTagChange")
     * @param Request $request
     * @return JsonResponse
     */
    public function changeTagInfoAction(Request $request) {
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
        //$tagSlug = ContentHarmonize::getSlugOfString($request->request->get('tagname', ''));
        $tagID  = $request->request->get('tagid', 0);
        $tagDescription = $request->request->get('descr', '');
        $tagLabel = $request->request->get('label', '');

        do {
            if (!$io->isReadyForAPI()) {
                $eCom->setError()->noPayload();
                break;
            }

            if ($tagID < 1 || empty($tagLabel)) {
                $eCom->setError()->noPayload();
                break;
            }

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }

            $headers = TRUE;

            $tag = $em->getEntityManager()->createQueryBuilder()
                ->select('t,m')
                ->from('AppBundle:Tag','t')
                ->leftJoin('t.tagMeta','m')
                ->where('t.ID = :tID')
                ->setParameter('tID',$tagID)
                ->getQuery()->getOneOrNullResult();
            if(!$tag)
                break;

            $flush = FALSE;
            /** @var $tag Tag */
            if($tag->getLabel() != trim($tagLabel)){
                $tag->setLabel( $tagLabel );
                $flush = TRUE;
            }
            if(!($tag->getMetaObj() ?? FALSE) && strlen($tagDescription) > 3){
                $newMeta = new TagMeta();
                $newMeta->setDescription( trim($tagDescription) );
                $newMeta->setTagObj( $tag );

                $em->getManager()->persist( $newMeta );
                $em->getManager()->flush();

                $tag->setMetaObj( $newMeta );
                $flush = TRUE;
            } else if($tag->getMetaObj()->getDescription() != trim($tagDescription) ){
                $tag->getMetaObj()->setDescription( trim($tagDescription) );

                $flush = TRUE;
            }
            if($flush){
                $em->getManager()->persist( $tag );
                $em->getManager()->flush();
            }
            $eCom->setPayload()->asBool( TRUE );
        } while( FALSE );

        $rp = new JsonResponse($eCom->toArray(), $eCom->getCode());
        if($headers)
            $rp->headers->set('Access-Control-Allow-Origin','http://chemnitz.offer-paradise.pw');

        return $rp;
    }
    /**
     * @Route("/content/edit/post/image", name="apiEditPostChangeImage")
     * @param Request $request
     * @return JsonResponse
     */
    public function editImageFromPostAction(Request $request) {
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
        $postID = $request->request->get('postid', 0);

        do {
            if (!$io->isReadyForAPI()) {
                $eCom->setError()->noPayload();
                break;
            }

            if($postID < 1){
                $eCom->setError()->noPayload();
                break;
            }

            if (!$userSecurities) {
                $eCom->setError()->noCredentials();
                break;
            }

            $qb = $em->getEntityManager()->createQueryBuilder()
                     ->select('c,t')
                     ->from('AppBundle:Content','c')
                     ->leftJoin('c.thumbnailObj','t')
                     ->where('c.ID = :postID')
                     ->setParameter('postID',$postID)
                     ->getQuery()->getOneOrNullResult();

            $flush = false;
            if($qb){
                do {
                    // crawl Image
                    // PARSE URL
                    $crw = new Crawler($request->request->get('newurl', ''));

                    // try to save image in tmp and try to copy image to specific folder on HDD
                    if (!$crw->crwImage($this->getParameter('tool')['images']['tmp_dir'] . '')) {
                        $eCom->setError()->wrongURL($io->getUrl());
                        break;
                    }

                    $create = new ImageCreator($em->getEntityManager());
                    try {
                        // create new image if thumbnailObj is null
                        if ($qb->getThumbnailObj() === NULL) {
                            if (!($create->storeInDBOnlyImageInformation($crw, $this->getParameter('tool'), NULL, FALSE))) {
                                $eCom->setError()->noImageInfo($io->getUrl());
                                break;
                            }
                        } else {
                            if (!($create->storeInDBOnlyImageInformation($crw, $this->getParameter('tool'), $qb->getThumbnailObj(), TRUE))) {
                                $eCom->setError()->noImageInfo($io->getUrl());
                                break;
                            }
                        }

                    } catch(\Exception $e){
                        echo $e->getMessage();
                    }
                    $flush = true;
                } while( FALSE );
            }

            $headers = TRUE;
            $eCom->setPayload()->asString( "/" . $this->getParameter('asset_smallImage_folder') . "/" . $create->getRawImageEntity()->getThumbnailLinkURL() );
        } while (FALSE);

        $rp = new JsonResponse($eCom->toArray(), $eCom->getCode());
        if($headers)
            $rp->headers->set('Access-Control-Allow-Origin','http://chemnitz.offer-paradise.pw');

        return $rp;
    }
}