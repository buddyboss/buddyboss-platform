<?php
/**
 * Media functions
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the media scripts
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_document_enqueue_scripts() {

	if ( bp_is_user_document() || bp_is_single_folder() || bp_is_document_directory() || bp_is_activity_component() || bp_is_group_activity() || bp_is_group_document() || bp_is_group_folders() || bp_is_messages_component() ) {
		if ( bp_is_profile_document_support_enabled() || bp_is_group_document_support_enabled() || bp_is_messages_document_support_enabled() ) {
			wp_enqueue_script( 'bp-media-dropzone' );
			wp_enqueue_script( 'bp-nouveau-codemirror' );
			wp_enqueue_script( 'bp-nouveau-codemirror-css' );
			wp_enqueue_script( 'bp-nouveau-media' );
			wp_enqueue_script( 'bp-exif' );
		}
	}
}

/**
 * Localize the strings needed for the messages UI
 *
 * @since BuddyPress 3.0.0
 *
 * @param  array $params Associative array containing the JS Strings needed by scripts.
 * @return array         The same array with specific strings for the messages UI if needed.
 */
function bp_nouveau_document_localize_scripts( $params = array() ) {

	$extensions     = array();
	$all_extensions = bp_document_extensions_list();
	foreach ( $all_extensions as $extension ) {
		if ( isset( $extension['is_active'] ) && true === (bool) $extension['is_active'] ) {
			$extensions[] = $extension['extension'];
		}
	}

	$folder_id        = 0;
	$type             = '';
	$user_id          = bp_loggedin_user_id();
	$group_id         = 0;
	$move_to_id_popup = $user_id;
	if ( bp_is_group_document() || bp_is_group_folders() ) {
		$folder_id        = (int) bp_action_variable( 1 );
		$type             = 'group';
		$group_id         = bp_get_current_group_id();
		$move_to_id_popup = $group_id;
	} elseif ( bp_is_user_document() || bp_is_user_folders() ) {
		$folder_id        = (int) bp_action_variable( 0 );
		$type             = 'profile';
		$move_to_id_popup = $user_id;
	} elseif ( bp_is_document_directory() ) {
		$folder_id = 0;
		$type      = 'profile';
	}
	$document_params = array(
		'max_upload_size_document'  => bp_document_file_upload_max_size( false, 'MB' ),
		'profile_document'          => bp_is_profile_document_support_enabled(),
		'group_document'            => bp_is_group_document_support_enabled(),
		'messages_document'         => bp_is_messages_document_support_enabled(),
		'document_type'             => implode( ',', $extensions ),
		'empty_document_type'       => __( 'Empty documents will not be uploaded.', 'buddyboss' ),
		'current_folder'            => $folder_id,
		'current_type'              => $type,
		'move_to_id_popup'          => $move_to_id_popup,
		'current_user_id'           => $user_id,
		'current_group_id'          => $group_id,
		'target_text'               => __( 'Documents', 'buddyboss' ),
		'create_folder_error_title' => __( 'Please enter title of folder', 'buddyboss' ),
		'document_select_error'     => __( 'This file not supporting in document', 'buddyboss' ),
	);

	$old_media = $params['media'];

	$params['media'] = array_merge( $old_media, $document_params );

	if ( bp_is_single_folder() ) {
		$params['media']['album_id'] = (int) bp_action_variable( 0 );
	}

	if ( bp_is_group_single() && bp_is_group_folders() ) {
		$params['media']['album_id'] = (int) bp_action_variable( 1 );
	}

	$document_i18n_strings = array(
		'folder_delete_confirm'   => __( 'Are you sure you want to delete this folder? Documents in this folder will also be deleted?', 'buddyboss' ),
		'document_delete_confirm' => __( 'Are you sure you want to delete this document?', 'buddyboss' ),
		'folder_delete_error'     => __( 'There was a problem deleting the folder.', 'buddyboss' ),
		'folder_move_error'       => __( 'Please select destination folder.', 'buddyboss' ),
	);

	$old_i18n_strings = $params['media']['i18n_strings'];

	$params['media']['i18n_strings'] = array_merge( $old_i18n_strings, $document_i18n_strings );

	return $params;
}

/**
 * Get the nav items for the Media directory
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array An associative array of nav items.
 */
