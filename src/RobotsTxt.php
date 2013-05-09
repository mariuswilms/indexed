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

/**
 * The RobotsTxt class allows to generate output according
 * to Robots Exclusion Standard.
 *
 * @link http://www.robotstxt.org/orig.html
 */
class RobotsTxt {

	protected static $_directivesOrder = array(
		'User-agent',    // 1.0
		'Allow',         // 2.0
		'Disallow',      // 1.0
		'Sitemap',       // nonstandard
		'Crawl-delay',   // nonstandard
		'Visit-time',    // 2.0
		'Request-rate',  // 2.0
		'Comment'        // 2.0
	);

	protected $_data = array();

	/**
	 * Allow access to a URL.
	 *
	 * @param string $url A relative URL.
	 * @paran string $agent Optionally an identifier of agent; defaults to any.
	 */
	public function allow($url, $agent = '*') {
		if (strpos($url, '://') !== false) {
			throw new Exception('Allow-URL must be relative.');
		}
		$this->_data[$agent]['Allow'][] = $url;
	}

	/**
	 * Disallow access to a URL.
	 *
	 * @param string $url A relative URL.
	 * @paran string $agent Optionally an identifier of agent; defaults to any.
	 */
	public function disallow($url, $agent = '*') {
		if (strpos($url, '://') !== false) {
			throw new Exception('Disallow-URL must be relative.');
		}
		$this->_data[$agent]['Disallow'][] = $url;
	}

	/**
	 * Hint where a sitemap is located.
	 *
	 * @param string $url A fully qualified URL.
	 * @paran string $agent Optionally an identifier of agent; defaults to any.
	 */
	public function sitemap($url, $agent = '*') {
		if (strpos($url, '://') === false) {
			throw new Exception('Sitemap-URL must be fully qualified.');
		}
		$this->_data[$agent]['Sitemap'][] = $url;
	}

	/**
	 * Number of seconds to wait between successive requests to the same server.
	 * This is a nonstandard extension to the Robots Exclusion Standard.
	 *
	 * @param integer $seconds Delay in seconds.
	 * @paran string $agent Optionally an identifier of agent; defaults to any.
	 */
	public function crawlDelay($seconds, $agent = '*') {
		$this->_data[$agent]['Crawl-delay'] = $seconds;
	}

	/**
	 * Time when the site should be visited. This directive is part of the
	 * Robots Exclusion Standard 2.0.
	 *
	 * @param string $from The starting time of the visit; UTC.
	 * @param string $until The time when the visit should end; UTC.
	 * @param string $agent Optionally an identifier of agent; defaults to any.
	 */
	public function visitTime($from, $until, $agent = '*') {
		$from = date('H:i', strtotime($from));
		$until = date('H:i', strtotime($until));

		$this->_data[$agent]['Visit-time'][] = "{$from} - {$until}";
	}

	/**
	 * Suggest a request rate and restricts request for documents per minute.
	 * This directive is part of the Robots Exclusion Standard 2.0.
	 *
	 * @param integer $documents Number of documents per minute.
	 * @param string $from Optionally the starting time this should take effect; UTC.
	 * @param string $until Optionally the ending time when this should take effect; UTC.
	 * @param string $agent Optionally an identifier of agent; defaults to any.
	 */
	public function requestRate($documents, $from = null, $until = null, $agent = '*') {
		$data = "{$documents}/60";

		if ($from && $until) {
			$from = date('H:i', strtotime($from));
			$until = date('H:i', strtotime($until));
			$data .= " {$from} - {$until}";
		}
		$this->_data[$agent]['Request-rate'][] = $data;
	}

	/**
	 * Adds a comment to a block.
	 *
	 * @param string $tety The comment's text.
	 * @param string $agent Optionally an identifier of agent; defaults to any.
	 */
	public function comment($text, $agent = '*') {
		$this->_data[$agent]['Commet'][] = $text;
	}

	/**
	 * Generates the contents of a possible robots.txt file.
	 *
	 * @return string The generated contents.
	 */
	public function generate() {
		$output = null;

		$data = $this->_sort($this->_data);

		foreach ($data as $agent => $ruleSet) {
			if ($output) {
				$output .= "\n";
			}
			$output .= "User-agent: {$agent}\n";

			foreach ($ruleSet as $directive => $rule) {
				foreach ((array) $rule as $value) {
					$output .= "{$directive}: {$value}\n";
				}
			}
		}
		return $output;
	}

	protected function _sort($data) {
		$sorted = array();

		foreach ($data as $agent => $ruleSet) {
			foreach (static::$_directivesOrder as $directive) {
				if (isset($data[$agent][$directive])) {
					$sorted[$agent][$directive] = $data[$agent][$directive];
				}
			}
		}
		krsort($sorted);
		return $sorted;
	}

	/**
	 * Deny access to a URL.
	 *
	 * @param string $url A relative URL.
	 * @paran string $agent Optionally an identifier of agent; defaults to any.
	 * @deprecated
	 */
	public function deny($url, $agent = '*') {
		trigger_error('RobotsTxt::deny() is deprecated, use disallow instead.', E_USER_DEPRECATED);
		return $this->disallow($url, $agent);
	}
}

?>