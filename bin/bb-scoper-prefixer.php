<?php

/**
 * File contains a script to add prefix to below files.
 *
 * @since   BuddyBoss 2.6.90
 * @package BuddyBoss
 */
$files = array(
	'vendor/composer/autoload_static.php',
	'vendor/composer/autoload_files.php',
);

foreach ( $files as $file ) {
	if ( file_exists( $file ) ) {
		$contents = file_get_contents( $file );
		$contents = preg_replace_callback(
			'/\'([a-f0-9]{32})\'\s*=>/',
			function ( $matches ) {
				return "'bb_platform_" . $matches[1] . "' =>";
			},
			$contents
		);
		file_put_contents( $file, $contents );
	}
}
