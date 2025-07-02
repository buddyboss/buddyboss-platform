<?php
/**
 * ReadyLaunch - Search Loop Member AJAX template.
 *
 * The template for AJAX search results for members.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<div class="bp-search-ajax-item bboss_ajax_search_member">
	<a href="<?php echo esc_url( bp_get_member_permalink() ); ?>">
		<div class="item-avatar">
			<?php bp_member_avatar( 'type=thumb&width=60&height=60' ); ?>
		</div>
	</a>
	<div class="item">
		<div class="item-title">
			<a href="<?php echo esc_url( bp_get_member_permalink() ); ?>"><?php bp_member_name(); ?></a>
			<span class="bb-rl-member-type"><?php echo bp_get_user_member_type( bp_get_member_user_id() ); ?></span>
		</div>
		<?php
		if ( bp_nouveau_member_has_meta() ) :
			?>
			<p class="entry-meta item-meta last-activity">
				<?php echo esc_html__( 'Last active', 'buddyboss' ) . ' ' . wp_kses_post( bb_get_member_last_activity_time() ); ?>
			</p><!-- #item-meta -->
		<?php endif; ?>
	</div>
</div>
