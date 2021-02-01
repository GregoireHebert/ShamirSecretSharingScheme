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
    public $quotient;

    /** @var string */
    private $degree;

    /** @var array */
    private $coefficients;

    private $m;

    public function __construct(array $coefficients, $m = 0)
    {
        // Remove coefficients that are leading zeros
        $coefficients = array_filter($coefficients);
        $coefficients = array_values($coefficients);

        // If coefficients remain, re-index them. Otherwise return [0] for p(x) = 0
        $coefficients = !empty($coefficients) ? $coefficients : ['0'];

        $this->degree = (string) (\count($coefficients) - 1);
        $this->coefficients = $coefficients;
        $this->m = $m;
    }

    public function getDegree(): string
    {
        return $this->degree;
    }

    public function __invoke(string $x, $final = false)
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

        if ($final) {
            return $value;
        }

        $reminders = BC::mod($value, $this->m);
        $this->quotient = BC::div(BC::sub($value, $reminders), $this->m);

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

    public function add($polynomial, $p): self
    {
        $polynomial = $this->checkNumericOrPolynomial($polynomial, $p);

        $coefficientsA = $this->coefficients;
        $coefficientsB = $polynomial->coefficients;

        // If degrees are unequal, make coefficient array sizes equal so we can do component-wise addition
        $degreeDifference = BC::sub($this->getDegree(), $polynomial->getDegree());
        if ('0' !== $degreeDifference) {
            $zeroArray = array_fill(0, (int) abs((float) $degreeDifference), '0');
            if ($degreeDifference < 0) {
                $coefficientsA = array_merge($zeroArray, $coefficientsA);
            } else {
                $coefficientsB = array_merge($zeroArray, $coefficientsB);
            }
        }

        $coefficientsSum = self::multiAdd($coefficientsA, $coefficientsB);

        return new self($coefficientsSum, $p);
    }

    public static function arithmeticAdd(callable ...$args): callable
    {
        $sum = static function ($x, ...$args) {
            $function = '0';
            foreach ($args as $arg) {
                $function = BC::add($function, $arg($x));
            }

            return $function;
        };

        return function ($x) use ($args, $sum) {
            return $sum($x, ...$args);
        };
    }

    public static function multiAdd(array ...$arrays): array
    {
        self::checkArrayLengths($arrays);

        $number_of_arrays = \count($arrays);
        $length_of_arrays = \count($arrays[0]);
        $sums = array_fill(0, $length_of_arrays, '0');

        for ($i = 0; $i < $length_of_arrays; ++$i) {
            for ($j = 0; $j < $number_of_arrays; ++$j) {
                $sums[$i] = BC::add($sums[$i], $arrays[$j][$i]);
            }
        }

        return $sums;
    }

    private static function checkArrayLengths(array $arrays): bool
    {
        if (\count($arrays) < 2) {
            throw new \Exception('Need at least two arrays to map over');
        }

        $n = \count($arrays[0]);
        foreach ($arrays as $array) {
            if (\count($array) !== $n) {
                throw new \Exception('Lengths of arrays are not equal');
            }
        }

        return true;
    }

    public function multiply($polynomial, $m): self
    {
        $polynomial = $this->checkNumericOrPolynomial($polynomial, $m);
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

        return new self($productCoefficients);
    }

    private function checkNumericOrPolynomial($input, $m): self
    {
        if ($input instanceof self) {
            return $input;
        }

        if (\is_string($input)) {
            return new self([$input], $m);
        }

        throw new \Exception('Input must be a Polynomial or a number');
    }
}
