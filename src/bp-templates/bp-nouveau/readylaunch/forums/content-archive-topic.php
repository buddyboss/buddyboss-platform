<?php
/**
 * Archive Topic Content Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums" class="bb-forums-topics-page">

	<div class="bb-rl-secondary-header flex items-center">
		<div class="bb-rl-entry-heading">
			<?php
			// Get total discussions count.
			$stats             = bbp_get_statistics();
			$total_discussions = isset( $stats['topic_count_int'] ) ? $stats['topic_count_int'] : 0;
			?>
			<h2><?php esc_html_e( 'Discussions', 'buddyboss' ); ?> <span class="bb-rl-heading-count"><?php echo esc_html( $total_discussions ); ?></span></h2>
		</div>
		<div class="bb-rl-sub-ctrls flex items-center">
			<?php
			/**
			 * Fires before the display of the groups list filters.
			 *
			 * @since BuddyBoss 2.9.00
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
							<?php
							$forum_page = bbp_get_page_by_path( bbp_get_root_slug() );
							if ( ! empty( $forum_page ) ) {
								$forum_url   = get_permalink( $forum_page->ID );
								$forum_title = $forum_page->post_title;
							} else {
								$forum_url   = get_post_type_archive_link( bbp_get_forum_post_type() );
								$forum_title = esc_html__( 'Forums', 'buddyboss' );
							}
							if ( ! empty( $forum_url ) && ! empty( $forum_title ) ) {
								echo '<option value="' . esc_url( $forum_url ) . '">' . esc_html( $forum_title ) . '</option>';
							}

							// Add a default option for Discussions.
							$discussion_url = get_post_type_archive_link( bbp_get_topic_post_type() );
							?>

							<option value="<?php echo esc_url( $discussion_url ); ?>" <?php selected( is_post_type_archive( bbp_get_topic_post_type() ) ); ?>><?php esc_html_e( 'Discussions', 'buddyboss' ); ?></option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="bb-rl-container-inner">
		<div class="bb-rl-forums-container-inner">

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

</div>
