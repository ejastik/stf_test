<?php

namespace Test\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Platform\RestBundle\Validator\Constraints\URL as URL;

/**
 * Tag
 *
 * @ORM\Table(name="testmediabundle_tag")
 * @ORM\Entity(repositoryClass="Test\MediaBundle\Entity\TagRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class Tag
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="Test\MediaBundle\Entity\ImageTag", mappedBy="tag", cascade={"persist", "remove"})
     */
    private $images;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * To string conversion for forms.
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->id;
    }

    /**
     * Get not accessible fields.
     *
     * @return array
     */
    public function getNotAccessibleFields()
    {
        return [];
    }

    /**
     * Get additional parsers
     *
     * @return array
     */
    public function getAdditionalParsers()
    {
        return [];
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->images = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Tag
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Tag
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Add image
     *
     * @param \Test\MediaBundle\Entity\ImageTag $image
     *
     * @return Tag
     */
    public function addImage(\Test\MediaBundle\Entity\ImageTag $image)
    {
        $this->images[] = $image;

        return $this;
    }

    /**
     * Remove image
     *
     * @param \Test\MediaBundle\Entity\ImageTag $image
     */
    public function removeImage(\Test\MediaBundle\Entity\ImageTag $image)
    {
        $this->images->removeElement($image);
    }

    /**
     * Get images
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getImages()
    {
        return $this->images;
    }
}
