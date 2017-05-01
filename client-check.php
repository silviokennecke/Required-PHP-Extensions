<?php

if (!function_exists('json_decode')) {
	die('Please install php-json!'."\r\n");
}

if (!isset($argv) || count($argv) < 1) {
	die('Please hand over a requirements json file!'."\r\n");
}

if (!file_exists($argv[1]) || !is_readable($argv[1])) {
	die('Please hand over a readable file!'."\r\n");
}

$contents = file_get_contents($argv[1]);
$extensions = json_decode($contents, true);

if (!$extensions) {
	die('The requirements json file does not contain valid JSON: ' . json_last_error_msg() . "\r\n");
}

$loaded_extensions = get_loaded_extensions();
$errors = [];
foreach ($extensions as $extension => $needed_by) {
	if (!in_array($extension, $loaded_extensions)) {
		$errors[$extension] = $needed_by;
	}
}

if (empty($errors)) {
	echo 'All required extensions are installed correctly!'."\r\n";
} else {
	echo 'The followind extensions are not (fully) installed or missing:'."\r\n";
	foreach ($errors as $extension => $error) {
		echo $extension . "\r\n";
	}
}
