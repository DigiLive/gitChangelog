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

namespace DigiLive\GitChangelog\Tests;

use DigiLive\GitChangelog\Utilities\ArrayUtils;
use PHPUnit\Framework\TestCase;

/**
 * Class UtilitiesTest
 *
 * PHPUnit tests of class Utilities.
 */
class UtilitiesTest extends TestCase
{
    public function testArrayStrPos0()
    {
        $this->assertTrue(ArrayUtils::arrayStrPos0('Hello World', 'Hello'));
        $this->assertTrue(ArrayUtils::arrayStrPos0('Hello World', 'hello'));
        $this->assertTrue(ArrayUtils::arrayStrPos0('Hello World', ['World', 'Hello']));

        $this->assertFalse(ArrayUtils::arrayStrPos0('Hello World', 'World'));
        $this->assertFalse(ArrayUtils::arrayStrPos0('Hello World', 'Hello', 1));
    }

    public function testNatSort()
    {
        $array = [0 => 'A', 1 => 'Z', 2 => 'B', 3 => '1'];
        ArrayUtils::natSort($array, 'ASC');
        $this->assertSame([3 => '1', 0 => 'A', 2 => 'B', 1 => 'Z'], $array);
        ArrayUtils::natSort($array, 'DESC');
        $this->assertSame([1 => 'Z', 2 => 'B', 0 => 'A', 3 => '1'], $array);
        ArrayUtils::natSort($array, 'AnyOther');
        $this->assertSame($array, $array);
    }
}
