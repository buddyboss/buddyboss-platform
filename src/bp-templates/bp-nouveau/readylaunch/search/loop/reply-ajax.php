<?php
/**
 * ReadyLaunch - Search Loop Reply AJAX template.
 *
 * The template for AJAX search results for replies.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$reply_id = get_the_ID();
?>
<div class="bp-search-ajax-item bp-search-ajax-item_reply">
	<div class="item-avatar">
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
			<i class="<?php echo esc_attr( bp_search_get_post_thumbnail_default( get_post_type(), 'icon' ) ); ?>"></i>
			<?php
		}
		?>
	</div>
	<div class="item">
		<div class="entry-title item-title">
			<a href="<?php bbp_reply_url( get_the_ID() ); ?>">
				<?php bbp_reply_author_display_name( get_the_ID() ); ?>
				<?php esc_html_e( 'replied to a discussion', 'buddyboss' ); ?>
			</a>
		</div>
		<div class="item-desc">
			<?php echo wp_kses_post( wp_trim_words( bbp_get_reply_content( $reply_id ), 30, '...' ) ); ?>
		</div>

		<div class="entry-meta">
			<?php bbp_reply_post_date( $reply_id, true ); ?>
		</div>
	</div>
</div>
