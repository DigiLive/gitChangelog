<?php

/** @noinspection PhpUnhandledExceptionInspection */

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

declare(strict_types=1);

namespace DigiLive\GitChangelog\Tests;

use DigiLive\GitChangelog\GitChangelog;
use DigiLive\GitChangelog\GitChangelogException;
use Exception;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

/**
 * Class GitChangelogTest
 *
 * PHPUnit tests of class GitChangelog.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 *
 * @package DigiLive\GitChangelog\Tests
 */
class GitChangelogTest extends TestCase
{
    /**
     * set up test environment
     */
    public function setUp(): void
    {
        vfsStream::setup('testFolder', null, ['baseLog.md' => 'Base Log Content']);
    }

    public function testFetchTagsCached()
    {
        $changelog = new GitChangelog();

        $tags = $changelog->fetchTags();
        $this->assertNull(reset($tags));
    }

    public function testFetchTagsUncached()
    {
        $changelog = new GitChangelog();
        $this->setPrivateProperty($changelog, 'gitTags', [null, 'dummyTag']);
        $changelog->setFromTag('');

        $this->assertSame([null], $changelog->fetchTags(true));
    }

    public function testFetchTagsThrowsExceptionOnInvalidFromTag()
    {
        $changelog = new GitChangelog();
        $this->setPrivateProperty($changelog, 'fromTag', 'notExisting');

        $this->expectException(Exception::class);
        $changelog->fetchTags(true);
    }

