/**
 * BuddyBoss Admin Settings 2.0 - MIME Checker Panel Component
 *
 * Shared component for the MIME type checker panel used in extension modals.
 * Renders the file upload UI, "Get MIME Type" button, and detected result
 * with a "Use this MIME type" action.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * MIME Checker Panel Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props              Component props.
 * @param {Object}   props.mimeChecker  The useMimeChecker() hook return value.
 * @param {Function} props.onUseMimeType Callback when "Use this MIME type" is clicked.
 * @returns {JSX.Element} MIME checker panel.
 */
export function MimeCheckerPanel( { mimeChecker, onUseMimeType } ) {
	return (
		<div className="bb-extension-modal__mime-checker">
			<h4 className="bb-extension-modal__mime-checker-title">
				{ __( 'Check MIME type', 'buddyboss-platform' ) }
			</h4>
			<p className="bb-extension-modal__mime-checker-desc">
				{ __( 'Upload a sample file and click \'Get MIME Type\' to view its MIME type.', 'buddyboss-platform' ) }
			</p>
			<div className="bb-extension-modal__mime-checker-upload-row">
				<input
					type="file"
					ref={ mimeChecker.fileInputRef }
					className="bb-extension-modal__mime-checker-file-hidden"
					onChange={ mimeChecker.handleFileSelect }
				/>
				<button
					type="button"
					className="bb-extension-modal__mime-checker-upload-btn"
					onClick={ function() {
						if ( mimeChecker.fileInputRef.current ) {
							mimeChecker.fileInputRef.current.click();
						}
					} }
				>
					<i className="bb-icons-rl bb-icons-rl-upload" />
					{ __( 'Upload File', 'buddyboss-platform' ) }
				</button>
				<span className="bb-extension-modal__mime-checker-upload-name">
					{ mimeChecker.selectedFileName || __( 'No file uploaded', 'buddyboss-platform' ) }
				</span>
			</div>
			<Button
				variant="primary"
				onClick={ mimeChecker.handleGetMimeType }
				disabled={ mimeChecker.isMimeChecking }
				className="bb-extension-modal__mime-checker-btn"
			>
				{ mimeChecker.isMimeChecking ? __( 'Checking...', 'buddyboss-platform' ) : __( 'Get MIME Type', 'buddyboss-platform' ) }
			</Button>
			{ mimeChecker.mimeCheckerError && (
				<p className="bb-extension-modal__mime-checker-error">
					{ mimeChecker.mimeCheckerError }
				</p>
			) }
			{ mimeChecker.mimeCheckerResult && (
				<div className="bb-extension-modal__mime-checker-result">
					<span className="bb-extension-modal__mime-checker-result-label">
						{ __( 'Detected MIME type:', 'buddyboss-platform' ) }
					</span>
					<code className="bb-extension-modal__mime-checker-result-value">
						{ mimeChecker.mimeCheckerResult }
					</code>
					<Button
						variant="primary"
						onClick={ onUseMimeType }
						className="bb-extension-modal__mime-checker-use-btn"
					>
						{ __( 'Use this MIME type', 'buddyboss-platform' ) }
					</Button>
				</div>
			) }
		</div>
	);
}
