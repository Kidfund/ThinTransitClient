<?php
/**
 * @author    : timbroder
 * Date: 9/9/17
 * @copyright 2015 Kidfund Inc
 */

namespace Kidfund\ThinTransportVaultClient;

/**
 * Interface VaultEncrypts.
 */
interface VaultEncrypts
{
    public function encrypt(string $key, string $plaintext, string $context = null) : string;

    public function decrypt(string $path, string $cyphertext, string $context = null) : string;
}