    /**
     * Sets a private or protected property on a given object via reflection
     *
     * @param   object  $object    - Instance in which the private or protected value is being modified.
     * @param   string  $property  - Property of instance which is being modified.
     * @param           $value     - New value of the property which is being modified.
     *
     * @return void
     * @throws \ReflectionException If no property exists by that name.
     */
    private function setPrivateProperty(object $object, string $property, $value): void
    {
        $reflection         = new ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);

        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }

    public function testFetchTagsTrowsExceptionOnInvalidToTag()
    {
        $changelog = new GitChangelog();
        $this->setPrivateProperty($changelog, 'toTag', 'notExisting');

        $this->expectException(Exception::class);
        $changelog->fetchTags(true);
    }

    public function testGet()
    {
        $changeLog    = new GitChangelog();
        $dummyContent = 'Dummy Content';
        $this->setPrivateProperty($changeLog, 'changelog', $dummyContent);

        // Test without base content.
        $this->assertequals($dummyContent, $changeLog->get(false));

        // Test with base content
        $changeLog->setBaseContent($dummyContent);
        $this->assertequals($dummyContent . $dummyContent, $changeLog->get(true));

        // Test with base content from file.
        $baseFilePath    = vfsStream::url('testFolder/baseLog.md');
        $baseFileContent = file_get_contents($baseFilePath);

        $changeLog->setBaseContent($baseFilePath);
        $this->assertequals($dummyContent . $baseFileContent, $changeLog->get(true));

        // Test with content of unreadable file.
        chmod($baseFilePath, 0000);
        $changeLog->setBaseContent($baseFilePath);
        $this->assertequals($dummyContent . $baseFilePath, $changeLog->get(true));
    }

    public function testSetLabels()
    {
        $changeLog = new GitChangelog();

        // Test with string parameters.
        $changeLog->setLabels('label1', 'label2');
        self::assertEquals(['label1', 'label2'], $this->getPrivateProperty($changeLog, 'labels'));

        // Remove all labels.
        $changeLog->setLabels();

        // Test with array parameter.
        $changeLog->setLabels('label1', 'label2');
        self::assertEquals(['label1', 'label2'], $this->getPrivateProperty($changeLog, 'labels'));
    }

    /**
     * Get a private or protected property on a given object via reflection
     *
     * @param   object  $object    - Instance in which the private or protected property exists.
     * @param   string  $property  - Property of instance which is being read.
     *
     * @return mixed The value of the property.
     * @throws \ReflectionException If no property exists by that name.
     */
    private function getPrivateProperty(object $object, string $property)
    {
        $reflection         = new ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);

        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }

    public function testSetLabelsRaisesNotice()
    {
        $changeLog = new GitChangelog();

        $this->expectNotice(); // PHP version ^7.3
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $this->expectWarning();  // PHP version ^8
        }
        /** @noinspection PhpParamsInspection */
        $changeLog->setLabels([]);
    }

    public function testSetLabelsRaisesError()
    {
        $changeLog = new GitChangelog();

        $this->expectException(\Error::class);
        /** @noinspection PhpParamsInspection */
        $changeLog->setLabels(new stdClass());
    }

    public function testFetchCommitData()
    {
        $changeLog = new GitChangelog();

        // Test the format of fetched commit data.
        // The first loop check the fetched data while the second loop checks the cached data.
        $loopCount = 0;
        do {
            $loopCount++;
            $commitData   = $changeLog->fetchCommitData();
            $firstElement = reset($commitData);

            $this->assertSame('', key($commitData));
            $this->assertArrayHasKey('date', $firstElement);
            $this->assertArrayHasKey('titles', $firstElement);
            $this->assertArrayHasKey('hashes', $firstElement);
            $this->assertIsArray($firstElement['titles']);
            $this->assertIsArray($firstElement['hashes']);
        } while ($loopCount < 2);
    }

    public function testSetFromTag()
    {
        $changeLog = new GitChangelog();

        // Test setting tag value.
        $changeLog->setFromTag('');
        $this->assertEquals('', $this->getPrivateProperty($changeLog, 'fromTag'));

        // Test removing tag value.
        $changeLog->setFromTag();
        $this->assertNull($this->getPrivateProperty($changeLog, 'fromTag'));

        // Test exception.
        $this->expectException(\OutOfBoundsException::class);
        $changeLog->setFromTag('DoesNotExist');
    }

    public function testProcessCommitData()
    {
        $changeLog = new ReflectionClass('DigiLive\GitChangelog\GitChangelog');
        $method    = $changeLog->getMethod('processCommitData');
        $method->setAccessible(true);

        $changeLog = new GitChangelog();
        $changeLog->setLabels('C', 'D', 'F');

        $commitData = [
            'A' => [
                'date'   => 'B',
                'titles' => [0 => 'C', 1 => 'D', 2 => 'C', 3 => 'E', 4 => 'F'],
                'hashes' => [0 => 'G', 1 => 'H', 2 => 'I', 3 => 'J', 4 => 'K'],
            ],
        ];
        $this->setPrivateProperty($changeLog, 'commitData', $commitData);
        $commitData = [
            'A' => [
                'date'   => 'B',
                'titles' => [0 => 'C', 1 => 'D', 4 => 'F'],
                'hashes' => [0 => ['G', 'I'], 1 => ['H'], 4 => ['K']],
            ],
        ];
        $method->invokeArgs($changeLog, []);

        $this->assertEquals($commitData, $this->getPrivateProperty($changeLog, 'commitData'));
    }

    public function testAddLabel()
    {
        $changeLog = new GitChangelog();

        $defaultLabels  = $this->getPrivateProperty($changeLog, 'labels');
        $expectedLabels = array_merge($defaultLabels, ['label1', 'label2']);

        // Test with string parameters.
        $changeLog->addLabel('label1', 'label2');
        $this->assertEquals($expectedLabels, $this->getPrivateProperty($changeLog, 'labels'));

        // Test with array parameter.
        $changeLog->addLabel('label1', 'label2');
        $this->assertEquals($expectedLabels, $this->getPrivateProperty($changeLog, 'labels'));
    }

    public function testSetToTag()
    {
        $changeLog = new GitChangelog();

        // Test setting tag value.
        $changeLog->setToTag('');
        $this->assertEquals('', $this->getPrivateProperty($changeLog, 'toTag'));

        // Test removing tag value.
        $changeLog->setToTag();
        $this->assertSame('', $this->getPrivateProperty($changeLog, 'toTag'));

        // Test exception.
        $this->expectException(\OutOfBoundsException::class);
        $changeLog->setToTag('DoesNotExist');
    }

    public function testRemoveLabel()
    {
        $changeLog = new GitChangelog();
        $labels    = ['Add', 'Cut', 'Fix', 'Bump'];

        $this->setPrivateProperty($changeLog, 'labels', $labels);

        // Test with array parameter.
        $changeLog->removeLabel(...$labels);
        $this->assertEquals([], $this->getPrivateProperty($changeLog, 'labels'));

        // Test with string parameters.
        $changeLog->addLabel('newLabel1', 'newLabel2', 'newLabel3');
        $changeLog->removeLabel('newLabel1', 'newLabel2', 'NotExisting');
        $this->assertEquals(['newLabel3'], $this->getPrivateProperty($changeLog, 'labels'));
    }

    public function testSave()
    {
        $changeLog    = new GitChangelog();
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

        $changeLog->setBaseContent($baseFilePath);
        $changeLog->save($saveFilePath);
        $fileContent = file_get_contents($saveFilePath);
        $this->assertEquals($dummyContent . $baseFileContent, $fileContent);
    }

    public function testSaveThrowsExceptionOnWriteableCheck()
    {
        $changeLog = new GitChangelog();

        $this->expectException(GitChangelogException::class);
        $changeLog->save('nonExistingPath/fileName');
    }

    public function testSaveThrowsExceptionOnWrite()
    {
        $changeLog = new GitChangelog();
        $filePath  = vfsStream::url('testFolder/changelog.md');

        // Create a file and remove any permission it has.
        $changeLog->save($filePath);
        chmod($filePath, 0000);

        $this->expectException(GitChangelogException::class);
        $changeLog->save(vfsStream::url('testFolder/changelog.md'));
    }

    public function testSetOptions()
    {
        $changeLog = new GitChangelog();

        // Set multiple options at once.
        $changeLog->setOptions(
            [
                'logHeader'   => 'Test1',
                'headTagName' => 'Test2',
            ]
        );
        $this->assertEquals('Test1', $this->getPrivateProperty($changeLog, 'options')['logHeader']);
        $this->assertEquals('Test2', $this->getPrivateProperty($changeLog, 'options')['headTagName']);

        // Set single option.
        $changeLog->setOptions('logHeader', 'Test');
        $this->assertEquals('Test', $this->getPrivateProperty($changeLog, 'options')['logHeader']);
    }

    public function testSetOptionsThrowsExceptionOnInvalidOption()
    {
        $changeLog = new GitChangelog();

        $this->expectException(\OutOfBoundsException::class);
        $changeLog->setOptions('NotExistingOption', 'Test');
    }

    public function testSetOptionsThrowsExceptionOnInvalidHeadTagNameValue()
    {
        $changeLog = new GitChangelog();
        $this->setPrivateProperty($changeLog, 'gitTags', ['Test']);

        $this->expectException(\RangeException::class);
        $changeLog->setOptions('headTagName', 'Test');
    }
}
