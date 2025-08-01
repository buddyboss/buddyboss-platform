<?php
/**
 * Search Loop - Single Forum Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<li class="bb-rl-forum-list-item">
	<?php if ( function_exists( 'bbp_get_forum_thumbnail_image' ) ) { ?>
		<a href="<?php bbp_forum_permalink(); ?>" class="bb-rl-forum-cover" title="<?php bbp_forum_title(); ?>">
			<?php echo bbp_get_forum_thumbnail_image( bbp_get_forum_id(), 'large', 'full' ); ?>
		</a>
	<?php } ?>

	<div class="bb-rl-card-forum-details">
		<div class="bb-rl-sec-header">
			<?php do_action( 'bbp_theme_before_forum_title' ); ?>
			<h3><a class="bbp-forum-title" href="<?php bbp_forum_permalink(); ?>"><?php bbp_forum_title(); ?></a></h3>
			<?php do_action( 'bbp_theme_after_forum_title' ); ?>
		</div>

		<div class="bb-rl-forum-meta">
			<?php
				$forum_id = bbp_get_forum_id();
				// get discussion count.
				$discussion_count = bbp_get_forum_topic_count( $forum_id, false );
				// get forum visibility/privacy status.
				$forum_visibility   = bbp_get_forum_visibility( $forum_id );
				$forum_visibilities = bbp_get_forum_visibilities( $forum_id );
				$privacy_label      = isset( $forum_visibilities[ $forum_visibility ] ) ? $forum_visibilities[ $forum_visibility ] : __( 'Public', 'buddyboss' );
			?>
			<div class="bb-rl-forum-meta-item">
				<?php echo esc_html( $privacy_label ); ?>
			</div>
			<div class="bb-rl-forum-meta-item <?php echo 0 === $discussion_count ? 'bb-rl-forum-meta-item-inactive' : ''; ?>">
				<span class="bb-rl-forum-topic-count-value"><?php echo esc_html( $discussion_count ); ?></span>
				<span class="bb-rl-forum-topic-count-label"><?php echo esc_html( _n( 'Discussion', 'Discussions', $discussion_count, 'buddyboss' ) ); ?></span>
			</div>
			<div class="bb-rl-forum-meta-item">
				<?php do_action( 'bbp_theme_before_forum_freshness_link' ); ?>
				<?php
				$bb_rl_instance = function_exists( 'bb_load_readylaunch' ) ? bb_load_readylaunch() : null;
				if ( $bb_rl_instance ) {
					add_filter( 'bbp_get_forum_last_active', array( $bb_rl_instance, 'bb_rl_get_forum_last_active' ), 10 );
					add_filter( 'bbp_get_forum_freshness_link', array( $bb_rl_instance, 'bb_rl_get_forum_freshness_link' ), 10, 6 );
				}
				bbp_forum_freshness_link();
				if ( $bb_rl_instance ) {
					remove_filter( 'bbp_get_forum_last_active', array( $bb_rl_instance, 'bb_rl_get_forum_last_active' ), 10 );
					remove_filter( 'bbp_get_forum_freshness_link', array( $bb_rl_instance, 'bb_rl_get_forum_freshness_link' ), 10, 6 );
				}
				?>
				<?php do_action( 'bbp_theme_after_forum_freshness_link' ); ?>
			</div>
		</div>

		<div class="bb-forum-content-wrap">
			<?php
				do_action( 'bbp_theme_before_forum_description' );
				remove_filter( 'bbp_get_forum_content', 'wpautop' );
			?>
			<div class="bb-forum-content"><?php echo bbp_get_forum_content_excerpt_view_more( bbp_get_forum_id(), 150, '&hellip;' ); ?></div>
			<?php
				add_filter( 'bbp_get_forum_content', 'wpautop' );
				do_action( 'bbp_theme_after_forum_description' );
			?>
		</div>

	</div>
</li>
