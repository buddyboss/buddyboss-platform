<?php
/**
 * BuddyBoss Forums component admin screen.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @package BuddyBoss\Forums\Admin
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the Forums component admin screen.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_forums_add_admin_menu() {

	if ( ! is_network_admin() && ! bp_is_network_activated() ) {
		$forum_url = 'edit.php?post_type=' . bbp_get_forum_post_type();

		// Add our screen.
		add_submenu_page(
			'buddyboss-platform',
			__( 'Forums', 'buddyboss' ),
			__( 'Forums', 'buddyboss' ),
			'bbp_forums_admin',
			$forum_url
		);
	}


}
add_action( bp_core_admin_hook(), 'bp_forums_add_admin_menu', 61 );

function bp_forums_add_sub_menu_page_admin_menu() {

	if ( is_multisite() && bp_is_network_activated() ) {
		$forum_url = 'edit.php?post_type=' . bbp_get_forum_post_type(); // buddyboss-settings
		// Add our screen.
		$hook = add_submenu_page( 'buddyboss-platform',
			__( 'Forums', 'buddyboss' ),
			__( 'Forums', 'buddyboss' ),
			'bbp_forums_admin',
			$forum_url,
			'' );
	}
}
add_action( 'admin_menu', 'bp_forums_add_sub_menu_page_admin_menu', 10 );

/**
 * Add forums component to custom menus array.
 *
 * @since BuddyBoss 1.0.0
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
 * @since BuddyBoss 1.0.0
 */
function bp_forums_highlight_topic_tag_parent_menu( $parent_file ) {
	$taxonomy  = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : '';
	$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';

	if ( bbp_get_topic_tag_tax_id() == $taxonomy && bbp_get_topic_post_type() == $post_type ) {
		return 'bp-forums';
	}

	return $parent_file;
}
add_filter( 'parent_file', 'bp_forums_highlight_topic_tag_parent_menu' );

/**
 * Make submenu highlight when on topic tag page
 *
 * @since BuddyBoss 1.0.0
 */
function bp_forums_highlight_topic_tag_submenu( $submenu_file ) {
	$taxonomy  = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : '';
	$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';

	if ( bbp_get_topic_tag_tax_id() == $taxonomy && bbp_get_topic_post_type() == $post_type ) {
		return 'edit-tags.php?taxonomy=' . bbp_get_topic_tag_tax_id() . '&post_type=' . bbp_get_topic_post_type();
	}

	return $submenu_file;
}
add_filter( 'submenu_file', 'bp_forums_highlight_topic_tag_submenu' );

/**
 * Make paretn menu highlight when on editing/creating topic
 *
 * @since BuddyBoss 1.0.0
 */
function bp_forums_highlight_forums_new_parent_menu( $parent_file ) {
	global $pagenow;

	$post_type         = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
	$forums_post_types = array( bbp_get_forum_post_type(), bbp_get_topic_post_type(), bbp_get_reply_post_type() );

	if ( $pagenow && 'post-new.php' == $pagenow && in_array( $post_type, $forums_post_types ) ) {
		return 'bp-forums';
	}

	return $parent_file;
}
add_filter( 'parent_file', 'bp_forums_highlight_forums_new_parent_menu' );

/**
 * Make submenu highlight when on editing/creating topic
 *
 * @since BuddyBoss 1.0.0
 */
function bp_forums_highlight_forums_new_submenu( $submenu_file ) {
	global $pagenow;

	$post_type         = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
	$forums_post_types = array( bbp_get_forum_post_type(), bbp_get_topic_post_type(), bbp_get_reply_post_type() );

	if ( $pagenow && 'post-new.php' == $pagenow && in_array( $post_type, $forums_post_types ) ) {
		return 'edit.php?post_type=' . $post_type;
	}

	return $submenu_file;
}
add_filter( 'submenu_file', 'bp_forums_highlight_forums_new_submenu' );

/**
 * Make paretn menu highlight when on editing/creating topic
 *
 * @since BuddyBoss 1.0.0
 */
function bp_forums_highlight_forums_view_parent_menu( $parent_file ) {
	global $pagenow;

	$post_type         = get_post_type();
	$forums_post_types = array( bbp_get_forum_post_type(), bbp_get_topic_post_type(), bbp_get_reply_post_type() );

	if ( $pagenow && 'post.php' == $pagenow && in_array( $post_type, $forums_post_types ) ) {
		return 'bp-forums';
	}

	return $parent_file;
}
add_filter( 'parent_file', 'bp_forums_highlight_forums_view_parent_menu' );

/**
 * Make submenu highlight when on editing/creating topic
 *
 * @since BuddyBoss 1.0.0
 */
