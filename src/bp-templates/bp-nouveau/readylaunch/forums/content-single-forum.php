<?php
/**
 * Single Forum Content Part
 *
 * @package BuddyBoss\Theme
 */

?>
<div class="bb-rl-container-inner">
	<div class="bb-rl-single-forum">

		<div class="bb-rl-forums-container-inner">
			<?php
				$forum_cover_photo = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
			?>
			<div class="bb-rl-forum-single-header">
				<div class="bb-rl-forum-single-header-cover">
					<img src="<?php echo esc_url( $forum_cover_photo ); ?>" alt="<?php the_title_attribute( array( 'post' => $post->ID ) ); ?>" class="banner-img wp-post-image"/>
				</div>
				<div class="bb-rl-forum-single-header-content">
					<h1 class="entry-title"><?php esc_html( bbp_forum_title() ); ?></h1>
					<div class="bb-rl-forum-meta">
						<?php
							$forum_id = bbp_get_forum_id();
							// get discussion count
							$discussion_count = bbp_get_forum_topic_count( $forum_id );
							// get forum visibility/privacy status
							$forum_visibility = bbp_get_forum_visibility( $forum_id );
							$forum_visibilities = bbp_get_forum_visibilities( $forum_id );
							$privacy_label = isset( $forum_visibilities[ $forum_visibility ] ) ? $forum_visibilities[ $forum_visibility ] : __( 'Public', 'buddyboss' );
						?>
						<div class="bb-rl-forum-meta-item">
							<?php echo esc_html( $privacy_label ); ?>
						</div>
						<div class="bb-rl-forum-meta-item <?php echo $discussion_count == 0 ? 'bb-rl-forum-meta-item-inactive' : ''; ?>">
							<span class="bb-rl-forum-topic-count-value"><?php echo $discussion_count; ?></span>
							<span class="bb-rl-forum-topic-count-label"><?php echo _n( 'Discussion', 'Discussions', $discussion_count, 'buddyboss' ); ?></span>
						</div>
						<div class="bb-rl-forum-meta-item">
							<?php do_action( 'bbp_theme_before_forum_freshness_link' ); ?>
							<?php bbp_forum_freshness_link(); ?>
							<?php do_action( 'bbp_theme_after_forum_freshness_link' ); ?>
						</div>
					</div>
					<div class="bb-rl-forum-description">
						<?php echo wp_kses_post( bbp_get_forum_content_excerpt_view_more() ); ?>
					</div>
					<div class="bb-rl-forum-actions">
						<?php if ( bbp_is_single_forum() && ! bbp_is_forum_category() && ( bbp_current_user_can_access_create_topic_form() || bbp_current_user_can_access_anonymous_user_form() ) ) { ?>
							<a href="#new-post" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small bb-rl-new-discussion-btn" data-modal-id="bb-rl-topic-form"><i class="bb-icons-rl-plus"></i> <?php esc_html_e( 'New discussion', 'buddyboss' ); ?></a>
							<?php
							// Remove subscription link if forum assigned to the group.
							if ( ! function_exists( 'bb_is_forum_group_forum' ) || ! bb_is_forum_group_forum( bbp_get_forum_id() ) ) {
								bbp_forum_subscription_link();
							}
							?>
						<?php } ?>
					</div><!-- .bb-rl-forum-actions -->
				</div><!-- .bb-rl-forum-single-header-content -->
			</div><!-- .bb-rl-forum-single-header -->

			<?php do_action( 'bbp_template_before_single_forum' ); ?>

			<?php if ( post_password_required() ) : ?>

				<?php bbp_get_template_part( 'form', 'protected' ); ?>

			<?php else : ?>
				<?php if ( bbp_has_forums() ) : ?>
					<ul class="bb-rl-forum-tabs">
						<li data-id="bb-rl-forum-discussions" class="bb-rl-forum-tabs-item selected">
							<a href="#" id="public-message"><?php echo esc_html( 'Discussions', 'buddyboss' ); ?></a>
						</li>
						<li data-id="bb-rl-forum-subforums" class="bb-rl-forum-tabs-item">
							<a href="#" id="private-message"><?php echo esc_html( 'Sub Forums', 'buddyboss' ); ?></a>
						</li>
					</ul>
				<?php endif; ?>

				<?php if ( bbp_has_forums() ) : ?>

					<div class="bb-rl-forum-tabs-content" id="bb-rl-forum-subforums">

						<?php bbp_get_template_part( 'loop', 'forums' ); ?>

						<?php bbp_get_template_part( 'pagination', 'forums' ); ?>

					</div>

				<?php endif; ?>

				<?php if ( ! bbp_is_forum_category() && bbp_has_topics() ) : ?>
					<div class="bb-rl-forum-tabs-content selected" id="bb-rl-forum-discussions">

						<?php bbp_get_template_part( 'loop', 'topics' ); ?>

						<?php bbp_get_template_part( 'pagination', 'topics' ); ?>

					</div>

					<?php bbp_get_template_part( 'form', 'topic' ); ?>


				<?php elseif ( ! bbp_is_forum_category() ) : ?>

					<?php bbp_get_template_part( 'feedback', 'no-topics' ); ?>

					<?php bbp_get_template_part( 'form', 'topic' ); ?>

				<?php elseif ( bbp_is_forum_category() ) : ?>

					<?php bbp_get_template_part( 'feedback', 'forum-category' ); ?>

				<?php endif; ?>

			<?php endif; ?>

			<?php do_action( 'bbp_template_after_single_forum' ); ?>

		</div>
	</div>
</div>