/**
 * BuddyBoss Admin Settings 2.0 - Group Members Tab
 *
 * Custom component for the Members tab in the Group Edit Modal.
 * Supports member list with role management, add member autocomplete,
 * and remove member functionality.
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
 * Role display labels.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Object}
 */
var roleLabels = {
	admin: __( 'Organizer', 'buddyboss' ),
	mod: __( 'Moderator', 'buddyboss' ),
	member: __( 'Member', 'buddyboss' ),
	banned: __( 'Banned', 'buddyboss' ),
};

/**
 * Group Members Tab Component
 *
 * @param {Object}   props            Component props.
 * @param {number}   props.groupId    Group ID.
 * @param {Function} props.setNotice  Function to show notices.
 * @returns {JSX.Element} Members tab.
 */
export function GroupMembersTab( { groupId, setNotice } ) {
	var membersState = useState( [] );
	var members = membersState[ 0 ];
	var setMembers = membersState[ 1 ];

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

	var searchTimerRef = useRef( null );

	/**
	 * Fetch members from the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var fetchMembers = useCallback( function () {
		setIsLoading( true );
		getGroupMembers( groupId, { page: page, per_page: perPage } ).then( function ( response ) {
			if ( response.success && response.data ) {
				setMembers( response.data.members || [] );
				setTotal( response.data.total || 0 );
			}
			setIsLoading( false );
		} ).catch( function () {
			setIsLoading( false );
		} );
	}, [ groupId, page ] );

	useEffect( function () {
		fetchMembers();
	}, [ fetchMembers ] );

	// Cleanup search timer on unmount.
	useEffect( function () {
		return function () {
			if ( searchTimerRef.current ) {
				clearTimeout( searchTimerRef.current );
			}
		};
	}, [] );

	/**
	 * Handle autocomplete search input change.
	 *
	 * @param {Object} e Input change event.
	 */
	var handleSearchInputChange = function ( e ) {
		var val = e.target.value;
		setSearchInput( val );

		if ( searchTimerRef.current ) {
			clearTimeout( searchTimerRef.current );
		}

		if ( val.length < 2 ) {
			setSuggestions( [] );
			setShowSuggestions( false );
			return;
		}

		searchTimerRef.current = setTimeout( function () {
			setIsSearching( true );
			setShowSuggestions( true );

			// Use the Settings 2.0 member autocomplete endpoint (POST-based).
			ajaxFetch( 'bb_admin_member_autocomplete', {
				term: val,
				group_id: groupId,
			} ).then( function ( response ) {
				setIsSearching( false );
				if ( response.success && response.data && Array.isArray( response.data.results ) ) {
					setSuggestions( response.data.results );
				} else {
					setSuggestions( [] );
				}
			} ).catch( function () {
				setIsSearching( false );
				setSuggestions( [] );
			} );
		}, 300 );
	};

	/**
	 * Handle adding a member from the autocomplete suggestions.
	 *
	 * @param {Object} user User object from suggestions.
	 */
	var handleAddMember = function ( user ) {
		setSearchInput( '' );
		setSuggestions( [] );
		setShowSuggestions( false );

		updateGroupMember( {
			group_id: groupId,
			user_id: user.id,
			role: 'member',
			action_type: 'add',
		} ).then( function ( response ) {
			if ( response.success ) {
				fetchMembers();
				if ( setNotice ) {
					setNotice( { type: 'success', message: response.data.message } );
				}
			} else {
				if ( setNotice ) {
					setNotice( { type: 'error', message: response.data?.message || __( 'Failed to add member.', 'buddyboss' ) } );
				}
			}
		} ).catch( function () {
			if ( setNotice ) {
				setNotice( { type: 'error', message: __( 'An error occurred.', 'buddyboss' ) } );
			}
		} );
	};

	/**
	 * Handle role change for a member.
	 *
	 * @param {number} userId  User ID.
	 * @param {string} newRole New role.
	 */
	var handleRoleChange = function ( userId, newRole ) {
		updateGroupMember( {
			group_id: groupId,
			user_id: userId,
			role: newRole,
		} ).then( function ( response ) {
			if ( response.success ) {
				fetchMembers();
			} else {
				if ( setNotice ) {
					setNotice( { type: 'error', message: response.data?.message || __( 'Failed to update role.', 'buddyboss' ) } );
				}
			}
		} ).catch( function () {
			if ( setNotice ) {
				setNotice( { type: 'error', message: __( 'An error occurred.', 'buddyboss' ) } );
			}
		} );
	};

	/**
	 * Handle removing a member.
	 *
	 * @param {number} userId User ID.
	 */
	var handleRemoveMember = function ( userId ) {
		updateGroupMember( {
			group_id: groupId,
			user_id: userId,
			role: 'remove',
		} ).then( function ( response ) {
			if ( response.success ) {
				fetchMembers();
				if ( setNotice ) {
					setNotice( { type: 'success', message: response.data.message } );
				}
			} else {
				if ( setNotice ) {
					setNotice( { type: 'error', message: response.data?.message || __( 'Failed to remove member.', 'buddyboss' ) } );
				}
			}
		} ).catch( function () {
			if ( setNotice ) {
				setNotice( { type: 'error', message: __( 'An error occurred.', 'buddyboss' ) } );
			}
		} );
	};

	var totalPages = Math.ceil( total / perPage );

	return (
		<div className="bb-group-members-tab">
			{ /* Add Member Autocomplete */ }
			<div className="bb-group-members-tab__add-member">
				<label className="bb-admin-meta-field__label">
					{ __( 'Add New Members', 'buddyboss' ) }
				</label>
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
												handleAddMember( user );
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
			</div>

			{ /* Members List */ }
			{ isLoading ? (
				<div className="bb-group-members-tab__loading">
					<Spinner />
				</div>
			) : 0 === members.length ? (
				<div className="bb-group-members-tab__empty">
					<p>{ __( 'No members found.', 'buddyboss' ) }</p>
				</div>
			) : (
				<div className="bb-group-members-tab__list">
					{ members.map( function ( member ) {
						return (
							<div key={ member.user_id } className="bb-group-members-tab__member-row">
								<div className="bb-group-members-tab__member-info">
									{ member.avatar_url && (
										<img
											src={ safeUrl( member.avatar_url ) }
											alt={ member.name }
											className="bb-group-members-tab__member-avatar"
										/>
									) }
									<span className="bb-group-members-tab__member-name">
										{ member.name }
									</span>
								</div>
								<div className="bb-group-members-tab__member-actions">
									<SelectControl
										value={ member.role }
										options={ roleOptions }
										onChange={ function ( newRole ) {
											handleRoleChange( member.user_id, newRole );
										} }
										__nextHasNoMarginBottom
									/>
									{ ! member.is_creator && (
										<button
											type="button"
											className="bb-group-members-tab__remove-btn"
											onClick={ function () {
												handleRemoveMember( member.user_id );
											} }
											title={ __( 'Remove member', 'buddyboss' ) }
										>
											<i className="bb-icons-rl bb-icons-rl-x"></i>
										</button>
									) }
								</div>
							</div>
						);
					} ) }
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
