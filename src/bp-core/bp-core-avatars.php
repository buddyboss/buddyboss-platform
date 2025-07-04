<?php
/**
 * BuddyPress Avatars.
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the constants we need for avatar support.
 *
 * @since BuddyPress 1.2.0
 */
function bp_core_set_avatar_constants() {

	$bp = buddypress();

	if ( ! defined( 'BP_AVATAR_THUMB_WIDTH' ) ) {
		define( 'BP_AVATAR_THUMB_WIDTH', 150 );
	}

	if ( ! defined( 'BP_AVATAR_THUMB_HEIGHT' ) ) {
		define( 'BP_AVATAR_THUMB_HEIGHT', 150 );
	}

	if ( ! defined( 'BP_AVATAR_FULL_WIDTH' ) ) {
		define( 'BP_AVATAR_FULL_WIDTH', 300 );
	}

	if ( ! defined( 'BP_AVATAR_FULL_HEIGHT' ) ) {
		define( 'BP_AVATAR_FULL_HEIGHT', 300 );
	}

	if ( ! defined( 'BP_AVATAR_ORIGINAL_MAX_WIDTH' ) ) {
		define( 'BP_AVATAR_ORIGINAL_MAX_WIDTH', 450 );
	}

	if ( ! defined( 'BP_AVATAR_ORIGINAL_MAX_FILESIZE' ) ) {
		define( 'BP_AVATAR_ORIGINAL_MAX_FILESIZE', bp_attachments_get_max_upload_file_size( 'avatar' ) );
	}

	if ( ! defined( 'BP_SHOW_AVATARS' ) ) {
		define( 'BP_SHOW_AVATARS', true );
	}
}
add_action( 'bp_init', 'bp_core_set_avatar_constants', 3 );

/**
 * Set up global variables related to avatars.
 *
 * @since BuddyPress 1.5.0
 */
