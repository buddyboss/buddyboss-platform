/**
 * BuddyBoss Admin Settings 2.0 - Import Content Panel
 *
 * Hosts the two legacy "Import …" admin pages — Import Profile Types
 * (`bp-member-type-import`) and Import Forums (`bbp-converter`) — as
 * sections inside a single Settings 2.0 panel. The Profile Types
 * section wraps the legacy `bp_member_type_import_submenu_page()`
 * form-processing branch verbatim via the
 * `bb_admin_tools_import_profile_types` AJAX adapter — same diff
 * computation, same `wp_insert_post()` call shape, same post-meta
 * keys. The Forums section is a placeholder until Phase 3.2 lands the
 * full `BBP_Converter` wrapper (17-step pipeline + progress UI).
 *
 * Sub-section visibility is gated server-side by
 * `bb_admin_tools_get_import_content_data` based on which Platform
 * components are active — same `bp_is_active()` rules the legacy
 * admin pages used.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, CheckboxControl, SelectControl, Spinner, TextControl } from '@wordpress/components';
import { Toast, useAutoDismissToast } from '../Toast';
import { ConfirmToggleModal } from '../modals/ConfirmToggleModal';

/**
 * Import Content Panel
 *
 * @returns {JSX.Element}
 */
export function ImportContentPanel() {
	var [ loading, setLoading ]     = useState( true );
	var [ loadError, setLoadError ] = useState( '' );
	var [ panelData, setPanelData ] = useState( null );

	var [ ptImporting, setPtImporting ]   = useState( false );
	var [ ptPendingAction, setPtPendingAction ] = useState( false );

	var [ toast, setToast ] = useState( null );
	useAutoDismissToast( toast, setToast );

	var ajaxUrl = ( window.bbAdminData && window.bbAdminData.ajaxUrl ) || window.ajaxurl || '/wp-admin/admin-ajax.php';
	var nonce   = ( window.bbAdminData && window.bbAdminData.toolsNonce ) || '';

	var fetchPanelData = useCallback( function () {
		setLoading( true );
		setLoadError( '' );

		var formData = new FormData();
		formData.append( 'action', 'bb_admin_tools_get_import_content_data' );
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
							|| __( 'Unable to load Import Content state.', 'buddyboss' )
					);
				}
				setLoading( false );
			} )
			.catch( function () {
				setLoadError( __( 'Unable to load Import Content state.', 'buddyboss' ) );
				setLoading( false );
			} );
	}, [ ajaxUrl, nonce ] );

	useEffect( function () {
		fetchPanelData();
	}, [ fetchPanelData ] );

	var handleProfileTypesClick = useCallback( function () {
		if ( ptImporting ) {
			return;
		}
		setPtPendingAction( true );
	}, [ ptImporting ] );

	var closePtConfirm = useCallback( function () {
		setPtPendingAction( false );
	}, [] );

	var runProfileTypesImport = useCallback( function () {
		setPtPendingAction( false );
		setPtImporting( true );

		var formData = new FormData();
		formData.append( 'action', 'bb_admin_tools_import_profile_types' );
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
						message: ( res.data && res.data.message ) || __( 'Profile types imported.', 'buddyboss' ),
					} );
					fetchPanelData();
				} else {
					setToast( {
						status:  'error',
						message: ( res && res.data && res.data.message ) || __( 'Profile types import failed.', 'buddyboss' ),
					} );
				}
				setPtImporting( false );
			} )
			.catch( function () {
				setToast( {
					status:  'error',
					message: __( 'Profile types import failed. Please try again.', 'buddyboss' ),
				} );
				setPtImporting( false );
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

	var ptData         = ( panelData && panelData.profile_types ) || {};
	var ptAvailable    = !! ptData.available;
	var ptPendingCount = parseInt( ptData.pending_count || 0, 10 );
	var ptPendingNames = Array.isArray( ptData.pending_names ) ? ptData.pending_names : [];

	var forumsAvailable = !! ( panelData && panelData.forums && panelData.forums.available );
	var mediaData       = ( panelData && panelData.media ) || {};
	var mediaAvailable  = !! mediaData.available;

	return (
		<>
			{ toast && (
				<Toast
					status={ toast.status }
					message={ toast.message }
					onDismiss={ function () { setToast( null ); } }
				/>
			) }

			{ /* ------------------------------ Profile Types ----------------------------- */ }
			{ ptAvailable && (
				<div className="bb-admin-feature-settings__section bb-admin-tools-import-profile-types">
					<div className="bb-admin-feature-settings__section-header">
						<div className="bb-admin-feature-settings__section-header-left">
							<h3 className="bb-admin-feature-settings__section-title">
								{ __( 'Import Profile Types', 'buddyboss' ) }
							</h3>
						</div>
					</div>
					<div className="bb-admin-feature-settings__section-body">
						<p className="bb-admin-feature-settings__section-description">
							{ __( 'Import your existing profile types (or "member types" in BuddyPress). You may have created these types manually via code or by using a third party plugin. Click "Run Migration" below and all registered member types will be imported. Then you can remove the old code or plugin.', 'buddyboss' ) }
						</p>

						{ ptPendingCount > 0 ? (
							<div className="bb-admin-tools-import-profile-types__pending">
								<p>
									{ sprintf(
										/* translators: %d: number of profile types pending import. */
										_n(
											'%d profile type is registered but not yet imported:',
											'%d profile types are registered but not yet imported:',
											ptPendingCount,
											'buddyboss'
										),
										ptPendingCount
									) }
								</p>
								<ul className="bb-admin-tools-import-profile-types__pending-list">
									{ ptPendingNames.map( function ( name ) {
										return <li key={ name }>{ name }</li>;
									} ) }
								</ul>
							</div>
						) : (
							<p className="bb-admin-tools-import-profile-types__empty">
								{ __( 'All registered profile types are already imported. Nothing to do here.', 'buddyboss' ) }
							</p>
						) }

						<div className="bb-admin-tools-import-profile-types__actions">
							<Button
								variant="primary"
								onClick={ handleProfileTypesClick }
								isBusy={ ptImporting }
								disabled={ ptImporting || ptPendingCount === 0 }
							>
								{ ptImporting
									? __( 'Importing…', 'buddyboss' )
									: __( 'Run Migration', 'buddyboss' ) }
							</Button>
						</div>
					</div>
				</div>
			) }

			{ /* ------------------------------ Forums Converter --------------------------- */ }
			{ forumsAvailable && (
				<ForumsConverterSection
					setToast={ setToast }
				/>
			) }

			{ /* ------------------------------ Import Media ------------------------------- */ }
			{ mediaAvailable && (
				<div className="bb-admin-feature-settings__section bb-admin-tools-import-media">
					<div className="bb-admin-feature-settings__section-header">
						<div className="bb-admin-feature-settings__section-header-left">
							<h3 className="bb-admin-feature-settings__section-title">
								{ __( 'Import Media', 'buddyboss' ) }
							</h3>
						</div>
					</div>
					<div className="bb-admin-feature-settings__section-body">
						{ mediaData.tables_exist ? (
							<>
								<p className="bb-admin-feature-settings__section-description">
									{ __( 'Legacy BuddyBoss Media data was detected on this site. Run the importer to migrate albums, photos, and forum/topic/reply attachments into the BuddyBoss Platform Media component, then remove the old plugin.', 'buddyboss' ) }
								</p>
								<div className="bb-admin-tools-import-media__actions">
									<a
										className="components-button is-primary"
										href={ mediaData.legacy_url }
									>
										{ __( 'Open Media Importer', 'buddyboss' ) }
									</a>
								</div>
							</>
						) : (
							<p className="bb-admin-feature-settings__section-description">
								{ __( 'BuddyBoss Media plugin database tables do not exist, so there is nothing to import.', 'buddyboss' ) }
							</p>
						) }
					</div>
				</div>
			) }

			{ /* If no import target is available, surface a helpful empty
			    state instead of a blank panel. */ }
			{ ! ptAvailable && ! forumsAvailable && ! mediaAvailable && (
				<div className="bb-admin-notice">
					<p>
						{ __( 'No import targets are available on this site. Activate the members, forums, or media component to use Import Content.', 'buddyboss' ) }
					</p>
				</div>
			) }

			<ConfirmToggleModal
				isOpen={ ptPendingAction }
				title={ __( 'Import profile types?', 'buddyboss' ) }
				message={ __( 'This will import every registered profile type that isn\'t already a BuddyBoss profile type. Existing types are left untouched.', 'buddyboss' ) }
				confirmLabel={ __( 'OK', 'buddyboss' ) }
				cancelLabel={ __( 'Cancel', 'buddyboss' ) }
				onConfirm={ runProfileTypesImport }
				onCancel={ closePtConfirm }
			/>
		</>
	);
}

/**
 * Two-column field row matching the Figma spec for Settings 2.0 forms
 * (label + help in the left column, input + per-input help in the right
 * column, divider below). Wraps the standard `.bb-admin-settings-form__field`
 * structure used by the auto-rendered settings forms so a hand-built
 * panel like Forums Converter inherits the same visual rhythm as the
 * registry-driven panels (Reactions, Profile Fields, etc.).
 *
 * @param {Object}      props
 * @param {string}      props.label     Field label text (left column).
 * @param {string}      [props.help]    Help text below the input (right column).
 * @param {JSX.Element} props.children  The control to render in the right column.
 * @returns {JSX.Element}
 */
function FieldRow( { label, help, children } ) {
	return (
		<div className="bb-admin-settings-form__field">
			<div className="bb-admin-settings-form__field-label">
				<label>
					<span className="bb-admin-settings-form__field-label-text">{ label }</span>
				</label>
			</div>
			<div className="bb-admin-settings-form__field-content">
				<div className="bb-admin-settings-form__field-input-wrapper">
					{ children }
				</div>
				{ help && (
					<p className="bb-admin-settings-form__field-description">{ help }</p>
				) }
			</div>
		</div>
	);
}

/* ============================================================================
 * Forums Converter section
 *
 * Wraps the legacy `BBP_Converter` 17-step pipeline. Form values write
 * straight to the same `_bbp_converter_*` options the legacy WP
 * Settings API form wrote to (via the
 * `bb_admin_tools_save_converter_settings` adapter), and execution
 * goes through the existing `wp_ajax_bbp_converter_process` AJAX
 * action — React polls it until `BBP_Converter::do_steps()` reports
 * the pipeline is complete (status === 'success' && step === 0). Zero
 * backend changes.
 * ========================================================================= */

/**
 * Forums Converter Section
 *
 * @param {Object}   props
 * @param {Function} props.setToast Parent toast setter for unified feedback UX.
 * @returns {JSX.Element}
 */
function ForumsConverterSection( { setToast } ) {
	var [ loading, setLoading ]         = useState( true );
	var [ loadError, setLoadError ]     = useState( '' );
	var [ platforms, setPlatforms ]     = useState( [] );
	var [ formValues, setFormValues ]   = useState( null );
	var [ converterState, setConverterState ] = useState( null );

	// Pending confirmation modal — same pattern Default Data uses so the
	// admin always confirms the destructive action before it runs.
	var [ pendingStart, setPendingStart ] = useState( false );

	// Live-import progress: { running, savingSettings, message, totalPercent }.
	var [ progress, setProgress ] = useState( {
		running:        false,
		savingSettings: false,
		message:        '',
		totalPercent:   0,
	} );

	// Cancellation: cooperative — flips between AJAX ticks so an
	// in-flight step finishes cleanly before the loop exits, leaving the
	// converter's `_bbp_converter_step` option intact so the admin can
	// resume by hitting Continue.
	var cancelRef = useRef( false );

	var ajaxUrl        = ( window.bbAdminData && window.bbAdminData.ajaxUrl ) || window.ajaxurl || '/wp-admin/admin-ajax.php';
	var toolsNonce     = ( window.bbAdminData && window.bbAdminData.toolsNonce ) || '';
	var converterNonce = ( window.bbAdminData && window.bbAdminData.converterNonce ) || '';

	var fetchConverterData = useCallback( function () {
		setLoading( true );
		setLoadError( '' );

		var formData = new FormData();
		formData.append( 'action', 'bb_admin_tools_get_converter_data' );
		formData.append( 'nonce', toolsNonce );

		fetch( ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			body:        formData,
		} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( res ) {
				if ( res && res.success && res.data ) {
					setPlatforms( res.data.platforms || [] );
					setFormValues( res.data.options || null );
					setConverterState( res.data.state || null );
					if ( res.data.state ) {
						setProgress( function ( prev ) {
							return Object.assign( {}, prev, {
								totalPercent: res.data.state.total_percent || 0,
							} );
						} );
					}
				} else {
					setLoadError(
						( res && res.data && res.data.message )
							|| __( 'Unable to load forum import settings.', 'buddyboss' )
					);
				}
				setLoading( false );
			} )
			.catch( function () {
				setLoadError( __( 'Unable to load forum import settings.', 'buddyboss' ) );
				setLoading( false );
			} );
	}, [ ajaxUrl, toolsNonce ] );

	useEffect( function () {
		fetchConverterData();
	}, [ fetchConverterData ] );

	var updateField = useCallback( function ( key, value ) {
		setFormValues( function ( prev ) {
			return Object.assign( {}, prev || {}, { [ key ]: value } );
		} );
	}, [] );

	/**
	 * Save the form to `_bbp_converter_*` options.
	 *
	 * @returns {Promise<boolean>} `true` on save success.
	 */
	var saveSettings = useCallback( function () {
		setProgress( function ( prev ) { return Object.assign( {}, prev, { savingSettings: true } ); } );

		var data = new FormData();
		data.append( 'action', 'bb_admin_tools_save_converter_settings' );
		data.append( 'nonce', toolsNonce );
		Object.keys( formValues || {} ).forEach( function ( k ) {
			var v = formValues[ k ];
			data.append( k, ( null === v || undefined === v ) ? '' : String( v ) );
		} );

		return fetch( ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			body:        data,
		} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( res ) {
				setProgress( function ( prev ) { return Object.assign( {}, prev, { savingSettings: false } ); } );
				if ( res && res.success ) {
					return true;
				}
				setToast( {
					status:  'error',
					message: ( res && res.data && res.data.message )
						|| __( 'Could not save forum import settings.', 'buddyboss' ),
				} );
				return false;
			} )
			.catch( function () {
				setProgress( function ( prev ) { return Object.assign( {}, prev, { savingSettings: false } ); } );
				setToast( {
					status:  'error',
					message: __( 'Could not save forum import settings.', 'buddyboss' ),
				} );
				return false;
			} );
	}, [ ajaxUrl, toolsNonce, formValues, setToast ] );

	/**
	 * Single tick of the converter pipeline.
	 *
	 * Calls the legacy `wp_ajax_bbp_converter_process` endpoint, which
	 * runs one batch from the current step and bumps
	 * `_bbp_converter_step` when the step completes. The endpoint
	 * responds with an HTML success message (not JSON) per the bbPress
	 * convention; we read `response.ok` for the loop condition and the
	 * pipeline state lives entirely in `_bbp_converter_step` server-side.
	 *
	 * @returns {Promise<{ ok: boolean, body: string }>}
	 */
	var runConverterTick = useCallback( function () {
		var body = new FormData();
		body.append( 'action', 'bbp_converter_process' );
		body.append( '_ajax_nonce', converterNonce );

		return fetch( ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			body:        body,
		} )
			.then( function ( res ) {
				return res.text().then( function ( text ) {
					return { ok: res.ok, body: text };
				} );
			} );
	}, [ ajaxUrl, converterNonce ] );

	/**
	 * Drive the converter loop until the legacy pipeline reports
	 * step === 0 (which `BBP_Converter` does when `do_steps()` exits
	 * the final step). After every tick we re-fetch the persisted
	 * state so the progress bar tracks the option-stored step count.
	 *
	 * @returns {Promise<void>}
	 */
	var driveConverter = useCallback( function () {
		var loop = function () {
			if ( cancelRef.current ) {
				setProgress( function ( prev ) { return Object.assign( {}, prev, { running: false } ); } );
				return;
			}
			runConverterTick()
				.then( function ( result ) {
					if ( ! result.ok ) {
						setProgress( function ( prev ) { return Object.assign( {}, prev, { running: false } ); } );
						setToast( {
							status:  'error',
							message: __( 'Forum import failed. Please verify your database credentials and try again.', 'buddyboss' ),
						} );
						return;
					}
					// Re-read state from the server so the progress bar
					// reflects the option-stored step count.
					var statusData = new FormData();
					statusData.append( 'action', 'bb_admin_tools_get_converter_data' );
					statusData.append( 'nonce', toolsNonce );
					fetch( ajaxUrl, {
						method:      'POST',
						credentials: 'same-origin',
						body:        statusData,
					} )
						.then( function ( r ) { return r.json(); } )
						.then( function ( r ) {
							if ( ! r || ! r.success || ! r.data || ! r.data.state ) {
								setProgress( function ( prev ) { return Object.assign( {}, prev, { running: false } ); } );
								return;
							}
							var nextState = r.data.state;
							setConverterState( nextState );
							setProgress( function ( prev ) {
								return Object.assign( {}, prev, {
									totalPercent: nextState.total_percent || 0,
								} );
							} );
							// `step` resets to 0 when the legacy
							// `do_steps()` finishes the final step
							// (`BBP_Converter::sync_table()` etc.) —
							// that's our loop-exit signal.
							if ( ! nextState.started || 0 === nextState.step ) {
								setProgress( function ( prev ) {
									return Object.assign( {}, prev, {
										running:      false,
										totalPercent: 100,
									} );
								} );
								setToast( {
									status:  'success',
									message: __( 'Forum import finished.', 'buddyboss' ),
								} );
								return;
							}
							loop();
						} )
						.catch( function () {
							setProgress( function ( prev ) { return Object.assign( {}, prev, { running: false } ); } );
						} );
				} )
				.catch( function () {
					setProgress( function ( prev ) { return Object.assign( {}, prev, { running: false } ); } );
					setToast( {
						status:  'error',
						message: __( 'Forum import failed. Please try again.', 'buddyboss' ),
					} );
				} );
		};
		loop();
	}, [ runConverterTick, ajaxUrl, toolsNonce, setToast ] );

	var handleStartClick = useCallback( function () {
		setPendingStart( true );
	}, [] );

	var closeStartConfirm = useCallback( function () {
		setPendingStart( false );
	}, [] );

	var runStart = useCallback( function () {
		setPendingStart( false );
		cancelRef.current = false;
		setProgress( {
			running:        true,
			savingSettings: false,
			message:        __( 'Starting forum import…', 'buddyboss' ),
			totalPercent:   0,
		} );

		saveSettings().then( function ( ok ) {
			if ( ! ok ) {
				setProgress( function ( prev ) { return Object.assign( {}, prev, { running: false } ); } );
				return;
			}
			driveConverter();
		} );
	}, [ saveSettings, driveConverter ] );

	var handleCancelImport = useCallback( function () {
		cancelRef.current = true;
	}, [] );

	if ( loading ) {
		return (
			<div className="bb-admin-feature-settings__section bb-admin-tools-import-forums">
				<div className="bb-admin-feature-settings__section-body">
					<div className="bb-admin-loading">
						<Spinner />
					</div>
				</div>
			</div>
		);
	}

	if ( loadError ) {
		return (
			<div className="bb-admin-feature-settings__section bb-admin-tools-import-forums">
				<div className="bb-admin-feature-settings__section-body">
					<div className="bb-admin-notice bb-admin-notice--error">
						<p>{ loadError }</p>
					</div>
				</div>
			</div>
		);
	}

	var values = formValues || {};
	var isRunning = progress.running;
	var canStart  = !! values.db_name && !! values.db_user && !! values.platform && ! isRunning && ! progress.savingSettings;

	return (
		<>
			<div className="bb-admin-feature-settings__section bb-admin-tools-import-forums">
				<div className="bb-admin-feature-settings__section-header">
					<div className="bb-admin-feature-settings__section-header-left">
						<h3 className="bb-admin-feature-settings__section-title">
							{ __( 'Import Forums', 'buddyboss' ) }
						</h3>
					</div>
				</div>
				<div className="bb-admin-feature-settings__section-body">
					<div className="bb-admin-tools-import-forums__notice">
						<i className="bb-icons-rl bb-icons-rl-info" aria-hidden="true"></i>
						<p>
							{ __( 'Enter your previous forums database details for conversion (be sure to back up first), and optionally adjust settings to fine-tune the process.', 'buddyboss' ) }
						</p>
					</div>

					<div className="bb-admin-tools-import-forums__form">
						<FieldRow
							label={ __( 'Select Forum', 'buddyboss' ) }
							help={ __( 'Choose the forum software you are migrating from.', 'buddyboss' ) }
						>
							<SelectControl
								value={ values.platform || '' }
								options={ [ { value: '', label: __( '— Select —', 'buddyboss' ) } ].concat( platforms ) }
								onChange={ function ( v ) { updateField( 'platform', v ); } }
								disabled={ isRunning }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						</FieldRow>

						<FieldRow
							label={ __( 'Database Server', 'buddyboss' ) }
							help={ __( 'Enter the database host (e.g., localhost or IP address).', 'buddyboss' ) }
						>
							<TextControl
								value={ values.db_server || '' }
								onChange={ function ( v ) { updateField( 'db_server', v ); } }
								disabled={ isRunning }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						</FieldRow>

						<FieldRow
							label={ __( 'Database Port', 'buddyboss' ) }
							help={ __( 'Default is 3306. Change only if your database uses a different port.', 'buddyboss' ) }
						>
							<TextControl
								type="number"
								value={ values.db_port || '' }
								onChange={ function ( v ) { updateField( 'db_port', v ); } }
								disabled={ isRunning }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						</FieldRow>

						<FieldRow
							label={ __( 'Database Name', 'buddyboss' ) }
							help={ __( 'Name of the database that contains your existing forum data.', 'buddyboss' ) }
						>
							<TextControl
								value={ values.db_name || '' }
								onChange={ function ( v ) { updateField( 'db_name', v ); } }
								disabled={ isRunning }
								placeholder={ __( 'Enter database name', 'buddyboss' ) }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						</FieldRow>

						<FieldRow
							label={ __( 'Database User', 'buddyboss' ) }
							help={ __( 'Username used to connect to the database.', 'buddyboss' ) }
						>
							<TextControl
								value={ values.db_user || '' }
								onChange={ function ( v ) { updateField( 'db_user', v ); } }
								disabled={ isRunning }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						</FieldRow>

						<FieldRow
							label={ __( 'Database Password', 'buddyboss' ) }
							help={ __( 'Password for the database user. Leave blank to keep the existing password.', 'buddyboss' ) }
						>
							<TextControl
								type="password"
								value={ values.db_pass || '' }
								onChange={ function ( v ) { updateField( 'db_pass', v ); } }
								disabled={ isRunning }
								placeholder={ __( 'Enter password', 'buddyboss' ) }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						</FieldRow>

						<FieldRow
							label={ __( 'Table Prefix', 'buddyboss' ) }
							help={ __( 'Enter the table prefix of your old forum (e.g., wp_bb_ for BuddyBoss Forums). Leave empty if none.', 'buddyboss' ) }
						>
							<TextControl
								value={ values.db_prefix || '' }
								onChange={ function ( v ) { updateField( 'db_prefix', v ); } }
								disabled={ isRunning }
								placeholder={ __( 'Enter table prefix', 'buddyboss' ) }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						</FieldRow>

						<FieldRow
							label={ __( 'Rows Limit', 'buddyboss' ) }
							help={ __( 'Number of rows to process at a time. Keep this low if you experience out-of-memory issues.', 'buddyboss' ) }
						>
							<TextControl
								type="number"
								value={ values.rows || '' }
								onChange={ function ( v ) { updateField( 'rows', v ); } }
								disabled={ isRunning }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						</FieldRow>

						<FieldRow
							label={ __( 'Delay Time', 'buddyboss' ) }
							help={ __( 'Delay (in seconds) between each group of rows to help prevent too many connections.', 'buddyboss' ) }
						>
							<TextControl
								type="number"
								value={ values.delay_time || '' }
								onChange={ function ( v ) { updateField( 'delay_time', v ); } }
								disabled={ isRunning }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						</FieldRow>

						<FieldRow
							label={ __( 'Convert Users', 'buddyboss' ) }
							help={ __( 'Non-Forums passwords cannot be automatically converted. They will be converted as each user logs in.', 'buddyboss' ) }
						>
							<CheckboxControl
								label={ __( 'Attempt to import user accounts from previous forums', 'buddyboss' ) }
								checked={ !! parseInt( values.convert_users || 0, 10 ) }
								onChange={ function ( v ) { updateField( 'convert_users', v ? 1 : 0 ); } }
								disabled={ isRunning }
								__nextHasNoMarginBottom
							/>
						</FieldRow>

						<FieldRow
							label={ __( 'Start Over', 'buddyboss' ) }
							help={ __( 'You should clean old conversion information before starting over.', 'buddyboss' ) }
						>
							<CheckboxControl
								label={ __( 'Start a fresh conversion from the beginning', 'buddyboss' ) }
								checked={ !! parseInt( values.restart || 0, 10 ) }
								onChange={ function ( v ) { updateField( 'restart', v ? 1 : 0 ); } }
								disabled={ isRunning }
								__nextHasNoMarginBottom
							/>
						</FieldRow>

						<FieldRow
							label={ __( 'Purge Previous Import', 'buddyboss' ) }
							help={ __( 'Use this if an import failed and you want to remove that incomplete data.', 'buddyboss' ) }
						>
							<CheckboxControl
								label={ __( 'Purge all information from a previously attempted import', 'buddyboss' ) }
								checked={ !! parseInt( values.clean || 0, 10 ) }
								onChange={ function ( v ) { updateField( 'clean', v ? 1 : 0 ); } }
								disabled={ isRunning }
								__nextHasNoMarginBottom
							/>
						</FieldRow>
					</div>

					{ ( isRunning || ( converterState && converterState.started ) ) && (
						<div className="bb-admin-tools-import-forums__progress">
							<div className="bb-admin-tools-import-forums__progress-label">
								{ isRunning
									? sprintf(
										/* translators: %d: integer percent (0–100). */
										__( 'Converting… %d%%', 'buddyboss' ),
										progress.totalPercent
									)
									: sprintf(
										/* translators: %d: integer percent (0–100). */
										__( 'Paused at %d%%. Press "Continue" to resume.', 'buddyboss' ),
										progress.totalPercent
									)
								}
							</div>
							<div className="bb-admin-tools-import-forums__progress-bar">
								<div
									className="bb-admin-tools-import-forums__progress-fill"
									style={ { width: progress.totalPercent + '%' } }
								></div>
							</div>
						</div>
					) }

					<div className="bb-admin-tools-import-forums__actions">
						<Button
							variant="primary"
							onClick={ handleStartClick }
							isBusy={ isRunning || progress.savingSettings }
							disabled={ ! canStart }
						>
							{ isRunning
								? __( 'Converting…', 'buddyboss' )
								: ( converterState && converterState.started
									? __( 'Continue', 'buddyboss' )
									: __( 'Start Now', 'buddyboss' )
								)
							}
						</Button>
						{ isRunning && (
							<Button
								variant="secondary"
								onClick={ handleCancelImport }
							>
								{ __( 'Cancel', 'buddyboss' ) }
							</Button>
						) }
					</div>
				</div>
			</div>

			<ConfirmToggleModal
				isOpen={ pendingStart }
				title={ __( 'Start forum import?', 'buddyboss' ) }
				message={ __( 'This will connect to the external forum database and begin importing forums, topics, replies, and (optionally) user accounts into BuddyBoss. The process runs in batches and can be paused and resumed. Back up your site before continuing.', 'buddyboss' ) }
				confirmLabel={ __( 'OK', 'buddyboss' ) }
				cancelLabel={ __( 'Cancel', 'buddyboss' ) }
				onConfirm={ runStart }
				onCancel={ closeStartConfirm }
			/>
		</>
	);
}
