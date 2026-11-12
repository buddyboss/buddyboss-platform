<?php
/**
 * Replies Loop - Single Reply Template
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div id="post-<?php bboss_bbp_reply_id(); ?>"
	<?php
	bboss_bbp_reply_class(
		bboss_bbp_get_reply_id(),
		array(
			'bb-rl-forum-reply-list-item',
			'scrubberpost',
		)
	);
	?>
	data-date="<?php echo esc_attr( get_post_time( 'F Y', false, bboss_bbp_get_reply_id(), true ) ); ?>">

	<div class="flex items-center bb-rl-reply-header">

		<div class="bb-rl-reply-author-avatar item-avatar">
			<?php
			$args = array( 'type' => 'avatar' );
			echo bboss_bbp_get_reply_author_link( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped bbPress author link incl. get_avatar() output; wp_kses_post() strips the avatar srcset.
			?>
		</div><!-- .bbp-reply-author -->

		<div class="bb-rl-reply-author-info">
			<h3>
				<?php
				$args = array( 'type' => 'name' );
				echo bboss_bbp_get_reply_author_link( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped bbPress author link incl. get_avatar() output; wp_kses_post() strips the avatar srcset.
				?>
			</h3>
			<span class="bb-rl-timestamp"><?php bboss_bbp_reply_post_date(); ?></span>

			<?php if ( bboss_bbp_is_single_user_replies() ) : ?>

				<span class="bbp-header">
				<?php esc_html_e( 'in reply to: ', 'buddyboss-platform' ); ?>
					<a class="bbp-topic-permalink"
						href="<?php bboss_bbp_topic_permalink( bboss_bbp_get_reply_topic_id() ); ?>"><?php bboss_bbp_topic_title( bboss_bbp_get_reply_topic_id() ); ?></a>
				</span>

			<?php endif; ?>

		</div>

		<?php
		/**
		 * Checked bboss_bbp_get_reply_admin_links() is empty or not if links not return then munu dropdown will not show
		 */
		if ( is_user_logged_in() && ! empty( wp_strip_all_tags( bboss_bbp_get_reply_admin_links() ) ) ) {
			?>
			<div class="bb-rl-reply-meta">
				<div class="bb-rl-more-actions bb-reply-actions bb-rl-dropdown-wrap">
					<?php
					$empty       = false;
					$topic_links = '';
					$reply_links = '';
					// If post is a topic, print the topic admin links instead.
					if ( bboss_bbp_is_topic( bboss_bbp_get_reply_id() ) ) {
						$args = array(
							'links' => array(
								'edit'  => bboss_bbp_get_topic_edit_link( array( 'id' => bboss_bbp_get_topic_id() ) ),
								'close' => bboss_bbp_get_topic_close_link( array( 'id' => bboss_bbp_get_topic_id() ) ),
								'stick' => bboss_bbp_get_topic_stick_link( array( 'id' => bboss_bbp_get_topic_id() ) ),
								'merge' => bboss_bbp_get_topic_merge_link( array( 'id' => bboss_bbp_get_topic_id() ) ),
								'trash' => bboss_bbp_get_topic_trash_link( array( 'id' => bboss_bbp_get_topic_id() ) ),
								'spam'  => bboss_bbp_get_topic_spam_link( array( 'id' => bboss_bbp_get_topic_id() ) ),
							),
						);

						$topic_links = bboss_bbp_get_topic_admin_links( $args );
						if ( '' === wp_strip_all_tags( $topic_links ) ) {
							$empty = true;
						}
						// If post is a reply, print the reply admin links instead.
					} else {
						$args = array(
							'links' => array(
								'edit'  => bboss_bbp_get_reply_edit_link( array( 'id' => bboss_bbp_get_reply_id() ) ),
								'move'  => bboss_bbp_get_reply_move_link( array( 'id' => bboss_bbp_get_reply_id() ) ),
								'split' => bboss_bbp_get_topic_split_link( array( 'id' => bboss_bbp_get_reply_id() ) ),
							),
						);

						if ( bboss_is_active( 'moderation' ) && function_exists( 'bboss_bbp_get_reply_report_link' ) ) {
							$args['links']['report'] = bboss_bbp_get_reply_report_link( array( 'id' => bboss_bbp_get_reply_id() ) );
						}

						$args['links']['spam']  = bboss_bbp_get_reply_spam_link( array( 'id' => bboss_bbp_get_reply_id() ) );
						$args['links']['trash'] = bboss_bbp_get_reply_trash_link( array( 'id' => bboss_bbp_get_reply_id() ) );

						$reply_links = bboss_bbp_get_reply_admin_links( $args );
						if ( '' === wp_strip_all_tags( $reply_links ) ) {
							$empty = true;
						}
					}

					$parent_class = '';
					if ( $empty ) {
						$parent_class = 'bb-rl-no-actions';
					} else {
						$parent_class = 'bb-rl-actions';
					}
					?>
					<div class="bb_more_options forum-dropdown bb-rl-context-wrap <?php echo esc_attr( $parent_class ); ?>">
						<?php
						// If post is a topic, print the topic admin links instead.
						if ( bboss_bbp_is_topic( bboss_bbp_get_reply_id() ) ) {
							echo wp_kses_post( bboss_bbp_get_topic_reply_link() );
							// If post is a reply, print the reply admin links instead.
						} else {
							echo wp_kses_post( bboss_bbp_get_reply_to_link() );
						}
						if ( ! $empty ) {
							?>
							<a href="#" class="bb-rl-context-btn bb_more_options_action bp-tooltip" data-balloon-pos="up"
								data-balloon="<?php esc_attr_e( 'More actions', 'buddyboss-platform' ); ?>" aria-label="<?php esc_attr_e( 'More actions', 'buddyboss-platform' ); ?>"><i
										class="bb-icons-rl-dots-three"></i></a>
							<ul class="bb_more_options_list bb_more_dropdown bb-rl-context-dropdown">
								<li>
									<?php
									do_action( 'bbp_theme_before_reply_admin_links' );
									echo $topic_links; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo $reply_links; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									do_action( 'bbp_theme_after_reply_admin_links' );
									?>
								</li>
							</ul>
							<?php
						}
						?>
					</div>
				</div>
			</div><!-- .bbp-meta -->
			<?php
		}

		/**
		 * Logged-out (anonymous) users can still post a threaded reply when the
		 * forum allows anonymous posting. The reply-link helpers already gate on
		 * bboss_bbp_current_user_can_publish_replies(), so build the link first and only
		 * render the "Reply" button when a link is actually returned.
		 *
		 * @since BuddyBoss 3.6.2
		 */
		if ( ! is_user_logged_in() ) {

			// If post is a topic, print the topic reply link, otherwise the reply-to link.
			if ( bboss_bbp_is_topic( bboss_bbp_get_reply_id() ) ) {
				$anonymous_reply_link = bboss_bbp_get_topic_reply_link();
			} else {
				$anonymous_reply_link = bboss_bbp_get_reply_to_link();
			}

			if ( ! empty( wp_strip_all_tags( $anonymous_reply_link ) ) ) {
				?>
				<div class="bb-rl-reply-meta">
					<div class="bb-rl-more-actions bb-reply-actions bb-rl-dropdown-wrap">
						<div class="bb_more_options forum-dropdown bb-rl-context-wrap bb-rl-no-actions">
							<?php echo $anonymous_reply_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>
				</div><!-- .bbp-meta -->
				<?php
			}
		}
		?>

	</div>

	<div class="bbp-after-author-hook">
		<?php do_action( 'bbp_theme_after_reply_author_details' ); ?>
	</div>

	<div class="bb-rl-reply-content">

		<?php do_action( 'bbp_theme_before_reply_content' ); ?>

		<?php bboss_bbp_reply_content(); ?>

		<?php do_action( 'bbp_theme_after_reply_content' ); ?>

	</div><!-- .bbp-reply-content -->

</div><!-- .reply -->
