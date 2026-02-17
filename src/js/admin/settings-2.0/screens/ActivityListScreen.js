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
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ajaxFetch } from '../utils/ajax';
import { ActivityEditModal } from '../components/activity/ActivityEditModal';

/**
 * Activity List Screen Component
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onNavigate Navigation handler.
 * @returns {JSX.Element} Activity list screen.
 */
export function ActivityListScreen( { onNavigate } ) {
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

	var searchQueryState = useState( '' );
	var searchQuery = searchQueryState[ 0 ];
	var setSearchQuery = searchQueryState[ 1 ];

	var searchInputState = useState( '' );
	var searchInput = searchInputState[ 0 ];
	var setSearchInput = searchInputState[ 1 ];

	var selectedIdsState = useState( [] );
	var selectedIds = selectedIdsState[ 0 ];
	var setSelectedIds = selectedIdsState[ 1 ];

	var isLoadingState = useState( true );
	var isLoading = isLoadingState[ 0 ];
	var setIsLoading = isLoadingState[ 1 ];

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

	var bulkActionState = useState( '' );
	var bulkAction = bulkActionState[ 0 ];
	var setBulkAction = bulkActionState[ 1 ];

	var editActivityState = useState( null );
	var editActivity = editActivityState[ 0 ];
	var setEditActivity = editActivityState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	var isEditLoadingState = useState( false );
	var isEditLoading = isEditLoadingState[ 0 ];
	var setIsEditLoading = isEditLoadingState[ 1 ];

	var noticeState = useState( null );
	var notice = noticeState[ 0 ];
	var setNotice = noticeState[ 1 ];

	var searchTimerRef = useRef( null );

	var totalPages = Math.ceil( total / perPage );

	/**
	 * Fetch activities from the server.
	 */
	var fetchActivities = useCallback( function () {
		setIsLoading( true );

		var spam = 'all';
		if ( 'spam' === filter ) {
			spam = 'spam_only';
		}

		ajaxFetch( 'bb_admin_get_activities', {
			page: currentPage,
			per_page: perPage,
			search: searchQuery,
			activity_type: actionFilter,
			spam: spam,
		} ).then( function ( response ) {
			if ( response.success && response.data ) {
				setActivities( response.data.activities || [] );
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
			}
			setIsLoading( false );
		} ).catch( function () {
			setIsLoading( false );
		} );
	}, [ currentPage, perPage, searchQuery, actionFilter, filter ] );

	// Fetch on mount and when filters change.
	useEffect( function () {
		fetchActivities();
	}, [ fetchActivities ] );

	// Clear notice after 5 seconds.
	useEffect( function () {
		if ( notice ) {
			var timer = setTimeout( function () {
				setNotice( null );
			}, 5000 );
			return function () {
				clearTimeout( timer );
			};
		}
	}, [ notice ] );

	/**
	 * Handle search input with debounce.
	 *
	 * @param {string} value Search value.
	 */
	var handleSearchChange = function ( value ) {
		setSearchInput( value );
		if ( searchTimerRef.current ) {
			clearTimeout( searchTimerRef.current );
		}
		searchTimerRef.current = setTimeout( function () {
			setSearchQuery( value );
			setCurrentPage( 1 );
		}, 500 );
	};

	/**
	 * Handle filter change from dropdown.
	 *
	 * @param {string} newFilter Filter value.
	 */
	var handleFilterChange = function ( newFilter ) {
		setFilter( newFilter );
		setCurrentPage( 1 );
		setSelectedIds( [] );
	};

	/**
	 * Handle action type filter change.
	 *
	 * @param {string} value Action type value.
	 */
	var handleActionFilterChange = function ( value ) {
		setActionFilter( value );
		setCurrentPage( 1 );
	};

	/**
	 * Handle select all checkbox.
	 *
	 * @param {boolean} checked Checked state.
	 */
	var handleSelectAll = function ( checked ) {
		if ( checked ) {
			setSelectedIds( activities.map( function ( a ) {
				return a.id;
			} ) );
		} else {
			setSelectedIds( [] );
		}
	};

	/**
	 * Handle individual row checkbox.
	 *
	 * @param {number}  id      Activity ID.
	 * @param {boolean} checked Checked state.
	 */
	var handleSelectRow = function ( id, checked ) {
		if ( checked ) {
			setSelectedIds( function ( prev ) {
				return prev.concat( [ id ] );
			} );
		} else {
			setSelectedIds( function ( prev ) {
				return prev.filter( function ( i ) {
					return i !== id;
				} );
			} );
		}
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
				fetchActivities();
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
			if ( ! window.confirm( __( 'Are you sure you want to delete the selected activities?', 'buddyboss' ) ) ) {
				return;
			}
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
			if ( ! window.confirm( __( 'Are you sure you want to delete this activity?', 'buddyboss' ) ) ) {
				return;
			}
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
	 * Format date for display.
	 *
	 * @param {string} dateStr Date string.
	 * @returns {string} Formatted date.
	 */
	var formatDate = function ( dateStr ) {
		if ( ! dateStr ) {
			return '';
		}
		var d = new Date( dateStr.replace( ' ', 'T' ) + 'Z' );
		var day = d.getUTCDate();
		var months = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
		var month = months[ d.getUTCMonth() ];
		var hours = String( d.getUTCHours() ).padStart( 2, '0' );
		var minutes = String( d.getUTCMinutes() ).padStart( 2, '0' );
		var seconds = String( d.getUTCSeconds() ).padStart( 2, '0' );
		return day + ' ' + month + ', ' + hours + ':' + minutes + ':' + seconds;
	};

	/**
	 * Strip HTML tags for display.
	 *
	 * @param {string} html HTML string.
	 * @returns {string} Plain text.
	 */
	var stripHtml = function ( html ) {
		if ( ! html ) {
			return '';
		}
		var tmp = document.createElement( 'div' );
		tmp.innerHTML = html;
		return tmp.textContent || tmp.innerText || '';
	};

	/**
	 * Truncate text to a max length.
	 *
	 * @param {string} text   Text to truncate.
	 * @param {number} maxLen Maximum length.
	 * @returns {string} Truncated text.
	 */
	var truncate = function ( text, maxLen ) {
		if ( ! text ) {
			return '';
		}
		if ( text.length <= maxLen ) {
			return text;
		}
		return text.substring( 0, maxLen ) + '...';
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

	/**
	 * Build pagination page numbers.
	 *
	 * @returns {Array} Array of page number items.
	 */
	var getPageNumbers = function () {
		var pages = [];
		var maxVisible = 5;

		if ( totalPages <= 7 ) {
			for ( var i = 1; i <= totalPages; i++ ) {
				pages.push( i );
			}
		} else {
			pages.push( 1 );

			if ( currentPage > maxVisible - 1 ) {
				pages.push( '...' );
			}

			var start = Math.max( 2, currentPage - 1 );
			var end = Math.min( totalPages - 1, currentPage + 1 );

			if ( currentPage <= 3 ) {
				end = Math.min( totalPages - 1, maxVisible );
			}
			if ( currentPage >= totalPages - 2 ) {
				start = Math.max( 2, totalPages - maxVisible + 1 );
			}

			for ( var j = start; j <= end; j++ ) {
				pages.push( j );
			}

			if ( currentPage < totalPages - ( maxVisible - 2 ) ) {
				pages.push( '...' );
			}

			pages.push( totalPages );
		}

		return pages;
	};

	return (
		<div className="bb-activity-list">
			{ /* Notice */ }
			{ notice && (
				<div className={ 'bb-admin-notice bb-admin-notice--' + notice.type }>
					<span>{ notice.message }</span>
					<button
						className="bb-admin-notice--dismiss"
						onClick={ function () {
							setNotice( null );
						} }
					>
						<i className='bb-icons-rl bb-icons-rl-x'></i>
					</button>
				</div>
			) }

			{ /* Header */ }
			<div className="bb-activity-list__header">
				<h2 className="bb-activity-list__title">{ __( 'Activities', 'buddyboss' ) }</h2>
			</div>

			{ /* Toolbar */ }
			<div className="bb-activity-list__toolbar">
				<div className="bb-activity-list__toolbar-left">
					{ /* Bulk Actions */ }
					<div className="bb-activity-list__bulk-actions">
						<SelectControl
							value={ bulkAction }
							options={ [ { label: __( 'Bulk actions', 'buddyboss' ), value: '' } ].concat(
								Object.keys( bulkActions ).map( function ( key ) {
									return { label: bulkActions[ key ], value: key };
								} )
							) }
							onChange={ setBulkAction }
							__nextHasNoMarginBottom
						/>
						<Button
							variant="secondary"
							onClick={ handleBulkApply }
							disabled={ ! bulkAction || 0 === selectedIds.length }
							className="bb-activity-list__bulk-apply"
						>
							{ __( 'Apply', 'buddyboss' ) }
						</Button>
					</div>
				</div>

				<div className="bb-activity-list__toolbar-right">
					{ /* Filter Dropdown */ }
					<SelectControl
						value={ filter }
						options={ filterOptions }
						onChange={ handleFilterChange }
						className="bb-activity-list__filter-select"
						__nextHasNoMarginBottom
					/>

					{ /* Action Type Filter */ }
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
									<optgroup key={ idx } label=" ">
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
					{ /* Search */ }
					<div className="bb-activity-list__search">
						<input
							type="text"
							value={ searchInput }
							onChange={ function ( e ) {
								handleSearchChange( e.target.value );
							} }
							placeholder={ __( 'Search activities', 'buddyboss' ) }
							className="bb-activity-list__search-input"
						/>
						<span className="bb-activity-list__search-icon">
							<i className="bb-icons-rl bb-icons-rl-search"></i>
						</span>
					</div>
				</div>
			</div>

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
														src={ activity.author.avatar_url }
														alt={ activity.author.name || '' }
														className="bb-activity-list__avatar"
													/>
												) }
												<span className="bb-activity-list__author-info">
													<a
														href={ activity.author?.profile_url || '#' }
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
												<span
													className="bb-activity-list__action-text"
													dangerouslySetInnerHTML={ { __html: truncate( stripHtml( activity.action ), 120 ) } }
												/>
												{ activity.content && (
													<span className="bb-activity-list__content-preview">
														{ truncate( stripHtml( activity.content ), 100 ) }
													</span>
												) }
											</div>
										</td>
										{ /* Custom columns from bp_activity_admin_get_custom_column filter */ }
										{ activity.custom_columns && Object.keys( activity.custom_columns ).map( function ( key ) {
											return (
												<td key={ key } className={ 'bb-activity-list__td--custom bb-activity-list__td--' + key }>
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
																		window.open( activity.permalink || activity.primary_link, '_blank' );
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
			{ ! isLoading && total > 0 && (
				<div className="bb-activity-list__footer">
					<span className="bb-activity-list__item-count">
						{ total } { __( 'items', 'buddyboss' ) }
					</span>

					{ totalPages > 1 && (
						<div className="bb-activity-list__pagination">
							<Button
								variant="secondary"
								disabled={ 1 === currentPage }
								onClick={ function () {
									setCurrentPage( function ( p ) {
										return Math.max( 1, p - 1 );
									} );
								} }
								className="bb-activity-list__pagination-btn bb-activity-list__pagination-btn--previous"
							>
								&lsaquo;
							</Button>

							{ getPageNumbers().map( function ( page, index ) {
								if ( '...' === page ) {
									return (
										<span key={ 'ellipsis-' + index } className="bb-activity-list__pagination-ellipsis">
											&hellip;
										</span>
									);
								}
								return (
									<Button
										key={ page }
										variant={ page === currentPage ? 'primary' : 'secondary' }
										onClick={ function () {
											setCurrentPage( page );
										} }
										className={ 'bb-activity-list__pagination-btn' + ( page === currentPage ? ' bb-activity-list__pagination-btn--current' : '' ) }
									>
										{ page }
									</Button>
								);
							} ) }

							<Button
								variant="secondary"
								disabled={ currentPage >= totalPages }
								onClick={ function () {
									setCurrentPage( function ( p ) {
										return Math.min( totalPages, p + 1 );
									} );
								} }
								className="bb-activity-list__pagination-btn bb-activity-list__pagination-btn--next"
							>
								&rsaquo;
							</Button>
						</div>
					) }
				</div>
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
		</div>
	);
}

export default ActivityListScreen;
