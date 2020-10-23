<?php

declare(strict_types=1);

/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2020, Ferry Cools (DigiLive)
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

namespace DigiLive\GitChangeLog;

class Utilities
{
    /**
     * Sort an array using a “natural order” algorithm.
     *
     * The sorting order can be defined as  'ASC' or 'DESC'.
     * For any other value, the array will remain unchanged.
     *
     * @param   array   $array  The input array.
     * @param   string  $order  Sorting order.
     */
    public static function natSort(array &$array, string $order): void
    {
        switch ($order) {
            case 'ASC':
                natsort($array);
                break;
            case 'DESC':
                natsort($array);
                $array = array_reverse($array, true);
        }
    }

    /**
     * Check if a string starts with a case-insensitive substring.
     *
     * Parameter $needles can be of type string or an array of strings.
     * Comparison is
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
            if (stripos($haystack, $needle, $offset) === 0) {
                return true;
            }
        }

        return false;
    }
}
