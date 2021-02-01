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

namespace Gheb\ShamirSecretSharingScheme\Polynomial;

bcscale(1000);

use BCMathExtended\BC;

class LagrangePolynomial
{
    /** @var int Index of x */
    const X = '0';

    /** @var int Index of y */
    const Y = '1';

    /**
     * Interpolate.
     *
     * @param array  $points The source of our approximation. Should be a set of arrays. Each array
     *                       (point) contains precisely two numbers, an x and y.
     *                       Example array: [[1,2], [2,3], [x,y], ...].
     * @param string $m      the value used to perform modulo arithmetics on Y values
     *
     * @return Polynomial The lagrange polynomial p(x)
     */
    public static function interpolate(array $points, string $m): Polynomial
    {
        // recall modulo arithmetic with a=mq+r formula
        $recalledPoints = self::moduloRecall($points, $m);

        // Validate input and sort points
        self::validate($recalledPoints, $degree = 1);
        $sorted = self::sort($recalledPoints);

        // Initialize
        $n = \count($sorted);
        $pT = new Polynomial(['0'], $m);

        for ($i = 0; $i < $n; ++$i) {
            $piT = new Polynomial([$sorted[$i][self::Y]], $m); // yi
            for ($j = 0; $j < $n; ++$j) {
                if ($j === $i) {
                    continue;
                }

                $xi = $sorted[$i][self::X];
                $xj = $sorted[$j][self::X];

                $LiT = new Polynomial([BC::div('1', BC::sub($xi, $xj)), BC::div("-$xj", BC::sub($xi, $xj))], $m);
                $piT = $piT->multiply($LiT, $m);
            }
            $pT = $pT->add($piT, $m);
        }

        $pT->roundCoefficients();

        return $pT;
    }

    private static function validate(array $points, int $degree = 2): void
    {
        if (\count($points) < $degree) {
            throw new \Exception('You need to have at least $degree sets of coordinates (arrays) for this technique');
        }

        $x_coordinates = [];
        foreach ($points as $point) {
            if (2 !== \count($point)) {
                throw new \Exception('Each array needs to have have precisely two numbers, an x- and y-component');
            }

            $x_component = $point[0];
            if (\in_array($x_component, $x_coordinates, true)) {
                throw new \Exception('Not a function. Your input array contains more than one coordinate with the same x-component.');
            }
            $x_coordinates[] = $x_component;
        }
    }

    private static function sort(array $points): array
    {
        usort($points, static function (array $a, array $b) {
            return $a[0] <=> $b[0];
        });

        return $points;
    }

    private static function moduloRecall(array $points, string $m): array
    {
        $points = array_map(static function ($el) use ($m) {
            $el[1] = BC::add(BC::mul($m, $el[2]), $el[1]);
            unset($el[2]);

            return $el;
        }, $points);

        return $points;
    }
}
