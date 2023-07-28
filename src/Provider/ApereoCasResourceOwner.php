<?php

namespace Ajtak\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class ApereoCasResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * Domain
     *
     * @var string
     */
    protected $domain;

    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array  $response
     */
    public function __construct(array $response = array())
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id
     *
     * @return string|null
     */
    public function getId()
    {
        return \array_key_exists('sub', $this->response) ? $this->response['sub'] : null;
    }

    /**
     * Get resource owner email
     *
     * @return string|null
     */
    public function getEmail()
    {
        return \array_key_exists('email', $this->response) ? $this->response['email'] : null;
    }

    /**
     * Get resource owner name
     *
     * @return string|null
     */
    public function getName()
    {
        return \array_key_exists('name', $this->response) ? $this->response['name'] : null;
    }

    /**
     * Get resource owner url
     *
     * @return string|null
     */
    public function getUrl()
    {
        $urlParts = array_filter([$this->domain]);

        return count($urlParts) ? implode('/', $urlParts) : null;
    }


    /**
     * Set resource owner domain
     *
     * @param string $domain
     *
     * @return ApereoCasResourceOwner
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }


    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
