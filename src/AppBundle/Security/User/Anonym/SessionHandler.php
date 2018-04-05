<?php

namespace AppBundle\Security\User\Anonym;

use AppBundle\Entity\Content;
use AppBundle\Entity\ContentLikeUserRelation;
use AppBundle\Entity\ContentViewDistillerFramed;
use AppBundle\Entity\ContentViewDistillerGlobal;
use AppBundle\Entity\ContentViewUserRelation;
use AppBundle\Entity\FingerprintInfo;
use AppBundle\Entity\User;
use AppBundle\Entity\UserFingerprintSession;
use AppBundle\Entity\UserSecurityControl;
use AppBundle\Entity\UserSessions;
use AppBundle\Template\VoteObject;
use AppBundle\Tools\Supply\ContentStream;
use AppBundle\Tools\Supply\ContentStreamContainer;
use AppBundle\Tools\Voting\ScoreCalculator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionHandler {
    private $session;
    private $newFingerprint;
    private $badFingerprint;

    private $em;
    private $fingerPrint;
    private $cookieIdentifierID;
    private $sessionID;
    private $isNewSession;

    private $userID;
    private $contentBaseID;
    private $hasRealUserObject;
    private $userObjID;
    /** @var  ArrayCollection */
    private $rawReferrers;
    /** @var  ArrayCollection */
    private $rawDeliveredSites;
    /** @var  ArrayCollection */
    private $jsHeartbeatedSites;
    /** @var  ArrayCollection */
    private $likedSites;
    /** @var  ArrayCollection */
    private $lovedSites;
    /** @var  ArrayCollection */
    private $dislikedSites;
    /** @var  ArrayCollection */
    private $timeFramedSites;
    /** @var  ContentStream */
    private $contentStream;

    private const SESSION_KEY_UNIQUE_COOKIE_ID  = 'uniqueID';
    private const SESSION_KEY_UNIQUE_SESSION_ID = 'sessionID';
    private const SESSION_KEY_FINGERPRINT       = 'fp';
    private const SESSION_KEY_CONTENT_STREAM    = 'contentStream';
    private const SESSION_KEY_RAW_SITES         = 'rawSites';
    private const SESSION_KEY_JS_SITES          = 'jsSites';
    private const SESSION_KEY_LIKED_SITES       = 'likedSites';
    private const SESSION_KEY_DISLIKED_SITES    = 'disLikedSites';
    private const SESSION_KEY_LOVED_SITES       = 'lovedSites';
    private const SESSION_KEY_FRAMED_SITES      = 'framedSites';
    private const SESSION_KEY_RAW_REFERRER_SITES= 'rawReferrer';

    public function __construct(SessionInterface $session, EntityManagerInterface $em = NULL) {
        $this->em                 = $em;
        $this->session            = $session;
        $this->newFingerprint     = FALSE;
        $this->badFingerprint     = FALSE;
        $this->fingerPrint        = $this->session->get(self::SESSION_KEY_FINGERPRINT, '');
        $this->cookieIdentifierID = '';
        $this->sessionID          = '';
        $this->isNewSession       = TRUE;
        $this->userID             = 0;
        $this->contentBaseID      = 0;
        $this->contentStream      = NULL;
        $this->hasRealUserObject  = NULL;

        $this->createSessionUniqueID();
        $this->createSitesArrays();
    }

    private function createSessionUniqueID() {
        if (!$this->session->has(self::SESSION_KEY_UNIQUE_COOKIE_ID)) {
            $this->cookieIdentifierID = bin2hex(random_bytes(3)) . '-' . uniqid();
            $this->sessionID          = bin2hex(random_bytes(5));
            $this->session->set(self::SESSION_KEY_UNIQUE_COOKIE_ID, $this->cookieIdentifierID);
            $this->session->set(self::SESSION_KEY_UNIQUE_SESSION_ID, $this->sessionID);
        } else {
            $this->cookieIdentifierID = $this->session->get(self::SESSION_KEY_UNIQUE_COOKIE_ID, '');
            $this->sessionID          = $this->session->get(self::SESSION_KEY_UNIQUE_SESSION_ID, '');
            $this->isNewSession       = FALSE;
        }
    }

    private function createSitesArrays() {
        $this->contentStream      = (new ContentStream())->parseContainer($this->session->get(self::SESSION_KEY_CONTENT_STREAM, new ContentStreamContainer()));
        $this->rawDeliveredSites  = new ArrayCollection($this->session->get(self::SESSION_KEY_RAW_SITES, []));
        $this->jsHeartbeatedSites = new ArrayCollection($this->session->get(self::SESSION_KEY_JS_SITES, []));
        $this->likedSites         = new ArrayCollection($this->session->get(self::SESSION_KEY_LIKED_SITES, []));
        $this->lovedSites         = new ArrayCollection($this->session->get(self::SESSION_KEY_LOVED_SITES, []));
        $this->dislikedSites      = new ArrayCollection($this->session->get(self::SESSION_KEY_DISLIKED_SITES, []));
        $this->timeFramedSites    = new ArrayCollection($this->session->get(self::SESSION_KEY_FRAMED_SITES, []));
        $this->rawReferrers       = new ArrayCollection($this->session->get(self::SESSION_KEY_RAW_REFERRER_SITES, []));
    }

    public function isNewSession(): bool {
        return $this->isNewSession;
    }

    public function hasFingerprint(): bool {
        return !empty($this->fingerPrint);
    }

    public function registerNewUser($clientIP = '0') {
        // check if this browser fingerprint is already in database
        $fpInfo = $this->em->createQueryBuilder()
                           ->select('fp')
                           ->from('AppBundle:FingerprintInfo', 'fp')
                           ->where('fp.fingerprint = :fp')
                           ->setParameter('fp', $this->fingerPrint)
                           ->getQuery()->getOneOrNullResult();

        if (!$fpInfo) {
            try {
                $fpInfo = new FingerprintInfo();
                $fpInfo->setFingerprint($this->fingerPrint);
                $fpInfo->setBrowser('toDo');
                $fpInfo->setHasAdblock(TRUE);
                $fpInfo->setHeader('notSet');
                $fpInfo->setLanguage('de');
                $fpInfo->setResolution('1920x1080');

                $this->em->persist($fpInfo);
                $this->em->flush();
            } catch (\Exception $e) {
                // just do nothing....
            }
        }

        // register new User in Database
        $user = new User();
        $user->setTimestamp(time());
        $user->setUsername('user-' . uniqid());
        $user->setActiveName('John Doe');
        $user->setSalt(base_convert(mt_rand(1000000, 9999999), 10, 36));
        $user->setIdentifier(base_convert(bin2hex(random_bytes(16)), 16, 36));

        $userSecurity = new UserSecurityControl();
        $userSecurity->setIsActive(FALSE);
        $userSecurity->setIsGeneric(TRUE);
        $userSecurity->setUserObj($user);

        $uFPSession = new UserFingerprintSession();
        $uFPSession->setUserObj($user);
        $uFPSession->setFingerprintObj($fpInfo);
        $uFPSession->setTimeLastUsed(time());
        $uFPSession->setIP($clientIP);

        $uSession = new UserSessions();
        $uSession->setUserObj($user);
        $uSession->setIsActive(TRUE);
        $uSession->setEndTime(time() + 1800);
        $uSession->setTimeTillAlive(time() + 1800);
        $uSession->setStartTime(time());
        $uSession->setSessionCookieID($this->cookieIdentifierID);
        $uSession->setSessionID($this->sessionID);

        $this->em->persist($user);
        $this->em->persist($userSecurity);
        $this->em->persist($uFPSession);
        $this->em->persist($uSession);

        $this->em->flush();

        // register new User in Session Var
        $this->userObjID         = $user->getID();
        $this->hasRealUserObject = $user;
    }

    public function getUserID():?int {
        return $this->userObjID;
    }

    public function getUserByID():?User {
        if (!$this->hasRealUserObject)
            $this->hasRealUserObject = $this->em->find('AppBundle:User', $this->userObjID);

        return $this->hasRealUserObject;
    }

    public function buildUser(int $userID) {
        $this->userID = $userID;
    }

    public function setBrowserFingerPrint(string $fingerPrint): bool {
        if ($fingerPrint && strlen($fingerPrint) == 32 && !$this->session->has(self::SESSION_KEY_FINGERPRINT)) {
            $this->newFingerprint = TRUE;
            $this->session->set(self::SESSION_KEY_FINGERPRINT, $fingerPrint);
            $this->fingerPrint = $fingerPrint;

            return TRUE;
        } else if (!$fingerPrint || strlen($fingerPrint) != 32) {
            $this->badFingerprint = TRUE;

            return FALSE;
        } else {
            $this->fingerPrint = $this->session->get(self::SESSION_KEY_FINGERPRINT);

            return TRUE;
        }
    }

    public function isValidBrowserView(int $pageID, string $pageUID): bool {
        if (!$this->rawDeliveredSites->containsKey($pageID))
            return FALSE;

        $array = $this->rawDeliveredSites->get($pageID);

        if (!isset($array[$pageUID]))
            return FALSE;

        return TRUE;
    }

    private function getContentLikeUserRelationObjByParameter(int $pageID, User $user):?ContentLikeUserRelation {
        // SELECT Love Status FROM DB
        $love = $this->em->createQueryBuilder()
                         ->select('clr,u,c')
                         ->from('AppBundle:ContentLikeUserRelation', 'clr')
                         ->leftJoin('clr.userObj', 'u')
                         ->leftJoin('clr.contentObj', 'c')
                         ->where('u.ID = :uID')
                         ->andWhere('c.ID = :cID')
                         ->setParameter('cID', $pageID)
                         ->setParameter('uID', $user->getID())
                         ->getQuery()
                         ->getOneOrNullResult();

        return $love;
    }

    public function isValidLikeAction(int $pageID, string $pageUID, User $user, bool $liked = TRUE) {
        if (!$this->isValidBrowserView($pageID, $pageUID)) {
            return FALSE;
        }

        $scoreCalc = new ScoreCalculator($this->em);

        $heartbeatTime = time();
        if (!($likes = $this->getContentLikeUserRelationObjByParameter($pageID, $user))) {
            $content = $this->em->find('AppBundle:Content', $pageID);

            $likes = new ContentLikeUserRelation();
            $likes->setContentObj($content);
            $likes->setUserObj($user);
            $likes->setMaxLikeValueByLike($liked);

            if ($liked) {
                $content->getParameterObj()->incrementLikeValue();
                $scoreCalc->calculateScoreOnContentAction($content, $scoreCalc::SCORE_TYPE_LIKE);
            } else {
                $content->getParameterObj()->incrementDisLikeValue();
                $scoreCalc->calculateScoreOnContentAction($content, $scoreCalc::SCORE_TYPE_DISLIKE);
            }
            $this->em->persist($content);
            $this->em->persist($likes);
        } else {
            if ($likes->getLikeValueLike() > 0 && $liked || $likes->getLikeValueLike() < 0 && !$liked)
                $liked = NULL;

            if ($likes->getLikeValueLike() > 0 && $liked) {
                $likes->getContentObj()->getParameterObj()->incrementLikeValue(-1);
                $scoreCalc->calculateScoreOnContentAction($likes->getContentObj(), $scoreCalc::SCORE_TYPE_LIKE * -1);
            } else if ($likes->getLikeValueLike() < 0 && !$liked) {
                $likes->getContentObj()->getParameterObj()->incrementDisLikeValue(-1);
                $scoreCalc->calculateScoreOnContentAction($likes->getContentObj(), $scoreCalc::SCORE_TYPE_DISLIKE * -1);
            } else if ($likes->getLikeValueLike() > 0 && !$liked) {
                $likes->getContentObj()->getParameterObj()->incrementLikeValue(-1);
                $likes->getContentObj()->getParameterObj()->incrementDisLikeValue();
                $scoreCalc->calculateScoreOnContentAction($likes->getContentObj(), $scoreCalc::SCORE_TYPE_LIKE * -1 + $scoreCalc::SCORE_TYPE_DISLIKE);
            } else if ($likes->getLikeValueLike() < 0 && $liked) {
                $likes->getContentObj()->getParameterObj()->incrementLikeValue();
                $likes->getContentObj()->getParameterObj()->incrementDisLikeValue(-1);
                $scoreCalc->calculateScoreOnContentAction($likes->getContentObj(), $scoreCalc::SCORE_TYPE_DISLIKE * -1 + $scoreCalc::SCORE_TYPE_LIKE);
            } else if ($liked) {
                $likes->getContentObj()->getParameterObj()->incrementLikeValue();
                $scoreCalc->calculateScoreOnContentAction($likes->getContentObj(), $scoreCalc::SCORE_TYPE_LIKE);
            } else if (!$liked) {
                $likes->getContentObj()->getParameterObj()->incrementDisLikeValue();
                $scoreCalc->calculateScoreOnContentAction($likes->getContentObj(), $scoreCalc::SCORE_TYPE_DISLIKE);
            }
        }
        $likes->setLikeValueLike($liked);
        $likes->setTimeLastActive($heartbeatTime);

        $maxLiked = max($likes->getLikeValueLove(), $likes->getLikeValueTime(), $likes->getLikeValueLike());
        $likes->setMaxLikeValue($maxLiked);

        $this->saveLikeStatus($liked, $pageID);

        $this->em->persist($likes);
        $this->em->flush();

        return TRUE;
    }

    public function isValidLoveAction(int $pageID, string $pageUID, User $user, bool $loved = TRUE) {
        if (!$this->isValidBrowserView($pageID, $pageUID)) {
            return FALSE;
        }

        $heartbeatTime = time();

        if (!($likes = $this->getContentLikeUserRelationObjByParameter($pageID, $user))) {
            $content = $this->em->find('AppBundle:Content', $pageID);

            $likes = new ContentLikeUserRelation();
            $likes->setContentObj($content);
            $likes->setUserObj($user);
            $likes->setMaxLikeValueByLove();

            $content->getParameterObj()->incrementLoveValue();

            $this->em->persist($likes);
        } else {
            if ($likes->getLikeValueLove() > 0 && $loved)
                $loved = FALSE;
        }

        $scoreCalc = new ScoreCalculator($this->em);

        if (!$loved) {
            $likes->getContentObj()->getParameterObj()->incrementLoveValue(-1);
            $scoreCalc->calculateScoreOnContentAction($likes->getContentObj(), $scoreCalc::SCORE_TYPE_LOVE * -1);
        } else if ($loved) {
            $likes->getContentObj()->getParameterObj()->incrementLoveValue();
            $scoreCalc->calculateScoreOnContentAction($likes->getContentObj(), $scoreCalc::SCORE_TYPE_LOVE);
        }
        $likes->setLikeValueLove($loved);
        $likes->setTimeLastActive($heartbeatTime);

        $maxLiked = max($likes->getLikeValueLove(), $likes->getLikeValueTime(), $likes->getLikeValueLike());
        $likes->setMaxLikeValue($maxLiked);

        $this->saveLoveStatus($loved, $pageID);

        $this->em->persist($likes);
        $this->em->flush();

        return TRUE;
    }

    private function saveLoveStatus(bool $loved, int $pageID) {
        $this->lovedSites[$pageID] = $loved ? 1 : 0;
        $this->session->set(self::SESSION_KEY_LOVED_SITES, $this->lovedSites->toArray());
    }

    private function saveLikeStatus(?bool $liked, int $pageID) {
        $this->likedSites[$pageID] = $liked === NULL ? 0 : ($liked ? 1 : -1);
        $this->session->set(self::SESSION_KEY_LIKED_SITES, $this->likedSites->toArray());
    }

    public function isValidBrowserHeartbeat(int $pageID, string $pageUID, User $user): bool {
        if (!$this->isValidBrowserView($pageID, $pageUID)) {
            return FALSE;
        }

        $heartbeatTime = time();

        $heartbeatStart          = (int)$this->rawDeliveredSites->get($pageID)[$pageUID];
        $heartbeatTimeDifference = $heartbeatTime - $heartbeatStart;
        if ($heartbeatStart < 100)
            return FALSE;

        if (!$this->jsHeartbeatedSites->containsKey($pageID)) {
            $heartbeatCount = 0;
            $heartbeatArray = [];
        } else {
            $heartbeatArray = $this->jsHeartbeatedSites->get($pageID);
            $heartbeatCount = count($heartbeatArray);
        }

        if ($heartbeatTimeDifference < (2 * $heartbeatCount + 1) ||
            $heartbeatTimeDifference > (2 * $heartbeatCount + 30)
        ) {
            //echo $heartbeatTimeDifference.' - '.$heartbeatCount;
            return TRUE;
        }

        if ($heartbeatCount >= 10)
            return FALSE;

        $heartbeatPoints = round(0 + pow(0.7, $heartbeatCount), 2);
        if (isset($heartbeatArray['' . $heartbeatPoints])) {
            return TRUE;
        }

        $heartbeatArray['' . $heartbeatPoints] = [$heartbeatTime];

        // get contentObject for this User and check if it has already been visited
        $qb = $this->em->createQueryBuilder()
                       ->select('u,cvr,c')
                       ->from('AppBundle:User', 'u')
                       ->leftJoin('u.contentViewUserRelationObj', 'cvr')
                       ->leftJoin('cvr.contentObj', 'c')
                       ->where('c.ID = :pageID')
                       ->andWhere('u.ID = :userID')
                       ->setParameter('pageID', $pageID)
                       ->setParameter('userID', $user->getID())
                       ->orderBy('cvr.factor', 'DESC')
                       ->setMaxResults(20)
                       ->getQuery()->getOneOrNullResult();

        /** @var $ltContentObj null|Content */
        $ltContentObj = NULL;
        /** @var $qb User */
        if (!$qb) {
            // check if this page is valid
            $contentObj = $this->em->createQueryBuilder()
                                   ->select('c')
                                   ->from('AppBundle:Content', 'c')
                                   ->where('c.ID = :ID')
                                   ->setParameter('ID', $pageID)
                                   ->getQuery()->getOneOrNullResult();

            if (!$contentObj)
                return FALSE;

            $this->addNewContentViewWithFactor($user, $contentObj, $heartbeatPoints);
            $ltContentObj = $contentObj;
        } else {
            $contentViewObj = $qb->getContentViewUserRelationObj();
            if (!$contentViewObj->isEmpty()) {
                /** @var $relation ContentViewUserRelation */
                $relation = $contentViewObj->get(0);
                //
                //  Only Insert new Value into this table, if last Factor is larger than this one
                //
                if ($relation->getFactor() > $heartbeatPoints) {
                    $this->addNewContentViewWithFactor($user, $relation->getContentObj(), $heartbeatPoints);
                    $ltContentObj = $relation->getContentObj();
                }
            }
        }

        //
        //  Check if there is a new Content Object
        //
        if ($ltContentObj !== NULL) {
            //
            //  Update Table Vars with Global View Distiller
            //
            $subName = 'CountTimeframe' . $heartbeatCount;

            $this->em->transactional(
                function (EntityManager $em) use ($pageID, &$ltContentObj, $subName) {
                    $cView = $em->createQueryBuilder()
                                ->select('cdf,c')
                                ->from('AppBundle:ContentViewDistillerGlobal', 'cdf')
                                ->innerJoin('cdf.contentObj', 'c')
                                ->where('c.ID = :cID')
                                ->setParameter('cID', $pageID)
                                ->getQuery()->setLockMode(LockMode::PESSIMISTIC_WRITE)
                                ->getOneOrNullResult();

                    /** @var $cView ContentViewDistillerGlobal */
                    if (!$cView) {
                        $cView = new ContentViewDistillerGlobal();
                        $cView->setContentObj($ltContentObj);
                    }
                    $subName1 = 'set' . $subName;
                    $subName2 = 'get' . $subName;

                    $cView->$subName1($cView->$subName2() + 1);

                    $em->persist($cView);
                }
            );

            //
            //  UPDATE VIEW Distiller if View is one, two or three
            //
            if (in_array($heartbeatCount, [0, 5])) {
                $ltContentObj->getParameterObj()->incrementViewGlobal();
                $this->updateContentDistillerViewFramed($ltContentObj);
            }
            if ($heartbeatCount == 1) {
                if (!($likes = $this->getContentLikeUserRelationObjByParameter($pageID, $user))) {
                    $content = $this->em->find('AppBundle:Content', $pageID);

                    $likes = new ContentLikeUserRelation();
                    $likes->setContentObj($content);
                    $likes->setUserObj($user);


                    $this->em->persist($likes);
                }

                $likes->setLikeValueTime($heartbeatCount);
                $likes->setTimeLastActive(time());

                $maxLiked = max($likes->getLikeValueLove(), $likes->getLikeValueTime(), $likes->getLikeValueLike());
                $likes->setMaxLikeValue($maxLiked);

                $this->em->persist($likes);
                $this->em->flush();

                $scoreCalc = new ScoreCalculator($this->em);
                $scoreCalc->calculateScoreOnContentAction($ltContentObj, $scoreCalc::SCORE_TYPE_TIME);
            } else if ($heartbeatCount == 0) {
                $scoreCalc = new ScoreCalculator($this->em);
                $scoreCalc->calculateScoreOnContentAction($ltContentObj, 0);
            }
        }
        // store in this OBJECT
        $this->jsHeartbeatedSites->set($pageID, $heartbeatArray);
        // store in this SESSION
        $this->session->set(self::SESSION_KEY_JS_SITES, $this->jsHeartbeatedSites->toArray());

        return TRUE;
    }

    private function updateContentDistillerViewFramed(Content $ltContentObj) {
        $heartbeat              = time();
        $contentDistillerFramed = $this->em->createQueryBuilder()
                                           ->select('cdf')
                                           ->from('AppBundle:ContentViewDistillerFramed', 'cdf')
                                           ->leftJoin('cdf.contentObj', 'c')
                                           ->where('c.ID = ' . $ltContentObj->getID())
                                           ->andWhere('cdf.timestampFramed >= ' . time())
                                           ->getQuery()->getResult();

        $frameList = [
            ContentViewDistillerFramed::TIME_FRAME_HOURS_HOURLY => TRUE,
            ContentViewDistillerFramed::TIME_FRAME_HOURS_DAILY_ONE => TRUE,
            ContentViewDistillerFramed::TIME_FRAME_HOURS_DAILY_THREE => TRUE,
            ContentViewDistillerFramed::TIME_FRAME_HOURS_WEEKLY => TRUE,
            ContentViewDistillerFramed::TIME_FRAME_HOURS_MONTHLY => TRUE,
        ];

        //
        //  Remove all existing Entities
        //
        if ($contentDistillerFramed) {
            foreach ($contentDistillerFramed as $item) {
                /** @var $item ContentViewDistillerFramed */
                if (isset($frameList[$item->getTimeFrameSeconds()])) {
                    unset($frameList[$item->getTimeFrameSeconds()]);

                    $item->increaseViewByOne();
                    //
                    //  UPDATE ContentParameterTable with new Hourly Value
                    //
                    if ($item->getTimeFrameSeconds() == ContentViewDistillerFramed::TIME_FRAME_HOURS_DAILY_ONE)
                        $ltContentObj->getParameterObj()->incrementViewFramedDaily();
                }
            }
        }
        //
        //  Check List to Create new Entries
        //
        foreach ($frameList as $frameValue => $bool) {
            $fValue       = ($frameValue * 3600);
            $newTimeStamp = (int)($heartbeat / $fValue);
            $newTimeStamp += 1;
            $newTimeStamp *= $fValue;

            $entity = new ContentViewDistillerFramed();
            $entity->setContentObj($ltContentObj);
            $entity->setTimestampFramed($newTimeStamp);
            $entity->setTimeFrameSeconds($frameValue);
            $entity->setTimestamp($heartbeat);
            $entity->setViews(1);

            $this->em->persist($entity);

            //
            //  UPDATE ContentParameterTable with new Hourly Value
            //
            if ($frameValue == ContentViewDistillerFramed::TIME_FRAME_HOURS_DAILY_ONE)
                $ltContentObj->getParameterObj()->setViewsFrameDaily(1);
        }

        $this->em->flush();
    }

    private function addNewContentViewWithFactor(User $user, Content $contentObj, $factorPoints) {
        $contentView = new ContentViewUserRelation();
        $contentView->setUserObj($user);
        $contentView->setTimestamp(time());
        $contentView->setContentObj($contentObj);
        $contentView->setFactor($factorPoints);

        $this->em->persist($contentView);
        $this->em->flush();
    }

    public function getFingerPrint(): string {
        return $this->fingerPrint;
    }

    /**
     * @param int
     **/
    public function setContentBaseID(int $ID) {
        $this->contentBaseID = $ID;
    }

    public function getUniquePageIdentifier(): string {
        $rawContentID = $this->contentBaseID;
        $uid = uniqid(mt_rand(1000, 9999) . '-');
        if (!$this->rawDeliveredSites->containsKey($rawContentID)) {
            $newArrayCol       = [];
            $newArrayCol[$uid] = time();
            $this->rawDeliveredSites->set($rawContentID, $newArrayCol);
        } else {
            $newArrayCol       = $this->rawDeliveredSites->get($rawContentID);
            $newArrayCol[$uid] = time();
            $this->rawDeliveredSites->set($rawContentID, $newArrayCol);
        }
        $this->session->set(self::SESSION_KEY_RAW_SITES, $this->rawDeliveredSites->toArray());

        return $uid;
    }

    public function getRawDeliveredSitesArray(): array {
        return $this->rawDeliveredSites->toArray();
    }

    public function getVoteObject(): VoteObject {
        $pageID = $this->contentBaseID;
        $likeScore = $this->likedSites->containsKey($pageID) ? $this->likedSites->get($pageID) : 0;
        $loveScore = $this->lovedSites->containsKey($pageID) ? $this->lovedSites->get($pageID) : 0;

        $vote = new VoteObject();
        $vote->setLikeAndLoveScore($likeScore, $loveScore);

        return $vote;
    }

    public function hasStream(): bool {
        return $this->contentStream->getLengthRemaining() > 0;
    }

    public function getStream(): ContentStream {
        return $this->contentStream;
    }

    public function saveStream() {
        $this->session->set(self::SESSION_KEY_CONTENT_STREAM, $this->contentStream->toContainer());
    }
    public function addReferrer(RequestStack $requestStack){
        if(!$ref = $requestStack->getCurrentRequest()->headers->get('referer'))
            return;

        $temp = explode('//',$ref,2);
        $temp = explode( '/',$temp[1] ?? '', 2);
        $temp = explode('.',$temp[0]);
        $host = (count($temp)>1)? $temp[count($temp)-2].'.'.$temp[count($temp)-1] :'';
        switch($host){
            case 'motherless.com':
            case 'missy.nl':
                break;
            case 'imgsmash.com':
                //echo "LALALAALALALALAL";
                break;
            default:
                return;
        }
        if(!$this->rawReferrers->contains( $host ))
            $this->rawReferrers->add( $host );

        $this->session->set(self::SESSION_KEY_RAW_REFERRER_SITES, $this->rawReferrers->toArray());
    }
    public function getRawReferrer():ArrayCollection{
        return $this->rawReferrers;
    }
}