<?php
/**
 * Core component CSS & JS.
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register scripts commonly used by BuddyPress.
 *
 * @since BuddyPress 2.1.0
 */
function bp_core_register_common_scripts() {
	$min = bp_core_get_minified_asset_suffix();
	$url = buddypress()->plugin_url . 'bp-core/js/';

	// Is WordPress' moment dist library registered?
	$is_moment_registered = wp_script_is( 'moment', 'registered' );
	$moment_locale_url    = '';

	/*
	 * In 11.0.0 we are deprecating `bp-moment` in favor of WordPress' already bundled `moment`.
	 * @todo completely remove `bp-moment` in 12.0.0.
	 */
	if ( ! $is_moment_registered ) {
		/*
		* Moment.js locale.
		*
		* Try to map current WordPress locale to a moment.js locale file for loading.
		*
		* eg. French (France) locale for WP is fr_FR. Here, we try to find fr-fr.js
		*     (this file doesn't exist).
		*/
		$wp_locale = sanitize_file_name( strtolower( get_locale() ) );

		// WP uses ISO 639-2 or -3 codes for some locales, which we must translate back to ISO 639-1.
		$iso_locales = array(
			'bel' => 'be',
			'bre' => 'br',
			'kir' => 'ky',
			'mri' => 'mi',
			'ssw' => 'ss',
		);

		if ( isset( $iso_locales[ $wp_locale ] ) ) {
			$locale = $iso_locales[ $wp_locale ];
		} else {
			$locale = $wp_locale;
		}

		$locale = str_replace( '_', '-', $locale );
		if ( file_exists( buddypress()->core->path . "bp-core/js/vendor/moment-js/locale/{$locale}{$min}.js" ) ) {
			$moment_locale_url = $url . "vendor/moment-js/locale/{$locale}{$min}.js";

			/*
			* Try to find the short-form locale.
			*
			* eg. French (France) locale for WP is fr_FR. Here, we try to find fr.js
			*     (this exists).
			*/
		} else {
			$locale = substr( $locale, 0, strpos( $locale, '-' ) );
			if ( file_exists( buddypress()->core->path . "bp-core/js/vendor/moment-js/locale/{$locale}{$min}.js" ) ) {
				$moment_locale_url = $url . "vendor/moment-js/locale/{$locale}{$min}.js";
			}
		}
	}

	// Set up default scripts to register.
	$scripts = array(
		// Legacy.
		'bp-confirm'          => array(
			'file'         => "{$url}confirm{$min}.js",
			'dependencies' => array( 'jquery' ),
			'footer'       => false,
		),
		'bp-widget-members'   => array(
			'file'         => "{$url}widget-members{$min}.js",
			'dependencies' => array( 'jquery' ),
			'footer'       => false,
		),
		'bp-jquery-query'     => array(
			'file'         => "{$url}jquery-query{$min}.js",
			'dependencies' => array( 'jquery' ),
			'footer'       => false,
		),
		'bp-jquery-cookie'    => array(
			'file'         => "{$url}vendor/jquery-cookie{$min}.js",
			'dependencies' => array( 'jquery' ),
			'footer'       => false,
		),
		'bp-jquery-scroll-to' => array(
			'file'         => "{$url}vendor/jquery-scroll-to{$min}.js",
			'dependencies' => array( 'jquery' ),
			'footer'       => false,
		),

		// Version 2.1.
		'jquery-caret'        => array(
			'file'         => "{$url}vendor/jquery.caret{$min}.js",
			'dependencies' => array( 'jquery' ),
			'footer'       => true,
		),
		'jquery-atwho'        => array(
			'file'         => "{$url}vendor/jquery.atwho{$min}.js",
			'dependencies' => array( 'jquery', 'jquery-caret' ),
			'footer'       => true,
		),

		// Version 2.3.
		'bp-plupload'         => array(
			'file'         => "{$url}bp-plupload{$min}.js",
			'dependencies' => array( 'plupload', 'jquery', 'json2', 'wp-backbone' ),
			'footer'       => true,
		),
		'bp-avatar'           => array(
			'file'         => "{$url}avatar{$min}.js",
			'dependencies' => array( 'jcrop' ),
			'footer'       => true,
		),
		'bp-webcam'           => array(
			'file'         => "{$url}webcam{$min}.js",
			'dependencies' => array( 'bp-avatar' ),
			'footer'       => true,
		),

		// Version 2.4.
		'bp-cover-image'      => array(
			'file'         => "{$url}cover-image{$min}.js",
			'dependencies' => array(),
			'footer'       => true,
		),

		// Version 2.7.
		'bp-moment'           => array(
			'file'         => "{$url}vendor/moment-js/moment{$min}.js",
			'dependencies' => array(),
			'footer'       => true,
		),
		'bp-livestamp'        => array(
			'file'         => "{$url}vendor/livestamp{$min}.js",
			'dependencies' => array( 'jquery', 'bp-moment' ),
			'footer'       => true,
		),

		// Version 3.1.1
		'bp-jquery-validate'  => array(
			'file'         => "{$url}vendor/jquery.validate{$min}.js",
			'dependencies' => array( 'jquery' ),
			'footer'       => true,
		),
		'jquery-mask'         => array(
			'file'         => "{$url}vendor/jquery.mask{$min}.js",
			'dependencies' => array( 'jquery' ),
			'footer'       => true,
		),

		'giphy'                         => array(
			'file'         => "{$url}vendor/giphy{$min}.js",
			'dependencies' => array(),
			'footer'       => true,
		),
		'emojione'                      => array(
			'file'         => "{$url}emojione-edited.js",
			'dependencies' => array(),
			'footer'       => true,
		),
		'emojionearea'                  => array(
			'file'         => "{$url}emojionearea-edited{$min}.js",
			'dependencies' => array( 'emojione' ),
			'footer'       => true,
		),
		'bp-exif'                       => array( 'file' => "{$url}vendor/exif.js" ),
		'bp-media-dropzone'             => array(
			'file'         => "{$url}vendor/dropzone{$min}.js",
			'dependencies' => array(),
			'footer'       => false,
		),
		'bp-medium-editor'              => array(
			'file'         => "{$url}vendor/medium-editor{$min}.js",
			'dependencies' => array(),
			'footer'       => false,
		),
		'bp-select2'                    => array(
			'file'         => "{$url}vendor/select2.min.js",
			'dependencies' => array(),
			'footer'       => false,
		),
		'isInViewport'                  => array(
			'file'         => "{$url}vendor/isInViewport{$min}.js",
			'dependencies' => array(),
			'footer'       => true,
		),
		'jquery-datetimepicker'         => array(
			'file'         => "{$url}vendor/jquery.datetimepicker.full{$min}.js",
			'dependencies' => array( 'jquery' ),
			'footer'       => true,
		),
		'bp-media-videojs'              => array(
			'file'         => "{$url}vendor/video{$min}.js",
			'dependencies' => array(),
			'footer'       => false,
		),
		'bp-media-videojs-seek-buttons' => array(
			'file'         => "{$url}vendor/videojs-seek-buttons.min.js",
			'dependencies' => array(),
			'footer'       => false,
		),
		'bp-media-videojs-flv'          => array(
			'file'         => "{$url}vendor/flv.js",
			'dependencies' => array(),
			'footer'       => false,
		),
		'bp-media-videojs-flash'        => array(
			'file'         => "{$url}vendor/videojs-flash.js",
			'dependencies' => array(),
			'footer'       => false,
		),

	);

	// Add the "register.js" file if it's a register page and Profile Type field.
	if ( bp_is_register_page() && bp_get_xprofile_member_type_field_id() > 0 ) {
		$scripts['bp-register-page'] = array(
			'file'         => "{$url}register{$min}.js",
			'dependencies' => array( 'jquery' ),
			'footer'       => false,
		);
	}

	/*
	 * In 11.0.0 we are deprecating `bp-moment` in favor of WordPress' already bundled `moment`.
	 * @todo completely remove `bp-moment` in 12.0.0.
	 */
	if ( ! $is_moment_registered ) {
		$scripts['bp-moment']         = array( 'file' => "{$url}vendor/moment-js/moment{$min}.js", 'dependencies' => array(), 'footer' => true );
		$bp_livestamp                 = $scripts['bp-livestamp'];
		$bp_livestamp['dependencies'] = array( 'jquery', 'bp-moment' );

		// Reset 'bp-livestamp' after 'bp-moment'.
		unset( $scripts['bp-livestamp'] );
		$scripts['bp-livestamp'] = $bp_livestamp;

		// Version 2.7 - Add Moment.js locale to our $scripts array if we found one.
		if ( $moment_locale_url ) {
			$scripts['bp-moment-locale'] = array( 'file' => esc_url( $moment_locale_url ), 'dependencies' => array( 'bp-moment' ), 'footer' => true );
		}
	}

	/**
	 * Filters the BuddyBoss Core javascript files to register.
	 *
	 * Default handles include 'bp-confirm', 'bp-widget-members',
	 * 'bp-jquery-query', 'bp-jquery-cookie', and 'bp-jquery-scroll-to'.
	 *
	 * @since BuddyPress 2.1.0 'jquery-caret', 'jquery-atwho' added.
	 * @since BuddyPress 2.3.0 'bp-plupload', 'bp-avatar', 'bp-webcam' added.
	 * @since BuddyPress 2.4.0 'bp-cover-image' added.
	 * @since BuddyPress 2.7.0 'bp-moment', 'bp-livestamp' added.
	 *              'bp-moment-locale' is added conditionally if a moment.js locale file is found.
	 *
	 * @param array $value Array of javascript file information to register.
	 */
	$scripts = apply_filters( 'bp_core_register_common_scripts', $scripts );

	$version = bp_get_version();
	foreach ( $scripts as $id => $script ) {
		$dependencies = isset( $script['dependencies'] ) ? $script['dependencies'] : array();
		$footer       = isset( $script['footer'] ) ? $script['footer'] : false;
		wp_register_script( $id, $script['file'], $dependencies, $version, $footer );
	}

	/**
	 * Translation for select2 script text.
	 */
	$bp_select2 = array(
		'i18n' => array(
			'errorLoading'     => esc_js( __( 'The results could not be loaded.', 'buddyboss' ) ),
			'inputTooLong'     => esc_js( __( 'Please delete %% character', 'buddyboss' ) ),
			'inputTooShort'    => esc_js( __( 'Please enter %% or more characters', 'buddyboss' ) ),
			'loadingMore'      => esc_js( __( 'Loading more results…', 'buddyboss' ) ),
			'maximumSelected'  => esc_js( __( 'You can only select %% item', 'buddyboss' ) ),
			'noResults'        => esc_js( __( 'No results found', 'buddyboss' ) ),
			'searching'        => esc_js( __( 'Searching…', 'buddyboss' ) ),
			'removeAllItems'   => esc_js( __( 'Remove all items', 'buddyboss' ) ),
			'msginputTooShort' => esc_js( __( 'Start typing to find members', 'buddyboss' ) ),
		),
	);

	wp_localize_script( 'bp-select2', 'bp_select2', $bp_select2 );

	/**
	 * Translate EmojineArea
	 */
	$bp_emojionearea = array(
		'recent'            => __( 'Recent', 'buddyboss' ),
		'smileys_people'    => __( 'Smileys & People', 'buddyboss' ),
		'animals_nature'    => __( 'Animals & Nature', 'buddyboss' ),
		'food_drink'        => __( 'Food & Drink', 'buddyboss' ),
		'activity'          => __( 'Activity', 'buddyboss' ),
		'travel_places'     => __( 'Travel & Places', 'buddyboss' ),
		'objects'           => __( 'Objects', 'buddyboss' ),
		'symbols'           => __( 'Symbols', 'buddyboss' ),
		'flags'             => __( 'Flags', 'buddyboss' ),
		'tones'             => __( 'Diversity', 'buddyboss' ),
		'searchPlaceholder' => __( 'Search', 'buddyboss' ),
	);

	wp_localize_script( 'emojionearea', 'bp_emojionearea', $bp_emojionearea );

	/**
	 * Translate Dropzone
	 */
	wp_localize_script(
		'bp-media-dropzone',
		'bp_media_dropzone',
		array(
			'dictDefaultMessage'           => __( "Drop files here to upload", 'buddyboss' ),
			'dictFallbackMessage'          => __( "Your browser does not support drag'n'drop file uploads.", 'buddyboss' ),
			'dictFallbackText'             => __( "Please use the fallback form below to upload your files like in the olden days.", 'buddyboss' ),
			'dictFileTooBig'               => __( "File size is too big ({{filesize}} MB). Max file size: {{maxFilesize}} MB.", 'buddyboss' ),
			'dictInvalidFileType'          => __( "You can't upload files of this type.", 'buddyboss' ),
			'dictResponseError'            => __( "Server responded with {{statusCode}} code.", 'buddyboss' ),
			'dictCancelUpload'             => __( "Cancel upload", 'buddyboss' ),
			'dictUploadCanceled'           => __( "Upload canceled.", 'buddyboss' ),
			'dictCancelUploadConfirmation' => __( "Are you sure you want to cancel this upload?", 'buddyboss' ),
			'dictRemoveFile'               => __( "Remove file", 'buddyboss' ),
			'dictMaxFilesExceeded'         => __( "You cannot upload more than 10 files at a time.", 'buddyboss' ),
		)
	);
}
add_action( 'bp_enqueue_scripts', 'bp_core_register_common_scripts', 1 );
add_action( 'bp_admin_enqueue_scripts', 'bp_core_register_common_scripts', 1 );

