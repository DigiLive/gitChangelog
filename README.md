# gitChangelog

[![GitHub release](https://img.shields.io/github/v/release/DigiLive/gitChangelog?include_prereleases)](https://github.com/DigiLive/gitChangelog/releases)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](https://opensource.org/licenses/BSD-3-Clause)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/7f0447563661494daac0c4fae4335ac0)](https://www.codacy.com/gh/DigiLive/gitChangelog/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=DigiLive/gitChangelog&amp;utm_campaign=Badge_Grade)

Generate a changelog from git commits of the local repository.

This library parses information which is stored in the Git directory in which it is currently located. With this
information, it generates a changelog which can be saved into a file.

Check out this [example](CHANGELOG.md) which is actually the changelog of this repository.

If you have any questions, comments or ideas concerning this library, Please consult
the [Wiki](https://github.com/DigiLive/gitChangelog/wiki) at first. Create a
new [issue](https://github.com/DigiLive/gitChangelog/issues/new) if your concerns remain unanswered.

## Features

### Main

- Choose from different renderers or create your own.
- List Tags and their date.
- List unique commit titles per tag/release.
- List commit hashes per unique title (optional).
- Include commit titles (and hashes) of the HEAD revision (E.g. Unreleased changes).
- Append the content of another file (E.g. An already existing changelog).
- Save the content of the (appended) changelog to file.

### Other

- Set path to generate a changelog another local repository.
- Set a From- and To tag to limit the changelog.
- Filter titles by labels<sup>1</sup>.
- Set a header for the changelog (E.g. a title).
- Set a custom title for the HEAD revision.
- Set a custom date for the HEAD revision (E.g. Next Tag/Release date).
- Set a custom message to indicate there are no commits present.
- In- or exclude merge commits.
- Set an ordering key for sorting tags/releases<sup>2</sup>.
- Set the sort order of tags/releases.
- Set the sort order of titles.

1. A label is considered to be the first word of a commit title.
2. Using an invalid key will result in unlisted changes or when enabled, just the changes of the HEAD revision.

#### Markdown Renderer

- Define a custom format for Tag/Release lines.
- Define a custom format for title lines.
- Define a custom format for singe hashes.
- Define a custom format for hash lines.

#### Html Renderer

- Define a custom format for singe hashes.

## Installation

The preferred method is to install the library with [Composer](http://getcomposer.org).

```sh
> composer require digilive/git-changelog:^1
```

Alternatively you can download the latest release from [Github](https://github.com/DigiLive/gitChangelog/releases).

## Example use

```php
<?php

use DigiLive\GitChangelog\Renderers\MarkDown;

// Instantiate composer's auto loader.
require 'Path/To/vendor/autoload.php';

// Or include the library manually.
// require_once 'Path/To/MarkDown.php';

$markDownLog = new MarkDown();

// Generate and save.
try {
    $markDownLog->build();
    $markDownLog->save('CHANGELOG.md');
} catch (Exception $e) {
    exit($e);
}
```

## Notes

- Some settings can be changed directly by setting a public property.
  **(Setting a value of an invalid type, might result in unexpected results.)**

- Others have to be set by calling a method.

## Commit guidelines

In order to suitable a good changelog, you should follow the following guidelines:

- Commit messages must have a title line and may have body copy. These must be separated by a blank line.

- The title line must not exceed 50 characters.

- The title line should be capitalized and must not end in a period.

- The title line must be written in an imperative mood (Fix, not Fixed / Fixes etc.).

- The body copy must be wrapped at 72 columns.

- The body copy must only contain explanations as to what and why, never how. The latter belongs in documentation and
  implementation.
