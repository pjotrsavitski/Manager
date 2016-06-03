<?php

namespace SocialiteProviders\Manager\OAuth2;

use Illuminate\Support\Arr;
use GuzzleHttp\ClientInterface;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\Contracts\OAuth2\ProviderInterface;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Manager\ConfigTrait;
use Laravel\Socialite\Two\AbstractProvider as BaseProvider;

abstract class AbstractProvider extends BaseProvider implements ProviderInterface
{
    use ConfigTrait;

    /**
     * @var array
     */
    protected $credentialsResponseBody;

    public static function serviceContainerKey($providerName)
    {
        return SocialiteWasCalled::SERVICE_CONTAINER_PREFIX.$providerName;
    }

    /**
     * @return \SocialiteProviders\Manager\OAuth2\User
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $user = $this->mapUserToObject($this->getUserByToken(
            $token = $this->parseAccessToken($response)
        ));

        $this->credentialsResponseBody = $response;

        if ($user instanceof User) {
            return $user->setAccessTokenResponseBody($this->credentialsResponseBody);
        }

        return $user->setToken($token)
                    ->setRefreshToken(Arr::get($response, 'refresh_token'))
                    ->setExpiresIn(Arr::get($response, 'expires_in'));
    }

    /**
     * Get the access token from the token response body.
     *
     * @param string $body
     *
     * @return string
     */
    protected function parseAccessToken($body)
    {
        return Arr::get($body, 'access_token');
    }
}
