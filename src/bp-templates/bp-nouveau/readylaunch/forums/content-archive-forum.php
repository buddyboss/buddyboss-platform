<?php
/**
 * Archive Forum Content Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div id="bbpress-forums" class="bb-forums-archive-page">

	<?php
	// Remove subscription link if forum assigned to the group.
	if ( ! function_exists( 'bb_is_forum_group_forum' ) || ! bb_is_forum_group_forum( bbp_get_forum_id() ) ) {
		bbp_forum_subscription_link();
	}
	?>

	<div class="bb-rl-secondary-header flex items-center">
		<div class="bb-rl-entry-heading">
			<?php
			// Determine post status based on user capabilities.
			if ( current_user_can( 'read_hidden_forums' ) ) {
				$post_status = array( 'publish', 'private', 'hidden' );
			} elseif ( current_user_can( 'read_private_forums' ) ) {
				$post_status = array( 'publish', 'private' );
			} else {
				$post_status = array( 'publish' );
			}

			$forums_args = array(
				'post_type'   => bbp_get_forum_post_type(),
				'post_status' => $post_status,
				'numberposts' => -1,
				'fields'      => 'ids',
			);

			$forums       = get_posts( $forums_args );
			$total_forums = ! empty( $forums ) ? count( $forums ) : 0;
			?>
			<h2><?php esc_html_e( 'Forums', 'buddyboss' ); ?> <span class="bb-rl-heading-count"><?php echo esc_html( $total_forums ); ?></span></h2>
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

							<option value="<?php echo esc_url( $discussion_url ); ?>"><?php esc_html_e( 'Discussions', 'buddyboss' ); ?></option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="bb-rl-container-inner">

		<?php do_action( 'bbp_template_before_forums_index' ); ?>

		<?php if ( bbp_has_forums() ) : ?>

			<?php bbp_get_template_part( 'loop', 'forums' ); ?>

			<?php bbp_get_template_part( 'pagination', 'forums' ); ?>

		<?php else : ?>

			<?php bbp_get_template_part( 'feedback', 'no-forums' ); ?>

			<?php endif; ?>
		<?php do_action( 'bbp_template_after_forums_index' ); ?>
	</div>

</div>
