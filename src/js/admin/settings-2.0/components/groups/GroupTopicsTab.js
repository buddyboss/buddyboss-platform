/**
 * BuddyBoss Admin Settings 2.0 - Group Topics Tab
 *
 * Custom component for the Topics tab in the Group Edit Modal.
 * Follows the same design pattern as Activity Topics (TopicListField/TopicItem).
 * Supports drag-and-drop reorder, 3-dot dropdown menu, add/edit/delete modals.
 *
 * Uses existing topic AJAX handlers (bb_add_topic, bb_delete_topic,
 * bb_migrate_topic, bb_update_topics_order) with per-action nonces
 * returned from the initial fetch.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import {
	Button,
	Spinner,
	Modal,
	TextControl,
	RadioControl,
	SelectControl,
	DropdownMenu,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { BBIcon } from '../common/BBIcon';
import { getGroupTopics } from '../../utils/ajax';

/**
 * Make an AJAX call to existing topic handlers with per-action nonces.
 *
 * These handlers use their own nonce actions (bb_add_topic, bb_delete_topic, etc.)
 * rather than the Settings 2.0 global nonce. This helper builds a FormData
 * request with the correct nonce, bypassing ajaxFetch's auto-nonce.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} action   AJAX action name.
 * @param {Object} data     Request data.
 * @param {string} nonce    Per-action nonce value.
 * @param {Object} options  Optional fetch options (e.g. { signal }).
 * @return {Promise} Promise resolving to JSON response.
 */
function topicAjaxFetch( action, data, nonce, options ) {
	var ajaxUrl = window.bbAdminData && window.bbAdminData.ajaxUrl
		? window.bbAdminData.ajaxUrl
		: '/wp-admin/admin-ajax.php';

	var formData = new FormData();
	formData.append( 'action', action );
	formData.append( 'nonce', nonce );

	Object.keys( data ).forEach( function ( key ) {
		var val = data[ key ];
		// Handle arrays (e.g. topic_ids for reorder).
		if ( Array.isArray( val ) ) {
			val.forEach( function ( item ) {
				formData.append( key + '[]', item );
			} );
		} else {
			formData.append( key, val );
		}
	} );

	return fetch( ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
		signal: options && options.signal ? options.signal : undefined,
	} ).then( function ( response ) {
		if ( ! response.ok ) {
			throw new Error( 'HTTP ' + response.status + ': ' + response.statusText );
		}
		return response.json();
	} );
}

/**
 * Group Topics Tab Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props            Component props.
 * @param {number}   props.groupId    Group ID.
 * @param {Function} props.setNotice  Function to show notices.
 * @returns {JSX.Element} Topics tab.
 */
