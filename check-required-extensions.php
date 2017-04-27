<?php

function getDirContents($dir, &$results = array()) {
	$files = scandir($dir);

	foreach ($files as $key => $value) {
		$path = realpath($dir . DIRECTORY_SEPARATOR . $value);
		if (!is_dir($path)) {
			$results[] = $path;
		} else if ($value != "." && $value != "..") {
			getDirContents($path, $results);
			$results[] = $path;
		}
	}

	return $results;
}

$files = getDirContents(__DIR__);
foreach ($files as $key => $file) {
	if (is_dir($file)) {
		unset($files[$key]);
	}
	
	if (substr($file, -4) != '.php') {
		unset($files[$key]);
	}
}

// begin sanning

// remove everything else
$functions = ['methods' => [], 'functions' => []];
foreach ($files as $file) {
	$content = file_get_contents($file);
	$content = preg_replace('/<\?=[\w\W]*\?>/imU', '', $content);
	$content = preg_replace('/\?>[\w\W]*<\?(=|php)/imU', '', $content);
	if (strpos($content, '<?php')) {
		$content = substr($content, 0, strpos($content, '<?php'));
	}
	if (strpos($content, '<?=')) {
		$content = substr($content, 0, strpos($content, '<?='));
	}
	if (strpos($content, '?>')) {
		$content = substr($content, 0, strpos($content, '?>') * (-1));
	}
	
	if (preg_match_all('/(?<is_method>(::|->)?)\W*(?<function>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\W*\(/m', $content, $matches, PREG_SET_ORDER)) {
		foreach($matches as $match) {
			if (empty($match['is_method'])) {
				if (!key_exists($match['function'], $functions['functions'])) {
					$functions['functions'][$match['function']] = [];
				}
				if (!in_array($file, $functions['functions'][$match['function']])) {
					$functions['functions'][$match['function']][] = $file;
				}
			} else {
				if (!key_exists($match['function'], $functions['methods'])) {
					$functions['methods'][$match['function']] = [];
				}
				if (!in_array($file, $functions['methods'][$match['function']])) {
					$functions['methods'][$match['function']][] = $file;
				}
			}
		}
	}
}

// get extensions and functions
$extensions = get_loaded_extensions();
foreach ($extensions as $key => $name) {
	$extensions[$name] = get_extension_funcs($name);
	if (!$extensions[$name]) {
		unset($extensions[$name]);
	}
	
	unset($extensions[$key]);
}

// compare to the loaded extensions
$required = [];
foreach ($functions['functions'] as $function => $files) {
	foreach($extensions as $name => $funcs) {
		if (in_array($function, $funcs)) {
			if (!array_key_exists($name, $required)) {
				$required[$name] = [];
			}
			
			$required[$name][$function] = $files;
		}
	}
}
foreach ($functions['methods'] as $function => $files) {
	foreach($extensions as $name => $funcs) {
		if (in_array($function, $funcs)) {
			if (!array_key_exists($name, $required)) {
				$required[$name] = [];
			}
			
			$required[$name][$function] = $files;
		}
	}
}

echo json_encode($required, JSON_PRETTY_PRINT);