function bp_nouveau_get_document_directory_nav_items() {
	$nav_items = array();

	$nav_items['all'] = array(
		'component' => 'document',
		'slug'      => 'all', // slug is used because BP_Core_Nav requires it, but it's the scope.
		'li_class'  => array(),
		'link'      => bp_get_document_directory_permalink(),
		'text'      => __( 'All Documents', 'buddyboss' ),
		//'count'     => bp_get_total_document_count(),
		'position'  => 5,
	);

	if ( is_user_logged_in() ) {
		$nav_items['personal'] = array(
			'component' => 'document',
			'slug'      => 'personal', // slug is used because BP_Core_Nav requires it, but it's the scope.
			'li_class'  => array(),
			'link'      => bp_loggedin_user_domain() . bp_get_document_slug() . '/my-document/',
			'text'      => __( 'My Documents', 'buddyboss' ),
			//'count'     => bp_document_get_total_document_count(),
			'position'  => 15,
		);
	}

	/**
	 * Use this filter to introduce your custom nav items for the media directory.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $nav_items The list of the media directory nav items.
	 */
	return apply_filters( 'bp_nouveau_get_document_directory_nav_items', $nav_items );
}

/**
 * Function get document support extension.
 *
 * @param string $format
 *
 * @return array|mixed|string|void
 */
