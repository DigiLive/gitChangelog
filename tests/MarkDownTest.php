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

use DigiLive\GitChangelog\Renderers\MarkDown;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

/**
 * Class MarkDownTest
 *
 * PHPUnit tests of class MarkDown
 *
 * @package DigiLive\GitChangelog\Tests
 */
class MarkDownTest extends TestCase
{
    public function testBuildAscendingOrders()
    {
        $changeLog = new MarkDown();
        $changeLog->setOptions('tagOrderDesc', false);
        $testValues     =
            [
                // No tags.
                [],
                // Head Revision included.
                ['' => ['date' => 'B', 'subjects' => ['C', 'D'], 'hashes' => [['E'], ['F']]]],
                // Dummy tag, no commits.
                ['A' => ['date' => 'B', 'subjects' => [], 'hashes' => []]],
                // Dummy tag and commits.
                ['A' => ['date' => 'B', 'subjects' => ['C', 'D'], 'hashes' => [['E', 'F'], ['G']]]],
                // Dummy tag and commits to be formatted.
                ['A' => ['date' => 'B', 'subjects' => ['#1'], 'hashes' => [['0123456']]]],
            ];
        $expectedValues =
            [
                //No tags
                "# Changelog\n\nNo changes.\n",
                // Head Revision included.
                "# Changelog\n\n## Upcoming changes (Undetermined)\n\n* C (E)\n* D (F)\n",
                // Dummy tag, no commits.
                "# Changelog\n\n## A (B)\n\n* No changes.\n",
                // Dummy tag and commits.
                "# Changelog\n\n## A (B)\n\n* C (E, F)\n* D (G)\n",
                // Dummy tag and commits to be formatted, but they're not.
                "# Changelog\n\n## A (B)\n\n* #1 (0123456)\n",
                // Dummy tag and commits to be formatted, and they are.
                "# Changelog\n\n## A (B)\n\n* [#1](<Issue>1</Issue>) ([0123456](<Commit>0123456</Commit>))\n",
                // Dummy tag and commits to be formatted, but hashes are disabled.
                "# Changelog\n\n## A (B)\n\n* [#1](<Issue>1</Issue>)\n",
            ];

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
        $changeLog = new MarkDown();
        $changeLog->setOptions('subjectOrder', 'DESC');
        $testValues     =
            [
                // No tags.
                [],
                // Head Revision included.
                ['' => ['date' => 'B', 'subjects' => ['C', 'D'], 'hashes' => [['E'], ['F']]]],
                // Dummy tag, no commits.
                ['A' => ['date' => 'B', 'subjects' => [], 'hashes' => []]],
                // Dummy tag and commits.
                ['A' => ['date' => 'B', 'subjects' => ['C', 'D'], 'hashes' => [['E', 'F'], ['G']]]],
            ];
        $expectedValues =
            [
                //No tags
                "# Changelog\n\nNo changes.\n",
                // Head Revision included.
                "# Changelog\n\n## Upcoming changes (Undetermined)\n\n* D (F)\n* C (E)\n",
                // Dummy tag, no commits.
                "# Changelog\n\n## A (B)\n\n* No changes.\n",
                // Dummy tag and commits.
                "# Changelog\n\n## A (B)\n\n* D (G)\n* C (E, F)\n",
            ];

        foreach ($testValues as $key => $value) {
            $this->setPrivateProperty($changeLog, 'commitData', $value);
            $changeLog->build();
            $this->assertEquals($expectedValues[$key], $changeLog->get());
        }
    }
}
