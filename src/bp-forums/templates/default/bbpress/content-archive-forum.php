<?php

/**
 * Archive Forum Content Part
 *
 * @package BuddyBoss\Theme
 */

?>

<div id="bbpress-forums">

	<?php if ( bbp_allow_search() ) : ?>

		<div class="bbp-search-form">

			<?php bbp_get_template_part( 'form', 'search' ); ?>

		</div>

	<?php endif; ?>

	<?php bbp_breadcrumb(); ?>

	<?php
	// Remove subscription link if forum assigned to the group.
	if ( ! function_exists( 'bb_is_forum_group_forum' ) || ! bb_is_forum_group_forum( bbp_get_forum_id() ) ) {
		bbp_forum_subscription_link();
	}
	?>

	<?php do_action( 'bbp_template_before_forums_index' ); ?>

	<?php if ( bbp_has_forums() ) : ?>

		<?php bbp_get_template_part( 'pagination', 'forums' ); ?>

		<?php bbp_get_template_part( 'loop', 'forums' ); ?>

		<?php bbp_get_template_part( 'pagination', 'forums' ); ?>

	<?php else : ?>

		<?php bbp_get_template_part( 'feedback', 'no-forums' ); ?>

	<?php endif; ?>

	<?php do_action( 'bbp_template_after_forums_index' ); ?>

</div>
