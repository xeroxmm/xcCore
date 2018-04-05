<?php

namespace AppBundle\Security\User\Anonym;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionUser {
    private const SESSION_KEY_UNIQUE_COOKIE_ID  = 'uniqueID';
    private const SESSION_KEY_UNIQUE_SESSION_ID = 'sessionID';
    private const SESSION_KEY_FINGERPRINT       = 'fp';
    private const SESSION_KEY_RAW_SITES         = 'rawSites';
    private const SESSION_KEY_JS_SITES          = 'jsSites';
    private const SESSION_KEY_LIKED_SITES       = 'likedSites';
    private const SESSION_KEY_DISLIKED_SITES    = 'disLikedSites';
    private const SESSION_KEY_LOVED_SITES       = 'lovedSites';
    private const SESSION_KEY_FRAMED_SITES      = 'framedSites';
    private const SESSION_KEY_USER_DATABASE_ID  = 'userDB_ID';

    private $userID;
    private $session;

    public function __construct(SessionInterface $session, int $databaseUserID) {
        $this->session = $session;
        $this->userID  = $databaseUserID;
        $this->setUserID($databaseUserID);
    }

    public function setUserID(int $databaseUserID) {
        $this->userID = $databaseUserID;
        $this->session->set(self::SESSION_KEY_USER_DATABASE_ID, $databaseUserID);
    }
    public function getUserID():int{
        return $this->userID;
    }
}