<?php
/**
 * BuddyPress Attachments functions.
 *
 * @package BuddyBoss\Attachments
 * @since BuddyPress 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Check if the current WordPress version is using Plupload 2.1.1
 *
 * Plupload 2.1.1 was introduced in WordPress 3.9. Our bp-plupload.js
 * script requires it. So we need to make sure the current WordPress
 * match with our needs.
 *
 * @since BuddyPress 2.3.0
 * @since BuddyPress 3.0.0 We now require WP >= 4.5, so this is always true.
 *
 * @return bool Always true.
 */
function bp_attachments_is_wp_version_supported() {
	return true;
}

/**
 * Get the Attachments Uploads dir data.
 *
 * @since BuddyPress 2.4.0
 *
 * @param string $data The data to get. Possible values are: 'dir', 'basedir' & 'baseurl'.
 *                     Leave empty to get all datas.
 * @return string|array The needed Upload dir data.
 */
function bp_attachments_uploads_dir_get( $data = '' ) {
	$attachments_dir = 'buddypress';
	$retval          = '';

	if ( 'dir' === $data ) {
		$retval = $attachments_dir;
	} else {
		$upload_data = bp_upload_dir();

		// Return empty string, if Uploads data are not available.
		if ( ! $upload_data ) {
			return $retval;
		}

		// Build the Upload data array for BuddyPress attachments.
		foreach ( $upload_data as $key => $value ) {
			if ( 'basedir' === $key || 'baseurl' === $key ) {
				$upload_data[ $key ] = trailingslashit( $value ) . $attachments_dir;

				// Fix for HTTPS.
				if ( 'baseurl' === $key && is_ssl() ) {
					$upload_data[ $key ] = str_replace( 'http://', 'https://', $upload_data[ $key ] );
				}
			} else {
				unset( $upload_data[ $key ] );
			}
		}

		// Add the dir to the array.
		$upload_data['dir'] = $attachments_dir;

		if ( empty( $data ) ) {
			$retval = $upload_data;
		} elseif ( isset( $upload_data[ $data ] ) ) {
			$retval = $upload_data[ $data ];
		}
	}

	/**
	 * Filter here to edit the Attachments upload dir data.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param string|array $retval The needed Upload dir data or the full array of data
	 * @param string       $data   The data requested
	 */
	return apply_filters( 'bp_attachments_uploads_dir_get', $retval, $data );
}

/**
 * Gets the upload dir array for cover photos.
 *
 * @since BuddyPress 3.0.0
 *
 * @return array See wp_upload_dir().
 */
function bp_attachments_cover_image_upload_dir( $args = array() ) {
	$object_id           = 0;
	$object_type         = isset( $_POST['item_type'] ) ? sanitize_text_field( $_POST['item_type'] ) : '';
	$args['object_type'] = $object_type;

	// Default values are for profiles.
	if ( empty( $object_type ) ) {
		$object_id = bp_displayed_user_id();

		if ( empty( $object_id ) ) {
			$object_id = bp_loggedin_user_id();
		}
	}

	$object_directory = 'members';

	// We're in a group, edit default values.
	if ( bp_is_group() || bp_is_group_create() ) {
		if ( empty( $object_type ) ) {
			$object_id = bp_get_current_group_id();
		}

		$object_directory = 'groups';
	}

	$r = bp_parse_args(
		$args,
		array(
			'object_id'        => $object_id,
			'object_type'      => $object_type,
			'object_directory' => $object_directory,
		),
		'cover_image_upload_dir'
	);

	// Set the subdir.
	$subdir = '/' . $r['object_directory'] . '/' . $r['object_id'] . '/cover-image';

	$upload_dir = bp_attachments_uploads_dir_get();

	/**
	 * Filters the cover photo upload directory.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param array $value      Array containing the path, URL, and other helpful settings.
	 * @param array $upload_dir The original Uploads dir.
	 */
	return apply_filters(
		'bp_attachments_cover_image_upload_dir',
		array(
			'path'    => $upload_dir['basedir'] . $subdir,
			'url'     => set_url_scheme( $upload_dir['baseurl'] ) . $subdir,
			'subdir'  => $subdir,
			'basedir' => $upload_dir['basedir'],
			'baseurl' => set_url_scheme( $upload_dir['baseurl'] ),
			'error'   => false,
		),
		$upload_dir
	);
}

/**
 * Get the max upload file size for any attachment.
 *
 * @since BuddyPress 2.4.0
 *
 * @param string $type A string to inform about the type of attachment
 *                     we wish to get the max upload file size for.
 * @return int Max upload file size for any attachment.
 */
function bp_attachments_get_max_upload_file_size( $type = '' ) {
	$fileupload_maxk = bp_core_get_root_option( 'fileupload_maxk' );

	if ( '' === $fileupload_maxk ) {
		$fileupload_maxk = 5120000; // 5mb;
	} else {
		$fileupload_maxk = $fileupload_maxk * 1024;
	}

	/**
	 * Filter here to edit the max upload file size.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param int    $fileupload_maxk Max upload file size for any attachment.
	 * @param string $type            The attachment type (eg: 'avatar' or 'cover_image').
	 */
	return apply_filters( 'bp_attachments_get_max_upload_file_size', $fileupload_maxk, $type );
}

/**
 * Get allowed types for any attachment.
 *
 * @since BuddyPress 2.4.0
 *
 * @param string $type The extension types to get.
 *                     Default: 'avatar'.
 * @return array The list of allowed extensions for attachments.
 */
function bp_attachments_get_allowed_types( $type = 'avatar' ) {
	// Defaults to BuddyPress supported image extensions.
	$exts = array( 'jpeg', 'gif', 'png' );

	/**
	 * It's not a BuddyPress feature, get the allowed extensions
	 * matching the $type requested.
	 */
	if ( 'avatar' !== $type && 'cover_image' !== $type ) {
		// Reset the default exts.
		$exts = array();

		switch ( $type ) {
			case 'video':
				$exts = wp_get_video_extensions();
				break;

			case 'audio':
				$exts = wp_get_video_extensions();
				break;

			default:
				$allowed_mimes = get_allowed_mime_types();

				/**
				 * Search for allowed mimes matching the type.
				 *
				 * Eg: using 'application/vnd.oasis' as the $type
				 * parameter will get all OpenOffice extensions supported
				 * by WordPress and allowed for the current user.
				 */
				if ( '' !== $type ) {
					$allowed_mimes = preg_grep( '/' . addcslashes( $type, '/.+-' ) . '/', $allowed_mimes );
				}

				$allowed_types = array_keys( $allowed_mimes );

				// Loop to explode keys using '|'.
				foreach ( $allowed_types as $allowed_type ) {
					$t    = explode( '|', $allowed_type );
					$exts = array_merge( $exts, (array) $t );
				}
				break;
		}
	}

	/**
	 * Filter here to edit the allowed extensions by attachment type.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param array  $exts List of allowed extensions.
	 * @param string $type The requested file type.
	 */
	return apply_filters( 'bp_attachments_get_allowed_types', $exts, $type );
}

/**
 * Get allowed attachment mime types.
 *
 * @since BuddyPress 2.4.0
 *
 * @param string $type          The extension types to get (Optional).
 * @param array  $allowed_types List of allowed extensions.
 * @return array List of allowed mime types.
 */
function bp_attachments_get_allowed_mimes( $type = '', $allowed_types = array() ) {
	if ( empty( $allowed_types ) ) {
		$allowed_types = bp_attachments_get_allowed_types( $type );
	}

	$validate_mimes = wp_match_mime_types( join( ',', $allowed_types ), wp_get_mime_types() );
	$allowed_mimes  = array_map( 'implode', $validate_mimes );

	/**
	 * Include jpg type if jpeg is set
	 */
	if ( isset( $allowed_mimes['jpeg'] ) && ! isset( $allowed_mimes['jpg'] ) ) {
		$allowed_mimes['jpg'] = $allowed_mimes['jpeg'];
	}

	return $allowed_mimes;
}

