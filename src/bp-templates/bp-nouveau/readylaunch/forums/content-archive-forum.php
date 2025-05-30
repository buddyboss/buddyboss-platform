<?php

/**
 * Archive Forum Content Part
 *
 * @package BuddyBoss\Theme
 */

?>

<div id="bbpress-forums" class="bb-forums-archive-page">

	<?php
	// Remove subscription link if forum assigned to the group.
	if ( ! function_exists( 'bb_is_forum_group_forum' ) || ! bb_is_forum_group_forum( bbp_get_forum_id() ) ) {
		bbp_forum_subscription_link();
	}
	?>

	<?php do_action( 'bbp_template_before_forums_index' ); ?>

	<div class="bb-rl-secondary-header flex items-center">
		<div class="bb-rl-entry-heading">
			<h2><?php esc_html_e( 'Forums', 'buddyboss' ); ?><span class="bb-rl-heading-count">10</span></h2>
		</div>
		<div class="bb-rl-sub-ctrls flex items-center">
			<?php
			/**
			 * Fires before the display of the groups list filters.
			 *
			 * @since BuddyBoss [BBVERSION]
			 */
			do_action( 'bb_before_directory_groups_filters' );
			?>

			<div id="bb-rl-groups-scope-filters" class="component-filters clearfix">
				<div id="bb-rl-groups-scope-select" class="last filter bb-rl-scope-filter bb-rl-filter">
					<label class="bb-rl-filter-label" for="bb-rl-groups-scope-options">
						<span>Type</span>
					</label>
					<div class="select-wrap">
						<select id="bb-rl-forum-scope-options" data-bp-forum-scope-filter="forums" data-dropdown-align="true" data-select2-id="bb-rl-forum-scope-options" tabindex="-1" class="select2-hidden-accessible" aria-hidden="true">
							<option value="forum">Forum</option>
							<option value="Discussion">Discussion</option>
						</select>
					</div>
				</div>
			</div> 
		</div>
	</div>

	<div class="bb-rl-container-inner">
		<?php if ( bbp_has_forums() ) : ?>

			<?php bbp_get_template_part( 'loop', 'forums' ); ?>

			<?php bbp_get_template_part( 'pagination', 'forums' ); ?>

		<?php else : ?>

			<?php bbp_get_template_part( 'feedback', 'no-forums' ); ?>

			<?php endif; ?>
		<?php do_action( 'bbp_template_after_forums_index' ); ?>
	</div>

</div>
