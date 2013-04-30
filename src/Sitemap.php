<?php
/**
 * indexed
 *
 * Copyright (c) 2013 David Persson. All rights reserved.
 *
 * Use of this source code is governed by a BSD-style
 * license that can be found in the LICENSE file.
 */

namespace indexed;

use Exception;
use DomDocument;

class Sitemap {

	/**
	 * Maximum number of URLs allowed to be contained.
	 */
	const MAX_URLS = 50000;

	/**
	 * Maximum size in bytes allowed.
	 */
	const MAX_SIZE = 10485760;

	/**
	 * Enable/disable debug mode.
	 *
	 * @var boolean
	 */
	public $debug = false;

	/**
	 * Holds items of the sitemap added via the `Sitemap::add()`.
	 *
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Base (i.e. 'http://example.org') used to fully qualify URLs.
	 *
	 * @var string
	 */
	protected $_base;

	/**
	 * Constructor.
	 *
	 * @param string $base The base to fully qualify URLs.
	 */
	public function __contruct($base) {
		$this->_base = $base;
	}

	/**
	 * Adds an item to the sitemap.
	 *
	 * @param string $url An absolute URL for the item to be added.
	 * @param array $options Additional options for the item:
	 *                       - modified
	 *                       - changes
	 *                         How often does the item change? Possible values
	 *                         are 'always', 'hourly', 'daily', 'weekly', 'monthly',
	 *                        'yearly', 'never'.
	 *                       - priority
	 *                         Possible values are 0.0 - 1.0 (most important).
	 *                         0.5 is considered the default.
	 *                       - title
	 *                         For XML used as a comment.
	 */
	public function add($url, $options = array()) {
		$defaults = array(
			'modified' => null,
			'changes' => null,
			'priority' => null,
			'title' => null
		);
		if (strpos($url, '://') === false) {
			$url = $this->_base . $url;
		}
		$this->_data[] = compact('url') + $options + $defaults;
	}

	/**
	 * Generates the sitemap in given format.
	 *
	 * @param string $format Either 'xml', 'indeXml' or 'txt'.
	 * @return string The sitemap in given format.
	 */
	public function generate($format = 'xml') {
		if (!method_exists($this, '_generate' . ucfirst($format))) {
			throw new Exception('Invalid format given.');
		}
		if (count($this->_data) > static::MAX_URLS) {
			throw new Exception('Too many URLs.');
		}

		$result = $this->{'_generate' . ucfirst($format)}();

		if (strlen($result) > static::MAX_SIZE) {
			throw new Exception('Result document exceeds allowed size.');
		}
		return $result;
	}

	protected function _generateXml() {
		$Document = new DomDocument('1.0', 'UTF-8');
		$Set = $Document->createElementNs(
			'http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset'
		);
		$Set->setAttributeNs(
			'http://www.w3.org/2001/XMLSchema-instance',
			'xsi:schemaLocation',
			'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'
		);

		foreach ($this->_data as $item) {
			$Page = $Document->createElement('url');

			if ($item['title']) {
				$Page->appendChild($Document->createComment($item['title']));
			}
			$Page->appendChild($Document->createElement('loc', $item['url']));

			if ($item['modified']) {
				$Page->appendChild($Document->createElement('lastmod', date('c', strtotime($item['modified']))));
			}
			if ($item['changes']) {
				$Page->appendChild($Document->createElement('changefreq', $item['changes']));
			}
			if ($item['priority']) {
				$Page->appendChild($Document->createElement('priority', $item['priority']));
			}
			$Set->appendChild($Page);
		}
		$Document->appendChild($Set);

		$Document->formatOutput = $this->debug;
		return $Document->saveXml();
	}

	// @link http://www.sitemaps.org/protocol.php#otherformats
	protected function _generateTxt() {
		$result = null;

		foreach ($this->_data as $item) {
			$result .= $item['url'] . "\n";
		}
		return $result;
	}

	protected function _generateIndexXml() {
		$Document = new DomDocument('1.0', 'UTF-8');
		$Set = $Document->createElementNs(
			'http://www.sitemaps.org/schemas/sitemap/0.9', 'sitemapindex'
		);
		$Set->setAttributeNs(
			'http://www.w3.org/2001/XMLSchema-instance',
			'xsi:schemaLocation',
			'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd'
		);

		foreach ($this->_data as $item) {
			$Map = $Document->createElement('sitemap');

			if ($item['title']) {
				$Map->appendChild($Document->createComment($item['title']));
			}
			$Map->appendChild($Document->createElement('loc', $item['url']));

			if ($item['modified']) {
				$Map->appendChild($Document->createElement('lastmod', date('c', strtotime($item['modified']))));
			}
			$Set->appendChild($Map);
		}
		$Document->appendChild($Set);

		$Document->formatOutput = $this->debug;
		return $Document->saveXml();
	}
}

?>