function bp_media_allowed_document_type() {

	$extension_lists = array(
		array(
			'name'        => 'csv',
			'extension'   => '.csv',
			'mime_type'   => 'text/csv',
			'description' => 'CSV',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'css',
			'extension'   => '.css',
			'mime_type'   => 'text/css',
			'description' => 'CSS',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'css',
			'extension'   => '.css',
			'mime_type'   => 'text/plain',
			'description' => 'CSS',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'doc',
			'extension'   => '.doc',
			'mime_type'   => 'application/msword',
			'description' => 'Word Document',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'docm',
			'extension'   => '.docm',
			'mime_type'   => 'application/vnd.ms-word.document.macroenabled.12',
			'description' => 'Word Document',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'docm',
			'extension'   => '.docm',
			'mime_type'   => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'description' => 'Word Document',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'docx',
			'extension'   => '.docx',
			'mime_type'   => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'description' => 'Word Document',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'dotx',
			'extension'   => '.dotx',
			'mime_type'   => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'description' => 'Word Template',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'dotm',
			'extension'   => '.dotm',
			'mime_type'   => 'application/vnd.ms-word.template.macroenabled.12',
			'description' => 'Word Template',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'gzip',
			'extension'   => '.gzip',
			'mime_type'   => 'application/gzip',
			'description' => 'Gzip Archive',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'htm',
			'extension'   => '.htm',
			'mime_type'   => 'text/html',
			'description' => 'HTML',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'html',
			'extension'   => '.html',
			'mime_type'   => 'text/html',
			'description' => 'HTML',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'ics',
			'extension'   => '.ics',
			'mime_type'   => 'text/calendar',
			'description' => 'iCalendar',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'ico',
			'extension'   => '.ico',
			'mime_type'   => 'image/x-icon',
			'description' => 'ICO',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'jar',
			'extension'   => '.jar',
			'mime_type'   => 'application/java-archive',
			'description' => 'JAR Archive',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'js',
			'extension'   => '.js',
			'mime_type'   => 'application/javascript',
			'description' => 'JavaScript',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'mp3',
			'extension'   => '.mp3',
			'mime_type'   => 'audio/mpeg',
			'description' => 'MP3',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'ods',
			'extension'   => '.ods',
			'mime_type'   => 'application/vnd.oasis.opendocument.spreadsheet',
			'description' => 'OpenDocument Spreadsheet',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'odt',
			'extension'   => '.odt',
			'mime_type'   => 'application/vnd.oasis.opendocument.text',
			'description' => 'OpenDocument Text',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'pdf',
			'extension'   => '.pdf',
			'mime_type'   => 'application/pdf',
			'description' => 'PDF',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'psd',
			'extension'   => '.psd',
			'mime_type'   => 'image/vnd.adobe.photoshop',
			'description' => 'Photoshop Document',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'ppt',
			'extension'   => '.ppt',
			'mime_type'   => 'application/vnd.ms-powerpoint',
			'description' => 'PowerPoint Presentation',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'pptx',
			'extension'   => '.pptx',
			'mime_type'   => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'description' => 'PowerPoint Presentation',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'ppsx',
			'extension'   => '.ppsx',
			'mime_type'   => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'description' => 'PowerPoint Slideshow',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'ppsx',
			'extension'   => '.ppsx',
			'mime_type'   => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'description' => 'PowerPoint Slideshow',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'pptm',
			'extension'   => '.pptm',
			'mime_type'   => 'application/vnd.ms-powerpoint.presentation.macroenabled.12',
			'description' => 'PowerPoint Presentation',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'pptm',
			'extension'   => '.pptm',
			'mime_type'   => 'application/octet-stream',
			'description' => 'PowerPoint Presentation',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'potx',
			'extension'   => '.potx',
			'mime_type'   => 'application/vnd.openxmlformats-officedocument.presentationml.template',
			'description' => 'PowerPoint Template',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'potm',
			'extension'   => '.potm',
			'mime_type'   => 'application/vnd.ms-powerpoint.template.macroenabled.12',
			'description' => 'PowerPoint Template',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'rar',
			'extension'   => '.rar',
			'mime_type'   => 'application/x-rar-compressed',
			'description' => 'RAR Archive',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'rtf',
			'extension'   => '.rtf',
			'mime_type'   => 'application/rtf',
			'description' => 'Rich Text Format',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'tar',
			'extension'   => '.tar',
			'mime_type'   => 'application/x-tar',
			'description' => 'Tar File (Tape Archive)',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'txt',
			'extension'   => '.txt',
			'mime_type'   => 'text/plain',
			'description' => 'Text File',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'xls',
			'extension'   => '.xls',
			'mime_type'   => 'application/vnd.ms-excel',
			'description' => 'Excel Spreadsheet',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'wav',
			'extension'   => '.wav',
			'mime_type'   => 'audio/x-wav',
			'description' => 'Waveform Audio',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'xlsx',
			'extension'   => '.xlsx',
			'mime_type'   => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'description' => 'Excel Spreadsheet',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'xlsm',
			'extension'   => '.xlsm',
			'mime_type'   => 'application/vnd.ms-excel.sheet.macroenabled.12',
			'description' => 'Excel Spreadsheet',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'xltx',
			'extension'   => '.xltx',
			'mime_type'   => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'description' => 'Excel Template',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'xltm',
			'extension'   => '.xltm',
			'mime_type'   => 'application/vnd.ms-excel.template.macroenabled.12',
			'description' => 'Excel Template',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => '7z',
			'extension'   => '.7z',
			'mime_type'   => 'application/x-7z-compressed',
			'description' => 'Zip',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'abw',
			'extension'   => '.abw',
			'mime_type'   => 'application/x-abiword',
			'description' => 'AbiWord',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'ace',
			'extension'   => '.ace',
			'mime_type'   => 'application/x-ace-compressed',
			'description' => 'Ace Archive',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'acc',
			'extension'   => '.acc',
			'mime_type'   => 'application/vnd.americandynamics.acc',
			'description' => 'Active Content Compression',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'acu',
			'extension'   => '.acu',
			'mime_type'   => 'application/vnd.acucobol',
			'description' => 'ACU Cobol',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'atc',
			'extension'   => '.atc',
			'mime_type'   => 'application/vnd.acucorp',
			'description' => 'ACU Cobol',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'adp',
			'extension'   => '.adp',
			'mime_type'   => 'audio/adpcm',
			'description' => 'Adaptive differential pulse-code modulation',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'zip',
			'extension'   => '.zip',
			'mime_type'   => 'application/zip',
			'description' => 'Zip',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'zir',
			'extension'   => '.zir',
			'mime_type'   => 'application/vnd.zul',
			'description' => 'ZIR',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'zaz',
			'extension'   => '.zaz',
			'mime_type'   => 'application/vnd.zzazz.deck+xml',
			'description' => 'ZAZ',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'zaz',
			'extension'   => '.zaz',
			'mime_type'   => 'application/vnd.zzazz.deck+xml',
			'description' => 'ZAZ',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'yaml',
			'extension'   => '.yaml',
			'mime_type'   => 'text/yaml',
			'description' => 'YAML',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'xml',
			'extension'   => '.xml',
			'mime_type'   => 'application/rss+xml',
			'description' => 'XML',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'rss',
			'extension'   => '.rss',
			'mime_type'   => 'application/rss+xml',
			'description' => 'RSS',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'xlsb',
			'extension'   => '.xlsb',
			'mime_type'   => 'application/vnd.ms-excel.sheet.binary.macroenabled.12',
			'description' => 'Excel Spreadsheet (Binary, Macro Enabled)',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'xlam',
			'extension'   => '.xlam',
			'mime_type'   => 'application/vnd.ms-excel.sheet.binary.macroenabled.12',
			'description' => 'Excel Spreadsheet (Binary, Macro Enabled)',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'vcf',
			'extension'   => '.vcf',
			'mime_type'   => 'text/x-vcard',
			'description' => 'vCard',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'svg',
			'extension'   => '.svg',
			'mime_type'   => 'image/svg+xml',
			'description' => 'SVG',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'ai',
			'extension'   => '.ai',
			'mime_type'   => 'application/postscript',
			'description' => 'Illustrator File',
			'is_default'  => 1,
			'is_active'   => 1,
		),
		array(
			'name'        => 'apk',
			'extension'   => '.apk',
			'mime_type'   => 'application/vnd.android.package-archive',
			'description' => 'Android Package',
			'is_default'  => 1,
			'is_active'   => 1,
		),
	);

	$extension_lists = apply_filters( 'bp_media_allowed_document_type', $extension_lists );

	return $extension_lists;
}

