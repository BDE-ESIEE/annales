<?php

namespace nk\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use OAuth2\OAuth2ServerException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class TokenController extends Controller
{
	/**
	 * Retrieve an access token from whether a username/password tuple or a google access token.
	 * @param Request $request
	 * @param String $network
	 * 
	 * @return type
     *
     * @ApiDoc(
     *  section="OAuth2",
     *  description="Get OAuth2 access token",
     *  input="nk\ApiBundle\Form\InputTokenType",
     *  resource=true
     * )
	*/
	public function getSocialTokenAction(Request $request, $network)
	{
		$server = $this->get('nk_oauth_server.server');

		try {
		    return $server->grantAccessToken($request, $network);
		} catch (OAuth2ServerException $e) {
		    return $e->getHttpResponse();
		}
	}
}
