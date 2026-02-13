/**
 * BuddyBoss Admin Settings 2.0 - Topic List Field Component
 *
 * Main component rendered by SettingsForm.js for the 'topic_list' field type.
 * Manages topic CRUD operations via existing AJAX endpoints.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useCallback, useRef } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { TopicItem } from './TopicItem';
import { TopicModal } from './TopicModal';
import { TopicDeleteModal } from './TopicDeleteModal';

/**
 * Make an AJAX request to the WordPress admin.
 *
 * @param {string} action AJAX action name.
 * @param {Object} data   Request data.
 * @returns {Promise<Object>} Response data.
 */
function ajaxRequest( action, data ) {
	var ajaxUrl = window.ajaxurl || ( window.bbAdminData && window.bbAdminData.ajaxUrl ) || '/wp-admin/admin-ajax.php';
	var formData = new FormData();

	formData.append( 'action', action );
	Object.keys( data ).forEach( function ( key ) {
		var val = data[ key ];
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
	} ).then( function ( response ) {
		return response.json();
	} );
}

/**
 * Topic List Field Component
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field configuration from PHP.
 * @param {*}        props.value    Current field value.
 * @param {Object}   props.values   All current field values.
 * @param {Function} props.onChange Change handler.
 * @returns {JSX.Element} Topic list component.
 */