/**
 * Check the uploaded attachment type is allowed.
 *
 * @since BuddyPress 2.4.0
 *
 * @param string $file          Full path to the file.
 * @param string $filename      The name of the file (may differ from $file due to $file being
 *                              in a tmp directory).
 * @param array  $allowed_mimes The attachment allowed mimes (Required).
 * @return bool True if the attachment type is allowed. False otherwise
 */
function bp_attachments_check_filetype( $file, $filename, $allowed_mimes ) {
	$filetype = wp_check_filetype_and_ext( $file, $filename, $allowed_mimes );

	if ( ! empty( $filetype['ext'] ) && ! empty( $filetype['type'] ) ) {
		return true;
	}

	return false;
}

/**
 * Use the absolute path to an image to set an attachment type for a given item.
 *
 * @since BuddyPress 2.4.0
 *
 * @param string $type The attachment type to create (avatar or cover_image). Default: avatar.
 * @param array  $args {
 *     @type int    $item_id   The ID of the object (Required). Default: 0.
 *     @type string $object    The object type (eg: group, user, blog) (Required). Default: 'user'.
 *     @type string $component The component for the object (eg: groups, xprofile, blogs). Default: ''.
 *     @type string $image     The absolute path to the image (Required). Default: ''.
 *     @type int    $crop_w    Crop width. Default: 0.
 *     @type int    $crop_h    Crop height. Default: 0.
 *     @type int    $crop_x    The horizontal starting point of the crop. Default: 0.
 *     @type int    $crop_y    The vertical starting point of the crop. Default: 0.
 * }
 * @return bool True on success, false otherwise.
 */
function bp_attachments_create_item_type( $type = 'avatar', $args = array() ) {
	if ( empty( $type ) || ( $type !== 'avatar' && $type !== 'cover_image' ) ) {
		return false;
	}

	$r = bp_parse_args(
		$args,
		array(
			'item_id'   => 0,
			'object'    => 'user',
			'component' => '',
			'image'     => '',
			'crop_w'    => 0,
			'crop_h'    => 0,
			'crop_x'    => 0,
			'crop_y'    => 0,
		),
		'create_item_' . $type
	);

	if ( empty( $r['item_id'] ) || empty( $r['object'] ) || ! file_exists( $r['image'] ) || ! @getimagesize( $r['image'] ) ) {
		return false;
	}

	// Make sure the file path is safe.
	if ( 1 === validate_file( $r['image'] ) ) {
		return false;
	}

	// Set the component if not already done.
	if ( empty( $r['component'] ) ) {
		if ( 'user' === $r['object'] ) {
			$r['component'] = 'xprofile';
		} else {
			$r['component'] = $r['object'] . 's';
		}
	}

	// Get allowed mimes for the Attachment type and check the image one is.
	$allowed_mimes = bp_attachments_get_allowed_mimes( $type );
	$is_allowed    = wp_check_filetype( $r['image'], $allowed_mimes );

	// It's not an image.
	if ( ! $is_allowed['ext'] ) {
		return false;
	}

	// Init the Attachment data.
	$attachment_data = array();

	if ( 'avatar' === $type ) {
		// Set crop width for the avatar if not given.
		if ( empty( $r['crop_w'] ) ) {
			$r['crop_w'] = bp_core_avatar_full_width();
		}

		// Set crop height for the avatar if not given.
		if ( empty( $r['crop_h'] ) ) {
			$r['crop_h'] = bp_core_avatar_full_height();
		}

		if ( is_callable( $r['component'] . '_avatar_upload_dir' ) ) {
			$dir_args = array( $r['item_id'] );

			// In case  of xprofile, we need an extra argument.
			if ( 'xprofile' === $r['component'] ) {
				$dir_args = array( false, $r['item_id'] );
			}

			$attachment_data = call_user_func_array( $r['component'] . '_avatar_upload_dir', $dir_args );
		}
	} elseif ( 'cover_image' === $type ) {
		$attachment_data = bp_attachments_cover_image_upload_dir();

		// The BP Attachments Uploads Dir is not set, stop.
		if ( ! $attachment_data ) {
			return false;
		}

		// Default to members for xProfile.
		$object_subdir = 'members';

		if ( 'xprofile' !== $r['component'] ) {
			$object_subdir = sanitize_key( $r['component'] );
		}

		// Set Subdir.
		$attachment_data['subdir'] = $object_subdir . '/' . $r['item_id'] . '/cover-image';

		// Set Path.
		$attachment_data['path'] = trailingslashit( $attachment_data['basedir'] ) . $attachment_data['subdir'];
	}

	if ( ! isset( $attachment_data['path'] ) || ! isset( $attachment_data['subdir'] ) ) {
		return false;
	}

	// It's not a regular upload, we may need to create some folders.
	if ( ! is_dir( $attachment_data['path'] ) ) {
		if ( ! wp_mkdir_p( $attachment_data['path'] ) ) {
			return false;
		}
	}

	// Set the image name and path.
	$image_file_name = wp_unique_filename( $attachment_data['path'], basename( $r['image'] ) );
	$image_file_path = $attachment_data['path'] . '/' . $image_file_name;

	// Copy the image file into the avatar dir.
	if ( ! copy( $r['image'], $image_file_path ) ) {
		return false;
	}

	// Init the response.
	$created = false;

	// It's an avatar, we need to crop it.
	if ( 'avatar' === $type ) {
		$created = bp_core_avatar_handle_crop(
			array(
				'object'        => $r['object'],
				'avatar_dir'    => trim( dirname( $attachment_data['subdir'] ), '/' ),
				'item_id'       => (int) $r['item_id'],
				'original_file' => trailingslashit( $attachment_data['subdir'] ) . $image_file_name,
				'crop_w'        => $r['crop_w'],
				'crop_h'        => $r['crop_h'],
				'crop_x'        => $r['crop_x'],
				'crop_y'        => $r['crop_y'],
			)
		);

		// It's a cover photo we need to fit it to feature's dimensions.
	} elseif ( 'cover_image' === $type ) {
		$cover_image = bp_attachments_cover_image_generate_file(
			array(
				'file'            => $image_file_path,
				'component'       => $r['component'],
				'cover_image_dir' => $attachment_data['path'],
			)
		);

		$created = ! empty( $cover_image['cover_file'] );
	}

	// Remove copied file if it fails.
	if ( ! $created ) {
		@unlink( $image_file_path );
	}

	// Return the response.
	return $created;
}

/**
 * Get the url or the path for a type of attachment.
 *
 * @since BuddyPress 2.4.0
 *
 * @param string $data whether to get the url or the path.
 * @param array  $args {
 *     @type string $object_dir  The object dir (eg: members/groups). Defaults to members.
 *     @type int    $item_id     The object id (eg: a user or a group id). Defaults to current user.
 *     @type string $type        The type of the attachment which is also the subdir where files are saved.
 *                               Defaults to 'cover-image'
 *     @type string $file        The name of the file.
 * }
 * @return string|bool The url or the path to the attachment, false otherwise
 */
