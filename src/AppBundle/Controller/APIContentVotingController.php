<?php

namespace AppBundle\Controller;

use AppBundle\API\APICommunicator;
use AppBundle\Entity\User;
use AppBundle\Safety\Types\Api;
use AppBundle\Security\User\Anonym\SessionHandler;
use AppBundle\Tools\Voting\ScoreCalculator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Class APIRelatedController
 * @Route("/api/collect")
 * @Method({"POST"})
 */
class APIContentVotingController extends Controller {
    private $client;
    private $apiCommunicator;

    public function __construct(EntityManagerInterface $em) {
        $session               = new Session();
        $this->client          = new SessionHandler($session, $em);
        $this->apiCommunicator = new APICommunicator();
    }

    /**
     * @Route("/data", name="apiCollectData")
     */
    public function aCDAction(Request $request) {
        /** @var $user User */
        $user = $this->getUser();

        //
        // OKAY ! -> User is registered and has got a fingerprint
        //
        if($user && $this->client->hasFingerprint()){
            $this->apiCommunicator->setPayload()->asBool(TRUE);
            $this->apiCommunicator->setInfo()->setValue('f'.$user->getID());

            return $this->apiCommunicator->doResponse();
        }
        //
        // ATTENTION ! -> New User Session - we do not need this at this place!
        //
        if ($this->client->isNewSession()) {
            $this->apiCommunicator->setError()->noPayload();
            return $this->apiCommunicator->doResponse();
        }

        if (!$this->client->hasFingerprint()) {
            if (!$this->client->setBrowserFingerPrint($request->request->get(Api::TYPE_POST_KEY_FINGERPRINT, FALSE))) {
                $this->apiCommunicator->setError()->noCredentials();
                return $this->apiCommunicator->doResponse();
            }

            // register new User only, if this user is not registered


            $this->client->registerNewUser( $request->getClientIp() );
            if($this->client->getUserID()){
                $providerKey = 'main';
                $token = new UsernamePasswordToken(
                    $this->client->getUserByID(),
                    null, $providerKey,
                    $this->client->getUserByID()->getRoles()
                );
                $this->get('security.token_storage')->setToken($token);

                $loginEvent = new InteractiveLoginEvent($request, $token);
                $this->get("event_dispatcher")->dispatch("security.interactive_login", $loginEvent);
            }
        }
        $this->apiCommunicator->setPayload()->asBool(TRUE);
        return $this->apiCommunicator->doResponse();
    }

    /**
     * @Route("/heartbeat", name="apiContentView")
     */
    public function aCVAction(Request $request) {
        $pid = $request->request->get(Api::TYPE_POST_KEY_PAGE_ID, 0);
        $puid = $request->request->get(Api::TYPE_POST_KEY_PAGE_UID,'_');

        if ($this->client->isNewSession()) {
            $this->apiCommunicator->setError()->noPayload();
            return $this->apiCommunicator->doResponse();
        }
        if (!$this->client->hasFingerprint()) {
            $this->apiCommunicator->setError()->noCredentials();
            return $this->apiCommunicator->doResponse();
        }
        if(!$this->getUser()){
            $this->apiCommunicator->setError()->noDatabaseFin('c2');
            return $this->apiCommunicator->doResponse();
        }
        if (!$this->client->isValidBrowserView($pid, $puid)) {
            $this->apiCommunicator->setError()->wrongURL('x6');
            return $this->apiCommunicator->doResponse();
        }

        if(!$this->client->isValidBrowserHeartbeat($pid, $puid, $this->getUser())){
            $this->apiCommunicator->setError()->wrongURL('x9');
            return $this->apiCommunicator->doResponse();
        }



        //$this->apiCommunicator->setPayload()->asString(print_r($this->client->getRawDeliveredSitesArray(),1));
        $this->apiCommunicator->setPayload()->asBool(TRUE);

        return $this->apiCommunicator->doResponse();
    }

    /**
     * @Route("/like", name="apiContentRateLike")
     */
    public function aCLkAction(Request $request) {
        $pid = $request->request->get(Api::TYPE_POST_KEY_PAGE_ID, 0);
        $puid = $request->request->get(Api::TYPE_POST_KEY_PAGE_UID,'_');
        //
        //  Check if USER and if FINGERPRINT
        //
        if(!$this->getUser()){
            $this->apiCommunicator->setError()->noDatabaseFin('c2');
            return $this->apiCommunicator->doResponse();
        }
        if (!$this->client->hasFingerprint()) {
            $this->apiCommunicator->setError()->noCredentials();
            return $this->apiCommunicator->doResponse();
        }
        if($this->getUser() && $this->client->hasFingerprint() && $this->client->isValidLikeAction($pid, $puid, $this->getUser(), TRUE)){
            $this->apiCommunicator->setPayload()->asBool(TRUE);
        } else
            $this->apiCommunicator->setError()->noDatabaseFin('x9');

        return $this->apiCommunicator->doResponse();
    }
    /**
     * @Route("/dislike", name="apiContentRateDisLike")
     */
    public function aCDkAction(Request $request) {
        $pid = $request->request->get(Api::TYPE_POST_KEY_PAGE_ID, 0);
        $puid = $request->request->get(Api::TYPE_POST_KEY_PAGE_UID,'_');
        //
        //  Check if USER and if FINGERPRINT
        //
        if($this->getUser() && $this->client->hasFingerprint() && $this->client->isValidLikeAction($pid, $puid, $this->getUser(), FALSE)){
            $this->apiCommunicator->setPayload()->asBool(TRUE);
        } else
            $this->apiCommunicator->setError()->noDatabaseFin('c2');

        return $this->apiCommunicator->doResponse();
    }
    /**
     * @Route("/love", name="apiContentLoveLike")
     */
    public function aCLvAction(Request $request) {
        $pid = $request->request->get(Api::TYPE_POST_KEY_PAGE_ID, 0);
        $puid = $request->request->get(Api::TYPE_POST_KEY_PAGE_UID,'_');
        //
        //  Check if USER and if FINGERPRINT
        //
        if($this->getUser() && $this->client->hasFingerprint() && $this->client->isValidLoveAction($pid, $puid, $this->getUser())){
            $this->apiCommunicator->setPayload()->asBool(TRUE);
        } else
            $this->apiCommunicator->setError()->noDatabaseFin('c2');

        return $this->apiCommunicator->doResponse();
    }
}