/**
 * BuddyBoss Admin Settings 2.0 - Email Templates List Screen
 *
 * Displays a paginated, searchable list of bp-email post type templates.
 * Title links open the native WP edit screen. "Add New Email" links to
 * the WP new post screen for bp-email.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef, useMemo } from '@wordpress/element';
import {
	Button,
	CheckboxControl,
	DropdownMenu,
	MenuGroup,
	MenuItem,
	SelectControl,
	Spinner,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getEmailTemplates, emailTemplateBulkAction } from '../utils/ajax';
import { safeUrl, sanitizeHtml } from '../utils/sanitize';
import { getPageNumbers } from '../utils/pagination';
import { Toast } from '../components/Toast';

/**
 * Sort options for the email templates list dropdown (static, never changes).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var sortOptions = [
	{ label: __( 'Newest', 'buddyboss' ), value: 'newest' },
	{ label: __( 'Oldest', 'buddyboss' ), value: 'oldest' },
];

/**
 * Number of email templates to fetch per page.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {number}
 */
var PER_PAGE = 20;

/**
 * Core column keys (built-in). Anything not in this list is a custom column
 * added by third-party plugins (e.g., WPML translation flags).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var CORE_COLUMNS = [ 'title', 'description', 'date' ];

/**
 * Email Templates List Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onNavigate Navigation callback.
 * @returns {JSX.Element} The email templates list screen.
 */
