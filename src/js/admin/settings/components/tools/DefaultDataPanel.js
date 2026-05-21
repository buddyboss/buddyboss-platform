/**
 * BuddyBoss Admin Settings 2.0 - Default Data Panel
 *
 * Bulk-insert demo content into the site (members, profile fields,
 * groups, activity, messages, forums). Wraps the legacy
 * `bp_admin_tools_default_data_save()` flow via the
 * `bb_admin_tools_default_data_run` AJAX endpoint — the backend
 * importer functions (`bp_dd_import_*` / `bp_dd_clear_db`) are
 * unchanged.
 *
 * Each checkbox below mirrors one `$_POST['bp']` key the legacy form
 * sent. Categories already imported on this site are reported by
 * `bb_admin_tools_get_panel_data` and rendered disabled to match the
 * legacy admin UX (importing the same category twice is a no-op).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, CheckboxControl, Spinner } from '@wordpress/components';
import { Toast, useAutoDismissToast } from '../Toast';
import { ConfirmToggleModal } from '../modals/ConfirmToggleModal';

/**
 * Field group definitions. Each entry mirrors the matching legacy
 * checkbox + the `bp_dd_is_imported( $category, $subtype )` pair
 * `BB_Admin_Tools_Ajax::get_panel_data()` reports.
 *
 * `requiresComponent` gates the row visibility against
 * `panelData.default_data.components.<id>` — same conditional rendering
 * the legacy form did via `bp_is_active()` server-side.
 */
var DEFAULT_DATA_GROUPS = [
	{
		id:    'members',
		label: __( 'Members', 'buddyboss' ),
		items: [
			{ id: 'import-profile',  label: __( 'Profile Fields (with data)', 'buddyboss' ), requiresComponent: 'xprofile' },
			{ id: 'import-friends',  label: __( 'Connections', 'buddyboss' ),                requiresComponent: 'friends' },
			{ id: 'import-activity', label: __( 'Activity Posts', 'buddyboss' ),             requiresComponent: 'activity' },
			{ id: 'import-messages', label: __( 'Private Messages', 'buddyboss' ),           requiresComponent: 'messages' },
		],
	},
	{
		id:                'groups',
		label:             __( 'Groups', 'buddyboss' ),
		requiresComponent: 'groups',
		items: [
			{ id: 'import-g-members',  label: __( 'Members', 'buddyboss' ) },
			{ id: 'import-g-activity', label: __( 'Activity Posts', 'buddyboss' ),             requiresComponent: 'activity' },
			{ id: 'import-g-forums',   label: __( 'Forums in Groups (with data)', 'buddyboss' ), requiresComponent: 'forums' },
		],
	},
	{
		id:                'forums',
		label:             __( 'Forums', 'buddyboss' ),
		requiresComponent: 'forums',
		items: [
			{ id: 'import-f-topics',  label: __( 'Discussions', 'buddyboss' ) },
			{ id: 'import-f-replies', label: __( 'Replies', 'buddyboss' ) },
		],
	},
];

/**
 * Default Data Panel
 *
 * @returns {JSX.Element}
 */
