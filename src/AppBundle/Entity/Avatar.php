<?php
namespace AppBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="image_avatar")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="my_region")
 */
class Avatar {
    /**
     * @ORM\Column(type="bigint", name="ID")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $ID;
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\User", mappedBy="avatarObj")
     */
    protected $userObj;
    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $linkURL;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->userObj = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get iD
     *
     * @return integer
     */
    public function getID()
    {
        return $this->ID;
    }

    /**
     * Set linkURL
     *
     * @param string $linkURL
     *
     * @return Avatar
     */
    public function setLinkURL($linkURL)
    {
        $this->linkURL = $linkURL;

        return $this;
    }

    /**
     * Get linkURL
     *
     * @return string
     */
    public function getLinkURL()
    {
        return $this->linkURL;
    }

    /**
     * Add userObj
     *
     * @param \AppBundle\Entity\User $userObj
     *
     * @return Avatar
     */
    public function addUserObj(\AppBundle\Entity\User $userObj)
    {
        $this->userObj[] = $userObj;

        return $this;
    }

    /**
     * Remove userObj
     *
     * @param \AppBundle\Entity\User $userObj
     */
    public function removeUserObj(\AppBundle\Entity\User $userObj)
    {
        $this->userObj->removeElement($userObj);
    }

    /**
     * Get userObj
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserObj()
    {
        return $this->userObj;
    }
}