/**
 * Register styles commonly used by BuddyPress.
 *
 * @since BuddyPress 2.1.0
 */
function bp_core_register_common_styles() {
	$min = bp_core_get_minified_asset_suffix();
	$url = buddypress()->plugin_url . 'bp-core/css/';

	/**
	 * Filters the URL for the Admin Bar stylesheet.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value URL for the Admin Bar stylesheet.
	 */
	$admin_bar_file = apply_filters( 'bp_core_admin_bar_css', "{$url}admin-bar{$min}.css" );

	/**
	 * Filters the BuddyBoss Core stylesheet files to register.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param array $value Array of stylesheet file information to register.
	 */
	$styles = apply_filters(
		'bp_core_register_common_styles',
		array(
			'bp-admin-bar'            => array(
				'file'         => $admin_bar_file,
				'dependencies' => array( 'admin-bar' ),
			),
			'bp-avatar'               => array(
				'file'         => "{$url}avatar{$min}.css",
				'dependencies' => array( 'jcrop' ),
			),
			'emojionearea'            => array(
				'file'         => "{$url}emojionearea-edited{$min}.css",
				'dependencies' => array(),
			),
			'bp-medium-editor'        => array(
				'file'         => "{$url}medium-editor{$min}.css",
				'dependencies' => array(),
			),
			'bp-medium-editor-beagle' => array(
				'file'         => "{$url}medium-editor-beagle{$min}.css",
				'dependencies' => array(),
			),
			'bp-select2'              => array(
				'file'         => "{$url}vendor/select2{$min}.css", // select2.min.css was issuing with rtl.
				'dependencies' => array(),
			),
			'jquery-datetimepicker'   => array(
				'file'         => "{$url}vendor/jquery.datetimepicker{$min}.css",
				'dependencies' => array(),
			),
			'bp-media-videojs-css'    => array(
				'file'         => "{$url}vendor/video-js{$min}.css",
				'dependencies' => array(),
			),
		)
	);

	foreach ( $styles as $id => $style ) {
		wp_register_style( $id, $style['file'], $style['dependencies'], bp_get_version() );

		wp_style_add_data( $id, 'rtl', true );
		if ( $min ) {
			wp_style_add_data( $id, 'suffix', $min );
		}
	}
}
add_action( 'bp_enqueue_scripts', 'bp_core_register_common_styles', 1 );
add_action( 'bp_admin_enqueue_scripts', 'bp_core_register_common_styles', 1 );