function bp_attachments_get_attachment( $data = 'url', $args = array() ) {
	// Default value.
	$attachment_data = false;

	$r = bp_parse_args(
		$args,
		array(
			'object_dir' => 'members',
			'item_id'    => bp_loggedin_user_id(),
			'type'       => 'cover-image',
			'file'       => '',
		),
		'attachments_get_attachment_src'
	);

	/**
	 * Filters whether or not to handle fetching a BuddyPress image attachment.
	 *
	 * If you want to override this function, make sure you return false.
	 *
	 * @since BuddyPress 2.5.1
	 *
	 * @param null|string $value If null is returned, proceed with default behaviour. Otherwise, value returned verbatim.
	 * @param array $r {
	 *     @type string $object_dir The object dir (eg: members/groups). Defaults to members.
	 *     @type int    $item_id    The object id (eg: a user or a group id). Defaults to current user.
	 *     @type string $type       The type of the attachment which is also the subdir where files are saved.
	 *                              Defaults to 'cover-image'
	 *     @type string $file       The name of the file.
	 * }
	 */
	$pre_filter = apply_filters( 'bp_attachments_pre_get_attachment', null, $r );
	if ( $pre_filter !== null ) {
		return $pre_filter;
	}

	// Get BuddyPress Attachments Uploads Dir datas.
	$bp_attachments_uploads_dir = bp_attachments_uploads_dir_get();

	// The BP Attachments Uploads Dir is not set, stop.
	if ( ! $bp_attachments_uploads_dir ) {
		return $attachment_data;
	}

	/**
	 * Filters BuddyPress image attachment sub directory.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $subdir     The sub dir to uploaded BuddyPress image.
	 * @param string $object_dir The object dir (eg: members/groups). Defaults to members.
	 * @param int    $item_id    The object id (eg: a user or a group id). Defaults to current user.
	 * @param string $type       The type of the attachment which is also the subdir where files are saved.
	 *                           Defaults to 'cover-image'
	 */
	$type_subdir = apply_filters( 'bb_attachments_get_attachment_sub_dir', $r['object_dir'] . '/' . $r['item_id'] . '/' . $r['type'], $r['object_dir'], $r['item_id'], $r['type'] );

	$type_dir = trailingslashit( $bp_attachments_uploads_dir['basedir'] ) . $type_subdir;

	/**
	 * Filters BuddyPress image attachment directory.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $dir        The dir to uploaded BuddyPress image.
	 * @param string $object_dir The object dir (eg: members/groups). Defaults to members.
	 * @param int    $item_id    The object id (eg: a user or a group id). Defaults to current user.
	 * @param string $type       The type of the attachment which is also the subdir where files are saved.
	 *                           Defaults to 'cover-image'
	 */
	$type_dir = apply_filters( 'bb_attachments_get_attachment_dir', $type_dir, $r['object_dir'], $r['item_id'], $r['type'] );

	if ( 1 === validate_file( $type_dir ) || ! is_dir( $type_dir ) ) {
		return bb_get_default_profile_group_cover( $data, $r );
	}

	if ( ! empty( $r['file'] ) ) {
		if ( ! file_exists( trailingslashit( $type_dir ) . $r['file'] ) ) {
			return bb_get_default_profile_group_cover( $data, $r );
		}

		if ( 'url' === $data ) {
			$attachment_data = trailingslashit( $bp_attachments_uploads_dir['baseurl'] ) . $type_subdir . '/' . $r['file'];
		} else {
			$attachment_data = trailingslashit( $type_dir ) . $r['file'];
		}
	} else {
		$file = false;

		// Open the directory and get the first file.
		if ( $att_dir = opendir( $type_dir ) ) {

			while ( false !== ( $attachment_file = readdir( $att_dir ) ) ) {
				// Look for the first file having the type in its name.
				if ( false !== strpos( $attachment_file, $r['type'] ) && empty( $file ) ) {
					$file = $attachment_file;
					break;
				}
			}
		}

		if ( empty( $file ) ) {
			return bb_get_default_profile_group_cover( $data, $r );
		}

		if ( 'url' === $data ) {
			$attachment_data = trailingslashit( $bp_attachments_uploads_dir['baseurl'] ) . $type_subdir . '/' . $file;
		} else {
			$attachment_data = trailingslashit( $type_dir ) . $file;
		}
	}

	return $attachment_data;
}

/**
 * Delete an attachment for the given arguments
 *
 * @since BuddyPress 2.4.0
 *
 * @see bp_attachments_get_attachment() For more information on accepted arguments.
 *
 * @param array $args Array of arguments for the attachment deletion.
 * @return bool True if the attachment was deleted, false otherwise.
 */
function bp_attachments_delete_file( $args = array() ) {

	$r = bp_parse_args(
		$args,
		array(
			'object_dir' => 'members',
			'item_id'    => bp_loggedin_user_id(),
			'type'       => 'cover-image',
			'file'       => '',
		),
		'bp_attachments_delete_file_args'
	);

	$attachment_path = '';
	if ( is_admin() && 0 === $r['item_id'] ) {

		$upload_dir = bp_attachments_uploads_dir_get();

		$cover_url = bb_get_default_custom_upload_profile_cover();
		$subdir    = 'members/0/cover-image';
		if ( 'groups' === $r['object_dir'] ) {
			$cover_url = bb_get_default_custom_upload_group_cover();
			$subdir    = 'groups/0/cover-image';
		}

		/**
		 * Filter to update the subdirectory.
		 *
		 * @since BuddyBoss 2.0.4
		 *
		 * @param string $subdir Subdirectory name.
		 * @param array  $r      Arguments.
		 */
		$subdir = apply_filters( 'bb_attachments_delete_file_subdir', $subdir, $r );

		$type_dir = trailingslashit( $upload_dir['basedir'] ) . $subdir;

		if ( 1 === validate_file( $type_dir ) || ! is_dir( $type_dir ) ) {
			return false;
		}

		if ( ! empty( $cover_url ) ) {

			$r['file'] = basename( $cover_url );

			if ( ! empty( $r['file'] ) ) {
				if ( ! file_exists( trailingslashit( $type_dir ) . $r['file'] ) ) {
					return false;
				}

				$attachment_path = trailingslashit( $type_dir ) . $r['file'];
			}
		} else {
			$file = false;

			// Open the directory and get the first file.
			if ( $att_dir = opendir( $type_dir ) ) {

				while ( false !== ( $attachment_file = readdir( $att_dir ) ) ) {
					// Look for the first file having the type in its name.
					if ( false !== strpos( $attachment_file, $r['type'] ) && empty( $file ) ) {
						$file = $attachment_file;
						break;
					}
				}
			}

			if ( empty( $file ) ) {
				return false;
			}

			$attachment_path = trailingslashit( $type_dir ) . $file;
		}
	} else {

		$has_cover = true;
		if ( 'members' === $r['object_dir'] ) {
			$has_cover = bp_attachments_get_user_has_cover_image( $r['item_id'] );
		} elseif ( 'groups' === $r['object_dir'] ) {
			$has_cover = bp_attachments_get_group_has_cover_image( $r['item_id'] );
		}

		if ( ! $has_cover ) {
			return false;
		}

		$attachment_path = bp_attachments_get_attachment( 'path', $args );
	}

	/**
	 * Filters whether or not to handle deleting an existing BuddyPress attachment.
	 *
	 * If you want to override this function, make sure you return false.
	 *
	 * @since BuddyPress 2.5.1
	 *
	 * @param bool $value Whether or not to delete the BuddyPress attachment.
	 * @param array $args Array of arguments for the attachment deletion.
	 */
	if ( ! apply_filters( 'bp_attachments_pre_delete_file', true, $args ) ) {
		return true;
	}

	if ( empty( $attachment_path ) ) {
		return false;
	}

	@unlink( $attachment_path );
	return true;
}

/**
 * Get the BuddyPress Plupload settings.
 *
 * @since BuddyPress 2.3.0
 *
 * @return array List of BuddyPress Plupload settings.
 */
