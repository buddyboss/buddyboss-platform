<?php
/**
 * Replies Loop - Single Reply Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div id="post-<?php bbp_reply_id(); ?>"
	<?php
	bbp_reply_class(
		bbp_get_reply_id(),
		array(
			'bb-rl-forum-reply-list-item',
			'scrubberpost',
		)
	);
	?>
	data-date="<?php echo esc_attr( get_post_time( 'F Y', false, bbp_get_reply_id(), true ) ); ?>">

	<div class="flex items-center bb-rl-reply-header">

		<div class="bb-rl-reply-author-avatar item-avatar">
			<?php
			$args = array( 'type' => 'avatar' );
			echo bbp_get_reply_author_link( $args );
			?>
		</div><!-- .bbp-reply-author -->

		<div class="bb-rl-reply-author-info">
			<h3>
				<?php
				$args = array( 'type' => 'name' );
				echo bbp_get_reply_author_link( $args );
				?>
			</h3>
			<span class="bb-rl-timestamp"><?php bbp_reply_post_date(); ?></span>

			<?php if ( bbp_is_single_user_replies() ) : ?>

				<span class="bbp-header">
				<?php esc_html_e( 'in reply to: ', 'buddyboss' ); ?>
					<a class="bbp-topic-permalink"
						href="<?php bbp_topic_permalink( bbp_get_reply_topic_id() ); ?>"><?php bbp_topic_title( bbp_get_reply_topic_id() ); ?></a>
				</span>

			<?php endif; ?>

		</div>

		<?php
		/**
		 * Checked bbp_get_reply_admin_links() is empty or not if links not return then munu dropdown will not show
		 */
		if ( is_user_logged_in() && ! empty( wp_strip_all_tags( bbp_get_reply_admin_links() ) ) ) {
			?>
			<div class="bb-rl-reply-meta">
				<div class="bb-rl-more-actions bb-reply-actions bb-rl-dropdown-wrap">
					<?php
					$empty       = false;
					$topic_links = '';
					$reply_links = '';
					// If post is a topic, print the topic admin links instead.
					if ( bbp_is_topic( bbp_get_reply_id() ) ) {
						$args = array(
							'links' => array(
								'edit'  => bbp_get_topic_edit_link( array( 'id' => bbp_get_topic_id() ) ),
								'close' => bbp_get_topic_close_link( array( 'id' => bbp_get_topic_id() ) ),
								'stick' => bbp_get_topic_stick_link( array( 'id' => bbp_get_topic_id() ) ),
								'merge' => bbp_get_topic_merge_link( array( 'id' => bbp_get_topic_id() ) ),
								'trash' => bbp_get_topic_trash_link( array( 'id' => bbp_get_topic_id() ) ),
								'spam'  => bbp_get_topic_spam_link( array( 'id' => bbp_get_topic_id() ) ),
							),
						);

						$topic_links = bbp_get_topic_admin_links( $args );
						if ( '' === wp_strip_all_tags( $topic_links ) ) {
							$empty = true;
						}
						// If post is a reply, print the reply admin links instead.
					} else {
						$args = array(
							'links' => array(
								'edit'  => bbp_get_reply_edit_link( array( 'id' => bbp_get_reply_id() ) ),
								'move'  => bbp_get_reply_move_link( array( 'id' => bbp_get_reply_id() ) ),
								'split' => bbp_get_topic_split_link( array( 'id' => bbp_get_reply_id() ) ),
							),
						);

						if ( bp_is_active( 'moderation' ) && function_exists( 'bbp_get_reply_report_link' ) ) {
							$args['links']['report'] = bbp_get_reply_report_link( array( 'id' => bbp_get_reply_id() ) );
						}

						$args['links']['spam']  = bbp_get_reply_spam_link( array( 'id' => bbp_get_reply_id() ) );
						$args['links']['trash'] = bbp_get_reply_trash_link( array( 'id' => bbp_get_reply_id() ) );

						$reply_links = bbp_get_reply_admin_links( $args );
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
						if ( bbp_is_topic( bbp_get_reply_id() ) ) {
							echo bbp_get_topic_reply_link();
							// If post is a reply, print the reply admin links instead.
						} else {
							echo bbp_get_reply_to_link();
						}
						if ( ! $empty ) {
							?>
							<a href="#" class="bb-rl-context-btn bb_more_options_action bp-tooltip" data-balloon-pos="up"
								data-balloon="<?php esc_attr_e( 'More actions', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'More actions', 'buddyboss' ); ?>"><i
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
		<?php } ?>

	</div>

	<div class="bbp-after-author-hook">
		<?php do_action( 'bbp_theme_after_reply_author_details' ); ?>
	</div>

	<div class="bb-rl-reply-content">

		<?php do_action( 'bbp_theme_before_reply_content' ); ?>

		<?php bbp_reply_content(); ?>

		<?php do_action( 'bbp_theme_after_reply_content' ); ?>

	</div><!-- .bbp-reply-content -->

</div><!-- .reply -->
