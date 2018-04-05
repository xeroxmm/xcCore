<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fingerprint_info")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class FingerprintInfo {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\UserFingerprintSession", mappedBy="fingerprintObj")
     */
    protected $userFingerprintSession;

    /**
     * @ORM\Column(type="string", length=32, unique=true)
     */
    protected $fingerprint;
    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $browser;
    /**
     * @ORM\Column(type="string", length=11)
     */
    protected $resolution;
    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $header;
    /**
     * @ORM\Column(type="string", length=3)
     */
    protected $language;
    /**
     * @ORM\Column(type="boolean")
     */
    protected $hasAdblock;

    public function __construct() {
        $this->resolution = '0x0';
        $this->header = 'null';
        $this->hasAdblock = TRUE;
        $this->language = '00';
        $this->userFingerprintSession = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getID() {
        return $this->ID;
    }

    /**
     * @param mixed $ID
     */
    public function setID($ID) {
        $this->ID = $ID;
    }

    /**
     * @return mixed
     */
    public function getUserFingerprintSession() {
        return $this->userFingerprintSession;
    }

    /**
     * @param mixed $userFingerprintSession
     */
    public function setUserFingerprintSession($userFingerprintSession) {
        $this->userFingerprintSession = $userFingerprintSession;
    }

    /**
     * @return mixed
     */
    public function getFingerprint() {
        return $this->fingerprint;
    }

    /**
     * @param mixed $fingerprint
     */
    public function setFingerprint($fingerprint) {
        $this->fingerprint = $fingerprint;
    }

    /**
     * @return mixed
     */
    public function getBrowser() {
        return $this->browser;
    }

    /**
     * @param mixed $browser
     */
    public function setBrowser($browser) {
        $this->browser = $browser;
    }

    /**
     * @return mixed
     */
    public function getResolution() {
        return $this->resolution;
    }

    /**
     * @param mixed $resolution
     */
    public function setResolution($resolution) {
        $this->resolution = $resolution;
    }

    /**
     * @return mixed
     */
    public function getHeader() {
        return $this->header;
    }

    /**
     * @param mixed $header
     */
    public function setHeader($header) {
        $this->header = $header;
    }

    /**
     * @return mixed
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language) {
        $this->language = $language;
    }

    /**
     * @return mixed
     */
    public function getHasAdblock() {
        return $this->hasAdblock;
    }

    /**
     * @param mixed $hasAdblock
     */
    public function setHasAdblock($hasAdblock) {
        $this->hasAdblock = $hasAdblock;
    }
}