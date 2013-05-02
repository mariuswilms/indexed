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
	 * Maximum number of pages allowed to be contained.
	 */
	const MAX_PAGES = 50000;

	/**
	 * Maximum size in bytes allowed.
	 */
	const MAX_SIZE = 10485760;

	/**
	 * Maximum number of images per page.
	 */
	const MAX_IMAGES_PER_PAGE = 1000;

	/**
	 * Enable/disable debug mode.
	 *
	 * @var boolean
	 */
	public $debug = false;

	/**
	 * Holds page items of the sitemap added via the `Sitemap::page()`.
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
	 * Namespace definitions used when generating XML sitemaps.
	 *
	 * @var array
	 */
	protected static $_namespaces = array(
		'core' => array(
			'prefix' => null,
			'version' => '0.9',
			'uri' => 'http://www.sitemaps.org/schemas/sitemap/{:version}',
			'schema' => 'http://www.sitemaps.org/schemas/sitemap/{:version}/sitemap.xsd'
		),
		'index' => array(
			'prefix' => null,
			'version' => '0.9',
			'uri' => 'http://www.sitemaps.org/schemas/sitemap/{:version}',
			'schema' => 'http://www.sitemaps.org/schemas/sitemap/{:version}/site{:name}.xsd'
		),
		'image' => array(
			'prefix' => 'image',
			'version' => '1.1',
			'uri' => 'http://www.google.com/schemas/sitemap-{:prefix}/{:version}',
			'schema' => 'http://www.google.com/schemas/sitemap-{:prefix}/{:version}/sitemap-{:prefix}.xsd'
		)
	);

	/**
	 * Constructor.
	 *
	 * @param string $base The base to fully qualify URLs.
	 */
	public function __construct($base) {
		$this->_base = $base;

		foreach (static::$_namespaces as $name => &$namespace) {
			foreach (array('uri', 'schema') as $field) {
				$namespace[$field] = strtr($namespace[$field], array(
					'{:name}' => $name,
					'{:version}' => $namespace['version'],
					'{:prefix}' => $namespace['prefix']
				));
			}
		}
	}

	/**
	 * Adds a page to the sitemap.
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
	public function page($url, $options = array()) {
		$defaults = array(
			'modified' => null,
			'changes' => null,
			'priority' => null,
			'title' => null,
			'images' => array()
		);
		if (strpos($url, '://') === false) {
			$url = $this->_base . $url;
		}
		if (isset($this->_data[$url])) {
			throw new Exception("Will not overwrite page with URL `{$url}`; already added.");
		}
		$this->_data[$url] = compact('url') + $options + $defaults;
	}

	/**
	 * Adds an image to the sitemap.
	 *
	 * @link http://www.google.com/support/webmasters/bin/answer.py?answer=178636
	 * @param string $url An absolute URL for the image.
	 * @param string $page An absolute URL for the page which contains the image.
	 * $param array $options Available options are:
	 *                       - title
	 *                         The title of the image.
	 *                       - license
	 *                         A fully qualified URL to the license of the image.
	 *                       - caption
	 *                         The caption of the image.
	 *                       - location
	 *                         The geographic location of the image (i.e. Limerick, Ireland).
	 */
	public function image($url, $page, array $options = array()) {
		if (strpos($url, '://') === false) {
			$url = $this->_base . $url;
		}
		if (strpos($page, '://') === false) {
			$page = $this->_base . $page;
		}
		$defaults = array(
			'title' => null,
			'license' => null,
			'caption' => null,
			'location' => null
		);
		if (isset($this->_data[$page]['images'][$url])) {
			throw new Exception("Will not overwrite image with URL `{$url}`; already added.");
		}
		$this->_data[$page]['images'][$url] = compact('url') + $options + $defaults;
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
		if (count($this->_data) > static::MAX_PAGES) {
			throw new Exception('Too many pages.');
		}

		$result = $this->{'_generate' . ucfirst($format)}();

		if (strlen($result) > static::MAX_SIZE) {
			throw new Exception('Result document exceeds allowed size.');
		}
		return $result;
	}

	protected function _generateXml() {
		$Document = new DomDocument('1.0', 'UTF-8');

		$namespaces = static::$_namespaces;
		$extensions = $this->_uses($this->_data);

		$Set = $Document->createElementNs($namespaces['core']['uri'], 'urlset');
		$schemaLocation = "{$namespaces['core']['uri']} {$namespaces['core']['schema']}";

		foreach ($extensions as $ext) {
			$Set->setAttribute("xmlns:{$namespaces[$ext]['prefix']}", $namespaces[$ext]['uri']);
			$schemaLocation .= " {$namespaces[$ext]['uri']} {$namespaces[$ext]['schema']}";
		}

		$Set->setAttributeNs(
			'http://www.w3.org/2001/XMLSchema-instance',
			'xsi:schemaLocation',
			$schemaLocation
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
			if ($item['images']) {
				if (count($item['images']) > static::MAX_IMAGES_PER_PAGE) {
					throw new Exception('Too many images for page');
				}

				foreach ($item['images'] as $image) {
					$Image = $Document->createElement('image:image');

					$Image->appendChild($Document->createElement('image:loc', $image['url']));

					if ($image['caption']) {
						$Image->appendChild($Document->createElement('image:caption', $image['caption']));
					}
					if ($image['location']) {
						$Image->appendChild($Document->createElement('image:geo_location', $image['location']));
					}
					if ($image['title']) {
						$Image->appendChild($Document->createElement('image:title', $image['title']));
					}
					if ($image['license']) {
						$Image->appendChild($Document->createElement('image:license', $image['license']));
					}

					$Page->appendChild($Image);
				}
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
		$namespaces = static::$_namespaces;

		$Set = $Document->createElementNs(
			$namespaces['index']['uri'], 'sitemapindex'
		);
		$Set->setAttributeNs(
			'http://www.w3.org/2001/XMLSchema-instance',
			'xsi:schemaLocation',
			"{$namespaces['index']['uri']} {$namespaces['index']['schema']}"
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

	protected function _uses($data) {
		$names = $extensions = array();

		foreach (static::$_namespaces as $name => $namespace) {
			if (!$namespace['prefix']) { // Not an extension.
				continue;
			}
			$names[$name] = $name . 's'; // Poor mans pluralize.
		}
		foreach ($data as $item) {
			foreach ($names as $name => $pluralName) {
				if (!empty($item[$name]) || !empty($item[$pluralName])) {
					$extensions[] = $name;
					unset($names[$name]);
				}
			}
		}
		return $extensions;
	}
}

?>