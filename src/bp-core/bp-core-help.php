<?php
/**
 * BuddyBoss Core Help Setup.
 *
 * @package BuddyBoss\Help
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Dynamically add the URL
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $attr
 *
 * @return mixed
 */
function bp_core_help_bp_docs_link( $attr ) {
	$slug    = isset( $attr['slug'] ) ? bp_core_dynamically_add_number_in_path( $attr['slug'] ) : '';
	$text    = isset( $attr['text'] ) ? $attr['text'] : '';
	$anchors = isset( $attr['anchors'] ) ? '#' . $attr['anchors'] : '';
	$url     = bp_get_admin_url( add_query_arg( array(
		'page'    => 'bp-help',
		'article' => $slug . $anchors
	), 'admin.php' ) );

	return apply_filters( 'bp_core_help_bp_docs_link', sprintf( '<a href="%s">%s</a>', $url, $text ), $attr );
}

add_shortcode( 'bp_docs_link', 'bp_core_help_bp_docs_link' );

if ( ! function_exists( 'bp_core_get_post_id_by_slug' ) ) {
	/**
	 * Get Post id by Post SLUG
	 *
	 * @param $slug
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function bp_core_get_post_id_by_slug( $slug ) {
		$post_id = array();
		$args    = array(
			'posts_per_page' => 1,
			'post_type'      => 'docs',
			'name'           => $slug,
			'post_parent'    => 0,
		);
		$docs    = get_posts( $args );
		if ( ! empty( $docs ) ) {
			foreach ( $docs as $doc ) {
				$post_id[] = $doc->ID;
			}
		}

		return $post_id;
	}
}

/**
 * Generate post slug by files name
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $dir_index_file
 *
 * @return string
 */
function bp_core_get_post_slug_by_index( $dir_index_file ) {
	$dir_file_array = explode( '/', $dir_index_file );
	$index_file = db_core_remove_file_extension_from_slug( end( $dir_file_array ) );

	return db_core_remove_file_number_from_slug( $index_file );
}

/**
 * Remove H1 tag from Content
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $content
 *
 * @return mixed|null|string|string[]
 */
function bp_core_stripe_header_tags( $content ) {
	$content = preg_replace( '/<h1[^>]*>([\s\S]*?)<\/h1[^>]*>/', '', $content );

	return $content;
}

/**
 * Wrap the content via the_content filter
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $content
 *
 * @return html
 */
function bp_core_rap_the_content_filter( $content ) {
	return apply_filters( 'the_content', $content );
}

/**
 * Remove file type from slug
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $slug
 * @param string $extention
 *
 * @return mixed
 */
function db_core_remove_file_extension_from_slug( $slug, $file_type = '.md' ) {
	return str_replace( $file_type, '', $slug );
}

/**
 * Remove file number from slug
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $index_file
 *
 * @return mixed
 */
function db_core_remove_file_number_from_slug( $index_file ) {
	$index_file = explode( '-', $index_file );

	if ( ( absint( $index_file[0] ) > 0 || '0' == $index_file[0] ) && count( $index_file ) > 1 ) {
		unset( $index_file[0] );
	}

	return implode( '-', $index_file );
}

/**
 * Remove number from the dir
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $path
 *
 * @return string $path
 */
function bp_core_strip_number_from_slug( $path ) {
	$new_path = '';

	foreach ( explode( '/', $path ) as $current_path ) {
		$current_path = db_core_remove_file_extension_from_slug( $current_path );
		$current_path = db_core_remove_file_number_from_slug( $current_path );

		$new_path .= empty( $new_path ) ? $current_path : '/' . $current_path;
	}

	return $new_path;
}


/**
 * Dynamically add the number slug before folder path
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $slug
 *
 * @return mixed
 */
function bp_core_dynamically_add_number_in_path( $slug ) {
	$new_slug = bp_core_strip_number_from_slug( $slug );

	$base_path = buddypress()->plugin_dir . 'bp-help';
	$docs_path = $base_path . '/docs/';

	$paths = bp_core_get_all_file_from_dir_and_subdir( $docs_path );
	if ( ! empty( $paths ) ) {
		foreach ( $paths as $path ) {
			$file_path = str_replace( $docs_path, "", $path );
			$path      = bp_core_strip_number_from_slug( $file_path );
			if ( $path == $new_slug ) {
				$new_slug = $file_path;
				break;
			}

		}
	}


	return $new_slug;
}

/**
 * Get all files and folders from the dir and sub dir
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $dir
 * @param array $results
 *
 * @return array
 */
function bp_core_get_all_file_from_dir_and_subdir( $dir, &$results = array() ) {
	$files = scandir( $dir );

	foreach ( $files as $key => $value ) {
		$path = realpath( $dir . DIRECTORY_SEPARATOR . $value );
		if ( ! is_dir( $path ) ) {
			$results[] = $path;
		} else if ( $value != "." && $value != ".." ) {
			bp_core_get_all_file_from_dir_and_subdir( $path, $results );
			$results[] = $path;
		}
	}

	return $results;
}