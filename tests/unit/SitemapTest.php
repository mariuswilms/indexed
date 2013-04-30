<?php
/**
 * indexed
 *
 * Copyright (c) 2013 David Persson. All rights reserved.
 *
 * Use of this source code is governed by a BSD-style
 * license that can be found in the LICENSE file.
 */

namespace indexed\tests\unit;

use indexed\Sitemap;
use DomDocument;

class SitemapTest extends \PHPUnit_Framework_TestCase {

	public $subject;

	protected $_online;

	public function setUp() {
		$this->subject = new Sitemap('http://example.org');
		$this->_online = (boolean) @fsockopen('google.com', 80);
	}

	public function testSiteindexXml() {
		if (!$this->_online) {
			$this->markTestSkipped('Not connected to the internet.');
		}

		$this->subject->add('/site-a/map.xml', array(
			'title' => 'a map'
		));
		$this->subject->add('/site-b/map.xml', array(
			'title' => 'b map'
		));

		$Document = new DomDocument();
		$Document->loadXml($this->subject->generate('indexXml'));
		$result = $Document->schemaValidate('http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd');

		$this->assertTrue($result);
	}

	public function testSitemapXml() {
		if (!$this->_online) {
			$this->markTestSkipped('Not connected to the internet.');
		}

		$this->subject->add('/posts-abcdef', array(
			'title' => 'post index'
		));
		$this->subject->add('/posts-abcdef/add', array(
			'title' => 'post add',
			'modified' => 'monthly',
			'priority' => 0.4,
			'section' => 'the section'
		));
		$Document = new DomDocument();
		$Document->loadXml($this->subject->generate('xml'));
		$result = $Document->schemaValidate('http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');

		$this->assertTrue($result);
	}

	public function testSitemapTxt() {
		$this->subject->add('/posts-abcdef', array(
			'title' => 'post index'
		));
		$this->subject->add('/posts-abcdef/add', array(
			'title' => 'post add',
			'modified' => 'monthly',
			'priority' => 0.4,
			'section' => 'the section'
		));

		$result = $this->subject->generate('txt');
		$expected = <<<TXT
/posts-abcdef
/posts-abcdef/add

TXT;
		$this->assertEquals($expected, $result);
	}
}

?>