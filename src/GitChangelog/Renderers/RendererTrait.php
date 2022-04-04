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

namespace DigiLive\GitChangelog\GitChangelog\Renderers;

use DigiLive\GitChangelog\GitChangelog\GitChangelogException;

/**
 * Common methods and properties of the changelog renderers.
 */
trait RendererTrait
{
    /**
     * @var ?string[] Urls of the provider's repository.
     *                <pre>
     *                'commit'       Url to the commit view.
     *                               If set, hashes of a commit title are converted into links which refer to the
     *                               corresponding commit.
     *                               {commit} is replaced by the hash id of the commit.
     *                'issue'        Url to the issue tracker.
     *                               If set, issue references in a commit title are converted into links which refer to
     *                               the corresponding issue at the tracker.
     *                               {issue} is replaced by the issue number.
     *                'mergeRequest' Url to the merge-request view (Also known as pull request).
     *                               If set, merge-request references in a commit title are converted into links
     *                               which refer to the corresponding merge-request at the web interface.
     *                               {mergeRequest} is replaced by the merge/pull request id.
     *                </pre>
     */
    private $urls = [
        'commit'       => null,
        'issue'        => null,
        'mergeRequest' => null,
    ];

    /**
     * @var string[] Formats of the tags and commit titles in the changelog.
     *               <pre>
     *               'tag'   Format of tag strings.
     *                       {tag} is replaced by the repository tag, {date} is replaced by the corresponding tag date.
     *               'title' Format of commit titles.
     *                       {title} is replaced by repository commit-title, {hashes} is replaced by the corresponding
     *                       commit hashes.
     *               </pre>
     */
    private $formats = [
        'tag'   => '## {tag} ({date})',
        'title' => '* {title} {hashes}',
    ];

    /**
     * @var string[] Patterns of an issue and merge request in a commit title.
     *               If set, commit titles are searched for the defined patterns and converted into links.
     *               <pre>
     *               Type           Description
     *               'issue'        Pattern of an issue string.
     *               'mergeRequest' Pattern of a merge request string (also known as pull request).
     *               </pre>
     *
     */
    private $patterns = [
        // /a^/ matches nothing and can be any character.
        'issue'        => '/a^/',
        'mergeRequest' => '/a^/',
    ];

    /**
     * Set the format of the tags or commit titles in the changelog.
     * <pre>
     * Type    Description
     * 'tag'   Format of tag strings.
     *         {tag} is replaced by the tags of the git log, {date} is replaced by the corresponding tag date.
     * 'title' Format of commit titles.
     *         {title} is replaced by commit title of the git log, {hashes} is replaced by the corresponding commit
     *         hashes.
     * </pre>
     *
     * @param   string  $type    'tag' or 'title'.
     * @param   string  $format  Format of the tag or title.
     *
     * @see RendererTrait::$formats
     */
    public function setFormat(string $type, string $format): void
    {
        if (!array_key_exists($type, $this->formats)) {
            throw new \OutOfBoundsException('The type you are trying to set, does not exists!');
        }

        $this->formats[$type] = $format;
    }

    /**
     * Set the urls of the webinterface of the provider's repository.
     *
     * If set, hashes and patterns found in a commit title, are converted into reference links.
     *
     * <pre>
     * Type           Description
     * 'commit'       Url to the commit view.
     *                If set, hashes of a commit title are converted into links which refer to the corresponding
     *                commit.
     *                {hash} is replaced by the hash id of the commit.
     * 'issue'        Url to the issue tracker.
     *                If set, issue references in a commit title are converted into links which refer to the
     *                corresponding issue at the web interface.
     *                {issue} is replaced by the issue number.
     * 'mergeRequest' Url to the merge-request view (also known as pull request).
     *                If set, merge-request references in a commit title are converted into links which refer to the
     *                corresponding merge-request at the web interface.
     *                {mergeRequest} is replaced by the merge/pull request id.
     * </pre>
     *
     * @param   string   $type  'commit', 'issue' or 'mergeRequest'.
     * @param   ?string  $url   Url of the webinterface.
     *
     * @see RendererTrait::$urls
     */
    public function setUrl(string $type, ?string $url): void
    {
        if (!array_key_exists($type, $this->urls)) {
            throw new \OutOfBoundsException('The type you are trying to set, does not exists!');
        }

        $this->urls[$type] = $url;
    }

    /**
     * Set the pattern of an issue or merge request.
     *
     * If set, commit titles are searched for the defined patterns and convert these into links.
     * Patterns are expected to be regular expressions without delimiters.
     *
     * NOTE:
     * The expression must contain exactly one capturing group which would capture the id of an issue or
     * merge-request. This captured id is formatted into a link if an url is set for it.
     *
     * <pre>
     * Type           Description
     * 'issue'        Pattern of an issue in a commit title.
     * 'mergeRequest' Pattern of a merge request in a commit title (also known as pull request).
     * </pre>
     *
     * @param   string   $type     'issue' or 'mergeRequest'.
     * @param   ?string  $pattern  Pattern of an issue or merge request.
     *
     * @throws \DigiLive\GitChangelog\GitChangelog\GitChangelogException If the pattern doesn't contain exactly one
     *                                                                   capturing group.
     * @see RendererTrait::$patterns
     */
    public function setPattern(string $type, ?string $pattern): void
    {
        if (!array_key_exists($type, $this->patterns)) {
            throw new \OutOfBoundsException('The type you are trying to set, does not exists!');
        }

        if (null === $pattern) {
            $this->patterns[$type] = '/a^/';

            return;
        }

        // Check for a capturing group  in the pattern.
        if (!preg_match('/^[^()]*\([^)]*\)[^()]*$/', $pattern)) {
            throw new GitChangelogException('The pattern must contain exactly one capturing group');
        }

        $this->patterns[$type] = "/$pattern(?![^\[]*\])/";
    }
}
