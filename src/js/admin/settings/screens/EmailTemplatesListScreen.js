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
import { sanitizeHtml, safeUrl } from '../utils/sanitize';
import { ListPagination } from '../components/common/ListPagination';
import { Toast, useAutoDismissToast } from '../components/Toast';
import { useListScreenState } from '../hooks/useListScreenState';
import { useListScreenHandlers } from '../hooks/useListScreenHandlers';
import { EmailTemplateModal } from '../components/emails/EmailTemplateModal';
import { EmailTemplateBulkEditModal } from '../components/emails/EmailTemplateBulkEditModal';
import { EmailTemplateBulkDeleteModal } from '../components/emails/EmailTemplateBulkDeleteModal';
import { DeleteConfirmModal } from '../components/common/DeleteConfirmModal';
import { EmailMissingModal } from '../components/emails/EmailMissingModal';

/**
 * Sort options for the email templates list dropdown (static, never changes).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var sortOptions = [
	{ label: __( 'Newest', 'buddyboss-platform' ), value: 'newest' },
	{ label: __( 'Oldest', 'buddyboss-platform' ), value: 'oldest' },
	{ label: __( 'Last Modified', 'buddyboss-platform' ), value: 'last_modified' },
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
 * @param {Object} props Component props.
 * @returns {JSX.Element} The email templates list screen.
 */
