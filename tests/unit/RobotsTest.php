<?php
/**
 * indexed
 *
 * Copyright (c) 2013-2014 David Persson. All rights reserved.
 *
 * Use of this source code is governed by a BSD-style
 * license that can be found in the LICENSE file.
 */

namespace indexed\tests\unit;

use indexed\Robots;

class RobotsTest extends \PHPUnit_Framework_TestCase {

	public $subject;

	protected $_online;

	public function setUp() {
		$this->subject = new Robots();
		$this->_online = (boolean) @fsockopen('google.com', 80);
	}

	public function testAllowThrowsExceptioOnAbsoluteUrls() {
		$this->setExpectedException('Exception');
		$this->subject->allow('http://posts/add');
	}

	public function testDisallowThrowsExceptioOnAbsoluteUrls() {
		$this->setExpectedException('Exception');
		$this->subject->disallow('http://posts/add');
	}

	public function testSitemapThrowsExceptioOnRelativeUrls() {
		$this->setExpectedException('Exception');
		$this->subject->sitemap('/posts/add');
	}

	public function testWildcards() {
		$this->subject->disallow('/*q=');
		$this->subject->disallow('/*.atom');
		$this->subject->disallow('/*/raw/');

		$result = $this->subject->generate();
		$expected = <<<TXT
User-agent: *
Disallow: /*q=
Disallow: /*.atom
Disallow: /*/raw/

TXT;
		$this->assertEquals($expected, $result);
	}

	public function testAllowDisallowOrder() {
		$this->subject->allow('/css/');
		$this->subject->disallow('/secret/');
		$this->subject->allow('/img/');

		$result = $this->subject->generate();
		$expected = <<<TXT
User-agent: *
Allow: /css/
Allow: /img/
Disallow: /secret/

TXT;
		$this->assertEquals($expected, $result);
	}

	public function testSitemap() {
		$this->subject->sitemap('http://example.org/sitemap.xml');

		$result = $this->subject->generate();
		$expectedUrl = 'http://example.org/sitemap.xml';
		$expected = <<<TXT
User-agent: *
Sitemap: {$expectedUrl}

TXT;
		$this->assertEquals($expected, $result);
	}

	public function testAgentBlocksSimple() {
		$this->subject->allow('/test0/');
		$this->subject->allow('/test1/', 'agent0');
		$this->subject->allow('/test2/');

		$result = $this->subject->generate();
		$expected = <<<TXT
User-agent: agent0
Allow: /test1/

User-agent: *
Allow: /test0/
Allow: /test2/

TXT;
		$this->assertEquals($expected, $result);
	}

	public function testAgentBlocksComplex() {

		$this->subject->allow('/test0/', 'agent0');
		$this->subject->allow('/test1/', 'agent1');
		$this->subject->allow('/test2/', 'agent2');
		$this->subject->allow('/test3/');
		$this->subject->allow('/test4/', 'agent2');

		$result = $this->subject->generate();
		$expected = <<<TXT
User-agent: agent2
Allow: /test2/
Allow: /test4/

User-agent: agent1
Allow: /test1/

User-agent: agent0
Allow: /test0/

User-agent: *
Allow: /test3/

TXT;
		$this->assertEquals($expected, $result);
	}

	public function testCrawlDelay() {
		$this->subject->crawlDelay(30);

		$result = $this->subject->generate();
		$expected = <<<TXT
User-agent: *
Crawl-delay: 30

TXT;
		$this->assertEquals($expected, $result);
	}

	public function testVisitTime() {
		$this->subject->visitTime('13:00', '20:00');

		$result = $this->subject->generate();
		$expected = <<<TXT
User-agent: *
Visit-time: 13:00 - 20:00

TXT;
		$this->assertEquals($expected, $result);
	}

	public function testRequestRate() {
		$this->subject->requestRate(20, '13:00', '20:00', 'agent0');
		$this->subject->requestRate(20, '13:00', null, 'agent1');
		$this->subject->requestRate(20, null, null, 'agent2');

		$result = $this->subject->generate();
		$expected = <<<TXT
User-agent: agent2
Request-rate: 20/60

User-agent: agent1
Request-rate: 20/60

User-agent: agent0
Request-rate: 20/60 13:00 - 20:00

TXT;
		$this->assertEquals($expected, $result);
	}
}

?>