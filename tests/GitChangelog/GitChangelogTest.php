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

namespace DigiLive\GitChangelog\Tests\GitChangelog;

use DigiLive\GitChangelog\GitChangelog\GitChangelog;
use DigiLive\GitChangelog\RepoHandler\RepoHandler;
use DigiLive\GitChangelog\Tests\ReflectionTrait;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests of class GitChangelog.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class GitChangelogTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @var \DigiLive\GitChangelog\GitChangelog\GitChangelog The object that will be tested against.
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
     * Test getting the content of the changelog.
     *
     * @return void
     * @throws \ReflectionException If property GitChangelog::$changelog doesn't exist.
     */
    public function testGet(): void
    {
        // Set the changelog content to a dummy value.
        $dummyContent = 'Dummy Content';
        $this->setPrivateProperty($this->changelog, 'changelog', $dummyContent);

        $this->assertequals($dummyContent, $this->changelog->get());
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
        $this->changelog->setLabels(new \stdClass());
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
     * @throws \ReflectionException If property GitChangelog::$repoHandler doesn't exist.
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If fetching the repository tags fails.
     */
    public function testSetFromTag(): void
    {
        // Test setting tag value.
        // Set to HEAD revision. '' is cast to null when searching for the tag.
        $this->changelog->setFromTag('');
        $this->assertSame('', $this->getPrivateProperty($this->changelog, 'repoHandler')->getOptions('fromTag'));

        // Test removing tag value.
        $this->changelog->setFromTag();
        $this->assertNull($this->getPrivateProperty($this->changelog, 'repoHandler')->getOptions('fromTag'));

        // Test exception.
        $this->expectException(\OutOfBoundsException::class);
        $this->changelog->setFromTag('nonExistingTag');
    }

    /**
     * Test the processing of the commit data.
     *
     * Titles C are merged together.
     * Title E is filtered out.
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
        $expected = [
            'A' => [
                'date'   => 'B',
                'titles' => [0 => 'C', 1 => 'D', 4 => 'F'],
                'hashes' => [0 => ['G', 'I'], 1 => ['H'], 4 => ['K']],
            ],
        ];

        // Start processing.
        $processCommitData = $this->getPrivateMethod($this->changelog, 'processCommitData');
        $processCommitData->invokeArgs($this->changelog, []);

        $this->assertSame($expected, $this->getPrivateProperty($this->changelog, 'commitData'));
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
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If fetching the repository tags fails.
     */
    public function testSetToTag(): void
    {
        // Test setting tag value.
        $this->changelog->setToTag('');
        $this->assertEquals('', $this->getPrivateProperty($this->changelog, 'repoHandler')->getOptions('toTag'));

        // Test removing tag value.
        $this->changelog->setToTag();
        $this->assertNull($this->getPrivateProperty($this->changelog, 'repoHandler')->getOptions('toTag'));

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
     * Test setting options.
     *
     * Tested against:
     * - A single option.
     * - Multiple options.
     *
     * @return void
     * @throws \OutOfBoundsException If the option you're trying to set, doesn't exist.
     * @throws \RangeException If setting option 'headTagName' to an invalid value.
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If fetching the repository tags fails.
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
     * @throws \OutOfBoundsException If the option you're trying to set, doesn't exist.
     * @throws \RangeException If setting option 'headTagName' to an invalid value.
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If fetching the repository tags fails.
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
     * @throws \ReflectionException If reflection of the repoHandler fails.
     */
    public function testSetOptionsThrowsExceptionOnInvalidHeadTagNameValue(): void
    {
        // Set the pre-fetched tags and options to test values.
        $tag         = 'existingTag';
        $repoHandler = $this->getPrivateProperty($this->changelog, 'repoHandler');
        $this->setPrivateProperty($repoHandler, 'gitTags', [$tag]);
        $this->setPrivateProperty($repoHandler, 'options', ['fromTag' => $tag, 'toTag' => $tag]);

        $this->expectException(\RangeException::class);
        $this->changelog->setOptions('headTagName', $tag);
    }

    /**
     * Test if commit data is being fetched from a repository.
     *
     * @return void
     * @throws \ReflectionException If method GitChangelog::getCommitData() doesn't exist.
     */
    public function testGetCommitDataUncached(): void
    {
        // Setup test stubs.
        $commitData = [
            'A' => [
                'date'   => 'B',
                'titles' => [0 => 'C'],
                'hashes' => [0 => 'D'],
            ],
        ];

        $expected = [
            'A' => [
                'date'   => 'B',
                'titles' => [0 => 'C'],
                'hashes' => [0 => ['D']],
            ],
        ];

        $stub = $this->createStub(RepoHandler::class);
        $stub->method('fetchCommitData')->willReturn($commitData);
        $this->setPrivateProperty($this->changelog, 'repoHandler', $stub);

        $commitData = $this->getPrivateMethod($this->changelog, 'getCommitData')->invoke($this->changelog, true);
        $this->assertEquals($expected, $commitData);
    }
}
