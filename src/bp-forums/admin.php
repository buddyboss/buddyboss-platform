<?php
/**
 * BuddyBoss Forums component admin screen.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @package BuddyBoss
 * @subpackage ForumsAdmin
 * @since BuddyBOss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the Forums component admin screen.
 *
 * @since BuddyBoss 3.1.1
 */
function bp_forums_add_admin_menu() {

	// Add our screen.
	$hook = add_menu_page(
		_x( 'Forums', 'Admin Dashboard SWA page title', 'buddyboss' ),
		_x( 'Forums', 'Admin Dashboard SWA menu', 'buddyboss' ),
		'bp_moderate',
		'bp-forums',
		'__return_null',
		'dashicons-editor-ul'
	);

	// Hook into early actions to load custom CSS and our init handler.
	add_action( "load-$hook", 'bp_forums_admin_load' );

	add_submenu_page(
		'bp-forums',
		bbp_get_forum_post_type_labels()['name'],
		bbp_get_forum_post_type_labels()['menu_name'],
		'bbp_forums_admin',
		'edit.php?post_type=' . bbp_get_forum_post_type()
	);

	add_submenu_page(
		'bp-forums',
		bbp_get_topic_post_type_labels()['name'],
		bbp_get_topic_post_type_labels()['menu_name'],
		'bbp_topics_admin',
		'edit.php?post_type=' . bbp_get_topic_post_type()
	);

	add_submenu_page(
		'bp-forums',
		bbp_get_topic_tag_tax_labels()['name'],
		__( 'Tags', 'buddyboss' ),
		'bbp_topic_tags_admin',
		'edit-tags.php?taxonomy=' . bbp_get_topic_tag_tax_id() . '&post_type=' . bbp_get_topic_post_type()
	);

	add_submenu_page(
		'bp-forums',
		bbp_get_reply_post_type_labels()['name'],
		bbp_get_reply_post_type_labels()['menu_name'],
		'bbp_replies_admin',
		'edit.php?post_type=' . bbp_get_reply_post_type()
	);

	remove_submenu_page( 'bp-forums', 'bp-forums' );
}
add_action( bp_core_admin_hook(), 'bp_forums_add_admin_menu' );

/**
 * Add forums component to custom menus array.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param array $custom_menus The list of top-level BP menu items.
 * @return array $custom_menus List of top-level BP menu items, with Forums added.
 */
function bp_forums_admin_menu_order( $custom_menus = array() ) {
	array_push( $custom_menus, 'bp-forums' );
	return $custom_menus;
}
add_filter( 'bp_admin_menu_order', 'bp_forums_admin_menu_order' );

/**
 * Make parent menu highlight when on topic tag page
 *
 * @since BuddyBoss 3.1.1
 */
function bp_forums_highlight_topic_tag_parent_menu( $parent_file ) {
	$taxonomy = isset( $_GET['taxonomy'] )? $_GET['taxonomy'] : '';
	$post_type = isset( $_GET['post_type'] )? $_GET['post_type'] : '';

	if ( bbp_get_topic_tag_tax_id() == $taxonomy && bbp_get_topic_post_type() == $post_type ) {
		return 'bp-forums';
	}

	return $parent_file;
}
add_filter( 'parent_file', 'bp_forums_highlight_topic_tag_parent_menu' );

/**
 * Make submenu highlight when on topic tag page
 *
 * @since BuddyBoss 3.1.1
 */
function bp_forums_highlight_topic_tag_submenu( $submenu_file ) {
	$taxonomy = isset( $_GET['taxonomy'] )? $_GET['taxonomy'] : '';
	$post_type = isset( $_GET['post_type'] )? $_GET['post_type'] : '';

	if ( bbp_get_topic_tag_tax_id() == $taxonomy && bbp_get_topic_post_type() == $post_type ) {
		return 'edit-tags.php?taxonomy=' . bbp_get_topic_tag_tax_id() . '&post_type=' . bbp_get_topic_post_type();
	}

	return $submenu_file;
}
add_filter( 'submenu_file', 'bp_forums_highlight_topic_tag_submenu' );

/**
 * Make paretn menu highlight when on editing/creating topic
 *
 * @since BuddyBoss 3.1.1
 */
function bp_forums_highlight_topic_edit_parent_menu( $parent_file ) {
	global $pagenow;

	$post_type = isset( $_GET['post_type'] )? $_GET['post_type'] : '';
	$forums_post_types = [ bbp_get_forum_post_type(), bbp_get_topic_post_type(), bbp_get_reply_post_type() ];

	if ( $pagenow && 'post-new.php' == $pagenow && in_array( $post_type, $forums_post_types ) ) {
		return 'bp-forums';
	}

	return $parent_file;
}
add_filter( 'parent_file', 'bp_forums_highlight_topic_edit_parent_menu' );

/**
 * Make submenu highlight when on editing/creating topic
 *
 * @since BuddyBoss 3.1.1
 */
function bp_forums_highlight_topic_edit_submenu( $submenu_file ) {
	global $pagenow;

	$post_type = isset( $_GET['post_type'] )? $_GET['post_type'] : '';
	$forums_post_types = [ bbp_get_forum_post_type(), bbp_get_topic_post_type(), bbp_get_reply_post_type() ];

	if ( $pagenow && 'post-new.php' == $pagenow && in_array( $post_type, $forums_post_types ) ) {
		return 'edit.php?post_type=' . $post_type;
	}

	return $submenu_file;
}
add_filter( 'submenu_file', 'bp_forums_highlight_topic_edit_submenu' );
