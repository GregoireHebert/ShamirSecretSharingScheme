<?php

declare(strict_types=1);

namespace Gheb\ShamirSecretSharingScheme\Polynomial;

bcscale(1000);

use BCMathExtended\BC;

class LagrangePolynomial
{
    /** @var int Index of x */
    const X = "0";

    /** @var int Index of y */
    const Y = "1";

    /**
     * Interpolate
     *
     * @param array $points The source of our approximation. Should be a set of arrays. Each array
     *                           (point) contains precisely two numbers, an x and y.
     *                           Example array: [[1,2], [2,3], [x,y], ...].
     *
     * @return Polynomial        The lagrange polynomial p(x)
     */
    public static function interpolate(array $points): Polynomial
    {
        // Validate input and sort points
        self::validate($points, $degree = 1);
        $sorted = self::sort($points);

        // Descriptive constants
        $x = self::X;
        $y = self::Y;

        // Initialize
        $n   = \count($sorted);
        $pT = new Polynomial(["0"]);

        for ($i = 0; $i < $n; $i++) {
            $piT = new Polynomial([$sorted[$i][$y]]); // yi
            for ($j = 0; $j < $n; $j++) {
                if ($j === $i) {
                    continue;
                }

                $xi = $sorted[$i][$x];
                $xj = $sorted[$j][$x];

                $LiT = new Polynomial([BC::div("1", BC::sub($xi, $xj)), BC::div("-$xj", BC::sub($xi, $xj))]);
                $piT = $piT->multiply($LiT);
            }
            $pT = $pT->add($piT);
        }

        $pT->roundCoefficients();

        return $pT;
    }

    public static function validate(array $points, int $degree = 2)
    {
        if (\count($points) < $degree) {
            throw new \Exception('You need to have at least $degree sets of coordinates (arrays) for this technique');
        }

        $x_coordinates = [];
        foreach ($points as $point) {
            if (\count($point) !== 2) {
                throw new \Exception('Each array needs to have have precisely two numbers, an x- and y-component');
            }

            $x_component = $point[0];
            if (\in_array($x_component, $x_coordinates)) {
                throw new \Exception('Not a function. Your input array contains more than one coordinate with the same x-component.');
            }
            $x_coordinates[] = $x_component;
        }
    }

    protected static function sort(array $points): array
    {
        \usort($points, static function (array $a, array $b) {
            return $a[0] <=> $b[0];
        });

        return $points;
    }
}
