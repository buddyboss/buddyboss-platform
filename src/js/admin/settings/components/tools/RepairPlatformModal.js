/**
 * Repair Platform progress + completion modal.
 *
 * Two variants per Figma `repair-platform-modal.png`:
 *  - `progress`  — wrench icon + "Repairing Community…" label + spinner + Cancel button.
 *  - `complete`  — green checkmark + "Repair community complete" + per-item result list + Done button.
 *
 * @since BuddyBoss [BBVERSION]
 */
import { Modal, Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Convert legacy AJAX HTML message → clean plain text.
 *
 * Legacy `bp_admin_repair_tools_wrapper_function` returns messages that
 * contain HTML entities (`&hellip;`) and inline markup (`<a href=...>...</a>`).
 * Use the browser's parser to decode entities, then strip tags so the result
 * renders cleanly inside the React result list without unsafe HTML injection.
 */
function decodeMessage( html ) {
	if ( ! html ) {
		return '';
	}
	const doc = new DOMParser().parseFromString( String( html ), 'text/html' );
	return ( doc.body.textContent || '' ).replace( /\s+/g, ' ' ).trim();
}

/**
 * Build the per-item result line for the Repair Platform completion modal.
 *
 * Legacy `bp_admin_repair_*` responses vary widely:
 *   - "Counting friends… Complete! 42 friends counted."  (count in summary)
 *   - "Migrated 17 groups… successfully."                  (count inline)
 *   - "Installing missing emails… Complete! View Emails." (no count)
 *
 * Figma renders short summaries like "20 users visibility data migrated
 * successfully". To produce a useful line in every case, drop the verbose
 * "Verbing things…" lead-in only when a meaningful summary remains; otherwise
 * fall back to the operation label so the row is never just a stray link.
 */
function formatResultText( item ) {
	const label = item.label || '';
	const hasCount = item.count !== null && item.count !== undefined && '' !== item.count;
	const summary  = ( item.summary || '' ).trim();

	// Server already extracted count + summary via bb_admin_repair_extract_count_summary().
	// Combine them with the operation label so the line reads naturally — e.g.
	// "{count} — Repair member last activity data" or "42 friends counted." when
	// the summary already carries enough context.
	if ( hasCount && summary ) {
		// If the summary already includes the count (e.g. "42 friends counted."),
		// trust it and render as-is. Otherwise prepend the count to give context.
		const summaryHasCount = String( summary ).indexOf( String( item.count ) ) !== -1;
		return summaryHasCount ? summary : item.count + ' — ' + summary;
	}
	if ( hasCount ) {
		return item.count + ' — ' + label;
	}
	if ( summary ) {
		return summary;
	}

	// True fallback: no server enrichment (e.g. network error, abort).
	const status = item.success ? __( 'completed successfully', 'buddyboss' ) : __( 'failed', 'buddyboss' );
	return label ? label + ' — ' + status : ( decodeMessage( item.message ) || status );
}

/**
 * Detect partial-success messages like "23/26 message threads updated successfully".
 *
 * Legacy repair handlers return `success: true` for any completed operation
 * even if some items were skipped — the partial info is encoded in the message
 * text. Match an "N/M" fraction at message start and treat N < M as a warning.
 */
function classifyResult( item ) {
	if ( ! item.success ) {
		return 'error';
	}
	const text = decodeMessage( item.message );
	const m = text.match( /\b(\d+)\s*\/\s*(\d+)\b/ );
	if ( m ) {
		const done = parseInt( m[ 1 ], 10 );
		const total = parseInt( m[ 2 ], 10 );
		if ( 0 === done && total > 0 ) {
			return 'error';
		}
		if ( done < total ) {
			return 'warning';
		}
	}
	if ( /^\s*0\b/.test( text ) ) {
		return 'error';
	}
	return 'success';
}

export default function RepairPlatformModal( { variant, results, onCancel, onClose } ) {
	if ( 'progress' === variant ) {
		return (
			<Modal
				title={ __( 'Repair Community', 'buddyboss' ) }
				onRequestClose={ onCancel }
				className="bb-tools-repair-modal is-progress"
			>
				<div className="bb-tools-repair-modal__body">
					<span className="bb-tools-repair-modal__icon bb-icons-rl bb-icons-rl-wrench"></span>
					<p>{ __( 'Repairing Community…', 'buddyboss' ) }</p>
					<Spinner />
				</div>
				<div className="bb-tools-repair-modal__actions">
					<Button variant="secondary" onClick={ onCancel }>
						{ __( 'Cancel', 'buddyboss' ) }
					</Button>
				</div>
			</Modal>
		);
	}

	return (
		<Modal
			title={ __( 'Repair Community', 'buddyboss' ) }
			onRequestClose={ onClose }
			className="bb-tools-repair-modal is-complete"
		>
			<div className="bb-tools-repair-modal__body">
				<div className="bb-tools-repair-modal__checkmark">
					<span className="bb-icons-rl bb-icons-rl-check-circle"></span>
				</div>
				<h3>{ __( 'Repair community complete', 'buddyboss' ) }</h3>
				<ul className="bb-tools-repair-modal__results">
					{ ( results || [] ).map( function ( item ) {
						const state = classifyResult( item );
						const iconClass = 'success' === state
							? 'bb-icons-rl bb-icons-rl-check-circle'
							: 'warning' === state
							? 'bb-icons-rl bb-icons-rl-warning'
							: 'bb-icons-rl bb-icons-rl-warning';
						return (
							<li
								key={ item.id }
								className={ 'is-' + state }
							>
								<span className={ iconClass }></span>
								<span className="bb-tools-repair-modal__result-text">
									{ formatResultText( item ) }
								</span>
							</li>
						);
					} ) }
				</ul>
			</div>
			<div className="bb-tools-repair-modal__actions">
				<Button variant="primary" onClick={ onClose }>
					{ __( 'Ok', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}
