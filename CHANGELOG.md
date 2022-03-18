# Changelog

## Next Release (Soon)

* Add force fetching tags when fetching commit data ([521d2e4][0])
* Add grouping of links to markdown renderer ([8dd502e][1])
* Add setting gitPath property to constructor ([60fa232][2])
* Add test for fetching duplicate tags ([5377c20][3])
* Fix [#14][4] - Ambiguous argument 'tag^' ([0127ce2][5])
* Fix setting wrong gitPath ([871f440][6])
* Optimize fetching commit data ([15543cb][7])

## v1.0.1 (2021-06-09)

* Add compatibility with PHP version 8 ([cb04682][8])
* Fix duplicating tags on re-fetching tags ([d30c8cd][9])

## v1.0.0 (2020-12-16)

* No changes.

## v1.0.0-rc.1 (2020-11-30)

* Add PhpUnit tests for class Html and MarkDown ([a4890bc][10])
* Add code coverage tags ([7ce91b8][11])
* Add formatting of issues ids & hashes to hyperlink ([10816fb][12])
* Add issue templates ([5bbf5ef][13], [6d34e1c][14])
* Add setting base content by value or file content. ([93ca694][15])
* Fix [#7][16], Fix [#8][17] ([d4e352e][18])
* Fix PhpUnit tests for GitChangelog ([b62ded6][19])
* Fix docBlock of GitChangelog::$labels ([1fea85e][20])
* Fix filename to PSR4 rules ([27911a9][21])
* Fix html renderer ([c66b572][22])
* Fix markdown renderer ([ab29669][23])
* Optimize Git execution and Fix docBlocks ([fc79a58][24])

## v0.4.0 (2020-10-28)

* Add separate renderers for GitChangelog ([2df97ee][25])

## v0.3.0 (2020-10-26)

* Fix get method ([a9a9804][26])
* Optimize save method ([d0b1a07][27])

## v0.2.0 (2020-10-23)

* Add Option to sort the changelog by tags in ascending/descending order
([5f6473d][28])

* Add PHPUnit tests for GitChangelog::setOptions() ([94b1301][29])

* Add formatting of a single hash ([392db51][30])

* Add git ignoring ([a574e81][31])

* Add options property which replaces individual option properties
([2357497][32])

* Add option to set another git repository ([f8e2449][33])

* Add setting sorting key for fetching tags ([a477f4f][34])

* Add sorting order of commit subjects ([37389dc][35])

* Bump php version ([101b8fa][36])

* Fix PHPUnit tests ([52de68a][37], [d888afd][38])

* Optimize commitData processing ([6dc2bee][39])

* Optimize method GitChangeLog::build() ([31d33af][40])

## v0.1.1 (2020-10-21)

* Add changelog ([da391ec][41])
* Bump php version ([ece339e][42])
* Cut composer.lock ([580233b][43])

## v0.1.0 (2020-10-21)

* Add changelog ([a4336bc][44])
* Add library code ([731f58a][45])

[0]:https://github.com/DigiLive/gitChangelog/commit/521d2e4
[1]:https://github.com/DigiLive/gitChangelog/commit/8dd502e
[2]:https://github.com/DigiLive/gitChangelog/commit/60fa232
[3]:https://github.com/DigiLive/gitChangelog/commit/5377c20
[4]:https://github.com/DigiLive/gitChangelog/issues/14
[5]:https://github.com/DigiLive/gitChangelog/commit/0127ce2
[6]:https://github.com/DigiLive/gitChangelog/commit/871f440
[7]:https://github.com/DigiLive/gitChangelog/commit/15543cb
[8]:https://github.com/DigiLive/gitChangelog/commit/cb04682
[9]:https://github.com/DigiLive/gitChangelog/commit/d30c8cd
[10]:https://github.com/DigiLive/gitChangelog/commit/a4890bc
[11]:https://github.com/DigiLive/gitChangelog/commit/7ce91b8
[12]:https://github.com/DigiLive/gitChangelog/commit/10816fb
[13]:https://github.com/DigiLive/gitChangelog/commit/5bbf5ef
[14]:https://github.com/DigiLive/gitChangelog/commit/6d34e1c
[15]:https://github.com/DigiLive/gitChangelog/commit/93ca694
[16]:https://github.com/DigiLive/gitChangelog/issues/7
[17]:https://github.com/DigiLive/gitChangelog/issues/8
[18]:https://github.com/DigiLive/gitChangelog/commit/d4e352e
[19]:https://github.com/DigiLive/gitChangelog/commit/b62ded6
[20]:https://github.com/DigiLive/gitChangelog/commit/1fea85e
[21]:https://github.com/DigiLive/gitChangelog/commit/27911a9
[22]:https://github.com/DigiLive/gitChangelog/commit/c66b572
[23]:https://github.com/DigiLive/gitChangelog/commit/ab29669
[24]:https://github.com/DigiLive/gitChangelog/commit/fc79a58
[25]:https://github.com/DigiLive/gitChangelog/commit/2df97ee
[26]:https://github.com/DigiLive/gitChangelog/commit/a9a9804
[27]:https://github.com/DigiLive/gitChangelog/commit/d0b1a07
[28]:https://github.com/DigiLive/gitChangelog/commit/5f6473d
[29]:https://github.com/DigiLive/gitChangelog/commit/94b1301
[30]:https://github.com/DigiLive/gitChangelog/commit/392db51
[31]:https://github.com/DigiLive/gitChangelog/commit/a574e81
[32]:https://github.com/DigiLive/gitChangelog/commit/2357497
[33]:https://github.com/DigiLive/gitChangelog/commit/f8e2449
[34]:https://github.com/DigiLive/gitChangelog/commit/a477f4f
[35]:https://github.com/DigiLive/gitChangelog/commit/37389dc
[36]:https://github.com/DigiLive/gitChangelog/commit/101b8fa
[37]:https://github.com/DigiLive/gitChangelog/commit/52de68a
[38]:https://github.com/DigiLive/gitChangelog/commit/d888afd
[39]:https://github.com/DigiLive/gitChangelog/commit/6dc2bee
[40]:https://github.com/DigiLive/gitChangelog/commit/31d33af
[41]:https://github.com/DigiLive/gitChangelog/commit/da391ec
[42]:https://github.com/DigiLive/gitChangelog/commit/ece339e
[43]:https://github.com/DigiLive/gitChangelog/commit/580233b
[44]:https://github.com/DigiLive/gitChangelog/commit/a4336bc
[45]:https://github.com/DigiLive/gitChangelog/commit/731f58a