/**
 * Load the JS for "Are you sure?" confirm links.
 *
 * @since BuddyPress 1.1.0
 */
function bp_core_confirmation_js() {
	if ( is_multisite() && ! bp_is_root_blog() ) {
		return false;
	}

	wp_enqueue_script( 'bp-confirm' );

	wp_localize_script(
		'bp-confirm',
		'BP_Confirm',
		array(
			'are_you_sure' => __( 'Are you sure?', 'buddyboss' ),
		)
	);

}
add_action( 'bp_enqueue_scripts', 'bp_core_confirmation_js' );
add_action( 'bp_admin_enqueue_scripts', 'bp_core_confirmation_js' );

/**
 * Enqueues the css and js required by the Avatar UI.
 *
 * @since BuddyPress 2.3.0
 */
function bp_core_avatar_scripts() {
	if ( ! bp_avatar_is_front_edit() ) {
		return false;
	}

	// Enqueue the Attachments scripts for the Avatar UI.
	bp_attachments_enqueue_scripts( 'BP_Attachment_Avatar' );

	// Add Some actions for Theme backcompat.
	add_action( 'bp_after_profile_avatar_upload_content', 'bp_avatar_template_check' );
	add_action( 'bp_after_group_admin_content', 'bp_avatar_template_check' );
	add_action( 'bp_after_group_avatar_creation_step', 'bp_avatar_template_check' );
}
add_action( 'bp_enqueue_scripts', 'bp_core_avatar_scripts' );

