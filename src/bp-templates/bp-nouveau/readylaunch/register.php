<?php
/**
 * The layout for register templates.
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

wp_head();
wp_enqueue_style( 'bb-rl-login-fonts', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/assets/fonts/fonts.css' );
wp_enqueue_style( 'bb-rl-login-style', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/css/login.css' );
wp_enqueue_style( 'bb-rl-login-style-icons', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl.min.css' );

bp_get_template_part( 'common/header-register' );

if ( have_posts() ) :
	/* Start the Loop */
	while ( have_posts() ) :
		the_post();

		the_content();
	endwhile;
endif;

wp_footer();

