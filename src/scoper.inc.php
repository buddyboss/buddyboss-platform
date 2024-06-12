<?php
/**
 * Scoper configuration file.
 *
 * @since BuddyBoss 2.6.30
 */

declare( strict_types=1 );

use Symfony\Component\Finder\Finder;

$namespace = 'BuddyBossPlatform';

return array(

	// The prefix configuration. If a non null value will be used, a random prefix will be generated.
	'prefix'    => $namespace,
	'whitelist' => array(

		// Excludes specific namespaces from being prefixed.
		'Composer\\*',
		'MyCLabs',
		'MyCLabs\\*',
	),
	'finders'   => array(),
	'patchers'  => array(
		function ( $file_path, $prefix, $contents ) {

			// Check the file path and possibly return the original contents.
			if ( strpos( $file_path, 'myclabs/php-enum' ) !== false ) {
				return file_get_contents( $file_path ); // Revert to original contents.
			}

			return $contents; // Return modified contents for other files.
		},
	),
);
