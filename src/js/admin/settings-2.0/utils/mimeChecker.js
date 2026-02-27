/**
 * BuddyBoss Admin Settings 2.0 - MIME Checker Utility
 *
 * Shared hook for checking file MIME types via the server-side
 * wp_ajax_bp_document_check_file_mime_type AJAX handler.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef } from '@wordpress/element';

/**
 * Custom hook for MIME type checking via AJAX.
 *
 * Manages the file input ref, loading state, result state, and provides
 * handlers for triggering the check, using the result, and closing the panel.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @returns {Object} MIME checker state and handlers.
 */
export function useMimeChecker() {
	var fileInputRef = useRef( null );
	var [ isMimeCheckerOpen, setIsMimeCheckerOpen ] = useState( false );
	var [ mimeCheckerResult, setMimeCheckerResult ] = useState( '' );
	var [ isMimeChecking, setIsMimeChecking ] = useState( false );
	var [ selectedFileName, setSelectedFileName ] = useState( '' );

	/**
	 * Handle file selection and store the selected file name.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event} event The file input change event.
	 */
	var handleFileSelect = function( event ) {
		if ( event.target.files && event.target.files[0] ) {
			setSelectedFileName( event.target.files[0].name );
		} else {
			setSelectedFileName( '' );
		}
	};

	/**
	 * Upload a file to detect its MIME type via the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleGetMimeType = function() {
		if ( ! fileInputRef.current || ! fileInputRef.current.files || ! fileInputRef.current.files[0] ) {
			return;
		}

		var ajaxUrl = window.bbAdminData?.ajaxUrl || '/wp-admin/admin-ajax.php';
		var formData = new FormData();
		formData.append( 'file', fileInputRef.current.files[0] );
		formData.append( 'action', 'bp_document_check_file_mime_type' );

		setIsMimeChecking( true );
		setMimeCheckerResult( '' );

		fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( result ) {
				if ( result.success && result.data && result.data.type ) {
					setMimeCheckerResult( result.data.type );
				}
				setIsMimeChecking( false );
			} )
			.catch( function() {
				setIsMimeChecking( false );
			} );
	};

	/**
	 * Close the MIME checker panel and reset its state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCloseMimeChecker = function() {
		setMimeCheckerResult( '' );
		setIsMimeCheckerOpen( false );
		setIsMimeChecking( false );
		setSelectedFileName( '' );
		if ( fileInputRef.current ) {
			fileInputRef.current.value = '';
		}
	};

	/**
	 * Reset only the file input and result (keeps panel open/closed state).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var resetMimeState = function() {
		setMimeCheckerResult( '' );
		setIsMimeCheckerOpen( false );
		setIsMimeChecking( false );
		setSelectedFileName( '' );
		if ( fileInputRef.current ) {
			fileInputRef.current.value = '';
		}
	};

	return {
		fileInputRef: fileInputRef,
		isMimeCheckerOpen: isMimeCheckerOpen,
		setIsMimeCheckerOpen: setIsMimeCheckerOpen,
		mimeCheckerResult: mimeCheckerResult,
		setMimeCheckerResult: setMimeCheckerResult,
		isMimeChecking: isMimeChecking,
		selectedFileName: selectedFileName,
		handleFileSelect: handleFileSelect,
		handleGetMimeType: handleGetMimeType,
		handleCloseMimeChecker: handleCloseMimeChecker,
		resetMimeState: resetMimeState,
	};
}
