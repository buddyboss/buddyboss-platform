<?php
/**
 * Admin-only cover-image upload + user-crop pipeline for Settings 2.0.
 *
 * Mirrors the avatar two-step pattern (upload → crop-set) so the React
 * `<CoverCropModal>` can let admins choose the visible region of the default
 * profile/group cover before the file gets fit to the feature dimensions.
 *
 * Why this lives in its own file (and adds NEW actions instead of modifying
 * `bp_cover_image_upload`):
 *
 *   - The existing `wp_ajax_bp_cover_image_upload` action does upload + auto-fit
 *     in a single round trip. Frontend cover uploads (member's own cover, group
 *     cover edit screen) rely on that single-step shape. Adding a `crop_pending`
 *     flag in there would risk breaking those paths, so we keep the public
 *     pipeline untouched.
 *
 *   - Avatar already follows the two-step pattern (`bp_avatar_upload` →
 *     `bp_avatar_set`); covers now do the same on the admin path.
 *
 * Two new actions:
 *
 *   - `wp_ajax_bb_admin_cover_image_upload_temp`
 *       → Saves the uploaded file at the default cover dir
 *         (`/uploads/buddyboss/{members,groups}/0/cover-image/tmp-<rand>.{ext}`)
 *         and returns the URL + image dimensions. SKIPS the auto-fit so the
 *         React modal can show the user the original image to crop against.
 *
 *   - `wp_ajax_bb_admin_cover_image_set`
 *       → Accepts the temp URL + crop coords. Loads the temp file via
 *         `wp_get_image_editor`, applies the user crop, runs the existing
 *         `bp_attachments_cover_image_generate_file()` pipeline for the final
 *         feature-dimension fit, fires the `*_cover_image_uploaded` action
 *         (so `bb_save_profile_group_cover_options_on_upload_custom_cover`
 *         stores the URL into `bp-default-custom-{profile,group}-cover` exactly
 *         as the legacy single-step flow does), removes any leftover temp
 *         files, and returns the final URL.
 *
 * Both handlers gate on admin context, manage_options cap, and a dedicated
 * nonce (`bb_admin_cover_cropstore`).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Allowed objects for the admin cover-crop pipeline. Mirrors
 * `bb_validate_custom_profile_group_avatar_ajax_reuqest()` in scope —
 * default profile cover is `user`, default group cover is `group`.
 *
 * @since BuddyBoss 3.0.0
 */
const BB_ADMIN_COVER_CROP_ALLOWED_OBJECTS = array( 'user', 'group' );

/**
 * Resolve the (basedir, baseurl, subdir) tuple for the default-cover
 * upload destination, given an `object`. Returns null when the object is not
 * one we manage.
 *
 * The subdir mirrors `bb_default_custom_profile_group_cover_image_upload_dir()`
 * in `bp-core/bp-core-filters.php` — same admin-default location the legacy
 * single-step upload writes to, so anything reading the option after the fact
 * sees the same path layout.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $object Either 'user' or 'group'.
 * @return ?array{basedir:string, baseurl:string, subdir:string, dir:string, url:string}
 */
function bb_admin_cover_crop_resolve_dir( $object ) {
	if ( ! in_array( $object, BB_ADMIN_COVER_CROP_ALLOWED_OBJECTS, true ) ) {
		return null;
	}

	$bp_uploads = bp_attachments_cover_image_upload_dir();
	if ( empty( $bp_uploads['basedir'] ) || empty( $bp_uploads['baseurl'] ) ) {
		return null;
	}

	$subdir = ( 'group' === $object )
		? '/groups/0/cover-image'
		: '/members/0/cover-image';

	$dir = $bp_uploads['basedir'] . $subdir;
	$url = $bp_uploads['baseurl'] . $subdir;

	if ( ! wp_mkdir_p( $dir ) ) {
		return null;
	}

	return array(
		'basedir' => $bp_uploads['basedir'],
		'baseurl' => $bp_uploads['baseurl'],
		'subdir'  => $subdir,
		'dir'     => $dir,
		'url'     => $url,
	);
}

/**
 * Common request validation for both phase-1 (temp upload) and phase-2
 * (crop set). Returns sanitized params on success, sends a JSON error and
 * exits on failure.
 *
 * Failure modes that produce a 4xx-style error response:
 *   - missing nonce / bad nonce
 *   - non-admin context
 *   - missing or unauthorized capability
 *   - object outside allowed list
 *
 * @since BuddyBoss 3.0.0
 *
 * @return array{object:string} Sanitized params.
 */