export function TopicListField( { field, value, values, onChange } ) {
	// Initialize topics from field data.
	var topicsState = useState( function () {
		return field.topics_data || [];
	} );
	var topics = topicsState[ 0 ];
	var setTopics = topicsState[ 1 ];

	var nonces = field.nonces || {};
	var topicsLimit = field.topics_limit || 20;

	// Modal states.
	var addModalState = useState( false );
	var isAddModalOpen = addModalState[ 0 ];
	var setIsAddModalOpen = addModalState[ 1 ];

	var editModalState = useState( false );
	var isEditModalOpen = editModalState[ 0 ];
	var setIsEditModalOpen = editModalState[ 1 ];

	var deleteModalState = useState( false );
	var isDeleteModalOpen = deleteModalState[ 0 ];
	var setIsDeleteModalOpen = deleteModalState[ 1 ];

	var editingTopicState = useState( null );
	var editingTopic = editingTopicState[ 0 ];
	var setEditingTopic = editingTopicState[ 1 ];

	var deletingTopicState = useState( null );
	var deletingTopic = deletingTopicState[ 0 ];
	var setDeletingTopic = deletingTopicState[ 1 ];

	var availableTopicsState = useState( [] );
	var availableTopics = availableTopicsState[ 0 ];
	var setAvailableTopics = availableTopicsState[ 1 ];

	var migrateNonceState = useState( '' );
	var migrateNonce = migrateNonceState[ 0 ];
	var setMigrateNonce = migrateNonceState[ 1 ];

	var savingState = useState( false );
	var isSaving = savingState[ 0 ];
	var setIsSaving = savingState[ 1 ];

	// Drag-and-drop state.
	var dragIndexState = useState( null );
	var dragIndex = dragIndexState[ 0 ];
	var setDragIndex = dragIndexState[ 1 ];

	var dragOverIndexState = useState( null );
	var dragOverIndex = dragOverIndexState[ 0 ];
	var setDragOverIndex = dragOverIndexState[ 1 ];

	/**
	 * Handle adding a new topic.
	 */
	var handleAddTopic = useCallback( function ( data ) {
		setIsSaving( true );
		ajaxRequest( 'bb_add_topic', {
			name: data.name,
			permission_type: data.permission_type,
			item_type: 'activity',
			item_id: 0,
			nonce: nonces.add || '',
		} ).then( function ( response ) {
			setIsSaving( false );
			if ( response.success && response.data && response.data.content && response.data.content.topic ) {
				var newTopic = response.data.content.topic;
				var updatedTopics = topics.concat( [ newTopic ] );
				setTopics( updatedTopics );
				onChange( field.name, updatedTopics );
				setIsAddModalOpen( false );
			}
		} ).catch( function () {
			setIsSaving( false );
		} );
	}, [ topics, nonces, field.name, onChange ] );

	/**
	 * Handle editing a topic.
	 */
	var handleEditTopic = useCallback( function ( data ) {
		setIsSaving( true );
		ajaxRequest( 'bb_add_topic', {
			topic_id: data.topic_id,
			name: data.name,
			permission_type: data.permission_type,
			item_type: 'activity',
			item_id: 0,
			nonce: nonces.add || '',
		} ).then( function ( response ) {
			setIsSaving( false );
			if ( response.success && response.data && response.data.content && response.data.content.topic ) {
				var updatedTopic = response.data.content.topic;
				// Compare using the original topic_id sent in the request,
				// because editing a topic name may create a new topic_id.
				var originalTopicId = String( data.topic_id );
				var updatedTopics = topics.map( function ( t ) {
					if ( String( t.topic_id ) === originalTopicId ) {
						return updatedTopic;
					}
					return t;
				} );
				setTopics( updatedTopics );
				onChange( field.name, updatedTopics );
				setIsEditModalOpen( false );
				setEditingTopic( null );
			}
		} ).catch( function () {
			setIsSaving( false );
		} );
	}, [ topics, nonces, field.name, onChange ] );

	/**
	 * Handle initiating delete (step 1: fetch available topics for migration).
	 */
	var handleInitiateDelete = useCallback( function ( topic ) {
		setDeletingTopic( topic );
		setIsSaving( true );
		ajaxRequest( 'bb_delete_topic', {
			topic_id: topic.topic_id,
			item_type: 'activity',
			item_id: 0,
			nonce: nonces.delete || '',
		} ).then( function ( response ) {
			setIsSaving( false );
			if ( response.success && response.data ) {
				setAvailableTopics( response.data.topic_lists || [] );
				setMigrateNonce( response.data.nonce || '' );
				setIsDeleteModalOpen( true );
			}
		} ).catch( function () {
			setIsSaving( false );
		} );
	}, [ nonces ] );

	/**
	 * Handle confirming delete (step 2: migrate or delete).
	 */
	var handleConfirmDelete = useCallback( function ( data ) {
		setIsSaving( true );
		ajaxRequest( 'bb_migrate_topic', {
			old_topic_id: data.old_topic_id,
			migrate_type: data.migrate_type,
			new_topic_id: data.new_topic_id || 0,
			item_type: 'activity',
			item_id: 0,
			nonce: data.nonce,
		} ).then( function ( response ) {
			setIsSaving( false );
			if ( response.success ) {
				var updatedTopics = topics.filter( function ( t ) {
					return String( t.topic_id ) !== String( data.old_topic_id );
				} );
				setTopics( updatedTopics );
				onChange( field.name, updatedTopics );
				setIsDeleteModalOpen( false );
				setDeletingTopic( null );
			}
		} ).catch( function () {
			setIsSaving( false );
		} );
	}, [ topics, field.name, onChange ] );

	/**
	 * Handle drag-and-drop reorder.
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
			var reordered = topics.slice();
			var draggedItem = reordered.splice( dragIndex, 1 )[ 0 ];
			reordered.splice( dragOverIndex, 0, draggedItem );

			setTopics( reordered );
			onChange( field.name, reordered );

			// Persist order via AJAX.
			var topicIds = reordered.map( function ( t ) {
				return t.topic_id;
			} );
			ajaxRequest( 'bb_update_topics_order', {
				topic_ids: topicIds,
				nonce: nonces.order || '',
			} );
		}
		setDragIndex( null );
		setDragOverIndex( null );
	};

	var handleDrop = function () {
		handleDragEnd();
	};

	var canAddMore = topics.length < topicsLimit;

	return (
		<div className="bb-topic-list">
			<div className="bb-topic-list__items">
				{ topics.map( function ( topic, index ) {
					return (
						<div
							key={ topic.topic_id || topic.id || index }
							draggable="true"
							onDragStart={ handleDragStart( index ) }
							onDragOver={ handleDragOver( index ) }
							onDrop={ handleDrop }
							onDragEnd={ handleDragEnd }
						>
							<TopicItem
								topic={ topic }
								onEdit={ function ( t ) {
									setEditingTopic( t );
									setIsEditModalOpen( true );
								} }
								onDelete={ handleInitiateDelete }
								dragHandleProps={ {
									onMouseDown: function ( e ) {
										// Allow drag to start from handle.
										e.stopPropagation();
									},
								} }
								isDragging={ dragIndex === index }
								isDragOver={ dragOverIndex === index }
							/>
						</div>
					);
				} ) }
			</div>

			{ canAddMore && (
				<Button
					variant="secondary"
					className="bb-topic-list__add-btn"
					onClick={ function () {
						setIsAddModalOpen( true );
					} }
				>
					{ __( '+ Add New Topic', 'buddyboss' ) }
				</Button>
			) }

			{ field.description && (
				<p className="bb-topic-list__description">{ field.description }</p>
			) }

			<TopicModal
				isOpen={ isAddModalOpen }
				onClose={ function () {
					setIsAddModalOpen( false );
				} }
				onSave={ handleAddTopic }
				topic={ null }
				isSaving={ isSaving }
			/>

			<TopicModal
				isOpen={ isEditModalOpen }
				onClose={ function () {
					setIsEditModalOpen( false );
					setEditingTopic( null );
				} }
				onSave={ handleEditTopic }
				topic={ editingTopic }
				isSaving={ isSaving }
			/>

			<TopicDeleteModal
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
