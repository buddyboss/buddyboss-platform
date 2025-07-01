<?php
/**
 * ReadyLaunch - Member Profile Loop template.
 *
 * This template handles displaying member profile fields in a widget loop.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$edit_profile_link = trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() . '/edit/group/' );
$bp                = buddypress();
$args              = array();

if ( bp_has_profile( $args ) ) {

	while ( bp_profile_groups() ) :
		bp_the_profile_group();

		if ( bp_profile_group_has_fields() ) {
			bp_nouveau_xprofile_hook( 'before', 'field_content' ); ?>

			<div class="widget bb-rl-profile-widget">
				<h2 class="bb-rl-profile-widget-header widget-title">
					<?php
					bp_the_profile_group_name();
					if ( bp_is_my_profile() ) {
						?>
						<div class="bb-rl-see-all">
							<a href="<?php echo esc_url( $edit_profile_link . bp_get_the_profile_group_id() ); ?>"><?php esc_attr_e( 'Edit', 'buddyboss' ); ?></a>
						</div>
						<?php
					}
					?>
				</h2>
				<div class="bb-rl-profile-widget-content <?php bp_the_profile_group_slug(); ?>">
					<?php
					while ( bp_profile_fields() ) {
						bp_the_profile_field();

						if (
							function_exists( 'bp_member_type_enable_disable' ) &&
							false === bp_member_type_enable_disable()
						) {
							if (
								function_exists( 'bp_get_xprofile_member_type_field_id' ) &&
								bp_get_the_profile_field_id() === bp_get_xprofile_member_type_field_id()
							) {
								continue;
							}
						}

						bp_nouveau_xprofile_hook( 'before', 'field_item' );

						if ( bp_field_has_data() ) {
							?>
									<div class="bb-rl-profile-widget-field">
										<div class="bb-rl-profile-widget-label"><?php bp_the_profile_field_name(); ?></div>
									<?php
									if ( bb_rl_get_user_social_networks_field_id() == bp_get_the_profile_field_id() ) {
										add_filter( 'bb_rl_get_user_social_networks_urls', 'bb_get_user_social_networks_urls_with_visibility', 10, 3 );
										$user_social_networks_urls = bb_rl_get_user_social_networks_urls();
										remove_filter( 'bb_rl_get_user_social_networks_urls', 'bb_get_user_social_networks_urls_with_visibility', 10, 3 );

										if ( ! empty( $user_social_networks_urls ) ) {
											?>
												<div class="flex align-items-center bb-rl-member-social-links">
												<?php echo wp_kses( $user_social_networks_urls, bb_members_allow_html_tags() ); ?>
												</div>
												<?php
										}
									} else {
										?>
											<div class="bb-rl-profile-widget-data"><?php bp_the_profile_field_value(); ?></div>
										<?php } ?>
									</div>
								<?php
						}

						bp_nouveau_xprofile_hook( '', 'field_item' );

					}

						bp_nouveau_xprofile_hook( 'after', 'field_items' );
					?>
				</div>
			</div>

			<?php
			bp_nouveau_xprofile_hook( 'after', 'field_content' );
		}

	endwhile;

	unset( $user_social_networks_urls, $edit_profile_link );
}
