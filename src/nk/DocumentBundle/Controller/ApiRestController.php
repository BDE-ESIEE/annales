<?php

namespace nk\DocumentBundle\Controller;

use nk\DocumentBundle\Entity\Document;
use nk\DocumentBundle\Form\DocumentType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\RestBundle\Context\Context;

class ApiRestController extends FOSRestController
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * Fetch the list of all classes.
     *
     * @ApiDoc(
     *  section="Documents",
     *  description="Get class list",
     *  resource=true
     * )
     */
    public function allClassesAction()
    {
        $data = array(
            'classes' => $this->get('nk.metadata_finder')->findAll()['classes'],
        );
        
        return $this->handleView($this->view($data, 200));
    }

    /**
     * Fetch the list of all fields for a given class name.
     *
     * @ApiDoc(
     *  section="Documents",
     *  description="Get fields list by class name",
     *  resource=true,
     *  requirements={
     *      {
     *          "name"="class",
     *          "dataType"="string",
     *          "requirement"="\w+",
     *          "description"="filter fields by class name"
     *      }
     *  }
     * )
     */
    public function allFieldsAction($class)
    {
        $data = array(
            'class' => $class,
            'fields' => $this->em->getRepository('nkDocumentBundle:Document')->findFieldsFromClass($class),
        );
        
        return $this->handleView($this->view($data, 200));
    }


    /**
     * Fetch the list of all documents for a given class name and a given field name.
     *
     * @ApiDoc(
     *  section="Documents",
     *  description="Get documents list by class name and field name",
     *  resource=true,
     *  requirements={
     *      {
     *          "name"="class",
     *          "dataType"="string",
     *          "requirement"="\w+",
     *          "description"="filter documents by class name"
     *      },
     *      {
     *          "name"="field",
     *          "dataType"="string",
     *          "requirement"="\w+",
     *          "description"="filter documents by field name"
     *      }
     *  }
     * )
     */
    public function allDocumentsAction($class, $field)
    {
        $data = array(
            'class' => $class,
            'field' => $field,
            'documents' => $this->em->getRepository('nkDocumentBundle:Document')->findBy(array(
                'class' => $class,
                'field' => $field
            )),
        );

        $context = (new Context())
            ->addGroup('list')
            ->setMaxDepth(true)
        ;

        $view = $this->view($data, 200);
        $view->setContext($context);
        
        return $this->handleView($view);
    }

    /**
     * Fetch a document from its id
     *
     * @ApiDoc(
     *  section="Documents",
     *  description="Get class list",
     *  resource=true
     * )
     */
    public function getDocumentAction(Document $document)
    {
        $suggestions = $this->em->getRepository('nkDocumentBundle:Document')->findSuggestionsFromDocument($document);

        $document->setViewed($document->getViewed() + 1);
        $this->em->persist($document);
        $this->em->flush();

        $data = array(
            'document'    => $document,
            'folders'     => $this->em->getRepository('nkFolderBundle:Folder')->getFolders($document),
            'suggestions' => $suggestions,
        );

        $context = (new Context())
            ->addGroup('details')
            ->setMaxDepth(true)
        ;

        $view = $this->view($data, 200);
        $view->setContext($context);
        
        return $this->handleView($view);
    }
}
