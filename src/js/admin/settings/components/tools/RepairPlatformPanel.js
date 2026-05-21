/**
 * BuddyBoss Admin Settings 2.0 - Repair Platform Panel
 *
 * Runs the platform-wide repair / migration callbacks registered via
 * `bp_admin_repair_list()` (the same list the legacy "Repair Community"
 * admin page exposed). Third-party items added via the `bp_repair_list`
 * filter — e.g. TutorLMS's `bb_migrate_tutor_group_course` — come
 * through automatically because the PHP endpoint returns
 * `apply_filters( 'bp_repair_list', ... )` verbatim.
 *
 * Execution goes through the existing
 * `wp_ajax_bp_admin_repair_tools_wrapper_function` AJAX handler, with
 * the legacy `bp-do-counts` nonce — zero backend changes. Paginated
 * repairs (functions that return `status: 'running'` with an `offset`)
 * are polled identically to the legacy jQuery implementation.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useCallback, useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button, CheckboxControl, Spinner } from '@wordpress/components';
import { Toast, useAutoDismissToast } from '../Toast';

/**
 * Repair Platform Panel
 *
 * @returns {JSX.Element}
 */
export function RepairPlatformPanel() {
	var [ loading, setLoading ]         = useState( true );
	var [ loadError, setLoadError ]     = useState( '' );
	var [ categories, setCategories ]   = useState( [] );
	var [ selected, setSelected ]       = useState( {} );
	var [ running, setRunning ]         = useState( false );
	var [ progress, setProgress ]       = useState( {} ); // { [slug]: { status, message } }

	var [ toast, setToast ] = useState( null );
	useAutoDismissToast( toast, setToast );

	// `running` ref so the cancellation effect can read the latest value
	// without re-running the long-running repair loop.
	var cancelRef = useRef( false );

	var ajaxUrl     = ( window.bbAdminData && window.bbAdminData.ajaxUrl ) || window.ajaxurl || '/wp-admin/admin-ajax.php';
	var toolsNonce  = ( window.bbAdminData && window.bbAdminData.toolsNonce ) || '';
	var repairNonce = ( window.bbAdminData && window.bbAdminData.repairNonce ) || '';

	var fetchRepairList = useCallback( function () {
		setLoading( true );
		setLoadError( '' );

		var formData = new FormData();
		formData.append( 'action', 'bb_admin_tools_get_repair_list' );
		formData.append( 'nonce', toolsNonce );

		fetch( ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			body:        formData,
		} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( res ) {
				if ( res && res.success && res.data && Array.isArray( res.data.categories ) ) {
					setCategories( res.data.categories );
				} else {
					setLoadError(
						( res && res.data && res.data.message )
							|| __( 'Unable to load repair tools.', 'buddyboss' )
					);
				}
				setLoading( false );
			} )
			.catch( function () {
				setLoadError( __( 'Unable to load repair tools.', 'buddyboss' ) );
				setLoading( false );
			} );
	}, [ ajaxUrl, toolsNonce ] );

	useEffect( function () {
		fetchRepairList();
	}, [ fetchRepairList ] );

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

	// All non-disabled item ids across every category — used by the
	// "Select all" master toggle at the top of the panel.
	var allSelectableIds = useMemo( function () {
		var ids = [];
		categories.forEach( function ( cat ) {
			cat.items.forEach( function ( item ) {
				if ( ! item.disabled ) {
					ids.push( item.id );
				}
			} );
		} );
		return ids;
	}, [ categories ] );

	var selectAllState = useMemo( function () {
		if ( allSelectableIds.length === 0 ) {
			return { checked: false, indeterminate: false };
		}
		var picked = allSelectableIds.filter( function ( id ) { return !! selected[ id ]; } ).length;
		return {
			checked:       picked === allSelectableIds.length,
			indeterminate: picked > 0 && picked < allSelectableIds.length,
		};
	}, [ allSelectableIds, selected ] );

	var toggleSelectAll = useCallback( function ( checked ) {
		setSelected( function () {
			if ( ! checked ) {
				return {};
			}
			var next = {};
			allSelectableIds.forEach( function ( id ) {
				next[ id ] = true;
			} );
			return next;
		} );
	}, [ allSelectableIds ] );

	var selectedIds = useMemo( function () {
		return Object.keys( selected ).filter( function ( id ) { return !! selected[ id ]; } );
	}, [ selected ] );

	/**
	 * Find an item across all categories. Used by `runRepairTick` to
	 * pick the right execution endpoint (BP paginated wrapper vs the
	 * bbPress single-shot wrapper) based on the item's `source` flag.
	 *
	 * @param {string} id Item id (slug).
	 * @returns {Object|null}
	 */
	var findItemById = useCallback( function ( id ) {
		for ( var i = 0; i < categories.length; i++ ) {
			var items = categories[ i ].items;
			for ( var j = 0; j < items.length; j++ ) {
				if ( items[ j ].id === id ) {
					return items[ j ];
				}
			}
		}
		return null;
	}, [ categories ] );

	/**
	 * Run a single repair-tool tick.
	 *
	 * Routes to one of two legacy AJAX endpoints based on the item's
	 * `source`:
	 *   - `bp`  → existing `bp_admin_repair_tools_wrapper_function`
	 *            (paginated; uses `bp-do-counts` nonce).
	 *   - `bbp` → our `bb_admin_tools_run_bbp_repair` wrapper
	 *            (single-shot; uses the unified `toolsNonce`).
	 * Both endpoints normalize their response into the same
	 * `{ done, offset, message }` shape so the run loop doesn't care
	 * which source it's talking to.
	 *
	 * @param {string}      type   Repair slug (e.g. `bp-total-member-count`).
	 * @param {number|null} offset Current batch offset (null for first call).
	 * @returns {Promise<{ done: boolean, offset: number|null, message: string }>}
	 */
	var runRepairTick = useCallback( function ( type, offset ) {
		var item   = findItemById( type );
		var source = ( item && item.source ) || 'bp';

		var formData = new FormData();

		if ( 'bbp' === source ) {
			// bbPress callbacks are single-shot — one POST returns the
			// final result (no pagination).
			formData.append( 'action', 'bb_admin_tools_run_bbp_repair' );
			formData.append( 'type', type );
			formData.append( 'nonce', toolsNonce );

			return fetch( ajaxUrl, {
				method:      'POST',
				credentials: 'same-origin',
				body:        formData,
			} )
				.then( function ( res ) {
					if ( ! res.ok ) {
						throw new Error( 'HTTP ' + res.status );
					}
					return res.json();
				} )
				.then( function ( res ) {
					if ( ! res || ! res.success || ! res.data ) {
						throw new Error(
							( res && res.data && res.data.message ) || 'Repair returned no data.'
						);
					}
					if ( ! res.data.success ) {
						throw new Error( res.data.message || __( 'Forum repair failed.', 'buddyboss' ) );
					}
					return {
						done:    true,
						offset:  null,
						message: res.data.message || '',
					};
				} );
		}

		// BP path — paginated through the legacy wrapper.
		formData.append( 'action', 'bp_admin_repair_tools_wrapper_function' );
		formData.append( 'type', type );
		formData.append( 'nonce', repairNonce );
		if ( null !== offset && undefined !== offset ) {
			formData.append( 'offset', String( offset ) );
		}

		return fetch( ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			body:        formData,
		} )
			.then( function ( res ) {
				if ( ! res.ok ) {
					throw new Error( 'HTTP ' + res.status );
				}
				return res.json();
			} )
			.then( function ( res ) {
				if ( ! res || ! res.success || ! res.data ) {
					throw new Error(
						( res && res.data && res.data.feedback ) || 'Repair returned no data.'
					);
				}
				var data = res.data;
				if ( 'running' === data.status ) {
					return {
						done:    false,
						offset:  data.offset || 0,
						message: data.records || '',
					};
				}
				return {
					done:    true,
					offset:  null,
					message: data.message || '',
				};
			} );
	}, [ ajaxUrl, repairNonce, toolsNonce, findItemById ] );

	/**
	 * Run every selected repair sequentially. Per-item state lives in
	 * `progress` so the UI can show a pending → running → done → error
	 * lifecycle per row. Cancellation is cooperative: clicking Cancel
	 * flips `cancelRef.current`, the loop reads it between ticks and
	 * exits cleanly without orphaning a half-finished batch.
	 *
	 * @returns {Promise<void>}
	 */
	var handleRunRepairs = useCallback( function () {
		if ( selectedIds.length === 0 || running ) {
			return;
		}
		cancelRef.current = false;
		setRunning( true );
		setProgress( {} );

		var typesToRun = selectedIds.slice();

		var runNext = function ( index ) {
			if ( index >= typesToRun.length ) {
				setRunning( false );
				setToast( {
					status:  'success',
					message: __( 'All selected repairs completed.', 'buddyboss' ),
				} );
				return;
			}
			if ( cancelRef.current ) {
				setRunning( false );
				setToast( {
					status:  'success',
					message: __( 'Repair cancelled.', 'buddyboss' ),
				} );
				return;
			}

			var type = typesToRun[ index ];

			setProgress( function ( prev ) {
				var next = Object.assign( {}, prev );
				next[ type ] = { status: 'running', message: __( 'Working…', 'buddyboss' ) };
				return next;
			} );

			var tick = function ( offset ) {
				if ( cancelRef.current ) {
					setRunning( false );
					return;
				}
				runRepairTick( type, offset )
					.then( function ( result ) {
						setProgress( function ( prev ) {
							var next = Object.assign( {}, prev );
							next[ type ] = {
								status:  result.done ? 'done' : 'running',
								message: result.message,
							};
							return next;
						} );
						if ( result.done ) {
							runNext( index + 1 );
						} else {
							tick( result.offset );
						}
					} )
					.catch( function ( err ) {
						setProgress( function ( prev ) {
							var next = Object.assign( {}, prev );
							next[ type ] = {
								status:  'error',
								message: ( err && err.message ) || __( 'Repair failed.', 'buddyboss' ),
							};
							return next;
						} );
						// Continue with the next item — one failure
						// shouldn't block the rest of the queue.
						runNext( index + 1 );
					} );
			};

			tick( null );
		};

		runNext( 0 );
	}, [ selectedIds, running, runRepairTick ] );

	var handleCancel = useCallback( function () {
		cancelRef.current = true;
	}, [] );

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

			<div className="bb-admin-feature-settings__section bb-admin-tools-repair-platform">
				<div className="bb-admin-feature-settings__section-header">
					<div className="bb-admin-feature-settings__section-header-left">
						<h3 className="bb-admin-feature-settings__section-title">
							{ __( 'Repair Platform', 'buddyboss' ) }
						</h3>
					</div>
				</div>
				<div className="bb-admin-feature-settings__section-body">
					<p className="bb-admin-feature-settings__section-description">
						{ __( 'BuddyBoss keeps track of various relationships between members, groups, and activity items. Occasionally these relationships become out of sync, most often after an import, update, or migration. Use the tools below to manually recalculate these relationships.', 'buddyboss' ) }
					</p>

					<div className="bb-admin-tools-repair-platform__select-all">
						<CheckboxControl
							label={ __( 'Select All', 'buddyboss' ) }
							checked={ selectAllState.checked }
							indeterminate={ selectAllState.indeterminate }
							onChange={ toggleSelectAll }
							disabled={ running || allSelectableIds.length === 0 }
							__nextHasNoMarginBottom
						/>
					</div>

					{ categories.map( function ( category ) {
						return (
							<div key={ category.id } className="bb-admin-tools-repair-platform__group">
								<div className="bb-admin-tools-repair-platform__group-label">
									{ category.label }
								</div>
								<ul className="bb-admin-tools-repair-platform__group-items">
									{ category.items.map( function ( item ) {
										var itemProgress = progress[ item.id ];
										return (
											<li key={ item.id } className={ 'bb-admin-tools-repair-platform__item' + ( itemProgress ? ' bb-admin-tools-repair-platform__item--' + itemProgress.status : '' ) }>
												<CheckboxControl
													label={ item.label }
													checked={ !! selected[ item.id ] }
													disabled={ item.disabled || running }
													onChange={ function ( checked ) { toggleItem( item.id, checked ); } }
													__nextHasNoMarginBottom
												/>
												{ itemProgress && (
													<span className="bb-admin-tools-repair-platform__item-status">
														{ 'running' === itemProgress.status && (
															<Spinner />
														) }
														{ 'done' === itemProgress.status && (
															<i className="bb-icons-rl bb-icons-rl-check-circle" aria-hidden="true"></i>
														) }
														{ 'error' === itemProgress.status && (
															<i className="bb-icons-rl bb-icons-rl-warning-circle" aria-hidden="true"></i>
														) }
														{ itemProgress.message && (
															<span className="bb-admin-tools-repair-platform__item-message">
																{ itemProgress.message }
															</span>
														) }
													</span>
												) }
											</li>
										);
									} ) }
								</ul>
							</div>
						);
					} ) }

					<div className="bb-admin-tools-repair-platform__actions">
						<Button
							variant="primary"
							onClick={ handleRunRepairs }
							isBusy={ running }
							disabled={ running || selectedIds.length === 0 }
						>
							{ running
								? sprintf(
									/* translators: %d: number of selected repair items currently running. */
									__( 'Running %d…', 'buddyboss' ),
									selectedIds.length
								)
								: __( 'Run Repairs', 'buddyboss' ) }
						</Button>
						{ running && (
							<Button
								variant="secondary"
								onClick={ handleCancel }
							>
								{ __( 'Cancel', 'buddyboss' ) }
							</Button>
						) }
					</div>
				</div>
			</div>
		</>
	);
}
