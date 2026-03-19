/**
 * BuddyBoss Admin Settings 2.0 - Discussion Tags List Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import {
	Button,
	Spinner,
	DropdownMenu,
	MenuGroup,
	MenuItem,
	Modal,
	SelectControl,
	CheckboxControl,

} from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getTopicTags, getTopicTag, deleteTopicTag, topicTagBulkAction } from '../utils/ajax';
import { safeUrl } from '../utils/sanitize';
import { ListPagination } from '../components/common/ListPagination';
import { AdminNotice } from '../components/common/AdminNotice';
import { ListToolbar } from '../components/common/ListToolbar';
import { TagCreateModal } from '../components/forums/TagCreateModal';

/**
 * Number of tags to fetch per page.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {number}
 */
var TAGS_PER_PAGE = 20;

/**
 * Discussion Tags List Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onNavigate Navigation handler.
 * @returns {JSX.Element} Discussion tags list screen.
 */
export default function DiscussionTagsListScreen( { onNavigate } ) {
	var tagsState = useState( [] );
	var tags = tagsState[ 0 ];
	var setTags = tagsState[ 1 ];

	var isLoadingState = useState( true );
	var isLoading = isLoadingState[ 0 ];
	var setIsLoading = isLoadingState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	// Pagination.
	var currentPageState = useState( 1 );
	var currentPage = currentPageState[ 0 ];
	var setCurrentPage = currentPageState[ 1 ];

	var totalState = useState( 0 );
	var total = totalState[ 0 ];
	var setTotal = totalState[ 1 ];

	var totalPages = Math.ceil( total / TAGS_PER_PAGE );

	// Search.
	var searchInputState = useState( '' );
	var searchInput = searchInputState[ 0 ];
	var setSearchInput = searchInputState[ 1 ];

	var searchQueryState = useState( '' );
	var searchQuery = searchQueryState[ 0 ];
	var setSearchQuery = searchQueryState[ 1 ];

	var searchTimerRef = useRef( null );

	// Selection state for bulk actions.
	var selectedIdsState = useState( [] );
	var selectedIds = selectedIdsState[ 0 ];
	var setSelectedIds = selectedIdsState[ 1 ];

	// Bulk actions.
	var bulkActionState = useState( '' );
	var bulkAction = bulkActionState[ 0 ];
	var setBulkAction = bulkActionState[ 1 ];

	var isBulkProcessingState = useState( false );
	var isBulkProcessing = isBulkProcessingState[ 0 ];
	var setIsBulkProcessing = isBulkProcessingState[ 1 ];

	// Bulk delete modal.
	var bulkDeleteOpenState = useState( false );
	var bulkDeleteOpen = bulkDeleteOpenState[ 0 ];
	var setBulkDeleteOpen = bulkDeleteOpenState[ 1 ];

	var bulkDeleteTargetIdsState = useState( [] );
	var bulkDeleteTargetIds = bulkDeleteTargetIdsState[ 0 ];
	var setBulkDeleteTargetIds = bulkDeleteTargetIdsState[ 1 ];

	var bulkDeleteConfirmState = useState( false );
	var bulkDeleteConfirm = bulkDeleteConfirmState[ 0 ];
	var setBulkDeleteConfirm = bulkDeleteConfirmState[ 1 ];

	// Create/Edit modal.
	var isCreateOpenState = useState( false );
	var isCreateOpen = isCreateOpenState[ 0 ];
	var setIsCreateOpen = isCreateOpenState[ 1 ];

	var editTagState = useState( null );
	var editTag = editTagState[ 0 ];
	var setEditTag = editTagState[ 1 ];

	// Inline edit state.
	var isEditOpenState = useState( false );
	var isEditOpen = isEditOpenState[ 0 ];
	var setIsEditOpen = isEditOpenState[ 1 ];

	var isEditLoadingState = useState( false );
	var isEditLoading = isEditLoadingState[ 0 ];
	var setIsEditLoading = isEditLoadingState[ 1 ];

	// Single delete modal state.
	var deleteTagState = useState( null );
	var deleteTagItem = deleteTagState[ 0 ];
	var setDeleteTagItem = deleteTagState[ 1 ];

	var isDeletingState = useState( false );
	var isDeleting = isDeletingState[ 0 ];
	var setIsDeleting = isDeletingState[ 1 ];

	var deleteConfirmState = useState( false );
	var deleteConfirm = deleteConfirmState[ 0 ];
	var setDeleteConfirm = deleteConfirmState[ 1 ];

	// Notice state.
	var noticeState = useState( null );
	var notice = noticeState[ 0 ];
	var setNotice = noticeState[ 1 ];

	// AbortController ref for cancelling stale requests.
	var abortRef = useRef( null );

	/**
	 * Fetch tags from the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} params Query parameters override.
	 */
	var fetchTags = useCallback( function ( params ) {
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		abortRef.current = new AbortController();

		setIsLoading( true );
		setError( '' );

		var queryParams = {
			page: params && params.page ? params.page : currentPage,
			per_page: TAGS_PER_PAGE,
		};

		if ( params && params.search ) {
			queryParams.search = params.search;
		} else if ( searchQuery ) {
			queryParams.search = searchQuery;
		}

		getTopicTags( queryParams, { signal: abortRef.current.signal } ).then( function ( response ) {
			if ( response.success && response.data ) {
				setTags( response.data.tags || [] );
				setTotal( response.data.total || 0 );
			} else {
				setError( ( response.data && response.data.message ) || __( 'Failed to load tags.', 'buddyboss' ) );
			}
			setIsLoading( false );
		} ).catch( function ( err ) {
			if ( err && 'AbortError' === err.name ) {
				return;
			}
			setIsLoading( false );
			setError( __( 'Failed to load tags.', 'buddyboss' ) );
		} );
	}, [ currentPage, searchQuery ] );

	// Initial fetch.
	useEffect( function () {
		fetchTags( { page: 1 } );

		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
			if ( searchTimerRef.current ) {
				clearTimeout( searchTimerRef.current );
			}
		};
	}, [] );

	// Refetch when searchQuery changes.
	useEffect( function () {
		setCurrentPage( 1 );
		setSelectedIds( [] );
		fetchTags( { page: 1, search: searchQuery } );
	}, [ searchQuery ] );



	/**
	 * Handle search input change with debounce.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} value Search input value.
	 */
	var handleSearchChange = function ( value ) {
		setSearchInput( value );

		if ( searchTimerRef.current ) {
			clearTimeout( searchTimerRef.current );
		}

		searchTimerRef.current = setTimeout( function () {
			setSearchQuery( value );
		}, 500 );
	};

	/**
	 * Clear search input.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSearchClear = function () {
		setSearchInput( '' );
		if ( searchTimerRef.current ) {
			clearTimeout( searchTimerRef.current );
		}
		setSearchQuery( '' );
	};

	/**
	 * Handle page change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} newPage Page number.
	 */
	var handlePageChange = function ( newPage ) {
		setCurrentPage( newPage );
		setSelectedIds( [] );
		fetchTags( { page: newPage, search: searchQuery } );
	};

	/**
	 * Handle select all checkbox toggle.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSelectAll = function () {
		if ( selectedIds.length === tags.length ) {
			setSelectedIds( [] );
		} else {
			setSelectedIds( tags.map( function ( t ) { return t.id; } ) );
		}
	};

	/**
	 * Handle single row checkbox toggle.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} tagId Tag ID.
	 */
	var handleSelectRow = function ( tagId ) {
		setSelectedIds( function ( prev ) {
			if ( -1 !== prev.indexOf( tagId ) ) {
				return prev.filter( function ( id ) { return id !== tagId; } );
			}
			return prev.concat( [ tagId ] );
		} );
	};

	/**
	 * Handle bulk action apply.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleBulkApply = function () {
		if ( 'bulk_delete' === bulkAction && selectedIds.length > 0 ) {
			setBulkDeleteTargetIds( selectedIds.slice() );
			setBulkDeleteConfirm( false );
			setBulkDeleteOpen( true );
		}
	};

	/**
	 * Handle bulk delete confirm.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleBulkDeleteConfirm = function () {
		if ( 0 === bulkDeleteTargetIds.length ) {
			return;
		}

		setIsBulkProcessing( true );

		topicTagBulkAction( bulkDeleteTargetIds, 'delete' ).then( function ( response ) {
			setIsBulkProcessing( false );
			setBulkDeleteOpen( false );
			setBulkDeleteTargetIds( [] );
			setSelectedIds( [] );
			setBulkAction( '' );

			if ( response.success ) {
				setNotice( {
					message: __( 'Tags deleted successfully.', 'buddyboss' ),
					type: 'success',
				} );
				fetchTags( { page: currentPage, search: searchQuery } );
			} else {
				setNotice( {
					message: ( response.data && response.data.message ) || __( 'Failed to delete tags.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function () {
			setIsBulkProcessing( false );
			setBulkDeleteOpen( false );
			setNotice( {
				message: __( 'Failed to delete tags.', 'buddyboss' ),
				type: 'error',
			} );
		} );
	};

	/**
	 * Handle row edit action.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} tag Tag object from the list.
	 */
	var handleEdit = function ( tag ) {
		setEditTag( null );
		setIsEditOpen( true );
		setIsEditLoading( true );

		getTopicTag( tag.id ).then( function ( response ) {
			if ( response.success && response.data ) {
				setEditTag( response.data );
			} else {
				setIsEditOpen( false );
				setNotice( {
					message: __( 'Failed to load tag data.', 'buddyboss' ),
					type: 'error',
				} );
			}
			setIsEditLoading( false );
		} ).catch( function () {
			setIsEditOpen( false );
			setIsEditLoading( false );
			setNotice( {
				message: __( 'Failed to load tag data.', 'buddyboss' ),
				type: 'error',
			} );
		} );
	};

	/**
	 * Handle row delete action — open single delete modal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} tag Tag object.
	 */
	var handleDeleteClick = function ( tag ) {
		setDeleteTagItem( tag );
		setDeleteConfirm( false );
	};

	/**
	 * Confirm and execute single tag deletion.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleDeleteConfirm = function () {
		if ( ! deleteTagItem ) {
			return;
		}

		setIsDeleting( true );

		deleteTopicTag( deleteTagItem.id ).then( function ( response ) {
			setIsDeleting( false );
			setDeleteTagItem( null );
			setDeleteConfirm( false );
			if ( response.success ) {
				setNotice( {
					message: __( 'Tag deleted successfully.', 'buddyboss' ),
					type: 'success',
				} );
				setSelectedIds( function ( prev ) {
					return prev.filter( function ( id ) { return id !== deleteTagItem.id; } );
				} );
				fetchTags( { page: currentPage, search: searchQuery } );
			} else {
				setNotice( {
					message: ( response.data && response.data.message ) || __( 'Failed to delete tag.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function () {
			setIsDeleting( false );
			setDeleteTagItem( null );
			setDeleteConfirm( false );
			setNotice( {
				message: __( 'Failed to delete tag.', 'buddyboss' ),
				type: 'error',
			} );
		} );
	};

	/**
	 * Handle tag create/edit success.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleTagSaved = function () {
		setIsCreateOpen( false );
		setIsEditOpen( false );
		setEditTag( null );
		setNotice( {
			message: __( 'Tag saved successfully.', 'buddyboss' ),
			type: 'success',
		} );
		fetchTags( { page: currentPage, search: searchQuery } );
	};

	// Build selected tag names for bulk delete modal.
	var bulkDeleteTagNames = bulkDeleteTargetIds.map( function ( id ) {
		var found = tags.filter( function ( t ) { return t.id === id; } );
		return found.length > 0 ? found[ 0 ] : { id: id, name: '#' + id };
	} );

	var isAllSelected = tags.length > 0 && selectedIds.length === tags.length;

	return (
		<div className="bb-discussion-tags-list">
			{ /* Notice */ }
			<AdminNotice notice={ notice } onDismiss={ function () { setNotice( null ); } } />

			{ /* Header */ }
			<div className="bb-discussion-tags-list__header">
				<h2 className="bb-discussion-tags-list__title">
					{ __( 'Discussion Tags', 'buddyboss' ) }
				</h2>
				<Button
					variant="primary"
					className="bb-discussion-tags-list__add-btn"
					onClick={ function () {
						setEditTag( null );
						setIsCreateOpen( true );
					} }
				>
					<i className="bb-icons-rl bb-icons-rl-plus"></i>
					{ __( 'Add New Tag', 'buddyboss' ) }
				</Button>
			</div>

			{ /* Toolbar: Bulk actions + Search */ }
			<ListToolbar
				className="bb-discussion-tags-list"
				bulkAction={ bulkAction }
				bulkOptions={ [
					{ value: '', label: __( 'Bulk actions', 'buddyboss' ) },
					{ value: 'bulk_delete', label: __( 'Delete', 'buddyboss' ) },
				] }
				onBulkActionChange={ setBulkAction }
				onBulkApply={ handleBulkApply }
				selectedCount={ selectedIds.length }
				isBulkProcessing={ isBulkProcessing }
				searchInput={ searchInput }
				onSearchChange={ handleSearchChange }
				searchPlaceholder={ __( 'Search tags', 'buddyboss' ) }
				onSearchClear={ handleSearchClear }
			/>

			{ /* Loading / Error / Empty */ }
			{ isLoading && (
				<div className="bb-discussion-tags-list__loading">
					<Spinner />
				</div>
			) }

			{ ! isLoading && error && (
				<div className="bb-discussion-tags-list__error">
					<p>{ error }</p>
					<Button
						variant="secondary"
						onClick={ function () {
							fetchTags( { page: currentPage, search: searchQuery } );
						} }
					>
						{ __( 'Retry', 'buddyboss' ) }
					</Button>
				</div>
			) }

			{ ! isLoading && ! error && 0 === tags.length && (
				<div className="bb-discussion-tags-list__empty">
					<p>{ searchQuery ? __( 'No tags found matching your search.', 'buddyboss' ) : __( 'No discussion tags found.', 'buddyboss' ) }</p>
				</div>
			) }

			{ /* Table */ }
			{ ! isLoading && ! error && tags.length > 0 && (
				<div className="bb-discussion-tags-list__table-wrap">
					<table className="bb-discussion-tags-list__table">
						<thead>
							<tr>
								<th className="bb-discussion-tags-list__col-cb">
									<CheckboxControl
										checked={ isAllSelected }
										onChange={ handleSelectAll }
										__nextHasNoMarginBottom
									/>
								</th>
								<th className="bb-discussion-tags-list__col-tag">
									{ __( 'Discussion', 'buddyboss' ) }
								</th>
								<th className="bb-discussion-tags-list__col-slug">
									{ __( 'Slug', 'buddyboss' ) }
								</th>
								<th className="bb-discussion-tags-list__col-count">
									{ __( 'Count', 'buddyboss' ) }
								</th>
								<th className="bb-discussion-tags-list__col-actions"></th>
							</tr>
						</thead>
						<tbody>
							{ tags.map( function ( tag ) {
								var isSelected = -1 !== selectedIds.indexOf( tag.id );
								return (
									<tr key={ tag.id } className={ 'bb-discussion-tags-list__row' + ( isSelected ? ' bb-discussion-tags-list__row--selected' : '' ) }>
										<td className="bb-discussion-tags-list__col-cb">
											<CheckboxControl
												checked={ isSelected }
												onChange={ function () {
													handleSelectRow( tag.id );
												} }
												__nextHasNoMarginBottom
											/>
										</td>
										<td className="bb-discussion-tags-list__col-tag">
											<div className="bb-discussion-tags-list__tag-cell">
												<i className="bb-icons-rl bb-icons-rl-tag bb-discussion-tags-list__tag-icon"></i>
												<span className="bb-discussion-tags-list__tag-name">
													{ decodeEntities( tag.name ) }
												</span>
											</div>
										</td>
										<td className="bb-discussion-tags-list__col-slug">
											<span className="bb-discussion-tags-list__slug-badge">
												{ decodeEntities( tag.slug ) }
											</span>
										</td>
										<td className="bb-discussion-tags-list__col-count">
											<div className="bb-discussion-tags-list__count-cell">
												<i className="bb-icons-rl bb-icons-rl-chat-text bb-discussion-tags-list__count-icon"></i>
												{ tag.count > 0 ? (
													<a
														href={ safeUrl( window.location.pathname + '?page=bb-settings&tab=forums&panel=discussions&tag_id=' + tag.id ) }
														className="bb-discussion-tags-list__count-link"
													>
														{ sprintf(
															_n( '%s discussion', '%s discussions', tag.count, 'buddyboss' ),
															tag.count
														) }
													</a>
												) : (
													<span className="bb-discussion-tags-list__count-zero">
														{ sprintf(
															_n( '%s discussion', '%s discussions', tag.count, 'buddyboss' ),
															tag.count
														) }
													</span>
												) }
											</div>
										</td>
										<td className="bb-discussion-tags-list__col-actions">
											<DropdownMenu
												icon={ <i className="bb-icons-rl-dots-three"></i> }
												label={ __( 'More options', 'buddyboss' ) }
												className="bb-discussion-tags-list__actions-menu"
											>
												{ function ( dropdownProps ) {
													var onClose = dropdownProps.onClose;
													return (
														<MenuGroup className="bb_dropdown_menu_group">
															{ tag.permalink && (
																<MenuItem
																	onClick={ function () {
																		var permalink = safeUrl( tag.permalink );
																		if ( '#' !== permalink ) {
																			window.open( permalink, '_blank', 'noopener noreferrer' );
																		}
																		onClose();
																	} }
																>
																	<i className="bb-icons-rl bb-icons-rl-eye" aria-hidden="true"></i>
																	{ __( 'View', 'buddyboss' ) }
																	<i className="bb-icons-rl bb-icons-rl-arrow-up-right bb-icons-external" aria-hidden="true"></i>
																</MenuItem>
															) }
															<MenuItem
																onClick={ function () {
																	handleEdit( tag );
																	onClose();
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-note-pencil" aria-hidden="true"></i>
																{ __( 'Edit', 'buddyboss' ) }
															</MenuItem>
															<MenuItem
																isDestructive
																onClick={ function () {
																	handleDeleteClick( tag );
																	onClose();
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-trash" aria-hidden="true"></i>
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
				</div>
			) }

			{ /* Footer */ }
			{ ! isLoading && (
				<ListPagination
					currentPage={ currentPage }
					totalPages={ totalPages }
					total={ total }
					onPageChange={ handlePageChange }
					className="bb-discussion-tags-list"
				/>
			) }

			{ /* Create Modal */ }
			<TagCreateModal
				isOpen={ isCreateOpen }
				onClose={ function () {
					setIsCreateOpen( false );
					setEditTag( null );
				} }
				onSaved={ handleTagSaved }
				editTag={ null }
			/>

			{ /* Edit Modal */ }
			<TagCreateModal
				isOpen={ isEditOpen }
				onClose={ function () {
					setIsEditOpen( false );
					setEditTag( null );
				} }
				onSaved={ handleTagSaved }
				editTag={ editTag }
				isLoading={ isEditLoading }
			/>

			{ /* Single Delete Confirmation Modal (Figma: warning + confirm checkbox) */ }
			{ deleteTagItem && (
				<Modal
					title={ __( 'Delete Tag', 'buddyboss' ) }
					onRequestClose={ function () {
						setDeleteTagItem( null );
						setDeleteConfirm( false );
					} }
					className="bb-tag-delete-modal bb-admin-settings-modal"
					shouldCloseOnClickOutside={ false }
				>
					<div className="bb-tag-delete-modal__body">
						<div className="bb-admin-delete__warning">
							<i className="bb-icons-rl bb-icons-rl-warning-circle"></i>
							<div className="bb-admin-delete__warning-text">
								<span className="bb-admin-delete__warning-title">
									{ __( 'Warning', 'buddyboss' ) }
								</span>
								<span className="bb-admin-delete__warning-desc">
									{ __( 'This permanently deletes discussion tags from the community and cannot be undone.', 'buddyboss' ) }
								</span>
							</div>
						</div>
						<p className="bb-tag-delete-modal__description">
							{ __( 'Deleting discussion tags removes them from discussions, leaving those discussions untagged.', 'buddyboss' ) }
						</p>
						<CheckboxControl
							label={ __( 'I understand that this deletes the discussion tags.', 'buddyboss' ) }
							checked={ deleteConfirm }
							onChange={ setDeleteConfirm }
							__nextHasNoMarginBottom
						/>
					</div>
					<div className="bb-tag-delete-modal__footer">
						<Button
							variant="secondary"
							onClick={ function () {
								setDeleteTagItem( null );
								setDeleteConfirm( false );
							} }
							disabled={ isDeleting }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleDeleteConfirm }
							isBusy={ isDeleting }
							disabled={ ! deleteConfirm || isDeleting }
							isDestructive
						>
							{ __( 'Delete', 'buddyboss' ) }
						</Button>
					</div>
				</Modal>
			) }

			{ /* Bulk Delete Confirmation Modal (Figma: item list + warning + confirm) */ }
			{ bulkDeleteOpen && (
				<Modal
					title={ __( 'Bulk Delete', 'buddyboss' ) }
					onRequestClose={ function () {
						setBulkDeleteOpen( false );
						setBulkDeleteTargetIds( [] );
						setBulkDeleteConfirm( false );
					} }
					className="bb-tag-bulk-delete-modal bb-admin-settings-modal"
					shouldCloseOnClickOutside={ false }
				>
					<div className="bb-tag-bulk-delete-modal__body">
						<div className="bb-admin-bulk-modal__selected-items">
							{ bulkDeleteTagNames.map( function ( item ) {
								return (
									<div key={ item.id } className="bb-admin-bulk-modal__selected-item">
										<CheckboxControl
											checked={ true }
											onChange={ function () {
												setBulkDeleteTargetIds( function ( prev ) {
													var next = prev.filter( function ( i ) { return i !== item.id; } );
													if ( 0 === next.length ) {
														setBulkDeleteOpen( false );
													}
													return next;
												} );
												setSelectedIds( function ( prev ) {
													return prev.filter( function ( i ) { return i !== item.id; } );
												} );
											} }
											__nextHasNoMarginBottom
										/>
										<span className="bb-admin-bulk-modal__selected-item-name">
											{ decodeEntities( item.name ) }
										</span>
									</div>
								);
							} ) }
						</div>
						<div className="bb-admin-delete__warning">
							<i className="bb-icons-rl bb-icons-rl-warning-circle"></i>
							<div className="bb-admin-delete__warning-text">
								<span className="bb-admin-delete__warning-title">
									{ __( 'Warning', 'buddyboss' ) }
								</span>
								<span className="bb-admin-delete__warning-desc">
									{ __( 'This permanently deletes discussion tags from the community and cannot be undone.', 'buddyboss' ) }
								</span>
							</div>
						</div>
						<p className="bb-tag-bulk-delete-modal__description">
							{ __( 'Deleting discussion tags removes them from discussions, leaving those discussions untagged.', 'buddyboss' ) }
						</p>
						<CheckboxControl
							label={ __( 'I understand that this deletes the discussion tags.', 'buddyboss' ) }
							checked={ bulkDeleteConfirm }
							onChange={ setBulkDeleteConfirm }
							__nextHasNoMarginBottom
						/>
					</div>
					<div className="bb-tag-bulk-delete-modal__footer">
						<Button
							variant="secondary"
							onClick={ function () {
								setBulkDeleteOpen( false );
								setBulkDeleteTargetIds( [] );
								setBulkDeleteConfirm( false );
							} }
							disabled={ isBulkProcessing }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ handleBulkDeleteConfirm }
							isBusy={ isBulkProcessing }
							disabled={ ! bulkDeleteConfirm || isBulkProcessing }
						>
							{ __( 'Delete', 'buddyboss' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}
