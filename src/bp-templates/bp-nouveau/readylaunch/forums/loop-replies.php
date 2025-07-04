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
				<div class="item-title">
					<h1 class="bb-rl-reply-topic-title"><?php bbp_reply_topic_title( bbp_get_reply_id() ); ?></h1>

					<?php if ( ! bbp_show_lead_topic() && is_user_logged_in() ) : ?>
						<div class="bb-rl-topic-states">
							<?php
							/**
							 * Checked bbp_get_topic_close_link() is empty or not
							 */
							if ( ! empty( bbp_get_topic_close_link() ) ) {
								if ( bbp_is_topic_open() ) {
									?>
									<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Close', 'buddyboss' ); ?>">
										<i class="bb-icon-rl bb-icon-lock-alt bb-topic-status open"><?php bbp_topic_close_link(); ?></i>
									</span>
								<?php } else { ?>
									<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Open', 'buddyboss' ); ?>">
										<i class="bb-icon-rl bb-icon-lock-alt-open bb-topic-status closed"><?php bbp_topic_close_link(); ?></i>
									</span>
									<?php
								}
							}

							/**
							 * Checked bbp_get_topic_stick_link() is empty or not
							 */
							if ( ! bbp_is_topic_super_sticky( $topic_id ) && ! empty( bbp_get_topic_stick_link() ) ) {
								if ( bbp_is_topic_sticky() ) {
									?>
									<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Unstick', 'buddyboss' ); ?>">
										<i class="bb-icon-rl bb-icon-thumbtack bb-topic-status bb-sticky sticky"><?php bbp_topic_stick_link(); ?></i>
									</span>
									<?php
								} else {
									?>
									<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Sticky', 'buddyboss' ); ?>">
										<i class="bb-icon-rl bb-icon-thumbtack bb-topic-status bb-sticky unsticky"><?php bbp_topic_stick_link(); ?></i>
									</span>
									<?php
								}
							}

							/**
							 * Checked bbp_get_topic_stick_link() is empty or not
							 */
							if ( ! empty( bbp_get_topic_stick_link() ) ) {
								if ( bbp_is_topic_super_sticky( $topic_id ) ) {
									?>
									<span class="bb-topic-status-wrapper" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Unstick', 'buddyboss' ); ?>">
										<i class="bb-icon-rl bb-icon-thumbtack-star bb-topic-status bb-super-sticky super-sticky"><?php bbp_topic_stick_link(); ?></i>
									</span>
									<?php
								} elseif ( ( ! bp_is_group() && ! bp_is_group_forum_topic() ) && ! bbp_is_topic_sticky() ) {
									?>
									<span class="bb-topic-status-wrapper" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Super Sticky', 'buddyboss' ); ?>">
										<i class="bb-icon-rl bb-icon-thumbtack-star bb-topic-status bb-super-sticky super-sticky unsticky"><?php bbp_topic_stick_link(); ?></i>
									</span>
									<?php
								}
							}
							?>

							<?php if ( function_exists( 'bp_is_active' ) && bp_is_active( 'moderation' ) && function_exists( 'bbp_get_topic_report_link' ) && bbp_get_topic_report_link( array( 'id' => get_the_ID() ) ) ) { ?>
								<div class="forum_single_action_wrap">
									<span class="forum_single_action_more-wrap" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'More Options', 'buddyboss' ); ?>">
										<i class="bb-icon-f bb-icon-ellipsis-v"></i>
									</span>
									<div class="forum_single_action_options">
										<?php
										if ( bp_is_active( 'moderation' ) && function_exists( 'bbp_get_topic_report_link' ) ) {
											?>
											<p class="bb-topic-report-link-wrap">
												<?php echo wp_kses_post( bbp_get_topic_report_link( array( 'id' => get_the_ID() ) ) ); ?>
											</p>
											<?php
										}
										?>
									</div>
								</div><!-- .forum_single_action_wrap -->
							<?php } ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="item-meta">
					<span class="bs-replied">
						<span class="bbp-topic-freshness-author">
							<?php
							bbp_author_link(
								array(
									'post_id' => bbp_get_topic_last_active_id(),
									'size'    => 1,
								)
							);
							?>
						</span> <?php esc_html_e( 'updated', 'buddyboss' ); ?> <?php bbp_topic_freshness_link(); ?>
					</span>
					<span class="bs-voices-wrap">
						<?php
						$voice_count = bbp_get_topic_voice_count( $topic_id );
						$voice_text  = $voice_count > 1 ? __( 'Members', 'buddyboss' ) : __( 'Member', 'buddyboss' );

						$topic_reply_count = bbp_get_topic_reply_count( $topic_id );
						$topic_post_count  = bbp_get_topic_post_count( $topic_id );
						$reply_count       = bbp_get_topic_replies_link( $topic_id );
						$topic_reply_text  = '';
						?>
						<span class="bs-voices"><?php bbp_topic_voice_count(); ?> <?php echo wp_kses_post( $voice_text ); ?></span>
						<span class="bs-separator">&middot;</span>
						<span class="bs-replies">
							<?php
							bbp_topic_reply_count( $topic_id );
							$topic_reply_text = (int) $topic_reply_count > 1 ? esc_html__( 'Replies', 'buddyboss' ) : esc_html__( 'Reply', 'buddyboss' );

							echo wp_kses_post( $topic_reply_text );
							?>
						</span>
					</span>

					<?php
					if ( ! empty( bbp_get_topic_forum_title() ) ) {

						$group_ids   = bbp_get_forum_group_ids( bbp_get_topic_forum_id() );
						$group_id    = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );
						$forum_title = ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) && $group_id ) ? bp_get_group_name( groups_get_group( $group_id ) ) : bbp_get_topic_forum_title();

						?>
						<div class="action bs-forums-meta flex align-items-center">
							<span class="color bs-meta-item forum-label" style="background: <?php echo esc_attr( color2rgba( textToColor( bbp_get_topic_forum_title() ), 0.6 ) ); ?>">
								<a href="<?php bbp_forum_permalink( bbp_get_topic_forum_id() ); ?>"><?php echo esc_html( $forum_title ); ?></a>
							</span>
						</div>
					<?php } ?>
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
