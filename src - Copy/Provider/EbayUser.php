<?php

namespace Nitromike502\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class EbayUser implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @param  array $response
     */
    public function __construct(array $response)
    {
        $this->data = $response;
    }

	public function getId()
	{
		return $this->getUserId();
	}
	
    /**
     * Returns the ID for the user as a string if present.
     *
     * @return string|null
     */
    public function getUserId()
    {
        return $this->getField('userId');
    }

    /**
     * Returns the username for the user as a string if present.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->getField('username');
    }

    /**
     * Returns the account type for the user as a string if present.
     *
     * @return string|null
     */
    public function getAccountType()
    {
        return $this->getField('accountType');
    }

    /**
     * Returns the last name for the user as a string if present.
     *
     * @return string|null
     */
    public function getRegistrationMarketplaceId()
    {
        return $this->getField('registrationMarketplaceId');
    }

    /**
     * Returns all the data obtained about the user.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Returns a field from the Graph node data.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    private function getField($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
}