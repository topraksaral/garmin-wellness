<?php
namespace techgyani\OAuth1\Client\Server;

use League\Oauth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use GuzzleHttp\Exception\BadResponseException;
use League\OAuth1\Client\Credentials\CredentialsInterface;


class Garmin extends Server
{

    const API_URL = "https://connectapi.garmin.com/";
    const USER_API_URL = "https://apis.garmin.com/wellness-api/rest/";

    /**
     * Get the URL for retrieving temporary credentials.
     *
     * @return string
     */
    public function urlTemporaryCredentials()
    {
        return self::API_URL . 'oauth-service-1.0/oauth/request_token';
    }

    /**
     * Get the URL for redirecting the resource owner to authorize the client.
     *
     * @return string
     */
    public function urlAuthorization()
    {
        return 'http://connect.garmin.com/oauthConfirm';
    }

    /**
     * Get the URL retrieving token credentials.
     *
     * @return string
     */
    public function urlTokenCredentials()
    {
        return 'https://connectapi.garmin.com/oauth-service/oauth/access_token';
    }

    /**
     * Get the authorization URL by passing in the temporary credentials
     * identifier or an object instance.
     *
     * @param TemporaryCredentials|string
     *
     * @return string
     */
    public function getAuthorizationUrl($temporaryIdentifier, $arr = [])
    {
        // Somebody can pass through an instance of temporary
        // credentials and we'll extract the identifier from there.
        if ($temporaryIdentifier instanceof TemporaryCredentials) {
            $temporaryIdentifier = $temporaryIdentifier->getIdentifier();
        }
        //$parameters = array('oauth_token' => $temporaryIdentifier, 'oauth_callback' => 'http://70.38.37.105:1225');

        $url = $this->urlAuthorization();

        //$queryString = http_build_query($parameters);
        $queryString = "oauth_token=" . $temporaryIdentifier . "&oauth_callback=" . $this->clientCredentials->getCallbackUri();

        return $this->buildUrl($url, $queryString);
    }


    protected function protocolHeader($method, $uri, CredentialsInterface $credentials, array $bodyParameters = array())
    {
        $parameters = array_merge(
            $this->baseProtocolParameters(),
            $this->additionalProtocolParameters(),
            array(
                'oauth_token' => $credentials->getIdentifier(),

            ),
            $bodyParameters
        );
        $this->signature->setCredentials($credentials);

        $parameters['oauth_signature'] = $this->signature->sign(
            $uri,
            array_merge($parameters, $bodyParameters),
            $method
        );

        return $this->normalizeProtocolParameters($parameters);
    }

    public function getActivitySummary(TokenCredentials $tokenCredentials, $params)
    {
        $client = $this->createHttpClient();
        $query = http_build_query($params);
        $query = '/activities?' . $query;

        $headers = $this->getHeaders($tokenCredentials, 'GET', self::USER_API_URL . $query);
        try {
            $response = $client->get(self::USER_API_URL . $query, [
                'headers' => $headers
            ]);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $statusCode = $response->getStatusCode();

            throw new \Exception(
                "Received error [$body] with status code [$statusCode] when retrieving activity summary."
            );
        }
        return $response->getBody()->getContents();
    }

    public function getActivityDetailSummary(TokenCredentials $tokenCredentials, $params)
    {
        $client = $this->createHttpClient();
        $query = http_build_query($params);
        $query = 'activityDetails?' . $query;

        $headers = $this->getHeaders($tokenCredentials, 'GET', self::USER_API_URL . $query);

        try {
            $response = $client->get(self::USER_API_URL . $query, $headers)->send();
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $statusCode = $response->getStatusCode();

            throw new \Exception(
                "Received error [$body] with status code [$statusCode] when retrieving activity summary."
            );
        }

        return $response->json();
    }

    public function getUserId(TokenCredentials $tokenCredentials, $params)
    {
        $client = $this->createHttpClient();
        $query = http_build_query($params);
        $query = 'user/id?' . $query;

        $headers = $this->getHeaders($tokenCredentials, 'GET', self::USER_API_URL . $query);

        try {
            $response = $client->get(self::USER_API_URL . $query, $headers)->send();
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $statusCode = $response->getStatusCode();

            throw new \Exception(
                "Received error [$body] with status code [$statusCode] when retrieving activity summary."
            );
        }

        return $response->json()['userId'];
    }

    public function urlUserDetails()
    {
    }

    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
    }

    public function userUid($data, TokenCredentials $tokenCredentials)
    {
    }

    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
    }

    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
    }
}
