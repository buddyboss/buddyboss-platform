/**
 * BuddyBoss Admin Settings 2.0 - Discussion Create Modal
 *
 * Uses BB_Admin_Meta_Field_Registry for field rendering via RegisteredMetaField.
 * Tags (Optional) remains a custom section (TagsAutocomplete component).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import {
	Modal,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { createDiscussion } from '../../utils/ajax';
import { groupFieldsWithLayout, buildRegisteredFieldPayload } from '../../utils/format';
import { RegisteredMetaField } from '../common/RegisteredMetaField';
import { forceRemoveEditor } from '../common/RichTextEditor';
import { TagsAutocomplete } from './TagsAutocomplete';

/**
 * Discussion Create Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props              Component props.
 * @param {boolean}  props.isOpen       Whether the modal is open.
 * @param {Function} props.onClose      Close handler.
 * @param {Function} props.onCreated    Success handler (receives topic_id).
 * @param {Array}    props.createFields Registered field definitions from server.
 * @returns {JSX.Element|null} Modal component or null.
 */
export function DiscussionCreateModal( { isOpen, onClose, onCreated, createFields } ) {
	// All registered field values keyed by field ID.
	var registeredValuesState = useState( {} );
	var registeredValues = registeredValuesState[ 0 ];
	var setRegisteredValues = registeredValuesState[ 1 ];

	// Tags state (custom, not in registry).
	var tagsState = useState( '' );
	var tags = tagsState[ 0 ];
	var setTags = tagsState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	// Track mounted state.
	var isMountedRef = useRef( true );
	useEffect( function () {
		isMountedRef.current = true;
		return function () {
			isMountedRef.current = false;
		};
	}, [] );

	// Initialize registered values from create field defaults when modal opens.
	useEffect( function () {
		if ( isOpen && createFields && Array.isArray( createFields ) ) {
			var initialValues = {};
			createFields.forEach( function ( field ) {
				initialValues[ field.id ] = field.value;
			} );
			setRegisteredValues( initialValues );
		}
	}, [ isOpen, createFields ] );

	var fields = createFields && Array.isArray( createFields ) ? createFields : [];

	/**
	 * Handle change for a registered field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} fieldId Field ID.
	 * @param {*}      val     New value.
	 */
	var handleFieldChange = useCallback( function ( fieldId, val ) {
		setRegisteredValues( function ( prev ) {
			var next = {};
			Object.keys( prev ).forEach( function ( k ) {
				next[ k ] = prev[ k ];
			} );
			next[ fieldId ] = val;
			return next;
		} );
	}, [] );

	if ( ! isOpen ) {
		return null;
	}

	/**
	 * Handle discussion creation form submission.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCreate = function () {
		var titleVal = registeredValues.title || '';
		if ( ! titleVal.trim() ) {
			setError( __( 'Discussion title is required.', 'buddyboss' ) );
			return;
		}

		var forumIdVal = registeredValues.forum_id || 0;
		if ( ! forumIdVal ) {
			setError( __( 'Forum is required.', 'buddyboss' ) );
			return;
		}

		setIsSaving( true );
		setError( '' );

		var payload = Object.assign(
			{
				title: titleVal.trim(),
				description: registeredValues.description || '',
				forum_id: forumIdVal,
				type: registeredValues.type || 'normal',
				topic_status: registeredValues.topic_status || 'open',
				visibility: registeredValues.visibility || 'publish',
				tags: tags,
			},
			buildRegisteredFieldPayload( fields, registeredValues, 0 )
		);

		createDiscussion( payload ).then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			if ( response.success ) {
				resetForm();
				if ( onCreated ) {
					onCreated( response.data.topic_id );
				}
			} else {
				setError( ( response.data && response.data.message ) || __( 'Failed to create discussion.', 'buddyboss' ) );
			}
		} ).catch( function () {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			setError( __( 'An error occurred. Please try again.', 'buddyboss' ) );
		} );
	};

	/**
	 * Reset all form fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var resetForm = function () {
		var initialValues = {};
		if ( createFields && Array.isArray( createFields ) ) {
			createFields.forEach( function ( field ) {
				initialValues[ field.id ] = field.value;
			} );
		}
		setRegisteredValues( initialValues );
		setTags( '' );
		setError( '' );

		if ( window.tinymce ) {
			fields.forEach( function ( field ) {
				if ( 'richtext' === field.type ) {
					var editor = window.tinymce.get( 'bb-admin-edit-' + field.id + '-0' );
					if ( editor ) {
						editor.setContent( '' );
					}
				}
			} );
		}
	};

	/**
	 * Handle modal close and reset form state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleClose = function () {
		fields.forEach( function ( field ) {
			if ( 'richtext' === field.type ) {
				forceRemoveEditor( 'bb-admin-edit-' + field.id + '-0' );
			}
		} );
		resetForm();
		onClose();
	};

	// Render visible fields.
	var visibleFields = fields.filter( function ( field ) {
		return field.visible;
	} );

	var grouped = groupFieldsWithLayout( visibleFields );

	return (
		<Modal
			title={ __( 'Start New Discussion', 'buddyboss' ) }
			onRequestClose={ handleClose }
			className="bb-discussion-modal bb-discussion-create-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-discussion-modal__body bb-admin-settings-modal__body">
				{ error && (
					<p className="bb-admin-settings-modal__error">{ error }</p>
				) }

				{ grouped.map( function ( item, idx ) {
					var nextIsRow = grouped[ idx + 1 ] && 'row' === grouped[ idx + 1 ].type;

					if ( 'row' === item.type ) {
						return (
							<div key={ 'row-' + idx } className="bb-admin-meta-field__row bb-admin-settings-modal__row bb-admin-settings-modal__row--separator">
								{ item.fields.map( function ( field ) {
									return (
										<RegisteredMetaField
											key={ field.id }
											field={ field }
											value={ registeredValues[ field.id ] }
											onChange={ function ( val ) {
												handleFieldChange( field.id, val );
											} }
											itemId={ 0 }
										/>
									);
								} ) }
							</div>
						);
					}
					return (
						<div key={ item.field.id } className={ 'components-base-control ' + ( 'richtext' === item.field.type || nextIsRow ? 'bb-admin-settings-modal__row--separator' : '') }>
							<RegisteredMetaField
								field={ item.field }
								value={ registeredValues[ item.field.id ] }
								onChange={ function ( val ) {
									handleFieldChange( item.field.id, val );
								} }
								itemId={ 0 }
							/>
						</div>
					);
				} ) }

				<TagsAutocomplete
					label={ __( 'Tags (Optional)', 'buddyboss' ) }
					value={ tags }
					onChange={ setTags }
					placeholder={ __( 'Enter tags, separated by commas', 'buddyboss' ) }
				/>
			</div>

			<div className="bb-discussion-modal__footer bb-admin-settings-modal__footer">
				<Button
					variant="secondary"
					onClick={ handleClose }
					disabled={ isSaving }
				>
					{ __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ handleCreate }
					isBusy={ isSaving }
					disabled={ isSaving || ! ( registeredValues.title || '' ).trim() || ! registeredValues.forum_id }
				>
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}
