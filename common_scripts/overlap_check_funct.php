<?php
//==============================================================================
/*
These functions are used to return a true/false value indicating whether ranges
$a - $b and $c - $d overlap.

1. The functions with name suffix '_inclusive' count the ranges as overlapping
   if they share a boundary value.
2. The functions with name suffix '_exclusive' count the ranges as non-
   overlapping if they share a boundary value.
3. The functions with name prefix 'safe_' allow the boundary values of each
   range to appear in either ascending or descending order.

The first function (ranges_overlap_inclusive) will return 'true' for all the
four possible ways in which the ranges can overlap, namely:
  $a <= $c <= $b <= $d
  $a <= $c <= $d <= $b
  $c <= $a <= $d <= $b
  $c <= $a <= $b <= $d

The logic for each of the other three functions works on a similar principle.
*/
//==============================================================================

function ranges_overlap_inclusive($a, $b, $c, $d): bool
{
    return $a <= $d && $c <= $b;
}

function ranges_overlap_exclusive($a, $b, $c, $d): bool
{
    return $a < $d && $c < $b;
}

function safe_ranges_overlap_inclusive($a, $b, $c, $d): bool
{
    return min($a,$b) <= max($c,$d) && min($c,$d) <= max($a,$b);
}

function safe_ranges_overlap_exclusive($a, $b, $c, $d): bool
{
    return min($a,$b) < max($c,$d) && min($c,$d) < max($a,$b);
}

//==============================================================================
