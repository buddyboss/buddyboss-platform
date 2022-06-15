<?php
/**
 * BuddyBoss Profile Search Directory
 *
 * @package BuddyBoss\Core\ProfileSearch
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
/**
 * Returns array of BuddyBoss Profile Search directories.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_directories() {
	static $dirs = array();

	if ( count( $dirs ) ) {
		return $dirs;
	}

	$bp_pages = bp_core_get_directory_page_ids();
	if ( isset( $bp_pages['members'] ) ) {
		$members                 = $bp_pages['members'];
		$dirs[ $members ]        = new stdClass();
		$dirs[ $members ]->label = get_the_title( $members );
		$dirs[ $members ]->link  = parse_url( get_page_link( $members ), PHP_URL_PATH );

		$member_types = bp_get_member_types( array(), 'objects' );
		foreach ( $member_types as $type ) {
			if ( $type->has_directory == 1 ) {
				$dirs[ $type->name ]        = new stdClass();
				$dirs[ $type->name ]->label = $dirs[ $members ]->label . ' - ' . $type->labels['name'];
				$dirs[ $type->name ]->link  = parse_url( bp_get_member_type_directory_permalink( $type->name ), PHP_URL_PATH );
			}
		}
	}

	if ( ! shortcode_exists( 'bp_ps_directory' ) ) {
		return $dirs;
	}

	$pages = get_pages();
	foreach ( $pages as $page ) {
		if ( has_shortcode( $page->post_content, 'bp_ps_directory' ) ) {
			$dirs[ $page->ID ]        = new stdClass();
			$dirs[ $page->ID ]->label = $page->post_title;
			$dirs[ $page->ID ]->link  = parse_url( get_page_link( $page->ID ), PHP_URL_PATH );
		}
	}

	return $dirs;
}

add_action( 'wp_enqueue_scripts', 'bp_ps_clear_directory', 1 );
/**
 * Clear BuddyBoss Profile Search directory.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_clear_directory() {
	global $bp;

	$dirs    = bp_ps_directories();
	$current = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

	foreach ( $dirs as $dir ) {
		if ( $dir->link == $current ) {
			add_filter(
				'bp_directory_members_search_form',
				function ( $text ) {
					return $text;
				}
			);
			break;
		}
	}
}

// add_shortcode ('bp_ps_directory', 'bp_ps_show_directory');
/**
 * Output BuddyBoss Profile Search directory template.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_show_directory( $attr, $content ) {
	ob_start();

	if ( bp_ps_debug() ) {
		echo "<!--\n";
		print_r( $attr );
		print_r( bp_ps_hidden_filters() );
		echo "-->\n";
	}

	if ( isset( $attr['order_by'] ) ) {
		bp_ps_set_sort_options( $attr['order_by'] );
	}

	$template = isset( $attr['template'] ) ? $attr['template'] : 'members/index';
	bp_ps_call_template( $template );

	if ( bp_get_theme_package_id() == 'nouveau' ) {
		printf(
			'<p class="bp-ps-error">' . __( '%s: The shortcode [bp_ps_directory] is not compatible with the Nouveau template pack.', 'buddyboss' ) . '</p>',
			'<strong>BP Profile Search ' . bp_get_version() . '</strong>'
		);
	}

	return ob_get_clean();
}

/**
 * Set BuddyBoss Profile Search sort options.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_set_sort_options( $options ) {
	global $bp_ps_sort_options;

	if ( ! isset( $bp_ps_sort_options ) ) {
		$bp_ps_sort_options = array();
	}
	list (, $fields) = bp_ps_get_fields();

	$options = explode( ',', $options );
	foreach ( $options as $option ) {
		$option = trim( preg_replace( '/\s+/', ' ', $option ) );
		$option = explode( ' ', $option );

		$code  = $option[0];
		$order = isset( $option[1] ) ? $option[1] : 'asc';

		if ( ! isset( $fields[ $code ]->sort_directory ) ||
			! is_callable( $fields[ $code ]->sort_directory ) ||
			! in_array( $order, array( 'asc', 'desc', 'both' ) ) ) {
			continue;
		}

		if ( $order == 'asc' ) {
			$bp_ps_sort_options[ $code ] = $fields[ $code ]->name;
		} elseif ( $order == 'desc' ) {
			$bp_ps_sort_options[ '-' . $code ] = $fields[ $code ]->name;
		} elseif ( $order == 'both' ) {
			$bp_ps_sort_options[ $code ]       = $fields[ $code ]->name . ' &#x21E1;';
			$bp_ps_sort_options[ '-' . $code ] = $fields[ $code ]->name . ' &#x21E3;';
		}
	}

	add_action( 'bp_members_directory_order_options', 'bp_ps_display_sort_options' );
}

/**
 * Output BuddyBoss Profile Search sort options.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_display_sort_options() {
	 global $bp_ps_sort_options;

	$version = bp_get_version();
	echo "\n<!-- BP Profile Search $version -->\n";

	$sort_options = apply_filters( 'bp_ps_sort_options', $bp_ps_sort_options );
	foreach ( $sort_options as $code => $name ) {
		?>
		<option value='<?php echo esc_attr( $code ); ?>'><?php echo esc_html( $name ); ?></option>
		<?php
	}

	echo "\n<!-- BP Profile Search end -->\n";
}

add_filter( 'bp_user_query_uid_clauses', 'bp_ps_uid_clauses', 99, 2 );

/**
 * Returns BuddyBoss Profile Search sql with added directory member item clauses.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_uid_clauses( $sql, $object ) {
	$code  = $object->query_vars['type'];
	$order = 'ASC';

	if ( isset( $code[0] ) && $code[0] == '-' ) {
		$code  = substr( $code, 1 );
		$order = 'DESC';
	}

	list (, $fields) = bp_ps_get_fields();
	if ( isset( $fields[ $code ]->sort_directory ) && is_callable( $fields[ $code ]->sort_directory ) ) {
		$f   = $fields[ $code ];
		$sql = call_user_func( $f->sort_directory, $sql, $object, $f, $order );
		add_action( 'bp_directory_members_item', 'bp_ps_directory_members_item' );
	}

	return $sql;
}

/**
 * Out BuddyBoss Profile Search field value template.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_directory_members_item() {
	global $members_template;

	$code = $members_template->type;
	if ( $code[0] == '-' ) {
		$code = substr( $code, 1 );
	}

	list (, $fields) = bp_ps_get_fields();
	if ( isset( $fields[ $code ]->get_value ) && is_callable( $fields[ $code ]->get_value ) ) {
		$f     = $fields[ $code ];
		$name  = $f->name;
		$value = call_user_func( $f->get_value, $f );
		bp_ps_call_template( 'members/bps-field-value', array( $name, $value ) );
	}
}
