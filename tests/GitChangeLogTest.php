<?php

/** @noinspection PhpUnhandledExceptionInspection */

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

namespace DigiLive\GitChangeLog\Tests;

use DigiLive\GitChangeLog\GitChangeLog;
use Exception;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use stdClass;

class GitChangeLogTest extends TestCase
{

    /**
     * set up test environment
     */
    public function setUp(): void
    {
        vfsStream::setup('testFolder', null, ['baseLog.md' => 'Base Log Content']);
    }

    public function testArrayStrPos0()
    {
        $this->assertTrue(GitChangeLog::arrayStrPos0('Hello World', 'Hello'));
        $this->assertTrue(GitChangeLog::arrayStrPos0('Hello World', 'hello'));
        $this->assertTrue(GitChangeLog::arrayStrPos0('Hello World', ['World', 'Hello']));

        $this->assertFalse(GitChangeLog::arrayStrPos0('Hello World', 'World'));
        $this->assertFalse(GitChangeLog::arrayStrPos0('Hello World', 'Hello', 1));
    }

    public function testFetchTagsCached()
    {
        $changelog = new GitChangeLog();

        $tags = $changelog->fetchTags();
        $this->assertEquals('HEAD', reset($tags));
    }

    public function testFetchTagsUncached()
    {
        $changelog = new GitChangeLog();
        $changelog->setFromTag('HEAD');

        $tags = $changelog->fetchTags(true);
        $this->assertEquals('HEAD', reset($tags));
    }

    public function testFetchTagsThrowsExceptionOnInvalidFromTag()
    {
        $changelog = new GitChangeLog();
        $this->setPrivateProperty($changelog, 'fromTag', 'notExisting');

        $this->expectException(Exception::class);
        $changelog->fetchTags(true);
    }

