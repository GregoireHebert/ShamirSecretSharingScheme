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

class Polynomial
{
    /** @var string */
    public $quotient;

    /** @var string */
    private $degree;

    /** @var array */
    private $coefficients;

    /** @var string  */
    private $modulo;

    public function __construct(array $coefficients, string $modulo)
    {
        // Remove coefficients that are leading zeros
        $coefficients = array_filter($coefficients);

        // If coefficients remain, re-index them. Otherwise return [0] for p(x) = 0
        $coefficients = array_values($coefficients);
        $coefficients = !empty($coefficients) ? $coefficients : ['0'];

        $this->degree = (string) (\count($coefficients) - 1);
        $this->coefficients = $coefficients;
        $this->modulo = $modulo;
    }

    public function getDegree(): string
    {
        return $this->degree;
    }

    public function __invoke(string $x, bool $applyModulo = true): string
    {
        // Start with the zero polynomial
        $polynomial = static function () {
            return '0';
        };

        // Iterate over each coefficient to create a callback function for each term
        for ($i = 0; $i < (int)$this->degree + 1; ++$i) {
            // Create a callback function for the current term
            $term = function ($x) use ($i) {
                return BC::mul($this->coefficients[$i], BC::pow($x, BC::sub($this->degree, "$i")));
            };

            // Add the new term to the polynomial
            $polynomial = self::arithmeticAdd($polynomial, $term);
        }

        $value = $polynomial($x);

        // return the value before applying the modulo operation. Needed to get the recomputed secret
        if (!$applyModulo) {
            return $value;
        }

        $reminders = BC::mod($value, $this->modulo);
        $this->quotient = BC::div(BC::sub($value, $reminders), $this->modulo);

        return $reminders;
    }

    public function roundCoefficients(): void
    {
        foreach ($this->coefficients as &$coefficient) {
            if ($coefficient < 100) {
                $coefficient = (string) round($coefficient);
                continue;
            }
            if (false !== $p = strpos($coefficient, '.9')) {
                $coefficient = BC::add(substr($coefficient, 0, $p), '1');
                continue;
            }
            if (false !== $p = strpos($coefficient, '.0')) {
                $coefficient = substr($coefficient, 0, strpos($coefficient, '.'));
            }
        }
    }

    public function add($polynomial, string $p): self
    {
        $polynomial = $this->checkNumericOrPolynomial($polynomial, $p);

        $coefficientsA = $this->coefficients;
        $coefficientsB = $polynomial->coefficients;

        // If degrees are unequal, make coefficient array sizes equal so we can do component-wise addition
        $degreeDifference = BC::sub($this->getDegree(), $polynomial->getDegree());
        if ('0' !== $degreeDifference) {
            $zeroArray = array_fill(0, (int) abs((float) $degreeDifference), '0');
            $coefficientsA = array_merge($zeroArray, $degreeDifference < 0 ? $coefficientsA : $coefficientsB);
        }

        $coefficientsSum = self::multiAdd($coefficientsA, $coefficientsB);

        return new self($coefficientsSum, $p);
    }

    public function multiply($polynomial, string $modulo): self
    {
        $polynomial = $this->checkNumericOrPolynomial($polynomial, $modulo);
        // Calculate the degree of the product of the polynomials
        $productDegree = BC::add($this->degree, $polynomial->degree);

        // Reverse the coefficients arrays so you can multiply component-wise
        $coefficientsA = array_reverse($this->coefficients);
        $coefficientsB = array_reverse($polynomial->coefficients);

        // Start with an array of coefficients that all equal 0
        $productCoefficients = array_fill(0, (int) $productDegree + 1, '0');

        // Iterate through the product of terms component-wise
        for ($i = 0; $i < (int)$this->degree + 1; ++$i) {
            for ($j = 0; $j < (int)$polynomial->degree + 1; ++$j) {
                // Calculate the degree of the current product
                $degree = BC::sub($productDegree, (string) ($i + $j));

                // Calculate the product of the coefficients
                $product = BC::mul($coefficientsA[$i], $coefficientsB[$j]);

                // Add the product to the existing coefficient of the current degree
                $productCoefficients[(int) $degree] = BC::add($productCoefficients[(int) $degree], $product);
            }
        }

        return new self($productCoefficients, $modulo);
    }

    private static function arithmeticAdd(callable ...$args): callable
    {
        $sum = static function ($x, ...$args) {
            $function = '0';
            foreach ($args as $arg) {
                $function = BC::add($function, $arg($x));
            }

            return $function;
        };

        return static function ($x) use ($args, $sum) {
            return $sum($x, ...$args);
        };
    }

    private static function multiAdd(array ...$points): array
    {
        self::checkArrayLengths($points);

        $number_of_arrays = \count($points);
        $length_of_arrays = \count($points[0]);
        $sums = array_fill(0, $length_of_arrays, '0');

        for ($i = 0; $i < $length_of_arrays; ++$i) {
            for ($j = 0; $j < $number_of_arrays; ++$j) {
                $sums[$i] = BC::add($sums[$i], $points[$j][$i]);
            }
        }

        return $sums;
    }

    private static function checkArrayLengths(array $points): bool
    {
        if (\count($points) < 2) {
            throw new \Exception('Need at least two points to map over');
        }

        $n = \count($points[0]);
        foreach ($points as $array) {
            if (\count($array) !== $n) {
                throw new \Exception('One point is missing a coordinate.');
            }
        }

        return true;
    }

    private function checkNumericOrPolynomial($polynomial, string $modulo): self
    {
        if ($polynomial instanceof self) {
            return $polynomial;
        }

        if (\is_string($polynomial) && \is_numeric($polynomial)) {
            return new self([$polynomial], $modulo);
        }

        throw new \Exception('Input must be a Polynomial or a numeric string');
    }
}
