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
	$slug    = isset( $attr['slug'] ) ? bp_core_help_dynamically_add_number_in_path( $attr['slug'] ) : '';
	$text    = isset( $attr['text'] ) ? $attr['text'] : '';
	$anchors = isset( $attr['anchors'] ) ? '#' . $attr['anchors'] : '';
	$url     = bp_get_admin_url(
		add_query_arg(
			array(
				'page'    => 'bp-help',
				'article' => $slug . $anchors,
			),
			'admin.php'
		)
	);

	$return = apply_filters( 'bp_core_help_bp_docs_link', $url, $attr );

	if ( ! empty( $text ) ) {
		$return = sprintf( '<a href="%s">%s</a>', $return, $text );
	}

	return $return;
}

add_shortcode( 'bp_docs_link', 'bp_core_help_bp_docs_link' );

/**
 * Dynamically Embed the Video
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $attr
 *
 * @return mixed
 */
function bp_core_help_embed_video( $attr, $content_url = '' ) {
	$width  = isset( $attr['width'] ) ? $attr['width'] : 500;
	$height = isset( $attr['height'] ) ? $attr['height'] : 500;
	$url    = isset( $attr['url'] ) ? $attr['url'] : isset( $content_url ) ? $content_url : '';

	if ( $url ) {
		$url  = new SimpleXMLElement( $url );
		$args = array(
			'width'    => $width,
			'height'   => $height,
			'discover' => true,
		);

		return wp_oembed_get( $url['href'], $args );
	} else {
		return __( 'Video URL required', 'buddyboss' );
	}

}

add_shortcode( 'bp_embed', 'bp_core_help_embed_video' );

/**
 * Anchor tag help doc link
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $slug
 * @param string $text
 * @param string $anchors
 *
 * @return mixed
 */
function bp_core_help_docs_link( $slug = '', $text = '', $anchors = '' ) {
	$attr = array(
		'slug'    => $slug,
		'text'    => $text,
		'anchors' => $anchors,
	);

	return bp_core_help_bp_docs_link( $attr );
}

/**
 * Print Docs Link
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $slug
 * @param string $text
 * @param string $anchors
 *
 * @return mixed
 */
function bp_core_help_get_docs_link( $slug = '', $text = '', $anchors = '' ) {
	echo bp_core_help_docs_link( $slug, $text, $anchors );
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
function bp_core_help_wrap_the_content_filter( $content ) {
	global $shortcode_tags;

	// Remove shortcodes rendering except bp-help's shortcodes
	if ( ! empty( $shortcode_tags ) ) {
		foreach ( $shortcode_tags as $tag => $shortcode_tag ) {
			if ( ! in_array( $tag, array( 'bp_docs_link', 'bp_embed' ) ) ) {
				remove_shortcode( $tag );
			}
		}
	}

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
function bp_core_help_remove_file_extension_from_slug( $slug, $file_type = '.md' ) {
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
function bp_core_help_remove_file_number_from_slug( $index_file ) {
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
function bp_core_help_strip_number_from_slug( $path ) {
	$new_path = '';

	foreach ( explode( '/', $path ) as $current_path ) {
		$current_path = bp_core_help_remove_file_extension_from_slug( $current_path );
		$current_path = bp_core_help_remove_file_number_from_slug( $current_path );

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
function bp_core_help_dynamically_add_number_in_path( $slug ) {
	$new_slug = bp_core_help_strip_number_from_slug( $slug );

	$base_path = buddypress()->plugin_dir . 'bp-help';
	$docs_path = $base_path . '/docs/';

	$paths = bp_core_get_all_file_from_dir_and_subdir( $docs_path );
	if ( ! empty( $paths ) ) {
		foreach ( $paths as $path ) {
			$file_path = str_replace( $docs_path, '', $path );
			$path      = bp_core_help_strip_number_from_slug( $file_path );
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
		} elseif ( $value != '.' && $value != '..' ) {
			bp_core_get_all_file_from_dir_and_subdir( $path, $results );
			$results[] = $path;
		}
	}

	return $results;
}

/**
 * Return the Default data format
 *
 * @param bool $date
 * @param bool $time
 *
 * @return mixed
 */
function bp_core_date_format( $time = false, $date = true, $symbol = ' @ ' ) {

	$format = $date ? get_option( 'date_format' ) : '';

	if ( $time ) {
		$format .= empty( $format ) ? get_option( 'time_format' ) : $symbol . get_option( 'time_format' );
	}
	return $format;
}
