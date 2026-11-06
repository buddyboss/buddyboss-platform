<?php
/**
 * Single Forum Content Template
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $post;
?>
<div class="bb-rl-container-inner">
	<div class="bb-rl-single-forum">

		<div class="bb-rl-forums-container-inner">
			<?php
			$forum_cover_photo = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
			if ( bboss_bbp_is_single_forum() && ! bboss_is_group() ) {
				?>
			<div class="bb-rl-forum-single-header">
				<div class="bb-rl-forum-single-header-cover">
					<?php
					if ( ! empty( $forum_cover_photo ) ) {
						?>
						<img src="<?php echo esc_url( $forum_cover_photo ); ?>" alt="<?php the_title_attribute( array( 'post' => $post->ID ) ); ?>" class="banner-img wp-post-image" alt="<?php esc_attr_e( 'Forum cover image', 'buddyboss' ); ?>"/>
						<?php
					} else {
						?>
						<img src="<?php echo esc_url( bboss()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/group_cover_image.jpeg' ); ?>" alt="<?php esc_attr_e( 'Forum placeholder image', 'buddyboss' ); ?>">
						<?php
					}
					?>
				</div>
				<div class="bb-rl-forum-single-header-content">
					<h1 class="entry-title"><?php echo esc_html( bboss_bbp_get_forum_title() ); ?></h1>
					<div class="bb-rl-forum-meta">
					<?php
						$forum_id = bboss_bbp_get_forum_id();
						// get discussion count.
						$discussion_count = bboss_bbp_get_forum_topic_count( $forum_id, false );
						// get forum visibility/privacy status.
						$forum_visibility   = bboss_bbp_get_forum_visibility( $forum_id );
						$forum_visibilities = bboss_bbp_get_forum_visibilities( $forum_id );
						$privacy_label      = isset( $forum_visibilities[ $forum_visibility ] ) ? $forum_visibilities[ $forum_visibility ] : __( 'Public', 'buddyboss' );
					?>
						<div class="bb-rl-forum-meta-item">
						<?php echo esc_html( $privacy_label ); ?>
						</div>
						<?php
						if ( 0 < (int) $discussion_count ) {
							?>
							<div class="bb-rl-forum-meta-item <?php echo 0 === $discussion_count ? 'bb-rl-forum-meta-item-inactive' : ''; ?>">
								<span class="bb-rl-forum-topic-count-value"><?php echo esc_html( $discussion_count ); ?></span>
								<span class="bb-rl-forum-topic-count-label"><?php echo esc_html( _n( 'Discussion', 'Discussions', $discussion_count, 'buddyboss' ) ); ?></span>
							</div>
							<?php
						}
						?>
						<div class="bb-rl-forum-meta-item">
						<?php do_action( 'bbp_theme_before_forum_freshness_link' ); ?>
						<?php bboss_bbp_forum_freshness_link(); ?>
						<?php do_action( 'bbp_theme_after_forum_freshness_link' ); ?>
						</div>
					</div>
					<div class="bb-rl-forum-description">
						<?php echo wp_kses_post( bboss_bbp_get_forum_content_excerpt_view_more() ); ?>
					</div>
					<div class="bb-rl-forum-actions">
						<?php if ( bboss_bbp_is_single_forum() && ! bboss_bbp_is_forum_category() && ( bboss_bbp_current_user_can_access_create_topic_form() || bboss_bbp_current_user_can_access_anonymous_user_form() ) ) { ?>
							<a href="#new-post" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small bb-rl-new-discussion-btn" data-modal-id="bb-rl-topic-form"><i class="bb-icons-rl-plus"></i> <?php esc_html_e( 'New discussion', 'buddyboss' ); ?></a>
							<?php
							// Remove subscription link if forum assigned to the group.
							if ( ! function_exists( 'bboss_is_forum_group_forum' ) || ! bboss_is_forum_group_forum( bboss_bbp_get_forum_id() ) ) {
								bboss_bbp_forum_subscription_link();
							}
							?>
						<?php } ?>
					</div><!-- .bb-rl-forum-actions -->
				</div><!-- .bb-rl-forum-single-header-content -->
			</div><!-- .bb-rl-forum-single-header -->
			<?php } ?>

			<?php
				$current_forum_id = bboss_bbp_get_forum_id();
				$discussion_count = bboss_bbp_get_forum_topic_count( $current_forum_id, false );
				$subforum_count   = bboss_bbp_get_forum_subforum_count( $current_forum_id );
			if ( bboss_is_group() ) {
				?>
				<div class="bb-rl-group-forum-header">
					<div class="bb-rl-forum-actions">
						<?php if ( bboss_bbp_is_single_forum() && ! bboss_bbp_is_forum_category() && ( bboss_bbp_current_user_can_access_create_topic_form() || bboss_bbp_current_user_can_access_anonymous_user_form() ) ) { ?>
							<a href="#new-post" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small bb-rl-new-discussion-btn" data-modal-id="bb-rl-topic-form"><i class="bb-icons-rl-plus"></i> <?php esc_html_e( 'New discussion', 'buddyboss' ); ?></a>
							<?php
							// Remove subscription link if forum assigned to the group.
							if ( ! function_exists( 'bboss_is_forum_group_forum' ) || ! bboss_is_forum_group_forum( bboss_bbp_get_forum_id() ) ) {
								bboss_bbp_forum_subscription_link();
							}
							?>
						<?php } ?>
					</div><!-- .bb-rl-forum-actions -->
				</div>
			<?php } ?>

			<?php do_action( 'bbp_template_before_single_forum' ); ?>

			<?php if ( post_password_required() ) : ?>

				<?php bboss_bbp_get_template_part( 'form', 'protected' ); ?>

			<?php else : ?>
					<ul class="bb-rl-forum-tabs">
						<li data-id="bb-rl-forum-discussions" class="bb-rl-forum-tabs-item selected">
							<a href="#" id="public-message">
								<?php
								if ( 0 < (int) $discussion_count ) {
									/* translators: %d: number of discussions */
									echo esc_html( sprintf( _n( 'Discussion (%d)', 'Discussions (%d)', $discussion_count, 'buddyboss' ), $discussion_count ) );
								} else {
									echo esc_html__( 'Discussions', 'buddyboss' );
								}
								?>
							</a>
						</li>
						<?php
						if ( bboss_bbp_has_forums() ) {
							?>
							<li data-id="bb-rl-forum-subforums" class="bb-rl-forum-tabs-item">
								<a href="#" id="private-message">
									<?php
									/* translators: %d: number of subforums */
									echo esc_html( sprintf( _n( 'Sub Forum (%d)', 'Sub Forums (%d)', $subforum_count, 'buddyboss' ), $subforum_count ) );
									?>
								</a>
							</li>
							<?php
						}
						?>
					</ul>

				<?php if ( bboss_bbp_has_forums() ) : ?>

					<div class="bb-rl-forum-tabs-content" id="bb-rl-forum-subforums">

						<?php bboss_bbp_get_template_part( 'loop', 'forums' ); ?>

						<?php bboss_bbp_get_template_part( 'pagination', 'forums' ); ?>

					</div>

				<?php endif; ?>

				<?php if ( ! bboss_bbp_is_forum_category() && bboss_bbp_has_topics() ) : ?>
					<div class="bb-rl-forum-tabs-content selected" id="bb-rl-forum-discussions">

						<?php bboss_bbp_get_template_part( 'loop', 'topics' ); ?>

						<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

					</div>

					<?php bboss_bbp_get_template_part( 'form', 'topic' ); ?>


				<?php elseif ( ! bboss_bbp_is_forum_category() ) : ?>

					<div class="bb-rl-forum-tabs-content selected" id="bb-rl-forum-discussions">
						<?php bboss_bbp_get_template_part( 'feedback', 'no-topics' ); ?>
					</div>

					<?php bboss_bbp_get_template_part( 'form', 'topic' ); ?>

				<?php elseif ( bboss_bbp_is_forum_category() ) : ?>
					<div class="bb-rl-forum-tabs-content selected" id="bb-rl-forum-discussions">
						<?php bboss_bbp_get_template_part( 'feedback', 'forum-category' ); ?>
					</div>

				<?php endif; ?>

			<?php endif; ?>

			<?php do_action( 'bbp_template_after_single_forum' ); ?>

		</div>
	</div>
</div>