function bp_forums_highlight_forums_view_submenu( $submenu_file ) {
	global $pagenow;

	$post_type         = get_post_type();
	$forums_post_types = array( bbp_get_forum_post_type(), bbp_get_topic_post_type(), bbp_get_reply_post_type() );

	if ( $pagenow && 'post.php' == $pagenow && in_array( $post_type, $forums_post_types ) ) {
		return 'edit.php?post_type=' . $post_type;
	}

	return $submenu_file;
}
add_filter( 'submenu_file', 'bp_forums_highlight_forums_view_submenu' );

/**
 * Output the tabs in the admin area.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
function bp_core_admin_forums_tabs( $active_tab = '' ) {
	$tabs_html    = '';
	$idle_class   = 'nav-tab';
	$active_class = 'nav-tab nav-tab-active';

	/**
	 * Filters the admin tabs to be displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $value Array of tabs to output to the admin area.
	 */
	$tabs = apply_filters( 'bp_core_admin_forums_tabs', bp_core_get_forums_admin_tabs( $active_tab ) );

	// Loop through tabs and build navigation.
	foreach ( array_values( $tabs ) as $tab_data ) {
		$is_current = (bool) ( $tab_data['name'] == $active_tab );
		$tab_class  = $is_current ? $tab_data['class'] . ' ' . $active_class : $tab_data['class'] . ' ' . $idle_class;
		$tabs_html .= '<a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a>';
	}

	echo $tabs_html;

	/**
	 * Fires after the output of tabs for the admin area.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_admin_groups_tabs' );
}

/**
 * Register tabs for the BuddyBoss > Forums screens.
 *
 * @param string $active_tab
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_core_get_forums_admin_tabs( $active_tab = '' ) {

	$tabs = array();

	$tabs[] = array(
		'href'  => ( is_multisite() ) ? get_admin_url( get_current_blog_id(), add_query_arg( array( 'post_type' => bbp_get_forum_post_type() ), 'edit.php' ) ) : bp_get_admin_url( add_query_arg( array( 'post_type' => bbp_get_forum_post_type() ), 'edit.php' ) ),
		'name'  => __( 'Forums', 'buddyboss' ),
		'class' => 'bp-forums',
	);

	$tabs[] = array(
		'href'  => ( is_multisite() ) ? get_admin_url( get_current_blog_id(), add_query_arg( array( 'post_type' => bbp_get_topic_post_type() ), 'edit.php' ) ) : bp_get_admin_url( add_query_arg( array( 'post_type' => bbp_get_topic_post_type() ), 'edit.php' ) ),
		'name'  => __( 'Discussions', 'buddyboss' ),
		'class' => 'bp-discussions',
	);

	$tabs[] = array(
		'href'  => ( is_multisite() ) ? get_admin_url( get_current_blog_id(), add_query_arg( array( 'taxonomy' =>  bbp_get_topic_tag_tax_id(), 'post_type' => bbp_get_topic_post_type() ), 'edit-tags.php' ) ) : bp_get_admin_url( add_query_arg( array( 'taxonomy' =>  bbp_get_topic_tag_tax_id(), 'post_type' => bbp_get_topic_post_type() ), 'edit-tags.php' ) ),
		'name'  => __( 'Discussion Tags', 'buddyboss' ),
		'class' => 'bp-tags',
	);

	$tabs[] = array(
		'href'  => ( is_multisite() ) ? get_admin_url( get_current_blog_id(), add_query_arg( array( 'post_type' => bbp_get_reply_post_type() ), 'edit.php' ) ) : bp_get_admin_url( add_query_arg( array( 'post_type' => bbp_get_reply_post_type() ), 'edit.php' ) ),
		'name'  => __( 'Replies', 'buddyboss' ),
		'class' => 'bp-replies',
	);

	/**
	 * Filters the tab data used in our wp-admin screens.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $tabs Tab data.
	 */
	return apply_filters( 'bp_core_get_forums_admin_tabs', $tabs );
}

/**
 * Add Navigation tab on top of the page BuddyBoss > Forums Templates
 *
 * @since BuddyBoss 1.0.0
 */
function bp_forums_admin_forums_listing_add_tab() {
	global $pagenow, $current_screen;

	if ( ( $current_screen->post_type == bbp_get_forum_post_type() && $pagenow == 'edit.php' ) || ( $current_screen->post_type == bbp_get_forum_post_type() && $pagenow == 'post-new.php' ) || ( $current_screen->post_type == bbp_get_forum_post_type() && $pagenow == 'post.php' ) ) {
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper"><?php bp_core_admin_forums_tabs( __( 'Forums', 'buddyboss' ) ); ?></h2>
		</div>
		<?php
	}

}
add_action( 'admin_notices', 'bp_forums_admin_forums_listing_add_tab' );

