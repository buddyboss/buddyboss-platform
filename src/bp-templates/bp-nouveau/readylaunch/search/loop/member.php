<?php
/**
 * ReadyLaunch - Search Loop Member template.
 *
 * The template for search results for members.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$member_user_id   = bp_get_member_user_id();
$member_user_link = bp_get_member_permalink();
?>
<li <?php bp_member_class( array( 'item-entry', 'bp-search-item' ) ); ?> data-bp-item-id="<?php echo esc_attr( $member_user_id ); ?>" data-bp-item-component="members">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php echo esc_url( $member_user_link ); ?>" data-bb-hp-profile="<?php echo esc_attr( $member_user_id ); ?>"><?php bp_member_avatar( bp_nouveau_avatar_args() ); ?></a>
		</div>

		<div class="item">
			<div class="flex bb-rl-search-member-header">
				<h2 class="item-title member-name">
					<a href="<?php echo esc_url( $member_user_link ); ?>" data-bb-hp-profile="<?php echo esc_attr( $member_user_id ); ?>"><?php bp_member_name(); ?></a>
					<?php
					echo bp_get_user_member_type( $member_user_id );
					?>
				</h2>
			</div>
			<?php
			if ( bp_nouveau_member_has_meta() ) :
				?>
				<p class="item-meta last-activity">
					<?php echo esc_html__( 'Last active', 'buddyboss' ) . ' ' . wp_kses_post( bb_get_member_last_activity_time() ); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</li>
