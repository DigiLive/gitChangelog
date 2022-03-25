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

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace DigiLive\GitChangelog\Tests;

use DigiLive\GitChangelog\GitChangelog;
use DigiLive\GitChangelog\GitChangelogException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * PHPUnit tests of class GitChangelog.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class GitChangelogTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @var \DigiLive\GitChangelog\GitChangelog The object that will be tested against.
     */
    private $changelog;

    /**
     * Set up each test case.
     *
     * @return void
     */
    public function setUp(): void
    {
        vfsStream::setup('testFolder', null, ['baseLog.md' => 'Base Log Content']);
        $this->changelog = new GitChangelog();
    }

    /**
     * Test fetching the cached repository tags.
     *
     * The first tag should always be null which represents the HEAD revision of the repository.
     *
     * @return void
     * @throws \DigiLive\GitChangelog\GitChangelogException When fetching the tags fails.
     */
    public function testFetchTagsCached(): void
    {
        $tags = $this->changelog->fetchTags();
        $this->assertNull(reset($tags));
    }

    /**
     * Test re-fetching the tags from the repository.
     *
     * @return void
     * @throws \DigiLive\GitChangelog\GitChangelogException When fetching the tags fails.
     * @throws \ReflectionException If property GitChangelog::$gitTags doesn't exist.
     */
    public function testFetchTagsUncached(): void
    {
        // Set dummy values as pre-fetched tags.
        $this->setPrivateProperty($this->changelog, 'gitTags', [null, 'dummyTag']);
        // Request HEAD only. '' is cast to null when searching for the tag.
        $this->changelog->setFromTag('');

        // Expect only the HEAD revision.
        $this->assertSame([null], $this->changelog->fetchTags(true));
    }

    /**
     * Test if fetching tags from the repository fails when the from-tag doesn't exist.
     *
     * @return void
     * @throws \DigiLive\GitChangelog\GitChangelogException When fetching the tags fails.
     * @throws \ReflectionException If property GitChangelog::$fromTag doesn't exist.
     */
    public function testFetchTagsThrowsExceptionOnInvalidFromTag(): void
    {
        // Set from-tag to non-existing repository tag.
        $this->setPrivateProperty($this->changelog, 'fromTag', 'notExisting');

        $this->expectException(GitChangelogException::class);
        $this->changelog->fetchTags(true);
    }

    /**
     * Test if fetching tags from the repository fails when the to-tag doesn't exist.
     *
     * @return void
     * @throws \DigiLive\GitChangelog\GitChangelogException When fetching the tags fails.
     * @throws \ReflectionException If property GitChangelog::$toTag doesn't exist.
     */
    public function testFetchTagsTrowsExceptionOnInvalidToTag(): void
    {
        // Set to-tag to non-existing repository tag.
        $this->setPrivateProperty($this->changelog, 'toTag', 'notExisting');

        $this->expectException(GitChangelogException::class);
        $this->changelog->fetchTags(true);
    }

    /**
     * Test getting the content of the changelog.
     *
     * Tested for content:
     * - without base content.
     * - with base content.
     * - with base content from a file.
     * - with base content from an unreadable file.
     *
     * @return void
     * @throws \ReflectionException If property GitChangelog::$changelog doesn't exist.
     */
    public function testGet(): void
    {
        // Set the changelog content to a dummy value.
        $dummyContent = 'Dummy Content';
        $this->setPrivateProperty($this->changelog, 'changelog', $dummyContent);

        // Test without base content.
        $this->assertequals($dummyContent, $this->changelog->get(false));

        // Test with base content
        $this->changelog->setBaseContent($dummyContent);
        $this->assertequals($dummyContent . $dummyContent, $this->changelog->get(true));

        // Setup base content from a file.
        $baseFilePath    = vfsStream::url('testFolder/baseLog.md');
        $baseFileContent = file_get_contents($baseFilePath);

        // Test with base content from a file.
        $this->changelog->setBaseContent($baseFilePath);
        $this->assertequals($dummyContent . $baseFileContent, $this->changelog->get(true));

        // Test with content of unreadable file.
        // Filepath is treated as a string and appended to the changelog.
        chmod($baseFilePath, 0000);
        $this->changelog->setBaseContent($baseFilePath);
        $this->assertequals($dummyContent . $baseFilePath, $this->changelog->get(true));
    }

    /**
     * Test setting the labels to include into the changelog.
     *
     * Tested against:
     * - Multiple parameters.
     * - No parameters.
     * - One unpacked array parameter.
     *
     * @return void
     * @throws \ReflectionException If property GitChangelog::$labels doesn't exist.
     */
    public function testSetLabels(): void
    {
        // Test with string parameters.
        $this->changelog->setLabels('label1', 'label2');
        self::assertSame(['label1', 'label2'], $this->getPrivateProperty($this->changelog, 'labels'));

        // Remove all labels.
        $this->changelog->setLabels();
        self::assertSame([], $this->getPrivateProperty($this->changelog, 'labels'));

        // Test with array parameter.
        $labels = ['label1', 'label2'];
        $this->changelog->setLabels(...$labels);
        self::assertSame(['label1', 'label2'], $this->getPrivateProperty($this->changelog, 'labels'));
    }

    /**
     * Test if setting labels raises a notice/warning when passed a packed array parameter.
     *
     * @return void
     */
    public function testSetLabelsRaisesNotice(): void
    {
        $this->expectNotice(); // PHP version ^7.3
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $this->expectWarning();  // PHP version ^8
        }
        /** @noinspection PhpParamsInspection */
        $this->changelog->setLabels([]);
    }

    /**
     * Test if setting labels raises an error when passed an object that doesn't implement the __toString() method
     *
     * @return void
     */
    public function testSetLabelsRaisesError(): void
    {
        $this->expectException(\Error::class);
        /** @noinspection PhpParamsInspection */
        $this->changelog->setLabels(new stdClass());
    }

    /**
     * Test if property GitChangelog::$commitData is properly constructed.
     *
     * @return void
     * @throws \DigiLive\GitChangelog\GitChangelogException When fetching the repository tags fails.
     */
    public function testFetchCommitData(): void
    {
        // Test the format of fetched commit data.
        // The first loop checks the fetched data while the second loop checks the cached data.
        $loopCount = 0;
        do {
            $loopCount++;
            $commitData   = $this->changelog->fetchCommitData();
            $firstElement = reset($commitData);

            $this->assertSame('', key($commitData));
            $this->assertArrayHasKey('date', $firstElement);
            $this->assertArrayHasKey('titles', $firstElement);
            $this->assertArrayHasKey('hashes', $firstElement);
            $this->assertIsArray($firstElement['titles']);
            $this->assertIsArray($firstElement['hashes']);
        } while ($loopCount < 2);
    }

    /**
     * Test setting the from-tag property.
     *
     * Tested against:
     * - A valid value.
     * - No value.
     * - An invalid value.
     *
     * @return void
     * @throws \ReflectionException If property GitChangelog::$fromTag doesn't exist.
     */
    public function testSetFromTag(): void
    {
        // Test setting tag value.
        // Set to HEAD revision. '' is cast to null when searching for the tag.
        $this->changelog->setFromTag('');
        $this->assertSame('', $this->getPrivateProperty($this->changelog, 'fromTag'));

        // Test removing tag value.
        $this->changelog->setFromTag();
        $this->assertNull($this->getPrivateProperty($this->changelog, 'fromTag'));

        // Test exception.
        $this->expectException(\OutOfBoundsException::class);
        $this->changelog->setFromTag('DoesNotExist');
    }

    /**
     * Test the processing of the commit data.
     *
     * @return void
     * @throws \ReflectionException If class reflection fails.
     */
    public function testProcessCommitData(): void
    {
        // Set dummy commit data.
        $commitData = [
            'A' => [
                'date'   => 'B',
                'titles' => [0 => 'C', 1 => 'D', 2 => 'C', 3 => 'E', 4 => 'F'],
                'hashes' => [0 => 'G', 1 => 'H', 2 => 'I', 3 => 'J', 4 => 'K'],
            ],
        ];

        $this->setPrivateProperty($this->changelog, 'commitData', $commitData);

        // Set labels to process.
        $this->changelog->setLabels('C', 'D', 'F');
        // Define expected data after processing.
        $processedData = [
            'A' => [
                'date'   => 'B',
                'titles' => [0 => 'C', 1 => 'D', 4 => 'F'],
                'hashes' => [0 => ['G', 'I'], 1 => ['H'], 4 => ['K']],
            ],
        ];

        // Start processing.
        $processCommitData = $this->getPrivateMethod($this->changelog, 'processCommitData');
        $processCommitData->invokeArgs($this->changelog, []);

        $this->assertSame($processedData, $this->getPrivateProperty($this->changelog, 'commitData'));
    }

    /**
     * Test adding a label.
     *
     * Tested against:
     * - Multiple parameters.
     * - One unpacked array parameter.
     *
     * @return void
     * @throws \ReflectionException If property GitChangelog::$labels doesn't exist.
     */
    public function testAddLabel(): void
    {
        // Get default labels.
        $defaultLabels  = $this->getPrivateProperty($this->changelog, 'labels');
        $expectedLabels = array_merge($defaultLabels, ['label1', 'label2']);

        // Test with string parameters.
        $this->changelog->addLabel('label1', 'label2');
        $this->assertEquals($expectedLabels, $this->getPrivateProperty($this->changelog, 'labels'));

        // Test with array parameter.
        $labels = ['label1', 'label2'];
        $this->changelog->addLabel(...$labels);
        $this->assertEquals($expectedLabels, $this->getPrivateProperty($this->changelog, 'labels'));
    }

    /**
     * Test setting the to-tag property.
     *
     * Tested against:
     * - A valid value.
     * - No value.
     * - An invalid value.
     *
     * @return void
     * @throws \ReflectionException If property GitChangelog::$toTag doesn't exist.
     */
    public function testSetToTag(): void
    {
        // Test setting tag value.
        $this->changelog->setToTag('');
        $this->assertEquals('', $this->getPrivateProperty($this->changelog, 'toTag'));

        // Test removing tag value.
        $this->changelog->setToTag();
        $this->assertSame('', $this->getPrivateProperty($this->changelog, 'toTag'));

        // Test exception.
        $this->expectException(\OutOfBoundsException::class);
        $this->changelog->setToTag('DoesNotExist');
    }

    /**
     * Test removing a label.
     *
     * Tested against:
     * - Multiple parameters.
     * - One unpacked array parameter.
     *
     * @return void
     * @throws \ReflectionException If property GitChangelog::$labels doesn't exist.
     */
    public function testRemoveLabel(): void
    {
        // Set test labels.
        $labels = ['Add', 'Cut', 'Fix', 'Bump'];
        $this->setPrivateProperty($this->changelog, 'labels', $labels);

        // Test with array parameter.
        $this->changelog->removeLabel(...$labels);
        $this->assertEquals([], $this->getPrivateProperty($this->changelog, 'labels'));

        // Test with string parameters.
        $this->changelog->addLabel('newLabel1', 'newLabel2', 'newLabel3');
        $this->changelog->removeLabel('newLabel1', 'newLabel2', 'NotExisting');
        $this->assertEquals(['newLabel3'], $this->getPrivateProperty($this->changelog, 'labels'));
    }

    /**
     * Test saving the changelog to a file.
     *
     * @return void
     * @throws \DigiLive\GitChangelog\GitChangelogException When writing of the file fails.
     * @throws \ReflectionException If property GitChangelog::$changelog doesn't exist.
     */
    public function testSave(): void
    {
        // Set up test values.
        $saveFilePath = vfsStream::url('testFolder/changelog.md');
        $dummyContent = 'Dummy Content';
        $this->setPrivateProperty($this->changelog, 'changelog', $dummyContent);

        // Test without base file.
        $this->changelog->save($saveFilePath);
        $this->assertFileExists($saveFilePath);
        $fileContent = file_get_contents($saveFilePath);
        $this->assertEquals($dummyContent, $fileContent);

        // Test with base file.
        $baseFilePath    = vfsStream::url('testFolder/baseLog.md');
        $baseFileContent = file_get_contents($baseFilePath);

        $this->changelog->setBaseContent($baseFilePath);
        $this->changelog->save($saveFilePath);
        $fileContent = file_get_contents($saveFilePath);
        $this->assertEquals($dummyContent . $baseFileContent, $fileContent);
    }

    /**
     * Test if saving the changelog throws an exception when writing to a non-existing file.
     *
     * @return void
     */
    public function testSaveThrowsExceptionOnNonExistingFile(): void
    {
        $this->expectException(GitChangelogException::class);
        $this->changelog->save('nonExistingPath/fileName');
    }

    /**
     * Test if saving the changelog throws an exception when writing to a non-writable file.
     *
     * @return void
     */
    public function testSaveThrowsExceptionOnNonWritableFile(): void
    {
        $filePath = vfsStream::url('testFolder/changelog.md');

        // Create a file and remove any permission it has.
        $this->changelog->save($filePath);
        chmod($filePath, 0000);

        $this->expectException(GitChangelogException::class);
        $this->changelog->save(vfsStream::url('testFolder/changelog.md'));
    }

    /**
     * Test setting options.
     *
     * Tested against:
     * - A single option.
     * - Multiple options.
     *
     * @return void
     * @throws \OutOfBoundsException If the option you're trying to set is invalid.
     * @throws \RangeException When setting option 'headTag' to an invalid value.
     * @throws \DigiLive\GitChangelog\GitChangelogException If fetching the repository tags fails.
     * @throws \ReflectionException If property GitChangelog::$options doesn't exist.
     */
    public function testSetOptions(): void
    {
        // Set single option.
        $this->changelog->setOptions('logHeader', 'Test');
        $this->assertEquals('Test', $this->getPrivateProperty($this->changelog, 'options')['logHeader']);

        // Set multiple options at once.
        $this->changelog->setOptions(
            [
                'logHeader'   => 'Test1',
                'headTagName' => 'Test2',
            ]
        );
        $this->assertEquals('Test1', $this->getPrivateProperty($this->changelog, 'options')['logHeader']);
        $this->assertEquals('Test2', $this->getPrivateProperty($this->changelog, 'options')['headTagName']);
    }

    /**
     * Test if setting an invalid option fails.
     *
     * @return void
     * @throws \RangeException When setting option 'headTag' to an invalid value.
     * @throws \DigiLive\GitChangelog\GitChangelogException If fetching the repository tags fails.
     */
    public function testSetOptionsThrowsExceptionOnInvalidOption(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->changelog->setOptions('NotExistingOption', 'Test');
    }

    /**
     * Test if setting an invalid tag name for the head revision fails.
     *
     * @return void
     * @throws \OutOfBoundsException If the option you're trying to set is invalid.
     * @throws \DigiLive\GitChangelog\GitChangelogException If fetching the repository tags fails.
     * @throws \ReflectionException If property GitChangelog::$gitTags doesn't exist.
     */
    public function testSetOptionsThrowsExceptionOnInvalidHeadTagNameValue(): void
    {
        // Set the pre-fetched tags to test values.
        $this->setPrivateProperty($this->changelog, 'gitTags', ['Test']);

        $this->expectException(\RangeException::class);
        $this->changelog->setOptions('headTagName', 'Test');
    }
}
