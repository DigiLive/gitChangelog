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

namespace DigiLive\GitChangelog\Tests\GitChangelog\Renderers;

use DigiLive\GitChangelog\GitChangelog\GitChangelogException;
use DigiLive\GitChangelog\GitChangelog\Renderers\RendererTrait;
use DigiLive\GitChangelog\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests of trait RendererTraitTest
 */
class RendererTraitTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @var object Dummy class wrapped around the trait that is tested against.
     */
    private $dummyClass;

    /**
     * Set up each test case.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->dummyClass = new class {
            use RendererTrait;
        };
    }

    /**
     * Test if the trait's properties have the correct default values.
     *
     * @return void
     * @throws \ReflectionException When getting a private property fails.
     */
    public function testDefaults(): void
    {
        $expectedUrls = [
            'commit'       => null,
            'issue'        => null,
            'mergeRequest' => null,
        ];

        $expectedFormats = [
            'tag'   => '## {tag} ({date})',
            'title' => '* {title} {hashes}',
        ];

        $expectedPatterns = [
            // /a^/ matches nothing and can be any character.
            'issue'        => '/a^/',
            'mergeRequest' => '/a^/',
        ];

        $this->assertSame(
            $expectedUrls,
            $this->getPrivateProperty($this->dummyClass, 'urls')
        );
        $this->assertSame(
            $expectedFormats,
            $this->getPrivateProperty($this->dummyClass, 'formats')
        );
        $this->assertSame(
            $expectedPatterns,
            $this->getPrivateProperty($this->dummyClass, 'patterns')
        );
    }

    /**
     * Test if a valid pattern can be set.
     *
     * @return void
     * @throws \ReflectionException When getting a private property fails.
     */
    public function testSetPatternValid(): void
    {
        $type    = 'issue';
        $pattern = '()';

        $this->dummyClass->setPattern($type, $pattern);
        $this->assertSame("/$pattern(?![^\[]*\])/", $this->getPrivateProperty($this->dummyClass, 'patterns')[$type]);
    }

    /**
     * Test if an invalid pattern type can't be set.
     *
     * @return void
     */
    public function testSetPatternInvalidType(): void
    {
        $type    = 'invalidType';
        $pattern = 'someValue';

        $this->expectException(\OutOfBoundsException::class);
        $this->dummyClass->setPattern($type, $pattern);
    }

    /**
     * Test if an invalid pattern can't be set.
     *
     * @return void
     */
    public function testSetPatternInvalidValue(): void
    {
        $type    = 'issue';
        $pattern = '';

        $this->expectException(GitChangelogException::class);
        $this->dummyClass->setPattern($type, $pattern);
    }

    /**
     * Test if a pattern can be unset.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testUnsetPattern(): void
    {
        $type    = 'issue';
        $pattern = null;

        $this->dummyClass->setPattern($type, $pattern);
        $this->assertSame('/a^/', $this->getPrivateProperty($this->dummyClass, 'patterns')[$type]);
    }

    /**
     * Test if a valid url can be set.
     *
     * Note: The syntax of the url isn't validated.
     *
     * @return void
     * @throws \ReflectionException When getting a private property fails.
     */
    public function testSetUrlValid(): void
    {
        $type = 'issue';
        $url  = 'someValue';

        $this->dummyClass->setUrl($type, $url);
        $this->assertSame($url, $this->getPrivateProperty($this->dummyClass, 'urls')[$type]);
    }

    /**
     * Test if an invalid url type can't be set.
     *
     * @return void
     */
    public function testSetUrlInvalidType(): void
    {
        $type = 'invalidType';
        $url  = 'someValue';

        $this->expectException(\OutOfBoundsException::class);
        $this->dummyClass->setUrl($type, $url);
    }

    /**
     * Test if a valid format can be set.
     *
     * @return void
     * @throws \ReflectionException When getting a private property fails.
     */
    public function testSetFormatValid(): void
    {
        $type   = 'tag';
        $format = 'someValue';

        $this->dummyClass->setFormat($type, $format);
        $this->assertSame($format, $this->getPrivateProperty($this->dummyClass, 'formats')[$type]);
    }

    /**
     * Test if an invalid format type can't be set.
     *
     * @return void
     */
    public function testSetFormatInvalidType(): void
    {
        $type   = 'invalidType';
        $format = 'someValue';

        $this->expectException(\OutOfBoundsException::class);
        $this->dummyClass->setFormat($type, $format);
    }
}
