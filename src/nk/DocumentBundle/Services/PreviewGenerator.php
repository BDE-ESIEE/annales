<?php

namespace nk\DocumentBundle\Services;

use nk\DocumentBundle\Entity\File;
use Doctrine\ORM\EntityManager;

class PreviewGenerator
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    public function __construct(EntityManager $em = null)
    {
    	$this->em = $em;
    }

    public function generatePreview(File $file)
    {
    	try {
	        $image = new \imagick($file->getPath().'[0]');
	        $image->setImageFormat('jpg');

	        $file->setPreview(str_replace('.pdf', '.jpg', $file->getPath()));

	        $image->writeImage($file->getPreview());
    	} catch (\ImagickException $e) {
    	}

        return $file;
    }

    public function generateAllPreview()
    {
    	$files = $this->em->getRepository('nkDocumentBundle:File')->findAll();

    	foreach ($files as $file) {
    		$this->generatePreview($file);
    		$this->em->persist($file);
    	}

    	$this->em->flush();
    }
}
