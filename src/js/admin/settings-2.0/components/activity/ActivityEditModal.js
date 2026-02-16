/**
 * BuddyBoss Admin Settings 2.0 - Activity Edit Modal
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import {
	Modal,
	TextControl,
	SelectControl,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Rich Text Editor wrapper for TinyMCE.
 *
 * @param {Object}   props          Component props.
 * @param {string}   props.id       Editor ID.
 * @param {string}   props.label    Field label.
 * @param {string}   props.value    Current value.
 * @param {Function} props.onChange  Change handler.
 * @returns {JSX.Element} Rich text editor.
 */
function RichTextEditor( { id, label, value, onChange } ) {
	var containerRef = useRef( null );
	var editorInitialized = useRef( false );

	// Initialize TinyMCE on mount.
	useEffect( function () {
		if ( window.wp && window.wp.editor && ! editorInitialized.current ) {
			// Small delay to ensure the textarea DOM element is ready.
			var timer = setTimeout( function () {
				var textarea = document.getElementById( id );
				if ( textarea ) {
					window.wp.editor.initialize( id, {
						tinymce: {
							wpautop: true,
							toolbar1: 'bold,italic,bullist,numlist,blockquote,link,unlink,code',
							toolbar2: '',
							height: 150,
							setup: function ( editor ) {
								editor.on( 'change keyup', function () {
									onChange( editor.getContent() );
								} );
							},
						},
						quicktags: {
							buttons: 'strong,em,link,block,del,ins,code',
						},
						mediaButtons: false,
					} );
					editorInitialized.current = true;
				}
			}, 100 );

			return function () {
				clearTimeout( timer );
			};
		}
	}, [ id ] );

	// Cleanup on unmount.
	useEffect( function () {
		return function () {
			if ( window.wp && window.wp.editor && editorInitialized.current ) {
				window.wp.editor.remove( id );
				editorInitialized.current = false;
			}
		};
	}, [ id ] );

	return (
		<div className="bb-activity-edit-modal__editor-field" ref={ containerRef }>
			<label className="bb-activity-edit-modal__label" htmlFor={ id }>
				{ label }
			</label>
			<div className="bb-activity-edit-modal__editor-wrapper">
				<textarea
					id={ id }
					defaultValue={ value }
					rows={ 6 }
					className="bb-activity-edit-modal__textarea"
				/>
			</div>
		</div>
	);
}

/**
 * Activity Edit Modal Component
 *
 * @param {Object}   props                  Component props.
 * @param {boolean}  props.isOpen           Whether the modal is open.
 * @param {Object}   props.activity         Activity object to edit.
 * @param {Object}   props.activityActions  Available activity types.
 * @param {Array}    props.topics           Available topics list.
 * @param {Function} props.onClose          Close handler.
 * @param {Function} props.onSave           Save handler.
 * @param {boolean}  props.isSaving         Whether save is in progress.
 * @returns {JSX.Element|null} Modal component or null.
 */
