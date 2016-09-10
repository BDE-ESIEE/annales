<?php
namespace nk\ApiBundle\Security;

use OAuth2\OAuth2 as BaseOAuth2;
use OAuth2\IOAuth2GrantUser;
use OAuth2\OAuth2ServerException;
use nk\UserBundle\Entity\User;
use OAuth2\Model\IOAuth2Client;
use OAuth2\Model\IOAuth2AuthCode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OAuth2 extends BaseOAuth2
{

    const GRANT_TYPE_ACCESS_TOKEN = 'social_access_token';
    const GRANT_TYPE_REGEXP = '#^(authorization_code|social_access_token|token|password|client_credentials|refresh_token|https?://.+|urn:.+)$#';
        
    public function grantAccessToken(Request $request = null, $network = null)
    {        
        if ($network != null) {
            if (!in_array(strtolower($network), array('google')))
                throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_REQUEST, 'Invalid network parameter or parameter missing');
        }

        $filters = array(
            "grant_type" => array(
                "filter" => FILTER_VALIDATE_REGEXP,
                "options" => array("regexp" => self::GRANT_TYPE_REGEXP),
                "flags" => FILTER_REQUIRE_SCALAR
            ),
            "scope" => array("flags" => FILTER_REQUIRE_SCALAR),
            "code" => array("flags" => FILTER_REQUIRE_SCALAR),
            "redirect_uri" => array("filter" => FILTER_SANITIZE_URL),
            "username" => array("flags" => FILTER_REQUIRE_SCALAR),
            "password" => array("flags" => FILTER_REQUIRE_SCALAR),
            "refresh_token" => array("flags" => FILTER_REQUIRE_SCALAR),
            "social_id" => array("flags" => FILTER_REQUIRE_SCALAR),
            "social_token" => array("flags" => FILTER_REQUIRE_SCALAR),
        );

        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        // Input data by default can be either POST or GET
        if ($request->getMethod() === 'POST') {
            $inputData = $request->request->all();
        } else {
            $inputData = $request->query->all();
        }

        // Basic authorization header
        $authHeaders = $this->getAuthorizationHeader($request);
        // Filter input data
        $input = filter_var_array($inputData, $filters);
        // Grant Type must be specified.
        if (!$input["grant_type"]) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');
        }

        // Authorize the client
        $clientCredentials = $this->getClientCredentials($inputData, $authHeaders);
        $client = $this->storage->getClient($clientCredentials[0]);
        if (!$client) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_CLIENT, 'The client credentials are invalid');
        }
        if ($this->storage->checkClientCredentials($client, $clientCredentials[1]) === false) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_CLIENT, 'The client credentials are invalid');
        }
        if (!$this->storage->checkRestrictedGrantType($client, $input["grant_type"])) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_UNAUTHORIZED_CLIENT, 'The grant type is unauthorized for this client_id');
        }

        // Do the granting
        switch ($input["grant_type"]) {
            case self::GRANT_TYPE_AUTH_CODE:
                // returns array('data' => data, 'scope' => scope)
                $stored = $this->grantAccessTokenAuthCode($client, $input);
                break;
            case self::GRANT_TYPE_USER_CREDENTIALS:
                // returns: true || array('scope' => scope)
                $stored = $this->grantAccessTokenUserCredentials($client, $input);
                break;
            case self::GRANT_TYPE_ACCESS_TOKEN:
                // returns: true || array('scope' => scope)
                $stored = $this->grantAccessTokenSocialAccessToken($client, $input, $network);
                break;
            case self::GRANT_TYPE_CLIENT_CREDENTIALS:
                // returns: true || array('scope' => scope)
                $stored = $this->grantAccessTokenClientCredentials($client, $input, $clientCredentials);
                break;
            case self::GRANT_TYPE_REFRESH_TOKEN:
                // returns array('data' => data, 'scope' => scope)
                $stored = $this->grantAccessTokenRefreshToken($client, $input);
                break;
            default:
                if (filter_var($input["grant_type"], FILTER_VALIDATE_URL)) {
                    // returns: true || array('scope' => scope)
                    $stored = $this->grantAccessTokenExtension($client, $inputData, $authHeaders);
                } else {
                    throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');
                }
        }

        if (!is_array($stored)) {
            $stored = array();
        }

        // if no scope provided to check against $input['scope'] then application defaults are set
        // if no data is provided than null is set
        $stored += array('scope' => $this->getVariable(self::CONFIG_SUPPORTED_SCOPES, null), 'data' => null,
                         'access_token_lifetime' => $this->getVariable(self::CONFIG_ACCESS_LIFETIME),
                         'issue_refresh_token' => true, 'refresh_token_lifetime' => $this->getVariable(self::CONFIG_REFRESH_LIFETIME));
        $scope = $stored['scope'];
        if ($input["scope"]) {
            // Check scope, if provided
            if (!isset($stored["scope"]) || !$this->checkScope($input["scope"], $stored["scope"])) {
                throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_SCOPE, 'An unsupported scope was requested.');
            }
            $scope = $input["scope"];
        }
        $token = $this->createAccessToken($client, $stored['data'], $scope, $stored['access_token_lifetime'], $stored['issue_refresh_token'], $stored['refresh_token_lifetime']);
        return new Response(json_encode($token), 200, $this->getJsonHeaders());
    }

    protected function grantAccessTokenSocialAccessToken(IOAuth2Client $client, array $input, $network)
    {
        if (!($this->storage instanceof IOAuth2GrantUser)) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_UNSUPPORTED_GRANT_TYPE);
        }
            
        $stored = $this->storage->checkSocialCredentials($client, $input['social_id'], $input['social_token'], $network);

        if ($stored === FALSE) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_GRANT);
        }

        return $stored;
    }
}