export default function EmailTemplatesListScreen( props ) {
	var stateItems = useState( [] );
	var items = stateItems[0];
	var setItems = stateItems[1];

	var stateTotal = useState( 0 );
	var total = stateTotal[0];
	var setTotal = stateTotal[1];

	var stateTotalPages = useState( 0 );
	var totalPages = stateTotalPages[0];
	var setTotalPages = stateTotalPages[1];

	var statePage = useState( 1 );
	var page = statePage[0];
	var setPage = statePage[1];

	var stateSort = useState( 'newest' );
	var sort = stateSort[0];
	var setSort = stateSort[1];

	var stateSearch = useState( '' );
	var search = stateSearch[0];
	var setSearch = stateSearch[1];

	var stateSearchInput = useState( '' );
	var searchInput = stateSearchInput[0];
	var setSearchInput = stateSearchInput[1];

	var stateIsLoading = useState( true );
	var isLoading = stateIsLoading[0];
	var setIsLoading = stateIsLoading[1];

	var stateAddNewUrl = useState( '' );
	var addNewUrl = stateAddNewUrl[0];
	var setAddNewUrl = stateAddNewUrl[1];

	var stateBulkActions = useState( {} );
	var bulkActions = stateBulkActions[0];
	var setBulkActions = stateBulkActions[1];

	var stateColumns = useState( {} );
	var columns = stateColumns[0];
	var setColumns = stateColumns[1];

	var stateSelectedIds = useState( [] );
	var selectedIds = stateSelectedIds[0];
	var setSelectedIds = stateSelectedIds[1];

	var stateBulkAction = useState( '' );
	var bulkAction = stateBulkAction[0];
	var setBulkAction = stateBulkAction[1];

	var stateBulkProcessing = useState( false );
	var bulkProcessing = stateBulkProcessing[0];
	var setBulkProcessing = stateBulkProcessing[1];

	var stateToast = useState( null );
	var toast = stateToast[0];
	var setToast = stateToast[1];

	var abortRef = useRef( null );
	var searchTimerRef = useRef( null );
	var isFirstLoad = useRef( true );

	/**
	 * Fetch email templates from the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} params            Query parameters.
	 * @param {number} params.fetchPage  Page number.
	 * @param {string} params.fetchSort  Sort order.
	 * @param {string} params.fetchSearch Search term.
	 */
	var fetchTemplates = useCallback( function( params ) {
		var fetchPage = params.fetchPage || 1;
		var fetchSort = params.fetchSort || 'newest';
		var fetchSearch = params.fetchSearch !== undefined ? params.fetchSearch : '';

		// Cancel any stale request.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}

		var controller = new AbortController();
		abortRef.current = controller;

		setIsLoading( true );

		var data = {
			page: fetchPage,
			per_page: PER_PAGE,
			sort: fetchSort,
			search: fetchSearch,
		};

		// Include metadata on first load.
		if ( isFirstLoad.current ) {
			data.include_meta = 1;
			isFirstLoad.current = false;
		}

		getEmailTemplates( data, { signal: controller.signal } )
			.then( function( response ) {
				if ( response.success && response.data ) {
					setItems( response.data.items || [] );
					setTotal( response.data.total || 0 );
					setTotalPages( response.data.total_pages || 0 );

					if ( response.data.add_new_url ) {
						setAddNewUrl( response.data.add_new_url );
					}

					if ( response.data.bulk_actions ) {
						setBulkActions( response.data.bulk_actions );
					}

					if ( response.data.columns ) {
						setColumns( response.data.columns );
					}
				}
				setIsLoading( false );
			} )
			.catch( function( err ) {
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				setIsLoading( false );
			} );
	}, [] );

	// Initial fetch.
	useEffect( function() {
		fetchTemplates( { fetchPage: 1, fetchSort: sort, fetchSearch: '' } );

		return function() {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
			if ( searchTimerRef.current ) {
				clearTimeout( searchTimerRef.current );
			}
		};
	}, [] );

	// Auto-dismiss success toast after 3 seconds.
	useEffect( function() {
		if ( ! toast ) {
			return;
		}
		if ( 'success' === toast.status ) {
			var timer = setTimeout( function() {
				setToast( null );
			}, 3000 );
			return function() {
				clearTimeout( timer );
			};
		}
	}, [ toast ] );

	/**
	 * Handle search input change with 500ms debounce.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} e Input change event.
	 */
	var handleSearchChange = useCallback( function( e ) {
		var val = e.target.value;
		setSearchInput( val );

		if ( searchTimerRef.current ) {
			clearTimeout( searchTimerRef.current );
		}

		searchTimerRef.current = setTimeout( function() {
			setSearch( val );
			setPage( 1 );
			setSelectedIds( [] );
			fetchTemplates( { fetchPage: 1, fetchSort: sort, fetchSearch: val } );
		}, 500 );
	}, [ sort, fetchTemplates ] );

	/**
	 * Handle sort change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} newSort New sort value.
	 */
	var handleSortChange = useCallback( function( newSort ) {
		setSort( newSort );
		setPage( 1 );
		setSelectedIds( [] );
		fetchTemplates( { fetchPage: 1, fetchSort: newSort, fetchSearch: search } );
	}, [ search, fetchTemplates ] );

	/**
	 * Handle page change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} newPage New page number.
	 */
	var handlePageChange = useCallback( function( newPage ) {
		setPage( newPage );
		setSelectedIds( [] );
		fetchTemplates( { fetchPage: newPage, fetchSort: sort, fetchSearch: search } );
	}, [ sort, search, fetchTemplates ] );

	/**
	 * Toggle individual item selection.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} id Item ID.
	 */
	var handleToggleSelect = useCallback( function( id ) {
		setSelectedIds( function( prev ) {
			var idx = prev.indexOf( id );
			if ( -1 === idx ) {
				return prev.concat( [ id ] );
			}
			return prev.filter( function( i ) {
				return i !== id;
			} );
		} );
	}, [] );

	/**
	 * Toggle select all items on current page.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSelectAll = useCallback( function() {
		var allIds = items.map( function( item ) {
			return item.id;
		} );
		var allSelected = allIds.length > 0 && allIds.every( function( id ) {
			return -1 !== selectedIds.indexOf( id );
		} );

		if ( allSelected ) {
			setSelectedIds( [] );
		} else {
			setSelectedIds( allIds );
		}
	}, [ items, selectedIds ] );

	/**
	 * Handle bulk action apply.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleBulkApply = useCallback( function() {
		if ( ! bulkAction || 0 === selectedIds.length || bulkProcessing ) {
			return;
		}

		setBulkProcessing( true );

		emailTemplateBulkAction( selectedIds, bulkAction )
			.then( function( response ) {
				setBulkProcessing( false );
				if ( response.success ) {
					setToast( { status: 'success', message: response.data.message } );
					setSelectedIds( [] );
					setBulkAction( '' );
					// Re-fetch to reflect changes.
					isFirstLoad.current = true;
					fetchTemplates( { fetchPage: page, fetchSort: sort, fetchSearch: search } );
				} else {
					setToast( {
						status: 'error',
						message: ( response.data && response.data.message ) || __( 'Something went wrong.', 'buddyboss' ),
					} );
				}
			} )
			.catch( function() {
				setBulkProcessing( false );
				setToast( { status: 'error', message: __( 'Something went wrong.', 'buddyboss' ) } );
			} );
	}, [ bulkAction, selectedIds, bulkProcessing, page, sort, search, fetchTemplates ] );

	var allSelected = items.length > 0 && items.every( function( item ) {
		return -1 !== selectedIds.indexOf( item.id );
	} );

	// Build bulk action options for the dropdown.
	var bulkActionOptions = [ { label: __( 'Bulk actions', 'buddyboss' ), value: '' } ];
	Object.keys( bulkActions ).forEach( function( key ) {
		bulkActionOptions.push( { label: decodeEntities( bulkActions[ key ] ), value: key } );
	} );

	// Derive custom column keys from server-provided columns (third-party plugins like WPML).
	var customColumnKeys = useMemo( function() {
		return Object.keys( columns ).filter( function( key ) {
			return CORE_COLUMNS.indexOf( key ) === -1;
		} );
	}, [ columns ] );

	var pageNumbers = getPageNumbers( page, totalPages );

	return (
		<div className="bb-email-templates-list">
			{/* Header */}
			<div className="bb-email-templates-list__header">
				<h2 className="bb-email-templates-list__title">
					{ __( 'Email Templates', 'buddyboss' ) }
				</h2>
				{ addNewUrl && (
					<a
						href={ safeUrl( addNewUrl ) }
						className="bb-email-templates-list__create-btn components-button is-primary"
					>
						<i className="bb-icons-rl bb-icons-rl-plus" />
						{ __( 'Add New Email', 'buddyboss' ) }
					</a>
				) }
			</div>

			{/* Toolbar */}
			<div className="bb-email-templates-list__toolbar">
				<div className="bb-email-templates-list__toolbar-left">
					{/* Bulk Actions */}
					<div className="bb-email-templates-list__bulk-actions">
						<SelectControl
							value={ bulkAction }
							options={ bulkActionOptions }
							onChange={ setBulkAction }
							__nextHasNoMarginBottom
						/>
						<Button
							className="bb-email-templates-list__bulk-apply is-secondary"
							onClick={ handleBulkApply }
							disabled={ ! bulkAction || 0 === selectedIds.length || bulkProcessing }
						>
							{ __( 'Apply', 'buddyboss' ) }
						</Button>
					</div>
				</div>

				<div className="bb-email-templates-list__toolbar-right">
					{/* Count badge */}
					<div className="bb-email-templates-list__count-badge">
						<SelectControl
							value="all"
							options={ [
								{
									label: sprintf(
										/* translators: %d: total number of email templates */
										__( 'All (%d)', 'buddyboss' ),
										total
									),
									value: 'all',
								},
							] }
							onChange={ function() {} }
							__nextHasNoMarginBottom
						/>
					</div>

					{/* Sort */}
					<div className="bb-email-templates-list__sort-select">
						<SelectControl
							value={ sort }
							options={ sortOptions }
							onChange={ handleSortChange }
							__nextHasNoMarginBottom
						/>
					</div>

					{/* Search */}
					<div className="bb-email-templates-list__search">
						<input
							type="text"
							className="bb-email-templates-list__search-input"
							placeholder={ __( 'Search emails', 'buddyboss' ) }
							value={ searchInput }
							onChange={ handleSearchChange }
						/>
					</div>
				</div>
			</div>

			{/* Table */}
			<div className="bb-email-templates-list__table-wrapper">
				{ isLoading ? (
					<div className="bb-email-templates-list__loading">
						<Spinner />
					</div>
				) : 0 === items.length ? (
					<div className="bb-email-templates-list__empty">
						{ search
							? __( 'No email templates found matching your search.', 'buddyboss' )
							: __( 'No email templates found.', 'buddyboss' )
						}
					</div>
				) : (
					<table className="bb-email-templates-list__table">
						<thead>
							<tr>
								<th className="bb-email-templates-list__th--checkbox">
									<CheckboxControl
										checked={ allSelected }
										onChange={ handleSelectAll }
										__nextHasNoMarginBottom
									/>
								</th>
								<th className="bb-email-templates-list__th--title">
									{ __( 'Title', 'buddyboss' ) }
								</th>
								{ customColumnKeys.map( function( key ) {
									return (
										<th key={ key } className={ 'bb-email-templates-list__th--custom bb-email-templates-list__th--' + key }>
											{ columns[ key ] ? decodeEntities( columns[ key ] ) : '' }
										</th>
									);
								} ) }
								<th className="bb-email-templates-list__th--description">
									{ __( 'Situations', 'buddyboss' ) }
								</th>
								<th className="bb-email-templates-list__th--date">
									{ __( 'Published', 'buddyboss' ) }
								</th>
								<th className="bb-email-templates-list__th--actions"></th>
							</tr>
						</thead>
						<tbody>
							{ items.map( function( item ) {
								var isSelected = -1 !== selectedIds.indexOf( item.id );
								return (
									<tr
										key={ item.id }
										className={
											'bb-email-templates-list__row' +
											( isSelected ? ' bb-email-templates-list__row--selected' : '' )
										}
									>
										<td className="bb-email-templates-list__td--checkbox">
											<CheckboxControl
												checked={ isSelected }
												onChange={ function() {
													handleToggleSelect( item.id );
												} }
												__nextHasNoMarginBottom
											/>
										</td>
										<td className="bb-email-templates-list__td--title">
											<a
												href={ safeUrl( item.edit_url ) }
												className="bb-email-templates-list__item-title"
											>
												{ decodeEntities( item.title ) }
											</a>
										</td>
										{ item.custom_columns && customColumnKeys.map( function( key ) {
											return (
												<td key={ key } className={ 'bb-email-templates-list__td--custom bb-email-templates-list__td--' + key }>
													<span dangerouslySetInnerHTML={ { __html: sanitizeHtml( item.custom_columns[ key ] ) } } />
												</td>
											);
										} ) }
										<td className="bb-email-templates-list__td--description">
											{ decodeEntities( item.description ) }
										</td>
										<td className="bb-email-templates-list__td--date">
											<i className="bb-icons-rl bb-icons-rl-clock"></i>
											{ item.date }
										</td>
										<td className="bb-email-templates-list__td--actions">
											<DropdownMenu
												icon={ <i className="bb-icons-rl-dots-three"></i> }
												label={ __( 'More options', 'buddyboss' ) }
											>
												{ function( { onClose } ) {
													return (
														<MenuGroup className="bb_dropdown_menu_group">
															<MenuItem
																onClick={ function() {
																	if ( item.edit_url ) {
																		window.location.href = safeUrl( item.edit_url );
																	}
																	onClose();
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-pencil-simple"></i>
																{ __( 'Edit', 'buddyboss' ) }
															</MenuItem>
															<MenuItem
																isDestructive
																onClick={ function() {
																	onClose();
																	setBulkProcessing( true );
																	emailTemplateBulkAction( [ item.id ], 'trash' )
																		.then( function( response ) {
																			setBulkProcessing( false );
																			if ( response.success ) {
																				setToast( { status: 'success', message: response.data.message } );
																				isFirstLoad.current = true;
																				fetchTemplates( { fetchPage: page, fetchSort: sort, fetchSearch: search } );
																			} else {
																				setToast( {
																					status: 'error',
																					message: ( response.data && response.data.message ) || __( 'Something went wrong.', 'buddyboss' ),
																				} );
																			}
																		} )
																		.catch( function() {
																			setBulkProcessing( false );
																			setToast( { status: 'error', message: __( 'Something went wrong.', 'buddyboss' ) } );
																		} );
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-trash"></i>
																{ __( 'Trash', 'buddyboss' ) }
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

			{/* Footer with pagination */}
			{ totalPages > 1 && ! isLoading && (
				<div className="bb-email-templates-list__footer">
					<span className="bb-email-templates-list__item-count">
						{ sprintf(
							/* translators: 1: start index, 2: end index, 3: total items */
							__( '%1$d\u2013%2$d of %3$d', 'buddyboss' ),
							( page - 1 ) * PER_PAGE + 1,
							Math.min( page * PER_PAGE, total ),
							total
						) }
					</span>
					<div className="bb-admin-pagination__pagination">
						<Button
							className="bb-admin-pagination__pagination-btn bb-admin-pagination__pagination-btn--previous is-secondary"
							disabled={ 1 === page }
							onClick={ function() {
								handlePageChange( page - 1 );
							} }
						>
							{ __( 'Previous', 'buddyboss' ) }
						</Button>
						{ pageNumbers.map( function( p, idx ) {
							if ( '...' === p ) {
								return (
									<span key={ 'ellipsis-' + idx } className="bb-admin-pagination__pagination-ellipsis">
										&hellip;
									</span>
								);
							}
							return (
								<Button
									key={ p }
									className={
										'bb-admin-pagination__pagination-btn' +
										( p === page ? ' bb-admin-pagination__pagination-btn--current is-primary' : ' is-secondary' )
									}
									onClick={ function() {
										handlePageChange( p );
									} }
								>
									{ p }
								</Button>
							);
						} ) }
						<Button
							className="bb-admin-pagination__pagination-btn bb-admin-pagination__pagination-btn--next is-secondary"
							disabled={ page === totalPages }
							onClick={ function() {
								handlePageChange( page + 1 );
							} }
						>
							{ __( 'Next', 'buddyboss' ) }
						</Button>
					</div>
				</div>
			) }

			{/* Toast notification */}
			{ toast && (
				<div className="bb-toast-container">
					<Toast
						status={ toast.status }
						message={ toast.message }
						onDismiss={ function() { setToast( null ); } }
					/>
				</div>
			) }
		</div>
	);
}
