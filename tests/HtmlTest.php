<?php
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

namespace DigiLive\GitChangelog\Tests;

use DigiLive\GitChangelog\Renderers\Html;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

/**
 * Class HtmlTest
 *
 * PHPUnit tests for class Html
 *
 * @package DigiLive\GitChangelog\Tests
 */
class HtmlTest extends TestCase
{
    public function testBuildAscendingOrders()
    {
        $changeLog = new Html();
        $changeLog->setOptions('tagOrderDesc', false);
        $testValues     =
            [
                // No tags.
                [],
                // Head Revision included.
                ['' => ['date' => 'B', 'titles' => ['#1', 'D'], 'hashes' => [['E'], ['F']]]],
                // Dummy tag, no commits.
                ['A' => ['date' => 'B', 'titles' => [], 'hashes' => []]],
                // Dummy tag and commits.
                ['A' => ['date' => 'B', 'titles' => ['C', 'D'], 'hashes' => [['E', 'F'], ['G']]]],
                // Dummy tag and commits to be formatted.
                ['A' => ['date' => 'B', 'titles' => ['#1'], 'hashes' => [['0123456']]]],
            ];
        $expectedValues =
            [
                //No tags
                '<h1>Changelog</h1><p>No changes.</p>',
                // Head Revision included.
                '<h1>Changelog</h1><h2>Upcoming changes (Undetermined)</h2><ul><li>#1 (E)</li><li>D (F)</li></ul>',
                // Dummy tag, no commits.
                '<h1>Changelog</h1><h2>A (B)</h2><ul><li>No changes.</li></ul>',
                // Dummy tag and commits.
                '<h1>Changelog</h1><h2>A (B)</h2><ul><li>C (E, F)</li><li>D (G)</li></ul>',
                // Dummy tag and commits to be formatted, but they're not.
                '<h1>Changelog</h1><h2>A (B)</h2><ul><li>#1 (0123456)</li></ul>',
                // Dummy tag and commits to be formatted, and they are.
                '<h1>Changelog</h1><h2>A (B)</h2><ul><li><a href="<Issue>1</Issue>">#1</a> (<a href="<Commit>0123456</Commit>">0123456</a>)</li></ul>',
                // Dummy tag and commits to be formatted, but hashes are disabled.
                '<h1>Changelog</h1><h2>A (B)</h2><ul><li><a href="<Issue>1</Issue>">#1</a> </li></ul>',
            ];

        next($testValues);
        foreach ($testValues as $key => $value) {
            $this->setPrivateProperty($changeLog, 'commitData', $value);
            $changeLog->build();
            $this->assertEquals($expectedValues[$key], $changeLog->get());
        }

        // Test formatting of issues and hashes.
        $changeLog->issueUrl  = '<Issue>{issue}</Issue>';
        $changeLog->commitUrl = '<Commit>{hash}</Commit>';
        $changeLog->build();
        $this->assertEquals($expectedValues[5], $changeLog->get());
        // Disable hashes
        $changeLog->setOptions('addHashes', false);
        $changeLog->build();
        $this->assertEquals($expectedValues[6], $changeLog->get());
    }

    /**
     * Sets a private or protected property on a given object via reflection
     *
     * @param   object  $object    - Instance in which the private or protected value is being modified.
     * @param   string  $property  - Property of instance which is being modified.
     * @param           $value     - New value of the property which is being modified.
     *
     * @return void
     * @throws ReflectionException If no property exists by that name.
     */
    private function setPrivateProperty(object $object, string $property, $value): void
    {
        $reflection         = new ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);

        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }

    public function testBuildDescendingOrders()
    {
        $changeLog = new Html();
        $changeLog->setOptions('titleOrder', 'DESC');
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
                '<h1>Changelog</h1><p>No changes.</p>',
                // Head Revision included.
                '<h1>Changelog</h1><h2>Upcoming changes (Undetermined)</h2><ul><li>D (F)</li><li>C (E)</li></ul>',
                // Dummy tag, no commits.
                '<h1>Changelog</h1><h2>A (B)</h2><ul><li>No changes.</li></ul>',
                // Dummy tag and commits.
                '<h1>Changelog</h1><h2>A (B)</h2><ul><li>D (G)</li><li>C (E, F)</li></ul>',
            ];

        // Test formatting of issues and hashes.
        foreach ($testValues as $key => $value) {
            $this->setPrivateProperty($changeLog, 'commitData', $value);
            $changeLog->build();
            $this->assertEquals($expectedValues[$key], $changeLog->get());
        }
    }
}
