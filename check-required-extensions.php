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
$functions = ['functions' => [], 'classes' => []];
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
	
	if (preg_match_all('/(?<is_method>(([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)::|->)?)\W*(?<function>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\W*\(/m', $content, $matches, PREG_SET_ORDER)) {
		foreach($matches as $match) {
			if (empty($match['is_method'])) {
				if (!key_exists($match['function'], $functions['functions'])) {
					$functions['functions'][$match['function']] = [];
				}
				if (!in_array($file, $functions['functions'][$match['function']])) {
					$functions['functions'][$match['function']][] = $file;
				}
			} else if ($match['is_method'] != '->') {
				$class_name = substr($match['is_method'], 0, -2);
				
				if (!key_exists($class_name, $functions['classes'])) {
					$functions['classes'][$class_name] = [];
				}
				if (!in_array($file, $functions['classes'][$class_name])) {
					$functions['classes'][$class_name][] = $file;
				}
			}
		}
	}
	if (preg_match_all('/(new|extends)\W+\\?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\)*(?<class>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/m', $content, $matches, PREG_SET_ORDER)) {
		foreach($matches as $match) {
			if (!key_exists($match['class'], $functions['classes'])) {
				$functions['classes'][$match['class']] = [];
			}
			if (!in_array($file, $functions['classes'][$match['class']])) {
				$functions['classes'][$match['class']][] = $file;
			}
		}
	}
}

// get extensions and functions
function getExtensionVars ($name) {
	$ext = new ReflectionExtension($name);
	
	$extensions = [];
	
	$extensions['functions'] = $ext->getFunctions();
	if (!$extensions['functions']) {
		$extensions['functions'] = [];
	} else {
		$extensions['functions'] = array_keys($extensions['functions']);
	}
	
	$extensions['classes'] = $ext->getClassNames();
	if (!$extensions['classes']) {
		$extensions['classes'] = [];
	} else {
		$extensions['classes'] = array_keys($extensions['classes']);
	}
	
	$extensions['constants'] = $ext->getClassNames();
	if (!$extensions['constants']) {
		$extensions['constants'] = [];
	} else {
		$extensions['constants'] = array_keys($extensions['constants']);
	}
	
	return $extensions;
}

$extensions_list = get_loaded_extensions();
$extensions = [];
foreach ($extensions_list as $key => $name) {
	$ext = new ReflectionExtension($name);
	
	if (!key_exists($name, $extensions)) {
		$extensions[$name] = getExtensionVars($name);
	}
	
	unset($extensions[$key]);
}

// compare to the loaded extensions
$required = [];
foreach ($functions['functions'] as $function => $files) {
	foreach($extensions as $name => $funcs) {
		if (in_array($function, $funcs['functions'])) {
			if (!array_key_exists($name, $required)) {
				$required[$name] = ['functions' => [], 'classes' => []];
			}
			
			$required[$name]['functions'][$function] = $files;
		}
	}
}
foreach ($functions['classes'] as $function => $files) {
	foreach($extensions as $name => $funcs['classes']) {
		if (in_array($function, $funcs['classes'])) {
			if (!array_key_exists($name, $required)) {
				$required[$name] = ['functions' => [], 'classes' => []];
			}
			
			$required[$name]['classes'][$function] = $files;
		}
	}
}

echo json_encode($required, JSON_PRETTY_PRINT);

