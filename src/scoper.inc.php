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

	// Excludes specific namespaces from being prefixed.
	'whitelist' => array(
		// Composer and vendor namespaces.
		'Composer\\*',
		'MyCLabs',
		'MyCLabs\\*',

		// Global namespace classes and functions that should not be prefixed.
		'\\WP_*',
		'\\Plugin_*',
		'\\Theme_*',
		'\\Core_*',
		'\\Language_*',
		'\\Bulk_*',
		'\\Automatic_*',
		'\\Ajax_*',
		'\\Walker*',
		'\\SimplePie*',

		// WordPress core classes without namespace (global namespace).
		'WP_*',
		'Plugin_*',
		'Theme_*',
		'Core_*',
		'Language_*',
		'Bulk_*',
		'Automatic_*',
		'Ajax_*',
		'Walker*',
		'SimplePie*',

		// PHP built-in classes and functions (global namespace).
		'Exception',
		'Error',
		'ErrorException',
		'Throwable',
		'Closure',
		'Generator',
		'Iterator',
		'IteratorAggregate',
		'ArrayAccess',
		'Serializable',
		'Countable',
		'Stringable',
		'stdClass',
		'Reflection*',
		'PDO*',
		'DateTime*',
		'DateInterval',
		'DatePeriod',
		'DateTimeZone',
		'DateTimeImmutable',
		'DateTimeInterface',
		'JsonSerializable',
		'Traversable',
		'RecursiveIterator',
		'RecursiveIteratorIterator',
		'FilterIterator',
		'LimitIterator',
		'NoRewindIterator',
		'InfiniteIterator',
		'AppendIterator',
		'MultipleIterator',
		'DirectoryIterator',
		'FilesystemIterator',
		'RecursiveDirectoryIterator',
		'GlobIterator',
		'Spl*',

		// WordPress constants.
		'ABSPATH',
		'WP_DEBUG',
		'WP_CONTENT_DIR',
		'WP_PLUGIN_DIR',
		'WPINC',
		'WP_CONTENT_URL',
		'WP_PLUGIN_URL',
		'WP_HOME',
		'WP_SITEURL',
		'WP_ADMIN',
		'WP_LOAD',
	),

	'finders'   => array(),

	'patchers'  => array(
		function ( $file_path, $prefix, $contents ) {
			// Check the file path and possibly return the original contents.
			if ( strpos( $file_path, 'myclabs/php-enum' ) !== false ) {
				return file_get_contents( $file_path ); // Revert to original contents.
			}

			// Fix any WordPress core classes that might have been prefixed.
			$contents = str_replace(
				array(
					$prefix . '\\WP_',
					$prefix . '\\Plugin_',
					$prefix . '\\Theme_',
					$prefix . '\\Core_',
					$prefix . '\\Language_',
					$prefix . '\\Bulk_',
					$prefix . '\\Automatic_',
					$prefix . '\\Ajax_',
					$prefix . '\\Walker',
					$prefix . '\\SimplePie',
					// Also catch any other prefixed WordPress classes.
					$prefix . '\\WP_Upgrader',
					$prefix . '\\WP_Upgrader_Skin',
					$prefix . '\\Plugin_Upgrader',
					$prefix . '\\Theme_Upgrader',
					$prefix . '\\Language_Pack_Upgrader',
					$prefix . '\\Core_Upgrade',
					$prefix . '\\File_Upload_Upgrader',
					$prefix . '\\Automatic_Upgrader_Skin',
					$prefix . '\\Bulk_Upgrader_Skin',
					$prefix . '\\Bulk_Plugin_Upgrader_Skin',
					$prefix . '\\Bulk_Theme_Upgrader_Skin',
					$prefix . '\\Plugin_Installer_Skin',
					$prefix . '\\Theme_Installer_Skin',
					$prefix . '\\Language_Pack_Upgrader_Skin',
					$prefix . '\\Plugin_Upgrader_Skin',
					$prefix . '\\Theme_Upgrader_Skin',
					$prefix . '\\WP_Ajax_Upgrader_Skin',
					$prefix . '\\WP_Automatic_Updater',
				),
				array(
					'WP_',
					'Plugin_',
					'Theme_',
					'Core_',
					'Language_',
					'Bulk_',
					'Automatic_',
					'Ajax_',
					'Walker',
					'SimplePie',
					// Also catch any other prefixed WordPress classes.
					'WP_Upgrader',
					'WP_Upgrader_Skin',
					'Plugin_Upgrader',
					'Theme_Upgrader',
					'Language_Pack_Upgrader',
					'Core_Upgrade',
					'File_Upload_Upgrader',
					'Automatic_Upgrader_Skin',
					'Bulk_Upgrader_Skin',
					'Bulk_Plugin_Upgrader_Skin',
					'Bulk_Theme_Upgrader_Skin',
					'Plugin_Installer_Skin',
					'Theme_Installer_Skin',
					'Language_Pack_Upgrader_Skin',
					'Plugin_Upgrader_Skin',
					'Theme_Upgrader_Skin',
					'WP_Ajax_Upgrader_Skin',
					'WP_Automatic_Updater',
				),
				$contents
			);

			// Additional fix for any remaining prefixed WordPress classes.
			$contents = preg_replace(
				'/\\\\' . preg_quote($prefix) . '\\\\WP_([A-Za-z_]+)/',
				'WP_$1',
				$contents
			);

			$contents = preg_replace(
				'/\\\\' . preg_quote($prefix) . '\\\\(Plugin|Theme|Core|Language|Bulk|Automatic|Ajax|Walker|SimplePie)([A-Za-z_]*)/',
				'$1$2',
				$contents
			);

			return $contents;
		},
	),
);
