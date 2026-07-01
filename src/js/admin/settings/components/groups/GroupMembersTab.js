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
import { __, sprintf } from '@wordpress/i18n';

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

	// Role filter dropdown (Figma 2026-05-08): 'all' | 'admin' | 'mod' | 'member' | 'banned'.
	// Drives which role section(s) are rendered in the member list.
	var roleFilterState = useState( 'all' );
	var roleFilter = roleFilterState[ 0 ];
	var setRoleFilter = roleFilterState[ 1 ];

	// Server-side text filter for the member list (Figma 2026-05-08). Distinct
	// from `searchInput` above which is the autocomplete for ADDING new members.
	// Debounced + dispatched by the effect below; the term is forwarded to BP's
	// `groups_get_group_members()` `search_terms` arg so all per-role filter
	// chains (moderation, privacy, third-party hooks) apply unchanged.
	var memberFilterQueryState = useState( '' );
	var memberFilterQuery = memberFilterQueryState[ 0 ];
	var setMemberFilterQuery = memberFilterQueryState[ 1 ];

	var searchTimerRef = useRef( null );
	var searchAbortRef = useRef( null );
	var blurTimerRef = useRef( null );

	// Debounce timer for the member-list server-side filter (Figma 2026-05-08).
	// Distinct from `searchTimerRef` above which debounces the autocomplete used
	// to ADD new members.
	var searchDebounceRef = useRef( null );

	// Per-role tracker of the search term that produced each section's
	// currently-loaded members. A role whose entry differs from the current
	// `normalizedMemberFilter` is "stale" and needs a refetch. Per-role
	// tracking (rather than a single string) closes this race:
	//   1. role-filter='admin', user types 'mike'  → only admin refetched
	//   2. role-filter='all'                       → mod/member/banned still
	//      hold their pre-search data unless we know they're stale
	// With per-role tracking, widening the role-filter refetches only the
	// roles that haven't yet seen the active search term.
	var lastFiredSearchByRoleRef = useRef( { admin: '', mod: '', member: '', banned: '' } );

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
	 * @param {string} role   Role key ('admin', 'mod', 'member', 'banned').
	 * @param {number} page   Page number.
	 * @param {string} search Optional search term — passed straight to BP's
	 *                        `groups_get_group_members()` `search_terms` arg
	 *                        on the server. Empty string means no filter.
	 */
	var fetchRoleMembers = useCallback( function ( role, page, search ) {
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
			{ role: role, page: page, per_page: PER_PAGE, search: search || '' },
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

			// Collect all operations as promises executed sequentially, ordered so
			// admin-INCREASING changes (adds + promotions to Organizer) run BEFORE
			// admin-REDUCING ones (removes + demotions). The server guards each
			// remove/demote against the live DB ("can't remove the only admin"), so a
			// promote-then-remove done in one save must send the promote first — else
			// the still-sole old admin is wrongly blocked. Legacy's single form pass
			// had the same net effect.
			var operations = [];

			// Phase 1a — adds (add as member, then promote if a higher role was chosen).
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

			// Phase 1b — promotions TO Organizer grow the admin pool first.
			Object.keys( roleChanges ).forEach( function ( userId ) {
				if ( 'admin' !== roleChanges[ userId ] ) {
					return;
				}
				operations.push( function () {
					return updateGroupMember( {
						group_id: groupId,
						user_id: parseInt( userId, 10 ),
						role: 'admin',
					} );
				} );
			} );

			// Phase 2a — removes.
			removes.forEach( function ( userId ) {
				operations.push( function () {
					return updateGroupMember( {
						group_id: groupId,
						user_id: userId,
						role: 'remove',
					} );
				} );
			} );

			// Phase 2b — remaining role changes (demotions, Moderator/Member/Banned).
			Object.keys( roleChanges ).forEach( function ( userId ) {
				if ( 'admin' === roleChanges[ userId ] ) {
					return;
				}
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

		// Recompute "sole admin" from the CURRENT (pending-applied) admin count
		// instead of the static server flag. Promoting another member to Organizer
		// in the same session drops the last admin's lock, so the creator can be
		// managed/removed without a save-and-reopen — matching legacy, which lets
		// you promote + remove in one save.
		var currentAdminCount = ( result.admin || [] ).length;
		roleSections.forEach( function ( section ) {
			( result[ section.key ] || [] ).forEach( function ( m ) {
				m.is_sole_admin = ( 'admin' === m.role && currentAdminCount <= 1 );
			} );
		} );

		return result;
	}, [ roleMembers, pendingRemoves, pendingRoleChanges, pendingAdds ] );

	// Effective per-role total after pending changes. roleTotals is the raw server
	// count, which still counts a member who has been moved out of a section (or
	// removed) in this unsaved session — that's why an emptied section kept showing
	// an empty bordered container. This adjusts the count so a section that pending
	// changes have emptied collapses, while a large multi-page section (whose
	// current page happens to be empty) still renders + paginates.
	var effectiveRoleTotals = useMemo( function () {
		var byId = {};
		Object.keys( roleMembers ).forEach( function ( rk ) {
			( roleMembers[ rk ] || [] ).forEach( function ( m ) {
				byId[ m.user_id ] = m;
			} );
		} );

		var totals = {};
		roleSections.forEach( function ( section ) {
			var total = roleTotals[ section.key ] || 0;

			Object.keys( pendingRoleChanges ).forEach( function ( uid ) {
				var orig = byId[ uid ] ? byId[ uid ].role : null;
				var next = pendingRoleChanges[ uid ];
				if ( orig === section.key && next !== section.key ) {
					total -= 1; // moved out of this section
				} else if ( orig !== section.key && next === section.key ) {
					total += 1; // moved into this section
				}
			} );

			pendingRemoves.forEach( function ( uid ) {
				if ( byId[ uid ] && byId[ uid ].role === section.key ) {
					total -= 1;
				}
			} );

			pendingAdds.forEach( function ( user ) {
				if ( ( user.role || 'member' ) === section.key ) {
					total += 1;
				}
			} );

			totals[ section.key ] = Math.max( 0, total );
		} );

		return totals;
	}, [ roleMembers, roleTotals, pendingRoleChanges, pendingRemoves, pendingAdds ] );

	/**
	 * Server-side counts per role (used in the role-filter dropdown labels:
	 * "All (23)", "Organizer (1)", etc.). Sourced from `roleTotals` so the
	 * counts reflect every member of the group, not just the currently-loaded
	 * page within each section.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @type {Object}
	 */
	var roleCounts = useMemo( function () {
		var totalAll = 0;
		roleSections.forEach( function ( s ) {
			totalAll += roleTotals[ s.key ] || 0;
		} );
		return {
			all:    totalAll,
			admin:  roleTotals.admin || 0,
			mod:    roleTotals.mod || 0,
			member: roleTotals.member || 0,
			banned: roleTotals.banned || 0,
		};
	}, [ roleTotals ] );

	/**
	 * Trimmed/lowercased query for the server-side member-list search.
	 * Empty when no filter is active.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @type {string}
	 */
	var normalizedMemberFilter = useMemo( function () {
		return ( memberFilterQuery || '' ).trim().toLowerCase();
	}, [ memberFilterQuery ] );

	/**
	 * Debounced server-side member search.
	 *
	 * When the user types in the filter input, fire 1–4 parallel role fetches
	 * (one per visible role section) with the search term, after a 300ms idle
	 * window. The server applies the term via BP's `groups_get_group_members()`
	 * `search_terms` arg, so all moderation/privacy/third-party filters keep
	 * firing per role exactly as on initial load.
	 *
	 * Per-role AbortController in `fetchRoleMembers` cancels any in-flight
	 * request for the same role on each new fetch, so rapid typing never
	 * stacks pending requests.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	useEffect( function () {
		// Cancel any pending debounce.
		if ( searchDebounceRef.current ) {
			clearTimeout( searchDebounceRef.current );
			searchDebounceRef.current = null;
		}

		// Decide which roles to (re)query. With role-filter narrowed to a
		// specific role, only that one needs to refetch; with 'all' selected
		// we cover every section so search hits across the whole group.
		var rolesToFetch = 'all' === roleFilter
			? [ 'admin', 'mod', 'member', 'banned' ]
			: [ roleFilter ];

		// Within rolesToFetch, only refetch the roles whose currently-loaded
		// data was produced by a different search term than the active one.
		// This handles two scenarios with the same logic:
		//   - User typed in the search box → all visible roles are stale.
		//   - User widened the role filter → only the roles that didn't
		//     receive the active search are stale; already-up-to-date roles
		//     skip the refetch.
		var staleRoles = rolesToFetch.filter( function ( r ) {
			return lastFiredSearchByRoleRef.current[ r ] !== normalizedMemberFilter;
		} );

		if ( 0 === staleRoles.length ) {
			return undefined;
		}

		searchDebounceRef.current = setTimeout( function () {
			staleRoles.forEach( function ( role ) {
				lastFiredSearchByRoleRef.current[ role ] = normalizedMemberFilter;
				fetchRoleMembers( role, 1, normalizedMemberFilter );
			} );
			// Reset stale roles' page state to 1 — search replaces the result
			// set (or restores page 1 when the search clears).
			setRolePages( function ( prev ) {
				var next = Object.assign( {}, prev );
				staleRoles.forEach( function ( r ) {
					next[ r ] = 1;
				} );
				return next;
			} );
		}, 300 );

		return function () {
			if ( searchDebounceRef.current ) {
				clearTimeout( searchDebounceRef.current );
				searchDebounceRef.current = null;
			}
		};
	}, [ normalizedMemberFilter, roleFilter, fetchRoleMembers ] );

	/**
	 * Check if all role sections are done loading (initial load).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @type {boolean}
	 */
	var isInitialLoading = roleLoading.admin && roleLoading.mod && roleLoading.member && roleLoading.banned;

	/**
	 * Whether the tab has completed its first load. Once true, stays true
	 * for the lifetime of the component. Used to gate the filter row and
	 * top-level spinner so subsequent refetches (search, role-filter,
	 * pagination) keep the filter UI mounted instead of remounting it on
	 * every AJAX round-trip — which was causing the search/filter row to
	 * flicker out of view between keystrokes.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @type {boolean}
	 */
	var hasLoadedOnceState = useState( false );
	var hasLoadedOnce = hasLoadedOnceState[ 0 ];
	var setHasLoadedOnce = hasLoadedOnceState[ 1 ];

	useEffect( function () {
		if ( ! hasLoadedOnce && ! isInitialLoading ) {
			setHasLoadedOnce( true );
		}
	}, [ isInitialLoading, hasLoadedOnce ] );

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
					{ /* Removable unless they are the sole remaining admin. The group
					     creator is NOT specially protected — legacy WP-admin
					     (bp-groups-admin.php) always let the creator be removed; the
					     is_creator gate here was a 3.0.0 migration regression. */ }
					{ ! member.is_sole_admin && (
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

			{ /* Filter Row — search + role-filter dropdown (Figma 2026-05-08).
			     Mounted from first paint (matches the "Add New Members" row
			     above) so the admin sees the search affordance immediately
			     instead of having to wait for the initial member fetch. The
			     dropdown counts append themselves only after `hasLoadedOnce`
			     so we don't briefly show "All (0)" / "Organizer (0)" while
			     the first fetch is in flight. */ }
			<div className="bb-group-members-tab__filters">
				<div className="bb-group-members-tab__filter-search">
					<input
						type="search"
						className="bb-group-members-tab__filter-search-input"
						placeholder={ __( 'Search member', 'buddyboss' ) }
						aria-label={ __( 'Search member', 'buddyboss' ) }
						value={ memberFilterQuery }
						onChange={ function ( e ) {
							setMemberFilterQuery( e.target.value );
						} }
					/>
					<i
						className="bb-icons-rl-magnifying-glass bb-group-members-tab__filter-search-icon"
						aria-hidden="true"
					/>
				</div>
				<div className="bb-group-members-tab__filter-role">
					<SelectControl
						hideLabelFromVision
						label={ __( 'Filter by role', 'buddyboss' ) }
						value={ roleFilter }
						onChange={ setRoleFilter }
						options={ [
							{ value: 'all',    label: hasLoadedOnce ? sprintf( __( 'All (%d)', 'buddyboss' ),       roleCounts.all )    : __( 'All', 'buddyboss' ) },
							{ value: 'admin',  label: hasLoadedOnce ? sprintf( __( 'Organizer (%d)', 'buddyboss' ), roleCounts.admin )  : __( 'Organizer', 'buddyboss' ) },
							{ value: 'mod',    label: hasLoadedOnce ? sprintf( __( 'Moderator (%d)', 'buddyboss' ), roleCounts.mod )    : __( 'Moderator', 'buddyboss' ) },
							{ value: 'member', label: hasLoadedOnce ? sprintf( __( 'Member (%d)', 'buddyboss' ),    roleCounts.member ) : __( 'Member', 'buddyboss' ) },
							{ value: 'banned', label: hasLoadedOnce ? sprintf( __( 'Banned (%d)', 'buddyboss' ),    roleCounts.banned ) : __( 'Banned', 'buddyboss' ) },
						] }
					/>
				</div>
			</div>

			{ /* Members List — Per-Role Sections.
			     Top-level spinner only shows BEFORE the first load completes.
			     After that, refetches (search/role-filter/pagination) keep
			     the list mounted and let each section render its own
			     section-scoped spinner via `sectionIsLoading`. */ }
			{ ! hasLoadedOnce ? (
				<div className="bb-group-members-tab__loading">
					<Spinner />
				</div>
			) : (
				<div className="bb-group-members-tab__list">
					{ ( function () {
						// Sections to render. Role filter narrows to one section
						// when a specific role is selected; 'all' renders every
						// non-empty section as before.
						var visibleSections = 'all' === roleFilter
							? roleSections
							: roleSections.filter( function ( s ) { return s.key === roleFilter; } );

						// Track whether any role section returned hits for the
						// active server search, so we can render a single "no
						// results" hint when nothing matched (instead of an
						// empty silent panel).
						var anyVisible = false;

						var rendered = visibleSections.map( function ( section ) {
							var displayMembers = displayMembersByRole[ section.key ] || [];
							var sectionTotal = roleTotals[ section.key ] || 0;
							// Pending-adjusted total: used to decide whether an emptied
							// section should collapse (roleTotals still counts moved-out
							// members until save).
							var sectionEffectiveTotal = effectiveRoleTotals[ section.key ] || 0;
							var sectionIsLoading = roleLoading[ section.key ];

							// Server returned only matching members when a search was active;
							// client-side re-filtering would just re-do the work and risk
							// hiding valid hits whose user_login matched (e.g. login "mike"
							// vs display_name "Michael" — server search includes both).
							var filteredMembers = displayMembers;

							if ( filteredMembers.length > 0 ) {
								anyVisible = true;
							}

							// Hide empty sections — but only when no search is
							// active. Under an active search we silently omit
							// zero-match sections so the matched section sits
							// flush at the top.
							if ( 0 === filteredMembers.length && 0 === sectionEffectiveTotal && ! sectionIsLoading ) {
								return null;
							}
							if ( 0 === filteredMembers.length && normalizedMemberFilter ) {
								return null;
							}

							return (
								<div key={ section.key } className="bb-group-members-tab__role-group">
									{ sectionIsLoading ? (
										<div className="bb-group-members-tab__section-loading">
											<Spinner />
										</div>
									) : filteredMembers.length > 0 ? (
										filteredMembers.map( renderMemberRow )
									) : null }

									{ /* Pagination is hidden while a search is active — A1 fires
									     a single page-1 fetch per role with the search term, so
									     we only surface that first page of hits. Pagination across
									     search results would need an explicit "show more matching"
									     UI which isn't part of this iteration. */ }
									{ ! sectionIsLoading && ! normalizedMemberFilter && renderPagination( section.key, sectionTotal ) }
								</div>
							);
						} );

						// Empty-state messaging — render exactly one of these
						// when the visible sections produced no rows AND no
						// section is mid-load (so we don't race the spinner).
						var anyLoading = visibleSections.some( function ( s ) {
							return roleLoading[ s.key ];
						} );

						if ( ! anyVisible && ! anyLoading ) {
							// 1) Search active → "no members match X".
							if ( normalizedMemberFilter ) {
								return (
									<div className="bb-group-members-tab__filter-empty" role="status">
										{ sprintf(
											/* translators: %s is the current search query. */
											__( 'No members match “%s”.', 'buddyboss' ),
											memberFilterQuery
										) }
									</div>
								);
							}

							// 2) Role filter narrowed to a 0-member role → role-specific
							//    notice ("No moderators in this group" etc.). Surfaces a
							//    blank pane that would otherwise look broken when
							//    e.g. an admin selects "Moderator (0)".
							if ( 'all' !== roleFilter ) {
								var emptyRoleLabels = {
									admin:  __( 'No organizers in this group yet.', 'buddyboss' ),
									mod:    __( 'No moderators in this group yet.', 'buddyboss' ),
									member: __( 'No members in this group yet.', 'buddyboss' ),
									banned: __( 'No banned members in this group.', 'buddyboss' ),
								};
								return (
									<div className="bb-group-members-tab__filter-empty" role="status">
										{ emptyRoleLabels[ roleFilter ] || __( 'No members found.', 'buddyboss' ) }
									</div>
								);
							}

							// 3) All-roles view + group truly empty (no add/remove
							//    happened) → fall back to a generic notice.
							return (
								<div className="bb-group-members-tab__filter-empty" role="status">
									{ __( 'This group has no members yet.', 'buddyboss' ) }
								</div>
							);
						}

						return rendered;
					} )() }
				</div>
			) }
		</div>
	);
}
