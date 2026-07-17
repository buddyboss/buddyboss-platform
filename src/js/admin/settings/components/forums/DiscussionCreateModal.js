/**
 * BuddyBoss Admin Settings 2.0 - Discussion Create Modal
 *
 * Uses BB_Admin_Meta_Field_Registry for field rendering via RegisteredMetaField.
 * Tags (Optional) remains a custom section (TagsAutocomplete component).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect, useCallback, Fragment } from '@wordpress/element';
import {
	Modal,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { createDiscussion } from '../../utils/ajax';
import { groupFieldsWithLayout, buildRegisteredFieldPayload, getVisibleFields, isFieldConditionalDisabled, needsSeparator, splitFieldsByMetaboxGroup } from '../../utils/format';
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
			setError( __( 'Discussion title is required.', 'buddyboss-platform' ) );
			return;
		}

		var forumIdVal = registeredValues.forum_id || 0;
		if ( ! forumIdVal ) {
			setError( __( 'Forum is required.', 'buddyboss-platform' ) );
			return;
		}

		setIsSaving( true );
		setError( '' );

		// buildRegisteredFieldPayload emits both plain keys and registered_field_* keys
		// automatically — no manual field list needed.
		var payload = Object.assign(
			buildRegisteredFieldPayload( fields, registeredValues, 0 ),
			{
				title: titleVal.trim(), // Override with trimmed value.
				tags: tags,             // Custom section, not in registry.
			}
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
				setError( ( response.data && response.data.message ) || __( 'Failed to create discussion.', 'buddyboss-platform' ) );
			}
		} ).catch( function () {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			setError( __( 'An error occurred. Please try again.', 'buddyboss-platform' ) );
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

	// Render visible fields (respects client-side conditionals like publish_mode → date/time),
	// split into runs by source metabox so bridged third-party metaboxes (e.g. WP Fusion)
	// render in a bordered section with their title heading at the end — matching Forums.
	var visibleFields = getVisibleFields( fields, registeredValues );
	var segments      = splitFieldsByMetaboxGroup( visibleFields );

	var renderGroupedItem = function ( item, idx, groupedList ) {
		var hasSeparator = needsSeparator( item, groupedList[ idx + 1 ] );

		if ( 'row' === item.type ) {
			return (
				<div key={ 'row-' + idx } className={ 'bb-admin-meta-field__row bb-admin-settings-modal__row' + ( hasSeparator ? ' bb-admin-settings-modal__row--separator' : '' ) }>
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
								disabled={ isFieldConditionalDisabled( field, registeredValues ) }
							/>
						);
					} ) }
				</div>
			);
		}
		return (
			<div key={ item.field.id } className={ 'components-base-control ' + ( hasSeparator ? 'bb-admin-settings-modal__row--separator' : '' ) }>
				<RegisteredMetaField
					field={ item.field }
					value={ registeredValues[ item.field.id ] }
					onChange={ function ( val ) {
						handleFieldChange( item.field.id, val );
					} }
					itemId={ 0 }
					disabled={ isFieldConditionalDisabled( item.field, registeredValues ) }
				/>
			</div>
		);
	};

	var renderSegments = function () {
		return segments.map( function ( segment, segIdx ) {
			var grouped = groupFieldsWithLayout( segment.fields );

			if ( ! segment.group ) {
				return (
					<Fragment key={ 'seg-flat-' + segIdx }>
						{ grouped.map( function ( item, idx ) {
							return renderGroupedItem( item, idx, grouped );
						} ) }
					</Fragment>
				);
			}

			return (
				<div key={ 'seg-group-' + segIdx } className="bb-admin-meta-field__group" data-group-id={ segment.group }>
					{ segment.label && (
						<h3 className="bb-admin-meta-field__group-title">{ segment.label }</h3>
					) }
					<div className="bb-admin-meta-field__group-fields">
						{ grouped.map( function ( item, idx ) {
							return renderGroupedItem( item, idx, grouped );
						} ) }
					</div>
				</div>
			);
		} );
	};

	return (
		<Modal
			title={ __( 'Start New Discussion', 'buddyboss-platform' ) }
			onRequestClose={ handleClose }
			className="bb-discussion-modal bb-discussion-create-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-discussion-modal__body bb-admin-settings-modal__body">
				{ error && (
					<p className="bb-admin-settings-modal__error">{ error }</p>
				) }

				{ renderSegments() }

				<div className="bb-admin-settings-modal__custom-section">
				<TagsAutocomplete
					label={ __( 'Tags (Optional)', 'buddyboss-platform' ) }
					value={ tags }
					onChange={ setTags }
					placeholder={ __( 'Enter tags, separated by commas', 'buddyboss-platform' ) }
				/>
				</div>
			</div>

			<div className="bb-discussion-modal__footer bb-admin-settings-modal__footer">
				<Button
					variant="secondary"
					onClick={ handleClose }
					disabled={ isSaving }
				>
					{ __( 'Cancel', 'buddyboss-platform' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ handleCreate }
					isBusy={ isSaving }
					disabled={ isSaving || ! ( registeredValues.title || '' ).trim() || ! registeredValues.forum_id }
				>
					{ __( 'Save', 'buddyboss-platform' ) }
				</Button>
			</div>
		</Modal>
	);
}