function bp_attachments_get_plupload_default_settings() {

	$max_upload_size = wp_max_upload_size();

	if ( ! $max_upload_size ) {
		$max_upload_size = 0;
	}

	$defaults = array(
		'runtimes'            => 'html5,flash,silverlight,html4',
		'file_data_name'      => 'file',
		'multipart_params'    => array(
			'action'   => 'bp_upload_attachment',
			'_wpnonce' => wp_create_nonce( 'bp-uploader' ),
		),
		'url'                 => admin_url( 'admin-ajax.php', 'relative' ),
		'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
		'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
		'filters'             => array(
			'max_file_size' => $max_upload_size . 'b',
		),
		'multipart'           => true,
		'urlstream_upload'    => true,
	);

	// WordPress is not allowing multi selection for iOs 7 device.. See #29602.
	if ( wp_is_mobile() && isset( $_SERVER['HTTP_USER_AGENT'] ) && strpos( $_SERVER['HTTP_USER_AGENT'], 'OS 7_' ) !== false &&
		strpos( $_SERVER['HTTP_USER_AGENT'], 'like Mac OS X' ) !== false ) {

		$defaults['multi_selection'] = false;
	}

	$settings = array(
		'defaults'      => $defaults,
		'browser'       => array(
			'mobile'    => wp_is_mobile(),
			'supported' => _device_can_upload(),
		),
		'limitExceeded' => is_multisite() && ! is_upload_space_available(),
	);

	/**
	 * Filter the BuddyPress Plupload default settings.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param array $settings Default Plupload parameters array.
	 */
	return apply_filters( 'bp_attachments_get_plupload_default_settings', $settings );
}

/**
 * Builds localization strings for the BuddyPress Uploader scripts.
 *
 * @since BuddyPress 2.3.0
 *
 * @return array Plupload default localization strings.
 */
function bp_attachments_get_plupload_l10n() {
	// Localization strings.
	return apply_filters(
		'bp_attachments_get_plupload_l10n',
		array(
			'queue_limit_exceeded'      => __( 'You have attempted to queue too many files.', 'buddyboss' ),
			'file_exceeds_size_limit'   => __( '%1$s exceeds the maximum upload size of %2$s for this site.', 'buddyboss' ),
			'zero_byte_file'            => __( 'This file is empty. Please try another.', 'buddyboss' ),
			'invalid_filetype'          => __( 'This file type is not allowed. Please try another.', 'buddyboss' ),
			'not_an_image'              => __( 'This file is not an image. Please try another.', 'buddyboss' ),
			'image_memory_exceeded'     => __( 'Memory exceeded. Please try another smaller file.', 'buddyboss' ),
			'image_dimensions_exceeded' => __( 'This is larger than the maximum size. Please try another.', 'buddyboss' ),
			'default_error'             => __( 'An error occurred. Please try again later.', 'buddyboss' ),
			'missing_upload_url'        => __( 'There was a configuration error. Please contact the server administrator.', 'buddyboss' ),
			'upload_limit_exceeded'     => __( 'You may only upload 1 file.', 'buddyboss' ),
			'http_error'                => __( 'HTTP error.', 'buddyboss' ),
			'upload_failed'             => __( 'Upload failed.', 'buddyboss' ),
			'big_upload_failed'         => __( 'Please try uploading this file with the %1$sbrowser uploader%2$s.', 'buddyboss' ),
			'big_upload_queued'         => __( '%s exceeds the maximum upload size for the multi-file uploader when used in your browser.', 'buddyboss' ),
			'io_error'                  => __( 'IO error.', 'buddyboss' ),
			'security_error'            => __( 'Security error.', 'buddyboss' ),
			'file_cancelled'            => __( 'File canceled.', 'buddyboss' ),
			'upload_stopped'            => __( 'Upload stopped.', 'buddyboss' ),
			'dismiss'                   => __( 'Dismiss', 'buddyboss' ),
			'crunching'                 => __( 'Crunching&hellip;', 'buddyboss' ),
			'unique_file_warning'       => __( 'Make sure to upload a unique file', 'buddyboss' ),
			'error_uploading'           => __( '"%s" has failed to upload.', 'buddyboss' ),
			'has_avatar_warning'        => __( 'If you\'d like to delete the existing profile photo but not upload a new one, please use the delete tab.', 'buddyboss' ),
			'avatar_size_warning'       => sprintf(
				__( 'For best results, upload an image that is %1$spx by %2$spx or larger.', 'buddyboss' ),
				bp_core_avatar_full_height(),
				bp_core_avatar_full_width()
			),
		)
	);
}

/**
 * Enqueues the script needed for the Uploader UI.
 *
 * @since BuddyPress 2.3.0
 *
 * @see BP_Attachment::script_data() && BP_Attachment_Avatar::script_data() for examples showing how
 * to set specific script data.
 *
 * @param string $class Name of the class extending BP_Attachment (eg: BP_Attachment_Avatar).
 * @return null|WP_Error
 */
