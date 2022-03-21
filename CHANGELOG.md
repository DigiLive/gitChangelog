# Changelog

## Next Release (Soon)

* Add Codacy configuration file ([4e76f4e][61])
* Add GitChangelog Exception class ([a1f1a60][60])
* Add force fetching tags when fetching commit data ([521d2e4][59])
* Add grouping of links to markdown renderer ([19a5d12][58])
* Add npm remark-lint packages ([272d015][57])
* Add reversed indexing of reference links ([ec254da][56])
* Add setting gitPath property to constructor ([60fa232][55])
* Add symfony/process package ([55d59bb][54])
* Add test for fetching duplicate tags ([5377c20][53])
* Fix [#14][52] - Ambiguous argument 'tag^' ([0127ce2][51])
* Fix Codacy issues ([6574d59][50])
* Fix PHPUnit test. ([c6b978e][49])
* Fix PHPUnit tests ([ea7589f][48])
* Fix SRP violation ([00fff5f][47])
* Fix codacy issues ([80fe12b][46])
* Fix grammar of issue templates ([6f365ba][45])
* Fix not clearing links before build ([da3954c][44])
* Fix property visibility ([dc2847c][43])
* Fix setting wrong gitPath ([871f440][42])
* Fix unused imports ([f12ed2e][41])
* Optimize MarkDown::build() ([3b44d93][40])
* Optimize code ([6cc0e1a][39])
* Optimize fetching commit data ([15543cb][38])

## v1.0.1 (2021-06-09)

* Add compatibility with PHP version 8 ([cb04682][37])
* Fix duplicating tags on re-fetching tags ([d30c8cd][36])

## v1.0.0 (2020-12-16)

* No changes.

## v1.0.0-rc.1 (2020-11-30)

* Add PhpUnit tests for class Html and MarkDown ([a4890bc][35])
* Add code coverage tags ([7ce91b8][34])
* Add formatting of issues ids & hashes to hyperlink ([10816fb][33])
* Add issue templates ([5bbf5ef][32], [6d34e1c][31])
* Add setting base content by value or file content. ([93ca694][30])
* Fix [#7][29], Fix [#8][28] ([d4e352e][27])
* Fix PhpUnit tests for GitChangelog ([b62ded6][26])
* Fix docBlock of GitChangelog::$labels ([1fea85e][25])
* Fix filename to PSR4 rules ([27911a9][24])
* Fix html renderer ([c66b572][23])
* Fix markdown renderer ([ab29669][22])
* Optimize Git execution and Fix docBlocks ([fc79a58][21])

## v0.4.0 (2020-10-28)

* Add separate renderers for GitChangelog ([2df97ee][20])

## v0.3.0 (2020-10-26)

* Fix get method ([a9a9804][19])
* Optimize save method ([d0b1a07][18])

## v0.2.0 (2020-10-23)

* Add Option to sort the changelog by tags in ascending/descending order
([5f6473d][17])

* Add PHPUnit tests for GitChangelog::setOptions() ([94b1301][16])

* Add formatting of a single hash ([392db51][15])

* Add git ignoring ([a574e81][14])

* Add options property which replaces individual option properties
([2357497][13])

* Add option to set another git repository ([f8e2449][12])

* Add setting sorting key for fetching tags ([a477f4f][11])

* Add sorting order of commit subjects ([37389dc][10])

* Bump php version ([101b8fa][9])

* Fix PHPUnit tests ([52de68a][8], [d888afd][7])

* Optimize commitData processing ([6dc2bee][6])

* Optimize method GitChangeLog::build() ([31d33af][5])

## v0.1.1 (2020-10-21)

* Add changelog ([da391ec][4])
* Bump php version ([ece339e][3])
* Cut composer.lock ([580233b][2])

## v0.1.0 (2020-10-21)

* Add changelog ([a4336bc][1])
* Add library code ([731f58a][0])

[0]:https://github.com/DigiLive/gitChangelog/commit/731f58a
[1]:https://github.com/DigiLive/gitChangelog/commit/a4336bc
[2]:https://github.com/DigiLive/gitChangelog/commit/580233b
[3]:https://github.com/DigiLive/gitChangelog/commit/ece339e
[4]:https://github.com/DigiLive/gitChangelog/commit/da391ec
[5]:https://github.com/DigiLive/gitChangelog/commit/31d33af
[6]:https://github.com/DigiLive/gitChangelog/commit/6dc2bee
[7]:https://github.com/DigiLive/gitChangelog/commit/d888afd
[8]:https://github.com/DigiLive/gitChangelog/commit/52de68a
[9]:https://github.com/DigiLive/gitChangelog/commit/101b8fa
[10]:https://github.com/DigiLive/gitChangelog/commit/37389dc
[11]:https://github.com/DigiLive/gitChangelog/commit/a477f4f
[12]:https://github.com/DigiLive/gitChangelog/commit/f8e2449
[13]:https://github.com/DigiLive/gitChangelog/commit/2357497
[14]:https://github.com/DigiLive/gitChangelog/commit/a574e81
[15]:https://github.com/DigiLive/gitChangelog/commit/392db51
[16]:https://github.com/DigiLive/gitChangelog/commit/94b1301
[17]:https://github.com/DigiLive/gitChangelog/commit/5f6473d
[18]:https://github.com/DigiLive/gitChangelog/commit/d0b1a07
[19]:https://github.com/DigiLive/gitChangelog/commit/a9a9804
[20]:https://github.com/DigiLive/gitChangelog/commit/2df97ee
[21]:https://github.com/DigiLive/gitChangelog/commit/fc79a58
[22]:https://github.com/DigiLive/gitChangelog/commit/ab29669
[23]:https://github.com/DigiLive/gitChangelog/commit/c66b572
[24]:https://github.com/DigiLive/gitChangelog/commit/27911a9
[25]:https://github.com/DigiLive/gitChangelog/commit/1fea85e
[26]:https://github.com/DigiLive/gitChangelog/commit/b62ded6
[27]:https://github.com/DigiLive/gitChangelog/commit/d4e352e
[28]:https://github.com/DigiLive/gitChangelog/issues/8
[29]:https://github.com/DigiLive/gitChangelog/issues/7
[30]:https://github.com/DigiLive/gitChangelog/commit/93ca694
[31]:https://github.com/DigiLive/gitChangelog/commit/6d34e1c
[32]:https://github.com/DigiLive/gitChangelog/commit/5bbf5ef
[33]:https://github.com/DigiLive/gitChangelog/commit/10816fb
[34]:https://github.com/DigiLive/gitChangelog/commit/7ce91b8
[35]:https://github.com/DigiLive/gitChangelog/commit/a4890bc
[36]:https://github.com/DigiLive/gitChangelog/commit/d30c8cd
[37]:https://github.com/DigiLive/gitChangelog/commit/cb04682
[38]:https://github.com/DigiLive/gitChangelog/commit/15543cb
[39]:https://github.com/DigiLive/gitChangelog/commit/6cc0e1a
[40]:https://github.com/DigiLive/gitChangelog/commit/3b44d93
[41]:https://github.com/DigiLive/gitChangelog/commit/f12ed2e
[42]:https://github.com/DigiLive/gitChangelog/commit/871f440
[43]:https://github.com/DigiLive/gitChangelog/commit/dc2847c
[44]:https://github.com/DigiLive/gitChangelog/commit/da3954c
[45]:https://github.com/DigiLive/gitChangelog/commit/6f365ba
[46]:https://github.com/DigiLive/gitChangelog/commit/80fe12b
[47]:https://github.com/DigiLive/gitChangelog/commit/00fff5f
[48]:https://github.com/DigiLive/gitChangelog/commit/ea7589f
[49]:https://github.com/DigiLive/gitChangelog/commit/c6b978e
[50]:https://github.com/DigiLive/gitChangelog/commit/6574d59
[51]:https://github.com/DigiLive/gitChangelog/commit/0127ce2
[52]:https://github.com/DigiLive/gitChangelog/issues/14
[53]:https://github.com/DigiLive/gitChangelog/commit/5377c20
[54]:https://github.com/DigiLive/gitChangelog/commit/55d59bb
[55]:https://github.com/DigiLive/gitChangelog/commit/60fa232
[56]:https://github.com/DigiLive/gitChangelog/commit/ec254da
[57]:https://github.com/DigiLive/gitChangelog/commit/272d015
[58]:https://github.com/DigiLive/gitChangelog/commit/19a5d12
[59]:https://github.com/DigiLive/gitChangelog/commit/521d2e4
[60]:https://github.com/DigiLive/gitChangelog/commit/a1f1a60
[61]:https://github.com/DigiLive/gitChangelog/commit/4e76f4e