function bp_core_set_avatar_globals() {
	$bp = buddypress();

	$bp->avatar        = new stdClass();
	$bp->avatar->thumb = new stdClass();
	$bp->avatar->full  = new stdClass();

	// Dimensions.
	$bp->avatar->thumb->width  = BP_AVATAR_THUMB_WIDTH;
	$bp->avatar->thumb->height = BP_AVATAR_THUMB_HEIGHT;
	$bp->avatar->full->width   = BP_AVATAR_FULL_WIDTH;
	$bp->avatar->full->height  = BP_AVATAR_FULL_HEIGHT;

	// Upload maximums.
	$bp->avatar->original_max_width    = BP_AVATAR_ORIGINAL_MAX_WIDTH;
	$bp->avatar->original_max_filesize = BP_AVATAR_ORIGINAL_MAX_FILESIZE;

	// Defaults.
	$bp->avatar->thumb->default = bp_core_avatar_default_thumb();
	$bp->avatar->full->default  = bp_core_avatar_default();

	// These have to be set on page load in order to avoid infinite filter loops at runtime.
	$bp->avatar->upload_path = bp_core_avatar_upload_path();
	$bp->avatar->url         = bp_core_avatar_url();

	// Cache the root blog's show_avatars setting, to avoid unnecessary
	// calls to switch_to_blog().
	$bp->avatar->show_avatars = (bool) BP_SHOW_AVATARS;

	// Backpat for pre-1.5.
	if ( ! defined( 'BP_AVATAR_UPLOAD_PATH' ) ) {
		define( 'BP_AVATAR_UPLOAD_PATH', $bp->avatar->upload_path );
	}

	// Backpat for pre-1.5.
	if ( ! defined( 'BP_AVATAR_URL' ) ) {
		define( 'BP_AVATAR_URL', $bp->avatar->url );
	}

	/**
	 * Fires at the end of the core avatar globals setup.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_core_set_avatar_globals' );
}
add_action( 'bp_setup_globals', 'bp_core_set_avatar_globals' );

/**
 * Get an avatar for a BuddyPress object.
 *
 * Supports avatars for users, groups, and blogs by default, but can be
 * extended to support custom components as well.
 *
 * This function gives precedence to locally-uploaded avatars. When a local
 * avatar is not found, Gravatar is queried. To disable Gravatar fallbacks
 * locally:
 *    add_filter( 'bp_core_fetch_avatar_no_grav', '__return_true' );
 *
 * @since BuddyPress 1.1.0
 * @since BuddyPress 2.4.0 Added 'extra_attr', 'scheme', 'rating' and 'force_default' for $args.
 *              These are inherited from WordPress 4.2.0. See {@link get_avatar()}.
 *
 * @param array|string $args {
 *     An array of arguments. All arguments are technically optional; some
 *     will, if not provided, be auto-detected by bp_core_fetch_avatar(). This
 *     auto-detection is described more below, when discussing specific
 *     arguments.
 *
 *     @type int|bool    $item_id    The numeric ID of the item for which you're requesting
 *                                   an avatar (eg, a user ID). If no 'item_id' is present,
 *                                   the function attempts to infer an ID from the 'object' + the
 *                                   current context: if 'object' is 'user' and the current page is a
 *                                   user page, 'item_id' will default to the displayed user ID; if
 *                                   'group' and on a group page, to the current group ID; if 'blog',
 *                                   to the current blog's ID. If no 'item_id' can be determined in
 *                                   this way, the function returns false. Default: false.
 *     @type string     $item_type   This param is use for find the type of avatar. If upload default then
 *                                   $item_type is 'default' otherwise null.  Default: null.
 *     @type string      $object     The kind of object for which you're getting an
 *                                   avatar. BuddyPress natively supports three options: 'user',
 *                                   'group', 'blog'; a plugin may register more.  Default: 'user'.
 *     @type string      $type       When a new avatar is uploaded to BP, 'thumb' and
 *                                   'full' versions are saved. This parameter specifies whether you'd
 *                                   like the 'full' or smaller 'thumb' avatar. Default: 'thumb'.
 *     @type string|bool $avatar_dir The name of the subdirectory where the
 *                                   requested avatar should be found. If no value is passed,
 *                                   'avatar_dir' is inferred from 'object': 'user' becomes 'avatars',
 *                                   'group' becomes 'group-avatars', 'blog' becomes 'blog-avatars'.
 *                                   Remember that this string denotes a subdirectory of BP's main
 *                                   avatar directory (usually based on {@link wp_upload_dir()}); it's a
 *                                   string like 'group-avatars' rather than the full directory path.
 *                                   Generally, it'll only be necessary to override the default value if
 *                                   storing avatars in a non-default location. Defaults to false
 *                                   (auto-detected).
 *     @type int|bool    $width      Requested avatar width. The unit is px. This value
 *                                   is used to build the 'width' attribute for the <img> element. If
 *                                   no value is passed, BP uses the global avatar width for this
 *                                   avatar type. Default: false (auto-detected).
 *     @type int|bool    $height     Requested avatar height. The unit is px. This
 *                                   value is used to build the 'height' attribute for the <img>
 *                                   element. If no value is passed, BP uses the global avatar height
 *                                   for this avatar type. Default: false (auto-detected).
 *     @type string      $class      The CSS class for the <img> element. Note that BP
 *                                   uses the 'avatar' class fairly extensively in its default styling,
 *                                   so if you plan to pass a custom value, consider appending it to
 *                                   'avatar' (eg 'avatar foo') rather than replacing it altogether.
 *                                   Default: 'avatar'.
 *     @type string|bool $css_id     The CSS id for the <img> element.
 *                                   Default: false.
 *     @type string      $title      The title attribute for the <img> element.
 *                                   Default: false.
 *     @type string      $alt        The alt attribute for the <img> element. In BP, this
 *                                   value is generally passed by the wrapper functions, where the data
 *                                   necessary for concatenating the string is at hand; see
 *                                   {@link bp_get_activity_avatar()} for an example. Default: ''.
 *     @type string|bool $email      An email to use in Gravatar queries. Unless
 *                                   otherwise configured, BP uses Gravatar as a fallback for avatars
 *                                   that are not provided locally. Gravatar's API requires using a hash
 *                                   of the user's email address; this argument provides it. If not
 *                                   provided, the function will infer it: for users, by getting the
 *                                   user's email from the database, for groups/blogs, by concatenating
 *                                   "{$item_id}-{$object}@{bp_get_root_domain()}". The user query adds
 *                                   overhead, so it's recommended that wrapper functions provide a
 *                                   value for 'email' when querying user IDs. Default: false.
 *     @type bool       $no_grav     Whether to disable the default Gravatar fallback.
 *                                   By default, BP will fall back on Gravatar when it cannot find a
 *                                   local avatar. In some cases, this may be undesirable, in which
 *                                   case 'no_grav' should be set to true. To disable Gravatar
 *                                   fallbacks globally, see the 'bp_core_fetch_avatar_no_grav' filter.
 *                                   Default: true for groups, otherwise false.
 *     @type bool       $html        Whether to return an <img> HTML element, vs a raw URL
 *                                   to an avatar. If false, <img>-specific arguments (like 'css_id')
 *                                   will be ignored. Default: true.
 *     @type string     $extra_attr  HTML attributes to insert in the IMG element. Not sanitized. Default: ''.
 *     @type string     $scheme      URL scheme to use. See set_url_scheme() for accepted values.
 *                                   Default null.
 *     @type string     $rating      What rating to display Gravatars for. Accepts 'G', 'PG', 'R', 'X'.
 *                                   Default is the value of the 'avatar_rating' option.
 *     @type bool       $force_default Used when creating the Gravatar URL. Whether to force the default
 *                                     image regardless if the Gravatar exists. Default: false.
 * }
 * @return string Formatted HTML <img> element, or raw avatar URL based on $html arg.
 */
function bp_core_fetch_avatar( $args = '' ) {
	$bp = buddypress();

	// If avatars are disabled for the root site, obey that request and bail.
	if ( ! $bp->avatar || ! $bp->avatar->show_avatars ) {
		return;
	}

	global $current_blog;

	// Set the default variables array and parse it against incoming $args array.
	$params = bp_parse_args(
		$args,
		array(
			'item_id'       => false,
			'item_type'     => null,
			'object'        => 'user',
			'type'          => 'thumb',
			'avatar_dir'    => false,
			'width'         => false,
			'height'        => false,
			'class'         => 'avatar',
			'css_id'        => false,
			'alt'           => '',
			'email'         => false,
			'no_grav'       => null,
			'html'          => true,
			'title'         => '',
			'extra_attr'    => '',
			'scheme'        => null,
			'rating'        => get_option( 'avatar_rating' ),
			'force_default' => false,
		)
	);

	/* Set item_id ***********************************************************/

	if ( empty( $params['item_id'] ) && ( ! is_admin() && ! empty( $params['item_type'] ) ) ) {

		switch ( $params['object'] ) {

			case 'blog':
				$params['item_id'] = $current_blog->id;
				break;

			case 'group':
				if ( bp_is_active( 'groups' ) ) {
					$params['item_id'] = $bp->groups->current_group->id;
				} else {
					$params['item_id'] = false;
				}

				break;

			case 'user':
			default:
				$params['item_id'] = bp_displayed_user_id();
				break;
		}

		/**
		 * Filters the ID of the item being requested.
		 *
		 * @since BuddyPress 1.1.0
		 *
		 * @param string $value  ID of avatar item being requested.
		 * @param string $value  Avatar type being requested.
		 * @param array  $params Array of parameters for the request.
		 */
		$params['item_id'] = apply_filters( 'bp_core_avatar_item_id', $params['item_id'], $params['object'], $params );

		if ( empty( $params['item_id'] ) ) {
			return false;
		}
	}

	/* Set avatar_dir ********************************************************/

	if ( empty( $params['avatar_dir'] ) ) {

		switch ( $params['object'] ) {

			case 'blog':
				$params['avatar_dir'] = 'blog-avatars';
				break;

			case 'group':
				if ( bp_is_active( 'groups' ) ) {
					$params['avatar_dir'] = 'group-avatars';
				} else {
					$params['avatar_dir'] = false;
				}

				break;

			case 'user':
			default:
				$params['avatar_dir'] = 'avatars';
				break;
		}

		/**
		 * Filters the avatar directory to use.
		 *
		 * @since BuddyPress 1.1.0
		 *
		 * @param string $value  Name of the subdirectory where the requested avatar should be found.
		 * @param string $value  Avatar type being requested.
		 * @param array  $params Array of parameters for the request.
		 */
		$params['avatar_dir'] = apply_filters( 'bp_core_avatar_dir', $params['avatar_dir'], $params['object'], $params );

		if ( empty( $params['avatar_dir'] ) ) {
			return false;
		}
	}

	/* <img> alt *************************************************************/

	if ( false !== strpos( $params['alt'], '%s' ) || false !== strpos( $params['alt'], '%1$s' ) ) {

		switch ( $params['object'] ) {

			case 'blog':
				$item_name = get_blog_option( $params['item_id'], 'blogname' );
				break;

			case 'group':
				$item_name = bp_get_group_name( groups_get_group( $params['item_id'] ) );
				break;

			case 'user':
			default:
				$item_name = bp_core_get_user_displayname( $params['item_id'] );
				break;
		}

		/**
		 * Filters the alt attribute value to be applied to avatar.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param string $value  alt to be applied to avatar.
		 * @param string $value  ID of avatar item being requested.
		 * @param string $value  Avatar type being requested.
		 * @param array  $params Array of parameters for the request.
		 */
		$item_name     = apply_filters( 'bp_core_avatar_alt', $item_name, $params['item_id'], $params['object'], $params );
		$params['alt'] = sprintf( $params['alt'], $item_name );
	}

	/* Sanity Checks *********************************************************/

	// Get a fallback for the 'alt' parameter, create html output.
	if ( empty( $params['alt'] ) ) {
		$params['alt'] = __( 'Profile Photo', 'buddyboss' );
	}
	$html_alt = ' alt="' . esc_attr( $params['alt'] ) . '"';

	// Filter image title and create html string.
	$html_title = '';

	/**
	 * Filters the title attribute value to be applied to avatar.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $value  Title to be applied to avatar.
	 * @param string $value  ID of avatar item being requested.
	 * @param string $value  Avatar type being requested.
	 * @param array  $params Array of parameters for the request.
	 */
	$params['title'] = apply_filters( 'bp_core_avatar_title', $params['title'], $params['item_id'], $params['object'], $params );

	if ( ! empty( $params['title'] ) ) {
		$html_title = ' title="' . esc_attr( $params['title'] ) . '"';
	}

	// Extra attributes.
	$extra_attr = ! empty( $args['extra_attr'] ) ? ' ' . $args['extra_attr'] : '';

	// Set CSS ID and create html string.
	$html_css_id = '';

	/**
	 * Filters the ID attribute to be applied to avatar.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param string $value  ID to be applied to avatar.
	 * @param string $value  ID of avatar item being requested.
	 * @param string $value  Avatar type being requested.
	 * @param array  $params Array of parameters for the request.
	 */
	$params['css_id'] = apply_filters( 'bp_core_css_id', $params['css_id'], $params['item_id'], $params['object'], $params );

	if ( ! empty( $params['css_id'] ) ) {
		$html_css_id = ' id="' . esc_attr( $params['css_id'] ) . '"';
	}

	// Set image width.
	if ( false !== $params['width'] ) {
		// Width has been specified. No modification necessary.
	} elseif ( 'thumb' == $params['type'] ) {
		$params['width'] = bp_core_avatar_thumb_width();
	} else {
		$params['width'] = bp_core_avatar_full_width();
	}
	$html_width = ' width="' . $params['width'] . '"';

	// Set image height.
	if ( false !== $params['height'] ) {
		// Height has been specified. No modification necessary.
	} elseif ( 'thumb' == $params['type'] ) {
		$params['height'] = bp_core_avatar_thumb_height();
	} else {
		$params['height'] = bp_core_avatar_full_height();
	}
	$html_height = ' height="' . $params['height'] . '"';

	/**
	 * Filters the classes to be applied to the avatar.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param array|string $value  Class(es) to be applied to the avatar.
	 * @param string       $value  ID of the avatar item being requested.
	 * @param string       $value  Avatar type being requested.
	 * @param array        $params Array of parameters for the request.
	 */
	$params['class'] = apply_filters( 'bp_core_avatar_class', $params['class'], $params['item_id'], $params['object'], $params );

	// Use an alias to leave the param unchanged.
	$avatar_classes = ! empty( $params['class'] ) ? $params['class'] : array();
	if ( ! is_array( $avatar_classes ) ) {
		$avatar_classes = explode( ' ', $avatar_classes );
	}

	// Merge classes.
	$avatar_classes = array_merge(
		$avatar_classes,
		array(
			$params['object'] . '-' . $params['item_id'] . '-avatar',
			'avatar-' . $params['width'],
		)
	);

	// Sanitize each class.
	$avatar_classes = array_map( 'sanitize_html_class', $avatar_classes );

	// Populate the class attribute.
	$html_class = ' class="' . join( ' ', $avatar_classes ) . ' photo"';

	// Set img URL and DIR based on prepopulated constants.
	$avatar_loc       = new stdClass();
	$avatar_loc->path = trailingslashit( bp_core_avatar_upload_path() );
	$avatar_loc->url  = trailingslashit( bp_core_avatar_url() );

	$avatar_loc->dir = trailingslashit( $params['avatar_dir'] );

	/**
	 * Filters the avatar folder directory URL.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value Path to the avatar folder URL.
	 * @param int    $value ID of the avatar item being requested.
	 * @param string $value Avatar type being requested.
	 * @param string $value Subdirectory where the requested avatar should be found.
	 */
	$avatar_folder_url = apply_filters( 'bp_core_avatar_folder_url', ( $avatar_loc->url . $avatar_loc->dir . $params['item_id'] ), $params['item_id'], $params['object'], $params['avatar_dir'] );

	/**
	 * Filters the avatar folder directory path.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value Path to the avatar folder directory.
	 * @param int    $value ID of the avatar item being requested.
	 * @param string $value Avatar type being requested.
	 * @param string $value Subdirectory where the requested avatar should be found.
	 */
	$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', ( $avatar_loc->path . $avatar_loc->dir . $params['item_id'] ), $params['item_id'], $params['object'], $params['avatar_dir'] );

	/**
	 * Look for uploaded avatar first. Use it if it exists.
	 * Set the file names to search for, to select the full size
	 * or thumbnail image.
	 */
	$avatar_size              = ( 'full' == $params['type'] ) ? '-bpfull' : '-bpthumb';
	$legacy_user_avatar_name  = ( 'full' == $params['type'] ) ? '-avatar2' : '-avatar1';
	$legacy_group_avatar_name = ( 'full' == $params['type'] ) ? '-groupavatar-full' : '-groupavatar-thumb';

	// Check for directory.
	if ( file_exists( $avatar_folder_dir ) ) {

		$avatar_url = '';

		// Open directory.
		if ( $av_dir = opendir( $avatar_folder_dir ) ) {

			// Stash files in an array once to check for one that matches.
			$avatar_files = array();
			while ( false !== ( $avatar_file = readdir( $av_dir ) ) ) {
				// Only add files to the array (skip directories).
				if ( 2 < strlen( $avatar_file ) ) {
					$avatar_files[] = $avatar_file;
				}
			}

			// Check for array.
			if ( 0 < count( $avatar_files ) ) {

				// Check for current avatar.
				foreach ( $avatar_files as $key => $value ) {
					if ( strpos( $value, $avatar_size ) !== false ) {
						$avatar_url = $avatar_folder_url . '/' . $avatar_files[ $key ];
					}
				}

				// Legacy avatar check.
				if ( ! isset( $avatar_url ) ) {
					foreach ( $avatar_files as $key => $value ) {
						if ( strpos( $value, $legacy_user_avatar_name ) !== false ) {
							$avatar_url = $avatar_folder_url . '/' . $avatar_files[ $key ];
						}
					}

					// Legacy group avatar check.
					if ( ! isset( $avatar_url ) ) {
						foreach ( $avatar_files as $key => $value ) {
							if ( strpos( $value, $legacy_group_avatar_name ) !== false ) {
								$avatar_url = $avatar_folder_url . '/' . $avatar_files[ $key ];
							}
						}
					}
				}
			}
			// Close the avatar directory.
			closedir( $av_dir );
		}

		/**
		 * Filters a locally uploaded avatar URL.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param string $avatar_url URL for a locally uploaded avatar.
		 * @param array  $params     Array of parameters for the request.
		 */
		$avatar_url = apply_filters( 'bp_core_fetch_avatar_url_check', $avatar_url, $params );

		// If we found a locally uploaded avatar.
		if ( isset( $avatar_url ) && ! empty( $avatar_url ) ) {
			// Support custom scheme.
			$avatar_url = set_url_scheme( $avatar_url, $params['scheme'] );

			// Return it wrapped in an <img> element.
			if ( true === $params['html'] ) {

				/**
				 * Filters an avatar URL wrapped in an <img> element.
				 *
				 * @since BuddyPress 1.1.0
				 *
				 * @param string $value             Full <img> element for an avatar.
				 * @param array  $params            Array of parameters for the request.
				 * @param string $value             ID of the item requested.
				 * @param string $value             Subdirectory where the requested avatar should be found.
				 * @param string $html_css_id       ID attribute for avatar.
				 * @param string $html_width        Width attribute for avatar.
				 * @param string $html_height       Height attribute for avatar.
				 * @param string $avatar_folder_url Avatar URL path.
				 * @param string $avatar_folder_dir Avatar DIR path.
				 */
				return apply_filters( 'bp_core_fetch_avatar', '<img src="' . $avatar_url . '"' . $html_class . $html_css_id . $html_width . $html_height . $html_alt . $html_title . $extra_attr . ' />', $params, $params['item_id'], $params['avatar_dir'], $html_css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir );

				// ...or only the URL
			} else {

				/**
				 * Filters a locally uploaded avatar URL.
				 *
				 * @since BuddyPress 1.2.5
				 *
				 * @param string $avatar_url URL for a locally uploaded avatar.
				 * @param array  $params     Array of parameters for the request.
				 */
				return apply_filters( 'bp_core_fetch_avatar_url', $avatar_url, $params );
			}
		}
	}

	// By default, Gravatar is not pinged for groups.
	if ( null === $params['no_grav'] ) {
		$params['no_grav'] = 'group' === $params['object'];
	}

	/**
	 * Filters whether or not to skip Gravatar check.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool  $value  Whether or not to skip Gravatar.
	 * @param array $params Array of parameters for the avatar request.
	 */
	if ( ! apply_filters( 'bp_core_fetch_avatar_no_grav', $params['no_grav'], $params ) ) {

		// Set gravatar type.
		if ( empty( $bp->grav_default->{$params['object']} ) ) {
			$default_grav = 'wavatar';
		} elseif ( 'mystery' == $bp->grav_default->{$params['object']} ) {

			/**
			 * Filters the Mystery person avatar src value.
			 *
			 * @since BuddyPress 1.2.0
			 *
			 * @param string $value Avatar value.
			 * @param string $value Width to display avatar at.
			 */
			$default_grav = apply_filters( 'bp_core_mysteryman_src', 'mm', $params['width'] );
		} else {
			$default_grav = $bp->grav_default->{$params['object']};
		}

		// Set gravatar object.
		if ( empty( $params['email'] ) ) {
			if ( 'user' == $params['object'] ) {
				$params['email'] = bp_core_get_user_email( $params['item_id'] );
			} elseif ( 'group' == $params['object'] || 'blog' == $params['object'] ) {
				$params['email'] = $params['item_id'] . '-' . $params['object'] . '@' . bp_get_root_domain();
			}
		}

		/**
		 * Filters the Gravatar email to use.
		 *
		 * @since BuddyPress 1.1.0
		 *
		 * @param string $value Email to use in Gravatar request.
		 * @param string $value ID of the item being requested.
		 * @param string $value Object type being requested.
		 */
		$params['email'] = apply_filters( 'bp_core_gravatar_email', $params['email'], $params['item_id'], $params['object'] );

		/**
		 * Filters the Gravatar URL host.
		 *
		 * @since BuddyPress 1.0.2
		 *
		 * @param string $value Gravatar URL host.
		 */
		$gravatar = apply_filters( 'bp_gravatar_url', 'https://www.gravatar.com/avatar/' );

		// Append email hash to Gravatar.
		$gravatar .= md5( strtolower( $params['email'] ) );

		// Main Gravatar URL args.
		$url_args = array(
			's' => $params['width'],
		);

		// Custom Gravatar URL args.
		if ( ! empty( $params['force_default'] ) ) {
			$url_args['f'] = 'y';
		}
		if ( ! empty( $params['rating'] ) ) {
			$url_args['r'] = strtolower( $params['rating'] );
		}

		/**
		 * Filters the Gravatar "d" parameter.
		 *
		 * @since BuddyPress 2.6.0
		 *
		 * @param string $default_grav The avatar default.
		 * @param array  $params       The avatar's data.
		 */
		$default_grav = apply_filters( 'bp_core_avatar_default', $default_grav, $params );

		// Only set default image if 'Gravatar Logo' is not requested.
		if ( 'gravatar_default' !== $default_grav ) {
			$url_args['d'] = $default_grav;
		}

		// Filter the URL args to the Gravatar URL.
		$url_args = apply_filters( 'bp_core_gravatar_url_args', $url_args, $params );

		if ( isset( $url_args['d'] ) && 'blank' === $url_args['d'] ) {
			$gravatar = apply_filters( 'bp_discussion_blank_option_default_avatar', bb_get_blank_profile_avatar() );
		} elseif ( isset( $url_args['d'] ) && 'mm' === $url_args['d'] ) {
			$key      = base64_encode( 'https://www.gravatar.com/avatar/' . md5( strtolower( $params['email'] ) ) . '?d=404' );
			$response = get_transient( $key );
			if ( false === $response ) {
				$gravcheck = 'https://www.gravatar.com/avatar/' . md5( strtolower( $params['email'] ) ) . '?d=404';
				$response  = get_headers( $gravcheck );
				set_transient( $key, $response, 3 * HOUR_IN_SECONDS );
			}
			if ( isset( $response[0] ) && $response[0] == 'HTTP/1.1 404 Not Found' ) {

				// Find default avatar option.
				$gravatar = bb_attachments_get_default_profile_group_avatar_image( $params );

				if ( ! $gravatar || empty( $gravatar ) ) {
					/**
					 * Filters the Gravatar URL host.
					 *
					 * @since BuddyPress 1.0.2
					 *
					 * @param string $value Gravatar URL host.
					 */
					$gravatar_url = apply_filters( 'bp_gravatar_url', 'https://www.gravatar.com/avatar/' );

					// Append email hash to Gravatar.
					$gravatar_url .= md5( strtolower( $params['email'] ) );

					$gravatar = esc_url(
						add_query_arg(
							rawurlencode_deep( array_filter( $url_args ) ),
							$gravatar_url
						)
					);

					$gravatar = apply_filters( 'bp_gravatar_not_found_avatar', $gravatar );
				}

				/**
				 * Filters a default avatar URL.
				 *
				 * @since BuddyBoss 1.8.6
				 *
				 * @param string $avatar_url URL for a default avatar.
				 * @param array  $params     Array of parameters for the request.
				 */
				$gravatar = apply_filters( 'bb_default_fetch_avatar_url_' . $params['object'], $gravatar, $params );

			} else {

				// Set up the Gravatar URL.
				$gravatar = esc_url(
					add_query_arg(
						rawurlencode_deep( array_filter( $url_args ) ),
						$gravatar
					)
				);
			}
		} else {
			// Set up the Gravatar URL.
			$gravatar = esc_url(
				add_query_arg(
					rawurlencode_deep( array_filter( $url_args ) ),
					$gravatar
				)
			);
		}

		// Set up the Gravatar URL.
		// $gravatar = esc_url( add_query_arg(
		// rawurlencode_deep( array_filter( $url_args ) ),
		// $gravatar
		// ) );

		// No avatar was found, and we've been told not to use a gravatar.
	} else {

		// Find default avatar option.
		$gravatar = bb_attachments_get_default_profile_group_avatar_image( $params );

		if ( ! $gravatar || empty( $gravatar ) ) {
			remove_filter( 'get_avatar_url', 'bb_core_get_avatar_data_url_filter', 10, 3 );
			$gravatar = get_avatar_url(
				$params['email'],
				array(
					'force_default' => true,
					'size'          => $params['width'],
				)
			);
			add_filter( 'get_avatar_url', 'bb_core_get_avatar_data_url_filter', 10, 3 );
		}

		/**
		 * Filters a default avatar URL.
		 *
		 * @since BuddyBoss 1.8.6
		 *
		 * @param string $avatar_url URL for a default avatar.
		 * @param array  $params     Array of parameters for the request.
		 */
		$gravatar = apply_filters( 'bb_default_fetch_avatar_url_' . $params['object'], $gravatar, $params );

		/**
		 * Filters the avatar default when Gravatar is not used.
		 *
		 * This is a variable filter dependent on the avatar type being requested.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param string $value  Default avatar for non-gravatar requests.
		 * @param array  $params Array of parameters for the avatar request.
		 */
		$gravatar = apply_filters( 'bp_core_default_avatar_' . $params['object'], $gravatar, $params );
	}

	/**
	 * Filters a gravatar avatar URL.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $avatar_url URL for a gravatar.
	 * @param array  $params     Array of parameters for the request.
	 */
	$gravatar = apply_filters( 'bp_core_fetch_gravatar_url_check', $gravatar, $params );

	if ( true === $params['html'] ) {

		/** This filter is documented in bp-core/bp-core-avatars.php */
		return apply_filters( 'bp_core_fetch_avatar', '<img src="' . $gravatar . '"' . $html_css_id . $html_class . $html_width . $html_height . $html_alt . $html_title . $extra_attr . ' />', $params, $params['item_id'], $params['avatar_dir'], $html_css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir );
	} else {

		/** This filter is documented in bp-core/bp-core-avatars.php */
		return apply_filters( 'bp_core_fetch_avatar_url', $gravatar, $params );
	}
}

/**
 * Delete an existing avatar.
 *
 * @since BuddyPress 1.1.0
 *
 * @param array|string $args {
 *     Array of function parameters.
 *     @type bool|int    $item_id    ID of the item whose avatar you're deleting.
 *                                   Defaults to the current item of type $object.
 *     @type string      $object     Object type of the item whose avatar you're
 *                                   deleting. 'user', 'group', 'blog', or custom.
 *                                   Default: 'user'.
 *     @type bool|string $avatar_dir Subdirectory where avatar is located.
 *                                   Default: false, which falls back on the default location
 *                                   corresponding to the $object.
 * }
 * @return bool True on success, false on failure.
 */
function bp_core_delete_existing_avatar( $args = '' ) {

	$defaults = array(
		'item_id'    => false,
		'item_type'  => null,
		'object'     => 'user', // User OR group OR blog OR custom type (if you use filters).
		'avatar_dir' => false,
	);

	$args = bp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	/**
	 * Filters whether or not to handle deleting an existing avatar.
	 *
	 * If you want to override this function, make sure you return false.
	 *
	 * @since BuddyPress 2.5.1
	 *
	 * @param bool  $value Whether or not to delete the avatar.
	 * @param array $args {
	 *     Array of function parameters.
	 *
	 *     @type bool|int    $item_id    ID of the item whose avatar you're deleting.
	 *                                   Defaults to the current item of type $object.
	 *     @type string      $object     Object type of the item whose avatar you're
	 *                                   deleting. 'user', 'group', 'blog', or custom.
	 *                                   Default: 'user'.
	 *     @type bool|string $avatar_dir Subdirectory where avatar is located.
	 *                                   Default: false, which falls back on the default location
	 *                                   corresponding to the $object.
	 * }
	 */
	if ( ! apply_filters( 'bp_core_pre_delete_existing_avatar', true, $args ) ) {
		return true;
	}

	if ( empty( $item_id ) && empty( $args['item_type'] ) ) {
		if ( 'user' == $object ) {
			$item_id = bp_displayed_user_id();
		} elseif ( 'group' == $object ) {
			$item_id = buddypress()->groups->current_group->id;
		} elseif ( 'blog' == $object ) {
			$item_id = get_current_blog_id();
		}

		/** This filter is documented in bp-core/bp-core-avatars.php */
		$item_id = apply_filters( 'bp_core_avatar_item_id', $item_id, $object );

		if ( ! $item_id ) {
			return false;
		}
	}

	if ( empty( $avatar_dir ) ) {
		if ( 'user' == $object ) {
			$avatar_dir = 'avatars';
		} elseif ( 'group' == $object ) {
			$avatar_dir = 'group-avatars';
		} elseif ( 'blog' == $object ) {
			$avatar_dir = 'blog-avatars';
		}

		/** This filter is documented in bp-core/bp-core-avatars.php */
		$avatar_dir = apply_filters( 'bp_core_avatar_dir', $avatar_dir, $object );

		if ( ! $avatar_dir ) {
			return false;
		}
	}

	/** This filter is documented in bp-core/bp-core-avatars.php */
	$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', bp_core_avatar_upload_path() . '/' . $avatar_dir . '/' . $item_id, $item_id, $object, $avatar_dir );

	if ( ! is_dir( $avatar_folder_dir ) ) {
		return false;
	}

	if ( $av_dir = opendir( $avatar_folder_dir ) ) {
		while ( false !== ( $avatar_file = readdir( $av_dir ) ) ) {
			if ( ( preg_match( '/-bpfull/', $avatar_file ) || preg_match( '/-bpthumb/', $avatar_file ) ) && '.' != $avatar_file && '..' != $avatar_file ) {
				@unlink( $avatar_folder_dir . '/' . $avatar_file );
			}
		}
		closedir( $av_dir );
	}

	@rmdir( $avatar_folder_dir );

	/**
	 * Fires after deleting an existing avatar.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param array $args Array of arguments used for avatar deletion.
	 */
	do_action( 'bp_core_delete_existing_avatar', $args );

	return true;
}

/**
 * Ajax delete an avatar for a given object and item id.
 *
 * @since BuddyPress 2.3.0
 *
 * @return string|null A JSON object containing success data if the avatar was deleted,
 *                     error message otherwise.
 */
function bp_avatar_ajax_delete() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	$avatar_data              = $_POST;
	$avatar_data['item_type'] = isset( $_POST['item_type'] ) ? sanitize_text_field( $_POST['item_type'] ) : null;

	if ( empty( $avatar_data['object'] ) || ( empty( $avatar_data['item_id'] ) && empty( $avatar_data['item_type'] ) ) ) {
		wp_send_json_error();
	}

	$nonce = 'bp_delete_avatar_link';
	if ( 'group' === $avatar_data['object'] ) {
		$nonce = 'bp_group_avatar_delete';
	}

	// Check the nonce.
	check_admin_referer( $nonce, 'nonce' );

	// Capability check.
	if ( ! bp_attachments_current_user_can( 'edit_avatar', $avatar_data ) ) {
		wp_send_json_error();
	}

	// Handle delete.
	if ( bp_core_delete_existing_avatar(
		array(
			'item_id'   => $avatar_data['item_id'],
			'item_type' => $avatar_data['item_type'],
			'object'    => $avatar_data['object'],
		)
	) ) {

		$avatar = '';
		if ( ! empty( $avatar_data['item_id'] ) || empty( $avatar_data['item_type'] ) ) {
			$avatar = esc_url(
				bp_core_fetch_avatar(
					array(
						'object'    => $avatar_data['object'],
						'item_id'   => $avatar_data['item_id'],
						'item_type' => $avatar_data['item_type'],
						'html'      => false,
						'type'      => 'full',
					)
				)
			);
		}

		$return = array(
			'avatar'        => html_entity_decode( $avatar, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ),
			'feedback_code' => 4,
			'item_id'       => $avatar_data['item_id'],
		);

		wp_send_json_success( $return );
	} else {
		wp_send_json_error(
			array(
				'feedback_code' => 3,
			)
		);
	}
}
add_action( 'wp_ajax_bp_avatar_delete', 'bp_avatar_ajax_delete' );