function bp_attachments_enqueue_scripts( $class = '' ) {
	// Enqueue me just once per page, please.
	if ( did_action( 'bp_attachments_enqueue_scripts' ) ) {
		return;
	}

	if ( ! $class || ! class_exists( $class ) ) {
		return new WP_Error( 'missing_parameter' );
	}

	// Get an instance of the class and get the script data.
	$attachment  = new $class();
	$script_data = $attachment->script_data();

	$args = bp_parse_args(
		$script_data,
		array(
			'action'            => '',
			'file_data_name'    => '',
			'max_file_size'     => 0,
			'browse_button'     => 'bp-browse-button',
			'container'         => 'bp-upload-ui',
			'drop_element'      => 'drag-drop-area',
			'bp_params'         => array(),
			'extra_css'         => array(),
			'extra_js'          => array(),
			'feedback_messages' => array(),
		),
		'attachments_enqueue_scripts'
	);

	if ( empty( $args['action'] ) || empty( $args['file_data_name'] ) ) {
		return new WP_Error( 'missing_parameter' );
	}

	// Get the BuddyPress uploader strings.
	$strings = bp_attachments_get_plupload_l10n();

	// Get the BuddyPress uploader settings.
	$settings = bp_attachments_get_plupload_default_settings();

	// Set feedback messages.
	if ( ! empty( $args['feedback_messages'] ) ) {
		$strings['feedback_messages'] = $args['feedback_messages'];
	}

	// Use a temporary var to ease manipulation.
	$defaults = $settings['defaults'];

	// Set the upload action.
	$defaults['multipart_params']['action'] = $args['action'];

	// Set BuddyPress upload parameters if provided.
	if ( ! empty( $args['bp_params'] ) ) {
		$defaults['multipart_params']['bp_params'] = $args['bp_params'];
	}

	// Merge other arguments.
	$ui_args = array_intersect_key(
		$args,
		array(
			'file_data_name' => true,
			'browse_button'  => true,
			'container'      => true,
			'drop_element'   => true,
		)
	);

	$defaults = array_merge( $defaults, $ui_args );

	if ( ! empty( $args['max_file_size'] ) ) {
		$defaults['filters']['max_file_size'] = $args['max_file_size'] . 'b';
	}

	// Specific to BuddyPress Avatars.
	if ( 'bp_avatar_upload' === $defaults['multipart_params']['action'] ) {

		// Include the cropping informations for avatars.
		$settings['crop'] = array(
			'full_h' => bp_core_avatar_full_height(),
			'full_w' => bp_core_avatar_full_width(),
		);

		// Avatar only need 1 file and 1 only!
		$defaults['multi_selection'] = false;

		// Does the object already has an avatar set.
		$has_avatar = $defaults['multipart_params']['bp_params']['has_avatar'];

		// What is the object the avatar belongs to.
		$object = $defaults['multipart_params']['bp_params']['object'];

		// Init the Avatar nav.
		$avatar_nav = array(
			'upload' => array(
				'id'      => 'upload',
				'caption' => __( 'Upload', 'buddyboss' ),
				'order'   => 0,
			),

			// The delete view will only show if the object has an avatar.
			'delete' => array(
				'id'      => 'delete',
				'caption' => __( 'Delete', 'buddyboss' ),
				'order'   => 100,
				'hide'    => (int) ! $has_avatar,
			),
		);

		// Create the Camera Nav if the WebCam capture feature is enabled.
		if ( bp_avatar_use_webcam() && 'user' === $object ) {
			$avatar_nav['camera'] = array(
				'id'      => 'camera',
				'caption' => __( 'Take Photo', 'buddyboss' ),
				'order'   => 10,
			);

			// Set warning messages.
			$strings['camera_warnings'] = array(
				'requesting' => __( 'Please allow application access to your camera.', 'buddyboss' ),
				'loading'    => __( 'Please wait while your camera connects.', 'buddyboss' ),
				'loaded'     => __( 'Camera loaded. Click "Capture" to take a photo.', 'buddyboss' ),
				'noaccess'   => __( 'Webcam not found or permission was denied. Please upload a photo.', 'buddyboss' ),
				'errormsg'   => __( 'Your browser is not supported. Please upload a photo instead.', 'buddyboss' ),
				'videoerror' => __( 'Video error. Please upload a photo instead.', 'buddyboss' ),
				'ready'      => __( 'Your profile photo is ready. Click "Save" to use this photo.', 'buddyboss' ),
				'nocapture'  => __( 'No photo captured. Click "Capture" to take your photo.', 'buddyboss' ),
			);
		}

		/**
		 * Use this filter to add a navigation to a custom tool to set the object's avatar.
		 *
		 * @since BuddyPress 2.3.0
		 *
		 * @param array  $avatar_nav {
		 *     An associative array of available nav items where each item is an array organized this way:
		 *     $avatar_nav[ $nav_item_id ].
		 *     @type string $nav_item_id The nav item id in lower case without special characters or space.
		 *     @type string $caption     The name of the item nav that will be displayed in the nav.
		 *     @type int    $order       An integer to specify the priority of the item nav, choose one.
		 *                               between 1 and 99 to be after the uploader nav item and before the delete nav item.
		 *     @type int    $hide        If set to 1 the item nav will be hidden
		 *                               (only used for the delete nav item).
		 * }
		 * @param string $object The object the avatar belongs to (eg: user or group).
		 */
		$settings['nav'] = bp_sort_by_key( apply_filters( 'bp_attachments_avatar_nav', $avatar_nav, $object ), 'order', 'num' );

		// Specific to BuddyPress cover photos.
	} elseif ( 'bp_cover_image_upload' === $defaults['multipart_params']['action'] ) {

		// cover photos only need 1 file and 1 only!
		$defaults['multi_selection'] = false;

		// Default cover component is xprofile.
		$cover_component = 'xprofile';

		// Get the object we're editing the cover photo of.
		$object = $defaults['multipart_params']['bp_params']['object'];

		// Set the cover component according to the object.
		if ( 'group' === $object ) {
			$cover_component = 'groups';
		} elseif ( 'user' !== $object ) {
			$cover_component = apply_filters( 'bp_attachments_cover_image_ui_component', $cover_component );
		}
		// Get cover photo advised dimensions.
		$cover_dimensions = bp_attachments_get_cover_image_dimensions( $cover_component );

		// Set warning messages.
		$strings['cover_image_warnings'] = apply_filters(
			'bp_attachments_cover_image_ui_warnings',
			array(
				'dimensions' => sprintf(
					__( 'For best results, upload an image that is %1$spx by %2$spx or larger.', 'buddyboss' ),
					(int) $cover_dimensions['width'],
					(int) $cover_dimensions['height']
				),
			)
		);
	}

	// Set Plupload settings.
	$settings['defaults'] = $defaults;

	/**
	 * Enqueue some extra styles if required
	 *
	 * Extra styles need to be registered.
	 */
	if ( ! empty( $args['extra_css'] ) ) {
		foreach ( (array) $args['extra_css'] as $css ) {
			if ( empty( $css ) ) {
				continue;
			}

			wp_enqueue_style( $css );
		}
	}

	wp_enqueue_script( 'bp-plupload' );
	wp_localize_script(
		'bp-plupload',
		'BP_Uploader',
		array(
			'strings'  => $strings,
			'settings' => $settings,
		)
	);

	/**
	 * Enqueue some extra scripts if required
	 *
	 * Extra scripts need to be registered.
	 */
	if ( ! empty( $args['extra_js'] ) ) {
		foreach ( (array) $args['extra_js'] as $js ) {
			if ( empty( $js ) ) {
				continue;
			}

			wp_enqueue_script( $js );
		}
	}

	/**
	 * Fires at the conclusion of bp_attachments_enqueue_scripts()
	 * to avoid the scripts to be loaded more than once.
	 *
	 * @since BuddyPress 2.3.0
	 */
	do_action( 'bp_attachments_enqueue_scripts' );
}

/**
 * Check the current user's capability to edit an avatar for a given object.
 *
 * @since BuddyPress 2.3.0
 *
 * @param string $capability The capability to check.
 * @param array  $args       An array containing the item_id and the object to check.
 * @return bool
 */
function bp_attachments_current_user_can( $capability, $args = array() ) {
	$can = false;

	if ( 'edit_avatar' === $capability || 'edit_cover_image' === $capability ) {
		/**
		 * Needed avatar arguments are set.
		 */
		if ( isset( $args['item_id'] ) && isset( $args['object'] ) ) {
			// Group profile photo.
			if ( bp_is_active( 'groups' ) && 'group' === $args['object'] ) {
				if ( bp_is_group_create() ) {
					$can = (bool) groups_is_user_creator( bp_loggedin_user_id(), $args['item_id'] ) || bp_current_user_can( 'bp_moderate' );
				} else {
					$can = (bool) groups_is_user_admin( bp_loggedin_user_id(), $args['item_id'] ) || bp_current_user_can( 'bp_moderate' );
				}
				// User profile photo.
			} elseif ( bp_is_active( 'xprofile' ) && 'user' === $args['object'] ) {
				$can = bp_loggedin_user_id() === (int) $args['item_id'] || bp_current_user_can( 'bp_moderate' );
			}
			/**
			 * No avatar arguments, fallback to bp_user_can_create_groups()
			 * or bp_is_item_admin()
			 */
		} else {
			if ( bp_is_group_create() ) {
				$can = bp_user_can_create_groups();
			} else {
				$can = bp_is_item_admin();
			}
		}
	}

	return apply_filters( 'bp_attachments_current_user_can', $can, $capability, $args );
}

/**
 * Send a JSON response back to an Ajax upload request.
 *
 * @since BuddyPress 2.3.0
 *
 * @param bool  $success  True for a success, false otherwise.
 * @param bool  $is_html4 True if the Plupload runtime used is html4, false otherwise.
 * @param mixed $data     Data to encode as JSON, then print and die.
 */
function bp_attachments_json_response( $success, $is_html4 = false, $data = null ) {
	$response = array( 'success' => $success );

	if ( isset( $data ) ) {
		$response['data'] = $data;
	}

	// Send regular json response.
	if ( ! $is_html4 ) {
		wp_send_json( $response );

		/**
		 * Send specific json response
		 * the html4 Plupload handler requires a text/html content-type for older IE.
		 * See https://core.trac.wordpress.org/ticket/31037
		 */
	} else {
		echo wp_json_encode( $response );

		wp_die();
	}
}

/**
 * Get an Attachment template part.
 *
 * @since BuddyPress 2.3.0
 *
 * @param string $slug Template part slug. eg 'uploader' for 'uploader.php'.
 * @return bool
 */
function bp_attachments_get_template_part( $slug ) {
	$switched = false;

	/*
	 * Use bp-legacy attachment template part for older bp-default themes or if in
	 * admin area.
	 */
	if ( ! bp_use_theme_compat_with_current_theme() || ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) {
		$current = bp_get_theme_compat_id();
		if ( 'legacy' !== $current ) {
			$switched = true;
			bp_setup_theme_compat( 'legacy' );
		}
	}

	// Load the template part.
	bp_get_template_part( 'assets/_attachments/' . $slug );

	if ( $switched ) {
		bp_setup_theme_compat( $current );
	}
}

