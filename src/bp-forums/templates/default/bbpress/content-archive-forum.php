<?php

/**
 * Archive Forum Content Part
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums">

	<?php if ( bboss_bbp_allow_search() ) : ?>

		<div class="bbp-search-form">

			<?php bboss_bbp_get_template_part( 'form', 'search' ); ?>

		</div>

	<?php endif; ?>

	<?php bboss_bbp_breadcrumb(); ?>

	<?php
	// Remove subscription link if forum assigned to the group.
	if ( ! function_exists( 'bboss_is_forum_group_forum' ) || ! bboss_is_forum_group_forum( bboss_bbp_get_forum_id() ) ) {
		bboss_bbp_forum_subscription_link();
	}
	?>

	<?php do_action( 'bbp_template_before_forums_index' ); ?>

	<?php if ( bboss_bbp_has_forums() ) : ?>

		<?php bboss_bbp_get_template_part( 'pagination', 'forums' ); ?>

		<?php bboss_bbp_get_template_part( 'loop', 'forums' ); ?>

		<?php bboss_bbp_get_template_part( 'pagination', 'forums' ); ?>

	<?php else : ?>

		<?php bboss_bbp_get_template_part( 'feedback', 'no-forums' ); ?>

	<?php endif; ?>

	<?php do_action( 'bbp_template_after_forums_index' ); ?>

</div>
