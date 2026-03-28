<?php
/**
 * BuddyBoss Admin Settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyPress 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Legacy General callbacks (Toolbar, Privacy, tutorials) removed — migrated to Settings 2.0 Advanced feature.
// See: bb-admin-settings-advanced.php, settings/advanced/settings-general.php, settings/advanced/settings-privacy.php

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

// Legacy Privacy callbacks (REST APIs, RSS Feeds) removed — migrated to Settings 2.0 Advanced feature.
// See: settings/advanced/settings-privacy.php

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
function bb_get_published_pages( $as_options = false ) {
	static $published_pages = null;

	if ( null === $published_pages ) {
		$published_pages = array();
		$pages           = get_pages(
				array(
						'post_status' => 'publish',
				)
		);

		foreach ( $pages as $page ) {
			$published_pages[ $page->ID ] = $page->post_title;
		}
	}

	if ( $as_options ) {
		$options = array();
		foreach ( $published_pages as $id => $title ) {
			$options[] = array(
					'value' => (string) $id,
					'label' => $title,
			);
		}
		return $options;
	}

	return $published_pages;
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

// Legacy Performance callbacks (Page Requests, Activity Loading, Content Counts) removed —
// migrated to Settings 2.0 Advanced feature.
// See: settings/advanced/settings-general.php
