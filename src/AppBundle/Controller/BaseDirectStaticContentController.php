<?php

namespace AppBundle\Controller;

use AppBundle\API\APICommunicator;
use AppBundle\Entity\Message;
use AppBundle\Entity\User;
use AppBundle\Forms\Contact\DMCAType;
use AppBundle\Forms\Contact\WebmasterType;
use AppBundle\Safety\Types\Api;
use AppBundle\Security\User\Anonym\SessionHandler;
use AppBundle\Tools\Voting\ScoreCalculator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Class StaticContentController
 */
class BaseDirectStaticContentController extends Controller {
    /**
     * @Route("/privacy", name="staticPrivacy")
     */
    public function privacyAction() {
        return $this->render('content/static/privacy.html.twig', []);
    }

    /**
     * @Route("/TOS", name="staticTOS")
     */
    public function TOSAction() {
        return $this->render('content/static/tos.html.twig', []);
    }

    /**
     * @Route("/partner", name="staticContentPartner")
     */
    public function contentPartnerAction() {
        return $this->render('content/static/partner.html.twig', []);
    }

    /**
     * @Route("/advertisement", name="staticContentAdvertisement")
     */
    public function contentAdvertisementAction() {
        return $this->render('content/static/advertisement.html.twig', []);
    }

    /**
     * @Route("/dmca", name="staticContactDMCA")
     * @param Request $request
     * @param \Swift_Mailer $mailer
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ContactDMCAAction(Request $request, \Swift_Mailer $mailer) {
        $message = new Message();

        $form = $this->createForm(DMCAType::class, $message);
        $form->add('goto', HiddenType::class, ['data' => $request->getScheme() . '://' . $request->getHttpHost()]);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $brand = $this->container->get('twig')->getGlobals()['brand'] ?? $this->getParameter('database_name');

            $message->setIp( $request->getClientIp() );

            /*$em = $this->getDoctrine()->getManager();
            $em->persist( $message );
            $em->flush();*/

            $to = FALSE;
            $toArr = $this->getParameter('email')['contactCollector'] ?? FALSE;
            $to = $toArr[0] ?? FALSE;

            if($to !== FALSE){
                unset($toArr[0]);
                /** @var $toArr array */

                $messageX = (new \Swift_Message('!!! DMCA ALERT !!! ' . $brand))
                    ->setFrom($this->getParameter('database_name').'@offer-paradise.pw', $brand)
                    ->setTo(''.$to)
                    ->setBody(
                        '' . $message->getMailString(),
                        'text/html'
                    );

                if(count($toArr) >= 1){
                    foreach($toArr as $a)
                        $messageX->addBcc($a);
                }

                $mailer->send($messageX);

                return $this->redirect($message->getGoto().'/thankyou');
            }
        } else if($form->isSubmitted() && !$form->isValid()){
            return $this->redirect($message->getGoto().'/dmca');
        }

        return $this->render('content/static/dmca.html.twig',
                             ['form' => $form->createView()]);
    }

    /**
     * @Route("/thankyou", name="staticContactSuccessful")
     */
    public function ContactSuccessfulAction() {
        return $this->render('content/static/contactSuccessful.html.twig', []);
    }

    /**
     * @Route("/contact", name="staticContactWebmaster")
     * @param Request $request
     * @param \Swift_Mailer $mailer
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contactWebmasterAction(Request $request, \Swift_Mailer $mailer) {
        $message = new Message();
        $form = $this->createForm(WebmasterType::class, $message);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $brand = $this->container->get('twig')->getGlobals()['brand'] ?? $this->getParameter('database_name');

            $message->setIp( $request->getClientIp() );

            /*$em = $this->getDoctrine()->getManager();
            $em->persist( $message );
            $em->flush();*/

            $to = FALSE;
            $toArr = $this->getParameter('email')['contactCollector'] ?? FALSE;
            $to = $toArr[0] ?? FALSE;

            if($to !== FALSE){
                unset($toArr[0]);
                /** @var $toArr array */

                $message = (new \Swift_Message('New Message from ' . $brand))
                    ->setFrom($this->getParameter('database_name').'@offer-paradise.pw', $brand)
                    ->setTo(''.$to)
                    ->setBody(
                        '' . $message->getMailString(),
                        'text/html'
                    );

                if(count($toArr) >= 1){
                    foreach($toArr as $a)
                        $message->addBcc($a);
                }

                $mailer->send($message);

                return $this->redirectToRoute('staticContactSuccessful');
            }
        }

        return $this->render('content/static/webmasterContact.html.twig',
                             ['form' => $form->createView()]);
    }
}