/**
 * Enqueues the css and js required by the Cover Photo UI.
 *
 * @since BuddyPress 2.4.0
 */
function bp_core_cover_image_scripts() {
	if ( ! bp_attachments_cover_image_is_edit() ) {
		return false;
	}

	// Enqueue the Attachments scripts for the Cover Photo UI.
	bp_attachments_enqueue_scripts( 'BP_Attachment_Cover_Image' );
}
add_action( 'bp_enqueue_scripts', 'bp_core_cover_image_scripts' );

/**
 * Enqueues jCrop library and hooks BP's custom cropper JS.
 *
 * @since BuddyPress 1.1.0
 */
function bp_core_add_jquery_cropper() {
	wp_enqueue_style( 'jcrop' );
	wp_enqueue_script( 'jcrop', array( 'jquery' ) );
	add_action( 'wp_head', 'bp_core_add_cropper_inline_js' );
	add_action( 'wp_head', 'bp_core_add_cropper_inline_css' );
}

/**
 * Output the inline JS needed for the cropper to work on a per-page basis.
 *
 * @since BuddyPress 1.1.0
 */
function bp_core_add_cropper_inline_js() {

	/**
	 * Filters the return value of getimagesize to determine if an image was uploaded.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param array $value Array of data found by getimagesize.
	 */
	$image = apply_filters( 'bp_inline_cropper_image', getimagesize( bp_core_avatar_upload_path() . buddypress()->avatar_admin->image->dir ) );
	if ( empty( $image ) ) {
		return;
	}

	// Get avatar full width and height.
	$full_height = bp_core_avatar_full_height();
	$full_width  = bp_core_avatar_full_width();

	// Calculate Aspect Ratio.
	if ( ! empty( $full_height ) && ( $full_width != $full_height ) ) {
		$aspect_ratio = $full_width / $full_height;
	} else {
		$aspect_ratio = 1;
	}

	// Default cropper coordinates.
	// Smaller than full-width: cropper defaults to entire image.
	if ( $image[0] < $full_width ) {
		$crop_left  = 0;
		$crop_right = $image[0];

		// Less than 2x full-width: cropper defaults to full-width.
	} elseif ( $image[0] < ( $full_width * 2 ) ) {
		$padding_w  = round( ( $image[0] - $full_width ) / 2 );
		$crop_left  = $padding_w;
		$crop_right = $image[0] - $padding_w;

		// Larger than 2x full-width: cropper defaults to 1/2 image width.
	} else {
		$crop_left  = round( $image[0] / 4 );
		$crop_right = $image[0] - $crop_left;
	}

	// Smaller than full-height: cropper defaults to entire image.
	if ( $image[1] < $full_height ) {
		$crop_top    = 0;
		$crop_bottom = $image[1];

		// Less than double full-height: cropper defaults to full-height.
	} elseif ( $image[1] < ( $full_height * 2 ) ) {
		$padding_h   = round( ( $image[1] - $full_height ) / 2 );
		$crop_top    = $padding_h;
		$crop_bottom = $image[1] - $padding_h;

		// Larger than 2x full-height: cropper defaults to 1/2 image height.
	} else {
		$crop_top    = round( $image[1] / 4 );
		$crop_bottom = $image[1] - $crop_top;
	}

	?>

	<script>
		jQuery(window).load( function(){
			jQuery('#avatar-to-crop').Jcrop({
				onChange: showPreview,
				onSelect: updateCoords,
				aspectRatio: <?php echo (int) $aspect_ratio; ?>,
				setSelect: [ <?php echo (int) $crop_left; ?>, <?php echo (int) $crop_top; ?>, <?php echo (int) $crop_right; ?>, <?php echo (int) $crop_bottom; ?> ]
			});
		});

		function updateCoords(c) {
			jQuery('#x').val(c.x);
			jQuery('#y').val(c.y);
			jQuery('#w').val(c.w);
			jQuery('#h').val(c.h);
		}

		function showPreview(coords) {
			if ( parseInt(coords.w) > 0 ) {
				var fw = <?php echo (int) $full_width; ?>;
				var fh = <?php echo (int) $full_height; ?>;
				var rx = fw / coords.w;
				var ry = fh / coords.h;

				jQuery( '#avatar-crop-preview' ).css({
					width: Math.round(rx * <?php echo (int) $image[0]; ?>) + 'px',
					height: Math.round(ry * <?php echo (int) $image[1]; ?>) + 'px',
					marginLeft: '-' + Math.round(rx * coords.x) + 'px',
					marginTop: '-' + Math.round(ry * coords.y) + 'px'
				});
			}
		}
	</script>

	<?php
}

