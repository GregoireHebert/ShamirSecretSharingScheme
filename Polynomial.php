<?php

declare(strict_types=1);

class Polynomial
{
    /** @var int */
    private $degree;

    /** @var array */
    private $coefficients;

    /** @var string */
    private $variable;

    public function __construct(array $coefficients, string $variable = "x")
    {
        // Remove coefficients that are leading zeros
        $coefficients = array_filter($coefficients);

        // If coefficients remain, re-index them. Otherwise return [0] for p(x) = 0
        $coefficients = !empty($coefficients) ? $coefficients : [0];

        $this->degree       = count($coefficients) - 1;
        $this->coefficients = $coefficients;
        $this->variable     = $variable;
    }

    /**
     * @return int
     */
    public function getDegree(): int
    {
        return $this->degree;
    }

    public function __invoke($x)
    {
        // Start with the zero polynomial
        $polynomial = static function () {
            return "0";
        };

        // Iterate over each coefficient to create a callback function for each term
        for ($i = 0; $i <= $this->degree; $i++) {
            // Create a callback function for the current term
            $term = function ($x) use ($i) {
                return bcmul($this->coefficients[$i], bcpow((string)$x , (string)($this->degree - $i)));
            };

            // Add the new term to the polynomial
            $polynomial = self::arithmeticAdd($polynomial, $term);
        }

        return $polynomial($x);
    }

    public function add($polynomial): self
    {
        $polynomial = $this->checkNumericOrPolynomial($polynomial);

        $coefficientsA = $this->coefficients;
        $coefficientsB = $polynomial->coefficients;

        // If degrees are unequal, make coefficient array sizes equal so we can do component-wise addition
        $degreeDifference = $this->getDegree() - $polynomial->getDegree();
        if ($degreeDifference !== 0) {
            $zeroArray = \array_fill(0, \abs($degreeDifference), 0);
            if ($degreeDifference < 0) {
                $coefficientsA = \array_merge($zeroArray, $coefficientsA);
            } else {
                $coefficientsB = \array_merge($zeroArray, $coefficientsB);
            }
        }

        $coefficientsSum = self::multiAdd($coefficientsA, $coefficientsB);

        return new Polynomial($coefficientsSum);
    }

    public static function arithmeticAdd(callable ...$args): callable
    {
        $sum = function ($x, ...$args) {
            $function = "0";
            foreach ($args as $arg) {
                $function = bcadd($function, $arg($x));
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
        $sums             = \array_fill(0, $length_of_arrays, 0);

        for ($i = 0; $i < $length_of_arrays; $i++) {
            for ($j = 0; $j < $number_of_arrays; $j++) {
                $sums[$i] = bcadd((string)$sums[$i], (string)$arrays[$j][$i]);
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

    public function multiply($polynomial): Polynomial
    {
        $polynomial = $this->checkNumericOrPolynomial($polynomial);
        // Calculate the degree of the product of the polynomials
        $productDegree = $this->degree + $polynomial->degree;

        // Reverse the coefficients arrays so you can multiply component-wise
        $coefficientsA = \array_reverse($this->coefficients);
        $coefficientsB = \array_reverse($polynomial->coefficients);

        // Start with an array of coefficients that all equal 0
        $productCoefficients = \array_fill(0, $productDegree + 1, 0);

        // Iterate through the product of terms component-wise
        for ($i = 0; $i < $this->degree + 1; $i++) {
            for ($j = 0; $j < $polynomial->degree + 1; $j++) {
                // Calculate the degree of the current product
                $degree = $productDegree - ($i + $j);

                // Calculate the product of the coefficients
                $product = bcmul((string)$coefficientsA[$i], (string)$coefficientsB[$j]);

                // Add the product to the existing coefficient of the current degree
                $productCoefficients[$degree] = bcadd((string)$productCoefficients[$degree], $product);
            }
        }

        return new self($productCoefficients);
    }

    private function checkNumericOrPolynomial($input): self
    {
        if ($input instanceof Polynomial) {
            return $input;
        }

        if (\is_numeric($input)) {
            return new Polynomial([$input]);
        }

        throw new \Exception('Input must be a Polynomial or a number');
    }
}