/**
 * Handle avatar uploading.
 *
 * The functions starts off by checking that the file has been uploaded
 * properly using bp_core_check_avatar_upload(). It then checks that the file
 * size is within limits, and that it has an accepted file extension (jpg, gif,
 * png). If everything checks out, crop the image and move it to its real
 * location.
 *
 * @since BuddyPress 1.1.0
 *
 * @see bp_core_check_avatar_upload()
 * @see bp_core_check_avatar_type()
 *
 * @param array  $file              The appropriate entry the from $_FILES superglobal.
 * @param string $upload_dir_filter A filter to be applied to 'upload_dir'.
 * @return bool True on success, false on failure.
 */
function bp_core_avatar_handle_upload( $file, $upload_dir_filter ) {

	/**
	 * Filters whether or not to handle uploading.
	 *
	 * If you want to override this function, make sure you return false.
	 *
	 * @since BuddyPress 1.2.4
	 *
	 * @param bool   $value             Whether or not to crop.
	 * @param array  $file              Appropriate entry from $_FILES superglobal.
	 * @parma string $upload_dir_filter A filter to be applied to 'upload_dir'.
	 */
	if ( ! apply_filters( 'bp_core_pre_avatar_handle_upload', true, $file, $upload_dir_filter ) ) {
		return true;
	}

	// Setup some variables.
	$bp          = buddypress();
	$upload_path = bp_core_avatar_upload_path();

	// Upload the file.
	$avatar_attachment          = new BP_Attachment_Avatar();
	$bp->avatar_admin->original = $avatar_attachment->upload( $file, $upload_dir_filter );

	// In case of an error, stop the process and display a feedback to the user.
	if ( ! empty( $bp->avatar_admin->original['error'] ) ) {
		bp_core_add_message( sprintf( __( 'Upload Failed! Error was: %s', 'buddyboss' ), $bp->avatar_admin->original['error'] ), 'error' );
		return false;
	}

	// The Avatar UI available width.
	$ui_available_width = 0;

	// Try to set the ui_available_width using the avatar_admin global.
	if ( isset( $bp->avatar_admin->ui_available_width ) ) {
		$ui_available_width = $bp->avatar_admin->ui_available_width;
	}

	// Maybe resize.
	$bp->avatar_admin->resized = $avatar_attachment->shrink( $bp->avatar_admin->original['file'], $ui_available_width );
	$bp->avatar_admin->image   = new stdClass();

	if ( is_wp_error( $bp->avatar_admin->resized ) ) {
		bp_core_add_message( sprintf( __( 'Upload Error: %s', 'buddyboss' ), $bp->avatar_admin->resized->get_error_message() ), 'error' );
		return false;
	}

	// We only want to handle one image after resize.
	if ( empty( $bp->avatar_admin->resized ) ) {
		$bp->avatar_admin->image->file = $bp->avatar_admin->original['file'];
		$bp->avatar_admin->image->dir  = str_replace( $upload_path, '', $bp->avatar_admin->original['file'] );
	} else {
		$bp->avatar_admin->image->file = $bp->avatar_admin->resized['path'];
		$bp->avatar_admin->image->dir  = str_replace( $upload_path, '', $bp->avatar_admin->resized['path'] );
		@unlink( $bp->avatar_admin->original['file'] );
	}

	// Check for WP_Error on what should be an image.
	if ( is_wp_error( $bp->avatar_admin->image->dir ) ) {
		bp_core_add_message( sprintf( __( 'Upload Error: %s', 'buddyboss' ), $bp->avatar_admin->image->dir->get_error_message() ), 'error' );
		return false;
	}

	// If the uploaded image is smaller than the "full" dimensions, throw a warning.
	if ( $avatar_attachment->is_too_small( $bp->avatar_admin->image->file ) ) {
		bp_core_add_message( sprintf( __( 'For best results, upload an image that is %1$spx by %2$spx or larger.', 'buddyboss' ), bp_core_avatar_full_width(), bp_core_avatar_full_height() ), 'error' );
	}

	// Set the url value for the image.
	$bp->avatar_admin->image->url = bp_core_avatar_url() . $bp->avatar_admin->image->dir;

	return true;
}