/** Cover Photo ***************************************************************/

/**
 * Get the cover photo settings
 *
 * @since BuddyPress 2.4.0
 *
 * @param string $component The component to get the settings for ("xprofile" for user or "groups").
 * @return false|array The cover photo settings in array, false on failure.
 */
function bp_attachments_get_cover_image_settings( $component = 'xprofile' ) {
	// Default parameters.
	$args = array();

	// First look in BP Theme Compat.
	$cover_image = bp_get_theme_compat_feature( 'cover_image' );

	if ( ! empty( $cover_image ) ) {
		$args = (array) $cover_image;
	}

	/**
	 * Then let people override/set the feature using this dynamic filter
	 *
	 * Eg: for the user's profile cover photo use:
	 * add_filter( 'bp_before_xprofile_cover_image_settings_parse_args', 'your_filter', 10, 1 );
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param array $settings The cover photo settings
	 */
	$settings = bp_parse_args(
		$args,
		array(
			'components'    => array(),
			'width'         => 1300,
			'height'        => 225,
			'callback'      => '',
			'theme_handle'  => '',
			'default_cover' => '',
		),
		$component . '_cover_image_settings'
	);

	if ( empty( $settings['components'] ) || empty( $settings['callback'] ) || empty( $settings['theme_handle'] ) ) {
		return false;
	}

	// Current component is not supported.
	if ( ! in_array( $component, $settings['components'] ) ) {
		return false;
	}

	// Set default cover if 'default_cover' is not found.
	if ( empty( $settings['default_cover'] ) ) {
		$settings['default_cover'] = bb_attachments_get_default_profile_group_cover_image( $component );
	}

	// Finally return the settings.
	return $settings;
}

/**
 * Get cover photo Width and Height.
 *
 * @since BuddyPress 2.4.0
 *
 * @param string $component The BuddyPress component concerned ("xprofile" for user or "groups").
 * @return array|bool An associative array containing the advised width and height for the cover photo. False if settings are empty.
 */
function bp_attachments_get_cover_image_dimensions( $component = 'xprofile' ) {
	// Let's prevent notices when setting the warning strings.
	$default = array(
		'width'  => 0,
		'height' => 0,
	);

	$settings = bp_attachments_get_cover_image_settings( $component );

	if ( empty( $settings ) ) {

		/**
		 * Filter here to edit the cover photo dimensions if needed.
		 *
		 * @since BuddyPress 2.4.0
		 *
		 * @param bool  false      Setting not found for the given component.
		 * @param array  $settings An associative array containing all the feature settings.
		 * @param string $compnent The requested component.
		 */
		return apply_filters( 'bp_attachments_get_cover_image_dimensions', false, $settings, $component );
	}

	// Get width and height.
	$wh = array_intersect_key( $settings, $default );

	/**
	 * Filter here to edit the cover photo dimensions if needed.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param array  $wh       An associative array containing the width and height values.
	 * @param array  $settings An associative array containing all the feature settings.
	 * @param string $compnent The requested component.
	 */
	return apply_filters( 'bp_attachments_get_cover_image_dimensions', $wh, $settings, $component );
}

/**
 * Are we on a page to edit a cover photo?
 *
 * @since BuddyPress 2.4.0
 *
 * @return bool True if on a page to edit a cover photo, false otherwise.
 */
function bp_attachments_cover_image_is_edit() {
	$retval = false;

	$current_component = bp_current_component();
	if ( bp_is_active( 'xprofile' ) && bp_is_current_component( 'xprofile' ) ) {
		$current_component = 'xprofile';
	}

	if ( ! bp_is_active( $current_component, 'cover_image' ) ) {
		return $retval;
	}

	if ( bp_is_user_change_cover_image() ) {
		$retval = ! bp_disable_cover_image_uploads();
	}

	if ( ( bp_is_group_admin_page() && 'group-cover-image' == bp_get_group_current_admin_tab() )
		|| ( bp_is_group_create() && bp_is_group_creation_step( 'group-cover-image' ) ) ) {
		$retval = ! bp_disable_group_cover_image_uploads();
	}

	return apply_filters( 'bp_attachments_cover_image_is_edit', $retval, $current_component );
}

/**
 * Does the user has a cover photo?
 *
 * @since BuddyPress 2.4.0
 *
 * @param int $user_id User ID to retrieve cover photo for.
 * @return bool True if the user has a cover photo, false otherwise.
 */
function bp_attachments_get_user_has_cover_image( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	$cover_src = bp_attachments_get_attachment(
		'url',
		array(
			'item_id' => $user_id,
		)
	);

	if ( false !== strpos( $cover_src, '/0/' ) || false !== strpos( $cover_src, '/bp-core/' ) ) {
		$cover_src = '';
	}

	return (bool) apply_filters( 'bp_attachments_get_user_has_cover_image', $cover_src, $user_id );
}

/**
 * Does the group has a cover photo?
 *
 * @since BuddyPress 2.4.0
 *
 * @param int $group_id Group ID to check cover photo existence for.
 * @return bool True if the group has a cover photo, false otherwise.
 */
function bp_attachments_get_group_has_cover_image( $group_id = 0 ) {
	if ( empty( $group_id ) ) {
		$group_id = bp_get_current_group_id();
	}

	$cover_src = bp_attachments_get_attachment(
		'url',
		array(
			'object_dir' => 'groups',
			'item_id'    => $group_id,
		)
	);

	if ( false !== strpos( $cover_src, '/0/' ) || false !== strpos( $cover_src, '/bp-core/' ) ) {
		$cover_src = '';
	}

	return (bool) apply_filters( 'bp_attachments_get_group_has_cover_image', $cover_src, $group_id );
}

/**
 * Generate the cover photo file.
 *
 * @since BuddyPress 2.4.0
 *
 * @param array                          $args {
 *     @type string $file            The absolute path to the image. Required.
 *     @type string $component       The component for the object (eg: groups, xprofile). Required.
 *     @type string $cover_image_dir The cover photo dir to write the image into. Required.
 * }
 * @param BP_Attachment_Cover_Image|null $cover_image_class The class to use to fit the cover photo.
 * @return false|array An array containing cover photo data on success, false otherwise.
 */
function bp_attachments_cover_image_generate_file( $args = array(), $cover_image_class = null ) {
	// Bail if an argument is missing.
	if ( empty( $args['file'] ) || empty( $args['component'] ) || empty( $args['cover_image_dir'] ) ) {
		return false;
	}

	// Get advised dimensions for the cover photo.
	$dimensions = bp_attachments_get_cover_image_dimensions( $args['component'] );

	// No dimensions or the file does not match with the cover photo dir, stop!
	if ( false === $dimensions || $args['file'] !== $args['cover_image_dir'] . '/' . wp_basename( $args['file'] ) ) {
		return false;
	}

	if ( ! is_a( $cover_image_class, 'BP_Attachment_Cover_Image' ) ) {
		$cover_image_class = new BP_Attachment_Cover_Image();
	}

	$upload_dir = bp_attachments_cover_image_upload_dir();

	// Make sure the file is inside the Cover Photo Upload path.
	if ( false === strpos( $args['file'], $upload_dir['basedir'] ) ) {
		return false;
	}

	// Resize the image so that it fit with the cover photo dimensions.
	$cover_image  = $cover_image_class->fit( $args['file'], $dimensions );
	$is_too_small = false;

	// Image is too small in width and height.
	if ( empty( $cover_image ) ) {
		$cover_file = $cover_image_class->generate_filename( $args['file'] );
		@rename( $args['file'], $cover_file );

		// It's too small!
		$is_too_small = true;
	} elseif ( ! empty( $cover_image['path'] ) ) {
		$cover_file = $cover_image['path'];

		// Image is too small in width or height.
		if ( $cover_image['width'] < $dimensions['width'] || $cover_image['height'] < $dimensions['height'] ) {
			$is_too_small = true;
		}
	}

	// We were not able to generate the cover photo file.
	if ( empty( $cover_file ) ) {
		return false;
	}

	// Do some clean up with old cover photo, now a new one is set.
	$cover_basename = wp_basename( $cover_file );

	if ( $att_dir = opendir( $args['cover_image_dir'] ) ) {
		while ( false !== ( $attachment_file = readdir( $att_dir ) ) ) {
			// Skip directories and the new cover photo.
			if ( 2 < strlen( $attachment_file ) && 0 !== strpos( $attachment_file, '.' ) && $cover_basename !== $attachment_file ) {
				@unlink( $args['cover_image_dir'] . '/' . $attachment_file );
			}
		}
	}

	// Finally return needed data.
	return array(
		'cover_file'     => $cover_file,
		'cover_basename' => $cover_basename,
		'is_too_small'   => $is_too_small,
	);
}

