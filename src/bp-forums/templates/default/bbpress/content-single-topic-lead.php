<?php

/**
 * Single Topic Lead Content Part
 *
 * @package BuddyBoss\Theme
 */

$topic_id = bbp_get_topic_id();
?>

<?php do_action( 'bbp_template_before_lead_topic' ); ?>

<li class="bs-item-wrap bs-header-item align-items-center no-hover-effect">
	<div class="item flex-1">
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
			<?php
		}
		?>
		<div class="item-title">
			<h1 class="bb-reply-topic-title"><?php bbp_reply_topic_title( bbp_get_reply_id() ); ?></h1>

			<?php if ( ! bbp_show_lead_topic() && is_user_logged_in() ) : ?>
				<div class="bb-topic-states push-right">
					<?php
					/**
					 * Checked bbp_get_topic_close_link() is empty or not
					 */
					if ( ! empty( bbp_get_topic_close_link() ) ) {
						if ( bbp_is_topic_open() ) {
							?>
							<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Close', 'buddyboss-theme' ); ?>">
								<i class="bb-topic-status open"><?php bbp_topic_close_link(); ?></i>
							</span>
						<?php } else { ?>
							<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Open', 'buddyboss-theme' ); ?>">
								<i class="bb-topic-status closed"><?php bbp_topic_close_link(); ?></i>
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
							<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Unstick', 'buddyboss-theme' ); ?>">
								<i class="bb-topic-status bb-sticky sticky"><?php bbp_topic_stick_link(); ?></i>
							</span>
							<?php
						} else {
							?>
							<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Sticky', 'buddyboss-theme' ); ?>">
								<i class="bb-topic-status bb-sticky unsticky"><?php bbp_topic_stick_link(); ?></i>
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
							<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Unstick', 'buddyboss-theme' ); ?>">
								<i class="bb-topic-status bb-super-sticky super-sticky"><?php bbp_topic_stick_link(); ?></i>
							</span>
							<?php
						} elseif ( ( ! bp_is_group() && ! bp_is_group_forum_topic() ) && ! bbp_is_topic_sticky() ) {
							?>
							<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Super Sticky', 'buddyboss-theme' ); ?>">
								<i class="bb-topic-status bb-super-sticky super-sticky unsticky"><?php bbp_topic_stick_link(); ?></i>
							</span>
							<?php
						}
					}
					if ( bbp_is_favorites_active() ) {
						$is_fav = bbp_is_user_favorite( get_current_user_id(), bbp_get_topic_id() );
						if ( $is_fav ) {
							?>
							<span class="bb-favorite-wrap" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Unfavorite', 'buddyboss-theme' ); ?>" data-unfav="<?php esc_attr_e( 'Unfavorite', 'buddyboss-theme' ); ?>" data-fav="<?php esc_attr_e( 'Favorite', 'buddyboss-theme' ); ?>">
								<i class="bb-topic-status bb-favorite-status favorited"><?php bbp_user_favorites_link(); ?></i>
							</span>
							<?php
						} else {
							?>
							<span class="bb-favorite-wrap" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Favorite', 'buddyboss-theme' ); ?>" data-unfav="<?php esc_attr_e( 'Unfavorite', 'buddyboss-theme' ); ?>" data-fav="<?php esc_attr_e( 'Favorite', 'buddyboss-theme' ); ?>">
								<i class="bb-topic-status bb-favorite-status unfavorited"><?php bbp_user_favorites_link(); ?></i>
							</span>
							<?php
						}
					}
					?>

					<?php if ( function_exists( 'bp_is_active' ) && bp_is_active( 'moderation' ) && function_exists( 'bbp_get_topic_report_link' ) && bbp_get_topic_report_link( array( 'id' => get_the_ID() ) ) ) { ?>
						<div class="forum_single_action_wrap">
							<span class="forum_single_action_more-wrap" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'More Options', 'buddyboss-theme' ); ?>">
								<i class="bb-icon bb-icon-menu-dots-v"></i>
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
				<?php esc_html_e( 'Posted by', 'buddyboss-theme' ); ?>
				<span class="bbp-topic-freshness-author">
					<?php
					bbp_author_link(
						array(
							'post_id' => $topic_id,
							'type'    => 'name',
						)
					);
					?>
				</span> <?php esc_html_e( 'on', 'buddyboss-theme' ); ?> <?php bbp_topic_post_date( $topic_id ); ?>
			</span>
		</div>

		<div class="item-description">
			<?php
			bbp_topic_content();
			?>
		</div>

		<div class="item-meta">
			<span class="bs-replied">
				<span class="bbp-topic-freshness-author"><?php
					bbp_author_link(
						array(
							'post_id' => bbp_get_topic_last_active_id(),
							'type'    => 'name',
						)
					);
					?>
				</span> <?php esc_html_e( 'replied', 'buddyboss-theme' ); ?> <?php bbp_topic_freshness_link(); ?>
			</span>
			<span class="bs-voices-wrap">
				<?php
				$voice_count       = bbp_get_topic_voice_count( $topic_id );
				$voice_text        = $voice_count > 1 ? __( 'Members', 'buddyboss-theme' ) : __( 'Member', 'buddyboss-theme' );
				$topic_reply_count = bbp_get_topic_reply_count( $topic_id );
				$topic_post_count  = bbp_get_topic_post_count( $topic_id );
				$reply_count       = bbp_get_topic_replies_link( $topic_id );
				$topic_reply_text  = '';
				?>
				<span class="bs-voices"><?php bbp_topic_voice_count(); ?> <?php echo wp_kses_post( $voice_text ); ?></span>
				<span class="bs-separator">&middot;</span>
				<span class="bs-replies">
					<?php
					if ( bbp_show_lead_topic() ) {
						bbp_topic_reply_count( $topic_id );
						$topic_reply_text = $topic_reply_count > 1 ? esc_html__( 'Replies', 'buddyboss-theme' ) : esc_html__( 'Reply', 'buddyboss-theme' );
					} else {
						bbp_topic_post_count( $topic_id );
						$topic_reply_text = $topic_post_count > 1 ? esc_html__( 'Posts', 'buddyboss-theme' ) : esc_html__( 'Post', 'buddyboss-theme' );
					}
					echo ' ' . wp_kses_post( $topic_reply_text );
					?>
				</span>
			</span>
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
				<i class="bb-icon-tag"></i>
				<ul>
					<?php echo wp_kses_post( rtrim( $html, ',' ) ); ?>
				</ul>
			</div>
			<?php
		} else {
			?>
			<div class="item-tags" style="display: none;">
				<i class="bb-icon-tag"></i>
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
<li class="bs-item-wrap bs-header-item align-items-center">
	<div class="topic-reply-count">
		<?php
		if ( bbp_show_lead_topic() ) {
			bbp_topic_reply_count( $topic_id );
			$topic_reply_text = $topic_reply_count > 1 ? esc_html__( 'Replies', 'buddyboss-theme' ) : esc_html__( 'Reply', 'buddyboss-theme' );
		} else {
			bbp_topic_post_count( $topic_id );
			$topic_reply_text = $topic_post_count > 1 ? esc_html__( 'Posts', 'buddyboss-theme' ) : esc_html__( 'Post', 'buddyboss-theme' );
		}
		echo ' ' . wp_kses_post( $topic_reply_text );
		?>
	</div>
</li>

<?php do_action( 'bbp_template_after_lead_topic' ); ?>