/**
 * Ajax upload an avatar.
 *
 * @since BuddyPress 2.3.0
 *
 * @return string|null A JSON object containing success data if the upload succeeded
 *                     error message otherwise.
 */
function bp_avatar_ajax_upload() {
	if ( ! bp_is_post_request() ) {
		wp_die();
	}

	/**
	 * Sending the json response will be different if
	 * the current Plupload runtime is html4.
	 */
	$is_html4 = false;
	if ( ! empty( $_POST['html4'] ) ) {
		$is_html4 = true;
	}

	// Check the nonce.
	check_admin_referer( 'bp-uploader' );

	// Init the BuddyPress parameters.
	$bp_params = array();

	// We need it to carry on.
	if ( ! empty( $_POST['bp_params'] ) ) {
		$bp_params = $_POST['bp_params'];
	} else {
		bp_attachments_json_response( false, $is_html4 );
	}

	// We need the object to set the uploads dir filter.
	if ( empty( $bp_params['object'] ) ) {
		bp_attachments_json_response( false, $is_html4 );
	}

	// Capability check.
	if ( ! bp_attachments_current_user_can( 'edit_avatar', $bp_params ) ) {
		bp_attachments_json_response( false, $is_html4 );
	}

	$bp                             = buddypress();
	$bp_params['upload_dir_filter'] = '';
	$needs_reset                    = array();

	if ( 'user' === $bp_params['object'] && bp_is_active( 'xprofile' ) ) {
		$bp_params['upload_dir_filter'] = 'xprofile_avatar_upload_dir';

		if ( ! bp_displayed_user_id() && ! empty( $bp_params['item_id'] ) ) {
			$needs_reset            = array(
				'key'   => 'displayed_user',
				'value' => $bp->displayed_user,
			);
			$bp->displayed_user->id = $bp_params['item_id'];
		}
	} elseif ( 'group' === $bp_params['object'] && bp_is_active( 'groups' ) ) {
		$bp_params['upload_dir_filter'] = 'groups_avatar_upload_dir';

		if ( ! bp_get_current_group_id() && ! empty( $bp_params['item_id'] ) ) {
			$needs_reset               = array(
				'component' => 'groups',
				'key'       => 'current_group',
				'value'     => $bp->groups->current_group,
			);
			$bp->groups->current_group = groups_get_group( $bp_params['item_id'] );
		}
	} else {
		/**
		 * Filter here to deal with other components.
		 *
		 * @since BuddyPress 2.3.0
		 *
		 * @var array $bp_params the BuddyPress Ajax parameters.
		 */
		$bp_params = apply_filters( 'bp_core_avatar_ajax_upload_params', $bp_params );
	}

	if ( ! isset( $bp->avatar_admin ) ) {
		$bp->avatar_admin = new stdClass();
	}

	/**
	 * The BuddyPress upload parameters is including the Avatar UI Available width,
	 * add it to the avatar_admin global for a later use.
	 */
	if ( isset( $bp_params['ui_available_width'] ) ) {
		$bp->avatar_admin->ui_available_width = (int) $bp_params['ui_available_width'];
	}

	// Upload the avatar.
	$avatar = bp_core_avatar_handle_upload( $_FILES, $bp_params['upload_dir_filter'] );

	// Reset objects.
	if ( ! empty( $needs_reset ) ) {
		if ( ! empty( $needs_reset['component'] ) ) {
			$bp->{$needs_reset['component']}->{$needs_reset['key']} = $needs_reset['value'];
		} else {
			$bp->{$needs_reset['key']} = $needs_reset['value'];
		}
	}

	// Init the feedback message.
	$feedback_message = false;

	if ( ! empty( $bp->template_message ) ) {
		$feedback_message = $bp->template_message;

		// Remove template message.
		$bp->template_message      = false;
		$bp->template_message_type = false;

		@setcookie( 'bp-message', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
		@setcookie( 'bp-message-type', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	}

	if ( empty( $avatar ) ) {
		// Default upload error.
		$message = __( 'Upload failed.', 'buddyboss' );

		// Use the template message if set.
		if ( ! empty( $feedback_message ) ) {
			$message = $feedback_message;
		}

		// Upload error reply.
		bp_attachments_json_response(
			false,
			$is_html4,
			array(
				'type'    => 'upload_error',
				'message' => $message,
			)
		);
	}

	if ( empty( $bp->avatar_admin->image->file ) ) {
		bp_attachments_json_response( false, $is_html4 );
	}

	$uploaded_image = @getimagesize( $bp->avatar_admin->image->file );

	// Set the name of the file.
	$name       = $_FILES['file']['name'];
	$name_parts = pathinfo( $name );
	$name       = trim( substr( $name, 0, - ( 1 + strlen( $name_parts['extension'] ) ) ) );

	// Finally return the avatar to the editor.
	bp_attachments_json_response(
		true,
		$is_html4,
		array(
			'name'     => $name,
			'url'      => $bp->avatar_admin->image->url,
			'width'    => $uploaded_image[0],
			'height'   => $uploaded_image[1],
			'feedback' => $feedback_message,
		)
	);
}
add_action( 'wp_ajax_bp_avatar_upload', 'bp_avatar_ajax_upload' );

/**
 * Handle avatar webcam capture.
 *
 * @since BuddyPress 2.3.0
 *
 * @param string $data    Base64 encoded image.
 * @param int    $item_id Item to associate.
 * @return bool True on success, false on failure.
 */
function bp_avatar_handle_capture( $data = '', $item_id = 0 ) {
	$item_type = isset( $_POST['item_type'] ) ? sanitize_text_field( $_POST['item_type'] ) : null;

	if ( empty( $data ) || ( empty( $item_id ) && empty( $item_type ) ) ) {
		return false;
	}

	/**
	 * Filters whether or not to handle avatar webcam capture.
	 *
	 * If you want to override this function, make sure you return false.
	 *
	 * @since BuddyPress 2.5.1
	 *
	 * @param bool   $value   Whether or not to crop.
	 * @param string $data    Base64 encoded image.
	 * @param int    $item_id Item to associate.
	 */
	if ( ! apply_filters( 'bp_avatar_pre_handle_capture', true, $data, $item_id ) ) {
		return true;
	}

	$avatar_dir = bp_core_avatar_upload_path() . '/avatars';

	// It's not a regular upload, we may need to create this folder.
	if ( ! file_exists( $avatar_dir ) ) {
		if ( ! wp_mkdir_p( $avatar_dir ) ) {
			return false;
		}
	}

	/**
	 * Filters the Avatar folder directory.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param string $avatar_dir Directory for storing avatars.
	 * @param int    $item_id    ID of the item being acted on.
	 * @param string $value      Avatar type.
	 * @param string $value      Avatars word.
	 */
	$avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', $avatar_dir . '/' . $item_id, $item_id, 'user', 'avatars' );

	// It's not a regular upload, we may need to create this folder.
	if ( ! is_dir( $avatar_folder_dir ) ) {
		if ( ! wp_mkdir_p( $avatar_folder_dir ) ) {
			return false;
		}
	}

	$original_file = $avatar_folder_dir . '/webcam-capture-' . $item_id . '.png';

	if ( file_put_contents( $original_file, $data ) ) {
		$avatar_to_crop = str_replace( bp_core_avatar_upload_path(), '', $original_file );

		// Crop to default values.
		$crop_args = array(
			'item_id'       => $item_id,
			'item_type'     => $item_type,
			'original_file' => $avatar_to_crop,
			'crop_x'        => 0,
			'crop_y'        => 0,
		);

		return bp_core_avatar_handle_crop( $crop_args );
	} else {
		return false;
	}
}

/**
 * Crop an uploaded avatar.
 *
 * @since BuddyPress 1.1.0
 *
 * @param array|string $args {
 *     Array of function parameters.
 *
 *     @type string      $object        Object type of the item whose avatar you're
 *                                      handling. 'user', 'group', 'blog', or custom.
 *                                      Default: 'user'.
 *     @type string      $avatar_dir    Subdirectory where avatar should be stored.
 *                                      Default: 'avatars'.
 *     @type bool|int    $item_id       ID of the item that the avatar belongs to.
 *     @type bool|string $original_file Absolute path to the original avatar file.
 *     @type int         $crop_w        Crop width. Default: the global 'full' avatar width,
 *                                      as retrieved by bp_core_avatar_full_width().
 *     @type int         $crop_h        Crop height. Default: the global 'full' avatar height,
 *                                      as retrieved by bp_core_avatar_full_height().
 *     @type int         $crop_x        The horizontal starting point of the crop. Default: 0.
 *     @type int         $crop_y        The vertical starting point of the crop. Default: 0.
 * }
 * @return bool True on success, false on failure.
 */
function bp_core_avatar_handle_crop( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'object'        => 'user',
			'avatar_dir'    => 'avatars',
			'item_id'       => false,
			'item_type'     => null,
			'original_file' => false,
			'crop_w'        => bp_core_avatar_full_width(),
			'crop_h'        => bp_core_avatar_full_height(),
			'crop_x'        => 0,
			'crop_y'        => 0,
		)
	);

	/**
	 * Filters whether to do cropping.
	 *
	 * If you want to override this function, make sure you return false.
	 *
	 * @since BuddyBoss 2.0.4
	 *
	 * @param bool  $value Whether to do crop.
	 * @param array $r     Array of parsed arguments for function.
	 */
	if ( apply_filters( 'bp_core_do_avatar_handle_crop', true, $r ) ) {

		/**
		 * Filters whether or not to handle cropping.
		 *
		 * If you want to override this function, make sure you return false.
		 *
		 * @since BuddyPress 1.2.4
		 *
		 * @param bool  $value Whether or not to crop.
		 * @param array $r     Array of parsed arguments for function.
		 */
		if ( ! apply_filters( 'bp_core_pre_avatar_handle_crop', true, $r ) ) {
			return true;
		}
	}

	// Crop the file.
	$avatar_attachment = new BP_Attachment_Avatar();
	$cropped           = $avatar_attachment->crop( $r );

	// Check for errors.
	if ( empty( $cropped['full'] ) || empty( $cropped['thumb'] ) || is_wp_error( $cropped['full'] ) || is_wp_error( $cropped['thumb'] ) ) {
		return false;
	}

	return true;
}

