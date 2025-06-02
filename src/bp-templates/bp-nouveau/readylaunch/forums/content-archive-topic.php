<?php

/**
 * Archive Topic Content Part
 *
 * @package BuddyBoss\Theme
 */

?>

<div id="bbpress-forums" class="bb-forums-topics-page">

	<div class="bb-rl-secondary-header flex items-center">
		<div class="bb-rl-entry-heading">
			<?php
			// Get total discussions count
			$stats = bbp_get_statistics();
			$total_discussions = isset( $stats['topic_count_int'] ) ? $stats['topic_count_int'] : 0;
			?>
			<h2><?php esc_html_e( 'Discussions', 'buddyboss' ); ?> <span class="bb-rl-heading-count"><?php echo esc_html( $total_discussions ); ?></span></h2>
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
							<option value="Discussion" selected>Discussion</option>
						</select>
					</div>
				</div>
			</div> 
		</div>
	</div>

	<div class="bb-rl-container-inner">

		<?php do_action( 'bbp_template_before_topics_index' ); ?>
		

		<?php if ( bbp_has_topics() ) : ?>

			<?php bbp_get_template_part( 'loop', 'topics' ); ?>

			<?php bbp_get_template_part( 'pagination', 'topics' ); ?>

		<?php else : ?>

			<?php bbp_get_template_part( 'feedback', 'no-topics' ); ?>

		<?php endif; ?>

		<?php do_action( 'bbp_template_after_topics_index' ); ?>
	</div>

</div>
