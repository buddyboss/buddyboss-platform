/**
 * BuddyBoss Admin Settings 2.0 - Replies List Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef, useMemo } from '@wordpress/element';
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
import { getReplies, getReply, saveReply, deleteReply, replyBulkAction } from '../utils/ajax';
import { sanitizeHtml, safeUrl } from '../utils/sanitize';
import { ReplyCreateModal } from '../components/forums/ReplyCreateModal';
import { AsyncSelectField } from '../components/fields/AsyncSelectField';
import { RichTextEditor, forceRemoveEditor } from '../components/common/RichTextEditor';

/**
 * Sort options for replies.
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
 * Number of replies to fetch per page.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {number}
 */
var REPLIES_PER_PAGE = 20;

/**
 * Replies List Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onNavigate Navigation handler.
 * @returns {JSX.Element} Replies list screen.
 */
export default function RepliesListScreen( { onNavigate } ) {
	var repliesState = useState( [] );
	var replies = repliesState[ 0 ];
	var setReplies = repliesState[ 1 ];

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

	// Filters.
	var forumIdState = useState( 0 );
	var forumId = forumIdState[ 0 ];
	var setForumId = forumIdState[ 1 ];

	var sortState = useState( 'newest' );
	var sort = sortState[ 0 ];
	var setSort = sortState[ 1 ];

	var searchState = useState( '' );
	var search = searchState[ 0 ];
	var setSearch = searchState[ 1 ];

	var searchTimerRef = useRef( null );

	// Metadata (views, bulk actions, columns).
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

	// Create modal.
	var isCreateOpenState = useState( false );
	var isCreateOpen = isCreateOpenState[ 0 ];
	var setIsCreateOpen = isCreateOpenState[ 1 ];

	// Edit modal.
	var editReplyState = useState( null );
	var editReply = editReplyState[ 0 ];
	var setEditReply = editReplyState[ 1 ];

	var isEditOpenState = useState( false );
	var isEditOpen = isEditOpenState[ 0 ];
	var setIsEditOpen = isEditOpenState[ 1 ];

	var isEditSavingState = useState( false );
	var isEditSaving = isEditSavingState[ 0 ];
	var setIsEditSaving = isEditSavingState[ 1 ];

	// Edit form fields.
	var editContentState = useState( '' );
	var editContent = editContentState[ 0 ];
	var setEditContent = editContentState[ 1 ];

	var editForumIdState = useState( 0 );
	var editForumId = editForumIdState[ 0 ];
	var setEditForumId = editForumIdState[ 1 ];

	var editTopicIdState = useState( 0 );
	var editTopicId = editTopicIdState[ 0 ];
	var setEditTopicId = editTopicIdState[ 1 ];

	var editReplyToState = useState( 0 );
	var editReplyTo = editReplyToState[ 0 ];
	var setEditReplyTo = editReplyToState[ 1 ];

	var editVisibilityState = useState( 'publish' );
	var editVisibility = editVisibilityState[ 0 ];
	var setEditVisibility = editVisibilityState[ 1 ];

	var editErrorState = useState( '' );
	var editError = editErrorState[ 0 ];
	var setEditError = editErrorState[ 1 ];

	// Single delete modal.
	var deleteReplyItemState = useState( null );
	var deleteReplyItem = deleteReplyItemState[ 0 ];
	var setDeleteReplyItem = deleteReplyItemState[ 1 ];

	var isDeletingState = useState( false );
	var isDeleting = isDeletingState[ 0 ];
	var setIsDeleting = isDeletingState[ 1 ];

	// Bulk delete modal.
	var bulkDeleteOpenState = useState( false );
	var bulkDeleteOpen = bulkDeleteOpenState[ 0 ];
	var setBulkDeleteOpen = bulkDeleteOpenState[ 1 ];

	var bulkDeleteConfirmState = useState( false );
	var bulkDeleteConfirmChecked = bulkDeleteConfirmState[ 0 ];
	var setBulkDeleteConfirmChecked = bulkDeleteConfirmState[ 1 ];

	// Bulk edit modal.
	var bulkEditOpenState = useState( false );
	var bulkEditOpen = bulkEditOpenState[ 0 ];
	var setBulkEditOpen = bulkEditOpenState[ 1 ];

	var bulkEditVisibilityState = useState( 'no_change' );
	var bulkEditVisibility = bulkEditVisibilityState[ 0 ];
	var setBulkEditVisibility = bulkEditVisibilityState[ 1 ];

	// Toast state.
	var toastState = useState( null );
	var toast = toastState[ 0 ];
	var setToast = toastState[ 1 ];

	// Forum filter UI state.
	var isForumFilterOpenState = useState( false );
	var isForumFilterOpen = isForumFilterOpenState[ 0 ];
	var setIsForumFilterOpen = isForumFilterOpenState[ 1 ];

	var forumFilterSearchState = useState( '' );
	var forumFilterSearch = forumFilterSearchState[ 0 ];
	var setForumFilterSearch = forumFilterSearchState[ 1 ];

	var forumFilterRef = useRef( null );

	// AbortController ref.
	var abortRef = useRef( null );

	/**
	 * Fetch replies from the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} params Query parameters override.
	 */
	var fetchReplies = useCallback( function ( params ) {
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		abortRef.current = new AbortController();

		setIsLoading( true );
		setError( '' );

		var queryParams = {
			page: params && params.page ? params.page : page,
			per_page: REPLIES_PER_PAGE,
			search: params && 'undefined' !== typeof params.search ? params.search : search,
			forum_id: params && 'undefined' !== typeof params.forum_id ? params.forum_id : forumId,
			sort: params && params.sort ? params.sort : sort,
			include_meta: meta ? 0 : 1,
		};

		getReplies( queryParams, { signal: abortRef.current.signal } ).then( function ( response ) {
			if ( response.success && response.data ) {
				setReplies( response.data.replies || [] );
				setTotalPages( response.data.total_pages || 1 );
				setTotalItems( response.data.total || 0 );

				if ( response.data.meta ) {
					setMeta( response.data.meta );
				}
			} else {
				setError( ( response.data && response.data.message ) || __( 'Failed to load replies.', 'buddyboss' ) );
			}
			setIsLoading( false );
		} ).catch( function ( err ) {
			if ( err && 'AbortError' === err.name ) {
				return;
			}
			setIsLoading( false );
			setError( __( 'Failed to load replies.', 'buddyboss' ) );
		} );
	}, [ page, search, forumId, sort, meta ] );

	// Initial fetch.
	useEffect( function () {
		fetchReplies( { page: 1 } );

		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
			if ( searchTimerRef.current ) {
				clearTimeout( searchTimerRef.current );
			}
		};
	}, [] );

	// Close forum filter on outside click.
	useEffect( function () {
		var handleClickOutside = function ( e ) {
			if ( forumFilterRef.current && ! forumFilterRef.current.contains( e.target ) ) {
				setIsForumFilterOpen( false );
			}
		};
		document.addEventListener( 'mousedown', handleClickOutside );
		return function () {
			document.removeEventListener( 'mousedown', handleClickOutside );
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
			fetchReplies( { page: 1, search: value } );
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
		fetchReplies( { page: newPage } );
	};

	/**
	 * Handle forum filter change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} newForumId Forum ID (0 for all).
	 */
	var handleForumFilter = function ( newForumId ) {
		setForumId( newForumId );
		setPage( 1 );
		setSelected( [] );
		setIsForumFilterOpen( false );
		setForumFilterSearch( '' );
		fetchReplies( { page: 1, forum_id: newForumId } );
	};

	/**
	 * Handle sort change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} newSort Sort value.
	 */
	var handleSortChange = function ( newSort ) {
		setSort( newSort );
		setPage( 1 );
		setSelected( [] );
		fetchReplies( { page: 1, sort: newSort } );
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
			setSelected( replies.map( function ( r ) {
				return r.id;
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
	 * @param {number}  replyId Reply ID.
	 * @param {boolean} checked Whether checkbox is checked.
	 */
	var handleSelectRow = function ( replyId, checked ) {
		if ( checked ) {
			setSelected( function ( prev ) {
				return prev.concat( [ replyId ] );
			} );
		} else {
			setSelected( function ( prev ) {
				return prev.filter( function ( id ) {
					return id !== replyId;
				} );
			} );
		}
	};

	/**
	 * Handle bulk action apply — routes to confirmation/edit modals.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleBulkApply = function () {
		if ( ! bulkAction || 0 === selected.length ) {
			return;
		}

		if ( 'delete' === bulkAction ) {
			setBulkDeleteConfirmChecked( false );
			setBulkDeleteOpen( true );
			return;
		}

		if ( 'edit' === bulkAction ) {
			setBulkEditVisibility( 'no_change' );
			setBulkEditOpen( true );
			return;
		}

		// For other actions (spam, etc.) execute directly.
		performBulkAction( bulkAction );
	};

	/**
	 * Execute a bulk action against the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} action    The action to perform.
	 * @param {Object} extraData Optional extra POST data.
	 */
	var performBulkAction = function ( action, extraData ) {
		setIsBulkProcessing( true );

		replyBulkAction( selected, action, extraData ).then( function ( response ) {
			setIsBulkProcessing( false );
			if ( response.success ) {
				setToast( {
					message: ( response.data && response.data.message ) || __( 'Bulk action completed.', 'buddyboss' ),
					type: 'success',
				} );
				setSelected( [] );
				setBulkAction( '' );
				fetchReplies( { page: 1 } );
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
	};

	/**
	 * Confirm bulk delete from the delete modal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleConfirmBulkDelete = function () {
		setBulkDeleteOpen( false );
		performBulkAction( 'delete' );
	};

	/**
	 * Confirm bulk edit from the edit modal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleConfirmBulkEdit = function () {
		setBulkEditOpen( false );
		performBulkAction( 'edit', {
			edit_visibility: bulkEditVisibility,
		} );
	};

	/**
	 * Handle row edit action.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} reply Reply object from the list.
	 */
	var handleEdit = function ( reply ) {
		getReply( reply.id ).then( function ( response ) {
			if ( response.success && response.data ) {
				var data = response.data;
				setEditReply( data );
				setEditContent( data.content || '' );
				setEditForumId( data.forum_id || 0 );
				setEditTopicId( data.topic_id || 0 );
				setEditReplyTo( data.reply_to || 0 );
				setEditVisibility( data.post_status || 'publish' );
				setEditError( '' );
				setIsEditOpen( true );
			} else {
				setToast( {
					message: __( 'Failed to load reply data.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function () {
			setToast( {
				message: __( 'Failed to load reply data.', 'buddyboss' ),
				type: 'error',
			} );
		} );
	};

	/**
	 * Handle edit modal save.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleEditSave = function () {
		if ( ! editContent.trim() ) {
			setEditError( __( 'Description is required.', 'buddyboss' ) );
			return;
		}

		setIsEditSaving( true );
		setEditError( '' );

		saveReply( {
			reply_id: editReply.id,
			content: editContent,
			forum_id: editForumId,
			topic_id: editTopicId,
			reply_to: editReplyTo,
			visibility: editVisibility,
		} ).then( function ( response ) {
			setIsEditSaving( false );
			if ( response.success ) {
				forceRemoveEditor( 'bb-reply-edit-description' );
				setIsEditOpen( false );
				setEditReply( null );
				setToast( {
					message: __( 'Reply updated successfully.', 'buddyboss' ),
					type: 'success',
				} );
				fetchReplies( { page: page } );
			} else {
				setEditError( ( response.data && response.data.message ) || __( 'Failed to update reply.', 'buddyboss' ) );
			}
		} ).catch( function () {
			setIsEditSaving( false );
			setEditError( __( 'An error occurred. Please try again.', 'buddyboss' ) );
		} );
	};

	/**
	 * Handle edit modal close.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleEditClose = function () {
		forceRemoveEditor( 'bb-reply-edit-description' );
		setIsEditOpen( false );
		setEditReply( null );
	};

	/**
	 * Handle row spam toggle.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} reply Reply object.
	 */
	var handleSpamToggle = function ( reply ) {
		replyBulkAction( [ reply.id ], 'spam' ).then( function ( response ) {
			if ( response.success ) {
				setToast( {
					message: reply.is_spam
						? __( 'Reply unmarked as spam.', 'buddyboss' )
						: __( 'Reply marked as spam.', 'buddyboss' ),
					type: 'success',
				} );
				fetchReplies( { page: page } );
			} else {
				setToast( {
					message: __( 'Failed to update reply.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function () {
			setToast( {
				message: __( 'Failed to update reply.', 'buddyboss' ),
				type: 'error',
			} );
		} );
	};

	/**
	 * Handle row delete action.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} reply Reply object.
	 */
	var handleDeleteClick = function ( reply ) {
		setDeleteReplyItem( reply );
	};

	/**
	 * Confirm and execute reply deletion.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleDeleteConfirm = function () {
		if ( ! deleteReplyItem ) {
			return;
		}

		setIsDeleting( true );

		deleteReply( deleteReplyItem.id ).then( function ( response ) {
			setIsDeleting( false );
			setDeleteReplyItem( null );
			if ( response.success ) {
				setToast( {
					message: __( 'Reply deleted successfully.', 'buddyboss' ),
					type: 'success',
				} );
				fetchReplies( { page: page } );
			} else {
				setToast( {
					message: ( response.data && response.data.message ) || __( 'Failed to delete reply.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function () {
			setIsDeleting( false );
			setDeleteReplyItem( null );
			setToast( {
				message: __( 'Failed to delete reply.', 'buddyboss' ),
				type: 'error',
			} );
		} );
	};

	/**
	 * Handle create reply success.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleReplyCreated = function () {
		setIsCreateOpen( false );
		setToast( {
			message: __( 'Reply created successfully.', 'buddyboss' ),
			type: 'success',
		} );
		fetchReplies( { page: 1 } );
	};

	// Build bulk action options from meta.
	var bulkActionOptions = [ { value: '', label: __( 'Bulk Actions', 'buddyboss' ) } ];
	if ( meta && meta.bulk_actions ) {
		Object.keys( meta.bulk_actions ).forEach( function ( key ) {
			bulkActionOptions.push( { value: key, label: decodeEntities( meta.bulk_actions[ key ] ) } );
		} );
	}

	// Build forum filter list from meta.
	var forumsList = meta && meta.views && meta.views.forums ? meta.views.forums : [];

	// Get filtered forums for the searchable dropdown.
	var filteredForums = useMemo( function () {
		if ( ! forumFilterSearch ) {
			return forumsList;
		}
		var lowerSearch = forumFilterSearch.toLowerCase();
		return forumsList.filter( function ( f ) {
			return f.name.toLowerCase().indexOf( lowerSearch ) !== -1;
		} );
	}, [ forumsList, forumFilterSearch ] );

	// Get selected reply names for bulk modal pills.
	var selectedReplyNames = useMemo( function () {
		var contentMap = {};
		replies.forEach( function ( r ) {
			contentMap[ r.id ] = r.content;
		} );
		return selected.map( function ( id ) {
			return { id: id, title: contentMap[ id ] || '#' + id };
		} );
	}, [ selected, replies ] );

	// Forum filter label.
	var forumFilterLabel = __( 'All Forums', 'buddyboss' );
	if ( forumId ) {
		var matchedForum = forumsList.filter( function ( f ) {
			return f.id === forumId;
		} );
		if ( matchedForum.length > 0 ) {
			forumFilterLabel = decodeEntities( matchedForum[ 0 ].name );
		}
	} else {
		forumFilterLabel = sprintf(
			__( 'All Forums (%s)', 'buddyboss' ),
			forumsList.length
		);
	}

	// Determine columns from meta.
	var columns = meta && meta.columns ? meta.columns : {};

	// Visibility options for edit modal.
	var visibilityOptions = [
		{ value: 'publish', label: __( 'Public', 'buddyboss' ) },
		{ value: 'private', label: __( 'Private', 'buddyboss' ) },
		{ value: 'hidden', label: __( 'Hidden', 'buddyboss' ) },
	];

	/**
	 * Build pagination page numbers with ellipsis.
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

			if ( page > maxVisible - 1 ) {
				pages.push( '...' );
			}

			var start = Math.max( 2, page - 1 );
			var end = Math.min( totalPages - 1, page + 1 );

			if ( page <= 3 ) {
				end = Math.min( totalPages - 1, maxVisible );
			}
			if ( page >= totalPages - 2 ) {
				start = Math.max( 2, totalPages - maxVisible + 1 );
			}

			for ( var j = start; j <= end; j++ ) {
				pages.push( j );
			}

			if ( page < totalPages - ( maxVisible - 2 ) ) {
				pages.push( '...' );
			}

			pages.push( totalPages );
		}

		return pages;
	};

	return (
		<div className="bb-replies-list">
			{ /* Header */ }
			<div className="bb-replies-list__header">
				<h2 className="bb-replies-list__title">
					{ __( 'Replies', 'buddyboss' ) }
				</h2>
				<Button
					variant="primary"
					className="bb-replies-list__create-btn"
					onClick={ function () {
						setIsCreateOpen( true );
					} }
				>
					{ __( '+ Create New Reply', 'buddyboss' ) }
				</Button>
			</div>

			{ /* Toast notification */ }
			{ toast && (
				<div className={ 'bb-replies-list__toast bb-replies-list__toast--' + toast.type }>
					<span>{ toast.message }</span>
					<button
						type="button"
						className="bb-replies-list__toast-close"
						onClick={ function () {
							setToast( null );
						} }
					>
						&times;
					</button>
				</div>
			) }

			{ /* Toolbar */ }
			<div className="bb-replies-list__toolbar">
				<div className="bb-replies-list__toolbar-left">
					<div className="bb-replies-list__bulk-actions">
						<SelectControl
							value={ bulkAction }
							options={ bulkActionOptions }
							onChange={ setBulkAction }
							__nextHasNoMarginBottom
						/>
						<Button
							variant="secondary"
							onClick={ handleBulkApply }
							disabled={ ! bulkAction || 0 === selected.length || isBulkProcessing }
							isBusy={ isBulkProcessing }
							className="bb-replies-list__bulk-apply"
						>
							{ __( 'Apply', 'buddyboss' ) }
						</Button>
					</div>
				</div>
				<div className="bb-replies-list__toolbar-right">
					{ /* Forum Filter */ }
					<div className="bb-replies-list__forum-filter" ref={ forumFilterRef }>
						<button
							type="button"
							className="bb-replies-list__forum-filter-btn"
							onClick={ function () {
								setIsForumFilterOpen( ! isForumFilterOpen );
							} }
						>
							{ forumFilterLabel }
							<span className="bb-replies-list__forum-filter-arrow">&#9662;</span>
						</button>
						{ isForumFilterOpen && (
							<div className="bb-replies-list__forum-filter-dropdown">
								<input
									type="text"
									className="bb-replies-list__forum-filter-search"
									placeholder={ __( 'Search forums', 'buddyboss' ) }
									value={ forumFilterSearch }
									onChange={ function ( e ) {
										setForumFilterSearch( e.target.value );
									} }
									autoFocus
								/>
								<div className="bb-replies-list__forum-filter-options">
									<button
										type="button"
										className={ 'bb-replies-list__forum-filter-option' + ( 0 === forumId ? ' is-active' : '' ) }
										onClick={ function () {
											handleForumFilter( 0 );
										} }
									>
										{ sprintf( __( 'All Forums (%s)', 'buddyboss' ), forumsList.length ) }
									</button>
									{ filteredForums.map( function ( f ) {
										return (
											<button
												key={ f.id }
												type="button"
												className={ 'bb-replies-list__forum-filter-option' + ( f.id === forumId ? ' is-active' : '' ) }
												onClick={ function () {
													handleForumFilter( f.id );
												} }
											>
												{ decodeEntities( f.name ) + ' (' + f.count + ')' }
											</button>
										);
									} ) }
								</div>
							</div>
						) }
					</div>

					{ /* Sort */ }
					<SelectControl
						value={ sort }
						options={ sortOptions }
						onChange={ handleSortChange }
						__nextHasNoMarginBottom
						className="bb-replies-list__sort-select"
					/>

					{ /* Search */ }
					<div className="bb-replies-list__search">
						<input
							type="text"
							value={ search }
							onChange={ function ( e ) {
								handleSearch( e.target.value );
							} }
							placeholder={ __( 'Search replies', 'buddyboss' ) }
							className="bb-replies-list__search-input"
						/>
						{ search && (
							<button
								type="button"
								className="bb-replies-list__search-clear"
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
				<div className="bb-replies-list__loading">
					<Spinner />
				</div>
			) }

			{ ! isLoading && error && (
				<div className="bb-replies-list__error">
					<p>{ error }</p>
					<Button
						variant="secondary"
						onClick={ function () {
							fetchReplies( { page: page } );
						} }
					>
						{ __( 'Retry', 'buddyboss' ) }
					</Button>
				</div>
			) }

			{ ! isLoading && ! error && 0 === replies.length && (
				<div className="bb-replies-list__empty">
					<p>{ search ? __( 'No replies found matching your search.', 'buddyboss' ) : __( 'No replies found.', 'buddyboss' ) }</p>
				</div>
			) }

			{ /* Table */ }
			{ ! isLoading && ! error && replies.length > 0 && (
				<table className="bb-replies-list__table">
					<thead>
						<tr>
							<th className="bb-replies-list__col-cb">
								<CheckboxControl
									checked={ replies.length > 0 && selected.length === replies.length }
									onChange={ handleSelectAll }
									__nextHasNoMarginBottom
								/>
							</th>
							<th className="bb-replies-list__col-reply">
								{ columns.title ? decodeEntities( columns.title ) : __( 'Reply', 'buddyboss' ) }
							</th>
							<th className="bb-replies-list__col-forum">
								{ columns.bbp_reply_forum ? decodeEntities( columns.bbp_reply_forum ) : __( 'Forum', 'buddyboss' ) }
							</th>
							<th className="bb-replies-list__col-discussion">
								{ columns.bbp_reply_topic ? decodeEntities( columns.bbp_reply_topic ) : __( 'Discussion', 'buddyboss' ) }
							</th>
							<th className="bb-replies-list__col-created">
								{ columns.bbp_reply_created ? decodeEntities( columns.bbp_reply_created ) : __( 'Created', 'buddyboss' ) }
							</th>
							<th className="bb-replies-list__col-actions"></th>
						</tr>
					</thead>
					<tbody>
						{ replies.map( function ( reply ) {
							var isSelected = -1 !== selected.indexOf( reply.id );

							return (
								<tr key={ reply.id } className={ ( isSelected ? 'is-selected' : '' ) + ( reply.is_spam ? ' is-spam' : '' ) }>
									<td className="bb-replies-list__col-cb">
										<CheckboxControl
											checked={ isSelected }
											onChange={ function ( checked ) {
												handleSelectRow( reply.id, checked );
											} }
											__nextHasNoMarginBottom
										/>
									</td>
									<td className="bb-replies-list__col-reply">
										<div className="bb-replies-list__reply-content">
											{ reply.permalink ? (
												<a
													href={ safeUrl( reply.permalink ) }
													className="bb-replies-list__reply-link"
													target="_blank"
													rel="noopener noreferrer"
												>
													{ decodeEntities( reply.content ) }
												</a>
											) : (
												decodeEntities( reply.content )
											) }
											{ reply.is_spam && (
												<span className="bb-replies-list__spam-badge">
													{ __( 'Spam', 'buddyboss' ) }
												</span>
											) }
										</div>
									</td>
									<td className="bb-replies-list__col-forum">
										{ reply.forum_name ? decodeEntities( reply.forum_name ) : '—' }
									</td>
									<td className="bb-replies-list__col-discussion">
										{ reply.topic_title ? decodeEntities( reply.topic_title ) : '—' }
									</td>
									<td className="bb-replies-list__col-created">
										<i className="bb-icons-rl bb-icons-rl-clock bb-replies-list__created-icon"></i>
										{ reply.created_date }{ reply.created_time ? ', ' + reply.created_time : '' }
									</td>
									<td className="bb-replies-list__col-actions">
										<DropdownMenu
											icon="ellipsis"
											label={ __( 'Actions', 'buddyboss' ) }
											className="bb-replies-list__actions-menu"
										>
											{ function ( { onClose } ) {
												return (
													<MenuGroup>
														{ reply.permalink && (
															<MenuItem
																onClick={ function () {
																	window.open( safeUrl( reply.permalink ), '_blank' );
																	onClose();
																} }
															>
																{ __( 'View', 'buddyboss' ) }
															</MenuItem>
														) }
														<MenuItem
															onClick={ function () {
																handleEdit( reply );
																onClose();
															} }
														>
															{ __( 'Edit', 'buddyboss' ) }
														</MenuItem>
														<MenuItem
															onClick={ function () {
																handleSpamToggle( reply );
																onClose();
															} }
														>
															{ reply.is_spam
																? __( 'Not Spam', 'buddyboss' )
																: __( 'Spam', 'buddyboss' )
															}
														</MenuItem>
														<MenuItem
															onClick={ function () {
																handleDeleteClick( reply );
																onClose();
															} }
															className="bb-replies-list__action-delete"
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

			{ /* Footer */ }
			{ ! isLoading && ! error && replies.length > 0 && (
				<div className="bb-replies-list__footer">
					<span className="bb-replies-list__item-count">
						{ sprintf(
							_n( '%s item', '%s items', totalItems, 'buddyboss' ),
							totalItems
						) }
					</span>

					{ totalPages > 1 && (
						<div className="bb-replies-list__pagination">
							<Button
								variant="secondary"
								disabled={ 1 === page }
								onClick={ function () {
									handlePageChange( Math.max( 1, page - 1 ) );
								} }
								className="bb-replies-list__pagination-btn bb-replies-list__pagination-btn--previous"
							>
								&lsaquo;
							</Button>

							{ getPageNumbers().map( function ( num, index ) {
								if ( '...' === num ) {
									return (
										<span key={ 'ellipsis-' + index } className="bb-replies-list__pagination-ellipsis">
											&hellip;
										</span>
									);
								}
								return (
									<Button
										key={ num }
										variant={ page === num ? 'primary' : 'secondary' }
										onClick={ function () {
											handlePageChange( num );
										} }
										className={ 'bb-replies-list__pagination-btn' + ( page === num ? ' bb-replies-list__pagination-btn--current' : '' ) }
									>
										{ num }
									</Button>
								);
							} ) }

							<Button
								variant="secondary"
								disabled={ page >= totalPages }
								onClick={ function () {
									handlePageChange( Math.min( totalPages, page + 1 ) );
								} }
								className="bb-replies-list__pagination-btn bb-replies-list__pagination-btn--next"
							>
								&rsaquo;
							</Button>
						</div>
					) }
				</div>
			) }

			{ /* Create Modal */ }
			<ReplyCreateModal
				isOpen={ isCreateOpen }
				onClose={ function () {
					setIsCreateOpen( false );
				} }
				onCreated={ handleReplyCreated }
			/>

			{ /* Edit Modal */ }
			{ isEditOpen && editReply && (
				<Modal
					title={ __( 'Edit Reply', 'buddyboss' ) }
					onRequestClose={ handleEditClose }
					className="bb-reply-edit-modal bb-admin-settings-modal"
					shouldCloseOnClickOutside={ false }
				>
					<div className="bb-reply-edit-modal__body">
						{ editError && (
							<p className="bb-reply-edit-modal__error">{ editError }</p>
						) }

						<RichTextEditor
							id="bb-reply-edit-description"
							label={ __( 'Description', 'buddyboss' ) }
							value={ editContent }
							onChange={ setEditContent }
						/>

						<div className="components-base-control">
							<label className="components-base-control__label">
								{ __( 'Forum', 'buddyboss' ) }
							</label>
							<AsyncSelectField
								value={ String( editForumId ) }
								onChange={ function ( val ) {
									setEditForumId( parseInt( val, 10 ) || 0 );
								} }
								asyncAction="bb_admin_forum_autocomplete"
								placeholder={ __( 'Select Forum', 'buddyboss' ) }
							/>
						</div>

						<div className="components-base-control">
							<label className="components-base-control__label">
								{ __( 'Discussion', 'buddyboss' ) }
							</label>
							<AsyncSelectField
								value={ String( editTopicId ) }
								onChange={ function ( val ) {
									setEditTopicId( parseInt( val, 10 ) || 0 );
								} }
								asyncAction="bb_admin_discussion_autocomplete"
								asyncExtraParams={ editForumId ? { forum_id: editForumId } : {} }
								placeholder={ __( 'Select Discussion', 'buddyboss' ) }
							/>
						</div>

						<div className="components-base-control">
							<label className="components-base-control__label">
								{ __( 'Reply to', 'buddyboss' ) }
							</label>
							<AsyncSelectField
								value={ String( editReplyTo ) }
								onChange={ function ( val ) {
									setEditReplyTo( parseInt( val, 10 ) || 0 );
								} }
								asyncAction="bb_admin_reply_autocomplete"
								asyncExtraParams={ editTopicId ? { topic_id: editTopicId } : {} }
								placeholder={ __( 'Select Reply', 'buddyboss' ) }
							/>
						</div>

						<SelectControl
							label={ __( 'Visibility', 'buddyboss' ) }
							value={ editVisibility }
							options={ visibilityOptions }
							onChange={ setEditVisibility }
							__nextHasNoMarginBottom
						/>
					</div>

					<div className="bb-reply-edit-modal__footer bb-admin-settings-modal__footer">
						<Button
							variant="secondary"
							onClick={ handleEditClose }
							disabled={ isEditSaving }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleEditSave }
							isBusy={ isEditSaving }
							disabled={ isEditSaving || ! editContent.trim() }
						>
							{ __( 'Save', 'buddyboss' ) }
						</Button>
					</div>
				</Modal>
			) }

			{ /* Single Delete Confirmation Modal */ }
			{ deleteReplyItem && (
				<Modal
					title={ __( 'Delete Reply', 'buddyboss' ) }
					onRequestClose={ function () {
						setDeleteReplyItem( null );
					} }
					className="bb-reply-delete-modal bb-admin-settings-modal"
					shouldCloseOnClickOutside={ false }
				>
					<div className="bb-reply-delete-modal__body">
						<p>
							{ __( 'Are you sure you want to delete this reply? This action cannot be undone.', 'buddyboss' ) }
						</p>
					</div>
					<div className="bb-reply-delete-modal__footer bb-admin-settings-modal__footer">
						<Button
							variant="secondary"
							onClick={ function () {
								setDeleteReplyItem( null );
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

			{ /* Bulk Delete Confirmation Modal */ }
			{ bulkDeleteOpen && (
				<Modal
					title={ __( 'Delete Reply?', 'buddyboss' ) }
					onRequestClose={ function () {
						setBulkDeleteOpen( false );
					} }
					className="bb-reply-delete-modal bb-admin-settings-modal"
					shouldCloseOnClickOutside={ false }
				>
					<div className="bb-reply-delete-modal__body">
						<div className="bb-admin-bulk-modal__selected-items">
							{ selectedReplyNames.map( function ( item ) {
								return (
									<div key={ item.id } className="bb-admin-bulk-modal__selected-item">
										<CheckboxControl
											checked={ true }
											onChange={ function () {
												setSelected( function ( prev ) {
													var next = prev.filter( function ( i ) { return i !== item.id; } );
													if ( 0 === next.length ) {
														setBulkDeleteOpen( false );
													}
													return next;
												} );
											} }
											__nextHasNoMarginBottom
										/>
										<span className="bb-admin-bulk-modal__selected-item-name">
											{ decodeEntities( item.title ) }
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
									{ __( 'This permanently deletes selected replies. This cannot be undone.', 'buddyboss' ) }
								</span>
							</div>
						</div>
						<p className="bb-reply-delete-modal__description">
							{ __( 'Deleting replies will remove them from the community permanently.', 'buddyboss' ) }
						</p>
						<CheckboxControl
							label={ __( 'I understand this will permanently delete the selected replies.', 'buddyboss' ) }
							checked={ bulkDeleteConfirmChecked }
							onChange={ setBulkDeleteConfirmChecked }
							__nextHasNoMarginBottom
						/>
					</div>
					<div className="bb-reply-delete-modal__footer bb-admin-settings-modal__footer">
						<Button
							variant="secondary"
							onClick={ function () {
								setBulkDeleteOpen( false );
							} }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ handleConfirmBulkDelete }
							disabled={ ! bulkDeleteConfirmChecked }
						>
							{ __( 'Delete', 'buddyboss' ) }
						</Button>
					</div>
				</Modal>
			) }

			{ /* Bulk Edit Modal */ }
			{ bulkEditOpen && (
				<Modal
					title={ __( 'Bulk Edit', 'buddyboss' ) }
					onRequestClose={ function () {
						setBulkEditOpen( false );
					} }
					className="bb-reply-bulk-edit-modal bb-admin-settings-modal"
					shouldCloseOnClickOutside={ false }
				>
					<div className="bb-reply-bulk-edit-modal__body">
						<div className="bb-admin-bulk-modal__selected-items">
							{ selectedReplyNames.map( function ( item ) {
								return (
									<div key={ item.id } className="bb-admin-bulk-modal__selected-item">
										<CheckboxControl
											checked={ true }
											onChange={ function () {
												setSelected( function ( prev ) {
													var next = prev.filter( function ( i ) { return i !== item.id; } );
													if ( 0 === next.length ) {
														setBulkEditOpen( false );
													}
													return next;
												} );
											} }
											__nextHasNoMarginBottom
										/>
										<span className="bb-admin-bulk-modal__selected-item-name">
											{ decodeEntities( item.title ) }
										</span>
									</div>
								);
							} ) }
						</div>

						<SelectControl
							label={ __( 'Visibility', 'buddyboss' ) }
							value={ bulkEditVisibility }
							options={ [
								{ value: 'no_change', label: __( '\u2014 No Change \u2014', 'buddyboss' ) },
								{ value: 'publish', label: __( 'Public', 'buddyboss' ) },
								{ value: 'private', label: __( 'Private', 'buddyboss' ) },
								{ value: 'hidden', label: __( 'Hidden', 'buddyboss' ) },
							] }
							onChange={ setBulkEditVisibility }
							__nextHasNoMarginBottom
						/>
					</div>
					<div className="bb-reply-bulk-edit-modal__footer">
						<Button
							variant="secondary"
							onClick={ function () {
								setBulkEditOpen( false );
							} }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleConfirmBulkEdit }
							disabled={ 'no_change' === bulkEditVisibility }
						>
							{ __( 'Save', 'buddyboss' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}
