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
     * @Secure(roles="ROLE_USER")
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
     *  description="Get document by id",
     *  resource=true
     * )
     */
    public function getDocumentAction(Document $document)
    {
        $suggestions = $this->em->getRepository('nkDocumentBundle:Document')->findSuggestionsFromDocument($document);

        $document->setViewed($document->getViewed() + 1);
        $this->em->persist($document);
        $this->em->flush();

        foreach ($document->getFiles() as $file) {
            /* @var $file \nk\DocumentBundle\Entity\File */
            $file->setDownloadPath($this->generateUrl('nk_document_file_preview_slug', array('slug' => $file->getSlug())));
        }

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

    /**
     * @Secure(roles="ROLE_USER")
     *
     * @ApiDoc(
     *  section="Documents",
     *  description="Search for documents from a given query string",
     *  parameters = {
     *    { "name" = "s", "dataType" = "string", "required" = true, "description" = "query string" },
     *    { "name" = "page", "dataType" = "integer", "required" = false, "description" = "page to fetch" }
     *  },
     *  resource=true
     * )
     */
    public function searchAction(Request $request)
    {
        $searchQuery = $this->get('nk.search_engine')->search($request->query->get('s'));
        $data = array(
            'query'          => $searchQuery->__toString(),
            'documents'      => $searchQuery->getResult()->getItems(),
            'current_page'   => intval($searchQuery->getResult()->getCurrentPageNumber()),
            'items_per_page' => $searchQuery->getResult()->getItemNumberPerPage(),
            'total_count'    => $searchQuery->getResult()->getTotalItemCount(),
            'suggestion'     => false,
            'next_page'      => $this->generateUrl(
                $searchQuery->getResult()->getRoute(), 
                [
                    's' => $request->query->get('s'),
                    $searchQuery->getResult()->getPaginatorOptions()['pageParameterName'] => $searchQuery->getResult()->getCurrentPageNumber()+1

                ],
                true
            ),
            'prev_page'      => $this->generateUrl(
                $searchQuery->getResult()->getRoute(), 
                [
                    's' => $request->query->get('s'),
                    $searchQuery->getResult()->getPaginatorOptions()['pageParameterName'] => $searchQuery->getResult()->getCurrentPageNumber()-1
                ],
                true
            ),
        );

        if ($searchQuery->hasSuggestion()) {
            $data['suggestion'] = $searchQuery->getSuggestion();
        }

        if($request->query->get('page', 1) == 1)
            $data['prev_page'] = false;

        if($data['current_page'] * $data['items_per_page'] >= $data['total_count'])
            $data['next_page'] = false;

        $context = (new Context())
            ->addGroup('list')
            ->setMaxDepth(true)
        ;

        $view = $this->view($data, 200);
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * Fetch every metadatas
     *
     * @ApiDoc(
     *  section="Documents",
     *  description="Get metadata list",
     *  resource=true
     * )
     */
    public function allMetadatasAction()
    {
        return $this->handleView($this->view($this->get('nk.metadata_finder')->findAll(), 200));
    }
}