/**
 * Output the inline CSS for the BP image cropper.
 *
 * @since BuddyPress 1.1.0
 */
function bp_core_add_cropper_inline_css() {
	?>

	<style>
		.jcrop-holder { float: left; margin: 0 20px 20px 0; text-align: left; }
		#avatar-crop-pane { width: <?php echo bp_core_avatar_full_width(); ?>px; height: <?php echo bp_core_avatar_full_height(); ?>px; overflow: hidden; }
		#avatar-crop-submit { margin: 20px 0; }
		.jcrop-holder img,
		#avatar-crop-pane img,
		#avatar-upload-form img,
		#create-group-form img,
		#group-settings-form img { border: none !important; max-width: none !important; }
	</style>

	<?php
}

/**
 * Define the 'ajaxurl' JS variable, used by themes as an AJAX endpoint.
 *
 * @since BuddyPress 1.1.0
 */
function bp_core_add_ajax_url_js() {
	?>

	<script>var ajaxurl = '<?php echo bp_core_ajax_url(); ?>';</script>

	<?php
}
add_action( 'wp_head', 'bp_core_add_ajax_url_js' );

/**
 * Get the proper value for BP's ajaxurl.
 *
 * Designed to be sensitive to FORCE_SSL_ADMIN and non-standard multisite
 * configurations.
 *
 * @since BuddyPress 1.7.0
 *
 * @return string AJAX endpoint URL.
 */
