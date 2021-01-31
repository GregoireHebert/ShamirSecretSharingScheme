<?php

declare(strict_types=1);

require '../vendor/autoload.php';

use Gheb\ShamirSecretSharingScheme\SodiumHelper;
use Gheb\ShamirSecretSharingScheme\ShamirSecretSharingHelper;

$secretKey = SodiumHelper::generateKey(); // this will be stored in shares.
$nonce = SodiumHelper::generateNonce(); // this could be part of your app configuration

$secret = 'Sensitive information';
var_dump("secret : $secret");

// symmetric key encryption with padded message to hide it's length. This does not matter, it's for show !
$encryptedMessage = SodiumHelper::encrypt($secret, $secretKey, $nonce);

// This is the best part !
// It splits the secret key into 5 (or more) shares. (but it could be more)
$points = ShamirSecretSharingHelper::getShareablePoints($secretKey, 3, 5);
var_dump($points);

// there you can store your points at different locations.
// and later get them back to get your secret back

// reconstructing and decrypting
// to reconstruct the secretKey at least 3 points are needed.s
$decryptedSecretKey = ShamirSecretSharingHelper::reconstructSecret([$points[0], $points[2], $points[3]]);
$decryptedSecret = SodiumHelper::decrypt($encryptedMessage, $nonce, $decryptedSecretKey);
var_dump("decrypted secret : $decryptedSecret");
