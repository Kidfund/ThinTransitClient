<?php

namespace Kidfund\ThinTransportVaultClient;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

/**
 * Class TransitClient.
 */
class TransitClient implements VaultEncrypts
{
    private $serverUrl;
    private $token;
    private $client;

    /**
     * TransportClient constructor.
     *
     * @param string $serverUrl The vault server E.G. http://192.168.20.20:8200
     * @param string $token     Token with the following (or more granular) access:
     *
     * path "transit/decrypt/*" {
     *   capabilities = ["create", "update"]
     * }
     *
     * path "transit/encrypt/*" {
     *  capabilities = ["create", "update"]
     * }
     *
     * also see vault.policy.web.json.example
     * @param ClientInterface $client
     */
    public function __construct(string $serverUrl, string $token, ClientInterface $client = null)
    {
        $this->serverUrl = $serverUrl;
        $this->token = $token;
        if ($client == null) {
            $this->client = new Client([
                'base_uri' => $this->serverUrl,
                'timeout'  => 5.0,
            ]);
        } else {
            $this->client = $client;
        }
    }

    /**
     * @param string      $key
     * @param string      $plaintext
     * @param string|null $context
     *
     * @throws VaultException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return string
     */
    public function encrypt(string $key, string $plaintext, string $context = null) : string
    {
        $url = '/transit/encrypt/'.$key;

        Log::debug('Encrypting');
        Log::debug([
            'key'       => $key,
            'plaintext' => $plaintext,
            'context'   => $context,
        ]);

        $data = $this->getEncryptPayload($key, $plaintext, $context);

        $response = $this->command($url, 'POST', $data);

        if ($response == null) {
            throw new VaultException('Empty response from Vault server');
        }

        if (!array_key_exists('data', $response)) {
            throw new VaultException('Vault Encrypt: data not returned');
        }

        if (!array_key_exists('ciphertext', $response['data'])) {
            throw new VaultException('Vault Encrypt: ciphertext not returned');
        }

        return $response['data']['ciphertext'];
    }

    /**
     * @param string      $key
     * @param string      $plaintext
     * @param string|null $context
     *
     * @return array
     */
    protected function getEncryptPayload(string $key, string $plaintext, string $context = null) : array
    {
        $encoded = $this->encode($plaintext);

        Log::debug("encoded: $encoded");

        $data = ['plaintext' => $encoded];

        Log::debug('Enc '.$key.':'.$plaintext.' as'.$encoded."\n");

        if ($context) {
            $encodedContext = $this->encode($context);
            $data['context'] = $encodedContext;
        }

        return $data;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function encode(string $string) : string
    {
        return base64_encode($string);
    }

    /**
     * @param string $base64
     *
     * @return string
     */
    protected function decode(string $base64) : string
    {
        return base64_decode($base64);
    }

    /**
     * @param string      $path
     * @param string      $cyphertext
     * @param string|null $context
     *
     * @throws VaultException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return string
     */
    public function decrypt(string $path, string $cyphertext, string $context = null) : string
    {
        Log::debug('Decrypting');
        Log::debug([
            'path'       => $path,
            'cyphertext' => $cyphertext,
            'context'    => $context,
        ]);

        $url = '/transit/decrypt/'.$path;
        $data = $this->getDecryptPayload($cyphertext, $context);

        $response = $this->command($url, 'POST', $data);

        $encoded = $response['data']['plaintext'];

        Log::debug("Encoded: $encoded");

        $plaintext = $this->decode($encoded);

        Log::debug("Plaintext: $plaintext");

        return $plaintext;
    }

    /**
     * @param string      $cyphertext
     * @param string|null $context
     *
     * @return array
     */
    protected function getDecryptPayload(string $cyphertext, string $context = null) : array
    {
        $data = ['ciphertext' => $cyphertext];

        if ($context) {
            $encodedContext = $this->encode($context);
            $data['context'] = $encodedContext;
        }

        return $data;
    }

    /**
     * @param array $payload
     *
     * @return array
     */
    protected function getCommandPayload(array $payload) : array
    {
        $payload = [
            'headers' => [
                'X-Vault-Token' => $this->token,
                'Content-Type'  => 'application/json',
            ],
            'json' => $payload,
        ];

        return $payload;
    }

    /**
     * @param $url
     * @param string $method
     * @param array  $payload
     *
     * @throws VaultException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return mixed
     */
    private function command(string $url, string $method = 'POST', array $payload = [])
    {
        Log::debug($payload);

        try {
            $response = $this->client->request($method, 'v1'.$url,
                $this->getCommandPayload($payload)
            );
        } catch (ServerException $e) {
            $exceptionResponse = $e->getResponse();
            $reasonPhrase = $exceptionResponse->getReasonPhrase();
            $statusCode = $exceptionResponse->getStatusCode();

            throw new VaultException($reasonPhrase, $statusCode);
        } catch (ClientException $e) {
            $exceptionResponse = $e->getResponse();
            $reasonPhrase = $exceptionResponse->getReasonPhrase();
            $statusCode = $exceptionResponse->getStatusCode();

            throw new VaultException($reasonPhrase, $statusCode);
        }

        return $this->parseResponse($response);
    }

    /**
     * @param $response
     *
     * @return mixed
     */
    private function parseResponse($response)
    {
        return json_decode($response->getBody(), true);
    }
}