export function GroupTopicsTab( { groupId, setNotice } ) {
	var topicsState = useState( [] );
	var topics = topicsState[ 0 ];
	var setTopics = topicsState[ 1 ];

	var isLoadingState = useState( true );
	var isLoading = isLoadingState[ 0 ];
	var setIsLoading = isLoadingState[ 1 ];

	var maxTopicsState = useState( 20 );
	var maxTopics = maxTopicsState[ 0 ];
	var setMaxTopics = maxTopicsState[ 1 ];

	var permissionTypesState = useState( [] );
	var permissionTypes = permissionTypesState[ 0 ];
	var setPermissionTypes = permissionTypesState[ 1 ];

	var noncesState = useState( {} );
	var nonces = noncesState[ 0 ];
	var setNonces = noncesState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	// Add/Edit modal state.
	var isAddModalOpenState = useState( false );
	var isAddModalOpen = isAddModalOpenState[ 0 ];
	var setIsAddModalOpen = isAddModalOpenState[ 1 ];

	var isEditModalOpenState = useState( false );
	var isEditModalOpen = isEditModalOpenState[ 0 ];
	var setIsEditModalOpen = isEditModalOpenState[ 1 ];

	var editingTopicState = useState( null );
	var editingTopic = editingTopicState[ 0 ];
	var setEditingTopic = editingTopicState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	// Delete modal state.
	var isDeleteModalOpenState = useState( false );
	var isDeleteModalOpen = isDeleteModalOpenState[ 0 ];
	var setIsDeleteModalOpen = isDeleteModalOpenState[ 1 ];

	var deletingTopicState = useState( null );
	var deletingTopic = deletingTopicState[ 0 ];
	var setDeletingTopic = deletingTopicState[ 1 ];

	var availableTopicsState = useState( [] );
	var availableTopics = availableTopicsState[ 0 ];
	var setAvailableTopics = availableTopicsState[ 1 ];

	var migrateNonceState = useState( '' );
	var migrateNonce = migrateNonceState[ 0 ];
	var setMigrateNonce = migrateNonceState[ 1 ];

	var isDeleteLoadingState = useState( false );
	var isDeleteLoading = isDeleteLoadingState[ 0 ];
	var setIsDeleteLoading = isDeleteLoadingState[ 1 ];

	// Drag-and-drop state.
	var dragIndexState = useState( null );
	var dragIndex = dragIndexState[ 0 ];
	var setDragIndex = dragIndexState[ 1 ];

	var dragOverIndexState = useState( null );
	var dragOverIndex = dragOverIndexState[ 0 ];
	var setDragOverIndex = dragOverIndexState[ 1 ];

	var abortRef = useRef( null );

	/**
	 * Fetch topics from the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var fetchTopics = function () {
		setIsLoading( true );

		// Cancel any in-flight request.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		abortRef.current = new AbortController();

		getGroupTopics( groupId, { signal: abortRef.current.signal } ).then( function ( response ) {
			if ( response.success && response.data ) {
				setTopics( response.data.topics || [] );
				setMaxTopics( response.data.max_topics || 20 );
				setPermissionTypes( response.data.permission_types || [] );
				setNonces( response.data.nonces || {} );
			}
			setIsLoading( false );
		} ).catch( function ( err ) {
			if ( 'AbortError' !== err.name ) {
				setIsLoading( false );
			}
		} );
	};

	// Fetch on mount.
	useEffect( function () {
		fetchTopics();

		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [ groupId ] ); // eslint-disable-line react-hooks/exhaustive-deps

	/**
	 * Handle adding a new topic.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} data Topic data { name, permission_type }.
	 */
	var handleAddTopic = useCallback( function ( data ) {
		setIsSaving( true );
		topicAjaxFetch( 'bb_add_topic', {
			name: data.name,
			permission_type: data.permission_type,
			item_type: 'groups',
			item_id: groupId,
		}, nonces.add || '' ).then( function ( response ) {
			setIsSaving( false );
			if ( response.success && response.data && response.data.content && response.data.content.topic ) {
				var newTopic = response.data.content.topic;
				setTopics( function ( prev ) {
					return prev.concat( [ newTopic ] );
				} );
				setIsAddModalOpen( false );
				setError( '' );
				if ( setNotice ) {
					setNotice( { type: 'success', message: __( 'Topic created successfully.', 'buddyboss' ) } );
				}
			} else {
				setError( response.data && response.data.message ? response.data.message : __( 'Failed to add topic.', 'buddyboss' ) );
			}
		} ).catch( function () {
			setIsSaving( false );
			setError( __( 'An error occurred while adding the topic.', 'buddyboss' ) );
		} );
	}, [ groupId, nonces ] );

	/**
	 * Handle editing a topic.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} data Topic data { name, permission_type, topic_id }.
	 */
	var handleEditTopic = useCallback( function ( data ) {
		setIsSaving( true );
		topicAjaxFetch( 'bb_add_topic', {
			topic_id: data.topic_id,
			name: data.name,
			permission_type: data.permission_type,
			item_type: 'groups',
			item_id: groupId,
		}, nonces.add || '' ).then( function ( response ) {
			setIsSaving( false );
			if ( response.success && response.data && response.data.content && response.data.content.topic ) {
				var updatedTopic = response.data.content.topic;
				var originalTopicId = String( data.topic_id );
				setTopics( function ( prev ) {
					return prev.map( function ( t ) {
						if ( String( t.topic_id ) === originalTopicId ) {
							return updatedTopic;
						}
						return t;
					} );
				} );
				setIsEditModalOpen( false );
				setEditingTopic( null );
				setError( '' );
				if ( setNotice ) {
					setNotice( { type: 'success', message: __( 'Topic updated successfully.', 'buddyboss' ) } );
				}
			} else {
				setError( response.data && response.data.message ? response.data.message : __( 'Failed to update topic.', 'buddyboss' ) );
			}
		} ).catch( function () {
			setIsSaving( false );
			setError( __( 'An error occurred while updating the topic.', 'buddyboss' ) );
		} );
	}, [ groupId, nonces ] );

	/**
	 * Handle initiating delete (step 1: fetch available topics for migration).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} topic Topic to delete.
	 */
	var handleInitiateDelete = useCallback( function ( topic ) {
		setDeletingTopic( topic );
		setIsDeleteLoading( true );
		topicAjaxFetch( 'bb_delete_topic', {
			topic_id: topic.topic_id,
			item_type: 'groups',
			item_id: groupId,
		}, nonces.delete || '' ).then( function ( response ) {
			setIsDeleteLoading( false );
			if ( response.success && response.data ) {
				setAvailableTopics( response.data.topic_lists || [] );
				setMigrateNonce( response.data.nonce || '' );
				setIsDeleteModalOpen( true );
				setError( '' );
			} else {
				setError( response.data && response.data.message ? response.data.message : __( 'Failed to initiate topic deletion.', 'buddyboss' ) );
			}
		} ).catch( function () {
			setIsDeleteLoading( false );
			setError( __( 'An error occurred while initiating topic deletion.', 'buddyboss' ) );
		} );
	}, [ groupId, nonces ] );

	/**
	 * Handle confirming delete (step 2: migrate or delete).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} data Delete confirmation data.
	 */
	var handleConfirmDelete = useCallback( function ( data ) {
		setIsSaving( true );
		topicAjaxFetch( 'bb_migrate_topic', {
			old_topic_id: data.old_topic_id,
			migrate_type: data.migrate_type,
			new_topic_id: data.new_topic_id || 0,
			item_type: 'groups',
			item_id: groupId,
		}, data.nonce ).then( function ( response ) {
			setIsSaving( false );
			if ( response.success ) {
				setTopics( function ( prev ) {
					return prev.filter( function ( t ) {
						return String( t.topic_id ) !== String( data.old_topic_id );
					} );
				} );
				setIsDeleteModalOpen( false );
				setDeletingTopic( null );
				setError( '' );
				if ( setNotice ) {
					setNotice( { type: 'success', message: __( 'Topic deleted successfully.', 'buddyboss' ) } );
				}
			} else {
				setError( response.data && response.data.message ? response.data.message : __( 'Failed to delete topic.', 'buddyboss' ) );
			}
		} ).catch( function () {
			setIsSaving( false );
			setError( __( 'An error occurred while deleting the topic.', 'buddyboss' ) );
		} );
	}, [ groupId ] );

	/**
	 * Handle drag-and-drop reorder.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleDragStart = function ( index ) {
		return function ( e ) {
			setDragIndex( index );
			e.dataTransfer.effectAllowed = 'move';
		};
	};

	var handleDragOver = function ( index ) {
		return function ( e ) {
			e.preventDefault();
			e.dataTransfer.dropEffect = 'move';
			setDragOverIndex( index );
		};
	};

	var handleDragEnd = function () {
		if ( null !== dragIndex && null !== dragOverIndex && dragIndex !== dragOverIndex ) {
			var previousTopics = topics.slice();
			var reordered = topics.slice();
			var draggedItem = reordered.splice( dragIndex, 1 )[ 0 ];
			reordered.splice( dragOverIndex, 0, draggedItem );

			setTopics( reordered );

			// Persist order via AJAX.
			var topicIds = reordered.map( function ( t ) {
				return t.topic_id;
			} );
			topicAjaxFetch( 'bb_update_topics_order', {
				topic_ids: topicIds,
			}, nonces.reorder || '' ).then( function ( response ) {
				if ( ! response.success ) {
					setTopics( previousTopics );
					setError( __( 'Failed to save topic order.', 'buddyboss' ) );
				}
			} ).catch( function () {
				setTopics( previousTopics );
				setError( __( 'Failed to save topic order.', 'buddyboss' ) );
			} );
		}
		setDragIndex( null );
		setDragOverIndex( null );
	};

	var handleDrop = function () {
		handleDragEnd();
	};

	var canAddMore = topics.length < maxTopics;

	if ( isLoading ) {
		return (
			<div className="bb-group-topics-tab__loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="bb-group-topics-tab">
			{ error && (
				<div className="bb-admin-notice bb-admin-notice--error">
					<span>{ error }</span>
					<button
						className="bb-admin-notice--dismiss"
						onClick={ function () {
							setError( '' );
						} }
					>
						<i className="bb-icons-rl bb-icons-rl-x"></i>
					</button>
				</div>
			) }

			<div className="bb-topic-list__items">
				{ topics.map( function ( topic, index ) {
					var permissionLabel = topic.permission_label || topic.permission_type || '';

					var itemClasses = 'bb-topic-list__item';
					if ( dragIndex === index ) {
						itemClasses += ' bb-topic-list__item--dragging';
					}
					if ( dragOverIndex === index ) {
						itemClasses += ' bb-topic-list__item--drag-over';
					}

					return (
						<div
							key={ topic.topic_id || index }
							draggable="true"
							onDragStart={ handleDragStart( index ) }
							onDragOver={ handleDragOver( index ) }
							onDrop={ handleDrop }
							onDragEnd={ handleDragEnd }
						>
							<div className={ itemClasses } data-topic-id={ topic.topic_id }>
								<span
									className="bb-topic-list__drag-handle"
									onMouseDown={ function ( e ) {
										e.stopPropagation();
									} }
								>
									<BBIcon name="list" />
								</span>
								<span className="bb-topic-list__name">{ topic.name }</span>
								<span className="bb-topic-list__permission">{ permissionLabel }</span>
								<div className="bb-topic-list__actions">
									<DropdownMenu
										icon={ <i className="bb-icons-rl-dots-three"></i> }
										label={ __( 'More options', 'buddyboss' ) }
										className="bb-topic-list__menu-btn"
									>
										{ function ( { onClose } ) {
											return (
												<MenuGroup className="bb_dropdown_menu_group">
													<MenuItem
														icon={ <BBIcon name="note-pencil" /> }
														iconPosition="left"
														onClick={ function () {
															onClose();
															setEditingTopic( topic );
															setIsEditModalOpen( true );
														} }
													>
														{ __( 'Edit', 'buddyboss' ) }
													</MenuItem>
													<MenuItem
														icon={ <BBIcon name="trash" /> }
														iconPosition="left"
														isDestructive
														onClick={ function () {
															onClose();
															handleInitiateDelete( topic );
														} }
													>
														{ __( 'Delete', 'buddyboss' ) }
													</MenuItem>
												</MenuGroup>
											);
										} }
									</DropdownMenu>
								</div>
							</div>
						</div>
					);
				} ) }

				{ canAddMore && (
					<Button
						variant="secondary"
						className="bb-topic-list__add-btn"
						onClick={ function () {
							setIsAddModalOpen( true );
						} }
					>
						<span className="bb-icons-rl-plus"></span>
						{ __( 'Add New Topic', 'buddyboss' ) }
					</Button>
				) }
			</div>

			<p className="bb-topic-list__description">
				{ /* translators: %d: maximum number of topics allowed. */
					wp.i18n.sprintf(
						__( 'Maximum of %d topics can be added.', 'buddyboss' ),
						maxTopics
					)
				}
			</p>

			{ /* Add Topic Modal */ }
			<GroupTopicModal
				isOpen={ isAddModalOpen }
				onClose={ function () {
					setIsAddModalOpen( false );
				} }
				onSave={ handleAddTopic }
				topic={ null }
				isSaving={ isSaving }
				permissionTypes={ permissionTypes }
			/>

			{ /* Edit Topic Modal */ }
			<GroupTopicModal
				isOpen={ isEditModalOpen }
				onClose={ function () {
					setIsEditModalOpen( false );
					setEditingTopic( null );
				} }
				onSave={ handleEditTopic }
				topic={ editingTopic }
				isSaving={ isSaving }
				permissionTypes={ permissionTypes }
			/>

			{ /* Delete loading overlay */ }
			{ isDeleteLoading && (
				<div className="bb-topic-list__delete-loading">
					<Spinner />
				</div>
			) }

			{ /* Delete Confirmation Modal */ }
			<GroupTopicDeleteModal
				isOpen={ isDeleteModalOpen }
				onClose={ function () {
					setIsDeleteModalOpen( false );
					setDeletingTopic( null );
				} }
				onConfirm={ handleConfirmDelete }
				topic={ deletingTopic }
				availableTopics={ availableTopics }
				migrateNonce={ migrateNonce }
				isSaving={ isSaving }
			/>
		</div>
	);
}

