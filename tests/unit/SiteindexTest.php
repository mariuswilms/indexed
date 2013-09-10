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

use indexed\Siteindex;
use DomDocument;

class SiteindexTest extends \PHPUnit_Framework_TestCase {

	public $subject;

	protected $_online;

	public function setUp() {
		$this->subject = new Siteindex('http://example.org');
		$this->subject->debug = true;
		$this->_online = (boolean) @fsockopen('google.com', 80);
	}

	public function testGenerateBasic() {
		if (!$this->_online) {
			$this->markTestSkipped('Not connected to the internet.');
		}

		$this->subject->sitemap('/site-a/map.xml', [
			'title' => 'a map'
		]);
		$this->subject->sitemap('/site-b/map.xml', [
			'title' => 'b map'
		]);

		$Document = new DomDocument();
		$Document->loadXml($this->subject->generate());
		$result = $Document->schemaValidate('http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd');

		$this->assertTrue($result);
	}
}

?>