export function DefaultDataPanel() {
	var [ loading, setLoading ]       = useState( true );
	var [ loadError, setLoadError ]   = useState( '' );
	var [ panelData, setPanelData ]   = useState( null );
	var [ selected, setSelected ]     = useState( {} );
	var [ submitting, setSubmitting ] = useState( false );
	var [ clearing, setClearing ]     = useState( false );

	var [ toast, setToast ] = useState( null );
	useAutoDismissToast( toast, setToast );

	// Confirmation modal state. Both Import and Clear show a WP <Modal>
	// confirmation step before triggering the destructive AJAX call —
	// matches the existing Settings 2.0 modal pattern (delete-confirm,
	// migration wizard) instead of the browser's native `confirm()`
	// dialog. `pendingAction` is 'import' | 'clear' | null.
	var [ pendingAction, setPendingAction ] = useState( null );

	var ajaxUrl = ( window.bbAdminData && window.bbAdminData.ajaxUrl ) || window.ajaxurl || '/wp-admin/admin-ajax.php';
	var nonce   = ( window.bbAdminData && window.bbAdminData.toolsNonce ) || '';

	var fetchPanelData = useCallback( function () {
		setLoading( true );
		setLoadError( '' );

		var formData = new FormData();
		formData.append( 'action', 'bb_admin_tools_get_panel_data' );
		formData.append( 'nonce', nonce );

		fetch( ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			body:        formData,
		} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( res ) {
				if ( res && res.success && res.data ) {
					setPanelData( res.data );
				} else {
					setLoadError(
						( res && res.data && res.data.message )
							|| __( 'Unable to load Default Data status.', 'buddyboss' )
					);
				}
				setLoading( false );
			} )
			.catch( function () {
				setLoadError( __( 'Unable to load Default Data status.', 'buddyboss' ) );
				setLoading( false );
			} );
	}, [ ajaxUrl, nonce ] );

	useEffect( function () {
		fetchPanelData();
	}, [ fetchPanelData ] );

	/**
	 * Build the visible-row list once panel data lands. Hides rows whose
	 * required component is inactive — matches the legacy form which
	 * omitted those checkboxes entirely.
	 *
	 * @returns {Array} Groups with `items` already filtered.
	 */
	var visibleGroups = useMemo( function () {
		if ( ! panelData ) {
			return [];
		}
		var components = ( panelData.default_data && panelData.default_data.components ) || {};
		return DEFAULT_DATA_GROUPS
			.filter( function ( group ) {
				return ! group.requiresComponent || components[ group.requiresComponent ];
			} )
			.map( function ( group ) {
				return Object.assign( {}, group, {
					items: group.items.filter( function ( item ) {
						return ! item.requiresComponent || components[ item.requiresComponent ];
					} ),
				} );
			} )
			.filter( function ( group ) { return group.items.length > 0; } );
	}, [ panelData ] );

	var importedMap = useMemo( function () {
		return ( panelData && panelData.default_data && panelData.default_data.imported ) || {};
	}, [ panelData ] );

	var toggleItem = useCallback( function ( itemId, checked ) {
		setSelected( function ( prev ) {
			var next = Object.assign( {}, prev );
			if ( checked ) {
				next[ itemId ] = true;
			} else {
				delete next[ itemId ];
			}
			return next;
		} );
	}, [] );

	var selectedIds = useMemo( function () {
		return Object.keys( selected ).filter( function ( id ) { return ! importedMap[ id ]; } );
	}, [ selected, importedMap ] );

	// Button is enabled even when nothing is selected (matches the
	// legacy form's `<input type="submit">` which had no disabled
	// state). Always opens the confirmation modal — the legacy form's
	// jQuery handler fired `confirm()` unconditionally on click without
	// any client-side validation; if the admin OKs an empty import, the
	// server-side AJAX returns a clear "No data categories were
	// selected" error that surfaces via the same toast as any other
	// import-time error.
	var handleImportClick = useCallback( function () {
		if ( submitting || clearing ) {
			return;
		}
		setPendingAction( 'import' );
	}, [ submitting, clearing ] );

	var handleClearClick = useCallback( function () {
		if ( submitting || clearing ) {
			return;
		}
		setPendingAction( 'clear' );
	}, [ submitting, clearing ] );

	var closeConfirm = useCallback( function () {
		setPendingAction( null );
	}, [] );

	var runImport = useCallback( function () {
		if ( selectedIds.length === 0 ) {
			setPendingAction( null );
			return;
		}
		setPendingAction( null );
		setSubmitting( true );

		var formData = new FormData();
		formData.append( 'action', 'bb_admin_tools_default_data_run' );
		formData.append( 'nonce', nonce );
		selectedIds.forEach( function ( id ) {
			formData.append( 'types[]', id );
		} );

		fetch( ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			body:        formData,
		} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( res ) {
				if ( res && res.success ) {
					setToast( {
						status:  'success',
						message: ( res.data && res.data.message ) || __( 'Selected data imported.', 'buddyboss' ),
					} );
					setSelected( {} );
					fetchPanelData();
				} else {
					setToast( {
						status:  'error',
						message: ( res && res.data && res.data.message ) || __( 'Import failed.', 'buddyboss' ),
					} );
				}
				setSubmitting( false );
			} )
			.catch( function () {
				setToast( {
					status:  'error',
					message: __( 'Import failed. Please try again.', 'buddyboss' ),
				} );
				setSubmitting( false );
			} );
	}, [ selectedIds, ajaxUrl, nonce, fetchPanelData ] );

	var runClear = useCallback( function () {
		setPendingAction( null );
		setClearing( true );

		var formData = new FormData();
		formData.append( 'action', 'bb_admin_tools_default_data_clear' );
		formData.append( 'nonce', nonce );

		fetch( ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			body:        formData,
		} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( res ) {
				if ( res && res.success ) {
					setToast( {
						status:  'success',
						message: ( res.data && res.data.message ) || __( 'Default data cleared.', 'buddyboss' ),
					} );
					setSelected( {} );
					fetchPanelData();
				} else {
					setToast( {
						status:  'error',
						message: ( res && res.data && res.data.message ) || __( 'Failed to clear default data.', 'buddyboss' ),
					} );
				}
				setClearing( false );
			} )
			.catch( function () {
				setToast( {
					status:  'error',
					message: __( 'Failed to clear default data. Please try again.', 'buddyboss' ),
				} );
				setClearing( false );
			} );
	}, [ ajaxUrl, nonce, fetchPanelData ] );

	if ( loading ) {
		return (
			<div className="bb-admin-loading">
				<Spinner />
			</div>
		);
	}

	if ( loadError ) {
		return (
			<div className="bb-admin-notice bb-admin-notice--error">
				<p>{ loadError }</p>
			</div>
		);
	}

	return (
		<>
			{ toast && (
				<Toast
					status={ toast.status }
					message={ toast.message }
					onDismiss={ function () { setToast( null ); } }
				/>
			) }

			<div className="bb-admin-feature-settings__section bb-admin-tools-default-data">
				<div className="bb-admin-feature-settings__section-header">
					<div className="bb-admin-feature-settings__section-header-left">
						<h3 className="bb-admin-feature-settings__section-title">
							{ __( 'Default Data', 'buddyboss' ) }
						</h3>
					</div>
				</div>
				<div className="bb-admin-feature-settings__section-body">
					<p className="bb-admin-feature-settings__section-description">
						{ __( 'Select the data you want to import. Some of these tools utilize substantial database resources. Avoid running more than 1 import job at a time.', 'buddyboss' ) }
					</p>

					{ visibleGroups.map( function ( group ) {
						return (
							<div key={ group.id } className="bb-admin-tools-default-data__group">
								<div className="bb-admin-tools-default-data__group-label">
									{ group.label }
								</div>
								<ul className="bb-admin-tools-default-data__group-items">
									{ group.items.map( function ( item ) {
										var isImported = !! importedMap[ item.id ];
										return (
											<li
												key={ item.id }
												className={ isImported ? 'bb-admin-tools-default-data__item bb-admin-tools-default-data__item--imported' : 'bb-admin-tools-default-data__item' }
											>
												<CheckboxControl
													label={ item.label }
													checked={ !! selected[ item.id ] || isImported }
													disabled={ isImported || submitting || clearing }
													onChange={ function ( checked ) { toggleItem( item.id, checked ); } }
													__nextHasNoMarginBottom
												/>
											</li>
										);
									} ) }
								</ul>
							</div>
						);
					} ) }

					<div className="bb-admin-tools-default-data__actions">
						<Button
							variant="primary"
							onClick={ handleImportClick }
							isBusy={ submitting }
							disabled={ submitting || clearing }
						>
							{ submitting
								? __( 'Importing…', 'buddyboss' )
								: __( 'Import Selected Data', 'buddyboss' ) }
						</Button>
						<Button
							variant="secondary"
							onClick={ handleClearClick }
							isBusy={ clearing }
							disabled={ submitting || clearing }
						>
							{ clearing
								? __( 'Clearing…', 'buddyboss' )
								: __( 'Clear Default Data', 'buddyboss' ) }
						</Button>
					</div>
				</div>
			</div>

			{ /* Confirmation modal — reuses the project's `ConfirmToggleModal`
			    so the chrome (header padding, header divider, body padding,
			    footer divider, footer button styling, backdrop dimming)
			    matches every other Settings 2.0 confirm dialog instead of
			    diverging into a custom modal. Message text is the verbatim
			    legacy copy from `class-bp-admin-tab.php:257-258`. */ }
			<ConfirmToggleModal
				isOpen={ !! pendingAction }
				title={ 'import' === pendingAction
					? __( 'Import default data?', 'buddyboss' )
					: __( 'Clear default data?', 'buddyboss' )
				}
				message={ 'import' === pendingAction
					? __( 'Are you sure you want to import data? This action is going to alter your database. If this is a live website you may want to create a backup of your database first.', 'buddyboss' )
					: __( 'Are you sure you want to delete all Default Data content? Content that was created by you and others, and not by this default data installer, will not be deleted.', 'buddyboss' )
				}
				confirmLabel={ __( 'OK', 'buddyboss' ) }
				cancelLabel={ __( 'Cancel', 'buddyboss' ) }
				isDestructive={ 'clear' === pendingAction }
				onConfirm={ 'import' === pendingAction ? runImport : runClear }
				onCancel={ closeConfirm }
			/>
		</>
	);
}
