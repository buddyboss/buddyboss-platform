<?php
/**
 * BuddyBoss Search Settings
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get the Search settings sections.
 *
 * @since BuddyBoss 1.0.0
 * @return array
 */
function bp_search_get_settings_sections() {
	return (array) apply_filters(
		'bp_search_get_settings_sections',
		array(
			'bp_search_settings_community'  => array(
				'page'     => 'search',
				'title'    => __( 'Network Search', 'buddyboss' ),
				'callback' => 'bp_search_settings_callback_community_section',
				'tutorial_callback' => '',
			),
			'bp_search_settings_post_types' => array(
				'page'     => 'search',
				'title'    => __( 'Pages and Posts Search', 'buddyboss' ),
				'callback' => 'bp_search_settings_callback_post_type_section',
				'tutorial_callback' => '',
			),
			'bp_search_settings_general'    => array(
				'page'     => 'search',
				'title'    => __( 'Autocomplete Settings', 'buddyboss' ),
				'callback' => '',
				'tutorial_callback' => 'bp_search_settings_tutorial',
			),
		)
	);
}

/**
 * Get all of the settings fields.
 *
 * @since BuddyBoss 1.0.0
 * @return array
 */
function bp_search_get_settings_fields() {

	$fields = array();

	/** General Section */
	$fields['bp_search_settings_general'] = array(

		'bp_search_autocomplete'      => array(
			'title'             => __( 'Enable Autocomplete', 'buddyboss' ),
			'callback'          => 'bp_search_settings_callback_autocomplete',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),

		'bp_search_number_of_results' => array(
			'title'             => __( 'Number of Results', 'buddyboss' ),
			'callback'          => 'bp_search_settings_callback_number_of_results',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),

	);

	$fields['bp_search_settings_community'] = array(
		'bp_search_members' => array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_members',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'class' => 'bp-search-parent-field',
			),
		),
	);

	$fields['bp_search_settings_community']['bp_search_user_fields_label'] = array(
		'title'    => '&#65279;',
		'callback' => 'bp_search_settings_callback_user_fields_label',
		'args'     => array(
			'class' => 'bp-search-child-field',
		),
	);

	$user_fields = bp_get_search_user_fields();

	foreach ( $user_fields as $field_key => $field_label ) {
		$fields['bp_search_settings_community'][ "bp_search_user_field_{$field_key}" ] = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_user_field',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'field' => array(
					'field_key'   => $field_key,
					'field_label' => $field_label,
				),
				'class' => 'bp-search-child-field',
			),
		);
	}

	$groups = bp_xprofile_get_groups(
		array(
			'fetch_fields' => true,
		)
	);

	if ( ! empty( $groups ) ) {
		foreach ( $groups as $group ) {
			if ( ! empty( $group->fields ) ) {

				$fields['bp_search_settings_community'][ "bp_search_xprofile_group_{$group->id}" ] = array(
					'title'    => '&#65279;',
					'callback' => 'bp_search_settings_callback_xprofile_group',
					'args'     => array(
						'group' => $group,
						'class' => 'bp-search-child-field bp-search-subgroup-heading',
					),
				);

				foreach ( $group->fields as $field ) {

					if ( true === bp_core_hide_display_name_field( $field->id ) ) {
						continue;
					}

					$fields['bp_search_settings_community'][ "bp_search_xprofile_{$field->id}" ] = array(
						'title'             => '&#65279;',
						'callback'          => 'bp_search_settings_callback_xprofile',
						'sanitize_callback' => 'intval',
						'args'              => array(
							'field' => $field,
							'class' => 'bp-search-child-field',
						),
					);
				}
			}
		}
	}

	if ( bp_is_active( 'forums' ) ) {
		$fields['bp_search_settings_community']['bp_search_post_type_forum'] = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_post_type',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'post_type' => 'forum',
				'class'     => 'bp-search-parent-field',
			),
		);

		$fields['bp_search_settings_community']['bp_search_post_type_topic'] = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_post_type',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'post_type' => 'topic',
				'class'     => 'bp-search-child-field',
			),
		);

		$fields['bp_search_settings_community']['bp_search_topic_tax_topic-tag'] = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_post_type_taxonomy',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'post_type' => 'topic',
				'taxonomy'  => 'topic-tag',
				'class'     => 'bp-search-child-field',
			),
		);

		$fields['bp_search_settings_community']['bp_search_post_type_reply'] = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_post_type',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'post_type' => 'reply',
				'class'     => 'bp-search-child-field',
			),
		);
	}

	if ( bp_is_active( 'groups' ) ) {
		$fields['bp_search_settings_community']['bp_search_groups'] = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_groups',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'class' => 'bp-search-parent-field',
			),
		);
	}

	if ( bp_is_active( 'media' ) ) {
		$fields['bp_search_settings_community']['bp_search_photos'] = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_photos',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'class' => 'bp-search-parent-field',
			),
		);
		$fields['bp_search_settings_community']['bp_search_albums'] = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_albums',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'class' => 'bp-search-child-field',
			),
		);
		$fields['bp_search_settings_community']['bp_search_videos'] = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_videos',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'class' => 'bp-search-parent-field',
			),
		);

	}

	if ( bp_is_active( 'media' ) && ( bp_is_group_document_support_enabled() || bp_is_profile_document_support_enabled() ) ) {
		$fields['bp_search_settings_community']['bp_search_documents'] = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_documents',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'class' => 'bp-search-parent-field',
			),
		);
		$fields['bp_search_settings_community']['bp_search_folders']   = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_folders',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'class' => 'bp-search-child-field',
			),
		);
	}

	if ( bp_is_active( 'activity' ) ) {
		$fields['bp_search_settings_community']['bp_search_activity'] = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_activity',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'class' => 'bp-search-parent-field',
			),
		);

		$fields['bp_search_settings_community']['bp_search_activity_comments'] = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_activity_comments',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'class' => 'bp-search-child-field',
			),
		);
	}

	$post_types = get_post_types( array( 'public' => true ) );

	foreach ( $post_types as $post_type ) {

		if ( in_array( $post_type, array( 'forum', 'topic', 'reply' ) ) ) {
			continue;
		}

		$fields['bp_search_settings_post_types'][ "bp_search_post_type_$post_type" ] = array(
			'title'             => '&#65279;',
			'callback'          => 'bp_search_settings_callback_post_type',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'post_type' => $post_type,
				'class'     => 'bp-search-parent-field',
			),
		);

		/**
		 * Filter to add or remove the Taxonomy from Post Type
		 *
		 * @since 1.1.9
		 *
		 * @param array Return the names or objects of the taxonomies which are registered for the requested object or object type
		 * @param array $post_type Post type
		 *
		 * @return array $taxonomies Return the names or objects of the taxonomies which are registered for the requested object or object type
		 */
		$taxonomies = (array) apply_filters( 'bp_search_settings_post_type_taxonomies', get_object_taxonomies( $post_type ), $post_type );

		foreach ( $taxonomies as $taxonomy ) {
			$fields['bp_search_settings_post_types'][ "bp_search_{$post_type}_tax_{$taxonomy}" ] = array(
				'title'             => '&#65279;',
				'callback'          => 'bp_search_settings_callback_post_type_taxonomy',
				'sanitize_callback' => 'intval',
				'args'              => array(
					'post_type' => $post_type,
					'taxonomy'  => $taxonomy,
					'class'     => 'bp-search-child-field',
				),
			);
		}

		if ( in_array( $post_type, array( 'post', 'page' ) ) ) {
			$fields['bp_search_settings_post_types'][ "bp_search_post_type_meta_$post_type" ] = array(
				'title'             => '&#65279;',
				'callback'          => 'bp_search_settings_callback_post_type_meta',
				'sanitize_callback' => 'intval',
				'args'              => array(
					'post_type' => $post_type,
					'class'     => 'bp-search-child-field',
				),
			);
		}
	}

	return (array) apply_filters( 'bp_search_get_settings_fields', $fields );
}

