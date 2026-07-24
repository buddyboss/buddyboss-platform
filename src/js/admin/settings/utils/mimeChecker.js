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
import { __ } from '@wordpress/i18n';

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
	var abortRef = useRef( null );
	var [ isMimeCheckerOpen, setIsMimeCheckerOpen ] = useState( false );
	var [ mimeCheckerResult, setMimeCheckerResult ] = useState( '' );
	var [ isMimeChecking, setIsMimeChecking ] = useState( false );
	var [ selectedFileName, setSelectedFileName ] = useState( '' );
	var [ mimeCheckerError, setMimeCheckerError ] = useState( '' );

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
		setMimeCheckerError( '' );
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

		// Abort any previous in-flight request.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}

		var controller = new AbortController();
		abortRef.current = controller;

		var ajaxUrl = window.bbAdminData?.ajaxUrl || '/wp-admin/admin-ajax.php';
		var formData = new FormData();
		formData.append( 'file', fileInputRef.current.files[0] );
		formData.append( 'action', 'bp_document_check_file_mime_type' );
		formData.append( 'nonce', window.bbAdminData.ajaxNonce );

		setIsMimeChecking( true );
		setMimeCheckerResult( '' );
		setMimeCheckerError( '' );

		fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
			signal: controller.signal,
		} )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( result ) {
				if ( result.success && result.data && result.data.type ) {
					setMimeCheckerResult( result.data.type );
				} else {
					setMimeCheckerError( __( 'Could not detect MIME type.', 'buddyboss-platform' ) );
				}
				setIsMimeChecking( false );
			} )
			.catch( function( err ) {
				// Ignore abort errors.
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				setIsMimeChecking( false );
				setMimeCheckerError( __( 'Failed to detect MIME type. Please try again.', 'buddyboss-platform' ) );
			} );
	};

	/**
	 * Close the MIME checker panel and reset its state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCloseMimeChecker = function() {
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		setMimeCheckerResult( '' );
		setIsMimeCheckerOpen( false );
		setIsMimeChecking( false );
		setSelectedFileName( '' );
		setMimeCheckerError( '' );
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
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		setMimeCheckerResult( '' );
		setIsMimeCheckerOpen( false );
		setIsMimeChecking( false );
		setSelectedFileName( '' );
		setMimeCheckerError( '' );
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
		mimeCheckerError: mimeCheckerError,
		handleFileSelect: handleFileSelect,
		handleGetMimeType: handleGetMimeType,
		handleCloseMimeChecker: handleCloseMimeChecker,
		resetMimeState: resetMimeState,
	};
}
