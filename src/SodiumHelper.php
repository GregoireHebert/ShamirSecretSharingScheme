<?php

/*
 * This file is part of the Gheb\ShamirSecretSharingScheme library.
 *
 * (c) Grégoire Hébert <gregoire@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gheb\ShamirSecretSharingScheme;

final class SodiumHelper
{
    public const BLOCK_SIZE = 16;

    public static function generateKey(): string
    {
        return sodium_crypto_secretbox_keygen();
    }

    /**
     * @return string 24 Bytes long string
     */
    public static function generateNonce(): string
    {
        return random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    }

    public static function encrypt(string $data, string $secretKey, string $nonce): string
    {
        $paddedMessage = sodium_pad($data, self::BLOCK_SIZE);

        return sodium_crypto_secretbox($paddedMessage, $nonce, $secretKey);
    }

    public static function decrypt(string $encryptedMessage, string $nonce, string $secretkey): string
    {
        $decryptedPaddedMessage = sodium_crypto_secretbox_open($encryptedMessage, $nonce, $secretkey);

        return sodium_unpad($decryptedPaddedMessage, self::BLOCK_SIZE);
    }
}
