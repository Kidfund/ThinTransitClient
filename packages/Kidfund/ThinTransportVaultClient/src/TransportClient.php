<?php

namespace Kidfund\ThinTransportVaultClient;

use GuzzleHttp\Client;
use Log;

class TransportClient
{

    private $serverUrl;
    private $token;
    private $client;

    /**
     * TransportClient constructor.
     * @param string $serverUrl The vault server E.G. http://192.168.10.10:8200
     * @param string $token Token with the following (or more granular) access:
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
     *
     */
    public function __construct($serverUrl, $token)
    {
        $this->serverUrl = $serverUrl;
        $this->token = $token;

        $this->client = new Client([
            'base_uri' => $this->serverUrl,
            'timeout'  => 2.0
        ]);
    }

    /**
     * @param $key
     * @param $plaintext
     * @param null $context
     * @return mixed
     */
    public function encrypt($key , $plaintext, $context = null) {
        $url = '/transit/encrypt/' . $key;

        Log::debug("Encrypting");
        Log::debug([
            "key" => $key,
            "plaintext" => $plaintext,
            "context" => $context,
        ]);

        $encoded = base64_encode($plaintext);

        Log::debug("encoded: $encoded");

        $data = ['plaintext' => $encoded];

        Log::debug('Enc ' . $key . ':' . $plaintext . ' as' . $encoded . "\n");



        if ($context) {
            $encodedContext = base64_encode($context);
            $data['context'] = $encodedContext;
        }

        return $this->command($url, 'POST', json_encode($data))['data']['ciphertext'];
    }

    /**
     * @param $path
     * @param $cyphertext
     * @param null $context
     * @return string
     */
    public function decrypt($path, $cyphertext, $context = null) {

        Log::debug("Decrypting");
        Log::debug([
            "path" => $path,
            "cyphertext" => $cyphertext,
            "context" => $context
        ]);

        $url = '/transit/decrypt/' . $path;
        $data = ['ciphertext' => $cyphertext];

        if ($context) {
            $encodedContext = base64_encode($context);
            $data['context'] = $encodedContext;
        }

        $encoded = $this->command($url, 'POST', json_encode($data))['data']['plaintext'];

        Log::debug("Encoded: $encoded");

        $plaintext = base64_decode($encoded);

        Log::debug("Plaintext: $plaintext");

        return $plaintext;

    }

    /**
     * @param $url
     * @param string $method
     * @param array $payload
     * @return mixed
     * @throws GuzzleHttp\Exception\ClientException
     */
    private function command($url, $method = 'POST', $payload = []) {
        Log::debug($payload);

        $response = $this->client->request($method, 'v1' . $url, [
            'headers' => [
                'X-Vault-Token' => $this->token,
                'Content-Type'  => 'application/json'
            ],
            'body' => $payload
        ]);

        return json_decode($response->getBody(), true);
    }
}
