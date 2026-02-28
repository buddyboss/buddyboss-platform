/**
 * BuddyBoss Admin Settings 2.0 - Group Members Tab
 *
 * Custom component for the Members tab in the Group Edit Modal.
 * Supports member list with role management, add member autocomplete,
 * and remove member functionality.
 *
 * All changes are collected locally and only committed when the parent
 * modal's Save button is clicked (via saveRef).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
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
 * Group Members Tab Component
 *
 * @param {Object}   props            Component props.
 * @param {number}   props.groupId    Group ID.
 * @param {Function} props.setNotice  Function to show notices.
 * @param {Object}   props.saveRef    Ref object — parent sets saveRef.current to call our save.
 * @returns {JSX.Element} Members tab.
 */
export function GroupMembersTab( { groupId, setNotice, saveRef } ) {
	// Server-fetched members (source of truth from DB).
	var fetchedMembersState = useState( [] );
	var fetchedMembers = fetchedMembersState[ 0 ];
	var setFetchedMembers = fetchedMembersState[ 1 ];

	var totalState = useState( 0 );
	var total = totalState[ 0 ];
	var setTotal = totalState[ 1 ];

	var isLoadingState = useState( true );
	var isLoading = isLoadingState[ 0 ];
	var setIsLoading = isLoadingState[ 1 ];

	var pageState = useState( 1 );
	var page = pageState[ 0 ];
	var setPage = pageState[ 1 ];

	var perPage = 20;

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
	var membersAbortRef = useRef( null );

	// Keep refs to latest pending state for the save callback.
	var pendingAddsRef = useRef( pendingAdds );
	var pendingRemovesRef = useRef( pendingRemoves );
	var pendingRoleChangesRef = useRef( pendingRoleChanges );
	pendingAddsRef.current = pendingAdds;
	pendingRemovesRef.current = pendingRemoves;
	pendingRoleChangesRef.current = pendingRoleChanges;

	/**
	 * Fetch members from the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var fetchMembers = useCallback( function () {
		// Cancel any in-flight members request.
		if ( membersAbortRef.current ) {
			membersAbortRef.current.abort();
		}
		membersAbortRef.current = new AbortController();

		setIsLoading( true );
		getGroupMembers( groupId, { page: page, per_page: perPage }, { signal: membersAbortRef.current.signal } ).then( function ( response ) {
			if ( response.success && response.data ) {
				setFetchedMembers( response.data.members || [] );
				setTotal( response.data.total || 0 );
			}
			setIsLoading( false );
		} ).catch( function ( err ) {
			if ( err && 'AbortError' === err.name ) {
				return;
			}
			setIsLoading( false );
		} );
	}, [ groupId, page ] );

	useEffect( function () {
		fetchMembers();
	}, [ fetchMembers ] );

	// Cleanup on unmount.
	useEffect( function () {
		return function () {
			if ( searchTimerRef.current ) {
				clearTimeout( searchTimerRef.current );
			}
			if ( searchAbortRef.current ) {
				searchAbortRef.current.abort();
			}
			if ( membersAbortRef.current ) {
				membersAbortRef.current.abort();
			}
		};
	}, [] );

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
	 * @param {number} userId User ID.
	 */
	var handleRemovePendingAdd = function ( userId ) {
		setPendingAdds( function ( prev ) {
			return prev.filter( function ( u ) {
				return u.id !== userId;
			} );
		} );
	};

	// Build the display list: fetched members (minus pending removes, with role overrides) + pending adds.
	var displayMembers = [];

	// Existing members minus removed ones.
	fetchedMembers.forEach( function ( member ) {
		if ( -1 !== pendingRemoves.indexOf( member.user_id ) ) {
			return; // Removed locally.
		}
		var role = pendingRoleChanges[ member.user_id ] || member.role;
		displayMembers.push( {
			user_id: member.user_id,
			name: member.name,
			avatar_url: member.avatar_url,
			profile_url: member.profile_url,
			role: role,
			is_creator: member.is_creator,
			is_pending: false,
		} );
	} );

	// Pending adds with their chosen role.
	pendingAdds.forEach( function ( user ) {
		displayMembers.push( {
			user_id: user.id,
			name: user.label || user.name,
			avatar_url: user.image || '',
			profile_url: '',
			role: user.role || 'member',
			is_creator: false,
			is_pending: true,
		} );
	} );

	var totalPages = Math.ceil( total / perPage );

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
								setTimeout( function () {
									setShowSuggestions( false );
								}, 200 );
							} }
							placeholder={ __( 'Type username to add', 'buddyboss' ) }
							className="bb-group-members-tab__search-input"
						/>
						{ showSuggestions && (
						<div className="bb-group-members-tab__suggestions">
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

			{ /* Members List */ }
			{ isLoading ? (
				<div className="bb-group-members-tab__loading">
					<Spinner />
				</div>
			) : 0 === displayMembers.length ? (
				<div className="bb-group-members-tab__empty">
					<p>{ __( 'No members found.', 'buddyboss' ) }</p>
				</div>
			) : (
				<div className="bb-group-members-tab__list">
					{ ( function () {
						// Group members by role in display order.
						var roleOrder = [ 'admin', 'mod', 'member', 'banned' ];
						var grouped = {};
						roleOrder.forEach( function ( r ) {
							grouped[ r ] = [];
						} );
						displayMembers.forEach( function ( member ) {
							var key = grouped[ member.role ] ? member.role : 'member';
							grouped[ key ].push( member );
						} );
						return roleOrder.map( function ( role ) {
							if ( 0 === grouped[ role ].length ) {
								return null;
							}
							return (
								<div key={ role } className="bb-group-members-tab__role-group">
									{ grouped[ role ].map( function ( member ) {
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
													{ ! member.is_creator && (
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
									} ) }
								</div>
							);
						} );
					} )() }
				</div>
			) }

			{ /* Pagination */ }
			{ totalPages > 1 && (
				<div className="bb-group-members-tab__pagination">
					<Button
						variant="secondary"
						disabled={ 1 === page }
						onClick={ function () {
							setPage( function ( p ) {
								return Math.max( 1, p - 1 );
							} );
						} }
					>
						{ __( 'Previous', 'buddyboss' ) }
					</Button>
					<span className="bb-group-members-tab__page-info">
						{ page + ' / ' + totalPages }
					</span>
					<Button
						variant="secondary"
						disabled={ page >= totalPages }
						onClick={ function () {
							setPage( function ( p ) {
								return Math.min( totalPages, p + 1 );
							} );
						} }
					>
						{ __( 'Next', 'buddyboss' ) }
					</Button>
				</div>
			) }
		</div>
	);
}
