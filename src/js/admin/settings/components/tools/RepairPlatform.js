/**
 * Repair Platform — Settings 2.0 React panel.
 *
 * Replaces the legacy `?page=bp-tools` Repair Community + Repair Forums
 * sub-pages with a unified panel showing the merged repair-items list
 * grouped by category (members & profiles, groups, forums & discussions,
 * connections, activity & reactions). Per Figma `rapair-platform.png`.
 *
 * Each repair item carries its dispatching AJAX endpoint + nonce in the
 * localized config (`window.bbToolsRepairConfig`). Community items go through
 * `bp_admin_repair_tools_wrapper_function` with the `bp-do-counts` nonce;
 * forum items go through `bp_admin_forum_repair_tools_wrapper_function` with
 * the `bbpress-do-counts` nonce. Both endpoints + both nonces are LOCKED BC
 * — third parties depend on them, so the React component must dispatch each
 * item to the right endpoint based on its `endpoint` + `nonce` metadata.
 *
 * Multisite: when running in Network Admin context, a site selector dropdown
 * is rendered above the checkbox tree and the selected `site_id` is included
 * in every AJAX payload.
 *
 * @since BuddyBoss [BBVERSION]
 */
import { useState, useMemo, useCallback, useRef, useEffect } from '@wordpress/element';
import { Button, CheckboxControl, SelectControl, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import RepairPlatformModal from './RepairPlatformModal';

export default function RepairPlatform() {
	const config = ( window.bbToolsRepairConfig ) || {};
	const repairItems = config.repairItems || [];
	const itemCategories = config.itemCategories || [];
	const isNetworkAdmin = !! config.isNetworkAdmin;
	const networkSites = config.networkSites || [];
	const nonces = {
		repair: config.repairNonce || '',
		forumRepair: config.forumRepairNonce || '',
	};
	const ajaxUrl = config.ajaxUrl || ( window.ajaxurl );

	const [ selectedIds, setSelectedIds ] = useState( function () {
		return new Set();
	} );
	const [ selectedSiteId, setSelectedSiteId ] = useState( isNetworkAdmin ? '' : '0' );
	const [ modal, setModal ] = useState( null );
	const [ results, setResults ] = useState( [] );
	const [ error, setError ] = useState( null );
	const abortRef = useRef( null );

	useEffect( function () {
		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

	const groupedItems = useMemo( function () {
		const groups = {};
		itemCategories.forEach( function ( cat ) {
			groups[ cat.id ] = {
				id: cat.id,
				label: cat.label,
				order: cat.order || 0,
				items: [],
			};
		} );
		repairItems.forEach( function ( item ) {
			const cat = item.category || 'connections';
			if ( ! groups[ cat ] ) {
				groups[ cat ] = {
					id: cat,
					label: __( 'Other', 'buddyboss-platform' ),
					order: 999,
					items: [],
				};
			}
			groups[ cat ].items.push( item );
		} );
		return Object.values( groups )
			.filter( function ( g ) {
				return g.items.length > 0;
			} )
			.sort( function ( a, b ) {
				return ( a.order || 0 ) - ( b.order || 0 );
			} );
	}, [ repairItems, itemCategories ] );

	const handleToggle = useCallback( function ( id, checked ) {
		setSelectedIds( function ( prev ) {
			const next = new Set( prev );
			if ( checked ) {
				next.add( id );
			} else {
				next.delete( id );
			}
			return next;
		} );
	}, [] );

	const handleSelectAll = useCallback( function ( checked ) {
		setSelectedIds(
			checked
				? new Set( repairItems.map( function ( i ) {
					return i.id;
				} ) )
				: new Set()
		);
	}, [ repairItems ] );

	const handleRepair = useCallback( async function () {
		if ( 0 === selectedIds.size ) {
			return;
		}
		if ( isNetworkAdmin && '' === selectedSiteId ) {
			setError( __( 'Please select a site before repairing.', 'buddyboss-platform' ) );
			return;
		}

		setError( null );
		setResults( [] );
		setModal( 'progress' );

		const controller = new AbortController();
		abortRef.current = controller;

		const collected = [];
		const itemsById = {};
		repairItems.forEach( function ( i ) {
			itemsById[ i.id ] = i;
		} );

		for ( const itemId of selectedIds ) {
			const item = itemsById[ itemId ];
			if ( ! item ) {
				continue;
			}
			const nonce = nonces[ item.nonce ] || nonces.repair;
			const endpoint = item.endpoint || 'bp_admin_repair_tools_wrapper_function';

			try {
				// Drive paginated repair handlers to completion: several legacy
				// callbacks (xprofile resync, display-name update, profile-set
				// repair, member-type assign) process users in batches of 50 and
				// return `status: 'running'` + `offset: N` until done. The legacy
				// admin page loops via jQuery; mirror that here so the React panel
				// doesn't strand the user on the first batch ("50 members updated
				// successfully" with no Complete state and no operation label).
				// Hard cap iterations to defend against a misbehaving handler that
				// never reports completion.
				const MAX_PAGINATED_ITERATIONS = 10000;
				let offset = 0;
				let json = null;

				for ( let i = 0; i < MAX_PAGINATED_ITERATIONS; i++ ) {
					const fd = new FormData();
					fd.append( 'action', endpoint );
					fd.append( 'nonce', nonce );
					fd.append( 'type', itemId );
					if ( offset > 0 ) {
						fd.append( 'offset', String( offset ) );
					}
					if ( isNetworkAdmin && selectedSiteId ) {
						fd.append( 'site_id', selectedSiteId );
					}

					const res = await fetch( ajaxUrl, {
						method: 'POST',
						body: fd,
						credentials: 'same-origin',
						signal: controller.signal,
					} );
					json = await res.json();

					if ( ! json || ! json.success ) {
						break;
					}
					const data = json.data || {};
					if ( 'running' !== data.status ) {
						break;
					}
					const nextOffset = ( typeof data.offset === 'number' ) ? data.offset : Number( data.offset );
					if ( ! ( nextOffset > offset ) ) {
						// No forward progress — bail to avoid an infinite loop.
						break;
					}
					offset = nextOffset;
				}

				collected.push( {
					id: itemId,
					label: item.label,
					success: !! ( json && json.success ),
					message: ( json && json.data && json.data.message )
						|| ( json && json.success
							? __( 'Repaired', 'buddyboss-platform' )
							: __( 'Failed', 'buddyboss-platform' ) ),
					// Server-extracted fields from `bb_admin_repair_extract_count_summary()`
					// — present on all responses since the Settings 2.0 Tools release.
					count:   ( json && json.data && typeof json.data.count !== 'undefined' ) ? json.data.count : null,
					summary: ( json && json.data && json.data.summary ) || '',
					// Optional pre-rendered HTML row text (e.g. emails responses include
					// a clickable "View Emails." link). The modal sanitizes via DOMPurify
					// before injecting. Plain `summary` is used when this is absent.
					summary_html: ( json && json.data && json.data.summary_html ) || '',
				} );
			} catch ( e ) {
				if ( 'AbortError' === e.name ) {
					setModal( null );
					return;
				}
				collected.push( {
					id: itemId,
					label: item.label,
					success: false,
					message: __( 'Network error', 'buddyboss-platform' ),
				} );
			}
		}

		setResults( collected );
		setModal( 'complete' );
	}, [ selectedIds, selectedSiteId, isNetworkAdmin, repairItems, nonces, ajaxUrl ] );

	const handleCancel = useCallback( function () {
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		setModal( null );
	}, [] );

	const handleCloseComplete = useCallback( function () {
		setModal( null );
		setResults( [] );
		setSelectedIds( new Set() );
	}, [] );

	const allChecked = selectedIds.size === repairItems.length && repairItems.length > 0;

	return (
		<div className="bb-tools-repair-platform">
			{ isNetworkAdmin && (
				<div className="bb-tools-repair-platform__site-selector">
					<SelectControl
						label={ __( 'Select a site to repair', 'buddyboss-platform' ) }
						value={ selectedSiteId }
						options={ [ { value: '', label: __( '— Select a site —', 'buddyboss-platform' ) } ].concat(
							networkSites.map( function ( s ) {
								return {
									value: String( s.blog_id ),
									label: s.domain + s.path,
								};
							} )
						) }
						onChange={ setSelectedSiteId }
					/>
				</div>
			) }

			{ error && (
				<Notice
					status="error"
					isDismissible={ true }
					onRemove={ function () {
						setError( null );
					} }
				>
					{ error }
				</Notice>
			) }

			<div className="bb-tools-repair-platform__select-all">
				<CheckboxControl
					label={ __( 'Select All', 'buddyboss-platform' ) }
					checked={ allChecked }
					onChange={ handleSelectAll }
				/>
			</div>

			{ groupedItems.map( function ( group ) {
				return (
					<div key={ group.id } className="bb-tools-repair-platform__section">
						<div className="bb-tools-repair-platform__section-heading">
							<span className="bb-tools-repair-platform__section-title">{ group.label }</span>
						</div>
						<div className="bb-tools-repair-platform__section-items">
							{ group.items.map( function ( item ) {
								return (
									<CheckboxControl
										key={ item.id }
										label={ item.label }
										checked={ selectedIds.has( item.id ) }
										onChange={ function ( checked ) {
											handleToggle( item.id, checked );
										} }
									/>
								);
							} ) }
						</div>
					</div>
				);
			} ) }

			<div className="bb-tools-repair-platform__actions">
				<Button
					variant="primary"
					onClick={ handleRepair }
					disabled={ 0 === selectedIds.size }
				>
					{ __( 'Repair Items', 'buddyboss-platform' ) }
				</Button>
			</div>

			{ 'progress' === modal && (
				<RepairPlatformModal variant="progress" onCancel={ handleCancel } />
			) }
			{ 'complete' === modal && (
				<RepairPlatformModal
					variant="complete"
					results={ results }
					onClose={ handleCloseComplete }
				/>
			) }
		</div>
	);
}
