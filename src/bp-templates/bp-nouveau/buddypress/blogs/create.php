<?php
/**
 * The template for BuddyBoss - Blogs Create
 *
 * This template can be overridden by copying it to yourtheme/buddypress/blogs/create.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_blogs_create_hook( 'before', 'content_template' );
bboss_nouveau_template_notices();
bboss_nouveau_blogs_create_hook( 'before', 'content' );

if ( bboss_blog_signup_enabled() ) :
	bboss_show_blog_signup_form();
else :
	bboss_nouveau_user_feedback( 'blogs-no-signup' );
endif;

bboss_nouveau_blogs_create_hook( 'after', 'content' );
bboss_nouveau_blogs_create_hook( 'after', 'content_template' );
