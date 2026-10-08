/**
 * BuddyBoss Admin Settings 2.0 - Reply Create Modal
 *
 * Uses BB_Admin_Meta_Field_Registry for field rendering via RegisteredMetaField.
 * Handles cascading async selects: Forum → Discussion → Reply-to.
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

import { createReply } from '../../utils/ajax';
import { groupFieldsWithLayout, buildRegisteredFieldPayload, getVisibleFields, isFieldConditionalDisabled, needsSeparator, splitFieldsByMetaboxGroup } from '../../utils/format';
import { RegisteredMetaField } from '../common/RegisteredMetaField';
import { forceRemoveEditor } from '../common/RichTextEditor';

/**
 * Reply Create Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props              Component props.
 * @param {boolean}  props.isOpen       Whether the modal is open.
 * @param {Function} props.onClose      Close handler.
 * @param {Function} props.onCreated    Success handler (receives reply_id).
 * @param {Array}    props.createFields Registered field definitions from server.
 * @returns {JSX.Element|null} Modal component or null.
 */
export function ReplyCreateModal( { isOpen, onClose, onCreated, createFields } ) {
	var registeredValuesState = useState( {} );
	var registeredValues = registeredValuesState[ 0 ];
	var setRegisteredValues = registeredValuesState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	// Key counters to force AsyncSelectField re-mount on cascading changes.
	var cascadeKeyState = useState( 0 );
	var cascadeKey = cascadeKeyState[ 0 ];
	var setCascadeKey = cascadeKeyState[ 1 ];

	var isMountedRef = useRef( true );
	useEffect( function () {
		isMountedRef.current = true;
		return function () {
			isMountedRef.current = false;
		};
	}, [] );

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
	 * Handle change for a registered field with cascading reset.
	 *
	 * When forum_id changes, reset topic_id and reply_to.
	 * When topic_id changes, reset reply_to.
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

			// Cascade: forum_id change resets topic_id and reply_to.
			if ( 'forum_id' === fieldId ) {
				next.topic_id = 0;
				next.reply_to = 0;
				setCascadeKey( function ( k ) { return k + 1; } );
			}

			// Cascade: topic_id change resets reply_to.
			if ( 'topic_id' === fieldId ) {
				next.reply_to = 0;
				setCascadeKey( function ( k ) { return k + 1; } );
			}

			return next;
		} );
	}, [] );

	if ( ! isOpen ) {
		return null;
	}

	/**
	 * Handle reply creation form submission.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCreate = function () {
		var contentVal = registeredValues.content || '';
		if ( ! contentVal.trim() ) {
			// Pull from TinyMCE if available.
			if ( window.tinymce ) {
				var editor = window.tinymce.get( 'bb-admin-edit-content-0' );
				if ( editor ) {
					contentVal = editor.getContent();
				}
			}
		}

		if ( ! contentVal.trim() ) {
			setError( __( 'Description is required.', 'buddyboss-platform' ) );
			return;
		}

		if ( ! registeredValues.topic_id ) {
			setError( __( 'Discussion is required.', 'buddyboss-platform' ) );
			return;
		}

		setIsSaving( true );
		setError( '' );

		// buildRegisteredFieldPayload emits both plain keys and registered_field_* keys automatically.
		var payload = Object.assign(
			buildRegisteredFieldPayload( fields, registeredValues, 0 ),
			{
				content: contentVal, // Override with TinyMCE-pulled value.
			}
		);

		createReply( payload ).then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			if ( response.success ) {
				resetForm();
				if ( onCreated ) {
					onCreated( response.data.reply_id );
				}
			} else {
				setError( ( response.data && response.data.message ) || __( 'Failed to create reply.', 'buddyboss-platform' ) );
			}
		} ).catch( function () {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			setError( __( 'An error occurred. Please try again.', 'buddyboss-platform' ) );
		} );
	};

	var resetForm = function () {
		var initialValues = {};
		if ( createFields && Array.isArray( createFields ) ) {
			createFields.forEach( function ( field ) {
				initialValues[ field.id ] = field.value;
			} );
		}
		setRegisteredValues( initialValues );
		setError( '' );
		setCascadeKey( function ( k ) { return k + 1; } );

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

	var handleClose = function () {
		fields.forEach( function ( field ) {
			if ( 'richtext' === field.type ) {
				forceRemoveEditor( 'bb-admin-edit-' + field.id + '-0' );
			}
		} );
		resetForm();
		onClose();
	};

	/**
	 * Build asyncExtraParams for cascading fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} field Field definition.
	 * @returns {Object} Extra params for async select.
	 */
	var getAsyncExtraParams = function ( field ) {
		if ( ! field.async_depends_on ) {
			return {};
		}

		var dependsValue = registeredValues[ field.async_depends_on ];
		if ( ! dependsValue ) {
			return {};
		}

		var params = {};
		params[ field.async_depends_on ] = dependsValue;
		return params;
	};

	// Render visible fields, split into runs by source metabox so bridged
	// third-party metaboxes (e.g. WP Fusion) render in a bordered section with
	// their title heading at the end — matching the Forums screen.
	var visibleFields = getVisibleFields( fields, registeredValues );
	var segments      = splitFieldsByMetaboxGroup( visibleFields );

	var renderGroupedItem = function ( item, idx, groupedList ) {
		var hasSeparator = needsSeparator( item, groupedList[ idx + 1 ], [ 'reply_to', 'reply_status' ] );

		if ( 'row' === item.type ) {
			return (
				<div key={ 'row-' + idx } className={ 'bb-admin-meta-field__row bb-admin-settings-modal__row' + ( hasSeparator ? ' bb-admin-settings-modal__row--separator' : '' ) }>
					{ item.fields.map( function ( field ) {
						return (
							<RegisteredMetaField
								key={ field.id + '-' + cascadeKey }
								field={ Object.assign( {}, field, { asyncExtraParams: getAsyncExtraParams( field ) } ) }
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
			<div key={ item.field.id + '-' + cascadeKey } className={ hasSeparator ? 'bb-admin-settings-modal__row--separator' : '' }>
				<RegisteredMetaField
					field={ Object.assign( {}, item.field, { asyncExtraParams: getAsyncExtraParams( item.field ) } ) }
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
			title={ __( 'Create New Reply', 'buddyboss-platform' ) }
			onRequestClose={ handleClose }
			className="bb-reply-modal bb-reply-create-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-reply-modal__body bb-admin-settings-modal__body">
				{ error && (
					<p className="bb-admin-settings-modal__error">{ error }</p>
				) }

				{ renderSegments() }
			</div>

			<div className="bb-reply-modal__footer bb-admin-settings-modal__footer">
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
					disabled={ isSaving || ! registeredValues.topic_id }
				>
					{ __( 'Save', 'buddyboss-platform' ) }
				</Button>
			</div>
		</Modal>
	);
}
