<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Content;
use AppBundle\Entity\ContentCombination;
use AppBundle\Entity\ContentParameter;
use AppBundle\Entity\Tag;
use AppBundle\Template\ContentBase;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JavaScriptController extends Controller {
    /**
     * @Route("/js/main.js", name="main_js")
     */
    public function jsIAction(Request $request) {
        $data = "var time = ".time().";";

        $response = new Response($data, 200);
        $response->headers->set('Content-Type','application/javascript');

        return $response;
    }
    /**
     * @Route("/js/standard.js", name="jsStandard")
     */
    public function jsSAction(Request $request) {
        return $this->render('content/assets/standard.js.twig');
    }
    /**
     * @Route("/js/footer.js", name="jsFooter")
     */
    public function jsFooterAction() {
        return $this->render('content/assets/standardFooter.js.twig');
    }
}