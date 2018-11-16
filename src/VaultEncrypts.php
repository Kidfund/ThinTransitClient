<?php
/**
 * @author    : timbroder
 * Date: 9/9/17
 * @copyright 2015 Kidfund Inc
 */

namespace Kidfund\ThinTransportVaultClient;

interface VaultEncrypts
{
    public function encrypt($key, $plaintext, $context = null);

    public function decrypt($path, $cyphertext, $context = null);
}
