<?php
/**
 * Filters related to the Blogs component.
 *
 * @package BuddyBoss\Blog\Filters
 * @since BuddyPress 1.6.0
 */

/** Display Filters **********************************************************/

add_filter( 'bp_get_blog_latest_post_title', 'wptexturize' );
add_filter( 'bp_get_blog_latest_post_title', 'convert_chars' );
add_filter( 'bp_get_blog_latest_post_title', 'trim' );

add_filter( 'bp_blog_latest_post_content', 'wptexturize' );
add_filter( 'bp_blog_latest_post_content', 'convert_smilies' );
add_filter( 'bp_blog_latest_post_content', 'convert_chars' );
add_filter( 'bp_blog_latest_post_content', 'wpautop' );
add_filter( 'bp_blog_latest_post_content', 'shortcode_unautop' );
add_filter( 'bp_blog_latest_post_content', 'prepend_attachment' );

/**
 * Ensure that the 'Create a new site' link at wp-admin/my-sites.php points to the BP blog signup.
 *
 * @since BuddyPress 1.6.0
 *
 *       returned value.
 *
 * @param string $url The original URL (points to wp-signup.php by default).
 * @return string The new URL.
 */
function bp_blogs_creation_location( $url ) {

	/**
	 * Filters the 'Create a new site' link URL.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $value URL for the 'Create a new site' signup page.
	 */
	return apply_filters( 'bp_blogs_creation_location', trailingslashit( bp_get_blogs_directory_permalink() . 'create' ), $url );
}
add_filter( 'wp_signup_location', 'bp_blogs_creation_location' );

/**
 * Only select comments by ID instead of all fields when using get_comments().
 *
 * @since BuddyPress 2.1.0
 *
 * @see bp_blogs_update_post_activity_meta()
 *
 * @param array $retval Current SQL clauses in array format.
 * @return array
 */
function bp_blogs_comments_clauses_select_by_id( $retval ) {
	$retval['fields'] = 'comment_ID';

	return $retval;
}

/**
 * Check whether the current activity about a post or a comment can be published.
 *
 * Abstracted from the deprecated `bp_blogs_record_post()`.
 *
 * @since BuddyPress 2.2.0
 *
 * @param bool $return  Whether the post should be published.
 * @param int  $blog_id ID of the blog.
 * @param int  $post_id ID of the post.
 * @param int  $user_id ID of the post author.
 * @return bool True to authorize the post to be published, otherwise false.
 */
function bp_blogs_post_pre_publish( $return = true, $blog_id = 0, $post_id = 0, $user_id = 0 ) {
	$bp = buddypress();

	// If blog is not trackable, do not record the activity.
	if ( ! bp_blogs_is_blog_trackable( $blog_id, $user_id ) ) {
		return false;
	}

	/*
	 * Stop infinite loops with WordPress MU Sitewide Tags.
	 * That plugin changed the way its settings were stored at some point. Thus the dual check.
	 */
	$sitewide_tags_blog_settings = bp_core_get_root_option( 'sitewide_tags_blog' );
	if ( ! empty( $sitewide_tags_blog_settings ) ) {
		$st_options   = maybe_unserialize( $sitewide_tags_blog_settings );
		$tags_blog_id = isset( $st_options['tags_blog_id'] ) ? $st_options['tags_blog_id'] : 0;
	} else {
		$tags_blog_id = bp_core_get_root_option( 'sitewide_tags_blog' );
		$tags_blog_id = intval( $tags_blog_id );
	}

	/**
	 * Filters whether or not BuddyPress should block sitewide tags activity.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param bool $value Current status of the sitewide tags activity.
	 */
	if ( (int) $blog_id == $tags_blog_id && apply_filters( 'bp_blogs_block_sitewide_tags_activity', true ) ) {
		return false;
	}

	/**
	 * Filters whether or not the current blog is public.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param int $value Value from the blog_public option for the current blog.
	 */
	$is_blog_public = apply_filters( 'bp_is_blog_public', (int) get_blog_option( $blog_id, 'blog_public' ) );

	if ( 0 === $is_blog_public && is_multisite() ) {
		return false;
	}

	return $return;
}
add_filter( 'bp_activity_post_pre_publish', 'bp_blogs_post_pre_publish', 10, 4 );
add_filter( 'bp_activity_post_pre_comment', 'bp_blogs_post_pre_publish', 10, 4 );

/**
 * Registers our custom thumb size with WP's Site Icon feature.
 *
 * @since BuddyPress 2.7.0
 *
 * @param  array $sizes Current array of custom site icon sizes.
 * @return array
 */
function bp_blogs_register_custom_site_icon_size( $sizes ) {
	$sizes[] = bp_core_avatar_thumb_width();
	return $sizes;
}
add_filter( 'site_icon_image_sizes', 'bp_blogs_register_custom_site_icon_size' );

/**
 * Set view post button for activity post content.
 *
 * @since BuddyBoss 1.7.2
 *
 * @param array $buttons     Group buttons.
 *
 * @return array
 */
function bb_nouveau_get_activity_inner_blogs_buttons( $buttons ) {
	global $activities_template;

	if ( ( 'blogs' === $activities_template->activity->component ) && isset( $activities_template->activity->secondary_item_id ) && 'new_blog_' . get_post_type( $activities_template->activity->secondary_item_id ) === $activities_template->activity->type ) {
		$blog_post = get_post( $activities_template->activity->secondary_item_id );
		// If we converted $content to an object earlier, flip it back to a string.
		if ( is_a( $blog_post, 'WP_Post' ) && ! has_post_thumbnail( $blog_post ) ) {
			$post_type_obj = get_post_type_object( $blog_post->post_type );

			$buttons['activity_post'] = array(
				'id'             => 'activity_post_link_wrap',
				'position'       => 4,
				'component'      => 'activity',
				'button_element' => 'a',
				'link_text'      => sprintf( '<span class="text">%1$s %2$s</span>', esc_html__( 'View', 'buddyboss' ), esc_attr( ucfirst( $post_type_obj->labels->singular_name ) ) ),
				'button_attr'    => array(
					'href'  => esc_url( get_permalink( $blog_post->ID ) ),
					'class' => 'button bb-icon-arrow-circle bp-secondary-action',
				),
			);
		}
	}

	return $buttons;
}
add_filter( 'bb_nouveau_get_activity_inner_buttons', 'bb_nouveau_get_activity_inner_blogs_buttons', 10, 1 );