/** General Section **************************************************************/

/**
 * Get settings fields by section.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $section_id
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 */
function bp_search_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bp_search_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bp_search_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Output settings API option
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $option
 * @param string $default
 * @param bool   $slug
 */
function bp_search_form_option( $option, $default = '', $slug = false ) {
	echo bp_search_get_form_option( $option, $default, $slug );
}

/**
 * Return settings API option
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses get_option()
 * @uses esc_attr()
 * @uses apply_filters()
 *
 * @param string $option
 * @param string $default
 * @param bool   $slug
 *
 * @return mixed
 */
function bp_search_get_form_option( $option, $default = '', $slug = false ) {

	// Get the option and sanitize it
	$value = get_option( $option, $default );

	// Slug?
	if ( true === $slug ) {
		$value = esc_attr( apply_filters( 'editable_slug', $value ) );

		// Not a slug
	} else {
		$value = esc_attr( $value );
	}

	// Fallback to default
	if ( empty( $value ) ) {
		$value = $default;
	}

	// Allow plugins to further filter the output
	return apply_filters( 'bp_search_get_form_option', $value, $option );
}

/**
 * Search autocomplete setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_autocomplete() {
	?>

	<input name="bp_search_autocomplete" id="bp_search_autocomplete" type="checkbox" value="1"
		<?php checked( bp_is_search_autocomplete_enable( true ) ); ?> />
	<label
		for="bp_search_autocomplete"><?php esc_html_e( 'Enable autocomplete dropdown when typing into search inputs.', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Checks if search autocomplete feature is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default bool Optional.Default value true
 *
 * @uses get_option() To get the bp_search_autocomplete option
 * @return bool Is search autocomplete enabled or not
 */
