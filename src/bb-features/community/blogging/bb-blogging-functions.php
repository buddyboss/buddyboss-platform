<?php
/**
 * Blogs feature runtime functions.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the current request is a blog-related front-end context.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool
 */
function bb_blog_is_blog_context() {
	return is_home() || is_singular( 'post' ) || is_author() || is_category() || is_tag() || is_date();
}