/**
 * Add Navigation tab on top of the page BuddyBoss > Forums > Discussions Templates
 *
 * @since BuddyBoss 1.0.0
 */
function bp_discussions_admin_discussions_listing_add_tab() {
	global $pagenow, $current_screen;

	if ( ( isset( $current_screen->post_type ) && $current_screen->post_type == bbp_get_topic_post_type() && $pagenow == 'edit.php' ) || ( isset( $current_screen->post_type ) && $current_screen->post_type == bbp_get_topic_post_type() && $pagenow == 'post-new.php' ) || ( isset( $current_screen->post_type ) && $current_screen->post_type == bbp_get_topic_post_type() && $pagenow == 'post.php' ) ) {
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper"><?php bp_core_admin_forums_tabs( __( 'Discussions', 'buddyboss' ) ); ?></h2>
		</div>
		<?php
	}

}
add_action( 'admin_notices', 'bp_discussions_admin_discussions_listing_add_tab' );

/**
 * Add Navigation tab on top of the page BuddyBoss > Forums > Replies Templates
 *
 * @since BuddyBoss 1.0.0
 */
function bp_replies_admin_replies_listing_add_tab() {
	global $pagenow, $current_screen;

	if ( ( isset( $current_screen->post_type ) && $current_screen->post_type == bbp_get_reply_post_type() && $pagenow == 'edit.php' ) || ( isset( $current_screen->post_type ) && $current_screen->post_type == bbp_get_reply_post_type() && $pagenow == 'post-new.php' ) || ( isset( $current_screen->post_type ) && $current_screen->post_type == bbp_get_reply_post_type() && $pagenow == 'post.php' ) ) {
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper"><?php bp_core_admin_forums_tabs( __( 'Replies', 'buddyboss' ) ); ?></h2>
		</div>
		<?php
	}

}
add_action( 'admin_notices', 'bp_replies_admin_replies_listing_add_tab' );

/**
 * Add Navigation tab on top of the page BuddyBoss > Forums > Tags Templates
 *
 * @since BuddyBoss 1.0.0
 */
function bp_tags_admin_tags_listing_add_tab() {
	global $pagenow ,$current_screen;

	if ( ( $current_screen->taxonomy == bbp_get_topic_tag_tax_id() && $pagenow == 'edit-tags.php' ) || ( $current_screen->taxonomy == bbp_get_topic_tag_tax_id() && $pagenow == 'term.php' ) ) {
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper"><?php bp_core_admin_forums_tabs( __( 'Discussion Tags', 'buddyboss' ) ); ?></h2>
		</div>
		<?php
	}

}
add_action( 'admin_notices', 'bp_tags_admin_tags_listing_add_tab' );

add_filter( 'parent_file', 'bbp_set_platform_tab_submenu_active' );
/**
 * Highlights the submenu item using WordPress native styles.
 *
 * @param string $parent_file The filename of the parent menu.
 *
 * @return string $parent_file The filename of the parent menu.
 */
function bbp_set_platform_tab_submenu_active( $parent_file ) {
	global $pagenow, $current_screen, $post;

	if ( ( isset( $post->post_type ) && $post->post_type == bbp_get_reply_post_type() && $pagenow == 'edit.php' ) || ( isset( $post->post_type ) && $post->post_type == bbp_get_reply_post_type() && $pagenow == 'post-new.php' ) || ( isset( $post->post_type ) && $post->post_type == bbp_get_reply_post_type() && $pagenow == 'post.php' ) ) {
		$parent_file = 'buddyboss-platform';
	} elseif ( ( $current_screen->taxonomy == bbp_get_topic_tag_tax_id() && $pagenow == 'edit-tags.php' ) || ( $current_screen->taxonomy == bbp_get_topic_tag_tax_id() && $pagenow == 'term.php' ) ) {
		$parent_file = 'buddyboss-platform';
	} elseif ( ( isset( $post->post_type ) && $post->post_type == bbp_get_topic_post_type() && $pagenow == 'edit.php' ) || ( isset( $post->post_type ) && $post->post_type == bbp_get_topic_post_type() && $pagenow == 'post-new.php' ) || ( isset( $post->post_type ) && $post->post_type == bbp_get_topic_post_type() && $pagenow == 'post.php' ) ) {
		$parent_file = 'buddyboss-platform';
	} elseif ( ( $current_screen->post_type == bbp_get_forum_post_type() && $pagenow == 'edit.php' ) || ( $current_screen->post_type == bbp_get_forum_post_type() && $pagenow == 'post-new.php' ) || ( $current_screen->post_type == bbp_get_forum_post_type() && $pagenow == 'post.php' ) ) {
		$parent_file = 'buddyboss-platform';
	}
	return $parent_file;
}
