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

use DigiLive\GitChangelog\Renderers\MarkDown;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests of class MarkDown
 */
class MarkDownTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @var \DigiLive\GitChangelog\Renderers\MarkDown The object that will be tested against.
     */
    private $changelog;

    /**
     * Set up each test case.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->changelog = new MarkDown();
    }

    /**
     * Test if a changelog is build with the commit titles in ascending order.
     *
     * @return void
     * @throws \DigiLive\GitChangelog\GitChangelogException When setting the GitChangelog options fails.
     * @throws \ReflectionException When setting a private property fails.
     */
    public function testBuildAscendingTitleOrder(): void
    {
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
                // Dummy tag and commits to be formatted.
                ['A' => ['date' => 'B', 'titles' => ['#1'], 'hashes' => [['0123456']]]],
            ];
        $expectedValues =
            [
                // No tags
                "# Changelog\n\nNo changes.\n",
                // Head Revision included.
                "# Changelog\n\n## Upcoming changes (Undetermined)\n\n* C (E)\n* D (F)\n\n",
                // Dummy tag, no commits.
                "# Changelog\n\n## A (B)\n\n* No changes.\n\n",
                // Dummy tag and commits.
                "# Changelog\n\n## A (B)\n\n* C (E, F)\n* D (G)\n\n",
                // Dummy tag and commits to be formatted, but they're not.
                "# Changelog\n\n## A (B)\n\n* #1 (0123456)\n\n",
                // Dummy tag and commits to be formatted, and they are.
                "# Changelog\n\n## A (B)\n\n* [#1][1] ([0123456][0])\n\n[0]:<c>0123456</c>\n[1]:<i>1</i>\n",
                // Dummy tag and commits to be formatted, but hashes are disabled.
                "# Changelog\n\n## A (B)\n\n* [#1][0]\n\n[0]:<i>1</i>\n",
            ];

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
    public function testBuildDescendingTitleOrder(): void
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
                // No tags
                "# Changelog\n\nNo changes.\n",
                // Head Revision included.
                "# Changelog\n\n## Upcoming changes (Undetermined)\n\n* D (F)\n* C (E)\n\n",
                // Dummy tag, no commits.
                "# Changelog\n\n## A (B)\n\n* No changes.\n\n",
                // Dummy tag and commits.
                "# Changelog\n\n## A (B)\n\n* D (G)\n* C (E, F)\n\n",
            ];

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

        $expectedValue = "# Changelog\n\n## A (B)\n\n* C (E, F)\n* D (G)\n\n## H (I)\n\n* J (L, M)\n* K (N)\n\n";

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

        $expectedValue = "# Changelog\n\n## H (I)\n\n* J (L, M)\n* K (N)\n\n## A (B)\n\n* C (E, F)\n* D (G)\n\n";

        $this->setPrivateProperty($this->changelog, 'commitData', $testValue);
        $this->changelog->build();
        $this->assertEquals($expectedValue, $this->changelog->get(false));
    }
}
