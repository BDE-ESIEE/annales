<?php
namespace nk\ApiBundle\Storage;

use OAuth2\Model\IOAuth2Client;
use Doctrine\ORM\EntityManager;

use FOS\OAuthServerBundle\Storage\OAuthStorage as BaseOAuthStorage;
use FOS\OAuthServerBundle\Model\ClientInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use FOS\OAuthServerBundle\Model\AccessTokenManagerInterface;
use FOS\OAuthServerBundle\Model\RefreshTokenManagerInterface;
use FOS\OAuthServerBundle\Model\AuthCodeManagerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\GenericOAuth2ResourceOwner;

class OAuthStorage extends BaseOAuthStorage
{
	/**
	 * @var string
	 */
	private $google_client_id;
	
	/**
	 * @var string
	 */
	private $google_client_secret;

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	protected $em;

	/**
	 * @var \HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\GenericOAuth2ResourceOwner
	 */
	private $googleResourceOwner;

	/**
	 * @var \Symfony\Component\Security\Core\User\UserProviderInterface
	 */
	protected $userProvider;
	
	/**
	 * @var array
	 */
	protected $socialDetails;

    /**
     * @param ClientManagerInterface       $clientManager
     * @param AccessTokenManagerInterface  $accessTokenManager
     * @param RefreshTokenManagerInterface $refreshTokenManager
     * @param AuthCodeManagerInterface     $authCodeManager
     * @param null|UserProviderInterface   $userProvider
     * @param null|EncoderFactoryInterface $encoderFactory
     */
    public function __construct(ClientManagerInterface $clientManager, AccessTokenManagerInterface $accessTokenManager,
        RefreshTokenManagerInterface $refreshTokenManager, AuthCodeManagerInterface $authCodeManager,
        UserProviderInterface $userProvider = null, EncoderFactoryInterface $encoderFactory = null, 
        EntityManager $em, GenericOAuth2ResourceOwner $googleResourceOwner, 
        $google_client_id, $google_client_secret)
    {
    	parent::__construct($clientManager, $accessTokenManager, $refreshTokenManager, $authCodeManager, $userProvider, $encoderFactory);
		$this->em                   = $em;
		$this->googleResourceOwner  = $googleResourceOwner;
		$this->google_client_id     = $google_client_id;
		$this->google_client_secret = $google_client_secret;
    }

	public function checkSocialCredentials(IOAuth2Client $client, $socialId, $socialToken, $network)
	{
		if (!$client instanceof ClientInterface) {
			throw new \InvalidArgumentException('Client has to implement the ClientInterface');
		}

		if (!in_array(strtolower($network), array('google')))
			throw new \InvalidArgumentException('Invalid network');
			
		try {
			$user = $this->em->getRepository('nkUserBundle:User')
				->findOneBy(
					array(strtolower($network).'Id' => $socialId)
				);
		} catch(AuthenticationException $e) {
			throw new \InvalidArgumentException('Invalid network');
		}

		if (null !== $user) {
			$encoder = $this->encoderFactory->getEncoder($user);

			if ($this->checkSocialAccessToken($socialId, $socialToken, $network)) {
				$this->updateSocialToken($user, $socialId, $socialToken, $network);
				return array(
					'data' => $user,
				);
			}
		}
		else {
			if ($this->checkSocialAccessToken($socialId, $socialToken, $network)) {
				
				$user = $this->createProfileFromSocialDetails($socialId, $socialToken, $network);
				
				if (null !== $user) {
					$encoder = $this->encoderFactory->getEncoder($user);
					
					return array(
						'data' => $user,
					);
				}
			}
		}

		return false;
	}
	
	private function checkSocialAccessToken($socialId, $socialToken, $network)
	{
		$function = 'check'.ucfirst($network).'AccessToken';
		return $this->$function($socialId, $socialToken);
	}
	
	private function checkGoogleAccessToken($socialId, $socialToken)
	{
    	$this->socialDetails = $this->googleResourceOwner->getUserInformation(array('access_token' => $socialToken));

    	if (null == $this->socialDetails->getUsername() || $this->socialDetails->getUsername() != $socialId) {
    		return false;
    	}

		return true;
	}
	
	private function checkUserExistWithEmail($email)
	{
		return $this->em->getRepository('nkUserBundle:User')
			->findOneBy(
				array('email' => $email)
			);
	}
	
	private function createProfileFromSocialDetails($socialId, $socialToken, $network)
	{
		return $this->userProvider->loadUserByOAuthUserResponse($this->socialDetails);
	}
}
