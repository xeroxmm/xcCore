<?php

namespace AppBundle\Controller;

use AppBundle\API\APICommunicator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class APIContentInfoController
 * @Route("/api")
 */
class APIServerInfoQwant extends Controller {
    /**
     * @Route("/info/qwant/json", name="apiInfoQwant")
     * @param Request $request
     * @return JsonResponse
     */
    public function infoQwantStringAction(Request $request) {
        $eCom = new APICommunicator();

        $s = '';
        if($request->get('q',FALSE)){
            $aContext = array(
                'http' => array(
                    'proxy' => 'tcp://147.75.208.102:55095',
                    'request_fulluri' => true,
                ),
            );
            $cxContext = stream_context_create($aContext);
            try {
                $s = file_get_contents(
                    'https://api.qwant.com/api/search/images?count=10&offset=1&size=large&q=' . str_replace(' ', '+', rtrim($request->get('q', ''), ' +')),
                    False,
                    $cxContext
                );
            }catch (\Exception $e){
                $s = $e->getMessage();
            }
        }

        $eCom->setPayload()->asString($s);

        $rp = new JsonResponse($eCom->toArray(), $eCom->getCode());
        if ( TRUE )
            $rp->headers->set('Access-Control-Allow-Origin', 'http://chemnitz.offer-paradise.pw');

        return $rp;
    }
}