function bp_is_search_autocomplete_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_autocomplete_enable', (bool) get_option( 'bp_search_autocomplete', $default ) );
}

/**
 * Number of results setting field
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_settings_callback_number_of_results() {
	?>

	<input name="bp_search_number_of_results" id="bp_search_number_of_results" type="number" min="1" step="1"
		   value="<?php bp_search_form_option( 'bp_search_number_of_results', '5' ); ?>" class="small-text"/>
	<label for="bp_search_number_of_results"><?php esc_html_e( 'results', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Link to Search tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_settings_tutorial() {
	?>

	<p>
		<a class="button" href="
		<?php
		echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62840,
				),
				'admin.php'
			)
		);
		?>
		"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * Component search helper text.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_settings_callback_community_section() {
	?>
	<p><?php esc_html_e( 'Search the following BuddyBoss components:', 'buddyboss' ); ?></p>
	<input id="bp_search_select_all_components" type="checkbox" value="1">
	<label for="bp_search_select_all_components"><?php esc_html_e( 'Select All', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Post type search helper text.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_settings_callback_post_type_section() {
	?>
	<p><?php esc_html_e( 'Search the following WordPress content and custom post types:', 'buddyboss' ); ?></p>
	<input id="bp_search_select_all_post_types" type="checkbox" value="1">
	<label for="bp_search_select_all_post_types"><?php esc_html_e( 'Select All', 'buddyboss' ); ?></label>
	<?php
}

/**
 * Allow Members search setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_members() {
	?>
	<input name="bp_search_members" id="bp_search_members" type="checkbox" value="1"
		<?php checked( bp_is_search_members_enable( true ) ); ?> />
	<label
		for="bp_search_members"><?php esc_html_e( 'Members', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Checks if members search feature is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default bool Optional.Default value true
 *
 * @uses get_option() To get the bp_search_members option
 * @return bool Is members search enabled or not
 */
function bp_is_search_members_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_members_enable', (bool) get_option( 'bp_search_members', $default ) );
}

/**
 * Output Field Group name
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @param $group
 */
function bp_search_settings_callback_xprofile_group( $args ) {
	$group = $args['group'];
	?>
	<strong><?php echo $group->name; ?></strong>
	<?php
}

/**
 * Allow xProfile field search setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_xprofile( $args ) {
	$field       = $args['field'];
	$id          = $field->id;
	$option_name = 'bp_search_xprofile_' . $id;
	?>

	<input name="<?php echo $option_name; ?>" id="<?php echo $option_name; ?>" type="checkbox" value="1"
		<?php checked( bp_is_search_xprofile_enable( $id ) ); ?> />
	<label
		for="<?php echo $option_name; ?>"><?php echo $field->name; ?></label>

	<?php
}

/**
 * Checks if xprofile field search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $id integer
 *
 * @uses get_option() To get the bp_search_members option
 * @return bool Is members search enabled or not
 */
function bp_is_search_xprofile_enable( $id ) {
	return (bool) apply_filters( 'bp_is_search_xprofile_enable', (bool) get_option( "bp_search_xprofile_$id" ) );
}

/**
 * Output Account field label name
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @param $group
 */
function bp_search_settings_callback_user_fields_label() {
	?>
	<strong><?php esc_html_e( 'Account', 'buddyboss' ); ?></strong>
	<?php
}