export default function EmailTemplatesListScreen( props ) {
	// Common list screen state (loading, notice, selection, bulk, search).
	var common = useListScreenState();
	var isLoading = common.isLoading;
	var setIsLoading = common.setIsLoading;
	var selectedIds = common.selectedIds;
	var setSelectedIds = common.setSelectedIds;
	var bulkAction = common.bulkAction;
	var setBulkAction = common.setBulkAction;
	var bulkProcessing = common.isBulkProcessing;
	var setBulkProcessing = common.setIsBulkProcessing;
	var searchInput = common.searchInput;
	var setSearchInput = common.setSearchInput;
	var search = common.searchQuery;
	var setSearch = common.setSearchQuery;

	// Screen-specific state.
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

	var stateFilter = useState( 'all' );
	var filter = stateFilter[0];
	var setFilter = stateFilter[1];

	// Ref for filter so fetchTemplates always reads the latest value without re-creating the callback.
	var filterRef = useRef( filter );
	filterRef.current = filter;

	var stateViews = useState( null );
	var views = stateViews[0];
	var setViews = stateViews[1];

	var stateBulkActions = useState( {} );
	var bulkActions = stateBulkActions[0];
	var setBulkActions = stateBulkActions[1];

	var stateColumns = useState( {} );
	var columns = stateColumns[0];
	var setColumns = stateColumns[1];

	var stateToast = useState( null );
	var toast = stateToast[0];
	var setToast = stateToast[1];

	// Create field definitions from server (for add modal).
	var stateCreateFields = useState( [] );
	var createFields = stateCreateFields[0];
	var setCreateFields = stateCreateFields[1];

	// Modal states.
	var stateEditModalOpen = useState( false );
	var editModalOpen = stateEditModalOpen[0];
	var setEditModalOpen = stateEditModalOpen[1];

	var stateEditEmailId = useState( 0 );
	var editEmailId = stateEditEmailId[0];
	var setEditEmailId = stateEditEmailId[1];

	var stateBulkEditModalOpen = useState( false );
	var bulkEditModalOpen = stateBulkEditModalOpen[0];
	var setBulkEditModalOpen = stateBulkEditModalOpen[1];

	var stateBulkDeleteModalOpen = useState( false );
	var bulkDeleteModalOpen = stateBulkDeleteModalOpen[0];
	var setBulkDeleteModalOpen = stateBulkDeleteModalOpen[1];

	var stateDeleteItem = useState( null );
	var deleteItem = stateDeleteItem[0];
	var setDeleteItem = stateDeleteItem[1];

	var stateDeleteConfirmChecked = useState( false );
	var deleteConfirmChecked = stateDeleteConfirmChecked[0];
	var setDeleteConfirmChecked = stateDeleteConfirmChecked[1];

	// Missing email states.
	var stateMissingCount = useState( 0 );
	var missingCount = stateMissingCount[0];
	var setMissingCount = stateMissingCount[1];

	var stateMissingEmails = useState( [] );
	var missingEmails = stateMissingEmails[0];
	var setMissingEmails = stateMissingEmails[1];

	var stateMissingModalOpen = useState( false );
	var missingModalOpen = stateMissingModalOpen[0];
	var setMissingModalOpen = stateMissingModalOpen[1];

	var abortRef = useRef( null );
	var isFirstLoad = useRef( true );

	// Common list screen handlers (select all, select row, search timer cleanup).
	var handlers = useListScreenHandlers( {
		setSearchInput: setSearchInput,
		setSearchQuery: setSearch,
		setPage: setPage,
		setSelectedIds: setSelectedIds,
		setSort: setSort,
		setFilter: setFilter,
		getItemIds: function () {
			return items.map( function ( item ) { return item.id; } );
		},
	} );
	var searchTimerRef = handlers.searchTimerRef;

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
		var fetchFilter = params.fetchFilter !== undefined ? params.fetchFilter : filterRef.current;

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
			status_filter: fetchFilter,
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

					if ( response.data.bulk_actions ) {
						setBulkActions( response.data.bulk_actions );
					}

					if ( response.data.columns ) {
						setColumns( response.data.columns );
					}

					if ( response.data.create_fields ) {
						setCreateFields( response.data.create_fields );
					}

					if ( response.data.views ) {
						setViews( response.data.views );
					}

					// Update missing email data when metadata is included.
					if ( 'undefined' !== typeof response.data.missing_count ) {
						setMissingCount( response.data.missing_count );
					}
					if ( response.data.missing_emails ) {
						setMissingEmails( response.data.missing_emails );
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

	// Initial fetch + auto-open missing modal if ?popup=yes.
	useEffect( function() {
		fetchTemplates( { fetchPage: 1, fetchSort: sort, fetchSearch: '' } );

		// Check URL params for auto-open missing email modal.
		var urlParams = new URLSearchParams( window.location.search );
		if ( 'yes' === urlParams.get( 'popup' ) ) {
			setMissingModalOpen( true );
		}

		return function() {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
			if ( searchTimerRef.current ) {
				clearTimeout( searchTimerRef.current );
			}
		};
	}, [] );

	useAutoDismissToast( toast, setToast );

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
		fetchTemplates( { fetchPage: 1, fetchSort: newSort, fetchSearch: search, fetchFilter: filter } );
	}, [ search, filter, fetchTemplates ] );

	/**
	 * Handle filter change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} newFilter New filter value.
	 */
	var handleFilterChange = useCallback( function( newFilter ) {
		setFilter( newFilter );
		setPage( 1 );
		setSelectedIds( [] );
		fetchTemplates( { fetchPage: 1, fetchSort: sort, fetchSearch: search, fetchFilter: newFilter } );
	}, [ sort, search, fetchTemplates ] );

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

	// Use shared select handlers from useListScreenHandlers.
	var handleToggleSelect = function( id ) {
		handlers.handleSelectRow( id, -1 === selectedIds.indexOf( id ) );
	};

	/**
	 * Handle bulk action apply.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleBulkApply = useCallback( function() {
		if ( ! bulkAction || 0 === selectedIds.length || bulkProcessing ) {
			return;
		}

		// Route bulk_edit and delete to their modals.
		if ( 'bulk_edit' === bulkAction ) {
			setBulkEditModalOpen( true );
			return;
		}

		if ( 'delete' === bulkAction ) {
			setBulkDeleteModalOpen( true );
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
						message: ( response.data && response.data.message ) || __( 'Something went wrong.', 'buddyboss-platform' ),
					} );
				}
			} )
			.catch( function() {
				setBulkProcessing( false );
				setToast( { status: 'error', message: __( 'Something went wrong.', 'buddyboss-platform' ) } );
			} );
	}, [ bulkAction, selectedIds, bulkProcessing, page, sort, search, fetchTemplates ] );

	var allSelected = items.length > 0 && items.every( function( item ) {
		return -1 !== selectedIds.indexOf( item.id );
	} );

	// Build bulk action options for the dropdown — Edit + Delete per Figma.
	var bulkActionOptions = [
		{ label: __( 'Bulk actions', 'buddyboss-platform' ), value: '' },
		{ label: __( 'Edit', 'buddyboss-platform' ), value: 'bulk_edit' },
		{ label: __( 'Delete', 'buddyboss-platform' ), value: 'delete' },
	];

	// Derive custom column keys from server-provided columns (third-party plugins like WPML).
	var customColumnKeys = useMemo( function() {
		return Object.keys( columns ).filter( function( key ) {
			return CORE_COLUMNS.indexOf( key ) === -1;
		} );
	}, [ columns ] );

	return (
		<div className="bb-email-templates-list">
			{/* Header */}
			<div className="bb-email-templates-list__header">
				<h2 className="bb-email-templates-list__title">
					{ __( 'Email Templates', 'buddyboss-platform' ) }
				</h2>
				<a
					href="themes.php?page=bp-emails-customizer-redirect"
					className="bb-email-templates-list__customize-btn is-secondary"
					target="_blank"
					rel="noopener noreferrer"
				>
					<i className="bb-icons-rl bb-icons-rl-gear" />
					{ __( 'Customize Layout', 'buddyboss-platform' ) }
				</a>
				<Button
					className="bb-email-templates-list__create-btn is-primary"
					onClick={ function() {
						setEditEmailId( 0 );
						setEditModalOpen( true );
					} }
				>
					<i className="bb-icons-rl bb-icons-rl-plus" />
					{ __( 'Add New Email', 'buddyboss-platform' ) }
				</Button>
			</div>

			{/* Missing email warning banner */}
			{ missingCount > 0 && (
				<div className="bb-email-missing-banner">
					<div className="bb-email-missing-banner__left">
						<i className="bb-icons-rl bb-icons-rl-warning-circle" />
						<span>
							{ sprintf(
								/* translators: %d: number of missing emails */
								__( 'Email Missing (%d)', 'buddyboss-platform' ),
								missingCount
							) }
						</span>
					</div>
					<a
						href="#"
						className="bb-email-missing-banner__action"
						onClick={ function( e ) {
							e.preventDefault();
							setMissingModalOpen( true );
						} }
					>
						{ __( 'Add Missing Email', 'buddyboss-platform' ) }
						<i className="bb-icons-rl-plus"></i>
					</a>
				</div>
			) }

			{/* Toolbar */}
			<div className="bb-email-templates-list__toolbar bb-admin-list-toolbar">
				<div className="bb-email-templates-list__toolbar-left bb-admin-list-toolbar__left">
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
							{ __( 'Apply', 'buddyboss-platform' ) }
						</Button>
					</div>
				</div>

				<div className="bb-email-templates-list__toolbar-right bb-admin-list-toolbar__right">
					{/* Status filter */}
					<div className="bb-email-templates-list__filter-select">
						<SelectControl
							value={ filter }
							options={ views ? [
								{ label: sprintf( __( 'All (%d)', 'buddyboss-platform' ), views.all || 0 ), value: 'all' },
								{ label: sprintf( __( 'Published (%d)', 'buddyboss-platform' ), views.publish || 0 ), value: 'publish' },
								{ label: sprintf( __( 'Draft (%d)', 'buddyboss-platform' ), views.draft || 0 ), value: 'draft' },
							] : [
								{ label: sprintf( __( 'All (%d)', 'buddyboss-platform' ), total ), value: 'all' },
							] }
							onChange={ handleFilterChange }
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
					<div className="bb-email-templates-list__search bb-admin-list-search">
						<input
							type="text"
							className="bb-email-templates-list__search-input bb-admin-list-search__input"
							placeholder={ __( 'Search emails', 'buddyboss-platform' ) }
							value={ searchInput }
							onChange={ handleSearchChange }
						/>
					</div>
				</div>
			</div>

			{/* Table */}
			<div className="bb-email-templates-list__table-wrapper">
				{ isLoading ? (
					<div className="bb-email-templates-list__loading bb-admin-list-table__loading">
						<Spinner />
					</div>
				) : 0 === items.length ? (
					<div className="bb-email-templates-list__empty bb-admin-list-table__empty">
						{ search
							? __( 'No email templates found matching your search.', 'buddyboss-platform' )
							: __( 'No email templates found.', 'buddyboss-platform' )
						}
					</div>
				) : (
					<table className="bb-email-templates-list__table bb-admin-list-table">
						<thead>
							<tr>
								<th className="bb-email-templates-list__th--checkbox bb-admin-list-table__checkbox">
									<CheckboxControl
										checked={ allSelected }
										onChange={ handlers.handleSelectAll }
										__nextHasNoMarginBottom
									/>
								</th>
								<th className="bb-email-templates-list__th--title">
									{ __( 'Title', 'buddyboss-platform' ) }
								</th>
								{ customColumnKeys.map( function( key ) {
									return (
										<th key={ key } className={ 'bb-email-templates-list__th--custom bb-email-templates-list__th--' + key }>
											{ columns[ key ] ? decodeEntities( columns[ key ] ) : '' }
										</th>
									);
								} ) }
								<th className="bb-email-templates-list__th--description">
									{ __( 'Situations', 'buddyboss-platform' ) }
								</th>
								<th className="bb-email-templates-list__th--date">
									{ __( 'Published', 'buddyboss-platform' ) }
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
											'bb-email-templates-list__row bb-admin-list-table__row' +
											( isSelected ? ' bb-email-templates-list__row--selected bb-admin-list-table__row--selected' : '' )
										}
									>
										<td className="bb-email-templates-list__td--checkbox bb-admin-list-table__checkbox">
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
												href="#"
												className="bb-email-templates-list__item-title"
												onClick={ function( e ) {
													e.preventDefault();
													setEditEmailId( item.id );
													setEditModalOpen( true );
												} }
											>
												{ decodeEntities( item.title ) }
											</a>
											{ item.status_label && (
												<span className="bb-admin-list__status-badge">
													{ item.status_label }
												</span>
											) }
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
										<td className="bb-email-templates-list__td--actions bb-admin-actions-toggle">
											<DropdownMenu
												icon={ <i className="bb-icons-rl-dots-three"></i> }
												label={ __( 'More options', 'buddyboss-platform' ) }
											>
												{ function( { onClose } ) {
													return (
														<MenuGroup className="bb_dropdown_menu_group">
															{ item.permalink && (
																<MenuItem
																	onClick={ function() {
																		window.open( safeUrl( item.permalink ), '_blank', 'noopener noreferrer' );
																		onClose();
																	} }
																>
																	<i className="bb-icons-rl bb-icons-rl-eye"></i>
																	{ __( 'Preview', 'buddyboss-platform' ) }
																	<i className="bb-icons-rl bb-icons-rl-arrow-up-right bb-icons-external"></i>
																</MenuItem>
															) }
															<MenuItem
																onClick={ function() {
																	onClose();
																	setEditEmailId( item.id );
																	setEditModalOpen( true );
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-note-pencil"></i>
																{ __( 'Edit', 'buddyboss-platform' ) }
															</MenuItem>
															<MenuItem
																isDestructive
																onClick={ function() {
																	onClose();
																	setDeleteConfirmChecked( false );
																	setDeleteItem( item );
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-trash"></i>
																{ __( 'Delete', 'buddyboss-platform' ) }
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
			{ ! isLoading && (
				<ListPagination
					currentPage={ page }
					totalPages={ totalPages }
					total={ total }
					onPageChange={ handlePageChange }
					className="bb-email-templates-list"
				/>
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

			{/* Add/Edit Modal */}
			<EmailTemplateModal
				isOpen={ editModalOpen }
				emailId={ editEmailId }
				createFields={ createFields }
				onClose={ function() {
					setEditModalOpen( false );
					setEditEmailId( 0 );
				} }
				onSaved={ function() {
					setEditModalOpen( false );
					setEditEmailId( 0 );
					isFirstLoad.current = true;
					fetchTemplates( { fetchPage: page, fetchSort: sort, fetchSearch: search } );
					setToast( { status: 'success', message: __( 'Email template saved.', 'buddyboss-platform' ) } );
				} }
			/>

			{/* Bulk Edit Modal */}
			<EmailTemplateBulkEditModal
				isOpen={ bulkEditModalOpen }
				selectedItems={ items.filter( function( item ) {
					return -1 !== selectedIds.indexOf( item.id );
				} ) }
				onClose={ function() {
					setBulkEditModalOpen( false );
				} }
				onSaved={ function() {
					setBulkEditModalOpen( false );
					setSelectedIds( [] );
					setBulkAction( '' );
					isFirstLoad.current = true;
					fetchTemplates( { fetchPage: page, fetchSort: sort, fetchSearch: search } );
					setToast( { status: 'success', message: __( 'Email templates updated.', 'buddyboss-platform' ) } );
				} }
			/>

			{/* Single Delete Confirmation Modal */}
			<DeleteConfirmModal
				isOpen={ !! deleteItem }
				singleTitle={ __( 'Delete email template?', 'buddyboss-platform' ) }
				items={ deleteItem ? [ { id: deleteItem.id, title: deleteItem.title } ] : [] }
				warningText={ __( 'This permanently deletes email templates and cannot be undone.', 'buddyboss-platform' ) }
				description={ __( 'Deleting the email template will remove it from the list and automatically unlink it from any associated situations.', 'buddyboss-platform' ) }
				confirmLabel={ __( 'I understand that this deletes the email template.', 'buddyboss-platform' ) }
				confirmChecked={ deleteConfirmChecked }
				onConfirmChange={ setDeleteConfirmChecked }
				onConfirm={ function () {
					if ( ! deleteItem ) {
						return;
					}
					var itemId = deleteItem.id;
					setDeleteItem( null );
					setDeleteConfirmChecked( false );
					setBulkProcessing( true );
					emailTemplateBulkAction( [ itemId ], 'trash' )
						.then( function ( response ) {
							setBulkProcessing( false );
							if ( response.success ) {
								setToast( { status: 'success', message: response.data.message } );
								isFirstLoad.current = true;
								fetchTemplates( { fetchPage: page, fetchSort: sort, fetchSearch: search } );
							} else {
								setToast( {
									status: 'error',
									message: ( response.data && response.data.message ) || __( 'Something went wrong.', 'buddyboss-platform' ),
								} );
							}
						} )
						.catch( function () {
							setBulkProcessing( false );
							setToast( { status: 'error', message: __( 'Something went wrong.', 'buddyboss-platform' ) } );
						} );
				} }
				onClose={ function () { setDeleteItem( null ); setDeleteConfirmChecked( false ); } }
				isProcessing={ bulkProcessing }
				className="bb-email-delete-modal"
			/>

			{/* Bulk Delete Modal */}
			<EmailTemplateBulkDeleteModal
				isOpen={ bulkDeleteModalOpen }
				selectedItems={ items.filter( function( item ) {
					return -1 !== selectedIds.indexOf( item.id );
				} ) }
				onRemoveItem={ function( id ) {
					setSelectedIds( function( prev ) {
						var next = prev.filter( function( i ) { return i !== id; } );
						if ( 0 === next.length ) {
							setBulkDeleteModalOpen( false );
						}
						return next;
					} );
				} }
				onClose={ function() {
					setBulkDeleteModalOpen( false );
				} }
				onDeleted={ function() {
					setBulkDeleteModalOpen( false );
					setSelectedIds( [] );
					setBulkAction( '' );
					isFirstLoad.current = true;
					fetchTemplates( { fetchPage: page, fetchSort: sort, fetchSearch: search } );
				} }
				setToast={ setToast }
			/>

			{/* Email Missing Modal */}
			<EmailMissingModal
				isOpen={ missingModalOpen }
				isLoading={ isLoading && 0 === missingEmails.length }
				missingCount={ missingCount }
				missingEmails={ missingEmails }
				onClose={ function() {
					setMissingModalOpen( false );
				} }
				onInstalled={ function() {
					setMissingModalOpen( false );
					setMissingCount( 0 );
					setMissingEmails( [] );
					isFirstLoad.current = true;
					fetchTemplates( { fetchPage: page, fetchSort: sort, fetchSearch: search } );
				} }
				setToast={ setToast }
			/>
		</div>
	);
}
