<?php

namespace nk\ApiDocBundle\Controller;

use Nelmio\ApiDocBundle\Controller\ApiDocController as Controller;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class ApiDocController extends Controller
{
    private function filterApiRoute($key) {
        return strstr($key, 'api_');
    }

    public function indexAction($view = ApiDoc::DEFAULT_VIEW)
    {
        $apiDocExtractor = $this->get('nelmio_api_doc.extractor.api_doc_extractor');
        $extractedDoc = $apiDocExtractor->extractAnnotations(
            array_filter($apiDocExtractor->getRoutes(), array($this, "filterApiRoute"), ARRAY_FILTER_USE_KEY), 
            $view
        );
        $htmlContent  = $this->get('nelmio_api_doc.formatter.html_formatter')->format($extractedDoc);

        return new Response($htmlContent, 200, array('Content-Type' => 'text/html'));
    }
}