function bp_core_ajax_url() {

	/**
	 * Filters the proper value for BuddyPress' ajaxurl.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $value Proper ajaxurl value for BuddyPress.
	 */
	return apply_filters( 'bp_core_ajax_url', admin_url( 'admin-ajax.php', is_ssl() ? 'admin' : 'http' ) );
}

/**
 * Get the JavaScript dependencies for buddypress.js.
 *
 * @since BuddyPress 2.0.0
 *
 * @return array The JavaScript dependencies.
 */
function bp_core_get_js_dependencies() {

	/**
	 * Filters the javascript dependencies for buddypress.js.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $value Array of javascript dependencies for buddypress.js.
	 */
	return apply_filters(
		'bp_core_get_js_dependencies',
		array(
			'jquery',
			'bp-confirm',
			'bp-widget-members',
			'bp-jquery-query',
			'bp-jquery-cookie',
			'bp-jquery-scroll-to',
			'wp-util',
			'wp-i18n',
		)
	);
}

/**
 * Add inline css to display the component's single item cover photo.
 *
 * @since BuddyPress 2.4.0
 *
 * @param bool $return True to get the inline css.
 * @return null|array|false The inline css or an associative array containing
 *                          the css rules and the style handle.
 */
function bp_add_cover_image_inline_css( $return = false ) {
	$bp = buddypress();

	// Find the component of the current item.
	if ( bp_is_user() ) {

		// User is not allowed to upload cover photos
		// no need to carry on.
		if ( bp_disable_cover_image_uploads() ) {
			return;
		}

		$cover_image_object = array(
			'component' => 'xprofile',
			'object'    => $bp->displayed_user,
		);
	} elseif ( bp_is_group() ) {

		// Users are not allowed to upload cover photos for their groups
		// no need to carry on.
		if ( bp_disable_group_cover_image_uploads() ) {
			return;
		}

		$cover_image_object = array(
			'component' => 'groups',
			'object'    => $bp->groups->current_group,
		);
	} else {
		$cover_image_object = apply_filters( 'bp_current_cover_image_object_inline_css', array() );
	}

	// Bail if no component were found.
	if ( empty( $cover_image_object['component'] ) || empty( $cover_image_object['object'] ) || ! bp_is_active( $cover_image_object['component'], 'cover_image' ) ) {
		return;
	}

	// Get the settings of the cover photo feature for the current component.
	$params = bp_attachments_get_cover_image_settings( $cover_image_object['component'] );

	// Bail if no params.
	if ( empty( $params ) ) {
		return;
	}

	// Try to call the callback.
	if ( is_callable( $params['callback'] ) ) {

		$object_dir = $cover_image_object['component'];

		if ( 'xprofile' === $object_dir ) {
			$object_dir = 'members';
		}

		$cover_image = bp_attachments_get_attachment(
			'url',
			array(
				'object_dir' => $object_dir,
				'item_id'    => $cover_image_object['object']->id,
			)
		);

		if ( empty( $cover_image ) ) {
			if ( ! empty( $params['default_cover'] ) ) {
				$cover_image = $params['default_cover'];
			}
		}

		$inline_css = call_user_func_array(
			$params['callback'],
			array(
				array(
					'cover_image' => esc_url_raw( $cover_image ),
					'component'   => sanitize_key( $cover_image_object['component'] ),
					'object_id'   => (int) $cover_image_object['object']->id,
					'width'       => (int) $params['width'],
					'height'      => (int) $params['height'],
				),
			)
		);

		// Finally add the inline css to the handle.
		if ( ! empty( $inline_css ) ) {

			// Used to get the css when Ajax setting the cover photo.
			if ( true === $return ) {
				return array(
					'css_rules' => '<style>' . "\n" . $inline_css . "\n" . '</style>',
					'handle'    => $params['theme_handle'],
				);
			}

			wp_add_inline_style( $params['theme_handle'], $inline_css );
		} else {
			return false;
		}
	}
}
add_action( 'bp_enqueue_scripts', 'bp_add_cover_image_inline_css', 11 );

