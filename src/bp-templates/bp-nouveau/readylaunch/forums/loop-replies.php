<?php
/**
 * Replies Loop Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$topic_id = bbp_get_topic_id();
?>

<?php do_action( 'bbp_template_before_replies_loop' ); ?>

<ul id="topic-<?php echo esc_attr( $topic_id ); ?>-replies"
	class="bb-rl-forums-items bb-rl-single-forum-list bb-rl-single-reply-list">

	<?php
	if ( ! empty( $topic_id ) && ! bbp_show_lead_topic() ) {
		?>
		<li class="bb-rl-item-wrap bb-rl-header-item align-items-center no-hover-effect">

			<div class="item flex-1">
				<div class="bb-rl-topic-header">
					<div class="bb-rl-topic-header-meta">
						<?php if ( ! bbp_show_lead_topic() && is_user_logged_in() ) : ?>
							<?php if ( function_exists( 'bp_is_active' ) && bp_is_active( 'moderation' ) && function_exists( 'bbp_get_topic_report_link' ) && bbp_get_topic_report_link( array( 'id' => get_the_ID() ) ) ) { ?>
								<div class="bb_more_options forum-dropdown bb-rl-context-wrap">
									<a href="#" class="bb-rl-context-btn bb_more_options_action bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="More Options">
										<i class="bb-icons-rl-dots-three"></i>
									</a>
									<div class="bb_more_options_list bb_more_dropdown bb-rl-context-dropdown">
										<div class="generic-button bb-rl-context-item">
											<?php echo wp_kses_post( bbp_get_topic_report_link( array( 'id' => get_the_ID() ) ) ); ?>
										</div>
									</div>
								</div>
							<?php } ?>
						<?php endif; ?>
					</div>
					<?php
					if ( ! empty( bbp_get_topic_forum_title() ) ) {

						$group_ids   = bbp_get_forum_group_ids( bbp_get_topic_forum_id() );
						$group_id    = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );
						$forum_title = ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) && $group_id ) ? bp_get_group_name( groups_get_group( $group_id ) ) : bbp_get_topic_forum_title();

						?>
						<div class="action bs-forums-meta flex align-items-center">
							<span class="color bs-meta-item forum-label bb-rl-topic-started-in">
								<a href="<?php bbp_forum_permalink( bbp_get_topic_forum_id() ); ?>"><?php echo esc_html( $forum_title ); ?></a>
							</span>
						</div>
					<?php } ?>
					<div class="item-title">
						<h1 class="bb-rl-topic-title"><?php bbp_reply_topic_title( bbp_get_reply_id() ); ?></h1>
					</div>

					<div class="item-meta">
						<div class="bb-rl-topic-footer">
							<div class="bb-rl-topic-stats">
								<?php
								$topic_reply_count = bbp_get_topic_reply_count( $topic_id );
								?>
								<div class="bb-rl-topic-voice-count">
									<i class="bb-icons-rl-user"></i> <span class="bs-voices"><?php bbp_topic_voice_count(); ?>
								</div>
								<div class="bb-rl-topic-reply-count">
									<i class="bb-icons-rl-chat"></i> <span class="bs-replies"><?php echo $topic_reply_count; ?></span>
								</div>
							</div>
							<div class="bb-rl-topic-actions">
								<div class="bb-rl-topic-favorite-link-wrap">
									<?php
									if ( bbp_is_favorites_active() ) {
										?>
											<p class="bb-topic-favorite-link-wrap">
												<?php
												echo wp_kses_post( bbp_get_topic_favorite_link( array( 'before' => '' ) ) );
												?>
											</p>
											<?php
									}
									?>
								</div>
								<div class="bb-rl-topic-subscription-link-wrap">
									<?php
										echo wp_kses_post( bbp_get_topic_subscription_link( array( 'before' => '' ) ) );
									?>
								</div>
								<div class="bb-rl-topic-reply-link-wrap">
									<?php
									bbp_topic_reply_link();
									if ( ! bbp_current_user_can_access_create_reply_form() && ! bbp_is_topic_closed() && ! bbp_is_forum_closed( bbp_get_topic_forum_id() ) && ! is_user_logged_in() ) {
										?>
											<a href="<?php echo esc_url( wp_login_url() ); ?>" class="bbp-topic-login-link bb-style-primary-bgr-color bb-style-border-radius"><?php esc_html_e( 'Log In to Reply', 'buddyboss' ); ?></a>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				$terms = bbp_get_form_topic_tags();
				if ( $terms && bbp_allow_topic_tags() ) {
					$tags_arr = explode( ', ', $terms );
					$html     = '';
					foreach ( $tags_arr as $topic_tag ) {
						$html .= '<li><a href="' . bbp_get_topic_tag_link( $topic_tag ) . '">' . $topic_tag . '</a></li>';
					}
					?>
					<div class="item-tags">
						<i class="bb-icon-l bb-icon-tag"></i>
						<ul>
							<?php echo wp_kses_post( rtrim( $html, ',' ) ); ?>
						</ul>
					</div>
					<?php
				} else {
					?>
					<div class="item-tags" style="display: none;">
						<i class="bb-icon-l bb-icon-tag"></i>
					</div>
					<?php
				}
				remove_filter( 'bbp_get_reply_content', 'bbp_reply_content_append_revisions', 99, 2 );
				?>
				<input type="hidden" name="bbp_topic_excerpt" id="bbp_topic_excerpt" value="<?php bbp_reply_excerpt( $topic_id, 50 ); ?>"/>
				<?php
				add_filter( 'bbp_get_reply_content', 'bbp_reply_content_append_revisions', 99, 2 );
				?>
			</div>
		</li><!-- .bbp-header -->
		<?php
	}

	if ( bbp_thread_replies() ) :
		bbp_list_replies();
	elseif ( bbp_replies() ) :
		while ( bbp_replies() ) :
			bbp_the_reply();
			?>
			<li><?php bbp_get_template_part( 'loop', 'single-reply' ); ?></li>

			<?php
		endwhile;
	endif;
	?>

</ul><!-- #topic-<?php bbp_topic_id(); ?>-replies -->

<?php do_action( 'bbp_template_after_replies_loop' ); ?>
