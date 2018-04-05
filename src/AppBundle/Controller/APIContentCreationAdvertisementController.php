<?php

namespace AppBundle\Controller;

use AppBundle\API\APICommunicator;
use AppBundle\Crawler\Crawler;
use AppBundle\Entity\UserSecurity;

use AppBundle\Safety\Content\AdvertisementLinkCreator;
use AppBundle\Safety\Content\DataParser;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class APIContentCreationAdvertisementController
 * @Route("/api/advertisement/create")
 * @Method({"POST"})
 */
class APIContentCreationAdvertisementController extends Controller {
    /**
     * @Route("/{tradeSlug}/url", name="apiAdvertisementCreateTradeURL", requirements={"tradeSlug": "\w+"})
     * @param Request $request
     * @param string $tradeSlug
     * @return JsonResponse
     */
    public function createTradeURLasAction(Request $request, string $tradeSlug = '') {
        $eCom = new APICommunicator();
        $em   = $this->getDoctrine();

        if(!\AppBundle\Safety\Types\Content::isAdvertisementSlug( $tradeSlug ))
            return $eCom->doResponse();

        do {
            // CHECK IF CREDENTIALS ARE GIVEN
            $io = new DataParser();
            $io->parseByRequestObj($request);
            $io->setType(\AppBundle\Safety\Types\Content::getAdvertisementIntBySlug( $tradeSlug ));

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

            $cLink = new AdvertisementLinkCreator($em->getEntityManager());
            $cLink->setTYPEbySlug( $tradeSlug );
            $io->setTitleIfEmpty( $crw->getSubCrawl()->getTitle() );

            if (!$cLink->storeInDB($userSecurities->getUserObj(),
                                  $io,
                                  $crw,
                                  $this->getParameter('tool'))) {
                $eCom->setError()->noDatabaseFin($cLink->getError());
                break;
            }

            if ($cLink === FALSE) {
                $eCom->setError()->noContentID();
                break;
            }

            $eCom->setPayload()->contentRelated()->setTitle($io->getTitle());
            // set status true
            break;
        } while (FALSE);

        return new JsonResponse($eCom->toArray(), $eCom->getCode());
    }
}