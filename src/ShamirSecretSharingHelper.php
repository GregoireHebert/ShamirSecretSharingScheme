<?php

declare(strict_types=1);

namespace Gheb\ShamirSecretSharingScheme;

use Gheb\ShamirSecretSharingScheme\Polynomial\LagrangePolynomial;
use Gheb\ShamirSecretSharingScheme\Polynomial\Polynomial;

final class ShamirSecretSharingHelper
{
    /**
     * This method will split your secret key into N shares (default 3).
     */
    public static function getShareablePoints(string $secretKey, int $shares = 3): array
    {
        // bin to dec
        $number = gmp_import($secretKey);
        $dec = gmp_strval($number);

        // split into N shares here 3 for example it mean I need a polynomial function of degree 2. (k-1)
        $k = $shares;

        // including our secret, we need 2 more numbers at random to plot our function.
        // they are coefficient, no real needs to be high.
        $numbers = array_map(static function () {
            return (string)random_int(0, 99);
        }, array_fill(0, $k-1, null));

        $polynomial = new Polynomial([...$numbers, $dec]);

        // let generate 3 points to dispatch
        return array_map(static function () use ($polynomial){
            $rand = random_int(0, 100);
            return ["$rand", $polynomial("$rand")];
        }, array_fill(0, $k, null));
    }

    public static function reconstructSecret(array $points): ?string
    {
        $lp = LagrangePolynomial::interpolate($points);
        $rdec = $lp("0");

        // TODO for some reasons, keys like "1472502186037957747441234705645302953943147992477563238054054428338355254101" fail to be converted
        return hex2bin(gmp_strval(gmp_init($rdec, 10), 16)) ?: null;
    }
}
