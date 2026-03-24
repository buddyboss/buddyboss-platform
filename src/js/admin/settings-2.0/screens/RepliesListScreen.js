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
import { sanitizeHtml, safeUrl, sanitizeCustomColumns } from '../utils/sanitize';
import { ListPagination } from '../components/common/ListPagination';
import { AdminNotice } from '../components/common/AdminNotice';
import { ListToolbar } from '../components/common/ListToolbar';
import { DeleteConfirmModal } from '../components/common/DeleteConfirmModal';
import { BulkEditModal } from '../components/common/BulkEditModal';
import { useListScreenHandlers } from '../hooks/useListScreenHandlers';
import { useListScreenState } from '../hooks/useListScreenState';
import { groupFieldsWithLayout, buildRegisteredFieldPayload, getVisibleFields, needsSeparator } from '../utils/format';
import { ReplyCreateModal } from '../components/forums/ReplyCreateModal';
import { RegisteredMetaField } from '../components/common/RegisteredMetaField';
import { forceRemoveEditor } from '../components/common/RichTextEditor';

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
 * Core column keys that are rendered natively by the React UI.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var CORE_COLUMNS = [ 'cb', 'title', 'bbp_reply_forum', 'bbp_reply_topic', 'bbp_reply_author', 'bbp_reply_created' ];

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
	// Common list screen state (loading, notice, selection, bulk, search).
	var common = useListScreenState();
	var isLoading = common.isLoading;
	var setIsLoading = common.setIsLoading;
	var notice = common.notice;
	var setNotice = common.setNotice;
	var selected = common.selectedIds;
	var setSelected = common.setSelectedIds;
	var bulkAction = common.bulkAction;
	var setBulkAction = common.setBulkAction;
	var isBulkProcessing = common.isBulkProcessing;
	var setIsBulkProcessing = common.setIsBulkProcessing;
	var search = common.searchInput;
	var setSearch = common.setSearchInput;
	var searchQuery = common.searchQuery;
	var setSearchQuery = common.setSearchQuery;

	// Screen-specific state.
	var repliesState = useState( [] );
	var replies = repliesState[ 0 ];
	var setReplies = repliesState[ 1 ];

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

	var searchTimerRef = useRef( null );

	// Metadata (views, bulk actions, columns).
	var viewsState = useState( null );
	var views = viewsState[ 0 ];
	var setViews = viewsState[ 1 ];

	var bulkActionsDataState = useState( null );
	var bulkActionsData = bulkActionsDataState[ 0 ];
	var setBulkActionsData = bulkActionsDataState[ 1 ];

	var columnsState = useState( null );
	var columns = columnsState[ 0 ];
	var setColumns = columnsState[ 1 ];

	var hasMetaRef = useRef( false );

	// Create modal.
	var isCreateOpenState = useState( false );
	var isCreateOpen = isCreateOpenState[ 0 ];
	var setIsCreateOpen = isCreateOpenState[ 1 ];

	// Edit modal.
	var createFieldsState = useState( [] );
	var createFields = createFieldsState[ 0 ];
	var setCreateFields = createFieldsState[ 1 ];

	var editReplyState = useState( null );
	var editReply = editReplyState[ 0 ];
	var setEditReply = editReplyState[ 1 ];

	var isEditOpenState = useState( false );
	var isEditOpen = isEditOpenState[ 0 ];
	var setIsEditOpen = isEditOpenState[ 1 ];

	var isEditLoadingState = useState( false );
	var isEditLoading = isEditLoadingState[ 0 ];
	var setIsEditLoading = isEditLoadingState[ 1 ];

	var isEditSavingState = useState( false );
	var isEditSaving = isEditSavingState[ 0 ];
	var setIsEditSaving = isEditSavingState[ 1 ];

	// Edit form: registered field values keyed by field ID.
	var editRegisteredValuesState = useState( {} );
	var editRegisteredValues = editRegisteredValuesState[ 0 ];
	var setEditRegisteredValues = editRegisteredValuesState[ 1 ];

	var editCascadeKeyState = useState( 0 );
	var editCascadeKey = editCascadeKeyState[ 0 ];
	var setEditCascadeKey = editCascadeKeyState[ 1 ];

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

	var deleteConfirmState = useState( false );
	var deleteConfirmChecked = deleteConfirmState[ 0 ];
	var setDeleteConfirmChecked = deleteConfirmState[ 1 ];

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
	var editAbortRef = useRef( null );

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
			include_meta: ( params && params.reset_meta ) || ! hasMetaRef.current ? 1 : 0,
		};

		getReplies( queryParams, { signal: abortRef.current.signal } ).then( function ( response ) {
			if ( response.success && response.data ) {
				setReplies( sanitizeCustomColumns( response.data.replies || [] ) );
				setTotalPages( response.data.total_pages || 1 );
				setTotalItems( response.data.total || 0 );

				if ( response.data.views ) {
					setViews( response.data.views );
				}
				if ( response.data.bulk_actions ) {
					setBulkActionsData( response.data.bulk_actions );
				}
				if ( response.data.columns ) {
					setColumns( response.data.columns );
				}
				if ( response.data.create_fields ) {
					setCreateFields( response.data.create_fields );
				}
				hasMetaRef.current = true;
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
	}, [ page, search, forumId, sort ] );

	// Initial fetch.
	useEffect( function () {
		fetchReplies( { page: 1 } );

		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
			if ( editAbortRef.current ) {
				editAbortRef.current.abort();
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
	 * Reset meta and refetch from page 1 (forces include_meta=1).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var resetAndRefetch = function () {
		hasMetaRef.current = false;
		setSelected( [] );
		fetchReplies( { page: 1, reset_meta: true } );
	};

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
		}, 500 );
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

	// Common selection handlers.
	var handlers = useListScreenHandlers( {
		setSearchInput: setSearch,
		setSearchQuery: setSearch,
		setPage: setPage,
		setSelectedIds: setSelected,
		getItemIds: function () {
			return replies.map( function ( r ) { return r.id; } );
		},
	} );
	var handleSelectAll = handlers.handleSelectAll;
	var handleSelectRow = handlers.handleSelectRow;

	/**
	 * Handle bulk action apply — routes to confirmation/edit modals.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleBulkApply = function () {
		if ( ! bulkAction || 0 === selected.length ) {
			return;
		}

		var action = bulkAction.replace( /^bulk_/, '' );

		if ( 'delete' === action ) {
			setBulkDeleteConfirmChecked( false );
			setBulkDeleteOpen( true );
			return;
		}

		if ( 'edit' === action ) {
			setBulkEditVisibility( 'no_change' );
			setBulkEditOpen( true );
		}
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
				setNotice( {
					message: ( response.data && response.data.message ) || __( 'Bulk action completed.', 'buddyboss' ),
					type: 'success',
				} );
				setBulkAction( '' );
				resetAndRefetch();
			} else {
				setNotice( {
					message: ( response.data && response.data.message ) || __( 'Bulk action failed.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function () {
			setIsBulkProcessing( false );
			setNotice( {
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
		setIsEditLoading( true );
		setIsEditOpen( true );
		setEditReply( null );
		setEditError( '' );

		// Abort any previous edit fetch.
		if ( editAbortRef.current ) {
			editAbortRef.current.abort();
		}
		var controller = new AbortController();
		editAbortRef.current = controller;

		getReply( reply.id, { signal: controller.signal } ).then( function ( response ) {
			setIsEditLoading( false );
			if ( response.success && response.data ) {
				var data = response.data;
				setEditReply( data );

				// Initialize registered values from registered_fields.
				var initVals = {};
				if ( data.registered_fields && Array.isArray( data.registered_fields ) ) {
					data.registered_fields.forEach( function ( field ) {
						initVals[ field.id ] = field.value;
					} );
				}
				setEditRegisteredValues( initVals );
			} else {
				setIsEditOpen( false );
				setNotice( {
					message: __( 'Failed to load reply data.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function ( err ) {
			if ( err && 'AbortError' === err.name ) {
				return;
			}
			setIsEditLoading( false );
			setIsEditOpen( false );
			setNotice( {
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
		var contentVal = editRegisteredValues.content || '';
		// Pull from TinyMCE if available.
		if ( window.tinymce ) {
			var tinymceEditor = window.tinymce.get( 'bb-admin-edit-content-' + editReply.id );
			if ( tinymceEditor ) {
				contentVal = tinymceEditor.getContent();
			}
		}

		if ( ! contentVal.trim() ) {
			setEditError( __( 'Description is required.', 'buddyboss' ) );
			return;
		}

		setIsEditSaving( true );
		setEditError( '' );

		var registeredPayload = editReply.registered_fields
			? buildRegisteredFieldPayload( editReply.registered_fields, editRegisteredValues, editReply.id )
			: {};

		// buildRegisteredFieldPayload emits both plain keys and registered_field_* keys automatically.
		var payload = Object.assign( registeredPayload, {
			reply_id: editReply.id,
			content: contentVal, // Override with TinyMCE-pulled value.
		} );

		saveReply( payload ).then( function ( response ) {
			setIsEditSaving( false );
			if ( response.success ) {
				// Clean up TinyMCE editors for richtext fields.
				if ( editReply && editReply.registered_fields ) {
					editReply.registered_fields.forEach( function ( field ) {
						if ( 'richtext' === field.type ) {
							forceRemoveEditor( 'bb-admin-edit-' + field.id + '-' + editReply.id );
						}
					} );
				}
				setIsEditOpen( false );
				setEditReply( null );
				setNotice( {
					message: __( 'Reply updated successfully.', 'buddyboss' ),
					type: 'success',
				} );
				resetAndRefetch();
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
		// Clean up TinyMCE editors for richtext fields.
		if ( editReply && editReply.registered_fields ) {
			editReply.registered_fields.forEach( function ( field ) {
				if ( 'richtext' === field.type ) {
					forceRemoveEditor( 'bb-admin-edit-' + field.id + '-' + editReply.id );
				}
			} );
		}
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
				setNotice( {
					message: reply.is_spam
						? __( 'Reply unmarked as spam.', 'buddyboss' )
						: __( 'Reply marked as spam.', 'buddyboss' ),
					type: 'success',
				} );
				resetAndRefetch();
			} else {
				setNotice( {
					message: __( 'Failed to update reply.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function () {
			setNotice( {
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
				setNotice( {
					message: __( 'Reply deleted successfully.', 'buddyboss' ),
					type: 'success',
				} );
				resetAndRefetch();
			} else {
				setNotice( {
					message: ( response.data && response.data.message ) || __( 'Failed to delete reply.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function () {
			setIsDeleting( false );
			setDeleteReplyItem( null );
			setNotice( {
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
		setNotice( {
			message: __( 'Reply created successfully.', 'buddyboss' ),
			type: 'success',
		} );
		resetAndRefetch();
	};

	// Build bulk action options from meta.
	var bulkActionOptions = [ { value: '', label: __( 'Bulk Actions', 'buddyboss' ) } ];
	if ( bulkActionsData ) {
		Object.keys( bulkActionsData ).forEach( function ( key ) {
			bulkActionOptions.push( { value: key, label: decodeEntities( bulkActionsData[ key ] ) } );
		} );
	}

	// Build forum filter list from meta.
	var forumsList = views && views.forums ? views.forums : [];

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
		forumFilterLabel = views && views.all
			? sprintf( __( 'All Forums (%s)', 'buddyboss' ), views.all )
			: __( 'All Forums', 'buddyboss' );
	}

	// Compute custom column keys (columns added by third-party plugins).
	var customColumnKeys = useMemo( function () {
		if ( ! columns ) {
			return [];
		}
		return Object.keys( columns ).filter( function ( key ) {
			return CORE_COLUMNS.indexOf( key ) === -1;
		} );
	}, [ columns ] );

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
					<i className="bb-icons-rl-plus"></i>
					{ __( 'Create New Reply', 'buddyboss' ) }
				</Button>
			</div>

			{ /* Notice */ }
			<AdminNotice notice={ notice } onDismiss={ function () { setNotice( null ); } } />

			{ /* Toolbar */ }
			<ListToolbar
				className="bb-replies-list"
				bulkAction={ bulkAction }
				bulkOptions={ bulkActionOptions }
				onBulkActionChange={ setBulkAction }
				onBulkApply={ handleBulkApply }
				selectedCount={ selected.length }
				isBulkProcessing={ isBulkProcessing }
				searchInput={ search }
				onSearchChange={ handleSearch }
				searchPlaceholder={ __( 'Search replies', 'buddyboss' ) }
				onSearchClear={ function () { handleSearch( '' ); } }
			>
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
						<i className="bb-icons-rl bb-icons-rl-caret-down"></i>
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
									{ views && views.all ? sprintf( __( 'All Forums (%s)', 'buddyboss' ), views.all ) : __( 'All Forums', 'buddyboss' ) }
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
				<SelectControl
					value={ sort }
					options={ sortOptions }
					onChange={ handleSortChange }
					__nextHasNoMarginBottom
					className="bb-replies-list__sort-select"
				/>
			</ListToolbar>

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
								{ columns && columns.title ? decodeEntities( columns.title ) : __( 'Reply', 'buddyboss' ) }
							</th>
							<th className="bb-replies-list__col-forum">
								{ columns && columns.bbp_reply_forum ? decodeEntities( columns.bbp_reply_forum ) : __( 'Forum', 'buddyboss' ) }
							</th>
							<th className="bb-replies-list__col-discussion">
								{ columns && columns.bbp_reply_topic ? decodeEntities( columns.bbp_reply_topic ) : __( 'Discussion', 'buddyboss' ) }
							</th>
							<th className="bb-replies-list__col-created">
								{ columns && columns.bbp_reply_created ? decodeEntities( columns.bbp_reply_created ) : __( 'Created', 'buddyboss' ) }
							</th>
							{ /* Custom columns from bbp_admin_replies_column_headers filter */ }
							{ customColumnKeys.map( function ( key ) {
								return (
									<th key={ key } className={ 'bb-replies-list__col-custom bb-replies-list__col--' + key }>
										{ columns[ key ] }
									</th>
								);
							} ) }
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
											{ reply.status_label && (
												<span className={ reply.is_spam ? 'bb-admin-list__spam-badge' : 'bb-admin-list__status-badge' }>
													{ reply.is_spam && ( <i className="bb-icons-rl-flag"></i> ) }
													{ reply.status_label }
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
									{ /* Custom columns */ }
									{ reply.custom_columns && customColumnKeys.map( function ( key ) {
										return (
											<td key={ key } className={ 'bb-replies-list__col-custom bb-replies-list__col--' + key }>
												<span dangerouslySetInnerHTML={ { __html: sanitizeHtml( reply.custom_columns[ key ] ) } } />
											</td>
										);
									} ) }
									<td className="bb-replies-list__col-actions">
										<DropdownMenu
											icon={ <i className="bb-icons-rl-dots-three"></i> }
											label={ __( 'Actions', 'buddyboss' ) }
											className="bb-replies-list__actions-menu"
										>
											{ function ( dropdownProps ) {
												var onClose = dropdownProps.onClose;
												return (
													<MenuGroup className="bb_dropdown_menu_group">
														{ reply.permalink && (
															<MenuItem
																onClick={ function () {
																	var permalink = safeUrl( reply.permalink );
																	if ( '#' !== permalink ) {
																		window.open( permalink, '_blank', 'noopener noreferrer' );
																	}
																	onClose();
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-eye"></i>
																{ __( 'View', 'buddyboss' ) }
																<i className="bb-icons-rl bb-icons-rl-arrow-up-right bb-icons-external"></i>
															</MenuItem>
														) }
														<MenuItem
															onClick={ function () {
																handleEdit( reply );
																onClose();
															} }
														>
															<i className="bb-icons-rl bb-icons-rl-note-pencil"></i>
															{ __( 'Edit', 'buddyboss' ) }
														</MenuItem>
														<MenuItem
															onClick={ function () {
																handleSpamToggle( reply );
																onClose();
															} }
														>
															<i className="bb-icons-rl bb-icons-rl-flag"></i>
															{ reply.is_spam
																? __( 'Not Spam', 'buddyboss' )
																: __( 'Spam', 'buddyboss' )
															}
														</MenuItem>
														<MenuItem
															isDestructive
															onClick={ function () {
																handleDeleteClick( reply );
																onClose();
															} }
															className="bb-replies-list__action-delete"
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

			{ /* Footer */ }
			{ ! isLoading && ! error && (
				<ListPagination
					currentPage={ page }
					totalPages={ totalPages }
					total={ totalItems }
					onPageChange={ handlePageChange }
					className="bb-replies-list"
				/>
			) }

			{ /* Create Modal */ }
			<ReplyCreateModal
				isOpen={ isCreateOpen }
				onClose={ function () {
					setIsCreateOpen( false );
				} }
				onCreated={ handleReplyCreated }
				createFields={ createFields }
			/>

			{ /* Edit Modal */ }
			{ isEditOpen && (
				<Modal
					title={ __( 'Edit Reply', 'buddyboss' ) }
					onRequestClose={ function () {
						if ( ! isEditSaving ) {
							handleEditClose();
						}
					} }
					className="bb-reply-edit-modal bb-reply-modal bb-admin-settings-modal"
					shouldCloseOnClickOutside={ false }
				>
					{ isEditLoading ? (
						<div className="bb-reply-modal__loading">
							<Spinner />
						</div>
					) : (
						<>
							<div className="bb-reply-modal__body bb-admin-settings-modal__body">
								{ editError && (
									<p className="bb-admin-settings-modal__error">{ editError }</p>
								) }

								{ editReply && editReply.registered_fields && ( function () {
									var visibleFields = getVisibleFields( editReply.registered_fields, editRegisteredValues );
									var grouped = groupFieldsWithLayout( visibleFields );

									var getEditAsyncExtraParams = function ( field ) {
										if ( ! field.async_depends_on ) {
											return {};
										}
										var depVal = editRegisteredValues[ field.async_depends_on ];
										if ( ! depVal ) {
											return {};
										}
										var params = {};
										params[ field.async_depends_on ] = depVal;
										return params;
									};

									var handleEditFieldChange = function ( fieldId, val ) {
										setEditRegisteredValues( function ( prev ) {
											var next = {};
											Object.keys( prev ).forEach( function ( k ) {
												next[ k ] = prev[ k ];
											} );
											next[ fieldId ] = val;

											if ( 'forum_id' === fieldId ) {
												next.topic_id = 0;
												next.reply_to = 0;
												setEditCascadeKey( function ( k ) { return k + 1; } );
											}
											if ( 'topic_id' === fieldId ) {
												next.reply_to = 0;
												setEditCascadeKey( function ( k ) { return k + 1; } );
											}

											return next;
										} );
									};

									return grouped.map( function ( item, idx ) {
										var hasSeparator = needsSeparator( item, grouped[ idx + 1 ], [ 'reply_to', 'reply_status' ] );

										if ( 'row' === item.type ) {
											return (
												<div key={ 'row-' + idx } className={ 'bb-admin-meta-field__row bb-admin-settings-modal__row' + ( hasSeparator ? ' bb-admin-settings-modal__row--separator' : '' ) }>
													{ item.fields.map( function ( field ) {
														return (
															<RegisteredMetaField
																key={ field.id + '-' + editReply.id + '-' + editCascadeKey }
																field={ Object.assign( {}, field, { asyncExtraParams: getEditAsyncExtraParams( field ) } ) }
																value={ editRegisteredValues[ field.id ] }
																onChange={ function ( val ) {
																	handleEditFieldChange( field.id, val );
																} }
																itemId={ editReply.id }
															/>
														);
													} ) }
												</div>
											);
										}

										return (
											<div key={ item.field.id + '-' + editReply.id + '-' + editCascadeKey } className={ hasSeparator ? 'bb-admin-settings-modal__row--separator' : '' }>
												<RegisteredMetaField
													field={ Object.assign( {}, item.field, { asyncExtraParams: getEditAsyncExtraParams( item.field ) } ) }
													value={ editRegisteredValues[ item.field.id ] }
													onChange={ function ( val ) {
														handleEditFieldChange( item.field.id, val );
													} }
													itemId={ editReply.id }
												/>
											</div>
										);
									} );
								} )() }
							</div>

							<div className="bb-reply-modal__footer bb-admin-settings-modal__footer">
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
									disabled={ isEditSaving }
								>
									{ __( 'Save', 'buddyboss' ) }
								</Button>
							</div>
						</>
					) }
				</Modal>
			) }

			{ /* Single Delete Confirmation Modal */ }
			<DeleteConfirmModal
				isOpen={ !! deleteReplyItem }
				singleTitle={ __( 'Delete reply?', 'buddyboss' ) }
				items={ deleteReplyItem ? [ { id: deleteReplyItem.id, title: deleteReplyItem.content || deleteReplyItem.id } ] : [] }
				warningText={ __( 'This permanently deletes replies from the community and cannot be undone.', 'buddyboss' ) }
				description={ __( 'Deletes the reply and all related content from the community. This action cannot be undone.', 'buddyboss' ) }
				confirmLabel={ __( 'I understand that this deletes the reply.', 'buddyboss' ) }
				confirmChecked={ deleteConfirmChecked }
				onConfirmChange={ setDeleteConfirmChecked }
				onConfirm={ handleDeleteConfirm }
				onClose={ function () { setDeleteReplyItem( null ); setDeleteConfirmChecked( false ); } }
				isProcessing={ isDeleting }
				className="bb-reply-delete-modal"
			/>

			{ /* Bulk Delete Confirmation Modal */ }
			<DeleteConfirmModal
				isOpen={ bulkDeleteOpen }
				singleTitle={ __( 'Delete reply?', 'buddyboss' ) }
				items={ selectedReplyNames }
				onRemoveItem={ function ( id ) {
					setSelected( function ( prev ) {
						var next = prev.filter( function ( i ) { return i !== id; } );
						if ( 0 === next.length ) {
							setBulkDeleteOpen( false );
						}
						return next;
					} );
				} }
				warningText={ __( 'This permanently deletes replies from the community and cannot be undone.', 'buddyboss' ) }
				description={ __( 'Deletes the reply and all related content from the community. This action cannot be undone.', 'buddyboss' ) }
				confirmLabel={ __( 'I understand that this deletes the reply.', 'buddyboss' ) }
				confirmChecked={ bulkDeleteConfirmChecked }
				onConfirmChange={ setBulkDeleteConfirmChecked }
				onConfirm={ handleConfirmBulkDelete }
				onClose={ function () { setBulkDeleteOpen( false ); } }
				className="bb-reply-delete-modal"
			/>

			{ /* Bulk Edit Modal */ }
			<BulkEditModal
				isOpen={ bulkEditOpen }
				items={ selectedReplyNames }
				onRemoveItem={ function ( id ) {
					setSelected( function ( prev ) {
						var next = prev.filter( function ( i ) { return i !== id; } );
						if ( 0 === next.length ) {
							setBulkEditOpen( false );
						}
						return next;
					} );
				} }
				onConfirm={ handleConfirmBulkEdit }
				onClose={ function () { setBulkEditOpen( false ); } }
				confirmDisabled={ 'no_change' === bulkEditVisibility }
				className="bb-reply-bulk-edit-modal"
			>
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
			</BulkEditModal>
		</div>
	);
}