/**
 * Ajax set an avatar for a given object and item id.
 *
 * @since BuddyPress 2.3.0
 *
 * @return string|null A JSON object containing success data if the crop/capture succeeded
 *                     error message otherwise.
 */
function bp_avatar_ajax_set() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Check the nonce.
	check_admin_referer( 'bp_avatar_cropstore', 'nonce' );

	$avatar_data = bp_parse_args(
		$_POST,
		array(
			'crop_w' => bp_core_avatar_full_width(),
			'crop_h' => bp_core_avatar_full_height(),
			'crop_x' => 0,
			'crop_y' => 0,
		)
	);

	$avatar_data['item_type'] = isset( $_POST['item_type'] ) ? sanitize_text_field( $_POST['item_type'] ) : null;

	if ( empty( $avatar_data['object'] ) || ( empty( $avatar_data['item_id'] ) && empty( $avatar_data['item_type'] ) ) || empty( $avatar_data['original_file'] ) ) {
		wp_send_json_error();
	}

	// Capability check.
	if ( ! bp_attachments_current_user_can( 'edit_avatar', $avatar_data ) ) {
		wp_send_json_error();
	}

	if ( ! empty( $avatar_data['type'] ) && 'camera' === $avatar_data['type'] && 'user' === $avatar_data['object'] ) {
		$webcam_avatar = false;

		if ( ! empty( $avatar_data['original_file'] ) ) {
			$webcam_avatar = str_replace( array( 'data:image/png;base64,', ' ' ), array( '', '+' ), $avatar_data['original_file'] );
			$webcam_avatar = base64_decode( $webcam_avatar );
		}

		if ( ! bp_avatar_handle_capture( $webcam_avatar, $avatar_data['item_id'] ) ) {
			$feedback_code = 1;
			if ( ! bb_is_gd_or_imagick_library_enabled() ) {
				$feedback_code = 5;
			}

			wp_send_json_error(
				array(
					'feedback_code' => $feedback_code,
				)
			);

		} else {
			$return = array(
				'avatar'        => esc_url(
					bp_core_fetch_avatar(
						array(
							'object'  => $avatar_data['object'],
							'item_id' => $avatar_data['item_id'],
							'html'    => false,
							'type'    => 'full',
						)
					)
				),
				'feedback_code' => 2,
				'item_id'       => $avatar_data['item_id'],
			);

			/**
			 * Fires if the new avatar was successfully captured.
			 *
			 * @since BuddyPress 1.1.0 Used to inform the avatar was successfully cropped
			 * @since BuddyPress 2.3.4 Add two new parameters to inform about the user id and
			 *              about the way the avatar was set (eg: 'crop' or 'camera')
			 *              Move the action at the right place, once the avatar is set
			 * @since BuddyPress 2.8.0 Added the `$avatar_data` parameter.
			 *
			 * @param string $item_id     Inform about the user id the avatar was set for.
			 * @param string $type        Inform about the way the avatar was set ('camera').
			 * @param array  $avatar_data Array of parameters passed to the avatar handler.
			 */
			do_action( 'xprofile_avatar_uploaded', (int) $avatar_data['item_id'], $avatar_data['type'], $avatar_data );

			wp_send_json_success( $return );
		}

		return;
	}

	$original_file = str_replace( bp_core_avatar_url(), '', $avatar_data['original_file'] );

	// Set avatars dir & feedback part.
	if ( 'user' === $avatar_data['object'] ) {
		$avatar_dir = 'avatars';

		// Defaults to object-avatars dir.
	} else {
		$avatar_dir = sanitize_key( $avatar_data['object'] ) . '-avatars';
	}

	/**
	 * Update avatar directory based on avatar data conditionally.
	 *
	 * @since BuddyBoss 2.0.4
	 *
	 * @param string $avatar_dir  Avatar Directory
	 * @param array  $avatar_data Avatar Data.
	 */
	$avatar_dir = apply_filters( 'bb_avatar_ajax_set_avatar_dir', $avatar_dir, $avatar_data );

	// Crop args.
	$r = array(
		'item_id'       => $avatar_data['item_id'],
		'item_type'     => $avatar_data['item_type'],
		'object'        => $avatar_data['object'],
		'avatar_dir'    => $avatar_dir,
		'original_file' => $original_file,
		'crop_w'        => $avatar_data['crop_w'],
		'crop_h'        => $avatar_data['crop_h'],
		'crop_x'        => $avatar_data['crop_x'],
		'crop_y'        => $avatar_data['crop_y'],
	);

	// Handle crop.
	if ( bp_core_avatar_handle_crop( $r ) ) {
		$return = array(
			'avatar'        => esc_url(
				bp_core_fetch_avatar(
					array(
						'object'    => $avatar_data['object'],
						'item_id'   => $avatar_data['item_id'],
						'item_type' => $avatar_data['item_type'],
						'html'      => false,
						'type'      => 'full',
					)
				)
			),
			'feedback_code' => 2,
			'item_id'       => $avatar_data['item_id'],
		);

		$r['avatar'] = $return['avatar'];

		if ( 'user' === $avatar_data['object'] ) {
			/** This action is documented in bp-core/bp-core-avatars.php */
			do_action( 'xprofile_avatar_uploaded', (int) $avatar_data['item_id'], $avatar_data['type'], $r );
		} elseif ( 'group' === $avatar_data['object'] ) {
			/** This action is documented in bp-groups/bp-groups-screens.php */
			do_action( 'groups_avatar_uploaded', (int) $avatar_data['item_id'], $avatar_data['type'], $r );
		} else {
			/** action to used for other component. **/
			do_action( sanitize_title( $avatar_data['object'] ) . '_avatar_uploaded', (int) $avatar_data['item_id'], $avatar_data['type'], $r );
		}

		wp_send_json_success( $return );
	} else {
		wp_send_json_error(
			array(
				'feedback_code' => 1,
			)
		);
	}
}
add_action( 'wp_ajax_bp_avatar_set', 'bp_avatar_ajax_set' );