export function ActivityEditModal( { isOpen, activity, activityActions, topics, onClose, onSave, isSaving } ) {
	var actionState = useState( '' );
	var actionText = actionState[ 0 ];
	var setActionText = actionState[ 1 ];

	var contentState = useState( '' );
	var content = contentState[ 0 ];
	var setContent = contentState[ 1 ];

	var titleState = useState( '' );
	var postTitle = titleState[ 0 ];
	var setPostTitle = titleState[ 1 ];

	var typeState = useState( '' );
	var type = typeState[ 0 ];
	var setType = typeState[ 1 ];

	var linkState = useState( '' );
	var primaryLink = linkState[ 0 ];
	var setPrimaryLink = linkState[ 1 ];

	var userIdState = useState( '' );
	var userId = userIdState[ 0 ];
	var setUserId = userIdState[ 1 ];

	var itemIdState = useState( '' );
	var itemId = itemIdState[ 0 ];
	var setItemId = itemIdState[ 1 ];

	var secondaryItemIdState = useState( '' );
	var secondaryItemId = secondaryItemIdState[ 0 ];
	var setSecondaryItemId = secondaryItemIdState[ 1 ];

	var topicIdState = useState( '' );
	var topicId = topicIdState[ 0 ];
	var setTopicId = topicIdState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	// Reset form when activity changes.
	useEffect( function () {
		if ( isOpen && activity ) {
			setActionText( activity.action || '' );
			setContent( activity.content || '' );
			setPostTitle( activity.post_title || '' );
			setType( activity.type || '' );
			setPrimaryLink( activity.primary_link || '' );
			// Use nullish coalescing style to preserve 0 values (0 is valid, not empty).
			setUserId( String( null != activity.user_id ? activity.user_id : '' ) );
			setItemId( String( null != activity.item_id ? activity.item_id : '' ) );
			setSecondaryItemId( String( null != activity.secondary_item_id ? activity.secondary_item_id : '' ) );
			setTopicId( activity.topic && activity.topic.id ? String( activity.topic.id ) : '' );
			setError( '' );
		}
	}, [ isOpen, activity ] );

	if ( ! isOpen || ! activity ) {
		return null;
	}

	// Build type options.
	var typeOptions = [ { label: __( 'Select type', 'buddyboss' ), value: '' } ];
	if ( activityActions ) {
		Object.keys( activityActions ).forEach( function ( key ) {
			typeOptions.push( { label: activityActions[ key ], value: key } );
		} );
	}

	// Build topic options (topics use topic_id as value, same as legacy metabox).
	var topicOptions = [ { label: __( '--- Select a topic ---', 'buddyboss' ), value: '' } ];
	if ( topics && Array.isArray( topics ) ) {
		topics.forEach( function ( topic ) {
			var tid = topic.topic_id || topic.id;
			topicOptions.push( { label: topic.name, value: String( tid ) } );
		} );
	}
	var hasTopics = topics && Array.isArray( topics ) && topics.length > 0;

	var handleSave = function () {
		setError( '' );

		// Get content from TinyMCE editors if available.
		var finalAction = actionText;
		var finalContent = content;

		if ( window.wp && window.wp.editor ) {
			var actionEditor = window.tinymce && window.tinymce.get( 'bb-activity-edit-action' );
			if ( actionEditor ) {
				finalAction = actionEditor.getContent();
			}

			var contentEditor = window.tinymce && window.tinymce.get( 'bb-activity-edit-content' );
			if ( contentEditor ) {
				finalContent = contentEditor.getContent();
			}
		}

		onSave( {
			activity_id: activity.id,
			action_text: finalAction,
			content: finalContent,
			post_title: postTitle,
			type: type,
			primary_link: primaryLink,
			user_id: userId,
			item_id: itemId,
			secondary_item_id: secondaryItemId,
			activity_topic: topicId,
		} );
	};

	return (
		<Modal
			title={ __( 'Edit Activity', 'buddyboss' ) + ' #' + activity.id }
			onRequestClose={ onClose }
			className="bb-activity-edit-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-activity-edit-modal__body">
				{ error && (
					<p className="bb-activity-edit-modal__error">{ error }</p>
				) }

				<RichTextEditor
					key={ 'action-' + activity.id }
					id="bb-activity-edit-action"
					label={ __( 'Action', 'buddyboss' ) }
					value={ activity.action || '' }
					onChange={ setActionText }
				/>

				<RichTextEditor
					key={ 'content-' + activity.id }
					id="bb-activity-edit-content"
					label={ __( 'Content', 'buddyboss' ) }
					value={ activity.content || '' }
					onChange={ setContent }
				/>

				<TextControl
					label={ __( 'Title', 'buddyboss' ) }
					value={ postTitle }
					onChange={ setPostTitle }
					__nextHasNoMarginBottom
				/>

				<SelectControl
					label={ __( 'Type', 'buddyboss' ) }
					value={ type }
					options={ typeOptions }
					onChange={ setType }
					__nextHasNoMarginBottom
				/>

				{ hasTopics && (
					<SelectControl
						label={ __( 'Topic', 'buddyboss' ) }
						value={ topicId }
						options={ topicOptions }
						onChange={ setTopicId }
						__nextHasNoMarginBottom
					/>
				) }

				<TextControl
					label={ __( 'Link', 'buddyboss' ) }
					value={ primaryLink }
					onChange={ setPrimaryLink }
					type="url"
					__nextHasNoMarginBottom
				/>

				<TextControl
					label={ __( 'Author ID', 'buddyboss' ) }
					value={ userId }
					onChange={ setUserId }
					type="number"
					__nextHasNoMarginBottom
				/>

				<div className="bb-activity-edit-modal__row">
					<TextControl
						label={ __( 'Primary Item ID', 'buddyboss' ) }
						value={ itemId }
						onChange={ setItemId }
						type="number"
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __( 'Secondary Item ID', 'buddyboss' ) }
						value={ secondaryItemId }
						onChange={ setSecondaryItemId }
						type="number"
						__nextHasNoMarginBottom
					/>
				</div>
			</div>

			<div className="bb-activity-edit-modal__footer bb-admin-settings-modal__footer">
				<div className="bb-activity-edit-modal__footer-left">
					{ activity.primary_link && (
						<a
							href={ activity.primary_link }
							target="_blank"
							rel="noopener noreferrer"
							className="bb-activity-edit-modal__view-link"
						>
							{ __( 'View Activity', 'buddyboss' ) }
							<i className="bb-icons-rl bb-icons-rl-external-link" style={ { marginLeft: '4px', fontSize: '14px' } }></i>
						</a>
					) }
				</div>
				<div className="bb-activity-edit-modal__footer-right">
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
						{ __( 'Save', 'buddyboss' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
}
