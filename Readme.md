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

// initialisation of modulo value, addressing insecure integer arithmetic.
// this would be part of your app configuration or stored elsewhere.
$m = "997"; // chose any prime number (here around 1000)

$points = ShamirSecretSharingHelper::getShareablePoints($secretKey, $m, 3);
var_dump($points);

// there you can store your points at different locations.
// and later get them back to get your secret back

// reconstructing and decrypting
// to reconstruct the secretKey the 3 points are needed along the
$decryptedSecretKey = ShamirSecretSharingHelper::reconstructSecret($points, $m);
$decryptedSecret = SodiumHelper::decrypt($encryptedMessage, $nonce, $decryptedSecretKey);
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

## Security Considerations

Sharmir’s secret sharing scheme offers information-theoretic security, meaning that the math we explored has been proven to be unbreakable, even against an attacker with unlimited computing power. However, the scheme still does contain a couple of known issues.

For example, Shamir’s scheme does not produce verifiable shares, which means that individuals are free to submit fake shares and prevent the correct secret from being reconstructed. An adversarial share holder with enough information can even produce a different share such that SS is reconstructed to a value of their choice. This issue is addressed by verifiable secret sharing schemes such as Feldman’s scheme.

Another issue is that because the length of any given share is equal to the length of an associated secret, the length of a secret is easily leaked. This issue is trivial to fix by simply padding the secret to a fixed length which should be done with sodium as demonstrated in the examples.

Finally, it is important to note that our concerns about security may extend beyond just the scheme itself. For real world cryptography applications, there is often the threat of side channel attacks, in which an attacker attempts to extract useful information from application timing, caching, fault, and more. If this is a concern, careful considerations should be made during development, such as using constant time functions and lookups, preventing memory from paging to disk, and a bunch of other things that are beyond the scope of this library.