function bb_admin_cover_crop_validate_request() {
	if ( ! is_admin() ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'buddyboss-platform' ) ), 403 );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'buddyboss-platform' ) ), 403 );
	}

	check_ajax_referer( 'bb_admin_cover_cropstore', 'nonce' );

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
	$object = isset( $_POST['object'] ) ? sanitize_key( wp_unslash( $_POST['object'] ) ) : '';
	if ( ! in_array( $object, BB_ADMIN_COVER_CROP_ALLOWED_OBJECTS, true ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid object.', 'buddyboss-platform' ) ), 400 );
	}

	return array(
		'object' => $object,
	);
}

/**
 * Phase 1: receive an uploaded file and stage it at the admin-default cover
 * dir without applying the auto-fit. The React modal needs the user's
 * original image (at full resolution) so the crop box has room to move; the
 * existing single-step `bp_cover_image_upload` would auto-fit before returning,
 * leaving zero pixels outside the cover band for the user to recompose against.
 *
 * Returns the staged URL plus image dimensions so the modal can compute the
 * canvas-to-image scale factor.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return void Always exits via wp_send_json_*.
 */
function bb_admin_cover_image_upload_temp_ajax() {
	$validated = bb_admin_cover_crop_validate_request();
	$object    = $validated['object'];

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified inside bb_admin_cover_crop_validate_request().
	if ( empty( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) ) {
		wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'buddyboss-platform' ) ), 400 );
	}

	$paths = bb_admin_cover_crop_resolve_dir( $object );
	if ( null === $paths ) {
		wp_send_json_error( array( 'message' => __( 'Could not resolve upload directory.', 'buddyboss-platform' ) ), 500 );
	}

	// Build allowed mime types (image/jpeg + image/png only — matches the
	// React-side accept attribute and the upload validator).
	$mimes = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
	);

	// Validate the file via wp_check_filetype_and_ext (byte-level check, not
	// just the extension). Mirrors the byte-level MIME check that admin
	// forum image uploads use — extension-only checks let renamed payloads
	// through.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified inside bb_admin_cover_crop_validate_request(); each individual $file member ($file['name'], $file['tmp_name']) is sanitized on the lines below before use.
	$file      = $_FILES['file'];
	$file_name = isset( $file['name'] ) ? sanitize_file_name( wp_unslash( $file['name'] ) ) : '';
	$tmp_name  = isset( $file['tmp_name'] ) ? sanitize_text_field( wp_unslash( $file['tmp_name'] ) ) : '';
	if ( '' === $tmp_name || '' === $file_name ) {
		wp_send_json_error( array( 'message' => __( 'Upload error.', 'buddyboss-platform' ) ), 400 );
	}

	$filetype = wp_check_filetype_and_ext( $tmp_name, $file_name, $mimes );
	if ( empty( $filetype['type'] ) || ! in_array( $filetype['type'], array( 'image/jpeg', 'image/png' ), true ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Invalid file type. Only JPEG and PNG images are allowed.', 'buddyboss-platform' ) ),
			400
		);
	}

	// Move the upload into our staged dir under a `tmp-<rand>` name so it
	// doesn't collide with an already-saved cover, and so phase-2 can find
	// and clean it up unambiguously by prefix. We bypass `wp_handle_upload`'s
	// upload_dir filter chain by calling `move_uploaded_file` directly — it's
	// already validated above.
	$ext = pathinfo( $file_name, PATHINFO_EXTENSION );
	if ( ! empty( $filetype['ext'] ) ) {
		$ext = $filetype['ext'];
	}
	$tmp_basename = 'tmp-' . wp_generate_password( 12, false ) . '.' . strtolower( $ext );
	$tmp_path     = $paths['dir'] . '/' . $tmp_basename;

	// Drop @ on move_uploaded_file — the false return is checked and a
	// genuine error (open_basedir, perms, disk-full) should bubble through
	// WP's error log. `@` would mask that diagnostic.
	if ( ! move_uploaded_file( $tmp_name, $tmp_path ) ) { // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found -- move_uploaded_file() is the secure way to handle an HTTP upload (verifies POST origin); wp_handle_upload is not applicable to this cropped-tmp flow.
		wp_send_json_error( array( 'message' => __( 'Could not save the uploaded file.', 'buddyboss-platform' ) ), 500 );
	}

	// Set sensible permissions matching what `BP_Attachment::upload` produces.
	// Drop @: a chmod failure on a writable upload dir is genuinely worth
	// logging — if it surfaces, the site has a permission problem worth fixing.
	chmod( $tmp_path, 0644 );

	// Keep @ on getimagesize — it intentionally emits a warning on bad input
	// and the boolean false return is the documented "not an image" signal.
	$dims = @getimagesize( $tmp_path );
	if ( false === $dims ) {
		// Drop @ on unlink: if cleanup fails the site has a filesystem
		// issue that should surface in the error log, not silently leak
		// tmp-* files into the cover dir.
		wp_delete_file( $tmp_path );
		wp_send_json_error( array( 'message' => __( 'Uploaded file is not a valid image.', 'buddyboss-platform' ) ), 400 );
	}

	// Return `original_name` so phase 2 can pass it to the
	// `*_cover_image_uploaded` action as the `$name` arg — third-party
	// listeners that log the uploaded filename otherwise see only the random
	// `tmp-XXXX` temp basename and have no way to correlate it to what the
	// admin uploaded. Strip the extension to match the shape `pathinfo
	// PATHINFO_FILENAME` returns for the legacy single-step flow.
	$original_name = pathinfo( $file_name, PATHINFO_FILENAME );

	wp_send_json_success(
		array(
			'url'           => esc_url_raw( $paths['url'] . '/' . $tmp_basename ),
			'basename'      => $tmp_basename,
			'width'         => (int) $dims[0],
			'height'        => (int) $dims[1],
			'original_name' => $original_name,
		)
	);
}
add_action( 'wp_ajax_bb_admin_cover_image_upload_temp', 'bb_admin_cover_image_upload_temp_ajax' );

