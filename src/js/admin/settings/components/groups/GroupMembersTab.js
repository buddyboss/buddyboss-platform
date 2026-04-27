/**
 * BuddyBoss Admin Settings 2.0 - Group Members Tab
 *
 * Custom component for the Members tab in the Group Edit Modal.
 * Displays members in per-role sections (Organizers, Moderators, Members,
 * Banned) with independent pagination for each section.
 *
 * All changes are collected locally and only committed when the parent
 * modal's Save button is clicked (via saveRef).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, useCallback, useMemo } from '@wordpress/element';
import {
	Button,
	SelectControl,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { getGroupMembers, updateGroupMember, ajaxFetch } from '../../utils/ajax';
import { safeUrl } from '../../utils/sanitize';

/**
 * Role options for the member role select.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var roleOptions = [
	{ label: __( 'Organizer', 'buddyboss' ), value: 'admin' },
	{ label: __( 'Moderator', 'buddyboss' ), value: 'mod' },
	{ label: __( 'Member', 'buddyboss' ), value: 'member' },
	{ label: __( 'Banned', 'buddyboss' ), value: 'banned' },
];

/**
 * Role section keys in display order.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var roleSections = [
	{ key: 'admin' },
	{ key: 'mod' },
	{ key: 'member' },
	{ key: 'banned' },
];

/**
 * Default per-page count matching legacy admin.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {number}
 */
var PER_PAGE = 10;

/**
 * Group Members Tab Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props            Component props.
 * @param {number}   props.groupId    Group ID.
 * @param {Function} props.setNotice  Function to show notices.
 * @param {Object}   props.saveRef    Ref object — parent sets saveRef.current to call our save.
 * @returns {JSX.Element} Members tab.
 */