    /**
     * Sets a private or protected property on a given object via reflection
     *
     * @param $object    - Instance in which the private or protected value is being modified.
     * @param $property  - Property of instance which is being modified.
     * @param $value     - New value of the property which is being modified.
     *
     * @return void
     * @throws ReflectionException If no property exists by that name.
     */
    public function setPrivateProperty($object, $property, $value): void
    {
        $reflection         = new ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);

        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }

    public function testFetchTagsTrowsExceptionOnInvalidToTag()
    {
        $changelog = new GitChangeLog();
        $this->setPrivateProperty($changelog, 'toTag', 'notExisting');

        $this->expectException(Exception::class);
        $changelog->fetchTags(true);
    }

    public function testGet()
    {
        $changeLog    = new GitChangeLog();
        $dummyContent = 'Dummy Content';
        $this->setPrivateProperty($changeLog, 'changelog', $dummyContent);

        // Test without base file.
        $this->assertequals($dummyContent, $changeLog->get());

        // Test with base file.
        $baseFilePath    = vfsStream::url('testFolder/baseLog.md');
        $baseFileContent = file_get_contents($baseFilePath);

        $changeLog->baseFile = $baseFilePath;
        $this->assertequals($dummyContent . $baseFileContent, $changeLog->get(true));

        // Test warning.
        chmod($baseFilePath, 0000);
        $this->expectWarning();
        $changeLog->get(true);
    }

    public function testSetLabels()
    {
        $changeLog = new GitChangeLog();

        // Test with string parameters.
        $changeLog->setLabels('label1', 'label2');
        self::assertEquals(['label1', 'label2'], $this->getPrivateProperty($changeLog, 'labels'));

        // Remove all labels.
        $changeLog->setLabels();

        // Test with array parameter.
        $changeLog->setLabels(...['label1', 'label2']);
        self::assertEquals(['label1', 'label2'], $this->getPrivateProperty($changeLog, 'labels'));
    }

    /**
     * Get a private or protected property on a given object via reflection
     *
     * @param $object    - Instance in which the private or protected property exists.
     * @param $property  - Property of instance which is being read.
     *
     * @return mixed The value of the property.
     * @throws ReflectionException If no property exists by that name.
     */
    private function getPrivateProperty($object, $property)
    {
        $reflection         = new ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);

        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }

    public function testSetLabelsRaisesNotice()
    {
        $changeLog = new GitChangeLog();

        $this->expectNotice();
        $changeLog->setLabels([]);
    }

    public function testSetLabelsRaisesError()
    {
        $changeLog = new GitChangeLog();

        $this->expectException('Error');
        $changeLog->setLabels(new stdClass());
    }

    public function testFetchCommitData()
    {
        $changeLog = new GitChangeLog();

        // Test the format of fetched commit data.
        // The first loop check the fetched data while the second loop checks the cached data.
        $loopCount = 0;
        do {
            $loopCount++;
            $commitData   = $changeLog->fetchCommitData();
            $firstElement = reset($commitData);

            $this->assertEquals('HEAD', key($commitData));
            $this->assertArrayHasKey('date', $firstElement);
            $this->assertArrayHasKey('subjects', $firstElement);
            $this->assertArrayHasKey('hashes', $firstElement);
            $this->assertIsArray($firstElement['subjects']);
            $this->assertIsArray($firstElement['hashes']);
        } while ($loopCount < 2);
    }

    public function testSetFromTag()
    {
        $changeLog = new GitChangeLog();

        // Test setting tag value.
        $changeLog->setFromTag('HEAD');
        $this->assertEquals('HEAD', $this->getPrivateProperty($changeLog, 'fromTag'));

        // Test removing tag value.
        $changeLog->setFromTag();
        $this->assertNull($this->getPrivateProperty($changeLog, 'fromTag'));

        // Test exception.
        $this->expectException('Exception');
        $changeLog->setFromTag('DoesNotExist');
    }

    public function testBuildAscendingCommitOrder()
    {
        $changeLog = new GitChangeLog();
        $changeLog->setOptions('tagOrderDesc', false);
        $testValues     =
            [
                // No tags.
                [],
                // Head Revision included.
                ['HEAD' => ['date' => 'B', 'subjects' => ['C', 'D'], 'hashes' => [['E'], ['F']]]],
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
                "# Changelog\n\n## Upcoming changes (Undetermined)\n\n* C (E)\n* D (F)\n",
                // Dummy tag, no commits.
                "# Changelog\n\n## A (B)\n\n* No changes.\n",
                // Dummy tag and commits.
                "# Changelog\n\n## A (B)\n\n* C (E, F)\n* D (G)\n",
            ];

        foreach ($testValues as $key => $value) {
            $this->setPrivateProperty($changeLog, 'commitData', $value);
            $changeLog->build();
            $this->assertEquals($expectedValues[$key], $changeLog->get());
        }
    }

    public function testBuildDescendingCommitOrder()
    {
        $changeLog = new GitChangeLog();
        $changeLog->setOptions('commitOrder', 'DESC');
        $testValues     =
            [
                // No tags.
                [],
                // Head Revision included.
                ['HEAD' => ['date' => 'B', 'subjects' => ['C', 'D'], 'hashes' => [['E'], ['F']]]],
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

    public function testProcessCommitData()
    {
        $changeLog = new ReflectionClass('DigiLive\GitChangeLog\GitChangeLog');
        $method    = $changeLog->getMethod('processCommitData');
        $method->setAccessible(true);

        $changeLog = new GitChangeLog();
        $changeLog->setLabels('C', 'D', 'F');

        $commitData = [
            'A' => [
                'date'     => 'B',
                'subjects' => [0 => 'C', 1 => 'D', 2 => 'C', 3 => 'E', 4 => 'F'],
                'hashes'   => [0 => 'G', 1 => 'H', 2 => 'I', 3 => 'J', 4 => 'K'],
            ],
        ];
        $this->setPrivateProperty($changeLog, 'commitData', $commitData);
        $commitData = [
            'A' => [
                'date'     => 'B',
                'subjects' => [0 => 'C', 1 => 'D', 4 => 'F'],
                'hashes'   => [0 => ['G', 'I'], 1 => ['H'], 4 => ['K']],
            ],
        ];
        $method->invokeArgs($changeLog, []);

        $this->assertEquals($commitData, $this->getPrivateProperty($changeLog, 'commitData'));
    }

    public function testAddLabel()
    {
        $changeLog = new GitChangeLog();

        $defaultLabels  = $this->getPrivateProperty($changeLog, 'labels');
        $expectedLabels = array_merge($defaultLabels, ['label1', 'label2']);

        // Test with string parameters.
        $changeLog->addLabel('label1', 'label2');
        $this->assertEquals($expectedLabels, $this->getPrivateProperty($changeLog, 'labels'));

        // Test with array parameter.
        $changeLog->addLabel(...['label1', 'label2']);
        $this->assertEquals($expectedLabels, $this->getPrivateProperty($changeLog, 'labels'));
    }

    public function testSetToTag()
    {
        $changeLog = new GitChangeLog();

        // Test setting tag value.
        $changeLog->setToTag('HEAD');
        $this->assertEquals('HEAD', $this->getPrivateProperty($changeLog, 'toTag'));

        // Test removing tag value.
        $changeLog->setToTag();
        $this->assertEquals('HEAD', $this->getPrivateProperty($changeLog, 'toTag'));

        // Test exception.
        $this->expectException('Exception');
        $changeLog->setToTag('DoesNotExist');
    }

    public function testRemoveLabel()
    {
        $changeLog = new GitChangeLog();

        $defaultLabels = $this->getPrivateProperty($changeLog, 'labels');

        // Test with array parameter.
        $changeLog->removeLabel(...$defaultLabels);
        $this->assertEquals([], $this->getPrivateProperty($changeLog, 'labels'));

        // Test with string parameters.
        $changeLog->addLabel('newLabel1', 'newLabel2', 'newLabel3');
        $changeLog->removeLabel('newLabel1', 'newLabel2');
        array_shift($defaultLabels);
        $this->assertEquals(['newLabel3'], $this->getPrivateProperty($changeLog, 'labels'));
    }

    public function testSave()
    {
        $changeLog    = new GitChangeLog();
        $saveFilePath = vfsStream::url('testFolder/changelog.md');
        $dummyContent = 'Dummy Content';
        $this->setPrivateProperty($changeLog, 'changelog', $dummyContent);

        // Test without base file.
        $changeLog->save($saveFilePath);
        $this->assertFileExists($saveFilePath);
        $fileContent = file_get_contents($saveFilePath);
        $this->assertEquals($dummyContent, $fileContent);


        // Test with base file.
        $baseFilePath    = vfsStream::url('testFolder/baseLog.md');
        $baseFileContent = file_get_contents($baseFilePath);

        $changeLog->baseFile = $baseFilePath;
        $changeLog->save($saveFilePath);
        $fileContent = file_get_contents($saveFilePath);
        $this->assertEquals($dummyContent . $baseFileContent, $fileContent);

        // Test warning.
        chmod($baseFilePath, 0000);
        $this->expectWarning();
        $changeLog->save($saveFilePath);
    }

    public function testSaveThrowsExceptionOnWriteableCheck()
    {
        $changeLog = new GitChangeLog();

        $this->expectException('RunTimeException');
        $changeLog->save('nonExistingPath/fileName');
        $changeLog->save(vfsStream::url('testFolder/changelog.md'));
    }

    public function testSaveThrowsExceptionOnWrite()
    {
        $changeLog = new GitChangeLog();
        $filePath  = vfsStream::url('testFolder/changelog.md');

        // Create a file and remove any permission it has.
        $changeLog->save($filePath);
        chmod($filePath, 0000);

        $this->expectException('RunTimeException');
        $changeLog->save(vfsStream::url('testFolder/changelog.md'));
    }

    public function testSetOptions()
    {
        $changeLog = new GitChangeLog();

        // Set multiple options at once.
        $changeLog->setOptions([
            'logHeader'   => 'Test1',
            'headSubject' => 'Test2',
        ]);
        $this->assertEquals('Test1', $this->getPrivateProperty($changeLog, 'options')['logHeader']);
        $this->assertEquals('Test2', $this->getPrivateProperty($changeLog, 'options')['headSubject']);

        // Set single option.
        $changeLog->setOptions('logHeader', 'Test');
        $this->assertEquals('Test', $this->getPrivateProperty($changeLog, 'options')['logHeader']);
    }

    public function testSetOptionsThrowsException()
    {
        $changeLog = new GitChangeLog();

        $this->expectException('InvalidArgumentException');
        $changeLog->setOptions('NotExistingOption', 'Test');
    }
}