/**
 * Phase 2: apply user crop coords to the previously-staged temp file, then
 * pipe the cropped result through the existing `bp_attachments_cover_image_generate_file`
 * helper for the final feature-dimension fit. This keeps the on-disk shape of
 * the cover identical to what the legacy single-step flow produces — same
 * filename pattern, same dir, same `xprofile_cover_image_uploaded` /
 * `groups_cover_image_uploaded` action signature — so every downstream hook
 * (option storage in `bb_save_profile_group_cover_options_on_upload_custom_cover`,
 * any third-party listener) continues to fire unchanged.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return void Always exits via wp_send_json_*.
 */
function bb_admin_cover_image_set_ajax() {
	$validated = bb_admin_cover_crop_validate_request();
	$object    = $validated['object'];

	$paths = bb_admin_cover_crop_resolve_dir( $object );
	if ( null === $paths ) {
		wp_send_json_error( array( 'message' => __( 'Could not resolve upload directory.', 'buddyboss-platform' ) ), 500 );
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified inside bb_admin_cover_crop_validate_request(); crop_x/y/w/h are coerced via (int), other strings via sanitize_file_name(). PHPCS doesn't recognise (int) cast as "sanitization" for its purposes.
	$basename      = isset( $_POST['basename'] ) ? sanitize_file_name( wp_unslash( $_POST['basename'] ) ) : '';
	$crop_x        = isset( $_POST['crop_x'] ) ? (int) wp_unslash( $_POST['crop_x'] ) : 0;
	$crop_y        = isset( $_POST['crop_y'] ) ? (int) wp_unslash( $_POST['crop_y'] ) : 0;
	$crop_w        = isset( $_POST['crop_w'] ) ? (int) wp_unslash( $_POST['crop_w'] ) : 0;
	$crop_h        = isset( $_POST['crop_h'] ) ? (int) wp_unslash( $_POST['crop_h'] ) : 0;
	$original_name = isset( $_POST['original_name'] ) ? sanitize_file_name( wp_unslash( $_POST['original_name'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	// Reject obviously bogus crop rectangles. `crop_w`/`crop_h` must be
	// positive, and the basename must look like our `tmp-XX...` naming so a
	// caller can't aim the editor at an arbitrary file in the cover-image dir.
	if ( $crop_w <= 0 || $crop_h <= 0 || $crop_x < 0 || $crop_y < 0 ) {
		wp_send_json_error( array( 'message' => __( 'Invalid crop coordinates.', 'buddyboss-platform' ) ), 400 );
	}
	if ( '' === $basename || 0 !== strpos( $basename, 'tmp-' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid temp file reference.', 'buddyboss-platform' ) ), 400 );
	}

	$tmp_path = $paths['dir'] . '/' . $basename;

	// `realpath` resolves any traversal (`..`) attempt; check the result is
	// inside the resolved cover-image dir before opening. The `trailingslashit`
	// on the dir prevents a prefix-collision edge case where `/foo/cover-image`
	// would `strpos`-match `/foo/cover-image-evil/tmp-x.jpg`.
	$real_tmp = realpath( $tmp_path );
	$real_dir = realpath( $paths['dir'] );
	if ( false === $real_tmp || false === $real_dir || 0 !== strpos( $real_tmp, trailingslashit( $real_dir ) ) ) {
		wp_send_json_error( array( 'message' => __( 'Temp file not found.', 'buddyboss-platform' ) ), 400 );
	}

	if ( ! file_exists( $real_tmp ) ) {
		wp_send_json_error( array( 'message' => __( 'Temp file not found.', 'buddyboss-platform' ) ), 400 );
	}

	// Upper-bound check: reject crop coords that fall outside the source
	// image. Without this, GD's `imagecrop` silently produces a 1×1 result
	// for out-of-bounds crops and the handler proceeds to fit/save a broken
	// cover. We already have the source dimensions cached at the top of
	// phase 1, but a re-`getimagesize` here is cheap and avoids passing
	// dimensions through the round-trip.
	$src_dims = @getimagesize( $real_tmp ); // phpcs:ignore Generic.PHP.DiscourageGoto -- @ here matches WP convention for getimagesize's documented false-on-failure signal.
	if ( false === $src_dims ) {
		wp_send_json_error( array( 'message' => __( 'Could not read source image dimensions.', 'buddyboss-platform' ) ), 500 );
	}
	$src_w = (int) $src_dims[0];
	$src_h = (int) $src_dims[1];
	if ( $crop_x + $crop_w > $src_w || $crop_y + $crop_h > $src_h ) {
		wp_send_json_error( array( 'message' => __( 'Crop region falls outside the source image.', 'buddyboss-platform' ) ), 400 );
	}

	// Apply the user's crop. We work on a NEW file under the same dir so the
	// subsequent `bp_attachments_cover_image_generate_file()` step produces
	// the canonical filename via `BP_Attachment_Cover_Image::generate_filename()`,
	// and so the temp file remains intact for cleanup at the end.
	$editor = wp_get_image_editor( $real_tmp );
	if ( is_wp_error( $editor ) ) {
		wp_send_json_error(
			array(
				'message' => sprintf(
					/* translators: %s: image editor error message. */
					__( 'Could not open the uploaded image: %s', 'buddyboss-platform' ),
					$editor->get_error_message()
				),
			),
			500
		);
	}

	$cropped = $editor->crop( $crop_x, $crop_y, $crop_w, $crop_h );
	if ( is_wp_error( $cropped ) ) {
		wp_send_json_error(
			array(
				'message' => sprintf(
					/* translators: %s: image editor error message. */
					__( 'Could not crop the image: %s', 'buddyboss-platform' ),
					$cropped->get_error_message()
				),
			),
			500
		);
	}

	// Save the cropped image to a separate file so generate_file's path-
	// containment check (file must live under cover_image_dir) passes and
	// the original temp file can be deleted afterwards.
	$cropped_basename = 'cropped-' . wp_generate_password( 12, false ) . '.jpg';
	$cropped_path     = $paths['dir'] . '/' . $cropped_basename;

	$saved = $editor->save( $cropped_path, 'image/jpeg' );
	if ( is_wp_error( $saved ) ) {
		wp_send_json_error(
			array(
				'message' => sprintf(
					/* translators: %s: image editor error message. */
					__( 'Could not save the cropped image: %s', 'buddyboss-platform' ),
					$saved->get_error_message()
				),
			),
			500
		);
	}

	// User → 'xprofile' (action prefix `xprofile_cover_image_uploaded`),
	// group → 'groups'. Matches the component dispatch in
	// `bp_attachments_cover_image_ajax_upload()`. Note: the action prefix is
	// `xprofile_` but the on-disk attachment dir for users is `members/0/
	// cover-image/` (see `bp_attachments_get_attachment` call below).
	$component = ( 'group' === $object ) ? 'groups' : 'xprofile';

	// Run the existing fit/finalize step. This:
	// - resizes the cropped image to feature dimensions (1950×450 default),
	// - writes the final file under the canonical name produced by
	// `BP_Attachment_Cover_Image::generate_filename()`,
	// - removes any sibling files in the cover-image dir EXCEPT the new
	// cover and our temp/cropped (we delete those explicitly below).
	$cover = bp_attachments_cover_image_generate_file(
		array(
			'file'            => $cropped_path,
			'component'       => $component,
			'cover_image_dir' => $paths['dir'],
		)
	);

	if ( ! $cover || empty( $cover['cover_basename'] ) ) {
		// Clean up our scratch files before bailing. Drop @ so cleanup
		// failures surface in WP's error log — a stuck file handle is worth
		// knowing about.
		if ( file_exists( $real_tmp ) ) {
			wp_delete_file( $real_tmp );
		}
		if ( file_exists( $cropped_path ) ) {
			wp_delete_file( $cropped_path );
		}

		$message = __( 'Could not generate the cover image.', 'buddyboss-platform' );
		if ( ! bb_is_gd_or_imagick_library_enabled() ) {
			$message = __( 'Image editor is missing. Please enable GD or Imagick.', 'buddyboss-platform' );
		}

		wp_send_json_error( array( 'message' => $message ), 500 );
	}

	// Best-effort cleanup of our scratch files. `generate_file` already
	// removes leftover siblings, but our two scratch files share the dir
	// and we want them gone whether or not generate_file scrubbed them.
	if ( file_exists( $real_tmp ) ) {
		wp_delete_file( $real_tmp );
	}
	if ( file_exists( $cropped_path ) && $cover['cover_basename'] !== $cropped_basename ) {
		wp_delete_file( $cropped_path );
	}

	$cover_url     = $paths['url'] . '/' . $cover['cover_basename'];
	$feedback_code = empty( $cover['is_too_small'] ) ? 1 : 0;

	// Pass the admin-supplied original filename to the action so third-party
	// listeners that log "user uploaded <name>" get the real name, not the
	// random `tmp-XXXX` prefix. Falls back to the temp basename's stem only
	// if phase 1 didn't supply it (older client / hand-crafted POST).
	$name = '' !== $original_name ? $original_name : pathinfo( $basename, PATHINFO_FILENAME );

	// Fire the same action the single-step flow fires so existing listeners
	// (`bb_save_profile_group_cover_options_on_upload_custom_cover` writes the
	// URL into `bp-default-custom-{profile,group}-cover` keyed off this hook)
	// continue to work without duplication.
	//
	// Contract for `$_POST['bp_params']` while the action runs:
	// ['object'    => 'user'|'group',
	// 'item_id'   => 0,
	// 'item_type' => 'default']
	// Some listeners (including
	// `bb_save_profile_group_cover_options_on_upload_custom_cover` in
	// bp-core-filters.php) read `$_POST['bp_params']['object']` to decide
	// between the user vs group option keys. We mirror the same shape the
	// single-step uploader sends so they pick the right branch — and we
	// admit `bb_admin_cover_image_set` to the validation gate's allowlist
	// in bp-core-filters.php so the listener accepts our action.
	//
	// We restore `$_POST['bp_params']` inside a try/finally so a listener
	// that throws or calls wp_die()/exit() can't leave the request with a
	// mutated $_POST for whatever runs after. The finally still runs on
	// normal return; the `exit()` case is handled by the runtime unwinding
	// through finally before the script terminates.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Nonce verified upstream; we're capturing the prior value verbatim to restore it in `finally` below — sanitization is the caller's responsibility, not ours.
	$prior_bp_params = isset( $_POST['bp_params'] ) ? $_POST['bp_params'] : null;
	// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Nonce verified upstream; we're shaping known-good input (object is sanitize_key'd, item_id is literal int, item_type is literal string).
	$_POST['bp_params'] = array(
		'object'    => $object,
		'item_id'   => 0,
		'item_type' => 'default',
	);

	try {
		do_action(
			$component . '_cover_image_uploaded',
			0,
			$name,
			$cover_url,
			$feedback_code
		);
	} finally {
		// Always restore — even if a listener threw. (`exit()` from a listener
		// still unwinds through `finally` before terminating.) The legacy
		// listener that writes the `bp-default-custom-{profile,group}-cover`
		// option fires from `do_action()` above with the gate now admitting
		// `bb_admin_cover_image_set`, so we no longer need a defense-in-depth
		// direct `bp_update_option` write here — that would double-fire any
		// downstream `update_option_*` hooks (audit log, cache bust,
		// telemetry, offload-CDN sync).
		if ( null === $prior_bp_params ) {
			unset( $_POST['bp_params'] );
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Restoring the original $_POST['bp_params'] value verbatim.
			$_POST['bp_params'] = $prior_bp_params;
		}
	}

	// Give 3rd-party plugins (e.g. cloud-storage offload) a chance to compute
	// the canonical URL for item_id=0 / default. Mirrors the same hook the
	// legacy single-step flow exposes.
	$return_url = bp_attachments_get_attachment(
		'url',
		array(
			'object_dir' => ( 'group' === $object ) ? 'groups' : 'members',
			'item_id'    => 0,
		)
	);
	if ( '' === $return_url ) {
		$return_url = $cover_url;
	}

	wp_send_json_success(
		array(
			'url'           => esc_url_raw( $return_url ),
			'feedback_code' => $feedback_code,
		)
	);
}
add_action( 'wp_ajax_bb_admin_cover_image_set', 'bb_admin_cover_image_set_ajax' );
