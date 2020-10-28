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

namespace DigiLive\GitChangelog\Renderers;

use DigiLive\GitChangelog\GitChangelog;
use DigiLive\GitChangelog\Utilities;
use Exception;

/**
 * Class MarkDown
 *
 * Renderer for GitChangelog.
 * The rendered changelog is formatted in markdown.
 *
 * @package DigiLive\GitChangelog\Renderers
 */
class MarkDown extends GitChangelog implements RendererInterface
{
    /**
     * @var string Format of tag strings. {tag} is replaced by the tags found in the git log, {date} is replaced by the
     *             corresponding tag date.
     */
    public $formatTag = '## {tag} ({date})';
    /**
     * @var string Format of hashes. {hashes} is replaced by the concatenated commit hashes.
     */
    public $formatHashes = " ({hashes})";
    /**
     * @var string Format of subjects. {subject} is replaced by commit subjects, {hashes} is replaced by the formatted
     *             commit hashes.
     */
    public $formatSubject = '* {subject}{hashes}';
    /**
     * @var string Format of a single commit hash. {hash} is replaced by the commit hash.
     */
    public $formatHash = '{hash}';

    /**
     * Generate the changelog.
     *
     * The generated changelog will be stored into a class property.
     *
     * @throws Exception When the defined From- or To-tag doesn't exist in the git repository.
     */
    public function build(): void
    {
        $logContent = "# {$this->options['logHeader']}\n";

        $commitData = $this->fetchCommitData();

        if (!$commitData) {
            $this->changelog = "\n$logContent{$this->options['noChangesMessage']}\n";
        }

        if (!$this->options['tagOrderDesc']) {
            $commitData = array_reverse($commitData);
        }

        foreach ($commitData as $tag => $data) {
            $logContent .= "\n";
            // Add tag header and date.
            $tagData = [$tag, $data['date']];
            if ($tag == 'HEAD') {
                $tagData = [$this->options['headSubject'], $this->options['nextTagDate']];
            }

            $logContent .= str_replace(['{tag}', '{date}'], $tagData, $this->formatTag) . "\n\n";

            // No subjects present for this tag.
            if (!$data['subjects']) {
                $subject    = $this->options['noChangesMessage'];
                $logContent .= str_replace(['{subject}', '{hashes}'], [$subject, ''], $this->formatSubject);
                $logContent .= "\n";
                continue;
            }

            // Sort commit subjects.
            Utilities::natSort($data['subjects'], $this->options['commitOrder']);

            // Add commit subjects.
            foreach ($data['subjects'] as $subjectKey => &$subject) {
                $logContent .= str_replace(
                    ['{subject}', '{hashes}'],
                    [$subject, $this->formatHashes($data['hashes'][$subjectKey])],
                    $this->formatSubject
                );
                $logContent .= "\n";
            }
        }

        $this->changelog = $logContent;
    }

    /**
     * Format the hashes of a commit subject into a string.
     *
     * Each hash is formatted as defined by property formatHash.
     * After formatting, all hashes are concatenated to a single line, comma separated.
     * Finally this line is formatted as defined by property formatHashes.
     *
     * @param   array  $hashes  Hashes to format
     *
     * @return string Formatted hash string.
     * @see GitChangelog::$formatHash
     * @see GitChangelog::$formatHashes
     */
    protected function formatHashes(array $hashes): string
    {
        if (!$this->options['addHashes']) {
            return '';
        }

        foreach ($hashes as &$hash) {
            $hash = str_replace('{hash}', $hash, $this->formatHash);
        }
        unset($hash);
        $hashes = implode(', ', $hashes);

        return str_replace('{hashes}', $hashes, $this->formatHashes);
    }
}
