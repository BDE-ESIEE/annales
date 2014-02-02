<?php

namespace nk\FolderBundle\Controller;

use nk\FolderBundle\Entity\Folder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;

class FolderController extends Controller
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Request
     */
    private $request;

    /**
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function myFoldersAction()
    {
        return array();
    }

    /**
     * @Secure(roles="ROLE_USER")
     */
    public function renameAction(Folder $folder)
    {
        $response = array('success' => 1);

        $folder->setName($this->request->query->get('name'));
        $this->em->persist($folder);
        $this->em->flush();

        $response = new Response(json_encode( $response ));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Secure(roles="ROLE_USER")
     */
    public function newAction()
    {
        $folder = new Folder;
        $this->em->persist($folder);
        $this->em->flush();

        $response = array(
            'success' => 1,
            'id' => $folder->getId(),
            'name' => $folder->getName(),
            'rename' => $this->generateUrl('nk_folder_rename', array('id' => $folder->getId())),
            'link' => $this->generateUrl('nk_folder_show', array('id' => $folder->getId())),
        );

        $response = new Response(json_encode( $response ));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Template
     */
    public function showAction(Folder $folder)
    {
        return array(
            'folder' => $folder,
        );
    }
}