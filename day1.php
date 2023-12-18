<?php

/**
 * Binet's Formula is used https://artofproblemsolving.com/wiki/index.php/Binet%27s_Formula
 * @param int $n
 * @return int
 */
function fibonacci(int $n): int
{
    $phi = (1 + sqrt(5)) / 2;

    return round((pow($phi, $n) - pow(-$phi, -$n)) / sqrt(5));
}

/**
 * @param $n
 * @return int|string
 */
function start($n): int|string
{
    try {
        if (!is_int($n) || $n < 0) {
            throw new Exception("Invalid input. Must be a strict integer and greater than or equal to 0.");
        }

        return fibonacci($n);
    } catch (Throwable $e) {
        return $e->getMessage();
    }
}

echo start(0.1);