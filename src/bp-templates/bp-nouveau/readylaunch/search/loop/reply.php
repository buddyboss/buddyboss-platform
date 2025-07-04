<?php
/**
 * ReadyLaunch - Search Loop Reply template.
 *
 * The template for search results for replies.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$reply_id = get_the_ID();
$topic_id = bbp_get_reply_topic_id( $reply_id );
?>
<li class="bp-search-item bp-search-item_reply">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php bbp_reply_url( $reply_id ); ?>" class="bp-search-item_reply_link">
				<?php
				$args   = array(
					'type'    => 'avatar',
					'post_id' => $reply_id,
				);
				$avatar = bbp_get_reply_author_link( $args );

				if ( $avatar ) {
					echo wp_kses_post( $avatar );
				} else {
					?>
					<i class="bb-icon-f <?php echo esc_attr( bp_search_get_post_thumbnail_default( get_post_type(), 'icon' ) ); ?>"></i>
					<?php
				}
				?>
			</a>
		</div>

		<div class="item">
			<div class="entry-title item-title">
				<?php
				$bbp_get_reply_author_url  = bbp_get_reply_author_url( $reply_id );
				$reply_author_display_name = bbp_get_reply_author_display_name( $reply_id );
				if ( ! empty( $bbp_get_reply_author_url ) ) {
					?>
					<a href="<?php echo esc_url( $bbp_get_reply_author_url ); ?>" data-bb-hp-profile="<?php echo esc_attr( bbp_get_reply_author_id( $reply_id ) ); ?>"><?php echo esc_html( $reply_author_display_name ); ?></a>
					<?php
				} else {
					?>
					<span><?php echo esc_html( $reply_author_display_name ); ?></span>
					<?php
				}
				?>

				<a href="<?php bbp_reply_url( $reply_id ); ?>"><?php esc_html_e( 'replied to a discussion', 'buddyboss' ); ?></a>
			</div>
			<div class="entry-content entry-summary">
				<?php echo wp_kses_post( wp_trim_words( bbp_get_reply_content( $reply_id ), 30, '...' ) ); ?>
			</div>
			<div class="entry-meta">
				<span class="datetime">
					<?php bbp_reply_post_date( $reply_id, true ); ?>
				</span>
			</div>
		</div>
	</div>
</li>
