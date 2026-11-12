<?php
/**
 * ReadyLaunch - Search Loop Member AJAX template.
 *
 * The template for AJAX search results for members.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<div class="bp-search-ajax-item bboss_ajax_search_member">
	<a href="<?php echo esc_url( bboss_get_member_permalink() ); ?>">
		<div class="item-avatar">
			<?php bboss_member_avatar( 'type=thumb&width=60&height=60' ); ?>
		</div>
	</a>
	<div class="item">
		<div class="item-title">
			<a href="<?php echo esc_url( bboss_get_member_permalink() ); ?>"><?php bboss_member_name(); ?></a>
			<span class="bb-rl-member-type"><?php echo wp_kses_post( bboss_get_user_member_type( bboss_get_member_user_id() ) ); ?></span>
		</div>
		<?php
		if ( bboss_nouveau_member_has_meta() ) :
			?>
			<p class="entry-meta item-meta last-activity">
				<?php echo esc_html__( 'Last active', 'buddyboss' ) . ' ' . wp_kses_post( bboss_get_member_last_activity_time() ); ?>
			</p><!-- #item-meta -->
		<?php endif; ?>
	</div>
</div>