function bp_document_download_file( $attachment_id, $type = 'document' ) {

	if ( 'document' === $type ) {

		$the_file = wp_get_attachment_url( $attachment_id );

		if ( ! $the_file ) {
			return;
		}

		// clean the file url.
		$file_url = stripslashes( trim( $the_file ) );

		// get filename.
		$file_name = basename( $the_file );

		// get file extension.
		$file_extension = pathinfo( $file_name );

		// security check.
		$file_name_lower = strtolower( $file_url );

		// Get all allowed document extensions.
		$all_extensions                   = bp_document_extensions_list();
		$allowed_for_download             = array();
		$allowed_file_type_with_mime_type = array();
		foreach ( $all_extensions as $extension ) {
			if ( true === (bool) $extension['is_active'] ) {
				$extension_name                                      = ltrim( $extension['extension'], '.' );
				$allowed_for_download[]                              = $extension_name;
				$allowed_file_type_with_mime_type[ $extension_name ] = $extension['mime_type'];
			}
		}

		$whitelist = apply_filters( 'bp_document_download_file_allowed_file_types', $allowed_for_download );

		$file_arr = explode( '.', $file_name_lower );
		$needle   = end( $file_arr );
		if ( ! in_array( $needle, $whitelist ) ) {
			exit( 'Invalid file!' );
		}

		$file_new_name = $file_name;
		$content_type  = isset( $allowed_file_type_with_mime_type[ $file_extension['extension'] ] ) ? $allowed_file_type_with_mime_type[ $file_extension['extension'] ] : '';
		$content_type  = apply_filters( 'bp_document_download_file_content_type', $content_type, $file_extension['extension'] );

		header( 'Expires: 0' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Cache-Control: pre-check=0, post-check=0, max-age=0', false );
		header( 'Pragma: no-cache' );
		header( "Content-type: {$content_type}" );
		header( "Content-Disposition:attachment; filename={$file_new_name}" );
		header( 'Content-Type: application/force-download' );

		readfile( "{$file_url}" );
		exit();
	} else {

		// Get folder object.
		$folder = folders_get_folder( $attachment_id );

		// Get Upload directory.
		$upload     = wp_upload_dir();
		$upload_dir = $upload['basedir'];

		// Create temp folder.
		$upload_dir = $upload_dir . '/' . $folder->user_id . '-download-folder-' . time();

		// If folder not exists then create.
		if ( ! is_dir( $upload_dir ) ) {

			// Create temp folder.
			wp_mkdir_p( $upload_dir );
			chmod( $upload_dir, 0777 );

			// Create given main parent folder.
			$parent_folder = $upload_dir . '/' . $folder->title;
			wp_mkdir_p( $parent_folder );

			// Fetch all the attachments of the parent folder.
			$get_parent_attachments = bp_document_get_folder_attachemt_ids( $folder->id );
			if ( ! empty( $get_parent_attachments ) ) {
				foreach ( $get_parent_attachments as $attachment ) {
					$the_file  = get_attached_file( $attachment->attachment_id );
					$file_name = basename( $the_file );
					copy( $the_file, $parent_folder . '/' . $file_name );
				}
			}
			bp_document_get_child_folders( $attachment_id, $parent_folder );

			$zip_name  = $upload_dir . '/' . $folder->title . '.zip';
			$file_name = $folder->title . '.zip';
			$rootPath  = realpath( "$upload_dir" );

			$zip = new ZipArchive();
			$zip->open( $zip_name, ZipArchive::CREATE | ZipArchive::OVERWRITE );

			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $rootPath ), RecursiveIteratorIterator::LEAVES_ONLY );

			foreach ( $files as $name => $file ) {
				$filePath     = $file->getRealPath();
				$relativePath = substr( $filePath, strlen( $rootPath ) + 1 );

				if ( ! $file->isDir() ) {
					$zip->addFile( $filePath, $relativePath );
				} else {
					if ( $relativePath !== false ) {
						$zip->addEmptyDir( $relativePath );
					}
				}
			}

			$zip->close();

			header( 'Expires: 0' );
			header( 'Cache-Control: no-cache, no-store, must-revalidate' );
			header( 'Cache-Control: pre-check=0, post-check=0, max-age=0', false );
			header( 'Pragma: no-cache' );
			header( 'Content-type: application/zip' );
			header( "Content-Disposition:attachment; filename={$file_name}" );
			header( 'Content-Type: application/force-download' );

			readfile( "{$zip_name}" );

			BP_Document::bp_document_remove_temp_directory( $upload_dir );
			exit();

		}
	}

}

