<?php
namespace Nitromike502\OAuth2\Client\Test;

use GuzzleHttp\ClientInterface;
use Nitromike502\OAuth2\Client\Provider\Ebay;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class EbayTest extends TestCase
{
    /**
     * @var Ebay
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new Ebay([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'mock_redirect_uri'
        ]);
    }

    protected function getJsonFile($file, $encode = false)
    {
        $json = file_get_contents(__DIR__ . '/../' . $file);
        $data = json_decode($json, true);

        if ($encode && json_last_error() == JSON_ERROR_NONE) {
            return $data;
        }

        return $json;
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testScopes()
    {
        $options = [
            'scope' => [uniqid(), uniqid()]
        ];

        $url = $this->provider->getAuthorizationUrl($options);

        $this->assertContains(implode('%20', $options['scope']), $url);
    }

    public function testGetAuthorizationUrl()
    {
        $this->provider->enableSandbox(false);
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('auth.ebay.com', $uri['host']);
        $this->assertEquals('/oauth2/authorize/', $uri['path']);
        
        $this->provider->enableSandbox(true);
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('auth.sandbox.ebay.com', $uri['host']);
        $this->assertEquals('/oauth2/authorize/', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $this->provider->enableSandbox(false);
        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('api.ebay.com', $uri['host']);
        $this->assertEquals('/identity/'.$this->provider->getApiVersion().'/oauth2/token', $uri['path']);
        
        $this->provider->enableSandbox(true);
        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('api.sandbox.ebay.com', $uri['host']);
        $this->assertEquals('/identity/'.$this->provider->getApiVersion().'/oauth2/token', $uri['path']);
    }

    public function testGetResourceOwnerDetailsUrl()
    {
        $token = 'mock_token';
        $tokenMock = $this->getMockBuilder(AccessToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenMock->method('getToken')->willReturn($token);

        $this->provider->enableSandbox(false);
        $url = $this->provider->getResourceOwnerDetailsUrl($tokenMock);
        $uri = parse_url($url);

        $this->assertEquals('apiz.ebay.com', $uri['host']);
        $this->assertEquals('/commerce/identity/v1/user/', $uri['path']);
        
        $this->provider->enableSandbox(true);
        $url = $this->provider->getResourceOwnerDetailsUrl($tokenMock);
        $uri = parse_url($url);

        $this->assertEquals('apiz.sandbox.ebay.com', $uri['host']);
        $this->assertEquals('/commerce/identity/v1/user/', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $accessToken = $this->getJsonFile('access_token_response.json');
        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->method('getBody')->willReturn($accessToken);
        $responseMock->method('getHeader')->willReturn(['content-type' => 'json']);
        $responseMock->method('getStatusCode')->willReturn(200);

        $clientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $clientMock->expects($this->once())->method('send')->willReturn($responseMock);
        $this->provider->setHttpClient($clientMock);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals(time() + 7200, $token->getExpires());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $accessToken = $this->getJsonFile('access_token_response.json');
        $accessTokenInfoJson = $this->getJsonFile('access_token_info.json');
        $accessTokenInfo = json_decode($accessTokenInfoJson, true);

        $postResponseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $postResponseMock->method('getBody')->willReturn($accessToken);
        $postResponseMock->method('getHeader')->willReturn(['content-type' => 'json']);
        $postResponseMock->method('getStatusCode')->willReturn(200);

        $accessTokenInfoResponseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $accessTokenInfoResponseMock->method('getBody')->willReturn($accessTokenInfoJson);
        $accessTokenInfoResponseMock->method('getHeader')->willReturn(['content-type' => 'json']);
        $accessTokenInfoResponseMock->method('getStatusCode')->willReturn(200);

        $clientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $clientMock->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls($postResponseMock, $accessTokenInfoResponseMock);

        $this->provider->setHttpClient($clientMock);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($accessTokenInfo, $user->toArray());
        $this->assertEquals($accessTokenInfo['userId'], $user->getUserId());
        $this->assertEquals($accessTokenInfo['userId'], $user->getId());
        $this->assertEquals($accessTokenInfo['username'], $user->getUsername());
        $this->assertEquals($accessTokenInfo['accountType'], $user->getAccountType());
        $this->assertEquals($accessTokenInfo['registrationMarketplaceId'], $user->getRegistrationMarketplaceId());
    }

    /**
     * @expectedException \Nitromike502\OAuth2\Client\Provider\Exception\EbayProviderException
     */
    public function testExceptionThrownWhenErrorReceived()
    {
        $status = rand(401,599);
        $postResponseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $postResponseMock->method('getBody')->willReturn('{"error": "error_code","error_description": "A human readable error message"}');
        $postResponseMock->method('getHeader')->willReturn(['content-type' => 'json']);
        $postResponseMock->method('getStatusCode')->willReturn($status);

        $clientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $clientMock->expects($this->once())->method('send')->willReturn($postResponseMock);

        $this->provider->setHttpClient($clientMock);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
