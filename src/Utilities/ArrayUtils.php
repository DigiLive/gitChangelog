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
 * Class Utilities
 *
 * This class contains supportive methods to help keeping the complexity of calling code low.
 */
class ArrayUtils
{
    /**
     * Sort an array using a “natural order” algorithm.
     *
     * The sorting order can be defined as  'asc' or 'desc' (case insensitive).
     * For any other value, the array will remain unchanged.
     *
     * @param   array   $array  The input array.
     * @param   string  $order  Sorting order.
     */
    public static function natSort(array &$array, string $order): void
    {
        switch (strtolower($order)) {
            case 'asc':
                natsort($array);
                break;
            case 'desc':
                natsort($array);
                $array = array_reverse($array, true);
        }
    }

    /**
     * Check if a string starts with a case-insensitive substring.
     *
     * Parameter $needles can be of type string or an array of strings.
     *
     * @param   string        $haystack  The string to search in.
     * @param   string|array  $needles   If a needle is not a string, it is converted to an integer and applied as the
     *                                   ordinal value of a character.
     * @param   int           $offset    [optional] If specified, search will start this number of characters counted
     *                                   from the beginning of the string. The offset cannot be negative.
     *
     * @return bool True when any of the needles exists.
     */
    public static function arrayStrPos0(string $haystack, $needles, int $offset = 0): bool
    {
        if (!is_array($needles)) {
            $needles = [$needles];
        }

        foreach ($needles as $needle) {
            $haystack = strtolower($haystack);
            if (0 === stripos($haystack, $needle, $offset)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Searches the array for a given value and returns the corresponding key if successful.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param   mixed  $value   The value to search for.
     *                          If needle is a string, the comparison is done in a case-sensitive manner.
     * @param   array  $array   The array.
     * @param   bool   $strict  [optional] If the third parameter strict is set to true then the array_search
     *                          function will also check the types of the needle in the haystack.
     *
     * @return int The key for needle if it is found in the array.
     *             If needle is found in haystack more than once, the first matching key is returned.
     *             To return the keys for all matching values, use array_keys with the optional search_value parameter
     *             instead.
     * @throws \OutOfBoundsException If the value is not found in array.
     */
    public static function arraySearch($value, array $array, bool $strict = false): int
    {
        $key = array_search($value, $array, $strict);
        if (false === $key) {
            throw new \OutOfBoundsException("Value $value does not exist in array!");
        }

        return $key;
    }

    /**
     * Searches the array for a given value and returns the corresponding keys where the value is similar to needle.
     *
     * Similarity is calculated by the Jaro-Winkler distance.
     *
     * @param   array   $array      An array containing keys to return.
     * @param   string  $needle     The value to search for.
     * @param   float   $threshold  The threshold for similarity where 1.0 is identical and 0.0 is completely different.
     *
     * @return array The keys for needle if it is found in the array.
     */
    public static function findSimilarKeys(array $array, string $needle, float $threshold): array
    {
        if (1 == $threshold) {
            return array_keys($array, $needle);
        }

        $similarity = new JaroWinkler();

        return array_keys(
            array_filter(
                $array,
                static function ($value) use ($similarity, $needle, $threshold) {
                    return $similarity->compare($needle, (string) $value) >= $threshold;
                }
            )
        );
    }
}
