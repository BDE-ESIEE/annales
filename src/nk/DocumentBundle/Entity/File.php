<?php

namespace nk\DocumentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;


/**
 * File
 *
 * @ORM\Table(name="nk_file")
 * @ORM\Entity(repositoryClass="nk\DocumentBundle\Entity\FileRepository")
 * @Gedmo\Uploadable(allowOverwrite = true, filenameGenerator = "SHA1")
 * @Serializer\ExclusionPolicy("none")
 */
class File
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
     * @var Document
     * @ORM\ManyToOne(targetEntity="Document", inversedBy="files")
     * @ORM\JoinColumn(nullable=false)
     * @Serializer\Groups({"details"})
     */
    private $document;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     * @Assert\NotBlank()
     * @Serializer\Groups({"list", "details"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="size", type="string", length=10)
     * @Assert\NotBlank()
     * @Serializer\Groups({"list", "details"})
     */
    private $size;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255)
     * @Gedmo\UploadableFilePath
     * @Serializer\Exclude
     */
    private $path;

    /**
     * @Assert\File(
     *     maxSize="64M",
     *     mimeTypes = {"application/pdf", "application/x-pdf"},
     *     mimeTypesMessage = "Ce fichier n'est pas un pdf",
     *     maxSizeMessage = "Fichier trop gros (64Mo max)"
     * )
     */
    private $file;

    /**
     * @var string
     *
     * @ORM\Column(name="preview", type="string", length=255, nullable=true)
     * @Serializer\Exclude
     */
    private $preview;

    /**
     * @var string
     *
     * @Serializer\Groups({"details"})
     */
    private $downloadPath;

    public function __construct(Document $document = null)
    {
        if($document !== null)
            $this->document = $document;
    }

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
     * @param string $name
     */
    public function setName($name)
    {
        if(substr($name, -4) !== '.pdf') $name .= '.pdf';

        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return File
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set document
     *
     * @param Document $document
     * @return File
     */
    public function setDocument(Document $document)
    {
        $this->document = $document;

        return $this;
    }

    /**
     * Get document
     *
     * @return Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Get web path
     *
     * @return string
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("path")
     * @Serializer\Groups({"details"})
     */
    public function getWebPath()
    {
        return preg_replace('#^.+\.\./www/(.+)$#', '$1', $this->getPath());
    }

    /**
     * @param mixed $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set preview
     *
     * @param string $preview
     * @return File
     */
    public function setPreview($preview)
    {
        $this->preview = $preview;

        return $this;
    }

    /**
     * Get preview
     *
     * @return string
     */
    public function getPreview()
    {
        return $this->preview;
    }

    /**
     * Get web path
     *
     * @return string
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("preview")
     * @Serializer\Groups({"details"})
     */
    public function getPreviewWebPath()
    {
        return preg_replace('#^.+\.\./www/(.+)$#', '$1', $this->getPreview());
    }

    /**
     * @return string
     */
    public function getDownloadPath()
    {
        return $this->downloadPath;
    }

    /**
     * Set downloadPath
     *
     * @param string $downloadPath
     * @return File
     */
    public function setDownloadPath($downloadPath)
    {
        $this->downloadPath = $downloadPath;

        return $this;
    }
}
