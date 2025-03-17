<?php
/**
 * BuddyPress - Users Settings
 *
 * @since BuddyBoss [BBVERSION]
 * 
 * @version 1.0.0
 */

$profile_link = trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() );
?>

<header class="entry-header settings-header flex align-items-center">
	<h1 class="entry-title settings-title"><?php esc_attr_e( 'Account Settings', 'buddyboss-theme' ); ?></h1>
	<a href="<?php echo $profile_link; ?>" class="push-right button outline small"><i class="bb-icon-f bb-icon-user"></i> <?php esc_attr_e( 'View Profile', 'buddyboss-theme' ); ?></a>
</header>

<div class="bp-settings-container">
	<?php if ( bp_core_can_edit_settings() ) : ?>
		<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>
	<?php endif; ?>

	<div class="bb-bp-settings-content">
		<?php
		switch ( bp_current_action() ) :
			case 'notifications':
				bp_get_template_part( 'members/single/settings/notifications' );
				break;
			case 'capabilities':
				bp_get_template_part( 'members/single/settings/capabilities' );
				break;
			case 'delete-account':
				bp_get_template_part( 'members/single/settings/delete-account' );
				break;
			case 'general':
				bp_get_template_part( 'members/single/settings/general' );
				break;
			case 'profile':
				bp_get_template_part( 'members/single/settings/profile' );
				break;
			case 'invites':
				bp_get_template_part( 'members/single/settings/group-invites' );
				break;
			case 'export':
				bp_get_template_part( 'members/single/settings/export-data' );
				break;
			case 'reported-content':
			case 'blocked-members':
				bp_get_template_part( 'members/single/settings/moderation' );
				break;
			default:
				bp_get_template_part( 'members/single/plugins' );
				break;
		endswitch;
		?>
	</div>
</div>
