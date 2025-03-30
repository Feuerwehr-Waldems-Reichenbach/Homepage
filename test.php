<?php
// Test how PHP handles 0 values
$value1 = 0;
$value2 = 0.00;
$value3 = "0";
$value4 = "0.00";

echo "Value: $value1, Formatted: " . number_format($value1, 2, ',', '.') . "€\n";
echo "Value: $value2, Formatted: " . number_format($value2, 2, ',', '.') . "€\n";
echo "Value: $value3, Formatted: " . number_format($value3, 2, ',', '.') . "€\n";
echo "Value: $value4, Formatted: " . number_format($value4, 2, ',', '.') . "€\n";

// Test logical operations
$test1 = $value1 || 100;
$test2 = ($value1 !== null && $value1 !== "") ? $value1 : 100;

echo "Logical OR: $value1 || 100 = $test1\n";
echo "Ternary: ($value1 !== null && $value1 !== '') ? $value1 : 100 = $test2\n"; 