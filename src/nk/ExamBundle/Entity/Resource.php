<?php

namespace nk\ExamBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use nk\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use \Doctrine\Common\Collections\ArrayCollection as ArrayCollection;
use JMS\Serializer\Annotation as Serializer;

/**
 * Resource
 *
 * @ORM\Table(name="nk_resource")
 * @ORM\Entity(repositoryClass="nk\ExamBundle\Entity\ResourceRepository")
 * @Serializer\ExclusionPolicy("none")
 */
class Resource
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"list", "details"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=20)
     * @Assert\NotBlank()
     * @Serializer\Groups({"list", "details"})
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="code", type="integer")
     * @Assert\NotBlank()
     * @Serializer\Groups({"list", "details"})
     */
    private $code;

    /**
     * @ORM\OneToMany(targetEntity="\nk\UserBundle\Entity\User", mappedBy="resource")
     */
    protected $users;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Exam", mappedBy="resource")
     */
    protected $exams;

    public function __toString()
    {
        return $this->getName();
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
     * @return Resource
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
     * Set code
     *
     * @param integer $code
     * @return Resource
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return integer 
     */
    public function getCode()
    {
        return $this->code;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->users = new ArrayCollection;
    }

    /**
     * Add users
     *
     * @param User $users
     * @return Resource
     */
    public function addUser(User $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param User $users
     */
    public function removeUser(User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUsers()
    {
        return $this->users;
    }
}
