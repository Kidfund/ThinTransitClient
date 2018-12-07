<?php

use \Illuminate\Container\Container as Container;
use \Illuminate\Support\Facades\Facade as Facade;
use Kidfund\ThinTransportVaultClient\TransitClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @author: timbroder
 * Date: 4/13/16
 * @copyright 2018 Kidfund Inc
 */
class ThinTransitClientIntegrationTest extends TestCase
{
    // TODO provide setup instructions to run vault

    const VAULT_ADDR='http://kidfund-dev-web.app:8200';
    const VAULT_TOKEN='6a4a2fd1-0d72-40a3-74f1-0b303e943fda';
    const VAULT_ROOT_TOKEN = 'ec25daef14e-3bfb81d9-c695-8a8b-2d27';
    const VAULTTEST_PREFIX = 'thingtransport_test';
    const VALID_STRING = 'the quick brown fox';
    const VAULT_PREFIX = 'vault:v1:';

    /**
     * Get env variables. these are set in phpunit.xml or can be overridden on the CLI
     */
    public function setUp()
    {
        parent::setUp();

        $app = new Container();
        $app->singleton('app', Container::class);
        $app->bind('log', function($app)
        {
            return new NullLogger();
        });

        Facade::setFacadeApplication($app);
    }

    /**
     * @param bool $root
     * @param null $addr
     * @return TransitClient
     */
    public function getRealVaultClient($root = false, $addr = null)
    {
        if ($root) {
            $token = self::VAULT_ROOT_TOKEN;
        } else {
            $token = self::VAULT_TOKEN;
        }

        if ($addr == null) {
            $addr = self::VAULT_ADDR;
        }
        return new TransitClient($addr, $token);
    }

    /**
     * @param $plaintext
     * @param null $client
     *
     * @return mixed
     * @throws \Kidfund\ThinTransportVaultClient\StringException
     * @throws \Kidfund\ThinTransportVaultClient\VaultException*
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getEncryptResponse($plaintext, $client = null)
    {
        if ($client == null) {
            $client = $this->getRealVaultClient();
        }

        $response = $client->encrypt($this::VAULTTEST_PREFIX, $plaintext);

        return $response;
    }

    /**
     * @param $ciphertext
     * @param null $client
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Kidfund\ThinTransportVaultClient\StringException
     * @throws \Kidfund\ThinTransportVaultClient\VaultException
     */
    public function getDecryptResponse($ciphertext, $client = null)
    {
        if ($client == null) {
            $client = $this->getRealVaultClient();
        }

        $response = $client->decrypt($this::VAULTTEST_PREFIX, $ciphertext);

        return $response;
    }

    /**
     * @test
     * @group VaultEndToEnd
     * @group EndToEnd
     * @expectedException \Kidfund\ThinTransportVaultClient\VaultException
     */
    public function it_handles_bad_url_gracefully()
    {
        // will return a response but empty
        $client = $this->getRealVaultClient(false, 'https://www.kidfund.us');
        $this->getEncryptResponse($this::VALID_STRING, $client);
    }

    /**
     * @test
     * @group VaultEndToEnd
     * @group EndToEnd
     * @expectedException \GuzzleHttp\Exception\ConnectException
     */
    public function it_handles_bad_url_gracefully2()
    {
        // should cause Guzzle exception
        $client = $this->getRealVaultClient(false, 'https://www.timbroder.com:8200');
        $this->getEncryptResponse($this::VALID_STRING, $client);
    }

//    /**
//     * @test
//     * @group VaultEndToEnd
//     * @group EndToEnd
//     */
//    public function it_encrypts_something()
//    {
//        $response = $this->getEncryptResponse($this::VALID_STRING);
//        $this->assertContains($this::VAULT_PREFIX, $response);
//    }
//
//    /**
//     * @test
//     * @group VaultEndToEnd
//     * @group EndToEnd
//     */
//    public function it_decrypts_something()
//    {
//        $client = $this->getRealVaultClient();
//        $ciphertext = $this->getEncryptResponse($this::VALID_STRING, $client);
//        unset($client);
//        $client = $this->getRealVaultClient();
//        $response = $this->getDecryptResponse($ciphertext, $client);
//
//        $this->assertContains($this::VALID_STRING, $response);
//    }
}
