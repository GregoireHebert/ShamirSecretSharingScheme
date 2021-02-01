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

require '../vendor/autoload.php';

use Gheb\ShamirSecretSharingScheme\ShamirSecretSharingHelper;
use Gheb\ShamirSecretSharingScheme\SodiumHelper;

$secretKey = SodiumHelper::generateKey(); // this will be stored in shares.
$nonce = SodiumHelper::generateNonce(); // this could be part of your app configuration

$secret = 'Sensitive information';
var_dump("secret : $secret");

// symmetric key encryption with padded message to hide it's length. This does not matter, it's for show !
$encryptedMessage = SodiumHelper::encrypt($secret, $secretKey, $nonce);

// This is the best part !
// It splits the secret key into 3 shares. (but it could be more)

// initialisation of modulo value, addressing insecure integer arithmetic.
// this would be part of your app configuration or stored elsewhere.
$m = '997'; // chose any prime number (here around 1000)

$points = ShamirSecretSharingHelper::getShareablePoints($secretKey, $m, 3);
var_dump($points);

// there you can store your points at different locations.
// and later get them back to get your secret back

// reconstructing and decrypting
// to reconstruct the secretKey the 3 points are needed along the
$decryptedSecretKey = ShamirSecretSharingHelper::reconstructSecret($points, $m);
$decryptedSecret = SodiumHelper::decrypt($encryptedMessage, $nonce, $decryptedSecretKey);
var_dump("decrypted secret : $decryptedSecret");
