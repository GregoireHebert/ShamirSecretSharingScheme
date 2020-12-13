<?php

declare(strict_types=1);

require 'vendor/autoload.php';

bcscale(100);

use Polynomial as MyPolynomial;
use LagrangePolynomial as MyLagrangePolynomial;

// classic symmetric key encryption with padded message to hide it's length

$secretKey = sodium_crypto_secretbox_keygen();
$message = 'Sensitive information';
$blockSize = 16;

// encryption

$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$paddedMessage = sodium_pad($message, $blockSize);
$encryptedMessage = sodium_crypto_secretbox($paddedMessage, $nonce, $secretKey);

// bin to dec
$number = gmp_import($secretKey);
$dec = gmp_strval($number);

// split into N shares here 3 for example it mean I need a polynomial function of degree 2. (k-1)
$k = 3;

// including our secret, we need 2 more numbers at random to plot our function.
// they are coefficient, no real needs to be high.
$numbers = array_map(static function () {
    return (string)random_int(0, 100);
}, array_fill(0, $k-1, null));

$polynomial = new MyPolynomial([...$numbers, $dec]);

// let generate 3 points to dispatch
$points = array_map(static function () use ($polynomial){
    $rand = random_int(0, 100);
    return [(string)$rand, $polynomial($rand)];
}, array_fill(0, $k, null));

// reconstructing
$lp = MyLagrangePolynomial::interpolate($points);
$rdec = $lp(0);
var_dump($rdec, $dec);
var_dump($rdec == $dec);die;

$rsecret = hex2bin(gmp_strval(gmp_init($rdec, 10), 16));

// decryption
$decryptedPaddedMessage = sodium_crypto_secretbox_open($encryptedMessage, $nonce, $rsecret);
$decryptedMessage = sodium_unpad($decryptedPaddedMessage, $blockSize);

var_dump($decryptedMessage);