/**
 * Is the current avatar upload error-free?
 *
 * @since BuddyPress 1.0.0
 *
 * @param array $file The $_FILES array.
 * @return bool True if no errors are found. False if there are errors.
 */
function bp_core_check_avatar_upload( $file ) {
	if ( isset( $file['error'] ) && $file['error'] ) {
		return false;
	}

	return true;
}

/**
 * Is the file size of the current avatar upload permitted?
 *
 * @since BuddyPress 1.0.0
 *
 * @param array $file The $_FILES array.
 * @return bool True if the avatar is under the size limit, otherwise false.
 */
function bp_core_check_avatar_size( $file ) {
	if ( $file['file']['size'] > bp_core_avatar_original_max_filesize() ) {
		return false;
	}

	return true;
}

/**
 * Get allowed avatar types.
 *
 * @since BuddyPress 2.3.0
 *
 * @return array
 */
function bp_core_get_allowed_avatar_types() {
	$allowed_types = bp_attachments_get_allowed_types( 'avatar' );

	/**
	 * Filters the list of allowed image types.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param array $allowed_types List of image types.
	 */
	$avatar_types = (array) apply_filters( 'bp_core_get_allowed_avatar_types', $allowed_types );

	if ( empty( $avatar_types ) ) {
		$avatar_types = $allowed_types;
	} else {
		$avatar_types = array_intersect( $allowed_types, $avatar_types );
	}

	return array_values( $avatar_types );
}

/**
 * Get allowed avatar mime types.
 *
 * @since BuddyPress 2.3.0
 *
 * @return array
 */
function bp_core_get_allowed_avatar_mimes() {
	$allowed_types = bp_core_get_allowed_avatar_types();

	return bp_attachments_get_allowed_mimes( 'avatar', $allowed_types );
}

/**
 * Does the current avatar upload have an allowed file type?
 *
 * Permitted file types are JPG, GIF and PNG.
 *
 * @since BuddyPress 1.0.0
 *
 * @param array $file The $_FILES array.
 * @return bool True if the file extension is permitted, otherwise false.
 */
function bp_core_check_avatar_type( $file ) {
	return bp_attachments_check_filetype( $file['file']['tmp_name'], $file['file']['name'], bp_core_get_allowed_avatar_mimes() );
}

/**
 * Fetch data from the BP root blog's upload directory.
 *
 * @since BuddyPress 1.8.0
 *
 * @param string $type The variable we want to return from the $bp->avatars object.
 *                     Only 'upload_path' and 'url' are supported. Default: 'upload_path'.
 * @return string The avatar upload directory path.
 */
function bp_core_get_upload_dir( $type = 'upload_path' ) {
	$bp = buddypress();

	switch ( $type ) {
		case 'upload_path':
			$constant = 'BP_AVATAR_UPLOAD_PATH';
			$key      = 'basedir';

			break;

		case 'url':
			$constant = 'BP_AVATAR_URL';
			$key      = 'baseurl';

			break;

		default:
			return false;

			break;
	}

	// See if the value has already been calculated and stashed in the $bp global.
	if ( isset( $bp->avatar->$type ) ) {
		$retval = $bp->avatar->$type;
	} else {
		if ( ! isset( $bp->avatar ) ) {
			$bp->avatar = new stdClass();
		}

		// If this value has been set in a constant, just use that.
		if ( defined( $constant ) ) {
			$retval = constant( $constant );
		} else {

			// Use cached upload dir data if available.
			if ( ! empty( $bp->avatar->upload_dir ) ) {
				$upload_dir = $bp->avatar->upload_dir;

				// No cache, so query for it.
			} else {

				// Get upload directory information from current site.
				$upload_dir = bp_upload_dir();

				// Stash upload directory data for later use.
				$bp->avatar->upload_dir = $upload_dir;
			}

			// Directory does not exist and cannot be created.
			if ( ! empty( $upload_dir['error'] ) ) {
				$retval = '';

			} else {
				$retval = $upload_dir[ $key ];

				// If $key is 'baseurl', check to see if we're on SSL
				// Workaround for WP13941, WP15928, WP19037.
				if ( $key == 'baseurl' && is_ssl() ) {
					$retval = str_replace( 'http://', 'https://', $retval );
				}
			}
		}

		// Stash in $bp for later use.
		$bp->avatar->$type = $retval;
	}

	return $retval;
}

/**
 * Get the absolute upload path for the WP installation.
 *
 * @since BuddyPress 1.2.0
 *
 * @return string Absolute path to WP upload directory.
 */
function bp_core_avatar_upload_path() {

	/**
	 * Filters the absolute upload path for the WP installation.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value Absolute upload path for the WP installation.
	 */
	return apply_filters( 'bp_core_avatar_upload_path', bp_core_get_upload_dir() );
}

/**
 * Get the raw base URL for root site upload location.
 *
 * @since BuddyPress 1.2.0
 *
 * @return string Full URL to current upload location.
 */
function bp_core_avatar_url() {

	/**
	 * Filters the raw base URL for root site upload location.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value Raw base URL for the root site upload location.
	 */
	return apply_filters( 'bp_core_avatar_url', bp_core_get_upload_dir( 'url' ) );
}