/**
 * Allow xProfile field search setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_user_field( $args ) {

	$field       = $args['field'];
	$id          = $field['field_key'];
	$option_name = 'bp_search_user_field_' . $id;
	?>

	<input name="<?php echo $option_name; ?>" id="<?php echo $option_name; ?>" type="checkbox" value="1"
		<?php checked( bp_is_search_user_field_enable( $id ) ); ?> />
	<label
		for="<?php echo $option_name; ?>"><?php echo $field['field_label']; ?></label>

	<?php
}

/**
 * Checks if xprofile field search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $id integer
 *
 * @uses get_option() To get the bp_search_members option
 * @return bool Is members search enabled or not
 */
function bp_is_search_user_field_enable( $id ) {
	return (bool) apply_filters( 'bp_is_search_user_field_enable', (bool) get_option( "bp_search_user_field_$id" ) );
}

/**
 * Allow Post Type search setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_post_type( $args ) {

	$post_type   = $args['post_type'];
	$option_name = 'bp_search_post_type_' . $post_type;

	$post_type_obj = get_post_type_object( $post_type );
	?>
	<input
		name="<?php echo $option_name; ?>"
		id="<?php echo $option_name; ?>"
		type="checkbox"
		value="1"
		<?php checked( bp_is_search_post_type_enable( $post_type, true ) ); ?>
	/>
	<label for="<?php echo $option_name; ?>">
		<?php echo $post_type === 'post' ? esc_html__( 'Blog Posts', 'buddyboss' ) : $post_type_obj->labels->name; ?>
	</label>
	<?php
}

/**
 * Checks if post type search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_type string
 *
 * @return bool Is members search enabled or not
 */
function bp_is_search_post_type_enable( $post_type, $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_post_type_enable', (bool) get_option( "bp_search_post_type_$post_type", $default ) );
}

/**
 * Allow Post Type Meta search setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_post_type_meta( $args ) {

	$post_type   = $args['post_type'];
	$option_name = 'bp_search_post_type_meta_' . $post_type;

	$post_type_obj = get_post_type_object( $post_type );
	?>
	<input
		name="<?php echo $option_name; ?>"
		id="<?php echo $option_name; ?>"
		type="checkbox"
		value="1"
		<?php checked( bp_is_search_post_type_meta_enable( $post_type ) ); ?>
	/>
	<label for="<?php echo $option_name; ?>">
		<?php printf( esc_html__( 'Meta Data', 'buddyboss' ) ); ?>
	</label>
	<?php
}

/**
 * Checks if post type Meta search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_type string
 *
 * @return bool Is post type meta search enabled or not
 */
function bp_is_search_post_type_meta_enable( $post_type ) {
	return (bool) apply_filters( 'bp_is_search_post_type_meta_enable', (bool) get_option( "bp_search_post_type_meta_$post_type" ) );
}

/**
 * Allow Post Type search setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_groups() {
	?>
	<input
		name="bp_search_groups"
		id="bp_search_groups"
		type="checkbox"
		value="1"
		<?php checked( bp_is_search_groups_enable( true ) ); ?>
	/>
	<label for="bp_search_groups">
		<?php esc_html_e( 'Groups', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Allow Post Type search setting field
 *
 * @since BuddyBoss 1.4.0
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_photos() {
	?>
	<input
			name="bp_search_photos"
			id="bp_search_photos"
			type="checkbox"
			value="1"
			<?php checked( bp_is_search_photos_enable( false ) ); ?>
	/>
	<label for="bp_search_photos">
		<?php esc_html_e( 'Photos', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Allow Post Type search setting field
 *
 * @since BuddyBoss 1.4.0
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_albums() {
	?>
	<input
			name="bp_search_albums"
			id="bp_search_albums"
			type="checkbox"
			value="1"
			<?php checked( bp_is_search_albums_enable( false ) ); ?>
	/>
	<label for="bp_search_albums">
		<?php esc_html_e( 'Albums', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Allow Post Type search setting field
 *
 * @since BuddyBoss 1.7.0
 *
 * @uses checked() To display the checked attribute.
 */
