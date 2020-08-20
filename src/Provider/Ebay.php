<?php

namespace Nitromike502\OAuth2\Client\Provider;

use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\EbayProviderException;
use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;

class Ebay extends AbstractProvider
{
    use BearerAuthorizationTrait;
    
    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'individualAccount';
    
    const BASE_SANDBOX_URL = 'https://api.sandbox.ebay.com';
    const BASE_PRODUCTION_URL = 'https://api.ebay.com';
    const AUTHORIZE_SANDBOX_URL = 'https://auth.sandbox.ebay.com';
    const AUTHORIZE_PRODUCTION_URL = 'https://auth.ebay.com';
    
    private $_enableSandboxMode;

    private $api_version = 'v1';
       
    protected $scopes = [];
    
    /**
     * @param array $options
     * @param array $collaborators
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($options = [], array $collaborators = [])
    {
        if (array_key_exists('sandbox',$options))
        {
            $this->enableSandbox((bool) $options['sandbox']);
        }
        $this->clientId = $options['clientId'];
        $this->clientSecret = $options['clientSecret'];
        $this->redirectUri = $options['redirectUri'];
        
        if (isset($options['scopes']))
        {
            $this->setScopes($options['scopes']);
        }
        
        parent::__construct($options, $collaborators);
    }
    
    public function enableSandbox($enable = true)
    {
        $this->_enableSandboxMode = $enable;
    }
    
    public function getBaseEbayUrl()
    {
        return $this->_enableSandboxMode ? static::BASE_SANDBOX_URL : static::BASE_PRODUCTION_URL;
    }
    
    public function setApiVersion($version)
    {
        $this->api_version = $version;
        return $this;
    }
    
    public function getApiVersion()
    {
        return $this->api_version;
    }
    
    /**
     * params for application user authorization request
     */
    protected function getAuthorizationParameters(array $options)
    {
        $params = parent::getAuthorizationParameters($options);
        $params['redirect_uri'] = $this->redirectUri;
        unset($params['approval_prompt']);
        return $params;
    }

    /**
     * Generates URL for user to authorize aplication
     */
    public function getBaseAuthorizationUrl()
    {
        $url = $this->_enableSandboxMode ? static::AUTHORIZE_SANDBOX_URL : static::AUTHORIZE_PRODUCTION_URL;
        return $url.'/oauth2/authorize/';
    }

    /**
     * generates base acccess token URL
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getBaseEbayUrl().'/identity/'.$this->api_version.'/oauth2/token';
    }

    public function setScopes($scopes = [])
    {
        $scopes = array_unique(array_filter($this->scopes));
        $scopes[] = '';
        $this->scopes = $scopes;
        return $this;
    }
    
    public function getDefaultScopes()
    {
        return $this->scopes;
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->_enableSandboxMode 
            ? 'https://apiz.sandbox.ebay.com/commerce/identity/v1/user/' 
            : 'https://apiz.ebay.com/commerce/identity/v1/user/';
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new EbayUser($response);
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) 
        {
            $message = $data['error_description'];
            throw new EbayProviderException($message, 0, $data);
        }
    }
    
    /**
     * Returns the string that should be used to separate scopes when building
     * the URL for requesting an access token.
     *
     * @return string Scope separator, defaults to ','
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }
}










