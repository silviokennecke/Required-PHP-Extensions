<?php

if (!function_exists('json_decode')) {
	die("\033[0;31mPlease install php-json!\r\n\033[0m");
}

if (!isset($argv) || count($argv) < 1) {
	die("\033[0;31mPlease hand over a requirements json file!\r\n\033[0m");
}

if (!file_exists($argv[1]) || !is_readable($argv[1])) {
	die("\033[0;31mPlease hand over a readable file!\r\n\033[0m");
}

$contents = file_get_contents($argv[1]);
$extensions = json_decode($contents, true);

if (!$extensions) {
	die("\033[0;31mThe requirements json file does not contain valid JSON: " . json_last_error_msg() . "\r\n\033[0m");
}

$loaded_extensions = get_loaded_extensions();
$errors = [];
foreach ($extensions as $extension => $needed_by) {
	if (!in_array($extension, $loaded_extensions)) {
		$errors[$extension] = $needed_by;
	}
}

if (empty($errors)) {
	echo "\033[0;32mAll required extensions are installed correctly!\r\n\033[0m";
} else {
	echo "\033[0;32mThe followind extensions are not (fully) installed or missing:\r\n\033[0m";
	foreach ($errors as $extension => $error) {
		echo $extension . "\r\n";
	}
}
