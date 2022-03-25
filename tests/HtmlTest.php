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

namespace DigiLive\GitChangelog\Tests;

use DigiLive\GitChangelog\Renderers\Html;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for class Html
 */
class HtmlTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @var \DigiLive\GitChangelog\Renderers\Html The object that will be tested against.
     */
    private $changelog;

    /**
     * Set up each test case.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->changelog = new Html();
    }

    /**
     * Test if a changelog is build with the commit titles in ascending order.
     *
     * @return void
     *
     * @throws \DigiLive\GitChangelog\GitChangelogException When setting the GitChangelog options fails.
     * @throws \ReflectionException When setting a private property fails.
     */
    public function testBuildAscendingTitleOrder()
    {
        $this->changelog->setOptions('tagOrder', 'asc');
        $testValues     =
            [
                // No tags.
                [],
                // Head Revision included.
                ['' => ['date' => 'B', 'titles' => ['D', 'C'], 'hashes' => [['F'], ['E']]]],
                // Dummy tag, no commits.
                ['A' => ['date' => 'B', 'titles' => [], 'hashes' => []]],
                // Dummy tag and commits.
                ['A' => ['date' => 'B', 'titles' => ['D', 'C'], 'hashes' => [['G'], ['E', 'F']]]],
                // Dummy tag and commits to be formatted.
                ['A' => ['date' => 'B', 'titles' => ['#1'], 'hashes' => [['0123456']]]],
            ];
        $expectedValues =
            [
                //No tags
                "<h1>Changelog</h1>\n<p>No changes.</p>\n",
                // Head Revision included.
                "<h1>Changelog</h1>\n\n<h2>Upcoming changes (Undetermined)</h2>\n" .
                "<ul>\n    <li>C (E)</li>\n    <li>D (F)</li>\n</ul>\n",
                // Dummy tag, no commits.
                "<h1>Changelog</h1>\n\n<h2>A (B)</h2>\n<ul>\n    <li>No changes.</li>\n</ul>\n",
                // Dummy tag and commits.
                "<h1>Changelog</h1>\n\n<h2>A (B)</h2>\n<ul>\n    <li>C (E, F)</li>\n    <li>D (G)</li>\n</ul>\n",
                // Dummy tag and commits to be formatted, but they"re not.
                "<h1>Changelog</h1>\n\n<h2>A (B)</h2>\n<ul>\n    <li>#1 (0123456)</li>\n</ul>\n",
                // Dummy tag and commits to be formatted, and they are.
                "<h1>Changelog</h1>\n\n<h2>A (B)</h2>\n<ul>\n    <li><a href=\"<i>1</i>\">#1</a>" .
                " (<a href=\"<c>0123456</c>\">0123456</a>)</li>\n</ul>\n",
                // Dummy tag and commits to be formatted, but hashes are disabled.
                "<h1>Changelog</h1>\n\n<h2>A (B)</h2>\n<ul>\n    <li><a href=\"<i>1</i>\">#1</a> </li>\n</ul>\n",
            ];

        next($testValues);
        foreach ($testValues as $key => $value) {
            $this->setPrivateProperty($this->changelog, 'commitData', $value);
            $this->changelog->build();
            $this->assertEquals($expectedValues[$key], $this->changelog->get(false));
        }

        // Test formatting of issues and hashes.
        $this->changelog->setUrl('issue', '<i>{issue}</i>');
        $this->changelog->setUrl('commit', '<c>{commit}</c>');
        $this->changelog->setPattern('issue', '#(\d+)');

        $this->changelog->build();
        $this->assertEquals($expectedValues[5], $this->changelog->get(false));
        // Disable hashes
        $this->changelog->setOptions('addHashes', false);
        $this->changelog->build();
        $this->assertEquals($expectedValues[6], $this->changelog->get(false));
    }

    /**
     * Test if a changelog is build with the commit titles in descending order.
     *
     * @return void
     * @throws \DigiLive\GitChangelog\GitChangelogException When setting the GitChangelog options fails.
     * @throws \ReflectionException When setting a private property fails.
     */
    public function testBuildDescendingTitleOrder()
    {
        $this->changelog->setOptions('titleOrder', 'desc');

        $testValues     =
            [
                // No tags.
                [],
                // Head Revision included.
                ['' => ['date' => 'B', 'titles' => ['C', 'D'], 'hashes' => [['E'], ['F']]]],
                // Dummy tag, no commits.
                ['A' => ['date' => 'B', 'titles' => [], 'hashes' => []]],
                // Dummy tag and commits.
                ['A' => ['date' => 'B', 'titles' => ['C', 'D'], 'hashes' => [['E', 'F'], ['G']]]],
            ];
        $expectedValues =
            [
                //No tags
                "<h1>Changelog</h1>\n<p>No changes.</p>\n",
                // Head Revision included.
                "<h1>Changelog</h1>\n\n<h2>Upcoming changes (Undetermined)</h2>\n" .
                "<ul>\n    <li>D (F)</li>\n    <li>C (E)</li>\n</ul>\n",
                // Dummy tag, no commits.
                "<h1>Changelog</h1>\n\n<h2>A (B)</h2>\n<ul>\n    <li>No changes.</li>\n</ul>\n",
                // Dummy tag and commits.
                "<h1>Changelog</h1>\n\n<h2>A (B)</h2>\n<ul>\n    <li>D (G)</li>\n    <li>C (E, F)</li>\n</ul>\n",
            ];

        // Test formatting of issues and hashes.
        foreach ($testValues as $key => $value) {
            $this->setPrivateProperty($this->changelog, 'commitData', $value);
            $this->changelog->build();
            $this->assertEquals($expectedValues[$key], $this->changelog->get(false));
        }
    }

    /**
     * Test if a changelog is build with the tags in descending order.
     *
     * @return void
     * @throws \DigiLive\GitChangelog\GitChangelogException When setting the GitChangelog options fails.
     * @throws \ReflectionException When setting a private property fails.
     */
    public function testBuildAscendingTagOrder(): void
    {
        $testValue = [
            // Tags are fetched from the repository in descending order.
            'H' => ['date' => 'I', 'titles' => ['J', 'K'], 'hashes' => [['L', 'M'], ['N']]],
            'A' => ['date' => 'B', 'titles' => ['C', 'D'], 'hashes' => [['E', 'F'], ['G']]],
        ];

        $expectedValue =
            "<h1>Changelog</h1>\n\n<h2>A (B)</h2>\n<ul>\n    <li>C (E, F)</li>\n    <li>D (G)</li>\n</ul>\n" .
            "\n<h2>H (I)</h2>\n<ul>\n    <li>J (L, M)</li>\n    <li>K (N)</li>\n</ul>\n";

        $this->changelog->setOptions('tagOrder', 'asc');
        $this->setPrivateProperty($this->changelog, 'commitData', $testValue);
        $this->changelog->build();
        $this->assertEquals($expectedValue, $this->changelog->get(false));
    }

    /**
     * Test if a changelog is build with the tags in descending order.
     *
     * @return void
     * @throws \DigiLive\GitChangelog\GitChangelogException When setting the GitChangelog options fails.
     * @throws \ReflectionException When setting a private property fails.
     */
    public function testBuildDescendingTagOrder(): void
    {
        $testValue = [
            // Tags are fetched from the repository in descending order.
            'H' => ['date' => 'I', 'titles' => ['J', 'K'], 'hashes' => [['L', 'M'], ['N']]],
            'A' => ['date' => 'B', 'titles' => ['C', 'D'], 'hashes' => [['E', 'F'], ['G']]],
        ];

        $expectedValue =
            "<h1>Changelog</h1>\n\n<h2>H (I)</h2>\n<ul>\n    <li>J (L, M)</li>\n    <li>K (N)</li>\n</ul>\n" .
            "\n<h2>A (B)</h2>\n<ul>\n    <li>C (E, F)</li>\n    <li>D (G)</li>\n</ul>\n";

        $this->setPrivateProperty($this->changelog, 'commitData', $testValue);
        $this->changelog->build();
        $this->assertEquals($expectedValue, $this->changelog->get(false));
    }
}