/**
 * Enqueues livestamp.js on BuddyPress pages.
 *
 * @since BuddyPress 2.7.0
 */
function bp_core_add_livestamp() {
	if ( ! is_buddypress() ) {
		return;
	}

	bp_core_enqueue_livestamp();
}
add_action( 'bp_enqueue_scripts', 'bp_core_add_livestamp' );

/**
 * Enqueue and localize livestamp.js script.
 *
 * @since BuddyPress 2.7.0
 */
function bp_core_enqueue_livestamp() {
	// If bp-livestamp isn't enqueued, do it now.
	if ( wp_script_is( 'bp-livestamp' ) ) {
		return;
	}

	/*
	 * Only enqueue Moment.js locale if we registered it in
	 * bp_core_register_common_scripts().
	 */
	if ( wp_script_is( 'bp-moment-locale', 'registered' ) ) {
		wp_enqueue_script( 'bp-moment-locale' );
		wp_add_inline_script( 'bp-livestamp', bp_core_moment_js_config() );
	} else {
		wp_add_inline_script(
			'moment',
			sprintf(
				"moment.updateLocale( '%s', %s );",
				get_user_locale(),
				wp_json_encode(
					array(
						'relativeTime' => array(
							/* Translators: %s is the relative time (eg: in a few seconds). */
							'future' => __( 'in %s', 'buddyboss' ),
							/* translators: %s: the human time diff. */
							'past'   => __( '%s ago', 'buddyboss' ),
							's'      => __( 'a few seconds', 'buddyboss' ),
							'm'      => __( 'a minute', 'buddyboss' ),
							/* Translators: %d is the amount of minutes. */
							'mm'     => __( '%d minutes', 'buddyboss' ),
							'h'      => __( 'an hour', 'buddyboss' ),
							/* Translators: %d is the amount of hours. */
							'hh'     => __( '%d hours', 'buddyboss' ),
							'd'      => __( 'a day', 'buddyboss' ),
							/* Translators: %d is the amount of days. */
							'dd'     => __( '%d days', 'buddyboss' ),
							'M'      => __( 'a month', 'buddyboss' ),
							/* Translators: %d is the amount of months. */
							'MM'     => __( '%d months', 'buddyboss' ),
							'y'      => __( 'a year', 'buddyboss' ),
							/* Translators: %d is the amount of years. */
							'yy'     => __( '%d years', 'buddyboss' ),
						),
					)
				)
			)
		);
	}

	wp_enqueue_script( 'bp-livestamp' );
}

/**
 * Return moment.js config.
 *
 * @since             BuddyPress 2.7.0
 * @deprecated        2.3.90 Softly deprecated as we're keeping the function into this file
 *                    to avoid fatal errors if deprecated code is ignored.
 *
 * @return string
 */
function bp_core_moment_js_config() {
	_deprecated_function( __FUNCTION__, '2.3.90' );

	// Grab the locale from the enqueued JS.
	$moment_locale = wp_scripts()->query( 'bp-moment-locale' );
	$moment_locale = substr( $moment_locale->src, strpos( $moment_locale->src, '/moment-js/locale/' ) + 18 );
	$moment_locale = str_replace( '.js', '', $moment_locale );

	$inline_js = <<<EOD
jQuery(function() {
	moment.locale( '{$moment_locale}' );
});
EOD;

	return $inline_js;
}

