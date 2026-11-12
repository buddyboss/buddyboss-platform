<?php
/**
 * ReadyLaunch - Member Settings Profile template.
 *
 * This template handles the profile visibility settings for members.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_member_hook( 'before', 'settings_template' ); ?>

<form action="<?php echo esc_url( bboss_displayed_user_domain() . bboss_get_settings_slug() . '/profile/' ); ?>" method="post" class="standard-form" id="settings-form">

	<?php
	if ( bboss_xprofile_get_settings_fields() ) {
		while ( bboss_profile_groups() ) :
			bboss_the_profile_group();

			$group_id = bboss_get_the_profile_group_id();
			// Check if Current Group is repeater if YES then get number of fields inside current group.
			$is_group_repeater_str = bboss_xprofile_get_meta( $group_id, 'group', 'is_repeater_enabled', true );
			$is_group_repeater     = 'on' === $is_group_repeater_str;
			$group_url             = esc_url( trailingslashit( bboss_displayed_user_domain() . bboss_get_profile_slug() . '/edit/group/' . $group_id ) );

			if ( bboss_profile_fields() ) {
				?>
				<table class="profile-settings bp-tables-user" id="<?php echo esc_attr( 'xprofile-settings-' . bboss_get_the_profile_group_slug() ); ?>">
					<thead>
					<tr>
						<th class="title field-group-name"><?php bboss_the_profile_group_name(); ?></th>
						<th class="title"><?php esc_html_e( 'Visibility', 'buddyboss' ); ?></th>
					</tr>
					</thead>

					<tbody>
					<?php
					while ( bboss_profile_fields() ) :
						bboss_the_profile_field();
						?>

						<tr <?php bboss_field_css_class(); ?>>
							<td class="field-name"><?php bboss_the_profile_field_name(); ?></td>
							<?php if ( $is_group_repeater ) : ?>
								<td class="field-visibility">
									<a title="<?php esc_html_e( 'Manage Privacy', 'buddyboss' ); ?>" href="<?php echo esc_url( $group_url ); ?>" ><?php esc_html_e( 'Manage Privacy', 'buddyboss' ); ?></a>
								</td>
							<?php else : ?>
								<td class="field-visibility"><?php bboss_profile_settings_visibility_select(); ?></td>
							<?php endif; ?>
						</tr>
					<?php endwhile; ?>
					</tbody>
				</table>
				<?php
			}
		endwhile;
	}
	?>

	<input type="hidden" name="field_ids" id="field_ids" value="<?php bboss_the_profile_field_ids(); ?>" />
	<?php bboss_nouveau_submit_button( 'members-profile-settings' ); ?>
</form>

<?php
bboss_nouveau_member_hook( 'after', 'settings_template' );
