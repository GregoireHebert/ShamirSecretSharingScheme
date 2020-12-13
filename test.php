<?php

declare(strict_types=1);

require 'vendor/autoload.php';

bcscale(100);

$s = "42";
$k = 3;

$polynomial = new Polynomial(["5", "3", $s]);

$points = [
    ["18", $polynomial(18)],
    ["27", $polynomial(27)],
    ["31", $polynomial(31)]
];

$lp = LagrangePolynomial::interpolate($points);
$rs = $lp(0);
var_dump($rs, $s);
var_dump($rs == $s);die;


