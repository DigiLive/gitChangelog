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

namespace DigiLive\GitChangelog\Tests\RepoHandler;

use DigiLive\GitChangelog\RepoHandler\RepoHandler;
use DigiLive\GitChangelog\RepoHandler\RepoHandlerException;
use DigiLive\GitChangelog\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests of class RepoHandler.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RepoHandlerTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @var \DigiLive\GitChangelog\RepoHandler\RepoHandler The object that will be tested against.
     */
    private $repoHandler;

    public function setUp(): void
    {
        $this->repoHandler = new RepoHandler();
    }

    /**
     * Test setting options.
     *
     * Tested against:
     * - A single option.
     * - Multiple options.
     *
     * @return void
     * @throws \ReflectionException If property RepoHandler::$options doesn't exist
     */
    public function testSetOptions()
    {
        // Set single option.
        $this->repoHandler->setOptions('fromTag', 'Test');
        $this->assertEquals('Test', $this->getPrivateProperty($this->repoHandler, 'options')['fromTag']);

        // Set multiple options at once.
        $this->repoHandler->setOptions(
            [
                'fromTag' => 'Test1',
                'toTag'   => 'Test2',
            ]
        );
        $this->assertEquals('Test1', $this->getPrivateProperty($this->repoHandler, 'options')['fromTag']);
        $this->assertEquals('Test2', $this->getPrivateProperty($this->repoHandler, 'options')['toTag']);
    }

    /**
     * Test if setting a non-existing option fails.
     *
     * @return void
     */
    public function testSetOptionsThrowsExceptionOnInvalidOption(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->repoHandler->setOptions('NonExistingOption', 'Test');
    }

    /**
     * Test re-fetching the tags from the repository.
     *
     * @return void
     * @throws \ReflectionException If property RepoHandler::$gitTags doesn't exist.
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If fetching the tags fails.
     */
    public function testFetchTags(): void
    {
        // Test fetching cached tags.
        $dummyData = [null, 'dummyTag'];
        $this->setPrivateProperty($this->repoHandler, 'gitTags', $dummyData);
        $this->assertSame($dummyData, $this->repoHandler->fetchTags(false));

        // Test fetching uncached tags.
        $dummyData = ['dummyTag'];
        $this->setPrivateProperty($this->repoHandler, 'gitTags', $dummyData);
        $this->assertNotSame($dummyData, $this->repoHandler->fetchTags(true));
    }

    /**
     * Test if fetching tags from the repository fails when the from-tag or to-tag doesn't exist.
     *
     * @return void
     * @throws \ReflectionException If property RepoHandler::$fromTag doesn't exist.
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If fetching the tags fails.
     */
    public function testFetchTagsThrowsExceptionOnInvalidFromTag(): void
    {
        foreach (['fromTag', 'toTag'] as $option) {
            // Set option to non-existing repository tag.
            $this->repoHandler->setOptions($option, 'nonExistingTag');
            $this->setPrivateProperty($this->repoHandler, 'gitTags', ['dummyTags']);
            $this->expectException(RepoHandlerException::class);
            $this->repoHandler->fetchTags(true);
        }
    }

    /**
     * Test if property RepoHandler::$commitData is properly constructed.
     *
     * @return void
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If addressing the repository fails.
     * @throws \ReflectionException If property RepoHandler::$commitData doesn't exist.
     */
    public function testFetchCommitData(): void
    {
        // Test cached data.
        $dummyData = ['Dummy'];
        $this->setPrivateProperty($this->repoHandler, 'commitData', $dummyData);
        $this->assertSame($dummyData, $this->repoHandler->fetchCommitData(false));


        // Test the format of the fetched commit data.
        $commitData = $this->repoHandler->fetchCommitData(true);

        $firstElement = reset($commitData);
        $this->assertArrayHasKey('date', $firstElement);
        $this->assertArrayHasKey('titles', $firstElement);
        $this->assertArrayHasKey('hashes', $firstElement);
        $this->assertIsArray($firstElement['titles']);
        $this->assertIsArray($firstElement['hashes']);
    }
}
