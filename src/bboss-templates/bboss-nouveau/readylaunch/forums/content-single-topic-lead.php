<?php
/**
 * Single Topic Lead Content Template
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$forum_id     = bboss_bbp_get_topic_forum_id();
$group_avatar = '';
if ( function_exists( 'bboss_bbp_is_forum_group_forum' ) && bboss_bbp_is_forum_group_forum( $forum_id ) ) {
	$group_ids = bboss_bbp_get_forum_group_ids( $forum_id );

	if ( ! empty( $group_ids ) && function_exists( 'bboss_groups_get_group' ) ) {
		$group_id = $group_ids[0]; // Get the first group ID.
		$group    = bboss_groups_get_group( $group_id );

		if ( $group && ! empty( $group->name ) ) {
			// Get group avatar.
			if ( function_exists( 'bboss_core_fetch_avatar' ) && ! bboss_disable_group_avatar_uploads() ) {
				$group_avatar = bboss_core_fetch_avatar(
					array(
						'item_id' => $group_id,
						'object'  => 'group',
						'type'    => 'thumb',
						'width'   => 20,
						'height'  => 20,
						'html'    => true,
						'alt'     => sprintf(
							/* translators: %s is the group name */
							__( '%s logo', 'buddyboss' ),
							$group->name
						),
						'class'   => 'bb-rl-group-avatar',
					)
				);
			}
		}
	}
}
?>

