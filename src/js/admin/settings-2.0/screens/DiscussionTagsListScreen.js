/**
 * BuddyBoss Admin Settings 2.0 - Discussion Tags List Screen
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
import { __, _n, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getTopicTags, getTopicTag, deleteTopicTag, topicTagBulkAction } from '../utils/ajax';
import { safeUrl } from '../utils/sanitize';
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
	var pageState = useState( 1 );
	var page = pageState[ 0 ];
	var setPage = pageState[ 1 ];

	var totalPagesState = useState( 1 );
	var totalPages = totalPagesState[ 0 ];
	var setTotalPages = totalPagesState[ 1 ];

	var totalItemsState = useState( 0 );
	var totalItems = totalItemsState[ 0 ];
	var setTotalItems = totalItemsState[ 1 ];

	// Search.
	var searchState = useState( '' );
	var search = searchState[ 0 ];
	var setSearch = searchState[ 1 ];

	var searchTimerRef = useRef( null );

	// Metadata (bulk actions, columns).
	var metaState = useState( null );
	var meta = metaState[ 0 ];
	var setMeta = metaState[ 1 ];

	// Selection state.
	var selectedState = useState( [] );
	var selected = selectedState[ 0 ];
	var setSelected = selectedState[ 1 ];

	// Bulk action state.
	var bulkActionState = useState( '' );
	var bulkAction = bulkActionState[ 0 ];
	var setBulkAction = bulkActionState[ 1 ];

	var isBulkProcessingState = useState( false );
	var isBulkProcessing = isBulkProcessingState[ 0 ];
	var setIsBulkProcessing = isBulkProcessingState[ 1 ];

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

	// Delete modal state.
	var deleteTagState = useState( null );
	var deleteTagItem = deleteTagState[ 0 ];
	var setDeleteTagItem = deleteTagState[ 1 ];

	var isDeletingState = useState( false );
	var isDeleting = isDeletingState[ 0 ];
	var setIsDeleting = isDeletingState[ 1 ];

	// Toast state.
	var toastState = useState( null );
	var toast = toastState[ 0 ];
	var setToast = toastState[ 1 ];

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
			page: params && params.page ? params.page : page,
			per_page: TAGS_PER_PAGE,
			search: params && 'undefined' !== typeof params.search ? params.search : search,
			include_meta: meta ? 0 : 1,
		};

		getTopicTags( queryParams, { signal: abortRef.current.signal } ).then( function ( response ) {
			if ( response.success && response.data ) {
				setTags( response.data.tags || [] );
				setTotalPages( response.data.total_pages || 1 );
				setTotalItems( response.data.total || 0 );

				if ( response.data.meta ) {
					setMeta( response.data.meta );
				}
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
	}, [ page, search, meta ] );

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

	/**
	 * Handle search input change with debounce.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} value Search text.
	 */
	var handleSearch = function ( value ) {
		setSearch( value );

		if ( searchTimerRef.current ) {
			clearTimeout( searchTimerRef.current );
		}

		searchTimerRef.current = setTimeout( function () {
			setPage( 1 );
			setSelected( [] );
			fetchTags( { page: 1, search: value } );
		}, 400 );
	};

	/**
	 * Handle page change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} newPage Page number.
	 */
	var handlePageChange = function ( newPage ) {
		setPage( newPage );
		setSelected( [] );
		fetchTags( { page: newPage } );
	};

	/**
	 * Handle select all checkbox toggle.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {boolean} checked Whether checkbox is checked.
	 */
	var handleSelectAll = function ( checked ) {
		if ( checked ) {
			setSelected( tags.map( function ( tag ) {
				return tag.id;
			} ) );
		} else {
			setSelected( [] );
		}
	};

	/**
	 * Handle individual row checkbox toggle.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number}  tagId   Tag term ID.
	 * @param {boolean} checked Whether checkbox is checked.
	 */
	var handleSelectRow = function ( tagId, checked ) {
		if ( checked ) {
			setSelected( function ( prev ) {
				return prev.concat( [ tagId ] );
			} );
		} else {
			setSelected( function ( prev ) {
				return prev.filter( function ( id ) {
					return id !== tagId;
				} );
			} );
		}
	};

	/**
	 * Handle bulk action apply.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleBulkApply = function () {
		if ( ! bulkAction || 0 === selected.length ) {
			return;
		}

		if ( 'delete' === bulkAction ) {
			setIsBulkProcessing( true );
			topicTagBulkAction( selected, 'delete' ).then( function ( response ) {
				setIsBulkProcessing( false );
				if ( response.success ) {
					var processed = response.data.processed || 0;
					setToast( {
						message: sprintf(
							_n( '%s tag deleted.', '%s tags deleted.', processed, 'buddyboss' ),
							processed
						),
						type: 'success',
					} );
					setSelected( [] );
					setBulkAction( '' );
					fetchTags( { page: 1 } );
				} else {
					setToast( {
						message: ( response.data && response.data.message ) || __( 'Bulk action failed.', 'buddyboss' ),
						type: 'error',
					} );
				}
			} ).catch( function () {
				setIsBulkProcessing( false );
				setToast( {
					message: __( 'Bulk action failed.', 'buddyboss' ),
					type: 'error',
				} );
			} );
		}
	};

	/**
	 * Handle row edit action.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} tag Tag object from the list.
	 */
	var handleEdit = function ( tag ) {
		// Fetch fresh data for the edit modal.
		getTopicTag( tag.id ).then( function ( response ) {
			if ( response.success && response.data ) {
				setEditTag( response.data );
				setIsEditOpen( true );
			} else {
				setToast( {
					message: __( 'Failed to load tag data.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function () {
			setToast( {
				message: __( 'Failed to load tag data.', 'buddyboss' ),
				type: 'error',
			} );
		} );
	};

	/**
	 * Handle row delete action.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} tag Tag object.
	 */
	var handleDeleteClick = function ( tag ) {
		setDeleteTagItem( tag );
	};

	/**
	 * Confirm and execute tag deletion.
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
			if ( response.success ) {
				setToast( {
					message: __( 'Tag deleted successfully.', 'buddyboss' ),
					type: 'success',
				} );
				fetchTags( { page: page } );
			} else {
				setToast( {
					message: ( response.data && response.data.message ) || __( 'Failed to delete tag.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function () {
			setIsDeleting( false );
			setDeleteTagItem( null );
			setToast( {
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
		setToast( {
			message: __( 'Tag saved successfully.', 'buddyboss' ),
			type: 'success',
		} );
		fetchTags( { page: page } );
	};

	// Build bulk action options from meta.
	var bulkActionOptions = [ { value: '', label: __( 'Bulk Actions', 'buddyboss' ) } ];
	if ( meta && meta.bulk_actions ) {
		Object.keys( meta.bulk_actions ).forEach( function ( key ) {
			bulkActionOptions.push( { value: key, label: decodeEntities( meta.bulk_actions[ key ] ) } );
		} );
	}

	// Determine columns from meta.
	var columns = meta && meta.columns ? meta.columns : {};

	// Build pagination range.
	var pageNumbers = [];
	var i;
	for ( i = 1; i <= totalPages; i++ ) {
		pageNumbers.push( i );
	}

	return (
		<div className="bb-discussion-tags-list">
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
					{ __( '+ Add New Tag', 'buddyboss' ) }
				</Button>
			</div>

			{ /* Toast notification */ }
			{ toast && (
				<div className={ 'bb-discussion-tags-list__toast bb-discussion-tags-list__toast--' + toast.type }>
					<span>{ toast.message }</span>
					<button
						type="button"
						className="bb-discussion-tags-list__toast-close"
						onClick={ function () {
							setToast( null );
						} }
					>
						&times;
					</button>
				</div>
			) }

			{ /* Toolbar */ }
			<div className="bb-discussion-tags-list__toolbar">
				<div className="bb-discussion-tags-list__toolbar-left">
					{ selected.length > 0 && (
						<>
							<SelectControl
								value={ bulkAction }
								options={ bulkActionOptions }
								onChange={ setBulkAction }
								__nextHasNoMarginBottom
							/>
							<Button
								variant="secondary"
								onClick={ handleBulkApply }
								disabled={ ! bulkAction || isBulkProcessing }
								isBusy={ isBulkProcessing }
								className="bb-discussion-tags-list__apply-btn"
							>
								{ __( 'Apply', 'buddyboss' ) }
							</Button>
						</>
					) }
				</div>
				<div className="bb-discussion-tags-list__toolbar-right">
					<div className="bb-discussion-tags-list__search">
						<input
							type="text"
							value={ search }
							onChange={ function ( e ) {
								handleSearch( e.target.value );
							} }
							placeholder={ __( 'Search tags', 'buddyboss' ) }
							className="bb-discussion-tags-list__search-input"
						/>
						{ search && (
							<button
								type="button"
								className="bb-discussion-tags-list__search-clear"
								onClick={ function () {
									handleSearch( '' );
								} }
							>
								&times;
							</button>
						) }
					</div>
				</div>
			</div>

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
							fetchTags( { page: page } );
						} }
					>
						{ __( 'Retry', 'buddyboss' ) }
					</Button>
				</div>
			) }

			{ ! isLoading && ! error && 0 === tags.length && (
				<div className="bb-discussion-tags-list__empty">
					<p>{ search ? __( 'No tags found matching your search.', 'buddyboss' ) : __( 'No discussion tags found.', 'buddyboss' ) }</p>
				</div>
			) }

			{ /* Table */ }
			{ ! isLoading && ! error && tags.length > 0 && (
				<table className="bb-discussion-tags-list__table">
					<thead>
						<tr>
							<th className="bb-discussion-tags-list__col-cb">
								<CheckboxControl
									checked={ tags.length > 0 && selected.length === tags.length }
									onChange={ handleSelectAll }
									__nextHasNoMarginBottom
								/>
							</th>
							<th className="bb-discussion-tags-list__col-tag">
								{ columns.name ? decodeEntities( columns.name ) : __( 'Tag', 'buddyboss' ) }
							</th>
							<th className="bb-discussion-tags-list__col-slug">
								{ columns.slug ? decodeEntities( columns.slug ) : __( 'Slug', 'buddyboss' ) }
							</th>
							<th className="bb-discussion-tags-list__col-count">
								{ columns.posts ? decodeEntities( columns.posts ) : __( 'Count', 'buddyboss' ) }
							</th>
							<th className="bb-discussion-tags-list__col-actions">
								{ __( 'Actions', 'buddyboss' ) }
							</th>
						</tr>
					</thead>
					<tbody>
						{ tags.map( function ( tag ) {
							var isSelected = -1 !== selected.indexOf( tag.id );

							return (
								<tr key={ tag.id } className={ isSelected ? 'is-selected' : '' }>
									<td className="bb-discussion-tags-list__col-cb">
										<CheckboxControl
											checked={ isSelected }
											onChange={ function ( checked ) {
												handleSelectRow( tag.id, checked );
											} }
											__nextHasNoMarginBottom
										/>
									</td>
									<td className="bb-discussion-tags-list__col-tag">
										<span className="bb-discussion-tags-list__tag-name">
											{ decodeEntities( tag.name ) }
										</span>
									</td>
									<td className="bb-discussion-tags-list__col-slug">
										{ decodeEntities( tag.slug ) }
									</td>
									<td className="bb-discussion-tags-list__col-count">
										{ sprintf(
											_n( '%s discussion', '%s discussions', tag.count, 'buddyboss' ),
											tag.count
										) }
									</td>
									<td className="bb-discussion-tags-list__col-actions">
										<DropdownMenu
											icon="ellipsis"
											label={ __( 'Actions', 'buddyboss' ) }
											className="bb-discussion-tags-list__actions-menu"
										>
											{ function ( { onClose } ) {
												return (
													<MenuGroup>
														{ tag.permalink && (
															<MenuItem
																onClick={ function () {
																	window.open( safeUrl( tag.permalink ), '_blank' );
																	onClose();
																} }
															>
																{ __( 'View', 'buddyboss' ) }
															</MenuItem>
														) }
														<MenuItem
															onClick={ function () {
																handleEdit( tag );
																onClose();
															} }
														>
															{ __( 'Edit', 'buddyboss' ) }
														</MenuItem>
														<MenuItem
															onClick={ function () {
																handleDeleteClick( tag );
																onClose();
															} }
															className="bb-discussion-tags-list__action-delete"
														>
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

			{ /* Pagination */ }
			{ ! isLoading && totalPages > 1 && (
				<div className="bb-discussion-tags-list__pagination">
					<span className="bb-discussion-tags-list__pagination-info">
						{ sprintf(
							__( 'Page %1$s of %2$s', 'buddyboss' ),
							page,
							totalPages
						) }
					</span>
					<div className="bb-discussion-tags-list__pagination-buttons">
						<Button
							variant="secondary"
							disabled={ 1 === page }
							onClick={ function () {
								handlePageChange( page - 1 );
							} }
							className="bb-discussion-tags-list__pagination-btn"
						>
							{ __( 'Previous', 'buddyboss' ) }
						</Button>
						{ pageNumbers.map( function ( num ) {
							return (
								<Button
									key={ num }
									variant={ num === page ? 'primary' : 'secondary' }
									onClick={ function () {
										handlePageChange( num );
									} }
									className="bb-discussion-tags-list__pagination-btn"
								>
									{ num }
								</Button>
							);
						} ) }
						<Button
							variant="secondary"
							disabled={ page === totalPages }
							onClick={ function () {
								handlePageChange( page + 1 );
							} }
							className="bb-discussion-tags-list__pagination-btn"
						>
							{ __( 'Next', 'buddyboss' ) }
						</Button>
					</div>
				</div>
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
			/>

			{ /* Delete Confirmation Modal */ }
			{ deleteTagItem && (
				<Modal
					title={ __( 'Delete Tag', 'buddyboss' ) }
					onRequestClose={ function () {
						setDeleteTagItem( null );
					} }
					className="bb-tag-delete-modal bb-admin-settings-modal"
					shouldCloseOnClickOutside={ false }
				>
					<div className="bb-tag-delete-modal__body">
						<p>
							{ sprintf(
								__( 'Are you sure you want to delete the tag "%s"? This action cannot be undone.', 'buddyboss' ),
								decodeEntities( deleteTagItem.name )
							) }
						</p>
					</div>
					<div className="bb-tag-delete-modal__footer bb-admin-settings-modal__footer">
						<Button
							variant="secondary"
							onClick={ function () {
								setDeleteTagItem( null );
							} }
							disabled={ isDeleting }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleDeleteConfirm }
							isBusy={ isDeleting }
							disabled={ isDeleting }
							className="is-destructive"
						>
							{ __( 'Delete', 'buddyboss' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}