function bp_search_settings_callback_videos() {
	?>
	<input
			name="bp_search_videos"
			id="bp_search_videos"
			type="checkbox"
			value="1"
			<?php checked( bp_is_search_videos_enable( false ) ); ?>
	/>
	<label for="bp_search_videos">
		<?php esc_html_e( 'Videos', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Allow Post Type search setting field
 *
 * @since BuddyBoss 1.4.0
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_documents() {
	?>
	<input
			name="bp_search_documents"
			id="bp_search_documents"
			type="checkbox"
			value="1"
			<?php checked( bp_is_search_documents_enable( false ) ); ?>
	/>
	<label for="bp_search_documents">
		<?php esc_html_e( 'Documents', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Allow Post Type search setting field
 *
 * @since BuddyBoss 1.4.0
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_folders() {
	?>
	<input
			name="bp_search_folders"
			id="bp_search_folders"
			type="checkbox"
			value="1"
			<?php checked( bp_is_search_folders_enable( false ) ); ?>
	/>
	<label for="bp_search_folders">
		<?php esc_html_e( 'Folders', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if groups search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is groups search enabled or not
 */
function bp_is_search_groups_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_groups_enable', (bool) get_option( 'bp_search_groups', $default ) );
}

/**
 * Checks if photos search is enabled.
 *
 * @since BuddyBoss 1.5.0
 *
 * @param $default integer
 *
 * @return bool Is media search enabled or not
 */
function bp_is_search_photos_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_photos_enable', (bool) get_option( 'bp_search_photos', $default ) );
}

/**
 * Checks if albums search is enabled.
 *
 * @since BuddyBoss 1.5.0
 *
 * @param $default integer
 *
 * @return bool Is albums search enabled or not
 */
function bp_is_search_albums_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_albums_enable', (bool) get_option( 'bp_search_albums', $default ) );
}

/**
 * Checks if video search is enabled.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param int $default whether video search enabled or not.
 *
 * @return bool Is video media search enabled or not.
 */
function bp_is_search_videos_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_videos_enable', (bool) get_option( 'bp_search_videos', $default ) );
}

/**
 * Checks if document search is enabled.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param $default integer
 *
 * @return bool Is documents search enabled or not
 */
function bp_is_search_documents_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_documents_enable', (bool) get_option( 'bp_search_documents', $default ) );
}

/**
 * Checks if folder search is enabled.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param $default integer
 *
 * @return bool Is documents search enabled or not
 */
function bp_is_search_folders_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_folders_enable', (bool) get_option( 'bp_search_folders', $default ) );
}

/**
 * Allow Activity search setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_activity() {
	?>
	<input
		name="bp_search_activity"
		id="bp_search_activity"
		type="checkbox"
		value="1"
		<?php checked( bp_is_search_activity_enable( true ) ); ?>
	/>
	<label for="bp_search_activity">
		<?php esc_html_e( 'Activity', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if Activity search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is Activity search enabled or not
 */
function bp_is_search_activity_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_activity_enable', (bool) get_option( 'bp_search_activity', $default ) );
}

/**
 * Allow Activity Comments search setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_activity_comments() {
	?>
	<input
		name="bp_search_activity_comments"
		id="bp_search_activity_comments"
		type="checkbox"
		value="1"
		<?php checked( bp_is_search_activity_comments_enable( true ) ); ?>
	/>
	<label for="bp_search_activity_comments">
		<?php esc_html_e( 'Activity Comments', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if Activity Comments search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is Activity Comments search enabled or not
 */
function bp_is_search_activity_comments_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_activity_comments_enable', (bool) get_option( 'bp_search_activity_comments', $default ) );
}

/**
 * Allow Post Type Taxonomy search setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_post_type_taxonomy( $args ) {

	$post_type   = $args['post_type'];
	$taxonomy    = $args['taxonomy'];
	$option_name = "bp_search_{$post_type}_tax_{$taxonomy}";

	$taxonomy_obj  = get_taxonomy( $taxonomy );
	$post_type_obj = get_post_type_object( $post_type );
	?>
	<input
		name="<?php echo $option_name; ?>"
		id="<?php echo $option_name; ?>"
		type="checkbox"
		value="1"
		<?php checked( bp_is_search_post_type_taxonomy_enable( $taxonomy, $post_type ) ); ?>
	/>
	<label for="<?php echo $option_name; ?>">
		<?php printf( esc_html__( '%s', 'buddyboss' ), $taxonomy_obj->labels->name ); ?>
	</label>
	<?php
}

/**
 * Checks if post type Taxonomy search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_type string post type name.
 * @param $taxonomy  string taxonomy name.
 *
 * @return bool Is post type Taxonomy search enabled or not
 */
function bp_is_search_post_type_taxonomy_enable( $taxonomy, $post_type ) {
	return (bool) apply_filters( 'bp_is_search_post_type_taxonomy_enable', (bool) get_option( "bp_search_{$post_type}_tax_{$taxonomy}" ) );
}