/**
 * Group Topic Add/Edit Modal Component.
 *
 * Follows the same pattern as Activity TopicModal.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                 Component props.
 * @param {boolean}  props.isOpen          Whether the modal is open.
 * @param {Function} props.onClose         Close handler.
 * @param {Function} props.onSave          Save handler.
 * @param {Object}   props.topic           Topic object for editing (null for add).
 * @param {boolean}  props.isSaving        Whether save is in progress.
 * @param {Array}    props.permissionTypes  Permission type options from API.
 * @returns {JSX.Element|null} Modal component or null.
 */
function GroupTopicModal( { isOpen, onClose, onSave, topic, isSaving, permissionTypes } ) {
	var isEditing = !! topic;
	var initialName = isEditing ? ( topic.name || '' ) : '';
	// Default to first permission type value (usually 'members') for groups.
	var defaultPermission = permissionTypes && permissionTypes.length > 0
		? permissionTypes[ 0 ].value
		: 'members';
	var initialPermission = isEditing ? ( topic.permission_type || defaultPermission ) : defaultPermission;

	var nameState = useState( initialName );
	var name = nameState[ 0 ];
	var setName = nameState[ 1 ];

	var permissionState = useState( initialPermission );
	var permission = permissionState[ 0 ];
	var setPermission = permissionState[ 1 ];

	var errorState = useState( '' );
	var modalError = errorState[ 0 ];
	var setModalError = errorState[ 1 ];

	// Reset form when topic changes.
	useEffect( function () {
		if ( isOpen ) {
			setName( isEditing ? ( topic.name || '' ) : '' );
			setPermission( isEditing ? ( topic.permission_type || defaultPermission ) : defaultPermission );
			setModalError( '' );
		}
	}, [ isOpen, topic ] );

	if ( ! isOpen ) {
		return null;
	}

	var handleSave = function () {
		var trimmedName = name.trim();
		if ( ! trimmedName ) {
			setModalError( __( 'Topic name is required.', 'buddyboss' ) );
			return;
		}
		setModalError( '' );
		onSave( {
			name: trimmedName,
			permission_type: permission,
			topic_id: isEditing ? topic.topic_id : 0,
		} );
	};

	return (
		<Modal
			title={ isEditing ? __( 'Edit Topic', 'buddyboss' ) : __( 'Add New Topic', 'buddyboss' ) }
			onRequestClose={ onClose }
			className="bb-topic-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-topic-modal__body">
				<TextControl
					label={ __( 'Topic Name', 'buddyboss' ) }
					value={ name }
					onChange={ function ( val ) {
						setName( val );
						if ( modalError ) {
							setModalError( '' );
						}
					} }
					placeholder={ __( 'Enter topic name', 'buddyboss' ) }
					__nextHasNoMarginBottom
				/>
				{ modalError && (
					<p className="bb-topic-modal__error">{ modalError }</p>
				) }

				{ permissionTypes && permissionTypes.length > 0 && (
					<div className="bb-topic-modal__permission">
						<label className="bb-topic-modal__permission-label">
							{ __( 'Posting Permissions', 'buddyboss' ) }
						</label>
						<RadioControl
							selected={ permission }
							options={ permissionTypes.map( function ( pt ) {
								return { label: pt.label, value: pt.value };
							} ) }
							onChange={ setPermission }
						/>
					</div>
				) }
			</div>

			<div className="bb-topic-modal__footer bb-admin-settings-modal__footer">
				<Button
					variant="secondary"
					onClick={ onClose }
					disabled={ isSaving }
				>
					{ __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ handleSave }
					isBusy={ isSaving }
					disabled={ isSaving }
				>
					{ isEditing ? __( 'Save', 'buddyboss' ) : __( 'Add Topic', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}

/**
 * Group Topic Delete Confirmation Modal.
 *
 * Follows the same pattern as Activity TopicDeleteModal.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                 Component props.
 * @param {boolean}  props.isOpen          Whether the modal is open.
 * @param {Function} props.onClose         Close handler.
 * @param {Function} props.onConfirm       Confirm handler.
 * @param {Object}   props.topic           Topic being deleted.
 * @param {Array}    props.availableTopics Topics available for migration.
 * @param {string}   props.migrateNonce    Nonce for migrate action.
 * @param {boolean}  props.isSaving        Whether action is in progress.
 * @returns {JSX.Element|null} Modal component or null.
 */
function GroupTopicDeleteModal( { isOpen, onClose, onConfirm, topic, availableTopics, migrateNonce, isSaving } ) {
	var migrateTypeState = useState( 'migrate' );
	var migrateType = migrateTypeState[ 0 ];
	var setMigrateType = migrateTypeState[ 1 ];

	var newTopicIdState = useState( '' );
	var newTopicId = newTopicIdState[ 0 ];
	var setNewTopicId = newTopicIdState[ 1 ];

	// Reset selections when the modal opens for a (different) topic.
	useEffect( function () {
		if ( isOpen && topic ) {
			setMigrateType( 'migrate' );
			setNewTopicId( '' );
		}
	}, [ isOpen, topic ] );

	if ( ! isOpen || ! topic ) {
		return null;
	}

	var topicOptions = ( availableTopics || [] ).map( function ( t ) {
		return {
			label: t.name,
			value: String( t.topic_id ),
		};
	} );

	// Add a default empty option.
	topicOptions.unshift( {
		label: __( 'Select topic', 'buddyboss' ),
		value: '',
	} );

	var handleConfirm = function () {
		onConfirm( {
			old_topic_id: topic.topic_id,
			migrate_type: migrateType,
			new_topic_id: 'migrate' === migrateType ? newTopicId : 0,
			nonce: migrateNonce,
		} );
	};

	var isConfirmDisabled = isSaving || ( 'migrate' === migrateType && ! newTopicId );

	return (
		<Modal
			title={
				/* translators: %s: Topic name. */
				wp.i18n.sprintf(
					__( 'Deleting "%s"?', 'buddyboss' ),
					topic.name
				)
			}
			onRequestClose={ onClose }
			className="bb-topic-delete-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-topic-delete-modal__body">
				<p className="bb-topic-delete-modal__warning">
					{ __( 'Deleting this topic will remove it from all posts it is assigned to and cannot be undone. Those posts will have no topic unless you assign a new one using the options below.', 'buddyboss' ) }
				</p>

				<RadioControl
					selected={ migrateType }
					options={ [
						{
							label: __( 'Move posts to another topic', 'buddyboss' ),
							value: 'migrate',
						},
						{
							label: __( 'Delete the topic', 'buddyboss' ),
							value: 'delete',
						},
					] }
					onChange={ setMigrateType }
				/>

				{ 'migrate' === migrateType && (
					<div className="bb-topic-delete-modal__migrate-select">
						<SelectControl
							value={ newTopicId }
							options={ topicOptions }
							onChange={ setNewTopicId }
							__nextHasNoMarginBottom
						/>
					</div>
				) }
			</div>

			<div className="bb-topic-delete-modal__footer">
				<Button
					variant="secondary"
					onClick={ onClose }
					disabled={ isSaving }
				>
					{ __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button
					variant="primary"
					isDestructive
					onClick={ handleConfirm }
					isBusy={ isSaving }
					disabled={ isConfirmDisabled }
				>
					{ __( 'Confirm & Delete', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}
