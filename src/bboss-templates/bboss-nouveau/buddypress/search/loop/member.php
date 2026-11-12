<?php
/**
 * Template for displaying the search results of the member
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/member.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$member_user_id   = bboss_get_member_user_id();
$member_user_link = bboss_get_member_permalink();
?>
<li <?php bboss_member_class( array( 'item-entry', 'bp-search-item' ) ); ?> data-bp-item-id="<?php echo esc_attr( $member_user_id ); ?>" data-bp-item-component="members">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php echo esc_url( $member_user_link ); ?>" data-bb-hp-profile="<?php echo esc_attr( $member_user_id ); ?>"><?php bboss_member_avatar( bboss_nouveau_avatar_args() ); ?></a>
		</div>

		<div class="item">
			<h2 class="item-title member-name">
				<a href="<?php echo esc_url( $member_user_link ); ?>" data-bb-hp-profile="<?php echo esc_attr( $member_user_id ); ?>"><?php bboss_member_name(); ?></a>
			</h2>
			<?php
			echo wp_kses_post( bboss_get_user_member_type( $member_user_id ) );
			if ( bboss_nouveau_member_has_meta() ) :
				?>
				<p class="item-meta last-activity">
					<span class="middot">&middot;</span>
					<?php echo esc_html__( 'Last active', 'buddyboss' ) . ' ' . wp_kses_post( bboss_get_member_last_activity_time() ); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</li>
