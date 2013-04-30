<?php
/**
 * indexed
 *
 * Copyright (c) 2013 David Persson. All rights reserved.
 *
 * Use of this source code is governed by a BSD-style
 * license that can be found in the LICENSE file.
 */

spl_autoload_register(function($class) {
	$file = __DIR__ . '/src/' . str_replace('indexed\\', '', $class) . '.php';

	if (file_exists($file)) {
		include $file;
	}
});

?>