/**
 * Enqueues the jQuery validate js.
 *
 * @since BuddyPress 3.1.1
 */
function bp_core_jquery_validate_scripts() {

	// wp_enqueue_script( 'bp-jquery-validate' );
	// add_action( 'wp_head', 'bp_core_add_jquery_validate_inline_js' );
}
add_action( 'bp_enqueue_scripts', 'bp_core_jquery_validate_scripts' );


/**
 * Output the inline JS needed for the jQuery validate
 *
 * @since BuddyPress 3.1.1
 */
function bp_core_add_jquery_validate_inline_js() {
	?>

	<script>
		jQuery(document).ready(function(){
			jQuery('#buddypress #signup-form').validate({
				submitHandler: function(form) {
				  jQuery(form).submit();
				}
			});
		});
	</script>

	<?php
}

/**
 * Enqueues jquery.mask.js on BuddyPress pages.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_add_jquery_mask() {
	if ( ! is_buddypress() ) {
		return;
	}

	if ( 'profile' != bp_current_component() || 'edit' != bp_current_action() ) {
		return;// we need this script only on profile edit screens
	}

	if ( wp_script_is( 'jquery-mask' ) ) {
		return;
	}

	wp_enqueue_script( 'jquery-mask' );

	add_action( 'wp_footer', 'bp_core_add_jquery_mask_inline_js' );
}
add_action( 'bp_enqueue_scripts', 'bp_core_add_jquery_mask' );

/**
 * Prints script to add input mask to all telephone fields.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_add_jquery_mask_inline_js() {
	?>

	<script>
		jQuery(document).ready(function(){
			jQuery(".field_type_telephone").each(function(){
				var $this = jQuery(this),
					field_id = $this.find('.input_mask_details').data('field_id'),
					pmask = $this.find('.input_mask_details').data('val');

				if ( field_id && pmask ) {
					jQuery( '#' + field_id ).mask( pmask ).bind('keypress', function(e){if(e.which == 13){jQuery(this).blur();} } );
				}
			});
		});
	</script>

	<?php
}

/**
 * Load the JS for register page and populate conditional field
 *
 * @since BuddyBoss 1.1.6
 */
function bp_core_register_page_js() {

	if ( bp_is_register_page() && bp_get_xprofile_member_type_field_id() > 0 ) {
		wp_enqueue_script( 'bp-register-page' );
		wp_enqueue_editor();

		$data = array(
			'ajaxurl'        => bp_core_ajax_url(),
			'field_id'       => 'field_' . bp_get_xprofile_member_type_field_id(),
			'nonce'          => wp_create_nonce( 'bp-core-register-page-js' ),
			'mismatch_email' => __( 'Mismatch', 'buddyboss' ),
			'valid_email'    => __( 'Enter valid email', 'buddyboss' ),
			'required_field' => __( 'This is a required field.', 'buddyboss' ),
		);

		wp_localize_script( 'bp-register-page', 'BP_Register', apply_filters( 'bp_core_register_js_settings', $data ) );
	}

}

add_action( 'bp_enqueue_scripts', 'bp_core_register_page_js' );

function bp_core_enqueue_isInViewPort() {
	if ( bp_is_user_media() ||
		 bp_is_single_album() ||
		 bp_is_media_directory() ||
		 bp_is_activity_component() ||
		 bp_is_group_activity() ||
		 bp_is_group_media() ||
		 bp_is_group_albums() ||
		 bp_is_messages_component() ||
		 ( function_exists( 'bp_is_profile_media_support_enabled' ) && bp_is_profile_media_support_enabled() ) ||
		 ( function_exists( 'bp_is_group_media_support_enabled' ) && bp_is_group_media_support_enabled() ) ||
		 ( function_exists( 'bp_is_group_albums_support_enabled' ) && bp_is_group_albums_support_enabled() ) ||
		 ( function_exists( 'bp_is_messages_media_support_enabled' ) && bp_is_messages_media_support_enabled() )
	) {
		wp_enqueue_script( 'isInViewport' );
	}
}
add_action( 'bp_enqueue_scripts', 'bp_core_enqueue_isInViewPort', 5 );

/**
 * Load the JS template for link preview.
 *
 * @since BuddyBoss 2.3.60
 */
function bb_load_link_preview_js_template() {
	bp_get_template_part( 'common/js-templates/members/bb-link-preview' );
}
add_action( 'bp_enqueue_scripts', 'bb_load_link_preview_js_template' );