/**
 * Check if a given user ID has an uploaded avatar.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $user_id ID of the user whose avatar is being checked.
 * @return bool True if the user has uploaded a local avatar. Otherwise false.
 */
function bp_get_user_has_avatar( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	$retval = false;
	$avatar = bp_core_fetch_avatar(
		array(
			'item_id'   => $user_id,
			'item_type' => null,
			'no_grav'   => true,
			'html'      => false,
			'type'      => 'full',
		)
	);

	if ( false !== strpos( $avatar, '/' . $user_id . '/' ) ) {
		$retval = true;
	}

	// Check that the avatar has '/default/USER_ID/' path.
	if ( false !== strpos( $avatar, '/default/' . $user_id . '/' ) ) {
		$retval = false;
	}

	// Support WP User Avatar Plugin default avatar image.
	$avatar_option = bp_get_option( 'avatar_default', 'mystery' );
	if ( 'wp_user_avatar' === $avatar_option ) {
		if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'wp-user-avatar/wp-user-avatar.php' ) ) {
			$default_image_id = bp_get_option( 'avatar_default_wp_user_avatar', '' );
			if ( '' !== $default_image_id ) {
				$image_attributes = wp_get_attachment_image_src( (int) $default_image_id );
				if ( isset( $image_attributes[0] ) && '' !== $image_attributes[0] ) {

					$wp_user_avatar = apply_filters( 'bp_core_avatar_default_local_size', $image_attributes[0] );

					if ( $avatar != $avatar_option ) {
						$retval = true;
					}
				}
			}
		}
	}

	/**
	 * Filters whether or not a user has an uploaded avatar.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $retval  Whether or not a user has an uploaded avatar.
	 * @param int  $user_id ID of the user being checked.
	 */
	return (bool) apply_filters( 'bp_get_user_has_avatar', $retval, $user_id );
}

/**
 * Utility function for fetching an avatar dimension setting.
 *
 * @since BuddyPress 1.5.0
 *
 * @param string $type   Dimension type you're fetching dimensions for. 'thumb'
 *                       or 'full'. Default: 'thumb'.
 * @param string $h_or_w Which dimension is being fetched. 'height' or 'width'.
 *                       Default: 'height'.
 * @return int|bool $dim The dimension.
 */
function bp_core_avatar_dimension( $type = 'thumb', $h_or_w = 'height' ) {
	$bp  = buddypress();
	$dim = isset( $bp->avatar->{$type}->{$h_or_w} ) ? (int) $bp->avatar->{$type}->{$h_or_w} : false;

	/**
	 * Filters the avatar dimension setting.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int|bool $dim    Dimension setting for the type.
	 * @param string   $type   The type of avatar whose dimensions are requested. Default 'thumb'.
	 * @param string   $h_or_w The dimension parameter being requested. Default 'height'.
	 */
	return apply_filters( 'bp_core_avatar_dimension', $dim, $type, $h_or_w );
}

/**
 * Get the 'thumb' avatar width setting.
 *
 * @since BuddyPress 1.5.0
 *
 * @return int The 'thumb' width.
 */
function bp_core_avatar_thumb_width() {

	/**
	 * Filters the 'thumb' avatar width setting.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int $value Value for the 'thumb' avatar width setting.
	 */
	return apply_filters( 'bp_core_avatar_thumb_width', bp_core_avatar_dimension( 'thumb', 'width' ) );
}

/**
 * Get the 'thumb' avatar height setting.
 *
 * @since BuddyPress 1.5.0
 *
 * @return int The 'thumb' height.
 */
function bp_core_avatar_thumb_height() {

	/**
	 * Filters the 'thumb' avatar height setting.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int $value Value for the 'thumb' avatar height setting.
	 */
	return apply_filters( 'bp_core_avatar_thumb_height', bp_core_avatar_dimension( 'thumb', 'height' ) );
}

/**
 * Get the 'full' avatar width setting.
 *
 * @since BuddyPress 1.5.0
 *
 * @return int The 'full' width.
 */
function bp_core_avatar_full_width() {

	/**
	 * Filters the 'full' avatar width setting.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int $value Value for the 'full' avatar width setting.
	 */
	return apply_filters( 'bp_core_avatar_full_width', bp_core_avatar_dimension( 'full', 'width' ) );
}

/**
 * Get the 'full' avatar height setting.
 *
 * @since BuddyPress 1.5.0
 *
 * @return int The 'full' height.
 */
function bp_core_avatar_full_height() {

	/**
	 * Filters the 'full' avatar height setting.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int $value Value for the 'full' avatar height setting.
	 */
	return apply_filters( 'bp_core_avatar_full_height', bp_core_avatar_dimension( 'full', 'height' ) );
}

/**
 * Get the max width for original avatar uploads.
 *
 * @since BuddyPress 1.5.0
 *
 * @return int The max width for original avatar uploads.
 */
function bp_core_avatar_original_max_width() {

	/**
	 * Filters the max width for original avatar uploads.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int $value Value for the max width.
	 */
	return apply_filters( 'bp_core_avatar_original_max_width', (int) buddypress()->avatar->original_max_width );
}

/**
 * Get the max filesize for original avatar uploads.
 *
 * @since BuddyPress 1.5.0
 *
 * @return int The max filesize for original avatar uploads.
 */
function bp_core_avatar_original_max_filesize() {

	/**
	 * Filters the max filesize for original avatar uploads.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int $value Value for the max filesize.
	 */
	return apply_filters( 'bp_core_avatar_original_max_filesize', (int) buddypress()->avatar->original_max_filesize );
}

/**
 * Get the URL of the 'full' default avatar.
 *
 * @since BuddyPress 1.5.0
 * @since BuddyPress 2.6.0 Introduced `$params` and `$object_type` parameters.
 *
 * @param string $type   'local' if the fallback should be the locally-hosted version
 *                       of the mystery person, 'gravatar' if the fallback should be
 *                       Gravatar's version. Default: 'gravatar'.
 * @param array  $params Parameters passed to bp_core_fetch_avatar().
 * @return string The URL of the default avatar.
 */
function bp_core_avatar_default( $type = 'gravatar', $params = array() ) {
	// Local override.
	if ( defined( 'BP_AVATAR_DEFAULT' ) ) {
		$avatar = BP_AVATAR_DEFAULT;

		// Use the local default image.
	} elseif ( 'local' === $type ) {
		$size = '';
		if (
			( isset( $params['type'] ) && 'thumb' === $params['type'] && bp_core_avatar_thumb_width() <= 50 ) ||
			( isset( $params['width'] ) && $params['width'] <= 50 )
		) {

			$size = '-50';
		}

		// Support WP User Avatar Plugin default avatar image.
		$avatar        = '';
		$avatar_option = bp_get_option( 'avatar_default', 'mystery' );
		if ( 'wp_user_avatar' === $avatar_option ) {
			if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'wp-user-avatar/wp-user-avatar.php' ) ) {
				$default_image_id = bp_get_option( 'avatar_default_wp_user_avatar', '' );
				if ( '' !== $default_image_id ) {
					$image_attributes = wp_get_attachment_image_src( (int) $default_image_id );
					if ( isset( $image_attributes[0] ) && '' !== $image_attributes[0] ) {
						$avatar = apply_filters( 'bp_core_avatar_default_local_size', $image_attributes[0], $size );
					}
				}
			}
		}

		if ( empty( $avatar ) ) {
			$component = isset( $params['object'] ) ? $params['object'] : 'user';
			$avatar    = apply_filters( 'bp_core_avatar_default_local_size', bb_attachments_get_default_profile_group_avatar_image( $params ), $size );
		}

		// Use Gravatar's mystery person as fallback.
	} else {
		$size = '';
		if ( isset( $params['type'] ) && 'thumb' === $params['type'] ) {
			$size = bp_core_avatar_thumb_width();
		} else {
			$size = bp_core_avatar_full_width();
		}
		$avatar = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mm&amp;s=' . $size;
	}

	/**
	 * Filters the URL of the 'full' default avatar.
	 *
	 * @since BuddyPress 1.5.0
	 * @since BuddyPress 2.6.0 Added `$params`.
	 *
	 * @param string $avatar URL of the default avatar.
	 * @param array  $params Params provided to bp_core_fetch_avatar().
	 */
	return apply_filters( 'bp_core_avatar_default', $avatar, $params );
}

/**
 * Get the URL of the 'thumb' default avatar.
 *
 * Uses Gravatar's mystery-person avatar, unless BP_AVATAR_DEFAULT_THUMB has been
 * defined.
 *
 * @since BuddyPress 1.5.0
 * @since BuddyPress 2.6.0 Introduced `$object_type` parameter.
 *
 * @param string $type   'local' if the fallback should be the locally-hosted version
 *                       of the mystery person, 'gravatar' if the fallback should be
 *                       Gravatar's version. Default: 'gravatar'.
 * @param array  $params Parameters passed to bp_core_fetch_avatar().
 * @return string The URL of the default avatar thumb.
 */
function bp_core_avatar_default_thumb( $type = 'gravatar', $params = array() ) {
	// Local override.
	if ( defined( 'BP_AVATAR_DEFAULT_THUMB' ) ) {
		$avatar = BP_AVATAR_DEFAULT_THUMB;

		// Use the local default image.
	} elseif ( 'local' === $type ) {
		$avatar = apply_filters( 'bp_core_avatar_default_thumb_local', bb_attachments_get_default_profile_group_avatar_image( $params ) );

		// Use Gravatar's mystery person as fallback.
	} else {
		$avatar = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mm&amp;s=' . bp_core_avatar_thumb_width();
	}

	/**
	 * Filters the URL of the 'thumb' default avatar.
	 *
	 * @since BuddyPress 1.5.0
	 * @since BuddyPress 2.6.0 Added `$params`.
	 *
	 * @param string $avatar URL of the default avatar.
	 * @param string $params Params provided to bp_core_fetch_avatar().
	 */
	return apply_filters( 'bp_core_avatar_thumb', $avatar, $params );
}

/**
 * Reset the week parameter of the WordPress main query if needed.
 *
 * When cropping an avatar, a $_POST['w'] var is sent, setting the 'week'
 * parameter of the WordPress main query to this posted var. To avoid
 * notices, we need to make sure this 'week' query var is reset to 0.
 *
 * @since BuddyPress 2.2.0
 *
 * @param WP_Query|null $posts_query The main query object.
 */