/**
 * Ajax Upload and set a cover photo
 *
 * @since BuddyPress 2.4.0
 *
 * @return string|null A json object containing success data if the upload succeeded,
 *                     error message otherwise.
 */
function bp_attachments_cover_image_ajax_upload() {
	if ( ! bp_is_post_request() ) {
		wp_die();
	}

	check_admin_referer( 'bp-uploader' );

	// Sending the json response will be different if the current Plupload runtime is html4.
	$is_html4 = ! empty( $_POST['html4'] );

	if ( empty( $_POST['bp_params'] ) ) {
		bp_attachments_json_response( false, $is_html4 );
	}

	$bp_params = bp_parse_args(
		$_POST['bp_params'],
		array(
			'object'    => 'user',
			'item_id'   => bp_loggedin_user_id(),
			'item_type' => null,
		),
		'attachments_cover_image_ajax_upload'
	);

	$bp_params['item_id'] = (int) $bp_params['item_id'];
	$bp_params['object']  = sanitize_text_field( $bp_params['object'] );

	// We need the object to set the uploads dir filter.
	if ( empty( $bp_params['object'] ) ) {
		bp_attachments_json_response( false, $is_html4 );
	}

	// Capability check.
	if ( ! bp_attachments_current_user_can( 'edit_cover_image', $bp_params ) ) {
		bp_attachments_json_response( false, $is_html4 );
	}

	$bp          = buddypress();
	$needs_reset = array();

	// Member's cover photo.
	if ( 'user' === $bp_params['object'] ) {
		$object_data = array(
			'dir'       => 'members',
			'component' => 'xprofile',
		);

		if ( ! bp_displayed_user_id() && ( ! empty( $bp_params['item_id'] ) || ( empty( $bp_params['item_id'] ) && ! empty( $bp_params['item_type'] ) ) ) ) {
			$needs_reset            = array(
				'key'   => 'displayed_user',
				'value' => $bp->displayed_user,
			);
			$bp->displayed_user->id = $bp_params['item_id'];
		}

		// Group's cover photo.
	} elseif ( 'group' === $bp_params['object'] ) {
		$object_data = array(
			'dir'       => 'groups',
			'component' => 'groups',
		);

		if ( ! bp_get_current_group_id() && ( ! empty( $bp_params['item_id'] ) || ( empty( $bp_params['item_id'] ) && ! empty( $bp_params['item_type'] ) ) ) ) {
			$needs_reset               = array(
				'component' => 'groups',
				'key'       => 'current_group',
				'value'     => $bp->groups->current_group,
			);
			$bp->groups->current_group = groups_get_group( $bp_params['item_id'] );
		}

		// Other object's cover photo.
	} else {
		$object_data = apply_filters( 'bp_attachments_cover_image_object_dir', array(), $bp_params['object'] );
	}

	// Stop here in case of a missing parameter for the object.
	if ( empty( $object_data['dir'] ) || empty( $object_data['component'] ) ) {
		bp_attachments_json_response( false, $is_html4 );
	}

	/**
	 * Filters whether or not to handle cover photo uploading.
	 *
	 * If you want to override this function, make sure you return an array with the 'result' key set.
	 *
	 * @since BuddyPress 2.5.1
	 *
	 * @param array $value
	 * @param array $bp_params
	 * @param array $needs_reset Stores original value of certain globals we need to revert to later.
	 * @param array $object_data
	 */
	$pre_filter = apply_filters( 'bp_attachments_pre_cover_image_ajax_upload', array(), $bp_params, $needs_reset, $object_data );
	if ( isset( $pre_filter['result'] ) ) {
		bp_attachments_json_response( $pre_filter['result'], $is_html4, $pre_filter );
	}

	$cover_image_attachment = new BP_Attachment_Cover_Image();
	$uploaded               = $cover_image_attachment->upload( $_FILES );

	// Reset objects.
	if ( ! empty( $needs_reset ) ) {
		if ( ! empty( $needs_reset['component'] ) ) {
			$bp->{$needs_reset['component']}->{$needs_reset['key']} = $needs_reset['value'];
		} else {
			$bp->{$needs_reset['key']} = $needs_reset['value'];
		}
	}

	if ( ! empty( $uploaded['error'] ) ) {
		// Upload error response.
		bp_attachments_json_response(
			false,
			$is_html4,
			array(
				'type'    => 'upload_error',
				'message' => sprintf( __( 'Upload Error: %s', 'buddyboss' ), $uploaded['error'] ),
			)
		);
	}

	$error_message = __( 'There was a problem uploading the cover photo.', 'buddyboss' );

	$bp_attachments_uploads_dir = bp_attachments_cover_image_upload_dir();

	// The BP Attachments Uploads Dir is not set, stop.
	if ( ! $bp_attachments_uploads_dir ) {
		bp_attachments_json_response(
			false,
			$is_html4,
			array(
				'type'    => 'upload_error',
				'message' => $error_message,
			)
		);
	}

	// Set some arguments for filters.
	$item_id   = (int) $bp_params['item_id'];
	$component = $object_data['component'];

	/**
	 * Filters BuddyPress image attachment subdirectory.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $subdir     The sub dir to uploaded BuddyPress image.
	 * @param string $object_dir The object dir (eg: members/groups). Defaults to members.
	 * @param int    $item_id    The object id (eg: a user or a group id). Defaults to current user.
	 * @param string $type       The type of the attachment which is also the subdir where files are saved.
	 *                           Defaults to 'cover-image'
	 */
	$cover_subdir = apply_filters( 'bb_attachments_get_attachment_sub_dir', $object_data['dir'] . '/' . $item_id . '/cover-image', $object_data['dir'], $item_id, 'cover-image' );

	$cover_dir = trailingslashit( $bp_attachments_uploads_dir['basedir'] ) . $cover_subdir;

	/**
	 * Filters BuddyPress image attachment directory.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $dir        The dir to uploaded BuddyPress image.
	 * @param string $object_dir The object dir (eg: members/groups). Defaults to members.
	 * @param int    $item_id    The object id (eg: a user or a group id). Defaults to current user.
	 * @param string $type       The type of the attachment which is also the subdir where files are saved.
	 *                           Defaults to 'cover-image'
	 */
	$cover_dir = apply_filters( 'bb_attachments_get_attachment_dir', $cover_dir, $object_data['dir'], $item_id, 'cover-image' );

	if ( 1 === validate_file( $cover_dir ) || ! is_dir( $cover_dir ) ) {
		// Upload error response.
		bp_attachments_json_response(
			false,
			$is_html4,
			array(
				'type'    => 'upload_error',
				'message' => $error_message,
			)
		);
	}

	/*
	 * Generate the cover photo so that it fit to feature's dimensions
	 *
	 * Unlike the avatar, uploading and generating the cover photo is happening during
	 * the same Ajax request, as we already instantiated the BP_Attachment_Cover_Image
	 * class, let's use it.
	 */
	$cover = bp_attachments_cover_image_generate_file(
		array(
			'file'            => $uploaded['file'],
			'component'       => $component,
			'cover_image_dir' => $cover_dir,
		),
		$cover_image_attachment
	);

	if ( ! $cover ) {
		bp_attachments_json_response(
			false,
			$is_html4,
			array(
				'type'    => 'upload_error',
				'message' => $error_message,
			)
		);
	}

	$component = ( 'xprofile' === $component ? 'members' : $component );

	$cover_url = trailingslashit( $bp_attachments_uploads_dir['baseurl'] ) . $cover_subdir . '/' . $cover['cover_basename'];

	/**
	 * Filters groups/members cover image attachment URL.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $cover_url URL to the image.
	 * @param array  $cover     Cover array.
	 * @param string $component Component either groups or members.
	 * @param int    $item_id   Inform about the item id the cover image was set for either group id or member id.
	 */
	$cover_url = apply_filters( 'bp_' . $component . '_attachments_cover_image_url', $cover_url, $cover, $component, $item_id );

	// 1 is success.
	$feedback_code = 1;

	// 0 is the size warning.
	if ( $cover['is_too_small'] ) {
		$feedback_code = 0;
	}

	// Set the name of the file.
	$name       = $_FILES['file']['name'];
	$name_parts = pathinfo( $name );
	$name       = trim( substr( $name, 0, - ( 1 + strlen( $name_parts['extension'] ) ) ) );

	/**
	 * Fires if the new cover photo was successfully uploaded.
	 *
	 * The dynamic portion of the hook will be xprofile in case of a user's
	 * cover photo, groups in case of a group's cover photo. For instance:
	 * Use add_action( 'xprofile_cover_image_uploaded' ) to run your specific
	 * code once the user has set his cover photo.
	 *
	 * @since BuddyPress 2.4.0
	 * @since BuddyPress 3.0.0 Added $cover_url, $name, $feedback_code arguments.
	 *
	 * @param int    $item_id       Inform about the item id the cover photo was set for.
	 * @param string $name          Filename.
	 * @param string $cover_url     URL to the image.
	 * @param int    $feedback_code If value not 1, an error occured.
	 */
	do_action(
		$object_data['component'] . '_cover_image_uploaded',
		(int) $bp_params['item_id'],
		$name,
		$cover_url,
		$feedback_code
	);

	// Give 3rd party plugins a chance to calculate the URL based on the id. I.e. if
	// the image is offloaded to external storage.
	$return_url = bp_attachments_get_attachment(
		'url',
		array(
			'object_dir' => $component,
			'item_id'    => $item_id,
		)
	);

	if ( '' === $return_url ) {
		$return_url = $cover_url;
	}

	// Finally, return the cover photo url to the UI.
	bp_attachments_json_response(
		true,
		$is_html4,
		array(
			'name'          => $name,
			'url'           => $return_url,
			'feedback_code' => $feedback_code,
		)
	);
}
add_action( 'wp_ajax_bp_cover_image_upload', 'bp_attachments_cover_image_ajax_upload' );

