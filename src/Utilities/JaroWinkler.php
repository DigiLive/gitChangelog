<?php

/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2022, Ferry Cools (DigiLive)
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

declare(strict_types=1);

namespace DigiLive\GitChangelog\Utilities;

/**
 * Get the similarity between two strings using the Jaro-Winkler algorithm.
 *
 * In computer science and statistics, the Jaro–Winkler distance is a string metric measuring an edit distance between
 * two sequences. It is a variant proposed in 1990 by William E. Winkler of the Jaro distance metric (1989, Matthew A.
 * Jaro).
 *
 * The Jaro–Winkler distance uses a prefix scale which gives more favourable ratings to strings that match from the
 * beginning for a set prefix length.
 *
 * The lower the Jaro–Winkler distance for two strings is, the more similar the strings are. The score is normalized
 * such that 1 means an exact match and 0 means there is no similarity. The original paper actually defined the metric
 * in terms of similarity, so the distance is defined as the inversion of that value (distance = 1 − similarity).
 */
class JaroWinkler
{
    /**
     * Calculate the Jaro-Winkler similarity between two strings using the Jaro-Winkler algorithm.
     *
     * The scale parameter is a constant scaling factor for how much the score is adjusted upwards for having common
     * prefixes. It cannot not exceed 0.25 (i.e. 1/4, with 4 being the maximum length of the prefix being considered),
     * otherwise the similarity could become larger than 1.
     * The standard value for this constant in Winkler's work is 0.1.
     *
     * @param   string  $string1  The first string to compare.
     * @param   string  $string2  The second string to compare.
     * @param   float   $scale    The scale factor for the prefix.
     *
     * @return float The similarity factor between the two strings.
     */
    public function compare(string $string1, string $string2, float $scale = 0.1): float
    {
        $jaroSimilarity = $this->jaro($string1, $string2);
        $prefixLength   = $this->getPrefixLength($string1, $string2);

        return $jaroSimilarity + $prefixLength * min(0.25, $scale) * (1.0 - $jaroSimilarity);
    }

    /**
     * Calculate the Jaro similarity between two strings.
     *
     * The Jaro distance is a measure of edit distance between two strings; its inverse, called the Jaro similarity, is
     * a measure of two strings' similarity: the higher the value, the more similar the strings are. The score is
     * normalized such that 0 equates to no similarities and 1 is an exact match.
     *
     * @param   string  $string1  The first string to compare.
     * @param   string  $string2  The second string to compare.
     *
     * @return float The Jaro distance between the two strings.
     */
    private function jaro(string $string1, string $string2): float
    {
        $string1Length = mb_strlen($string1);
        $string2Length = mb_strlen($string2);

        if (!$string1Length) {
            // If both strings are empty, return 1, if only one of the strings is empty, return 0.
            return !$string2Length ? 1.0 : 0.0;
        }

        // Maximum distance between two characters to be considered as common.
        $distance = (int) floor(min($string1Length, $string2Length) / 2.0);

        // Get common characters.
        $commons1       = $this->getCommonCharacters($string1, $string2, $distance);
        $commons2       = $this->getCommonCharacters($string2, $string1, $distance);
        $commons1Length = mb_strlen($commons1);
        $commons2Length = mb_strlen($commons2);

        if (!$commons1Length || !$commons2Length) {
            return 0;
        }

        // Define the number of transpositions.
        // Each common character of string 1 is compared with common characters of string 2.
        // Each difference is half a transposition; that is, the number of transpositions is half the number of
        // characters which are common to the two strings but occupy different positions in each one.
        $transpositions = 0;
        $upperBound     = min($commons1Length, $commons2Length);

        for ($i = 0; $i < $upperBound; $i++) {
            if ($commons1[$i] != $commons2[$i]) {
                $transpositions++;
            }
        }
        $transpositions /= 2.0;

        // Return the Jaro similarity.
        return ($commons1Length / $string1Length +
                $commons2Length / $string2Length +
                ($commons1Length - $transpositions) / $commons1Length) / 3.0;
    }

    /**
     * Get the common characters of two strings.
     *
     * Two characters from string 1 and string 2 respectively, are considered common only if they are the same and
     * not farther apart than defined by the allowed distance.
     *
     * @param   string  $string1          The first string to compare.
     * @param   string  $string2          The second string to compare.
     * @param   int     $allowedDistance  The allowed distance between two characters to consider them as common.
     *
     * @return string The common characters between the two strings.
     */
    private function getCommonCharacters(string $string1, string $string2, int $allowedDistance): string
    {
        $string1Length = mb_strlen($string1);
        $string2Length = mb_strlen($string2);
        $string2Temp   = $string2;

        $commonCharacters = '';
        for ($i = 0; $i < $string1Length; $i++) {
            $noMatch = true;
            // Compare if the current character of string 1, matches a character of string 2 within a given allowed
            // distance. If it does, add it to the common characters.
            $start = max(0, $i - $allowedDistance);
            $end   = min($i + $allowedDistance + 1, $string2Length);
            for ($j = $start; $noMatch && $j < $end; $j++) {
                if ($string2Temp[$j] == $string1[$i]) {
                    $noMatch          = false;
                    $string2Temp[$j]  = "\x00";
                    $commonCharacters .= $string1[$i];
                }
            }
        }

        return $commonCharacters;
    }

    /**
     * Get the length of the matching prefix between two strings.
     *
     * The matching prefix is the longest string that match between the strings from the start of them, up to a maximum
     * of 4 characters.
     *
     * @param   string  $string1  The first string to compare.
     * @param   string  $string2  The second string to compare.
     *
     * @return int The length of the matching prefix between the two strings.
     */
    private function getPrefixLength(string $string1, string $string2): int
    {
        // Limit the max prefix length to the length of the shortest string or to 4.
        $maxLength = min([4, mb_strlen($string1), mb_strlen($string2)]);

        for ($i = 0; $i < $maxLength; $i++) {
            if ($string1[$i] != $string2[$i]) {
                // Return the index of the first different character.
                return $i;
            }
        }

        // The first n characters are the same.
        return $maxLength;
    }
}