<?php do_action( 'bbp_template_before_lead_topic' ); ?>
<div class="bb-rl-topic-header">

	<div class="bb-rl-topic-header-meta">
		<?php if ( ! bboss_bbp_is_topic_open() ) { ?>
				<div class="bb-rl-topic-status-closed">
					<?php esc_html_e( 'Closed', 'buddyboss' ); ?>
				</div>
		<?php } ?>
		<?php
			$empty       = true;
			$topic_links = '';

			$args = array(
				'sep'    => '',
				'before' => '',
				'after'  => '',
				'links'  => array(
					'edit'  => bboss_bbp_get_topic_edit_link( array( 'id' => bboss_bbp_get_topic_id() ) ),
					'close' => bboss_bbp_get_topic_close_link( array( 'id' => bboss_bbp_get_topic_id() ) ),
					'stick' => bboss_bbp_get_topic_stick_link( array( 'id' => bboss_bbp_get_topic_id() ) ),
					'merge' => bboss_bbp_get_topic_merge_link( array( 'id' => bboss_bbp_get_topic_id() ) ),
					'trash' => bboss_bbp_get_topic_trash_link( array( 'id' => bboss_bbp_get_topic_id() ) ),
					'spam'  => bboss_bbp_get_topic_spam_link( array( 'id' => bboss_bbp_get_topic_id() ) ),
				),
			);

			if ( bboss_is_active( 'moderation' ) && function_exists( 'bboss_bbp_get_topic_report_link' ) ) {
				$report_link             = bboss_bbp_get_topic_report_link( array( 'id' => bboss_bbp_get_topic_id() ) );
				$args['links']['report'] = str_replace( 'button', '', $report_link );
			}

			$topic_links = bboss_bbp_get_topic_admin_links( $args );
			if ( ! empty( wp_strip_all_tags( $topic_links ) ) ) {
				unset( $args['before'] );
				unset( $args['after'] );
				$topic_links = bboss_bbp_get_topic_admin_links( $args );
				$empty       = false;
			}
			if ( ! $empty ) {
				?>
				<div class="bb_more_options forum-dropdown bb-rl-context-wrap">
					<a href="#" class="bb-rl-context-btn bb_more_options_action bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'More Options', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'More Options', 'buddyboss' ); ?>">
						<i class="bb-icons-rl-dots-three"></i>
					</a>
					<div class="bb_more_options_list bb_more_dropdown bb-rl-context-dropdown">
						<div class="generic-button bb-rl-context-item">
						<?php
							do_action( 'bbp_theme_before_reply_admin_links' );
							echo wp_kses_post( $topic_links );
							do_action( 'bbp_theme_after_reply_admin_links' );
						?>
						</div>
					</div>
				</div>
			<?php } ?>
	</div>

	<div class="bb-rl-topic-started-in">
		<?php
		/* translators: %1$s is the group avatar, %2$s is the forum title, %3$s is the forum permalink */
		printf( '<a href="%1$s">%2$s%3$s</a>', esc_url( bboss_bbp_get_forum_permalink( bboss_bbp_get_topic_forum_id() ) ), wp_kses_post( $group_avatar ), esc_html( bboss_bbp_get_forum_title( bboss_bbp_get_topic_forum_id() ) ) );
		?>
	</div>

	<div class="bb-rl-topic-author">
		<div class="bb-rl-topic-avatar">
			<?php bboss_bbp_topic_author_link( array( 'size' => '48' ) ); ?>
		</div>
		<div class="bb-rl-topic-author-details">
			<div class="bb-rl-topic-author-name">
			<?php
				$bbp_topic_id = bboss_bbp_get_topic_id();
				bboss_bbp_author_link(
					array(
						'post_id' => $bbp_topic_id,
						'type'    => 'name',
					)
				);
				?>
			</div>
			<div class="bb-rl-topic-time">
				<?php bboss_bbp_topic_post_date( $bbp_topic_id ); ?>
			</div>
		</div>
	</div>
	<h2 class="bb-rl-topic-title">
		<?php bboss_bbp_topic_title(); ?>
	</h2>
	<div class="bb-rl-topic-content">
		<?php bboss_bbp_topic_content(); ?>
	</div>
	<input type="hidden" name="bbp_topic_excerpt" id="bbp_topic_excerpt" value="<?php bboss_bbp_reply_excerpt( bboss_bbp_get_topic_forum_id(), 50 ); ?>"/>
	<div class="bb-rl-topic-footer">
		<?php bboss_bbp_topic_tag_list(); ?>

		<div class="bb-rl-topic-stats">
			<div class="bb-rl-topic-voice-count"><i class="bb-icons-rl-user"></i> <?php bboss_bbp_topic_voice_count(); ?></div>
			<div class="bb-rl-topic-reply-count"><i class="bb-icons-rl-chat"></i> <?php bboss_bbp_show_lead_topic() ? bboss_bbp_topic_reply_count() : bboss_bbp_topic_post_count(); ?></div>
		</div>

		<div class="bb-rl-topic-actions">
			<div class="bb-rl-topic-favorite-link-wrap">
				<?php
				if ( bboss_bbp_is_favorites_active() ) {
					?>
						<p class="bb-topic-favorite-link-wrap">
							<?php
							echo wp_kses_post( bboss_bbp_get_topic_favorite_link( array( 'before' => '' ) ) );
							?>
						</p>
						<?php
				}
				?>
			</div>
			<div class="bb-rl-topic-subscription-link-wrap">
				<?php
					echo wp_kses_post( bboss_bbp_get_topic_subscription_link( array( 'before' => '' ) ) );
				?>
			</div>
			<div class="bb-rl-topic-reply-link-wrap">
				<?php
				bboss_bbp_topic_reply_link();
				if ( ! bboss_bbp_current_user_can_access_create_reply_form() && ! bboss_bbp_is_topic_closed() && ! bboss_bbp_is_forum_closed( bboss_bbp_get_topic_forum_id() ) && ! is_user_logged_in() ) {
					?>
						<a href="<?php echo esc_url( wp_login_url() ); ?>" class="bbp-topic-login-link bb-style-primary-bgr-color bb-style-border-radius"><?php esc_html_e( 'Log In to Reply', 'buddyboss' ); ?></a>
				<?php } ?>
			</div>
		</div><!-- .bb-rl-forum-actions -->
	</div>
</div>

<?php do_action( 'bbp_template_after_lead_topic' ); ?>