function bp_core_avatar_reset_query( $posts_query = null ) {
	$reset_w = false;

	// Group's avatar edit screen.
	if ( bp_is_group_admin_page() ) {
		$reset_w = bp_is_group_admin_screen( 'group-avatar' );

		// Group's avatar create screen.
	} elseif ( bp_is_group_create() ) {
		/**
		 * We can't use bp_get_groups_current_create_step().
		 * as it's not set yet
		 */
		$reset_w = 'group-avatar' === bp_action_variable( 1 );

		// User's change avatar screen.
	} else {
		$reset_w = bp_is_user_change_avatar();
	}

	// A user or a group is cropping an avatar.
	if ( true === $reset_w && isset( $_POST['avatar-crop-submit'] ) ) {
		$posts_query->set( 'w', 0 );
	}
}
add_action( 'bp_parse_query', 'bp_core_avatar_reset_query', 10, 1 );

/**
 * Checks whether Avatar UI should be loaded.
 *
 * @since BuddyPress 2.3.0
 *
 * @return bool True if Avatar UI should load, false otherwise.
 */
function bp_avatar_is_front_edit() {
	$retval = false;

	// No need to carry on if the current WordPress version is not supported.
	if ( ! bp_attachments_is_wp_version_supported() ) {
		return $retval;
	}

	if ( bp_is_user_change_avatar() && 'crop-image' !== bp_get_avatar_admin_step() ) {
		$retval = ! bp_core_get_root_option( 'bp-disable-avatar-uploads' );
	}

	if ( bp_is_active( 'groups' ) ) {
		// Group creation.
		if ( bp_is_group_create() && bp_is_group_creation_step( 'group-avatar' ) && 'crop-image' !== bp_get_avatar_admin_step() ) {
			$retval = ! bp_disable_group_avatar_uploads();

			// Group Manage.
		} elseif ( bp_is_group_admin_page() && bp_is_group_admin_screen( 'group-avatar' ) && 'crop-image' !== bp_get_avatar_admin_step() ) {
			$retval = ! bp_disable_group_avatar_uploads();
		}
	}

	/**
	 * Use this filter if you need to :
	 * - Load the avatar UI for a component that is !groups or !user (return true regarding your conditions)
	 * - Completely disable the avatar UI introduced in 2.3 (eg: __return_false())
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param bool $retval Whether or not to load the Avatar UI.
	 */
	return apply_filters( 'bp_avatar_is_front_edit', $retval );
}

/**
 * Checks whether the Webcam Avatar UI part should be loaded.
 *
 * @since BuddyPress 2.3.0
 *
 * @global $is_safari
 * @global $is_IE
 *
 * @return bool True to load the Webcam Avatar UI part. False otherwise.
 */
function bp_avatar_use_webcam() {
	global $is_safari, $is_IE, $is_chrome;
	$browser	= bb_core_get_browser();
	$is_firefox	= isset( $browser['name'] ) ? 'Firefox' === $browser['b_name'] : false;

	/**
	 * Do not use the webcam feature for mobile devices
	 * to avoid possible confusions.
	 */
	if ( wp_is_mobile() ) {
		return false;
	}

	/**
	 * Bail when the browser does not support getUserMedia.
	 *
	 * @see http://caniuse.com/#feat=stream
	 */
	if ( ( $is_safari && isset( $browser['version'] ) && $browser['version'] < 11 ) || $is_IE || ( ( $is_chrome || $is_firefox ) && ! is_ssl() ) ) {
		return false;
	}

	/**
	 * Use this filter if you need to disable the webcam capture feature
	 * by returning false.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param bool $value Whether or not to load Webcam Avatar UI part.
	 */
	return apply_filters( 'bp_avatar_use_webcam', true );
}

/**
 * Template function to load the Avatar UI javascript templates.
 *
 * @since BuddyPress 2.3.0
 */
function bp_avatar_get_templates() {
	if ( ! bp_avatar_is_front_edit() ) {
		return;
	}

	bp_attachments_get_template_part( 'avatars/index' );
}

/**
 * Trick to check if the theme's BuddyPress templates are up to date.
 *
 * If the "avatar templates" are not including the new template tag, this will
 * help users to get the avatar UI.
 *
 * @since BuddyPress 2.3.0
 */
function bp_avatar_template_check() {
	if ( ! bp_avatar_is_front_edit() ) {
		return;
	}

	if ( ! did_action( 'bp_attachments_avatar_check_template' ) ) {
		bp_attachments_get_template_part( 'avatars/index' );
	}
}

/**
 * Inject uploaded profile photo as avatar into get_avatar()
 *
 * @param string|null $data      HTML for the user's avatar. Default null.
 * @param mixed       $id_or_email The avatar to retrieve. Accepts a user_id, Gravatar MD5 hash,
 *                                 user email, WP_User object, WP_Post object, or WP_Comment object.
 * @param array       $args        Arguments passed to get_avatar_url(), after processing.
 *
 * @return mixed|void
 *
 * @since BuddyBoss 1.6.4
 */
function bp_core_pre_get_avatar_filter( $data, $id_or_email, $args ) {

	if ( isset( $args['force_default'] ) && true === $args['force_default'] ) {
		return $data;
	}

	$user = false;

	// Ugh, hate duplicating code; process the user identifier.
	if ( is_numeric( $id_or_email ) ) {
		$user = get_user_by( 'id', absint( $id_or_email ) );
	} elseif ( $id_or_email instanceof WP_User ) {
		// User Object.
		$user = $id_or_email;
	} elseif ( $id_or_email instanceof WP_Post ) {
		// Post Object.
		$user = get_user_by( 'id', (int) $id_or_email->post_author );
	} elseif ( $id_or_email instanceof WP_Comment ) {
		if ( ! empty( $id_or_email->user_id ) ) {
			$user = get_user_by( 'id', (int) $id_or_email->user_id );
		}
	} elseif ( is_email( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
	}

	// No user, so bail.
	if ( false === $user instanceof WP_User ) {
		return $data;
	}

	// Set BuddyPress-specific avatar args.
	$args['item_id'] = $user->ID;
	$args['html']    = false;
	// Use the 'full' type if size is larger than BP's thumb width.
	if ( (int) $args['size'] > bp_core_avatar_thumb_width() ) {
		$args['type'] = 'full';
	}

	// Get the BuddyPress avatar URL.
	if ( $bp_avatar = bp_core_fetch_avatar( $args ) ) {
		$url = $bp_avatar;

		// Avatar classes.
		$class = array( 'avatar', 'avatar-' . (int) $args['size'], 'photo' );
		if ( ( isset( $args['found_avatar'] ) && ! $args['found_avatar'] ) || $args['force_default'] ) {
			$class[] = 'avatar-default';
		}
		if ( $args['class'] ) {
			if ( is_array( $args['class'] ) ) {
				$class = array_merge( $class, $args['class'] );
			} else {
				$class[] = $args['class'];
			}
		}

		// Add `loading` attribute.
		$extra_attr = isset( $args['extra_attr'] ) ? $args['extra_attr'] : '';
		$loading    = isset( $args['loading'] ) ? $args['loading'] : '';
		if ( in_array( $loading, array( 'lazy', 'eager' ), true ) && ! preg_match( '/\bloading\s*=/', $extra_attr ) ) {
			if ( ! empty( $extra_attr ) ) {
				$extra_attr .= ' ';
			}
			$extra_attr .= "loading='{$loading}'";
		}

		$data = sprintf(
			"<img alt='%s' src='%s' srcset='%s' class='%s' height='%d' width='%d' %s/>",
			esc_attr( $args['alt'] ),
			esc_url( $url ),
			esc_url( $url ) . ' 2x',
			esc_attr( join( ' ', $class ) ),
			(int) $args['height'],
			(int) $args['width'],
			$extra_attr
		);
	}

	return apply_filters( 'bp_core_pre_get_avatar_data_url_filter', $data, $id_or_email, $args );
}
add_filter( 'pre_get_avatar', 'bp_core_pre_get_avatar_filter', 10, 3 );

/**
 * Filter {@link get_avatar_url()} to use the BuddyPress user avatar URL.
 *
 * @since BuddyBoss 1.7.3
 *
 * @param  string $retval      The URL of the avatar.
 * @param  mixed  $id_or_email The Gravatar to retrieve. Accepts a user_id, gravatar md5 hash,
 *                             user email, WP_User object, WP_Post object, or WP_Comment object.
 * @param  array  $args        Arguments passed to get_avatar_data(), after processing.
 * @return string
 */
function bb_core_get_avatar_data_url_filter( $retval, $id_or_email, $args ) {
	$user = null;

	// Added this check for the display proper images in /wp-admin/options-discussion.php page Default Avatar page.
	global $pagenow;
	if ( 'options-discussion.php' === $pagenow ) {
		if ( true === $args['force_default'] && 'mm' === $args['default'] ) {
			return $retval;
		} elseif ( true === $args['force_default'] ) {
			return $retval;
		}
	}

	// Ugh, hate duplicating code; process the user identifier.
	if ( is_numeric( $id_or_email ) ) {
		$user = get_user_by( 'id', absint( $id_or_email ) );
	} elseif ( $id_or_email instanceof WP_User ) {
		// User Object
		$user = $id_or_email;
	} elseif ( $id_or_email instanceof WP_Post ) {
		// Post Object
		$user = get_user_by( 'id', (int) $id_or_email->post_author );
	} elseif ( $id_or_email instanceof WP_Comment ) {
		if ( ! empty( $id_or_email->user_id ) ) {
			$user = get_user_by( 'id', (int) $id_or_email->user_id );
		}
	} elseif ( is_email( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
	}

	// No user, so bail.
	if ( false === $user instanceof WP_User ) {
		return $retval;
	}

	// Set BuddyPress-specific avatar args.
	$args['item_id'] = $user->ID;
	$args['html']    = false;

	// Use the 'full' type if size is larger than BP's thumb width.
	if ( (int) $args['size'] > bp_core_avatar_thumb_width() ) {
		$args['type'] = 'full';
	}

	// Get the BuddyPress avatar URL.
	if ( $bp_avatar = bp_core_fetch_avatar( $args ) ) {
		return $bp_avatar;
	}

	return $retval;
}
add_filter( 'get_avatar_url', 'bb_core_get_avatar_data_url_filter', 10, 3 );
