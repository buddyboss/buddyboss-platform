<?php
/**
 * ReadyLaunch - The template for activity feed comment.
 *
 * This template handles the display of individual activity comments
 * including avatar, content, reactions, and reply functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_activity_hook( 'before', 'comment_entry' );

$activity_comment_id = bp_get_activity_comment_id();
?>
	<li id="bb-rl-acomment-<?php echo esc_attr( $activity_comment_id ); ?>" class="<?php bp_activity_comment_css_class(); ?>" data-bp-activity-comment-id="<?php echo esc_attr( $activity_comment_id ); ?>" data-bp-timestamp="<?php bb_nouveau_activity_comment_timestamp(); ?>" data-bp-activity-comment="<?php bb_nouveau_edit_activity_comment_data(); ?>">
		<div id="bb-rl-acomment-display-<?php echo esc_attr( $activity_comment_id ); ?>" class="bb-rl-acomment-display">
			<?php bb_nouveau_activity_comment_bubble_buttons(); ?>
			<div class="bb-rl-acomment_inner">
				<div class="bb-rl-acomment-avatar bb-rl-item-avatar">
					<a href="<?php echo esc_url( bp_get_activity_comment_user_link() ); ?>">
						<?php
						bp_activity_avatar(
							array(
								'type'    => 'thumb',
								'user_id' => bp_get_activity_comment_user_id(),
							)
						);
						?>
					</a>
				</div>
				<div class="bb-rl-acomment-content_wrap">
					<div class="bb-rl-acomment-content_block">
						<div class="bb-rl-acomment-meta">
							<?php bp_nouveau_activity_comment_action(); ?>
						</div>
						<div class="bb-rl-acomment-meta__time">
							<?php bp_nouveau_activity_comment_meta(); ?>
						</div>
						<div class="bb-rl-acomment-content">
							<?php
							bp_activity_comment_content();
							do_action( 'bp_activity_after_comment_content', $activity_comment_id );
							?>
						</div>
					</div>
					<div class="bb-rl-acomment-foot-actions">
						<?php bp_nouveau_activity_comment_buttons( array( 'container' => 'div' ) ); ?>
						<div class="bb-rl-comment-reactions">
							<?php
							if ( bb_is_reaction_activity_comments_enabled() ) {
								echo wp_kses_post( bb_get_activity_post_user_reactions_html( $activity_comment_id, 'activity_comment' ) );
							}
							if ( bp_activity_can_comment_reply() ) {
								$activity_id   = bp_get_activity_id();
								$replies_count = BP_Activity_Activity::bb_get_all_activity_comment_children_count(
									array(
										'spam'     => 'ham_only',
										'activity' => bp_activity_current_comment(),
									)
								);
								$reply_count   = $replies_count['all_child_count'] ?? 0;

								$activity_state_comment_class['activity_state_comment_class'] = 'activity-state-comments';
								if ( $reply_count > 0 ) {
									$activity_state_comment_class['has-comments'] = 'has-comments';
								}
								$activity_state_class = apply_filters( 'bp_nouveau_get_activity_comment_buttons_activity_state', $activity_state_comment_class, $activity_id );
								if ( $reply_count > 0 ) {
									?>
									<a href="#" class="<?php echo esc_attr( trim( implode( ' ', $activity_state_class ) ) ); ?>">
										<span class="acomments-count" data-comments-count="<?php echo esc_attr( $reply_count ); ?>">
											<?php
											if ( $reply_count > 1 ) {
												/* translators: %d: number of activity replies */
												echo esc_html( sprintf( _x( '%d replies', 'placeholder: activity replies count', 'buddyboss' ), $reply_count ) );
											} else {
												/* translators: %d: number of activity reply */
												echo esc_html( sprintf( _x( '%d reply', 'placeholder: activity reply count', 'buddyboss' ), $reply_count ) );
											}
											?>
										</span>
									</a>
									<?php
								}
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="bb-rl-acomment-edit-form-<?php echo esc_attr( $activity_comment_id ); ?>" class="bb-rl-acomment-edit-form"></div>
		<?php
		$args = array(
			'limit_comments'     => isset( $args['limit_comments'] ) && true === $args['limit_comments'],
			'comment_load_limit' => isset( $args['show_replies'] ) && false === $args['show_replies'] ? 0 : bb_get_activity_comment_loading(),
		);
		bp_nouveau_activity_recurse_comments( bp_activity_current_comment(), $args );
		?>
	</li>
<?php
bp_nouveau_activity_hook( 'after', 'comment_entry' );
