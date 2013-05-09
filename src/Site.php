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

abstract class Site {

	/**
	 * Maximum number of items allowed to be contained.
	 */
	const MAX_ITEMS = 50000;

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
	 * Holds items of the site.
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
	 * Namespace definitions used when generating.
	 *
	 * @var array
	 */
	protected static $_namespaces = array();

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
	 * Generates the sitemap in given format.
	 *
	 * @return string XML
	 */
	abstract public function generate();

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

	/**
	 * Returns a `loc` element with the URL wrapped - if needed - in a CDATA section.
	 *
	 * @param string $url
	 * @param object $document
	 * @param string $namespace Optional namespace.
	 * @return object
	 */
	protected function _safeLocElement($url, $document, $namespace = null) {
		$name = $namespace ? "{$namespace}:loc" : 'loc';

		if (!$this->_needsEscape($url)) {
			$element = $document->createElement($name, $url);
		} else {
			$element = $document->createElement($name);
			$element->appendChild($document->createCDATASection($url));
		}
		return $element;
	}

	/**
	 * Helper function to check if a string (in this case an URL) needs to be
	 * wrapped in a CDATA section.
	 *
	 * @param string $string
	 * @return boolean
	 */
	protected function _needsEscape($string) {
		return strpos($string, '&') !== false && strpos($string, '&amp;') === false;
	}
}

?>