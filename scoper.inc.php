<?php

declare( strict_types=1 );

use Symfony\Component\Finder\Finder;

$namespace = 'BuddyBossPlatform';

return [
	// The prefix configuration. If a non null value will be used, a random prefix will be generated.
	'prefix' => $namespace,
	'whitelist' => [
		// Excludes specific namespaces from being prefixed.
		'Composer\\*',
		'Humbug\\PhpScoper\\*', // Namespace for PHP-Scoper.
		'Humbug\\PhpScoper',
		'PHPUnit\\*',
		'PHPUnit\\Framework\TestCase', // A specific class.
		'MyCLabs',
		'MyCLabs\\*',
	],
	'finders' => [],
	'patchers' => [
		function ( $filePath, $prefix, $contents ) {

			// Check the file path and possibly return the original contents.
			if ( strpos( $filePath, 'myclabs/php-enum' ) !== false ) {
				return file_get_contents( $filePath ); // Revert to original contents.
			}
			return $contents; // Return modified contents for other files.
		},
	],
];
