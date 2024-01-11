<?php
/**
 * The template for BuddyBoss - Blogs Create
 *
 * This template can be overridden by copying it to yourtheme/buddypress/blogs/create.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

bp_nouveau_blogs_create_hook( 'before', 'content_template' );
bp_nouveau_template_notices();
bp_nouveau_blogs_create_hook( 'before', 'content' );

if ( bp_blog_signup_enabled() ) :
	bp_show_blog_signup_form();
else :
	bp_nouveau_user_feedback( 'blogs-no-signup' );
endif;

bp_nouveau_blogs_create_hook( 'after', 'content' );
bp_nouveau_blogs_create_hook( 'after', 'content_template' );
