# Shamir's Secret Sharing Scheme for PHP

DO NOT USE IN PRODUCTION, THIS IS AT A POC STATE

basic example is available in the `examples` directory.
```php
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
// It splits the secret key into 3 shares. (but it could be more)
$points = ShamirSecretSharingHelper::getShareablePoints($secretKey, 3);
var_dump($points);

// there you can store your points at different locations.
// and later get them back to get your secret back

// reconstructing and decrypting
// to reconstruct the secretKey the 3 points are needed.
$decryptedSecretKey = ShamirSecretSharingHelper::reconstructSecret($points);
$decryptedSecret = SodiumHelper::decrypt($encryptedMessage, $nonce, $secretKey);
var_dump("decrypted secret : $decryptedSecret");
```

## Resources

### Shamir's secret Sharing Scheme readings
https://en.wikipedia.org/wiki/Homomorphic_secret_sharing
https://en.wikipedia.org/wiki/Shamir%27s_Secret_Sharing
https://wiki.owasp.org/index.php/Security_by_Design_Principles
https://kariera.future-processing.pl/blog/splitting-your-secrets-with-shamirs-secret-sharing-scheme/
https://www.geeksforgeeks.org/shamirs-secret-sharing-algorithm-cryptography/
https://ericrafaloff.com/shamirs-secret-sharing-scheme/

