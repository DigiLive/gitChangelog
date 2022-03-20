# Changelog

## v1.0.2 (2022-02-20)

* Add Codacy configuration file ([4e76f4e][0])
* Add force fetching tags when fetching commit data ([521d2e4][1])
* Add grouping of links to markdown renderer ([19a5d12][2])
* Add npm remark-lint packages ([272d015][3])
* Add setting gitPath property to constructor ([60fa232][4])
* Add test for fetching duplicate tags ([5377c20][5])
* Fix [#14][6] - Ambiguous argument 'tag^' ([0127ce2][7])
* Fix PHPUnit tests ([ea7589f][8])
* Fix grammar of issue templates ([6f365ba][9])
* Fix not clearing links before build ([da3954c][10])
* Fix setting wrong gitPath ([871f440][11])
* Optimize code ([6cc0e1a][12])
* Optimize fetching commit data ([15543cb][13])

## v1.0.1 (2021-06-09)

* Add compatibility with PHP version 8 ([cb04682][14])
* Fix duplicating tags on re-fetching tags ([d30c8cd][15])

## v1.0.0 (2020-12-16)

* No changes.

## v1.0.0-rc.1 (2020-11-30)

* Add PhpUnit tests for class Html and MarkDown ([a4890bc][16])
* Add code coverage tags ([7ce91b8][17])
* Add formatting of issues ids & hashes to hyperlink ([10816fb][18])
* Add issue templates ([5bbf5ef][19], [6d34e1c][20])
* Add setting base content by value or file content. ([93ca694][21])
* Fix [#7][22], Fix [#8][23] ([d4e352e][24])
* Fix PhpUnit tests for GitChangelog ([b62ded6][25])
* Fix docBlock of GitChangelog::$labels ([1fea85e][26])
* Fix filename to PSR4 rules ([27911a9][27])
* Fix html renderer ([c66b572][28])
* Fix markdown renderer ([ab29669][29])
* Optimize Git execution and Fix docBlocks ([fc79a58][30])

## v0.4.0 (2020-10-28)

* Add separate renderers for GitChangelog ([2df97ee][31])

## v0.3.0 (2020-10-26)

* Fix get method ([a9a9804][32])
* Optimize save method ([d0b1a07][33])

## v0.2.0 (2020-10-23)

* Add Option to sort the changelog by tags in ascending/descending order
([5f6473d][34])

* Add PHPUnit tests for GitChangelog::setOptions() ([94b1301][35])

* Add formatting of a single hash ([392db51][36])

* Add git ignoring ([a574e81][37])

* Add options property which replaces individual option properties
([2357497][38])

* Add option to set another git repository ([f8e2449][39])

* Add setting sorting key for fetching tags ([a477f4f][40])

* Add sorting order of commit subjects ([37389dc][41])

* Bump php version ([101b8fa][42])

* Fix PHPUnit tests ([52de68a][43], [d888afd][44])

* Optimize commitData processing ([6dc2bee][45])

* Optimize method GitChangeLog::build() ([31d33af][46])

## v0.1.1 (2020-10-21)

* Add changelog ([da391ec][47])
* Bump php version ([ece339e][48])
* Cut composer.lock ([580233b][49])

## v0.1.0 (2020-10-21)

* Add changelog ([a4336bc][50])
* Add library code ([731f58a][51])

[0]:https://github.com/DigiLive/gitChangelog/commit/4e76f4e
[1]:https://github.com/DigiLive/gitChangelog/commit/521d2e4
[2]:https://github.com/DigiLive/gitChangelog/commit/19a5d12
[3]:https://github.com/DigiLive/gitChangelog/commit/272d015
[4]:https://github.com/DigiLive/gitChangelog/commit/60fa232
[5]:https://github.com/DigiLive/gitChangelog/commit/5377c20
[6]:https://github.com/DigiLive/gitChangelog/issues/14
[7]:https://github.com/DigiLive/gitChangelog/commit/0127ce2
[8]:https://github.com/DigiLive/gitChangelog/commit/ea7589f
[9]:https://github.com/DigiLive/gitChangelog/commit/6f365ba
[10]:https://github.com/DigiLive/gitChangelog/commit/da3954c
[11]:https://github.com/DigiLive/gitChangelog/commit/871f440
[12]:https://github.com/DigiLive/gitChangelog/commit/6cc0e1a
[13]:https://github.com/DigiLive/gitChangelog/commit/15543cb
[14]:https://github.com/DigiLive/gitChangelog/commit/cb04682
[15]:https://github.com/DigiLive/gitChangelog/commit/d30c8cd
[16]:https://github.com/DigiLive/gitChangelog/commit/a4890bc
[17]:https://github.com/DigiLive/gitChangelog/commit/7ce91b8
[18]:https://github.com/DigiLive/gitChangelog/commit/10816fb
[19]:https://github.com/DigiLive/gitChangelog/commit/5bbf5ef
[20]:https://github.com/DigiLive/gitChangelog/commit/6d34e1c
[21]:https://github.com/DigiLive/gitChangelog/commit/93ca694
[22]:https://github.com/DigiLive/gitChangelog/issues/7
[23]:https://github.com/DigiLive/gitChangelog/issues/8
[24]:https://github.com/DigiLive/gitChangelog/commit/d4e352e
[25]:https://github.com/DigiLive/gitChangelog/commit/b62ded6
[26]:https://github.com/DigiLive/gitChangelog/commit/1fea85e
[27]:https://github.com/DigiLive/gitChangelog/commit/27911a9
[28]:https://github.com/DigiLive/gitChangelog/commit/c66b572
[29]:https://github.com/DigiLive/gitChangelog/commit/ab29669
[30]:https://github.com/DigiLive/gitChangelog/commit/fc79a58
[31]:https://github.com/DigiLive/gitChangelog/commit/2df97ee
[32]:https://github.com/DigiLive/gitChangelog/commit/a9a9804
[33]:https://github.com/DigiLive/gitChangelog/commit/d0b1a07
[34]:https://github.com/DigiLive/gitChangelog/commit/5f6473d
[35]:https://github.com/DigiLive/gitChangelog/commit/94b1301
[36]:https://github.com/DigiLive/gitChangelog/commit/392db51
[37]:https://github.com/DigiLive/gitChangelog/commit/a574e81
[38]:https://github.com/DigiLive/gitChangelog/commit/2357497
[39]:https://github.com/DigiLive/gitChangelog/commit/f8e2449
[40]:https://github.com/DigiLive/gitChangelog/commit/a477f4f
[41]:https://github.com/DigiLive/gitChangelog/commit/37389dc
[42]:https://github.com/DigiLive/gitChangelog/commit/101b8fa
[43]:https://github.com/DigiLive/gitChangelog/commit/52de68a
[44]:https://github.com/DigiLive/gitChangelog/commit/d888afd
[45]:https://github.com/DigiLive/gitChangelog/commit/6dc2bee
[46]:https://github.com/DigiLive/gitChangelog/commit/31d33af
[47]:https://github.com/DigiLive/gitChangelog/commit/da391ec
[48]:https://github.com/DigiLive/gitChangelog/commit/ece339e
[49]:https://github.com/DigiLive/gitChangelog/commit/580233b
[50]:https://github.com/DigiLive/gitChangelog/commit/a4336bc
[51]:https://github.com/DigiLive/gitChangelog/commit/731f58a