/**
 * Ajax delete a cover photo for a given object and item id.
 *
 * @since BuddyPress 2.4.0
 *
 * @return string|null A json object containing success data if the cover photo was deleted
 *                     error message otherwise.
 */
function bp_attachments_cover_image_ajax_delete() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	$item_type = isset( $_POST['item_type'] ) ? $_POST['item_type'] : '';

	if ( empty( $_POST['object'] ) || ( empty( $_POST['item_id'] ) && empty( $item_type ) ) ) {
		wp_send_json_error();
	}

	$args = array(
		'object'  => sanitize_text_field( $_POST['object'] ),
		'item_id' => (int) $_POST['item_id'],
	);

	// Check permissions.
	check_admin_referer( 'bp_delete_cover_image', 'nonce' );
	if ( ! bp_attachments_current_user_can( 'edit_cover_image', $args ) ) {
		wp_send_json_error();
	}

	// Set object for the user's case.
	if ( 'user' === $args['object'] ) {
		$component = 'xprofile';
		$dir       = 'members';

		// Set it for any other cases.
	} else {
		$component = $args['object'] . 's';
		$dir       = $component;
	}

	/**
	 * Update directory name while deleting the cover image.
	 *
	 * @since BuddyBoss 2.0.4
	 *
	 * @param string $dir  Directory name.
	 * @param array  $args Arguments.
	 */
	$dir = apply_filters( 'bp_attachments_cover_image_ajax_delete_dir', $dir, $args );

	// Handle delete.
	if ( bp_attachments_delete_file(
		array(
			'item_id'    => $args['item_id'],
			'object_dir' => $dir,
			'type'       => 'cover-image',
		)
	) ) {
		/**
		 * Fires if the cover photo was successfully deleted.
		 *
		 * The dynamic portion of the hook will be xprofile in case of a user's
		 * cover photo, groups in case of a group's cover photo. For instance:
		 * Use add_action( 'xprofile_cover_image_deleted' ) to run your specific
		 * code once the user has deleted his cover photo.
		 *
		 * @since BuddyPress 2.8.0
		 *
		 * @param int $item_id Inform about the item id the cover photo was deleted for.
		 */
		do_action( "{$component}_cover_image_deleted", (int) $args['item_id'] );

		$response = array(
			'reset_url'     => '',
			'feedback_code' => 3,
		);

		$cover_params = array();
		if ( ! empty( $args['item_id'] ) ) {
			// Get cover photo settings in case there's a default header.
			$cover_params = bp_attachments_get_cover_image_settings( $component );
		}

		// Check if there's a default cover.
		if ( ! empty( $cover_params['default_cover'] ) ) {
			$response['reset_url'] = $cover_params['default_cover'];
		}

		wp_send_json_success( $response );

	} else {
		wp_send_json_error(
			array(
				'feedback_code' => 2,
			)
		);
	}
}
add_action( 'wp_ajax_bp_cover_image_delete', 'bp_attachments_cover_image_ajax_delete' );

/**
 * Get default cover image class if cover type is 'BuddyBoss' or 'None'.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param int    $item_id The item id (eg: a user or a group id). Defaults to current user.
 * @param string $item    The item to get the settings for ("user" or "group").
 * @return string|null Return the class if the cover type is 'BuddyBoss' or 'None' otherwise null.
 */
function bb_attachment_get_cover_image_class( $item_id = 0, $item = 'user' ) {

	$cover_image_class = '';

	if ( 'user' === $item && ! bp_disable_cover_image_uploads() ) {

		if ( empty( $item_id ) ) {
			$item_id = bp_displayed_user_id();
		}

		$profile_cover_type = bb_get_default_profile_cover_type();
		$cover_image_class  = bp_attachments_get_user_has_cover_image( $item_id ) ? '' : ' has-default';

		if ( 'custom' === $profile_cover_type ) {
			$cover_image_class = '';
		}
	} elseif ( 'group' === $item && ! bp_disable_group_cover_image_uploads() ) {

		if ( empty( $item_id ) ) {
			$item_id = bp_get_current_group_id();
		}

		$group_cover_type  = bb_get_default_group_cover_type();
		$cover_image_class = bp_attachments_get_group_has_cover_image( $item_id ) ? '' : ' has-default';

		if ( 'custom' === $group_cover_type ) {
			$cover_image_class = '';
		}
	}

	/**
	 * Filters default cover image URL.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string|null $cover_image_class The default profile or group cover class.
	 * @param int         $item_id           The item id (eg: a user or a group id). Defaults to current user.
	 * @param string      $item              The item to get the settings for ("user" or "group").
	 */
	return apply_filters( 'bb_attachment_get_cover_image_class', $cover_image_class, $item_id, $item );
}
