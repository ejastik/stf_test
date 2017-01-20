<?php
namespace Platform\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\UserInterface;

/**
 * User
 */
class User extends BaseUser implements UserInterface
{
    public function __construct()
    {
        parent::__construct();
        // your own logic

        $this->favoriteRealty = new ArrayCollection();
    }

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $groups;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="confirm_requested_at", type="datetime", nullable=true)
     */
    private $confirmRequestedAt;

    /**
     * @return string
     */
    public function getNameOrLogin()
    {
        $userName = '';

        if ($this->getUserInfo())
        {
            return (string)$this->getUserInfo();
        }

        return '[' . $this->username . ']' . $userName;
    }

    /**
     * @return string
     */
    public function getNameAndLogin()
    {
        $userName = '';

        if ($this->getUserInfo())
        {
            $userName = ' ' . (string)$this->getUserInfo();
        }

        return '[' . $this->username . ']' . $userName;
    }

    /**
     * To string conversion for forms.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->getUserInfo())
        {
            return (string)$this->getUserInfo();
        }

        return '-';
    }

    /**
     * Get not accessible fields.
     *
     * @return array
     */
    public function getNotAccessibleFields()
    {
        return ['username', 'usernameCanonical', 'email', 'emailCanonical', 'salt', 'password', 'lastLogin', 'roles', 'confirmationToken', 'passwordRequestedAt', 'locked', 'expired', 'credentialsExpired'];
    }

    /**
     * Set confirmRequestedAt
     *
     * @param \DateTime $confirmRequestedAt
     *
     * @return User
     */
    public function setConfirmRequestedAt($confirmRequestedAt)
    {
        $this->confirmRequestedAt = $confirmRequestedAt;

        return $this;
    }

    /**
     * Get confirmRequestedAt
     *
     * @return \DateTime
     */
    public function getConfirmRequestedAt()
    {
        return $this->confirmRequestedAt;
    }
}