function bp_document_get_child_folders( $folder_id = 0, $parent_folder = '' ) {

	global $wpdb, $bp;

	$document_folder_table = $bp->table_prefix . 'bp_media_albums';

	if ( 0 === $folder_id ) {
		return;
	}

	$documents_folder_query = $wpdb->prepare( "SELECT * FROM {$document_folder_table} WHERE FIND_IN_SET(id,(SELECT GROUP_CONCAT(lv SEPARATOR ',') FROM ( SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',') FROM {$document_folder_table} WHERE parent IN (@pv)) AS lv FROM {$document_folder_table} JOIN (SELECT @pv:=%d)tmp WHERE parent IN (@pv)) a))", $folder_id );

	$data = $wpdb->get_results( $documents_folder_query, ARRAY_A ); // db call ok; no-cache ok;

	// Build array of item references.
	foreach ( $data as $key => &$item ) {
		$itemsByReference[ $item['id'] ] = &$item;
		// Children array:
		$itemsByReference[ $item['id'] ]['children'] = array();
		// Empty data class (so that json_encode adds "data: {}" )
		$itemsByReference[ $item['id'] ]['data'] = new StdClass();
	}

	// Set items as children of the relevant parent item.
	foreach ( $data as $key => &$item ) {
		if ( $item['parent'] && isset( $itemsByReference[ $item['parent'] ] ) ) {
			$itemsByReference [ $item['parent'] ]['children'][] = &$item;
		}
	}

	// Remove items that were added to parents elsewhere.
	foreach ( $data as $key => &$item ) {
		if ( $item['parent'] && isset( $itemsByReference[ $item['parent'] ] ) ) {
			unset( $data[ $key ] );
		}
	}

	bp_document_create_folder_recursive( $data, $parent_folder );

}

/**
 * This function will give the breadcrumbs ul li html.
 *
 * @param      $array
 * @param bool  $first
 *
 * @return string
 * @since BuddyBoss 1.3.0
 */
function bp_document_create_folder_recursive( $array, $parent_folder ) {

	// Base case: an empty array produces no list.
	if ( empty( $array ) || empty( $parent_folder ) ) {
		return '';
	}

	foreach ( $array as $item ) {

		$id    = $item['id'];
		$title = $item['title'];
		$child = $item['children'];

		// Create given main parent folder.
		$sub_parent_folder = $parent_folder . '/' . $title;
		wp_mkdir_p( $sub_parent_folder );

		// Fetch all the attachments of the parent folder.
		$get_current_attachments = bp_document_get_folder_attachemt_ids( $id );
		if ( ! empty( $get_current_attachments ) ) {
			foreach ( $get_current_attachments as $attachment ) {
				$the_file  = get_attached_file( $attachment->attachment_id );
				$file_name = basename( $the_file );
				copy( $the_file, $sub_parent_folder . '/' . $file_name );
			}
		}
		if ( ! empty( $child ) ) {
			bp_document_create_folder_recursive( $child, $sub_parent_folder );
		}
	}
}

function bp_document_preview_extension_list() {
	$extension_arr = apply_filters(
		'bp_document_preview_extension_list',
		array(
			'xlsm',
			'potx',
			'pps',
			'docm',
			'dotx',
			'doc',
			'docx',
			'xls',
			'xlsx',
			'xlr',
			'wps',
			'wpd',
			'rtf',
			'pptx',
			'ppt',
			'pps',
			'odt',
		)
	);

	return $extension_arr;
}

function bp_document_get_preview_text_from_attachment( $attachment_id ) {

	$file_open = fopen( get_attached_file( $attachment_id ), 'r' );
	$file_data = fread( $file_open, 10000 );
	$more_text = false;
	if ( strlen( $file_data ) >= 9999 ) {
		$file_data .= '...';
		$more_text  = true;
	}
	fclose( $file_open );

	$data              = array();
	$data['text']      = $file_data;
	$data['more_text'] = $more_text;

	return apply_filters( 'bp_document_get_preview_text_from_attachment', $data, $attachment_id );
}
