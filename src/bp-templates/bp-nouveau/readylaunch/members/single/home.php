<?php
/**
 * ReadyLaunch - Member Home template.
 *
 * This template handles the member profile home page layout and structure.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_member_hook( 'before', 'home_content' );
?>
<div id="item-header" role="complementary" data-bp-item-id="<?php echo esc_attr( bp_displayed_user_id() ); ?>" data-bp-item-component="members" class="users-header single-headers">
	<?php bp_nouveau_member_header_template_part(); ?>
</div><!-- #item-header -->

<div class="bp-wrap">
	<div class="bb-rl-content-wrapper">
		<div class="bb-rl-primary-container">
			<?php
			if (
				! bp_is_user_profile_edit() &&
				! bp_is_user_messages() &&
				! bp_is_user_settings() &&
				! bp_is_user_change_avatar() &&
				! bp_is_user_notifications()
			) {
				?>
				<div id="item-header" role="complementary" data-bp-item-id="<?php echo esc_attr( bp_displayed_user_id() ); ?>" data-bp-item-component="members" class="users-header single-headers bb-rl-profile-header">
					<?php
						$template = 'member-header';
						/**
						 * Fires before the display of a member's header.
						 *
						 * @since BuddyPress 1.2.0
						 */
						do_action( 'bp_before_member_header' );

						// Get the template part for the header.
						bp_nouveau_member_get_template_part( $template );

						/**
						 * Fires after the display of a member's header.
						 *
						 * @since BuddyPress 1.2.0
						 */
						do_action( 'bp_after_member_header' );

						bp_nouveau_template_notices();

					if ( ! bp_nouveau_is_object_nav_in_sidebar() ) {
						bp_get_template_part( 'members/single/parts/item-nav' );
					}
					?>
				</div>
				<?php
			}
			?>

			<div class="bp-wrap">
				<div id="item-body" class="item-body">
					<?php bp_nouveau_member_template_part(); ?>
				</div><!-- #item-body -->
			</div><!-- // .bp-wrap -->
		</div>
		<?php
		if (
			! bp_is_user_profile_edit() &&
			! bp_is_messages_component() &&
			! bp_is_user_settings() &&
			! bp_is_user_change_avatar() &&
			! bp_is_user_notifications()
		) {
			?>
			<div class="bb-rl-secondary-container">
			<?php
				bp_get_template_part( 'sidebar/right-sidebar' );
			?>
			</div>
			<?php
		}
		?>
	</div>
</div>
<?php bp_nouveau_member_hook( 'after', 'home_content' ); ?>
