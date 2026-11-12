<?php
/**
 * ReadyLaunch - Member Profile Loop template.
 *
 * This template handles displaying member profile fields in a widget loop.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$edit_profile_link = trailingslashit( bboss_displayed_user_domain() . bboss_get_profile_slug() . '/edit/group/' );
$bp                = bboss();
$args              = array();

if ( bboss_has_profile( $args ) ) {

	while ( bboss_profile_groups() ) :
		bboss_the_profile_group();

		if ( bboss_profile_group_has_fields() ) {
			bboss_nouveau_xprofile_hook( 'before', 'field_content' ); ?>

			<div class="widget bb-rl-profile-widget">
				<h2 class="bb-rl-profile-widget-header widget-title">
					<?php
					bboss_the_profile_group_name();
					if ( bboss_is_my_profile() ) {
						?>
						<div class="bb-rl-see-all">
							<a href="<?php echo esc_url( $edit_profile_link . bboss_get_the_profile_group_id() ); ?>"><?php esc_attr_e( 'Edit', 'buddyboss' ); ?></a>
						</div>
						<?php
					}
					?>
				</h2>
				<div class="bb-rl-profile-widget-content <?php bboss_the_profile_group_slug(); ?>">
					<?php
					while ( bboss_profile_fields() ) {
						bboss_the_profile_field();

						if (
							function_exists( 'bboss_member_type_enable_disable' ) &&
							false === bboss_member_type_enable_disable()
						) {
							if (
								function_exists( 'bboss_get_xprofile_member_type_field_id' ) &&
								bboss_get_the_profile_field_id() === bboss_get_xprofile_member_type_field_id()
							) {
								continue;
							}
						}

						bboss_nouveau_xprofile_hook( 'before', 'field_item' );

						if ( bboss_field_has_data() ) {
							?>
									<div class="bb-rl-profile-widget-field">
										<div class="bb-rl-profile-widget-label"><?php bboss_the_profile_field_name(); ?></div>
									<?php
									if ( bboss_rl_get_user_social_networks_field_id() == bboss_get_the_profile_field_id() ) {
										add_filter( 'bb_rl_get_user_social_networks_urls', 'bb_get_user_social_networks_urls_with_visibility', 10, 3 );
										$user_social_networks_urls = bboss_rl_get_user_social_networks_urls();
										remove_filter( 'bb_rl_get_user_social_networks_urls', 'bb_get_user_social_networks_urls_with_visibility', 10, 3 );

										if ( ! empty( $user_social_networks_urls ) ) {
											?>
												<div class="flex align-items-center bb-rl-member-social-links">
												<?php echo wp_kses( $user_social_networks_urls, bboss_members_allow_html_tags() ); ?>
												</div>
												<?php
										}
									} else {
										?>
											<div class="bb-rl-profile-widget-data"><?php bboss_the_profile_field_value(); ?></div>
										<?php } ?>
									</div>
								<?php
						}

						bboss_nouveau_xprofile_hook( '', 'field_item' );

					}

						bboss_nouveau_xprofile_hook( 'after', 'field_items' );
					?>
				</div>
			</div>

			<?php
			bboss_nouveau_xprofile_hook( 'after', 'field_content' );
		}

	endwhile;

	unset( $user_social_networks_urls, $edit_profile_link );
}