export function GroupMembersTab( { groupId, setNotice, saveRef } ) {
	// Per-role fetched members.
	var roleMembersState = useState( { admin: [], mod: [], member: [], banned: [] } );
	var roleMembers = roleMembersState[ 0 ];
	var setRoleMembers = roleMembersState[ 1 ];

	// Per-role total counts (from server).
	var roleTotalsState = useState( { admin: 0, mod: 0, member: 0, banned: 0 } );
	var roleTotals = roleTotalsState[ 0 ];
	var setRoleTotals = roleTotalsState[ 1 ];

	// Per-role loading state.
	var roleLoadingState = useState( { admin: true, mod: true, member: true, banned: true } );
	var roleLoading = roleLoadingState[ 0 ];
	var setRoleLoading = roleLoadingState[ 1 ];

	// Per-role current page.
	var rolePagesState = useState( { admin: 1, mod: 1, member: 1, banned: 1 } );
	var rolePages = rolePagesState[ 0 ];
	var setRolePages = rolePagesState[ 1 ];

	// Pending local changes (not yet saved to server).
	var pendingAddsState = useState( [] );
	var pendingAdds = pendingAddsState[ 0 ];
	var setPendingAdds = pendingAddsState[ 1 ];

	var pendingRemovesState = useState( [] );
	var pendingRemoves = pendingRemovesState[ 0 ];
	var setPendingRemoves = pendingRemovesState[ 1 ];

	// Map of userId → newRole for pending role changes.
	var pendingRoleChangesState = useState( {} );
	var pendingRoleChanges = pendingRoleChangesState[ 0 ];
	var setPendingRoleChanges = pendingRoleChangesState[ 1 ];

	// Autocomplete state.
	var searchInputState = useState( '' );
	var searchInput = searchInputState[ 0 ];
	var setSearchInput = searchInputState[ 1 ];

	var suggestionsState = useState( [] );
	var suggestions = suggestionsState[ 0 ];
	var setSuggestions = suggestionsState[ 1 ];

	var isSearchingState = useState( false );
	var isSearching = isSearchingState[ 0 ];
	var setIsSearching = isSearchingState[ 1 ];

	var showSuggestionsState = useState( false );
	var showSuggestions = showSuggestionsState[ 0 ];
	var setShowSuggestions = showSuggestionsState[ 1 ];

	// Staged user selected from autocomplete, pending "+ Add" click.
	var selectedUserState = useState( null );
	var selectedUser = selectedUserState[ 0 ];
	var setSelectedUser = selectedUserState[ 1 ];

	var searchTimerRef = useRef( null );
	var searchAbortRef = useRef( null );
	var blurTimerRef = useRef( null );

	// One AbortController per role.
	var abortRefs = useRef( { admin: null, mod: null, member: null, banned: null } );

	// Keep refs to latest pending state for the save callback.
	var pendingAddsRef = useRef( pendingAdds );
	var pendingRemovesRef = useRef( pendingRemoves );
	var pendingRoleChangesRef = useRef( pendingRoleChanges );
	pendingAddsRef.current = pendingAdds;
	pendingRemovesRef.current = pendingRemoves;
	pendingRoleChangesRef.current = pendingRoleChanges;

	/**
	 * Fetch members for a specific role from the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} role Role key ('admin', 'mod', 'member', 'banned').
	 * @param {number} page Page number.
	 */
	var fetchRoleMembers = useCallback( function ( role, page ) {
		// Cancel any in-flight request for this role.
		if ( abortRefs.current[ role ] ) {
			abortRefs.current[ role ].abort();
		}
		abortRefs.current[ role ] = new AbortController();

		setRoleLoading( function ( prev ) {
			var next = Object.assign( {}, prev );
			next[ role ] = true;
			return next;
		} );

		getGroupMembers(
			groupId,
			{ role: role, page: page, per_page: PER_PAGE },
			{ signal: abortRefs.current[ role ].signal }
		).then( function ( response ) {
			if ( response.success && response.data ) {
				setRoleMembers( function ( prev ) {
					var next = Object.assign( {}, prev );
					next[ role ] = response.data.members || [];
					return next;
				} );

				// Each per-role response includes its own total count.
				setRoleTotals( function ( prev ) {
					var next = Object.assign( {}, prev );
					next[ role ] = response.data.total || 0;
					return next;
				} );
			}

			setRoleLoading( function ( prev ) {
				var next = Object.assign( {}, prev );
				next[ role ] = false;
				return next;
			} );
		} ).catch( function ( err ) {
			if ( err && 'AbortError' === err.name ) {
				return;
			}
			setRoleLoading( function ( prev ) {
				var next = Object.assign( {}, prev );
				next[ role ] = false;
				return next;
			} );
		} );
	}, [ groupId ] );

	// Fetch all roles on mount.
	useEffect( function () {
		roleSections.forEach( function ( section ) {
			fetchRoleMembers( section.key, 1 );
		} );

		return function () {
			// Abort all in-flight requests on unmount.
			Object.keys( abortRefs.current ).forEach( function ( key ) {
				if ( abortRefs.current[ key ] ) {
					abortRefs.current[ key ].abort();
				}
			} );
		};
	}, [ fetchRoleMembers ] );

	// Cleanup timers on unmount.
	useEffect( function () {
		return function () {
			if ( searchTimerRef.current ) {
				clearTimeout( searchTimerRef.current );
			}
			if ( blurTimerRef.current ) {
				clearTimeout( blurTimerRef.current );
			}
			if ( searchAbortRef.current ) {
				searchAbortRef.current.abort();
			}
		};
	}, [] );

	/**
	 * Handle page change for a specific role section.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} role    Role key.
	 * @param {number} newPage New page number.
	 */
	var handlePageChange = function ( role, newPage ) {
		setRolePages( function ( prev ) {
			var next = Object.assign( {}, prev );
			next[ role ] = newPage;
			return next;
		} );
		fetchRoleMembers( role, newPage );
	};

	/**
	 * Expose save function to parent via saveRef.
	 * Processes all pending changes sequentially using existing AJAX endpoints.
	 * Returns a Promise that resolves when all changes are committed.
	 */
	useEffect( function () {
		if ( ! saveRef ) {
			return;
		}

		saveRef.current = function () {
			var adds = pendingAddsRef.current;
			var removes = pendingRemovesRef.current;
			var roleChanges = pendingRoleChangesRef.current;

			// Collect all operations as promises executed sequentially.
			var operations = [];

			// Process adds — first add as member, then promote if different role.
			adds.forEach( function ( user ) {
				operations.push( function () {
					return updateGroupMember( {
						group_id: groupId,
						user_id: user.id,
						role: 'member',
						action_type: 'add',
					} ).then( function ( response ) {
						// If a non-member role was chosen, promote after adding.
						if ( response.success && user.role && 'member' !== user.role ) {
							return updateGroupMember( {
								group_id: groupId,
								user_id: user.id,
								role: user.role,
							} );
						}
						return response;
					} );
				} );
			} );

			// Process removes.
			removes.forEach( function ( userId ) {
				operations.push( function () {
					return updateGroupMember( {
						group_id: groupId,
						user_id: userId,
						role: 'remove',
					} );
				} );
			} );

			// Process role changes.
			Object.keys( roleChanges ).forEach( function ( userId ) {
				operations.push( function () {
					return updateGroupMember( {
						group_id: groupId,
						user_id: parseInt( userId, 10 ),
						role: roleChanges[ userId ],
					} );
				} );
			} );

			if ( 0 === operations.length ) {
				return Promise.resolve();
			}

			// Execute sequentially. If any operation fails the error is propagated
			// to the caller (GroupEditModal) which surfaces it as a notice. Operations
			// that already ran are NOT rolled back — the same limitation exists in the
			// legacy WP admin form which processes all changes in a single non-atomic pass.
			var errors = [];
			var chain = Promise.resolve();
			operations.forEach( function ( op ) {
				chain = chain.then( op ).catch( function ( err ) {
					errors.push( err && err.message ? err.message : String( err ) );
				} );
			} );

			return chain.then( function () {
				if ( errors.length ) {
					return Promise.reject( new Error( errors.join( ' ' ) ) );
				}
				// Clear pending state only when all operations succeeded.
				setPendingAdds( [] );
				setPendingRemoves( [] );
				setPendingRoleChanges( {} );
			} );
		};
	}, [ saveRef, groupId ] );

	/**
	 * Handle autocomplete search input change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} e Input change event.
	 */
	var handleSearchInputChange = function ( e ) {
		var val = e.target.value;
		setSearchInput( val );

		// Clear staged user when the input text changes.
		if ( selectedUser ) {
			setSelectedUser( null );
		}

		if ( searchTimerRef.current ) {
			clearTimeout( searchTimerRef.current );
		}

		if ( val.length < 2 ) {
			setSuggestions( [] );
			setShowSuggestions( false );
			return;
		}

		searchTimerRef.current = setTimeout( function () {
			// Cancel any in-flight autocomplete request.
			if ( searchAbortRef.current ) {
				searchAbortRef.current.abort();
			}
			searchAbortRef.current = new AbortController();

			setIsSearching( true );
			setShowSuggestions( true );

			// Use the Settings 2.0 member autocomplete endpoint (POST-based).
			ajaxFetch( 'bb_admin_member_autocomplete', {
				term: val,
				group_id: groupId,
			}, { signal: searchAbortRef.current.signal } ).then( function ( response ) {
				setIsSearching( false );
				if ( response.success && response.data && Array.isArray( response.data.results ) ) {
					setSuggestions( response.data.results );
				} else {
					setSuggestions( [] );
				}
			} ).catch( function ( err ) {
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				setIsSearching( false );
				setSuggestions( [] );
			} );
		}, 300 );
	};

	/**
	 * Handle selecting a user from the autocomplete suggestions.
	 * Stages the user for adding — actual add happens on "+ Add" click.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} user User object from suggestions.
	 */
	var handleSelectUser = function ( user ) {
		setSelectedUser( user );
		setSearchInput( user.label || user.name );
		setSuggestions( [] );
		setShowSuggestions( false );
	};

	/**
	 * Handle adding the staged member to the pending adds list.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleAddMember = function () {
		if ( ! selectedUser ) {
			return;
		}

		// Check if already in pending adds.
		var alreadyPending = pendingAdds.some( function ( u ) {
			return u.id === selectedUser.id;
		} );
		if ( alreadyPending ) {
			setSelectedUser( null );
			setSearchInput( '' );
			return;
		}

		// Store with default role 'member'.
		var userWithRole = Object.assign( {}, selectedUser, { role: 'member' } );
		setPendingAdds( function ( prev ) {
			return prev.concat( [ userWithRole ] );
		} );
		setSelectedUser( null );
		setSearchInput( '' );
	};

	/**
	 * Handle role change for a pending-add member.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} userId  User ID.
	 * @param {string} newRole New role.
	 */
	var handlePendingAddRoleChange = function ( userId, newRole ) {
		setPendingAdds( function ( prev ) {
			return prev.map( function ( u ) {
				if ( u.id === userId ) {
					return Object.assign( {}, u, { role: newRole } );
				}
				return u;
			} );
		} );
	};

	/**
	 * Handle role change for a member (local only).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} userId  User ID.
	 * @param {string} newRole New role.
	 */
	var handleRoleChange = function ( userId, newRole ) {
		setPendingRoleChanges( function ( prev ) {
			var next = {};
			Object.keys( prev ).forEach( function ( k ) {
				next[ k ] = prev[ k ];
			} );
			next[ userId ] = newRole;
			return next;
		} );
	};

	/**
	 * Handle removing a fetched member (local only).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} userId User ID.
	 */
	var handleRemoveMember = function ( userId ) {
		setPendingRemoves( function ( prev ) {
			return prev.concat( [ userId ] );
		} );
	};

	/**
	 * Handle removing a pending-add member from the local list.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} userId User ID.
	 */
	var handleRemovePendingAdd = function ( userId ) {
		setPendingAdds( function ( prev ) {
			return prev.filter( function ( u ) {
				return u.id !== userId;
			} );
		} );
	};

	/**
	 * Pre-compute display members for all role sections.
	 * Memoized to avoid redundant computation on every render.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @type {Object} Map of role key to array of display member objects.
	 */
	var displayMembersByRole = useMemo( function () {
		var result = {};

		// Build a lookup of userId → member across all fetched role sections
		// for efficient cross-section role-change resolution.
		var allFetchedById = {};
		var allRoleKeys = Object.keys( roleMembers );
		for ( var r = 0; r < allRoleKeys.length; r++ ) {
			var rk = allRoleKeys[ r ];
			var rMembers = roleMembers[ rk ] || [];
			for ( var m = 0; m < rMembers.length; m++ ) {
				allFetchedById[ rMembers[ m ].user_id ] = rMembers[ m ];
			}
		}

		roleSections.forEach( function ( section ) {
			var role = section.key;
			var members = [];

			// Fetched members for this role (minus pending removes, with role overrides).
			( roleMembers[ role ] || [] ).forEach( function ( member ) {
				if ( -1 !== pendingRemoves.indexOf( member.user_id ) ) {
					return;
				}

				var effectiveRole = pendingRoleChanges[ member.user_id ] || member.role;

				// Skip if role changed to a different section.
				if ( effectiveRole !== role ) {
					return;
				}

				members.push( {
					user_id: member.user_id,
					name: member.name,
					avatar_url: member.avatar_url,
					profile_url: member.profile_url,
					role: effectiveRole,
					is_creator: member.is_creator,
					is_sole_admin: !! member.is_sole_admin,
					is_pending: false,
				} );
			} );

			// Members role-changed INTO this section from other sections.
			var addedIds = {};
			members.forEach( function ( m ) {
				addedIds[ m.user_id ] = true;
			} );

			Object.keys( pendingRoleChanges ).forEach( function ( userId ) {
				var newRole = pendingRoleChanges[ userId ];
				if ( newRole !== role ) {
					return;
				}

				var numUserId = parseInt( userId, 10 );

				// Skip if already in this section's fetched list.
				if ( addedIds[ numUserId ] ) {
					return;
				}

				// Look up from the pre-built index.
				var foundMember = allFetchedById[ numUserId ];
				if ( ! foundMember ) {
					return;
				}

				// Skip if removed.
				if ( -1 !== pendingRemoves.indexOf( foundMember.user_id ) ) {
					return;
				}

				addedIds[ numUserId ] = true;
				members.push( {
					user_id: foundMember.user_id,
					name: foundMember.name,
					avatar_url: foundMember.avatar_url,
					profile_url: foundMember.profile_url,
					role: newRole,
					is_creator: foundMember.is_creator,
					is_sole_admin: false,
					is_pending: false,
				} );
			} );

			// Pending adds for this role.
			pendingAdds.forEach( function ( user ) {
				var addRole = user.role || 'member';
				if ( addRole !== role ) {
					return;
				}
				members.push( {
					user_id: user.id,
					name: user.label || user.name,
					avatar_url: user.image || '',
					profile_url: '',
					role: addRole,
					is_creator: false,
					is_sole_admin: false,
					is_pending: true,
				} );
			} );

			result[ role ] = members;
		} );

		return result;
	}, [ roleMembers, pendingRemoves, pendingRoleChanges, pendingAdds ] );

	/**
	 * Check if all role sections are done loading (initial load).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @type {boolean}
	 */
	var isInitialLoading = roleLoading.admin && roleLoading.mod && roleLoading.member && roleLoading.banned;

	/**
	 * Build an array of page numbers to display with ellipsis.
	 *
	 * Shows at most 5 page buttons around the current page plus
	 * first/last pages, using null as an ellipsis placeholder.
	 * Example: [1, 2, 3, 4, 5, null, 15] or [1, null, 5, 6, 7, null, 15].
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} currentPage Current active page.
	 * @param {number} totalPages  Total number of pages.
	 * @return {Array} Array of page numbers and null (ellipsis) values.
	 */
	var getPageNumbers = function ( currentPage, totalPages ) {
		// Show all pages when few enough.
		if ( totalPages <= 7 ) {
			var all = [];
			for ( var i = 1; i <= totalPages; i++ ) {
				all.push( i );
			}
			return all;
		}

		var pages = [];
		var rangeStart = Math.max( 2, currentPage - 1 );
		var rangeEnd = Math.min( totalPages - 1, currentPage + 1 );

		// Ensure at least 3 pages in the middle range.
		if ( rangeEnd - rangeStart < 2 ) {
			if ( rangeStart <= 2 ) {
				rangeEnd = Math.min( totalPages - 1, rangeStart + 2 );
			} else {
				rangeStart = Math.max( 2, rangeEnd - 2 );
			}
		}

		pages.push( 1 );

		if ( rangeStart > 2 ) {
			pages.push( null ); // Left ellipsis.
		}

		for ( var p = rangeStart; p <= rangeEnd; p++ ) {
			pages.push( p );
		}

		if ( rangeEnd < totalPages - 1 ) {
			pages.push( null ); // Right ellipsis.
		}

		pages.push( totalPages );

		return pages;
	};

	/**
	 * Render numbered pagination controls for a role section.
	 *
	 * Renders: < 1 2 3 4 5 ... N > with the current page highlighted.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} role       Role key.
	 * @param {number} totalCount Total members for this role.
	 * @return {JSX.Element|null} Pagination controls or null.
	 */
	var renderPagination = function ( role, totalCount ) {
		var totalPages = Math.ceil( totalCount / PER_PAGE );
		var currentPage = rolePages[ role ] || 1;

		if ( totalPages <= 1 ) {
			return null;
		}

		var pageNumbers = getPageNumbers( currentPage, totalPages );

		return (
			<div className="bb-group-members-tab__pagination" role="navigation" aria-label={ __( 'Pagination', 'buddyboss' ) }>
				<button
					type="button"
					className="bb-group-members-tab__page-arrow"
					disabled={ 1 === currentPage }
					onClick={ function () {
						handlePageChange( role, currentPage - 1 );
					} }
					aria-label={ __( 'Previous page', 'buddyboss' ) }
				>
					<i className="bb-icons-rl bb-icons-rl-caret-left" aria-hidden="true"></i>
				</button>

				<div className="bb-group-members-tab__page-numbers">
					{ pageNumbers.map( function ( pageNum, idx ) {
						if ( null === pageNum ) {
							return (
								<span key={ 'ellipsis-' + idx } className="bb-group-members-tab__page-ellipsis" aria-hidden="true">
									&hellip;
								</span>
							);
						}
						return (
							<button
								key={ pageNum }
								type="button"
								className={ 'bb-group-members-tab__page-number' + ( pageNum === currentPage ? ' bb-group-members-tab__page-number--active' : '' ) }
								onClick={ function () {
									if ( pageNum !== currentPage ) {
										handlePageChange( role, pageNum );
									}
								} }
								aria-label={ pageNum.toString() }
								aria-current={ pageNum === currentPage ? 'page' : undefined }
							>
								{ pageNum }
							</button>
						);
					} ) }
				</div>

				<button
					type="button"
					className="bb-group-members-tab__page-arrow"
					disabled={ currentPage >= totalPages }
					onClick={ function () {
						handlePageChange( role, currentPage + 1 );
					} }
					aria-label={ __( 'Next page', 'buddyboss' ) }
				>
					<i className="bb-icons-rl bb-icons-rl-caret-right" aria-hidden="true"></i>
				</button>
			</div>
		);
	};

	/**
	 * Render a single member row.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} member Member display object.
	 * @return {JSX.Element} Member row.
	 */
	var renderMemberRow = function ( member ) {
		return (
			<div key={ member.user_id } className={ 'bb-group-members-tab__member-row' + ( member.is_pending ? ' bb-group-members-tab__member-row--pending' : '' ) }>
				<div className="bb-group-members-tab__member-pill">
					{ member.avatar_url && (
						<img
							src={ safeUrl( member.avatar_url ) }
							alt={ member.name }
							className="bb-group-members-tab__member-avatar"
						/>
					) }
					{ member.profile_url ? (
						<a href={ safeUrl( member.profile_url ) } target="_blank" rel="noopener noreferrer" className="bb-group-members-tab__member-name">
							{ member.name }
						</a>
					) : (
						<span className="bb-group-members-tab__member-name">
							{ member.name }
						</span>
					) }
					{ ! member.is_creator && ! member.is_sole_admin && (
						<button
							type="button"
							className="bb-group-members-tab__remove-btn"
							onClick={ function () {
								if ( member.is_pending ) {
									handleRemovePendingAdd( member.user_id );
								} else {
									handleRemoveMember( member.user_id );
								}
							} }
							title={ __( 'Remove member', 'buddyboss' ) }
						>
							<i className="bb-icons-rl bb-icons-rl-x"></i>
						</button>
					) }
				</div>
				<div className="bb-group-members-tab__member-actions">
					<SelectControl
						value={ member.role }
						options={ roleOptions }
						disabled={ member.is_sole_admin }
						onChange={ function ( newRole ) {
							if ( member.is_pending ) {
								handlePendingAddRoleChange( member.user_id, newRole );
							} else {
								handleRoleChange( member.user_id, newRole );
							}
						} }
						__nextHasNoMarginBottom
					/>
				</div>
			</div>
		);
	};

	return (
		<div className="bb-group-members-tab">
			{ /* Add Member Autocomplete */ }
			<div className="bb-group-members-tab__add-member">
				<label className="bb-admin-meta-field__label">
					{ __( 'Add New Members', 'buddyboss' ) }
				</label>
				<div className="bb-group-members-tab__add-row">
					<div className="bb-group-members-tab__autocomplete-wrapper">
						<input
							type="text"
							value={ searchInput }
							onChange={ handleSearchInputChange }
							onFocus={ function () {
								if ( suggestions.length > 0 ) {
									setShowSuggestions( true );
								}
							} }
							onBlur={ function () {
								// Delay to allow click on suggestion.
								blurTimerRef.current = setTimeout( function () {
									setShowSuggestions( false );
								}, 200 );
							} }
							placeholder={ __( 'Type username to add', 'buddyboss' ) }
							className="bb-group-members-tab__search-input"
							role="combobox"
							aria-autocomplete="list"
							aria-expanded={ showSuggestions }
							aria-label={ __( 'Search members', 'buddyboss' ) }
						/>
						{ showSuggestions && (
						<div className="bb-group-members-tab__suggestions" role="listbox" aria-label={ __( 'User suggestions', 'buddyboss' ) }>
							{ isSearching ? (
								<div className="bb-group-members-tab__suggestions-loading">
									<Spinner />
								</div>
							) : 0 === suggestions.length ? (
								<div className="bb-group-members-tab__suggestions-empty">
									{ __( 'No users found.', 'buddyboss' ) }
								</div>
							) : (
								suggestions.map( function ( user ) {
									return (
										<button
											key={ user.id }
											type="button"
											role="option"
											className="bb-group-members-tab__suggestion-item"
											onMouseDown={ function ( e ) {
												e.preventDefault();
												handleSelectUser( user );
											} }
										>
											{ user.image && (
												<img
													src={ safeUrl( user.image ) }
													alt={ user.label || user.name }
													className="bb-group-members-tab__suggestion-avatar"
												/>
											) }
											<span className="bb-group-members-tab__suggestion-name">
												{ user.label || user.name }
											</span>
										</button>
									);
								} )
							) }
						</div>
						) }
					</div>
					<Button
						variant="secondary"
						className="bb-group-members-tab__add-btn"
						disabled={ ! selectedUser }
						onClick={ handleAddMember }
					>
						<i className="bb-icons-rl-plus"></i>
						{ __( 'Add', 'buddyboss' ) }
					</Button>
				</div>
			</div>

			{ /* Members List — Per-Role Sections */ }
			{ isInitialLoading ? (
				<div className="bb-group-members-tab__loading">
					<Spinner />
				</div>
			) : (
				<div className="bb-group-members-tab__list">
					{ roleSections.map( function ( section ) {
						var displayMembers = displayMembersByRole[ section.key ] || [];
						var sectionTotal = roleTotals[ section.key ] || 0;
						var sectionIsLoading = roleLoading[ section.key ];

						// Hide empty sections entirely.
						if ( 0 === displayMembers.length && 0 === sectionTotal && ! sectionIsLoading ) {
							return null;
						}

						return (
							<div key={ section.key } className="bb-group-members-tab__role-group">
								{ sectionIsLoading ? (
									<div className="bb-group-members-tab__section-loading">
										<Spinner />
									</div>
								) : displayMembers.length > 0 ? (
									displayMembers.map( renderMemberRow )
								) : null }

								{ ! sectionIsLoading && renderPagination( section.key, sectionTotal ) }
							</div>
						);
					} ) }
				</div>
			) }
		</div>
	);
}
