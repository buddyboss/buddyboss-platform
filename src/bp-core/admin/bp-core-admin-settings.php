<?php
/**
 * BuddyBoss Admin Settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyPress 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Admin bar for logged out users setting field.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_admin_bar() {
	?>

	<input id="hide-loggedout-adminbar" name="hide-loggedout-adminbar" type="checkbox" value="1" <?php checked( ! bp_hide_loggedout_adminbar( false ) ); ?> />
	<label for="hide-loggedout-adminbar"><?php esc_html_e( 'Show the Toolbar for logged out users', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow members to delete their accounts setting field.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_account_deletion() {
	?>

	<input id="bp-disable-account-deletion" name="bp-disable-account-deletion" type="checkbox" value="1" <?php checked( ! bp_disable_account_deletion( false ) ); ?> />
	<label for="bp-disable-account-deletion"><?php esc_html_e( 'Allow members to delete their profiles', 'buddyboss' ); ?></label>

	<?php
}


/**
 * Admin bar for logged in users setting field.
 *
 * @since BuddyBoss 1.1.0
 */
function bp_admin_setting_callback_login_admin_bar() {
	?>

	<input id="show-login-adminbar" name="show-login-adminbar" type="checkbox" value="1" <?php checked( bp_show_login_adminbar( true ) ); ?> />
	<label for="show-login-adminbar"><?php esc_html_e( 'Show the Toolbar for logged-in members (non-admins)', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Admin bar for admin users setting field.
 *
 * @since BuddyBoss 1.1.0
 */
function bp_admin_setting_callback_admin_admin_bar() {
	?>

	<input id="show-admin-adminbar" name="show-admin-adminbar" type="checkbox" value="1" <?php checked( bp_show_admin_adminbar( true ) ); ?> />
	<label for="show-admin-adminbar"><?php esc_html_e( 'Show the Toolbar for logged-in admins', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Link to Admin Settings tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_tutorial() {
	?>
	<p>
		<a class="button" target="_blank" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62792,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Allow admin to make the site private network.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_private_network() {
	?>

	<input id="bp-enable-private-network" name="bp-enable-private-network" type="checkbox" value="1" <?php checked( ! bp_enable_private_network( false ) ); ?> />
	<label for="bp-enable-private-network"><?php esc_html_e( 'Restrict site access to only logged-in members', 'buddyboss' ); ?></label>
	<?php
	printf(
		'<p class="description">%s</p>',
		sprintf(
			__( 'Login and <a href="%s">Registration</a> content will remain publicly visible.', 'buddyboss' ),
			add_query_arg(
				array(
					'page' => 'bp-pages',
				),
				admin_url( 'admin.php' )
			)
		)
	);
}

/**
 * Allow admin to make the site private network.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_private_network_public_content() {
	?>

	<label for="bp-enable-private-network-public-content"><?php esc_html_e( 'Enter URLs or URI fragments (e.g. /groups/) to remain publicly visible always. Enter one URL or URI per line. ', 'buddyboss' ); ?></label>
	<textarea rows="10" cols="100" id="bp-enable-private-network-public-content" name="bp-enable-private-network-public-content" style="margin-top: 10px;"><?php echo esc_textarea( bp_enable_private_network_public_content() ); ?></textarea>
	<?php
}

/**
 * Link to Privacy tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_privacy_tutorial() {
	?>
	<p>
		<a class="button" target="_blank" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62793,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/** Activity *******************************************************************/

/**
 * Allow Akismet setting field.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_activity_akismet() {
	?>

	<input id="_bp_enable_akismet" name="_bp_enable_akismet" type="checkbox" value="1" <?php checked( bp_is_akismet_active( true ) ); ?> />
	<label for="_bp_enable_akismet"><?php esc_html_e( 'Enable Akismet spam protection for activity feed', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow activity comments on posts and comments.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_blogforum_comments() {
	?>

	<input id="bp-disable-blogforum-comments" name="bp-disable-blogforum-comments" type="checkbox" value="1" <?php checked( ! bp_disable_blogforum_comments( false ) ); ?> />
	<label for="bp-disable-blogforum-comments"><?php esc_html_e( 'Allow activity feed commenting on blog posts, custom post types, and forum discussions', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Link to Posts in Activity Feed tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_posts_in_activity_tutorial() {
	?>
	<p>
		<a class="button" target="_blank" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62823,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Allow link previews in activity posts.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_enable_activity_link_preview() {
	?>

	<input id="_bp_enable_activity_link_preview" name="_bp_enable_activity_link_preview" type="checkbox" value="1" <?php checked( bp_is_activity_link_preview_active( false ) ); ?> />
	<label for="_bp_enable_activity_link_preview"><?php esc_html_e( 'When links are used in activity posts, display an image and excerpt from the site', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Link to Activity Settings tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_activity_settings_tutorial() {
	?>
	<p>
		<a class="button" target="_blank" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 62822,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}


/**
 * Sanitization for bp-disable-blogforum-comments setting.
 *
 * In the UI, a checkbox asks whether you'd like to *enable* post/comment activity comments. For
 * legacy reasons, the option that we store is 1 if these comments are *disabled*. So we use this
 * function to flip the boolean before saving the intval.
 *
 * @since BuddyPress 1.6.0
 *
 * @param bool $value Whether or not to sanitize.
 * @return bool
 */
function bp_admin_sanitize_callback_blogforum_comments( $value = false ) {
	return $value ? 0 : 1;
}

/** Settings Page *************************************************************/

/**
 * The main settings page
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_settings() {
	$active_tab  = bp_core_get_admin_active_tab();
	$form_action = bp_core_admin_setting_url( $active_tab );
	?>

	<div class="wrap">
		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Settings', 'buddyboss' ) ); ?></h2>
		<div class="nav-settings-subsubsub">
			<ul class="subsubsub">
				<?php bp_core_settings_admin_tabs(); ?>
			</ul>
		</div>
		<form action="<?php echo esc_url( $form_action ); ?>" method="post" enctype="multipart/form-data">
			<?php
			$tab_object = bp_core_get_admin_active_tab_object();
			if ( $tab_object ) {
				$tab_object->form_html();
			}
			?>
		</form>
	</div>

	<?php
}

/**
 * The main Integrations page
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_integrations() {
	$active_tab  = bp_core_get_admin_integration_active_tab();
	$form_action = bp_core_admin_integrations_url( $active_tab );
	?>

	<div class="wrap">
		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Integrations', 'buddyboss' ) ); ?></h2>
		<div class="nav-settings-subsubsub">
			<ul class="subsubsub">
				<?php bp_core_admin_integration_tabs(); ?>
			</ul>
		</div>
		<form action="<?php echo esc_url( $form_action ); ?>" method="post">
			<?php bp_core_get_admin_integration_active_tab_object()->form_html(); ?>
		</form>
	</div>

	<?php
}

/**
 * Load the BuddyBoss App integration admin screen.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_buddyboss_app() {
	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'BuddyBoss App', 'buddyboss' ) ); ?></h2>
		<?php require buddypress()->plugin_dir . 'bp-core/admin/templates/about-buddyboss-app.php'; ?>
	</div>
	<?php
}

/**
 * Output settings API option.
 *
 * @since BuddyPress 1.6.0
 *
 * @param string $option  Form option to echo.
 * @param string $default Form option default.
 * @param bool   $slug    Form option slug.
 */
function bp_form_option( $option, $default = '', $slug = false ) {
	echo bp_get_form_option( $option, $default, $slug );
}

/**
 * Return settings API option
 *
 * @since BuddyPress 1.6.0
 *
 * @param string $option  Form option to return.
 * @param string $default Form option default.
 * @param bool   $slug    Form option slug.
 * @return string
 */
function bp_get_form_option( $option, $default = '', $slug = false ) {

	// Get the option and sanitize it.
	$value = bp_get_option( $option, $default );

	// Slug?
	if ( true === $slug ) {

		/**
		 * Filters the slug value in the form field.
		 *
		 * @since BuddyPress 1.6.0
		 *
		 * @param string $value Value being returned for the requested option.
		 */
		$value = esc_attr( apply_filters( 'editable_slug', $value ) );
	} else { // Not a slug.
		$value = esc_attr( $value );
	}

	// Fallback to default.
	if ( empty( $value ) ) {
		$value = $default;
	}

	/**
	 * Filters the settings API option.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $value  Value being returned for the requested option.
	 * @param string $option Option whose value is being requested.
	 */
	return apply_filters( 'bp_get_form_option', $value, $option );
}

/** Invites Section ************************************************************/

/**
 * Enable email subject field markup.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_member_invite_email_subject() {
	?>
	<input id="bp-disable-invite-member-email-subject" name="bp-disable-invite-member-email-subject" type="checkbox" value="1" <?php checked( bp_disable_invite_member_email_subject() ); ?> />
	<label for="bp-disable-invite-member-email-subject"><?php esc_html_e( 'Allow members to customize the email subject', 'buddyboss' ); ?></label>
	<?php
}

/**
 * Enable email content field markup.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_member_invite_email_content() {
	?>
	<input id="bp-disable-invite-member-email-content" name="bp-disable-invite-member-email-content" type="checkbox" value="1" <?php checked( bp_disable_invite_member_email_content() ); ?> />
	<label for="bp-disable-invite-member-email-content"><?php esc_html_e( 'Allow members to customize the email body content', 'buddyboss' ); ?></label>
	<?php
}

/**
 * Enable member invite field markup.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_member_invite_member_type() {
	?>
	<input id="bp-disable-invite-member-type" name="bp-disable-invite-member-type" type="checkbox" value="1" <?php checked( bp_disable_invite_member_type() ); ?> />
	<label for="bp-disable-invite-member-type"><?php esc_html_e( 'Allow members to select profile type of invitee', 'buddyboss' ); ?></label>
	<?php
		printf(
			'<p class="description">%s</p>',
			sprintf(
				__( 'Customize this setting while editing any of your <a href="%s">Profile Types</a>.', 'buddyboss' ),
				bb_get_feature_settings_url( 'members', 'profile_types' )
			)
		);
}

/**
 * Allow Post Type feed setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @uses checked() To display the checked attribute
 */
function bp_feed_settings_callback_post_type( $args ) {

	$post_type     = $args['post_type'];
	$option_name   = bb_post_type_feed_option_name( $post_type );
	$post_type_obj = get_post_type_object( $post_type );

	// Description for the last option of CPT.
	if ( true === $args['description'] && 'post' !== $post_type ) {
		?>
		<p class="description" style="margin-bottom: 10px;"><?php esc_html_e( 'Select which custom post types show in the activity feed when members publish them. For each custom post type, you can select whether or not to show comments in these activity posts (if comments are supported).', 'buddyboss' ); ?></p>
		<?php
	}
	?>
	<input
		class="bp-feed-post-type-checkbox <?php echo 'bp-feed-post-type-' . esc_attr( $post_type ); ?>"
		data-post_type="<?php echo esc_attr( $post_type ); ?>"
		name="<?php echo esc_attr( $option_name ); ?>"
		id="<?php echo esc_attr( $option_name ); ?>"
		type="checkbox"
		value="1"
		<?php checked( bp_is_post_type_feed_enable( $post_type, false ) ); ?>
	/>
	<label for="<?php echo esc_attr( $option_name ); ?>">
		<?php echo 'post' === $post_type ? esc_html__( 'WordPress Posts', 'buddyboss' ) : $post_type_obj->labels->name; ?>
	</label>
	<?php

	// Description for the WordPress Blog Posts
	if ( 'post' === $post_type ) {
		?>
		<p class="description"><?php esc_html_e( 'When members publish new blog posts, show them in the activity feed.', 'buddyboss' ); ?></p>
		<?php
	}
}

/**
 * Allow activity comments on posts and comments.
 *
 * @since BuddyBoss 1.7.2
 *
 * @param array $args Feed settings.
 *
 * @return void
 */
function bb_feed_settings_callback_post_type_comments( $args ) {
	$post_type              = $args['post_type'];
	$option_name            = bb_post_type_feed_comment_option_name( $post_type );
	$post_type_obj          = get_post_type_object( $post_type );
	$is_cpt_comment_enabled = bb_activity_is_enabled_cpt_global_comment( $post_type );

	if ( in_array( $post_type, bb_feed_not_allowed_comment_post_types(), true ) ) {
		?>
			<p class="description <?php echo esc_attr( 'bp-feed-post-type-comment-' . $post_type ); ?>">
				<?php
				printf(
				/* translators: %s: comment post type */
					esc_html__( 'Comments are not supported for %s.', 'buddyboss' ),
					esc_html( $post_type_obj->labels->name )
				);
				?>
			</p>
		<?php
		return;
	}
	?>

	<input
		class="bp-feed-post-type-commenet-checkbox <?php echo 'bp-feed-post-type-comment-' . esc_attr( $post_type ); ?>"
		name="<?php echo esc_attr( $option_name ); ?>"
		id="<?php echo esc_attr( $option_name ); ?>"
		type="checkbox"
		value="1"
		<?php checked( bb_is_post_type_feed_comment_enable( $post_type, false ) ); ?>
		<?php disabled( $is_cpt_comment_enabled, false ); ?>
	/>
	<label for="<?php echo esc_attr( $option_name ); ?>">
		<?php echo 'post' === $post_type ? esc_html__( 'Enable WordPress Post comments in the activity feed', 'buddyboss' ) : sprintf( esc_html__( 'Enable %s comments in the activity feed.', 'buddyboss' ), esc_html( $post_type_obj->labels->name ) ); ?>
	</label>
	<?php

	// Description for the WordPress Blog Posts.
	if ( 'post' === $post_type ) {
		?>
		<p class="description"><?php esc_html_e( 'Allow members to view and create comments to blog posts in the activity feed.', 'buddyboss' ); ?></p>
		<?php
	}
}

/**
 * Allow Platform default activity feed setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @uses checked() To display the checked attribute
 */
function bp_feed_settings_callback_platform( $args ) {

		$option_name = $args['activity_name'];
	?>
		<input name="<?php echo esc_attr( 'bp-feed-platform-' . $option_name ); ?>" id="<?php echo esc_attr( $option_name ); ?>" type="checkbox" value="1" <?php checked( bp_platform_is_feed_enable( 'bp-feed-platform-' . $option_name, true ) ); ?>/>
		<label for="<?php echo esc_attr( $option_name ); ?>"><?php echo esc_html( $args['activity_label'] ); ?></label>
	<?php

}

/**
 * Admin bar for logged out users setting field.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_register() {
	?>

	<input id="bp-enable-site-registration" name="bp-enable-site-registration" type="checkbox" value="1" <?php checked( bp_enable_site_registration() ); ?> />
	<label for="bp-enable-site-registration"><?php esc_html_e( 'Allow non-members to register new accounts', 'buddyboss' ); ?></label>
	<?php
	if ( false === bp_enable_site_registration() && bp_is_active( 'invites' ) ) {
		printf(
			'<p class="description">%s</p>',
			sprintf(
				__(
					'Because <a href="%s">Email Invites</a> is enabled, invited users will still be allowed to register new accounts.',
					'buddyboss'
				),
				esc_url(
					add_query_arg(
						array(
							'page' => 'bp-settings',
							'tab'  => 'bp-invites',
						),
						admin_url( 'admin.php' )
					)
				)
			)
		);
	}
}

/**
 * Allow member type to send invites setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @uses checked() To display the checked attribute
 */
function bp_admin_setting_callback_enable_send_invite_member_type( $args ) {

	$option_name = $args['name'];

	if ( true === $args['description'] ) {
		?>
		<p class="description" style="margin-bottom: 10px;"><?php esc_html_e( 'Only allow the selected profile types to send invites.', 'buddyboss' ); ?></p>
		<?php
	}
	?>
	<input name="<?php echo esc_attr( 'bp-enable-send-invite-member-type-' . $option_name ); ?>" id="<?php echo esc_attr( $option_name ); ?>" type="checkbox" value="1" <?php checked( bp_enable_send_invite_member_type( 'bp-enable-send-invite-member-type-' . $option_name, false ) ); ?>/>
	<label for="<?php echo esc_attr( $option_name ); ?>"><?php echo esc_html( $args['member_type_name'] ); ?></label>
	<?php

}

/**
 * Link to Email Invites tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_email_invites_tutorial() {
	?>
	<p>
		<a class="button" target="_blank" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 62838,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}


/**
 * Save our settings.
 *
 * @since 1.6.0
 */
function bp_core_admin_settings_save() {
	global $wp_settings_fields;

	if (
		isset( $_GET['page'] )
		&& 'bp-integrations' == $_GET['page']
		&& isset( $_GET['tab'] )
		&& 'bp-compatibility' == $_GET['tab']
		&& ! empty( $_POST['submit'] ) ) {

		check_admin_referer( 'buddypress-options' );

		// Because many settings are saved with checkboxes, and thus will have no values
		// in the $_POST array when unchecked, we loop through the registered settings.
		if ( isset( $wp_settings_fields['buddypress'] ) ) {
			foreach ( (array) $wp_settings_fields['buddypress'] as $section => $settings ) {
				foreach ( $settings as $setting_name => $setting ) {
					$value = isset( $_POST[ $setting_name ] ) ? $_POST[ $setting_name ] : '';

					bp_update_option( $setting_name, $value );
				}
			}
		}

		bp_core_redirect(
			add_query_arg(
				array(
					'page'    => 'bp-integrations',
					'tab'     => 'bp-compatibility',
					'updated' => 'true',
				),
				bp_get_admin_url( 'admin.php' )
			)
		);
	}
}

add_action( 'bp_admin_init', 'bp_core_admin_settings_save', 100 );

/**
 * Admin settings for showing the email confirmation field.
 *
 * @since BuddyBoss 1.1.6
 */
function bp_admin_setting_callback_register_show_confirm_email() {
	?>

	<input id="register-confirm-email" name="register-confirm-email" type="checkbox" value="1" <?php checked( bp_register_confirm_email( false ) ); ?> />
	<label for="register-confirm-email"><?php esc_html_e( 'Add Email confirmation to register form', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Admin settings for showing the legal agreement confirmation field.
 *
 * @since BuddyBoss 1.5.8.3
 */
function bb_admin_setting_callback_register_show_legal_agreement() {
	?>

	<input id="register-legal-agreement" name="register-legal-agreement" type="checkbox" value="1" <?php checked( bb_register_legal_agreement( false ) ); ?> />
	<label for="register-legal-agreement"><?php esc_html_e( 'Add Legal Agreement checkbox to register form', 'buddyboss' ); ?></label>
	<?php
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Require non-members to explicitly agree to your Terms of Service and Privacy Policy before registering.', 'buddyboss' )
		);
}

/**
 * Admin settings for showing the password confirmation field.
 *
 * @since BuddyBoss 1.1.6
 */
function bp_admin_setting_callback_register_show_confirm_password() {
	?>

	<input id="register-confirm-password" name="register-confirm-password" type="checkbox" value="1" <?php checked( bp_register_confirm_password( false ) ); ?> />
	<label for="register-confirm-password"><?php esc_html_e( 'Add Password confirmation to register form', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Admin settings for showing the allow custom registration checkbox.
 *
 * @since BuddyBoss 1.2.8
 */
function bp_admin_setting_callback_register_allow_custom_registration() {

	$allow_custom_registration = bp_allow_custom_registration();
	?>

	<select name="allow-custom-registration" id="allow-custom-registration">
		<option value="0" <?php selected( 0, $allow_custom_registration ); ?>><?php esc_html_e( 'BuddyBoss Registration', 'buddyboss' ); ?></option>
		<option value="1" <?php selected( 1, $allow_custom_registration ); ?>><?php esc_html_e( 'Custom URL', 'buddyboss' ); ?></option>
	</select>
	<?php
	if ( ! $allow_custom_registration ) {
		printf(
			'<p class="description">%s</p>',
			sprintf(
				__(
					'Use the default BuddyBoss registration form. Make sure to configure the <a href="%s">registration pages</a>.',
					'buddyboss'
				),
				esc_url(
					add_query_arg(
						array(
							'page' => 'bp-pages',
						),
						admin_url( 'admin.php' )
					)
				)
			)
		);
	}
}

/**
 * Admin settings for showing the allow custom registration checkbox.
 *
 * @since BuddyBoss 1.2.8
 */
function bp_admin_setting_callback_register_page_url() {
	?>
	<input style="width: 89%;" id="register-page-url" name="register-page-url" type="text" value="<?php echo esc_url( bp_custom_register_page_url() ); ?>" />
	<?php
	printf(
		'<p class="description">%s</p>',
		esc_html__( 'Enter a custom URL to redirect users to register to your site. Useful for membership plugins.', 'buddyboss' )
	);
}

/**
 * Link to Registration tutorial
 *
 * @since BuddyBoss 1.2.8
 */
function bp_admin_registration_setting_tutorial() {
	?>
	<p>
		<a class="button" target="_blank" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 86158,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Link to Moderation Block tutorial
 *
 * @since BuddyBoss 1.5.6
 */
function bp_admin_moderation_block_setting_tutorial() {
	?>
	<p>
		<a class="button" target="_blank" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 121711,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Link to Moderation Report tutorial
 *
 * @since BuddyBoss 1.5.6
 */
function bp_admin_moderation_report_setting_tutorial() {
	?>
	<p>
		<a class="button" target="_blank" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 121712,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * After update activity setting
 *
 * @since BuddyBoss 1.7.2
 *
 * @param string $tab_name  Settings tab name.
 *
 * @uses bb_feed_post_types()                    Get all post type name.
 * @uses bb_post_type_feed_option_name()         Settings option name for post type.
 * @uses bb_post_type_feed_comment_option_name() Settings option name for post type comment.
 *
 * @return void
 */
function bb_after_update_activity_settings( $tab_name ) {
	if ( 'bp-activity' !== $tab_name ) {
		return;
	}

	foreach ( bb_feed_post_types() as $key => $post_type ) {
		// Post type option name.
		$pt_opt_name = bb_post_type_feed_option_name( $post_type );

		// Post type comment option name.
		$ptc_opt_name = bb_post_type_feed_comment_option_name( $post_type );

		// Get the post type activity status.
		$opt_value = bp_get_option( $pt_opt_name, '' );

		// If the post type activity disable then its comment also make disable.
		if ( empty( $opt_value ) ) {
			bp_update_option( $ptc_opt_name, 0 );
		}
	}
}
add_action( 'bp_admin_tab_setting_save', 'bb_after_update_activity_settings', 10, 1 );

/**
 * Allow admin to make the REST APIs private.
 *
 * @since BuddyBoss 1.8.6
 */
function bb_admin_setting_callback_private_rest_apis() {
	$disable_field    = false;
	$checked_checkbox = bp_enable_private_rest_apis();
	if ( function_exists( 'bbapp_is_private_app_enabled' ) ) {
		if ( true === bbapp_is_private_app_enabled() ) {
			$disable_field = false;
		} else {
			$disable_field    = true;
			$checked_checkbox = false;
		}
	}
	?>

	<input id="bb-enable-private-rest-apis" name="bb-enable-private-rest-apis" type="checkbox" value="1" <?php checked( $checked_checkbox ); disabled( $disable_field ); ?>/>
	<label for="bb-enable-private-rest-apis"><?php esc_html_e( 'Restrict REST API access to only logged-in members', 'buddyboss' ); ?></label>
	<p class="description">
		<?php
		printf(
			wp_kses_post(
			/* translators: Registration link. */
				__( 'Login and %s APIs will remain publicly visible.', 'buddyboss' )
			),
			sprintf(
				'<a href="%s">' . esc_html__( 'Registration', 'buddyboss' ) . '</a>',
				esc_url(
					add_query_arg(
						array( 'page' => 'bp-pages' ),
						admin_url( 'admin.php' )
					)
				)
			)
		);
		?>
	</p>
	<?php
	if ( function_exists( 'bbapp_is_private_app_enabled' ) && false === bbapp_is_private_app_enabled() ) {
		?>
		<div class="bp-feedback info bp-feedback--clean bp-feedback--vmiddle">
			<span class="bp-icon" aria-hidden="true"></span>
			<p>
				<?php
				printf(
					wp_kses_post(
					/* translators: Settings link. */
						__( 'Your BuddyBoss App is currently public. To restrict access to REST APIs for logged-out members, please enable "Private App" in the %s.', 'buddyboss' )
					),
					sprintf(
						'<a href="%s">' . esc_html__( 'BuddyBoss App\'s settings', 'buddyboss' ) . '</a>',
						esc_url(
							add_query_arg(
								array( 'page' => 'bbapp-settings' ),
								admin_url( 'admin.php' )
							)
						)
					)
				);
				?>
			</p>
		</div>
		<?php
	}
}

/**
 * Allow admin to exclude REST APIs endpoint.
 *
 * @since BuddyBoss 1.8.6
 */
function bb_admin_setting_callback_private_rest_apis_public_content() {
	$disable_field = false;
	if ( function_exists( 'bbapp_is_private_app_enabled' ) && false === bbapp_is_private_app_enabled() ) {
		$disable_field = true;
	}
	?>

	<label for="bb-enable-private-rest-apis-public-content" style="display:block;"><?php esc_html_e( 'Enter REST API endpoint URLs or URI fragments (e.g. wp-json/wp/v2/pages/&lt;id&gt;) to remain publicly visible always. Enter one URL or URI per line.', 'buddyboss' ); ?></label>
	<textarea rows="10" cols="100" id="bb-enable-private-rest-apis-public-content" name="bb-enable-private-rest-apis-public-content" style="margin-top: 10px;" <?php disabled( $disable_field ); ?>><?php echo esc_textarea( bb_enable_private_rest_apis_public_content() ); ?></textarea>
	<?php
}

/**
 * Allow admin to make the RSS feeds private.
 *
 * @since BuddyBoss 1.8.6
 */
function bb_admin_setting_callback_private_rss_feeds() {
	?>

	<input id="bb-enable-private-rss-feeds" name="bb-enable-private-rss-feeds" type="checkbox" value="1" <?php checked( bp_enable_private_rss_feeds() ); ?>/>
	<label for="bb-enable-private-rss-feeds"><?php esc_html_e( 'Restrict RSS feed access to only logged-in members', 'buddyboss' ); ?></label>
	<?php
}

/**
 * Allow admin to exclude RSS feeds endpoint.
 *
 * @since BuddyBoss 1.8.6
 */
function bb_admin_setting_callback_private_rss_feeds_public_content() {
	?>

	<label for="bb-enable-private-rss-feeds-public-content" style="display:block;"><?php esc_html_e( 'Enter RSS feed URLs or URI fragments (e.g. /post-name/feed/) to remain publicly visible always. Enter one URL or URI per line.', 'buddyboss' ); ?></label>
	<textarea rows="10" cols="100" id="bb-enable-private-rss-feeds-public-content" name="bb-enable-private-rss-feeds-public-content" style="margin-top: 10px;"><?php echo esc_textarea( bb_enable_private_rss_feeds_public_content() ); ?></textarea>
	<?php
}

/**
 * Register the labs settings section.
 *
 * @since BuddyBoss 1.9.3
 *
 * @return array
 */
function bb_labs_get_settings_sections() {

	$settings = array(
		'bp_labs_settings' => array(
			'page'     => 'labs',
			'title'    => esc_html__( 'BuddyBoss Labs', 'buddyboss' ),
			'callback' => 'bb_labs_info_section_callback',
		),
	);

	return (array) apply_filters( 'bb_labs_get_settings_sections', $settings );

}

/**
 * Get settings fields by section.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param string $section_id Section id.
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 */
function bb_labs_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty.
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bb_labs_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bb_labs_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Get all the settings fields.
 *
 * @since BuddyBoss 1.9.3
 *
 * @return array
 */
function bb_labs_get_settings_fields() {

	$fields = (array) apply_filters( 'bb_labs_get_settings_fields', array() );

	if ( empty( $fields ) ) {
		$fields['bp_labs_settings'] = array(
			'bb_labs_no_settings_callback' => array(
				'title'    => ' ',
				'callback' => 'bb_labs_no_settings_callback',
				'args'     => array( 'class' => 'notes-hidden-header' ),
			),
		);
	}

	return $fields;
}

/**
 * BuddyBoss Labs settings section callback.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_labs_info_section_callback() {
	?>

	<p>
		<?php
		printf(
			'<p class="description">%s</p>',
			sprintf(
				wp_kses_post(
				/* translators: Support portal. */
					__(
						'BuddyBoss Labs provides early-access to upcoming BuddyBoss features. You can help us prepare these features for official release by reporting issues and providing feedback through the <a href="%s" target="_blank" >support portal</a>.',
						'buddyboss'
					)
				),
				'https://support.buddyboss.com'
			)
		);
		?>
	</p>

	<p>
		<?php
		printf(
			'<p class="description">%s</p>',
			wp_kses_post(
			/* translators: Support portal. */
				__(
					'Please note, customer support will not be able to provide support for these features until their official release.',
					'buddyboss'
				)
			)
		);
		?>
	</p>

	<?php
}

/**
 * Function to show the notice about the no labs features available.
 *
 * @since BuddyBoss 2.1.5.1
 *
 * @return void
 */
function bb_labs_no_settings_callback() {
	printf(
		'<p class="no-field-notice">%s</p><style>.submit{display:none;}</style>',
		wp_kses_post(
		/* translators: Support portal. */
			__(
				'There are currently no BuddyBoss Labs features available.',
				'buddyboss'
			)
		)
	);
}

/**
 * Link to registration restrictions tutorial.
 *
 * @since BuddyBoss 2.4.11
 */
function bb_registration_restrictions_tutorial() {
	?>
	<p>
		<a class="button" target="_blank" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 126835,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Allow admin to add blacklist emails and domains.
 *
 * @since  BuddyBoss 2.4.11
 */
function bb_admin_setting_callback_domain_restrictions() {

	$domain_restrictions = bb_domain_restrictions_setting();
	$conditions          = array(
		''             => esc_html__( 'Select Condition', 'buddyboss' ),
		'always_allow' => esc_html__( 'Always Allow', 'buddyboss' ),
		'never_allow'  => esc_html__( 'Never Allow', 'buddyboss' ),
		'only_allow'   => esc_html__( 'Only Allow', 'buddyboss' ),
	);
	?>
	<label for="bb-domain-restrictions-setting">
		<?php
		esc_html_e( 'Add domain(s) to restrict new users from being able to register, you can use a wildcard (*) symbol to apply restrictions to an entire extension.
		When multiple restrictions are in place, a domain will always take priority over an extension.
		', 'buddyboss' );
		?>
	</label>

	<div id="bb-domain-restrictions-setting" class="bb-domain-restrictions-listing registration-restrictions-listing">
		<div class="restrictions-error"></div>
		<div class="registration-restrictions-rule-list bb-sortable">
		<?php
		// Count the occurrences used later to validate.
		$pre_saved_conditions = array(
			'always_allow' => 0,
			'only_allow'   => 0,
		);

		if ( ! empty( $domain_restrictions ) ) {
			foreach ( $domain_restrictions as $key_rule => $rule ) {
				if ( isset( $rule['condition'] ) && isset( $pre_saved_conditions[ $rule['condition'] ] ) ) {
					$pre_saved_conditions[ $rule['condition'] ] += 1;
				}
			}
			foreach ( $domain_restrictions as $key_rule => $rule ) {
				?>
				<div class="registration-restrictions-rule">
					<span class='registration-restrictions-priority' style='display:none;'><?php echo esc_html( $key_rule + 1 ); ?></span>
					<div class="registration-restrictions-input">
						<input type="text" name="bb-domain-restrictions[<?php echo esc_attr( $key_rule ); ?>][domain]" class="registration-restrictions-domain" placeholder="<?php esc_attr_e( 'Domain name', 'buddyboss' ); ?>" value="<?php echo esc_attr( $rule['domain'] ); ?>"/>
					</div>
					<div class="registration-restrictions-input registration-restrictions-input-tld">
						<input type="text" name="bb-domain-restrictions[<?php echo esc_attr( $key_rule ); ?>][tld]" class="registration-restrictions-tld" placeholder="<?php esc_attr_e( 'Extension', 'buddyboss' ); ?>" value="<?php echo esc_attr( $rule['tld'] ); ?>"/>
					</div>
					<div class="registration-restrictions-select">
						<select name="bb-domain-restrictions[<?php echo esc_attr( $key_rule ); ?>][condition]" class="registration-restrictions-input-select">
							<?php
							foreach ( $conditions as $key => $value ) {
								$disabled = false;
								if (
									(
										'always_allow' === $key && $pre_saved_conditions['only_allow'] > 0
									) ||
									(
										'only_allow' === $key && $pre_saved_conditions['always_allow'] > 0
									)
								) {
									$disabled = true;
								}
								?>
								<option value='<?php echo esc_attr( $key ); ?>'
									<?php
									selected( $key === $rule['condition'] );
									disabled( $disabled );
									?>
								>
									<?php echo esc_html( $value ); ?>
								</option>
								<?php
							}
							?>
						</select>
					</div>
					<div class="registration-restrictions-remove">
						<button class="registration-restrictions-rule-remove domain-rule-remove" aria-label="Remove Rule">
							<i class="bb-icon-f bb-icon-times"></i>
						</button>
					</div>
				</div>
				<?php
			}
		}
		?>

			<!-- This below HTML is for clone only - Starts -->
			<div class="custom registration-restrictions-rule" style="display: none;">
				<span class='registration-restrictions-priority' style='display:none;'><?php echo esc_html( empty( $domain_restrictions ) ? 0 : count( $domain_restrictions ) + 1 ); ?></span>
				<div class="registration-restrictions-input">
					<input type="text" name="bb-domain-restrictions[placeholder_priority_index][domain]" class="registration-restrictions-domain" placeholder="<?php esc_attr_e( 'Domain name', 'buddyboss' ); ?>" value="" />
				</div>
				<div class="registration-restrictions-input registration-restrictions-input-tld">
					<input type="text" name="bb-domain-restrictions[placeholder_priority_index][tld]" class="registration-restrictions-tld" placeholder="<?php esc_attr_e( 'Extension', 'buddyboss' ); ?>" value="" />
				</div>
				<div class="registration-restrictions-select">
					<select name="bb-domain-restrictions[placeholder_priority_index][condition]" class="registration-restrictions-input-select">
						<?php
						foreach ( $conditions as $key => $value ) {
							$disabled = false;
							if (
								(
									'always_allow' === $key && $pre_saved_conditions['only_allow'] > 0
								) ||
								(
									'only_allow' === $key && $pre_saved_conditions['always_allow'] > 0
								)
							) {
								$disabled = true;
							}
							?>
							<option value='<?php echo esc_attr( $key ); ?>'
								<?php echo disabled( $disabled ); ?>
							>
								<?php echo esc_html( $value ); ?>
							</option>
							<?php
						}
						?>
					</select>
				</div>
				<div class="registration-restrictions-remove">
					<button class="registration-restrictions-rule-remove domain-rule-remove" aria-label="<?php esc_attr_e( 'Remove Rule', 'buddyboss' ); ?>">
						<i class="bb-icon-f bb-icon-times"></i>
					</button>
				</div>
			</div>
			<!-- This below HTML is for clone only - Ends -->

		</div>
		<input type='hidden' class='registration-restrictions-lastindex' value='<?php echo empty( $domain_restrictions ) ? 0 : count( $domain_restrictions ); ?>' />
		<button class="button registration-restrictions-add-rule domain-rule-add"> <?php esc_html_e( 'Add Domain', 'buddyboss' ); ?></button>
	</div>
	<?php
}

/**
 * Allow admin to add whitelist emails and domains.
 *
 * @since BuddyBoss 2.4.11
 */
function bb_admin_setting_callback_email_restrictions() {

	$email_restrictions = bb_email_restrictions_setting();
	$conditions         = array(
		''             => esc_html__( 'Select Condition', 'buddyboss' ),
		'always_allow' => esc_html__( 'Always Allow', 'buddyboss' ),
		'never_allow'  => esc_html__( 'Never Allow', 'buddyboss' ),
	);
	?>
	<label for="bb-email-restrictions-setting"><?php esc_html_e( 'Enter specific email addresses which you want to allow for user registrations. Enter one address per line.', 'buddyboss' ); ?></label>
	<div id="bb-email-restrictions-setting" class="bb-email-restrictions-listing registration-restrictions-listing">
		<div class="restrictions-error"></div>
		<div class="registration-restrictions-rule-list">
		<?php
		if ( ! empty( $email_restrictions ) ) {
			foreach ( $email_restrictions as $key_rule => $rule ) {
				?>
				<div class="registration-restrictions-rule">
					<div class="registration-restrictions-input">
						<input type="email" name="bb-email-restrictions[<?php echo esc_attr( $key_rule ); ?>][address]" class="registration-restrictions-domain" placeholder="<?php esc_attr_e( 'Email address', 'buddyboss' ); ?>" value="<?php echo esc_attr( $rule['address'] ); ?>"/>
					</div>
					<div class="registration-restrictions-select">
						<select name="bb-email-restrictions[<?php echo esc_attr( $key_rule ); ?>][condition]" class="registration-restrictions-input-select">
							<?php
							foreach ( $conditions as $key => $value ) {
								?>
								<option value='<?php echo esc_attr( $key ); ?>'
									<?php selected( $key === $rule['condition'] ); ?>
								>
									<?php echo esc_html( $value ); ?>
								</option>
								<?php
							}
							?>
						</select>
					</div>
					<div class="registration-restrictions-remove">
						<button class="registration-restrictions-rule-remove email-rule-remove" aria-label="Remove Rule">
							<i class="bb-icon-f bb-icon-times"></i>
						</button>
					</div>
				</div>
				<?php
			}
		}
		?>
			<!-- This below HTML is for clone only - Starts -->
			<div class="custom registration-restrictions-rule" style="display: none;">
				<div class="registration-restrictions-input">
					<input type="email" name="bb-email-restrictions[placeholder_priority_index][address]" class="registration-restrictions-domain" placeholder="<?php esc_attr_e( 'Email address', 'buddyboss' ); ?>" value=""/>
				</div>
				<div class="registration-restrictions-select">
					<select name="bb-email-restrictions[placeholder_priority_index][condition]" class="registration-restrictions-input-select">
						<?php
						foreach ( $conditions as $key => $value ) {
							?>
							<option value='<?php echo esc_attr( $key ); ?>'><?php echo esc_html( $value ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
				<div class="registration-restrictions-remove">
					<button class="registration-restrictions-rule-remove email-rule-remove" aria-label="<?php esc_attr_e( 'Remove Rule', 'buddyboss' ); ?>">
						<i class="bb-icon-f bb-icon-times"></i>
					</button>
				</div>
			</div>
			<!-- This below HTML is for clone only - Ends -->

		</div>
		<input type='hidden' class='registration-restrictions-lastindex' value='<?php echo empty( $email_restrictions ) ? 0 : count( $email_restrictions ); ?>' />
		<button class="button registration-restrictions-add-rule email-rule-add"> <?php esc_html_e( 'Add Email', 'buddyboss' ); ?></button>
	</div>
	<?php
}


/**
 * Callback function for registration restrictions section.
 *
 * @since BuddyBoss 2.4.11
 */
function bb_admin_setting_callback_registration_restrictions_instructions() {
	?>
	<p class='description'><?php esc_html_e( 'Domain restrictions can be configured to limit new user registrations to specific domains or extensions. This setting is only available when using the BuddyBoss Registration Form.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Get label with buddyboss registration notice if not active for the registration restrictions.
 *
 * @since BuddyBoss 2.4.11
 *
 * @return string $bb_registration_notice Notice content.
 */
function bb_get_buddyboss_registration_notice() {
	static $bb_registration_notice = '';

	if ( '' !== $bb_registration_notice ) {
		return $bb_registration_notice;
	}

	if ( bp_allow_custom_registration() ) {
		$bb_registration_notice = sprintf(
			'<br/><span class="bb-head-notice"> %1$s <a href="#bp_registration"><strong>%2$s</strong></a> %3$s</span>',
			esc_html__( 'Enable the', 'buddyboss' ),
			esc_html__( 'BuddyBoss Registration Form', 'buddyboss' ),
			esc_html__( 'to unlock', 'buddyboss' )
		);
	}

	return $bb_registration_notice;
}

/**
 * Allow pinned activity posts.
 *
 * @since BuddyBoss 2.4.60
 */
/**
 * Link to redirection tutorial.
 *
 * @since BuddyBoss 2.4.70
 */
function bb_admin_redirection_setting_tutorial() {
	?>
	<p>
		<a class="button" target="_blank" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 127063,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Get the published page list.
 *
 * @since BuddyBoss 2.4.70
 *
 * @param bool $for_json Optional. When true, returns array of {value, label} objects
 *                       for Settings 2.0 JSON responses. Default false.
 *
 * @return array Associative array of page id and page title of pages,
 *               or indexed array of {value, label} when $for_json is true.
 */
function bb_get_published_pages( $for_json = false ) {
	static $published_pages      = array();
	static $published_pages_json = array();

	if ( empty( $published_pages ) ) {
		$pages = get_pages(
			array(
				'post_status' => 'publish',
				'number'      => 500,
			)
		);

		foreach ( $pages as $page ) {
			$published_pages[ $page->ID ] = $page->post_title;

			if ( function_exists( 'bb_register_feature' ) ) {
				$published_pages_json[] = array(
					'value' => (string) $page->ID,
					'label' => $page->post_title,
				);
			}
		}
	}

	return $for_json ? $published_pages_json : $published_pages;
}

/**
 * Admin settings for showing the login redirection settings.
 *
 * @since BuddyBoss 2.4.70
 */
function bb_admin_setting_callback_login_redirection() {
	$login_redirection = bb_login_redirection();
	?>
	<select name="bb-login-redirection" id="bb-login-redirection">
		<option value="" <?php selected( '', $login_redirection ); ?>><?php esc_html_e( 'Default', 'buddyboss' ); ?></option>
		<option value="0" <?php selected( 0, $login_redirection ); ?>><?php esc_html_e( 'Custom URL', 'buddyboss' ); ?></option>
		<?php
		$pages = bb_get_published_pages();
		foreach ( $pages as $id => $title ) {
			?>
			<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, $login_redirection ); ?>><?php echo esc_html( $title ); ?></option>
			<?php
		}
		?>
	</select>
	<p class="description">
		<?php
		esc_html_e(
			'Select a page or external link to redirect your members to after they login.',
			'buddyboss'
		);
		?>
	</p>
	<?php
}

/**
 * Admin settings for showing the custom login redirection page url.
 *
 * @since BuddyBoss 2.4.70
 */
function bp_admin_setting_callback_custom_login_redirection() {
	?>
	<input style="width: 89%;" id="bb-custom-login-redirection" name="bb-custom-login-redirection" type="text" value="<?php echo esc_url( bb_custom_login_redirection() ); ?>"/>
	<p class="description">
		<?php
		esc_html_e(
			'Select a page or external link to redirect your members to after they login.',
			'buddyboss'
		)
		?>
	</p>
	<?php
}

/**
 * Admin settings for showing the logout redirection settings.
 *
 * @since BuddyBoss 2.4.70
 */
function bb_admin_setting_callback_logout_redirection() {
	$logout_redirection = bb_logout_redirection();
	?>
	<select name="bb-logout-redirection" id="bb-logout-redirection">
		<option value="" <?php selected( '', $logout_redirection ); ?>><?php esc_html_e( 'Default', 'buddyboss' ); ?></option>
		<option value="0" <?php selected( 0, $logout_redirection ); ?>><?php esc_html_e( 'Custom URL', 'buddyboss' ); ?></option>
		<?php
		$pages = bb_get_published_pages();
		foreach ( $pages as $id => $title ) {
			?>
			<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, $logout_redirection ); ?>><?php echo esc_html( $title ); ?></option>
			<?php
		}
		?>
	</select>
	<p class="description">
		<?php
		esc_html_e(
			'Select a page or external link to redirect your members to after they logout.',
			'buddyboss'
		)
		?>
	</p>
	<?php
}

/**
 * Admin settings for showing the custom logout redirection page url.
 *
 * @since BuddyBoss 2.4.70
 */
function bp_admin_setting_callback_custom_logout_redirection() {
	?>
	<input style="width: 89%;" id="bb-custom-logout-redirection" name="bb-custom-logout-redirection" type="text" value="<?php echo esc_url( bb_custom_logout_redirection() ); ?>"/>
	<p class="description">
		<?php
		esc_html_e(
			'Select a page or external link to redirect your members to after they logout.',
			'buddyboss'
		);
		?>
	</p>
	<?php
}

/**
 * Link to General Performance tutorial.
 *
 * @since BuddyBoss 2.5.80
 */
function bb_admin_performance_general_setting_tutorial() {
	?>
	<p>
		<a class="button" target="_blank" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 127427,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Link to Activity Performance tutorial.
 *
 * @since BuddyBoss 2.5.80
 */
function bb_admin_performance_activity_setting_tutorial() {
	?>
	<p>
		<a class="button" target="_blank" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 127427,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Function to render the fields in a general section of the performance tab.
 *
 * @since BuddyBoss 2.5.80
 */
function bb_admin_performance_setting_general_callback() {
	$bb_ajax_request_page_load = bb_get_ajax_request_page_load();
	?>
	<label for="bb_ajax_request_page_load"><?php esc_html_e( 'Load', 'buddyboss' ); ?></label>
	<select name="bb_ajax_request_page_load" id="bb_ajax_request_page_load">
		<option value="1" <?php selected( $bb_ajax_request_page_load, 1 ); ?>>1</option>
		<option value="2" <?php selected( $bb_ajax_request_page_load, 2 ); ?>>2</option>
	</select>
	<label for="bb_ajax_request_page_load"><?php esc_html_e( 'page requests on page load', 'buddyboss' ); ?></label>
	<p class="description"><?php esc_html_e( 'Select how many requests will be sent on page load. We recommend 1 request for high performing servers, and 2 for slower performing environments, or those who see conflicts with third party plugins.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Function to render the fields in a activity section of the performance tab.
 *
 * @since BuddyBoss 2.5.80
 */
function bb_admin_performance_setting_activity_callback() {
	$bb_load_activity_per_request = bb_get_load_activity_per_request();
	$bb_activity_load_type        = bp_get_option( 'bb_activity_load_type', 'infinite' );

	$activity_per_page = apply_filters( 'bb_performance_activity_per_page', array() );
	$activity_per_page = bp_parse_args(
		$activity_per_page,
		array( 5, 10, 15, 20 )
	);
	asort( $activity_per_page );

	$activity_autoload_options = apply_filters( 'bb_performance_activity_autoload', array() );
	$activity_autoload_options        = bp_parse_args(
		$activity_autoload_options,
		array(
			'infinite'  => __( 'Infinite scrolling', 'buddyboss' ),
			'load_more' => __( 'Load more', 'buddyboss' ),
		)
	);
	?>

	<label for="bb_load_activity_per_request"><?php esc_html_e( 'Load', 'buddyboss' ); ?></label>
	<select name="bb_load_activity_per_request" id="bb_load_activity_per_request">
		<?php
		foreach ( $activity_per_page as $load_val ) {
			echo '<option value="' . esc_attr( $load_val ) . '" ' . selected( $bb_load_activity_per_request, $load_val, false ) . '>' . esc_html( $load_val ) . '</option>';
		}
		?>
	</select>
	<label for="bb_activity_load_type"><?php esc_html_e( 'activity posts at a time using', 'buddyboss' ); ?></label>
	<select name="bb_activity_load_type" id="bb_activity_load_type">
		<?php
		foreach ( $activity_autoload_options as $load_val => $load_label ) {
			echo '<option value="' . esc_attr( $load_val ) . '" ' . selected( $bb_activity_load_type, $load_val, false ) . '>' . esc_html( $load_label ) . '</option>';
		}
		?>
	</select>
	<p class="description"><?php esc_html_e( 'Use infinite scrolling to automatically load new posts while scrolling down feeds. Increasing the number of posts retrieved in each request may negatively impact page loading speeds.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Setting for enable content count.
 *
 * @since BuddyBoss 2.8.10
 */
function bb_admin_setting_callback_content_counts() {
	?>

	<input id="bb-enable-content-counts" name="bb-enable-content-counts" type="checkbox" value="1" <?php checked( bb_enable_content_counts() ); ?> />
	<label for="bb-enable-content-counts"><?php esc_html_e( 'Enable content counts across your site', 'buddyboss' ); ?></label>
	<p class="description">
		<?php
		esc_html_e(
			'Disabling content counts will remove the counts on pages such as Members Directory, Groups Directory, Media pages such as Photos & Videos. This will also remove the counts under the profile tabs and can improve page load performance.',
			'buddyboss'
		);
		?>
	</p>
	<?php
}

