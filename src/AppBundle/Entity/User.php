<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_base")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class User implements UserInterface {
    protected $roles;
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $username;
    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $activeName;
    /**
     * @ORM\Column(type="string", length=64)
     */
    protected $identifier;
    /**
     * @ORM\Column(type="string", length=32)
     */
    protected $salt;
    /**
     * @ORM\Column(type="integer")
     */
    protected $timestamp;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Avatar", inversedBy="userObj")
     * @ORM\JoinColumn(name="avatarID", referencedColumnName="ID", onDelete="CASCADE", nullable=true)
     */
    protected $avatarObj;
    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\UserSecurity", mappedBy="userObj")
     */
    protected $securityObj;
    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\UserSecurityEmail", mappedBy="userObj")
     */
    protected $securityEmailObj;
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\UserSessions", mappedBy="userObj")
     */
    protected $sessionObj;
    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\UserSecurityControl", mappedBy="userObj")
     */
    protected $securityControlObj;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentParameter", mappedBy="userObj")
     */
    protected $contentParameter;
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\UserFingerprintSession", mappedBy="userObj")
     */
    protected $fingerprintSessionObj;
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentLikeUserRelation", mappedBy="userObj")
     */
    protected $contentLikeUserRelationObj;
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContentViewUserRelation", mappedBy="userObj")
     */
    protected $contentViewUserRelationObj;


    function __construct() {
        $this->contentList                = new ArrayCollection();
        $this->contentParameter           = new ArrayCollection();
        $this->salt                       = md5('haddaway');
        $this->contentLikeUserRelationObj = new ArrayCollection();
        $this->fingerprintSessionObj      = new ArrayCollection();
        $this->contentViewUserRelationObj = new ArrayCollection();
        $this->sessionObj                 = new ArrayCollection();
        $this->roles                      = ['ROLE_ANONYMOUS'];
    }

    /**
     * @return mixed
     */
    public function getActiveName() {
        return $this->activeName;
    }

    /**
     * @param mixed $activeName
     */
    public function setActiveName($activeName) {
        $this->activeName = $activeName;
    }

    /**
     * @return mixed
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    /**
     * @param mixed $identifier
     */
    public function setIdentifier($identifier) {
        $this->identifier = $identifier;
    }

    /**
     * @return mixed
     */
    public function getSalt() {
        return $this->salt;
    }

    /**
     * @param mixed $salt
     */
    public function setSalt($salt) {
        $this->salt = $salt;
    }

    /**
     * @return mixed
     */
    public function getSecurityEmailObj() {
        return $this->securityEmailObj;
    }

    /**
     * @param mixed $securityEmailObj
     */
    public function setSecurityEmailObj($securityEmailObj) {
        $this->securityEmailObj = $securityEmailObj;
    }

    /**
     * @return mixed
     */
    public function getSessionObj() {
        return $this->sessionObj;
    }

    /**
     * @param mixed $sessionObj
     */
    public function setSessionObj(UserSessions $sessionObj) {
        $this->sessionObj = $sessionObj;
    }

    /**
     * @return mixed
     */
    public function getSecurityControlObj() {
        return $this->securityControlObj;
    }

    /**
     * @param mixed $securityControlObj
     */
    public function setSecurityControlObj($securityControlObj) {
        $this->securityControlObj = $securityControlObj;
    }

    /**
     * @return mixed
     */
    public function getFingerprintSessionObj():UserFingerprintSession {
        return $this->fingerprintSessionObj;
    }

    /**
     * @param mixed $fingerprintSessionObj
     */
    public function setFingerprintSessionObj(UserFingerprintSession $fingerprintSessionObj) {
        $this->fingerprintSessionObj = $fingerprintSessionObj;
    }

    /**
     * @return mixed
     */
    public function getContentLikeUserRelationObj() {
        return $this->contentLikeUserRelationObj;
    }

    /**
     * @param mixed $contentLikeUserRelationObj
     */
    public function setContentLikeUserRelationObj($contentLikeUserRelationObj) {
        $this->contentLikeUserRelationObj = $contentLikeUserRelationObj;
    }

    /**
     * @return Collection
     */
    public function getContentViewUserRelationObj():Collection {
        return $this->contentViewUserRelationObj;
    }

    /**
     * @param mixed $contentViewUserRelationObj
     */
    public function setContentViewUserRelationObj($contentViewUserRelationObj) {
        $this->contentViewUserRelationObj = $contentViewUserRelationObj;
    }

    public function getID() {
        return $this->ID;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username) {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Set timestamp
     *
     * @param integer $timestamp
     *
     * @return User
     */
    public function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return integer
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Set avatarObj
     *
     * @param \AppBundle\Entity\Avatar $avatarObj
     *
     * @return User
     */
    public function setAvatarObj(\AppBundle\Entity\Avatar $avatarObj = null) {
        $this->avatarObj = $avatarObj;

        return $this;
    }

    /**
     * Get avatarObj
     *
     * @return \AppBundle\Entity\Avatar
     */
    public function getAvatarObj() {
        return $this->avatarObj;
    }

    /**
     * Add securityObj
     *
     * @param \AppBundle\Entity\UserSecurity $securityObj
     *
     * @return User
     */
    public function addSecurityObj(\AppBundle\Entity\UserSecurity $securityObj) {
        $this->securityObj[] = $securityObj;

        return $this;
    }

    /**
     * Remove securityObj
     *
     * @param \AppBundle\Entity\UserSecurity $securityObj
     */
    public function removeSecurityObj(\AppBundle\Entity\UserSecurity $securityObj) {
        /** TODO implement removeSecurityObject */
        $this->securityObj->removeElement($securityObj);
    }

    /**
     * Get securityObj
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSecurityObj() {
        return $this->securityObj;
    }

    /**
     * Add contentParameter
     *
     * @param \AppBundle\Entity\ContentParameter $contentParameter
     *
     * @return User
     */
    public function addContentParameter(\AppBundle\Entity\ContentParameter $contentParameter) {
        $this->contentParameter[] = $contentParameter;

        return $this;
    }

    /**
     * Remove contentParameter
     *
     * @param \AppBundle\Entity\ContentParameter $contentParameter
     */
    public function removeContentParameter(\AppBundle\Entity\ContentParameter $contentParameter) {
        $this->contentParameter->removeElement($contentParameter);
    }

    /**
     * Get contentParameter
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContentParameter() {
        return $this->contentParameter;
    }

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return (Role|string)[] The user roles
     */
    public function getRoles() {
        return $this->roles;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword() {
        // TODO: Implement getPassword() method.

        return '';
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials() {
        // TODO: Implement eraseCredentials() method.
    }
}
