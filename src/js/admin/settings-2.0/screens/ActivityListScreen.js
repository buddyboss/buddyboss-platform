/**
 * BuddyBoss Admin Settings 2.0 - Activity List Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import {
	Button,
	CheckboxControl,
	SelectControl,
	Spinner,
	DropdownMenu,
	MenuGroup,
	MenuItem,
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';
import { ajaxFetch } from '../utils/ajax';
import { sanitizeHtml, safeUrl } from '../utils/sanitize';
import { ListPagination } from '../components/common/ListPagination';
import { AdminNotice } from '../components/common/AdminNotice';
import { ListToolbar } from '../components/common/ListToolbar';
import { useListScreenHandlers } from '../hooks/useListScreenHandlers';
import { useListScreenState } from '../hooks/useListScreenState';
import { ActivityEditModal } from '../components/activity/ActivityEditModal';
import { ActivityCommentModal } from '../components/activity/ActivityCommentModal';

/**
 * Activity List Screen Component
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onNavigate Navigation handler.
 * @returns {JSX.Element} Activity list screen.
 */
export function ActivityListScreen( { onNavigate } ) {
	// Common list screen state (loading, notice, selection, bulk, search).
	var common = useListScreenState();
	var isLoading = common.isLoading;
	var setIsLoading = common.setIsLoading;
	var notice = common.notice;
	var setNotice = common.setNotice;
	var selectedIds = common.selectedIds;
	var setSelectedIds = common.setSelectedIds;
	var bulkAction = common.bulkAction;
	var setBulkAction = common.setBulkAction;
	var isBulkProcessing = common.isBulkProcessing;
	var setIsBulkProcessing = common.setIsBulkProcessing;
	var searchInput = common.searchInput;
	var setSearchInput = common.setSearchInput;
	var searchQuery = common.searchQuery;
	var setSearchQuery = common.setSearchQuery;

	// Screen-specific state.
	var activitiesState = useState( [] );
	var activities = activitiesState[ 0 ];
	var setActivities = activitiesState[ 1 ];

	var totalState = useState( 0 );
	var total = totalState[ 0 ];
	var setTotal = totalState[ 1 ];

	var allCountState = useState( 0 );
	var allCount = allCountState[ 0 ];
	var setAllCount = allCountState[ 1 ];

	var currentPageState = useState( 1 );
	var currentPage = currentPageState[ 0 ];
	var setCurrentPage = currentPageState[ 1 ];

	var perPageState = useState( 20 );
	var perPage = perPageState[ 0 ];

	var spamCountState = useState( 0 );
	var spamCount = spamCountState[ 0 ];
	var setSpamCount = spamCountState[ 1 ];

	var filterState = useState( 'all' );
	var filter = filterState[ 0 ];
	var setFilter = filterState[ 1 ];

	var actionFilterState = useState( '' );
	var actionFilter = actionFilterState[ 0 ];
	var setActionFilter = actionFilterState[ 1 ];

	var activityActionsState = useState( {} );
	var activityActions = activityActionsState[ 0 ];
	var setActivityActions = activityActionsState[ 1 ];

	var activityActionsGroupedState = useState( [] );
	var activityActionsGrouped = activityActionsGroupedState[ 0 ];
	var setActivityActionsGrouped = activityActionsGroupedState[ 1 ];

	var bulkActionsState = useState( {} );
	var bulkActions = bulkActionsState[ 0 ];
	var setBulkActions = bulkActionsState[ 1 ];

	var columnsState = useState( {} );
	var columns = columnsState[ 0 ];
	var setColumns = columnsState[ 1 ];

	var viewsState = useState( {} );
	var views = viewsState[ 0 ];
	var setViews = viewsState[ 1 ];

	var editActivityState = useState( null );
	var editActivity = editActivityState[ 0 ];
	var setEditActivity = editActivityState[ 1 ];

	var commentActivityState = useState( null );
	var commentActivity = commentActivityState[ 0 ];
	var setCommentActivity = commentActivityState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	var isCommentSavingState = useState( false );
	var isCommentSaving = isCommentSavingState[ 0 ];
	var setIsCommentSaving = isCommentSavingState[ 1 ];

	var isEditLoadingState = useState( false );
	var isEditLoading = isEditLoadingState[ 0 ];
	var setIsEditLoading = isEditLoadingState[ 1 ];

	var deleteConfirmState = useState( null ); // { action, ids, message }
	var deleteConfirm = deleteConfirmState[ 0 ];
	var setDeleteConfirm = deleteConfirmState[ 1 ];

	var hasMetaRef = useRef( false );

	var totalPages = Math.ceil( total / perPage );

	/**
	 * Fetch activities from the server.
	 *
	 * @param {Object} options         Optional fetch options.
	 * @param {AbortSignal} options.signal AbortController signal to cancel in-flight requests.
	 */
	var fetchActivities = useCallback( function ( options ) {
		setIsLoading( true );

		var spam = 'ham_only';
		if ( 'spam' === filter ) {
			spam = 'spam_only';
		}

		var fetchOptions = {};
		if ( options && options.signal ) {
			fetchOptions.signal = options.signal;
		}

		ajaxFetch( 'bb_admin_get_activities', {
			page: currentPage,
			per_page: perPage,
			search: searchQuery,
			activity_type: actionFilter,
			spam: spam,
			include_meta: hasMetaRef.current ? 0 : 1,
		}, fetchOptions ).then( function ( response ) {
			if ( response.success && response.data ) {
				var rawActivities = response.data.activities || [];
				// Sanitize HTML once at fetch time to avoid DOMParser overhead per render.
				var sanitizedActivities = rawActivities.map( function ( activity ) {
					var updates = {
						action: activity.action ? sanitizeHtml( activity.action ) : '',
						content: activity.content ? sanitizeHtml( activity.content ) : '',
					};
					if ( activity.custom_columns ) {
						var sanitizedColumns = {};
						Object.keys( activity.custom_columns ).forEach( function ( key ) {
							sanitizedColumns[ key ] = sanitizeHtml( activity.custom_columns[ key ] );
						} );
						updates.custom_columns = sanitizedColumns;
					}
					return Object.assign( {}, activity, updates );
				} );
				setActivities( sanitizedActivities );
				setTotal( response.data.total || 0 );
				setSpamCount( response.data.spam_count || 0 );

				// Track total "all" count separately.
				if ( 'all' === filter ) {
					setAllCount( response.data.total || 0 );
				}
				if ( response.data.activity_actions ) {
					setActivityActions( response.data.activity_actions );
				}
				if ( response.data.activity_actions_grouped ) {
					setActivityActionsGrouped( response.data.activity_actions_grouped );
				}
				if ( response.data.bulk_actions ) {
					setBulkActions( response.data.bulk_actions );
				}
				if ( response.data.columns ) {
					setColumns( response.data.columns );
				}
				if ( response.data.views ) {
					setViews( response.data.views );
				}
				hasMetaRef.current = true;
			}
			setIsLoading( false );
		} ).catch( function ( error ) {
			// Ignore aborted requests — they are expected during cleanup.
			if ( error && 'AbortError' === error.name ) {
				return;
			}
			setIsLoading( false );
			setNotice( {
				type: 'error',
				message: __( 'Failed to load activities. Please try again.', 'buddyboss' ),
			} );
		} );
	}, [ currentPage, perPage, searchQuery, actionFilter, filter ] );

	// Fetch on mount and when filters change. Abort stale requests on cleanup.
	useEffect( function () {
		var controller = new AbortController();
		fetchActivities( { signal: controller.signal } );
		return function () {
			controller.abort();
		};
	}, [ fetchActivities ] );

	// Cleanup search debounce timer on unmount.
	useEffect( function () {
		return function () {
			if ( handlers.searchTimerRef.current ) {
				clearTimeout( handlers.searchTimerRef.current );
			}
		};
	}, [] );

	// Common list screen handlers (search, sort, filter, select).
	var handlers = useListScreenHandlers( {
		setSearchInput: setSearchInput,
		setSearchQuery: setSearchQuery,
		setPage: setCurrentPage,
		setSelectedIds: setSelectedIds,
		setFilter: setFilter,
		getItemIds: function () {
			return activities.map( function ( a ) { return a.id; } );
		},
	} );
	var handleSearchChange = handlers.handleSearchChange;
	var handleFilterChange = handlers.handleFilterChange;
	var handleSelectAll = handlers.handleSelectAll;
	var handleSelectRow = handlers.handleSelectRow;

	// Action type filter — separate from status filter, only resets page (not selection).
	var handleActionFilterChange = function ( value ) {
		setActionFilter( value );
		setCurrentPage( 1 );
	};

	/**
	 * Perform action on activity/activities.
	 *
	 * @param {string}       action The action (spam, ham, delete).
	 * @param {Array|number} ids    Activity ID(s).
	 */
	var performAction = function ( action, ids ) {
		if ( ! ids || ( Array.isArray( ids ) && 0 === ids.length ) ) {
			return;
		}

		var idString = Array.isArray( ids ) ? ids.join( ',' ) : String( ids );

		ajaxFetch( 'bb_admin_activity_action', {
			activity_ids: idString,
			do_action: action,
		} ).then( function ( response ) {
			if ( response.success ) {
				setNotice( { type: 'success', message: response.data.message } );
				setSelectedIds( [] );
				setBulkAction( '' );

				// If we're beyond page 1, go back to page 1 to avoid landing on an empty page.
				// Otherwise re-fetch the current page (page 1).
				if ( currentPage > 1 ) {
					setCurrentPage( 1 );
				} else {
					fetchActivities();
				}
			} else {
				setNotice( { type: 'error', message: response.data?.message || __( 'Action failed.', 'buddyboss' ) } );
			}
		} ).catch( function () {
			setNotice( { type: 'error', message: __( 'An error occurred.', 'buddyboss' ) } );
		} );
	};

	/**
	 * Handle bulk action apply.
	 */
	var handleBulkApply = function () {
		if ( ! bulkAction || 0 === selectedIds.length ) {
			return;
		}

		// Strip 'bulk_' prefix (same as legacy bp_activity_admin_load).
		var action = bulkAction.replace( /^bulk_/, '' );

		if ( 'delete' === action ) {
			setDeleteConfirm( {
				message: __( 'Are you sure you want to delete the selected activities?', 'buddyboss' ),
				onConfirm: function () {
					setDeleteConfirm( null );
					performAction( action, selectedIds );
				},
			} );
			return;
		}

		performAction( action, selectedIds );
	};

	/**
	 * Handle single activity action.
	 *
	 * @param {string} action   The action.
	 * @param {Object} activity The activity object.
	 */
	var handleSingleAction = function ( action, activity ) {
		if ( 'delete' === action ) {
			setDeleteConfirm( {
				message: __( 'Are you sure you want to delete this activity?', 'buddyboss' ),
				onConfirm: function () {
					setDeleteConfirm( null );
					performAction( action, activity.id );
				},
			} );
			return;
		}
		performAction( action, activity.id );
	};

	/**
	 * Handle edit activity click.
	 * Fetches fresh activity data from server so bp_activity_admin_edit hook fires.
	 *
	 * @param {Object} activity The activity object.
	 */
	var handleEditClick = function ( activity ) {
		setIsEditLoading( true );
		setEditActivity( null );
		ajaxFetch( 'bb_admin_get_activity', { activity_id: activity.id } ).then( function ( response ) {
			setIsEditLoading( false );
			if ( response.success && response.data && response.data.activity ) {
				setEditActivity( response.data.activity );
				if ( response.data.activity_actions ) {
					setActivityActions( response.data.activity_actions );
				}
			} else {
				setNotice( { type: 'error', message: response.data?.message || __( 'Failed to load activity.', 'buddyboss' ) } );
			}
		} ).catch( function () {
			setIsEditLoading( false );
			setNotice( { type: 'error', message: __( 'An error occurred while loading the activity.', 'buddyboss' ) } );
		} );
	};

	/**
	 * Handle save from edit modal.
	 *
	 * @param {Object} data The updated activity data.
	 */
	var handleEditSave = function ( data ) {
		setIsSaving( true );
		ajaxFetch( 'bb_admin_save_activity', data ).then( function ( response ) {
			setIsSaving( false );
			if ( response.success ) {
				setNotice( { type: 'success', message: response.data.message } );
				setEditActivity( null );
				fetchActivities();
			} else {
				setNotice( { type: 'error', message: response.data?.message || __( 'Failed to save activity.', 'buddyboss' ) } );
			}
		} ).catch( function () {
			setIsSaving( false );
			setNotice( { type: 'error', message: __( 'An error occurred.', 'buddyboss' ) } );
		} );
	};

	/**
	 * Handle save from comment modal.
	 *
	 * @param {Object} data The comment data ({ activity_id, content }).
	 */
	var handleCommentSave = function ( data ) {
		setIsCommentSaving( true );
		ajaxFetch( 'bb_admin_add_activity_comment', data ).then( function ( response ) {
			setIsCommentSaving( false );
			if ( response.success ) {
				setNotice( { type: 'success', message: response.data.message } );
				setCommentActivity( null );
				fetchActivities();
			} else {
				setNotice( { type: 'error', message: response.data?.message || __( 'Failed to post comment.', 'buddyboss' ) } );
			}
		} ).catch( function () {
			setIsCommentSaving( false );
			setNotice( { type: 'error', message: __( 'An error occurred.', 'buddyboss' ) } );
		} );
	};

	/**
	 * Format date for display using the site's timezone and locale.
	 *
	 * Uses dateI18n() from @wordpress/date so the output respects the
	 * WordPress timezone setting and translates month names.
	 * date_recorded is stored as UTC in the database; passing the raw
	 * string lets dateI18n convert it to the site's local time.
	 *
	 * @param {string} dateStr UTC date string (e.g. "2026-02-27 14:23:45").
	 * @returns {string} Formatted date in site timezone.
	 */
	var formatDate = function ( dateStr ) {
		if ( ! dateStr ) {
			return '';
		}
		return dateI18n( 'j M, H:i:s', dateStr );
	};

	// Build action type options for select.
	var actionOptions = [ { label: __( 'All Actions', 'buddyboss' ), value: '' } ];
	Object.keys( activityActions ).forEach( function ( key ) {
		actionOptions.push( { label: activityActions[ key ], value: key } );
	} );

	// Build filter options from views (dynamic, supports plugins adding custom views).
	var filterOptions = [];
	if ( Object.keys( views ).length > 0 ) {
		Object.keys( views ).forEach( function ( key ) {
			var view = views[ key ];
			var label = view.label || key;
			if ( view.count > 0 || 'all' === key ) {
				label = label + ' (' + view.count + ')';
			}
			filterOptions.push( { label: label, value: key } );
		} );
	} else {
		// Fallback before first API response.
		filterOptions.push( { label: __( 'All', 'buddyboss' ), value: 'all' } );
	}

	var allSelected = activities.length > 0 && selectedIds.length === activities.length;

	return (
		<div className="bb-activity-list">
			{ /* Notice */ }
			<AdminNotice notice={ notice } onDismiss={ function () { setNotice( null ); } } />

			{ /* Header */ }
			<div className="bb-activity-list__header">
				<h2 className="bb-activity-list__title">{ __( 'Activities', 'buddyboss' ) }</h2>
			</div>

			{ /* Toolbar */ }
			<ListToolbar
				className="bb-activity-list"
				bulkAction={ bulkAction }
				bulkActions={ bulkActions }
				onBulkActionChange={ setBulkAction }
				onBulkApply={ handleBulkApply }
				selectedCount={ selectedIds.length }
				isBulkProcessing={ false }
				searchInput={ searchInput }
				onSearchChange={ handleSearchChange }
				searchPlaceholder={ __( 'Search activities', 'buddyboss' ) }
			>
				<SelectControl
					value={ filter }
					options={ filterOptions }
					onChange={ handleFilterChange }
					className="bb-activity-list__filter-select"
					__nextHasNoMarginBottom
				/>
				<div className="bb-activity-list__action-filter">
					<select
						value={ actionFilter }
						onChange={ function ( e ) {
							handleActionFilterChange( e.target.value );
						} }
					>
						<option value="">{ __( 'All Actions', 'buddyboss' ) }</option>
						{ activityActionsGrouped.map( function ( group, idx ) {
							return (
								<optgroup key={ idx } label={ group.label }>
									{ group.options.map( function ( opt ) {
										return (
											<option key={ opt.value } value={ opt.value }>
												{ opt.label }
											</option>
										);
									} ) }
								</optgroup>
							);
						} ) }
					</select>
				</div>
			</ListToolbar>

			{ /* Table */ }
			<div className="bb-activity-list__table-wrapper">
				{ isLoading ? (
					<div className="bb-activity-list__loading">
						<Spinner />
					</div>
				) : 0 === activities.length ? (
					<div className="bb-activity-list__empty">
						<p>{ __( 'No activities found.', 'buddyboss' ) }</p>
					</div>
				) : (
					<table className="bb-activity-list__table">
						<thead>
							<tr>
								<th className="bb-activity-list__th--checkbox">
									<CheckboxControl
										checked={ allSelected }
										onChange={ handleSelectAll }
										__nextHasNoMarginBottom
									/>
								</th>
								<th className="bb-activity-list__th--author">
									{ __( 'Author', 'buddyboss' ) }
								</th>
								<th className="bb-activity-list__th--activity">
									{ __( 'Activity', 'buddyboss' ) }
								</th>
								{ /* Custom columns from bp_activity_list_table_get_columns filter */ }
								{ Object.keys( columns ).filter( function ( key ) {
									return [ 'author', 'comment', 'action', 'response' ].indexOf( key ) === -1;
								} ).map( function ( key ) {
									return (
										<th key={ key } className={ 'bb-activity-list__th--custom bb-activity-list__th--' + key }>
											{ columns[ key ] }
										</th>
									);
								} ) }
								<th className="bb-activity-list__th--date">
									{ __( 'Submitted', 'buddyboss' ) }
								</th>
								<th className="bb-activity-list__th--actions">&nbsp;</th>
							</tr>
						</thead>
						<tbody>
							{ activities.map( function ( activity ) {
								var isSelected = selectedIds.indexOf( activity.id ) !== -1;
								var isSpam = 1 === activity.is_spam;

								return (
									<tr
										key={ activity.id }
										className={ 'bb-activity-list__row' + ( isSelected ? ' bb-activity-list__row--selected' : '' ) + ( isSpam ? ' bb-activity-list__row--spam' : '' ) }
									>
										<td className="bb-activity-list__td--checkbox">
											<CheckboxControl
												checked={ isSelected }
												onChange={ function ( checked ) {
													handleSelectRow( activity.id, checked );
												} }
												__nextHasNoMarginBottom
											/>
										</td>
										<td className="bb-activity-list__td--author">
											<div className="bb-activity-list__author">
												{ activity.author?.avatar_url && (
													<img
														src={ safeUrl( activity.author.avatar_url ) }
														alt={ activity.author.name || '' }
														className="bb-activity-list__avatar"
													/>
												) }
												<span className="bb-activity-list__author-info">
													<a
														href={ safeUrl( activity.author?.profile_url || '#' ) }
														className="bb-activity-list__author-name"
													>
														{ activity.author?.name || __( 'Unknown', 'buddyboss' ) }
													</a>
													{ isSpam && (
														<span className="bb-activity-list__spam-badge">
															<i className="bb-icons-rl bb-icons-rl-flag"></i>
															{ __( 'Spam', 'buddyboss' ) }
														</span>
													) }
												</span>
											</div>
										</td>
										<td className="bb-activity-list__td--activity">
											<div className="bb-activity-list__content">
												{/* Safe: action and content are sanitized via sanitizeHtml at fetch time. */}
												<span
													className="bb-activity-list__action-text"
													dangerouslySetInnerHTML={ { __html: activity.action } }
												/>
												{ activity.content && (
													<span
														className="bb-activity-list__content-preview"
														dangerouslySetInnerHTML={ { __html: activity.content } }
													/>
												) }
											</div>
										</td>
										{ /* Custom columns from bp_activity_admin_get_custom_column filter */ }
										{ activity.custom_columns && Object.keys( activity.custom_columns ).map( function ( key ) {
											return (
												<td key={ key } className={ 'bb-activity-list__td--custom bb-activity-list__td--' + key }>
													{/* Safe: custom_columns are already sanitized via sanitizeHtml at fetch time (line 168). */}
												<span dangerouslySetInnerHTML={ { __html: activity.custom_columns[ key ] } } />
												</td>
											);
										} ) }
										<td className="bb-activity-list__td--date">
											<span className="bb-activity-list__date">
												<i className="bb-icons-rl bb-icons-rl-clock"></i>
												{ formatDate( activity.date_recorded ) }
											</span>
										</td>
										<td className="bb-activity-list__td--actions">
											<DropdownMenu
												icon={ <i className="bb-icons-rl-dots-three"></i> }
												label={ __( 'More options', 'buddyboss' ) }
											>
												{ function ( { onClose } ) {
													return (
														<MenuGroup className="bb_dropdown_menu_group">
															{ ( activity.permalink || activity.primary_link ) && (
																<MenuItem
																	onClick={ function () {
																		var activityUrl = activity.permalink || activity.primary_link;
																		try {
																			var parsed = new URL( activityUrl, window.location.origin );
																			if ( 'http:' === parsed.protocol || 'https:' === parsed.protocol ) {
																				window.open( parsed.href, '_blank', 'noopener,noreferrer' );
																			}
																		} catch ( e ) {
																			// Invalid URL — do nothing.
																		}
																		onClose();
																	} }
																>
																	<i className="bb-icons-rl bb-icons-rl-eye"></i>
																	{ __( 'View Activity', 'buddyboss' ) }
																	<i className="bb-icons-rl bb-icons-rl-arrow-up-right bb-icons-external"></i>
																</MenuItem>
															) }
															<MenuItem
																onClick={ function () {
																	handleEditClick( activity );
																	onClose();
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-pencil-simple"></i>
																{ __( 'Edit', 'buddyboss' ) }
															</MenuItem>
															{ isSpam ? (
																<MenuItem
																	onClick={ function () {
																		handleSingleAction( 'ham', activity );
																		onClose();
																	} }
																>
																	<i className="bb-icons-rl bb-icons-rl-flag"></i>
																	{ __( 'Not Spam', 'buddyboss' ) }
																</MenuItem>
															) : (
																<MenuItem
																	onClick={ function () {
																		handleSingleAction( 'spam', activity );
																		onClose();
																	} }
																>
																	<i className="bb-icons-rl bb-icons-rl-flag"></i>
																	{ __( 'Spam', 'buddyboss' ) }
																</MenuItem>
															) }
															{ activity.can_comment && ! isSpam && (
																<MenuItem
																	onClick={ function () {
																		setCommentActivity( activity );
																		onClose();
																	} }
																>
																	<i className="bb-icons-rl bb-icons-rl-chat-text"></i>
																	{ __( 'Comment', 'buddyboss' ) }
																</MenuItem>
															) }
															<MenuItem
																isDestructive
																onClick={ function () {
																	handleSingleAction( 'delete', activity );
																	onClose();
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-trash"></i>
																{ __( 'Delete', 'buddyboss' ) }
															</MenuItem>
														</MenuGroup>
													);
												} }
											</DropdownMenu>
										</td>
									</tr>
								);
							} ) }
						</tbody>
					</table>
				) }
			</div>

			{ /* Footer */ }
			{ ! isLoading && (
				<ListPagination
					currentPage={ currentPage }
					totalPages={ totalPages }
					total={ total }
					onPageChange={ function ( page ) { setCurrentPage( page ); } }
					className="bb-activity-list"
				/>
			) }

			{ /* Edit Loading Overlay */ }
			{ isEditLoading && (
				<div className="bb-activity-list__edit-loading">
					<Spinner />
				</div>
			) }

			{ /* Edit Modal */ }
			<ActivityEditModal
				isOpen={ !! editActivity }
				activity={ editActivity }
				activityActions={ activityActions }
				onClose={ function () {
					setEditActivity( null );
				} }
				onSave={ handleEditSave }
				isSaving={ isSaving }
			/>

			{ /* Comment Modal */ }
			<ActivityCommentModal
				isOpen={ !! commentActivity }
				activity={ commentActivity }
				onClose={ function () {
					setCommentActivity( null );
				} }
				onSave={ handleCommentSave }
				isSaving={ isCommentSaving }
			/>

			{ /* Delete Confirmation Modal */ }
			{ deleteConfirm && (
				<Modal
					title={ __( 'Confirm Delete', 'buddyboss' ) }
					onRequestClose={ function () {
						setDeleteConfirm( null );
					} }
					className="bb-activity-list__delete-confirm-modal bb-admin-settings-modal"
				>
					<div className="bb-admin-settings-modal__body">
						<p>{ deleteConfirm.message }</p>
					</div>
					<div className="bb-admin-settings-modal__footer">
						<Button
							variant="primary"
							isDestructive
							onClick={ deleteConfirm.onConfirm }
						>
							{ __( 'Delete', 'buddyboss' ) }
						</Button>
						<Button
							variant="secondary"
							onClick={ function () {
								setDeleteConfirm( null );
							} }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}

export default ActivityListScreen;
