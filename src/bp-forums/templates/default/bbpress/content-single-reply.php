<?php

/**
 * Single Reply Content Part
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums">

	<?php bboss_bbp_breadcrumb(); ?>

	<?php do_action( 'bbp_template_before_single_reply' ); ?>

	<?php if ( post_password_required() ) : ?>

		<?php bboss_bbp_get_template_part( 'form', 'protected' ); ?>

	<?php else : ?>

		<?php bboss_bbp_get_template_part( 'loop', 'single-reply' ); ?>

	<?php endif; ?>

	<?php do_action( 'bbp_template_after_single_reply' ); ?>

</div>
