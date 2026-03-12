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
} from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getTopicTags, getTopicTag, deleteTopicTag } from '../utils/ajax';
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
	var currentPageState = useState( 1 );
	var currentPage = currentPageState[ 0 ];
	var setCurrentPage = currentPageState[ 1 ];

	var totalState = useState( 0 );
	var total = totalState[ 0 ];
	var setTotal = totalState[ 1 ];

	var totalPages = Math.ceil( total / TAGS_PER_PAGE );

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

	// Notice state (matches Groups/Discussions pattern).
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
	}, [ currentPage ] );

	// Initial fetch.
	useEffect( function () {
		fetchTags( { page: 1 } );

		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

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
	 * Handle page change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} newPage Page number.
	 */
	var handlePageChange = function ( newPage ) {
		setCurrentPage( newPage );
		fetchTags( { page: newPage } );
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
				setNotice( {
					message: __( 'Failed to load tag data.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function () {
			setNotice( {
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
				setNotice( {
					message: __( 'Tag deleted successfully.', 'buddyboss' ),
					type: 'success',
				} );
				fetchTags( { page: currentPage } );
			} else {
				setNotice( {
					message: ( response.data && response.data.message ) || __( 'Failed to delete tag.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function () {
			setIsDeleting( false );
			setDeleteTagItem( null );
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
		fetchTags( { page: currentPage } );
	};

	/**
	 * Build pagination page numbers.
	 *
	 * @since BuddyBoss [BBVERSION]
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
		<div className="bb-discussion-tags-list">
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
						<i className="bb-icons-rl bb-icons-rl-x"></i>
					</button>
				</div>
			) }

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
							fetchTags( { page: currentPage } );
						} }
					>
						{ __( 'Retry', 'buddyboss' ) }
					</Button>
				</div>
			) }

			{ ! isLoading && ! error && 0 === tags.length && (
				<div className="bb-discussion-tags-list__empty">
					<p>{ __( 'No discussion tags found.', 'buddyboss' ) }</p>
				</div>
			) }

			{ /* Table */ }
			{ ! isLoading && ! error && tags.length > 0 && (
				<div className="bb-discussion-tags-list__table-wrap">
					<table className="bb-discussion-tags-list__table">
						<tbody>
							{ tags.map( function ( tag ) {
								return (
									<tr key={ tag.id } className="bb-discussion-tags-list__row">
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
			{ ! isLoading && total > 0 && (
				<div className="bb-discussion-tags-list__footer">
					<span className="bb-discussion-tags-list__item-count">
						{ sprintf(
						/* translators: %s: total number of items. */
						_n( '%s item', '%s items', total, 'buddyboss' ),
						total
					) }
					</span>

					{ totalPages > 1 && (
						<div className="bb-discussion-tags-list__pagination">
							<Button
								variant="secondary"
								disabled={ 1 === currentPage }
								onClick={ function () {
									handlePageChange( Math.max( 1, currentPage - 1 ) );
								} }
								className="bb-discussion-tags-list__pagination-btn bb-discussion-tags-list__pagination-btn--previous"
							>
								&lsaquo;
							</Button>

							{ getPageNumbers().map( function ( page, index ) {
								if ( '...' === page ) {
									return (
										<span key={ 'ellipsis-' + index } className="bb-discussion-tags-list__pagination-ellipsis">
											&hellip;
										</span>
									);
								}
								return (
									<Button
										key={ page }
										variant={ currentPage === page ? 'primary' : 'secondary' }
										onClick={ function () {
											handlePageChange( page );
										} }
										className={ 'bb-discussion-tags-list__pagination-btn' + ( currentPage === page ? ' bb-discussion-tags-list__pagination-btn--current' : '' ) }
									>
										{ page }
									</Button>
								);
							} ) }

							<Button
								variant="secondary"
								disabled={ currentPage >= totalPages }
								onClick={ function () {
									handlePageChange( Math.min( totalPages, currentPage + 1 ) );
								} }
								className="bb-discussion-tags-list__pagination-btn bb-discussion-tags-list__pagination-btn--next"
							>
								&rsaquo;
							</Button>
						</div>
					) }
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
