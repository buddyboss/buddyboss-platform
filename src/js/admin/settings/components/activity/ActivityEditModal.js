/**
 * BuddyBoss Admin Settings 2.0 - Activity Edit Modal
 *
 * Fully dynamic — all fields come from the PHP registry.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, Fragment } from '@wordpress/element';
import { splitFieldsByMetaboxGroup } from '../../utils/format';
import {
	Modal,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { RegisteredMetaField } from '../common/RegisteredMetaField';
import { safeUrl } from '../../utils/sanitize';

/**
 * Group consecutive fields with layout='half' into row wrappers.
 *
 * Returns an array of items where each item is either:
 * - A single field object (layout !== 'half')
 * - An array of field objects (consecutive half-layout fields, wrapped in a row)
 *
 * @param {Array} fields Array of field objects.
 * @returns {Array} Grouped items.
 */
function groupFieldsWithLayout( fields ) {
	var result = [];
	var halfBuffer = [];

	var flushHalf = function () {
		if ( halfBuffer.length > 0 ) {
			result.push( { type: 'row', fields: halfBuffer } );
			halfBuffer = [];
		}
	};

	fields.forEach( function ( field ) {
		if ( 'half' === field.layout ) {
			halfBuffer.push( field );
		} else {
			flushHalf();
			result.push( { type: 'single', field: field } );
		}
	} );

	flushHalf();

	return result;
}

/**
 * Activity Edit Modal Component
 *
 * @param {Object}   props                  Component props.
 * @param {boolean}  props.isOpen           Whether the modal is open.
 * @param {Object}   props.activity         Activity object to edit.
 * @param {Object}   props.activityActions  Available activity types (kept for backward compat).
 * @param {Function} props.onClose          Close handler.
 * @param {Function} props.onSave           Save handler.
 * @param {boolean}  props.isSaving         Whether save is in progress.
 * @returns {JSX.Element|null} Modal component or null.
 */
export function ActivityEditModal( { isOpen, activity, activityActions, onClose, onSave, isSaving } ) {
	// All field values keyed by field ID.
	var registeredValuesState = useState( {} );
	var registeredValues = registeredValuesState[ 0 ];
	var setRegisteredValues = registeredValuesState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	// Reset form when activity changes.
	useEffect( function () {
		if ( isOpen && activity ) {
			var initialValues = {};
			if ( activity.registered_fields && Array.isArray( activity.registered_fields ) ) {
				activity.registered_fields.forEach( function ( field ) {
					initialValues[ field.id ] = field.value;
				} );
			}
			setRegisteredValues( initialValues );
			setError( '' );
		}
	}, [ isOpen, activity ] );

	if ( ! isOpen || ! activity ) {
		return null;
	}

	// Separate registered fields by context.
	var normalFields = [];
	var afterFields = [];
	if ( activity.registered_fields && Array.isArray( activity.registered_fields ) ) {
		activity.registered_fields.forEach( function ( field ) {
			if ( 'after' === field.context ) {
				afterFields.push( field );
			} else {
				normalFields.push( field );
			}
		} );
	}

	// Group normal fields for half-layout rows.
	var groupedNormalFields = groupFieldsWithLayout( normalFields );
	// "After" fields are where bridged third-party metaboxes live. Split them
	// into runs by source metabox so each renders inside a bordered section
	// headed by its title — parity with the Forums/Groups modals.
	var afterSegments = splitFieldsByMetaboxGroup( afterFields );

	/**
	 * Handle change for a registered field.
	 *
	 * @param {string} fieldId Field ID.
	 * @param {*}      val     New value.
	 */
	var handleRegisteredFieldChange = function ( fieldId, val ) {
		setRegisteredValues( function ( prev ) {
			var next = {};
			Object.keys( prev ).forEach( function ( k ) {
				next[ k ] = prev[ k ];
			} );
			next[ fieldId ] = val;
			return next;
		} );
	};

	/**
	 * Render a single field with its change handler.
	 *
	 * @param {Object} field Field data.
	 * @returns {JSX.Element} Rendered field.
	 */
	var renderField = function ( field ) {
		return (
			<RegisteredMetaField
				key={ field.id + '-' + activity.id }
				field={ field }
				value={ registeredValues[ field.id ] }
				onChange={ function ( val ) {
					handleRegisteredFieldChange( field.id, val );
				} }
				activityId={ activity.id }
			/>
		);
	};

	/**
	 * Render grouped items (single fields and row-wrapped half fields).
	 *
	 * @param {Array} grouped Grouped field items.
	 * @returns {Array} Rendered elements.
	 */
	var renderGroupedFields = function ( grouped ) {
		return grouped.map( function ( item, idx ) {
			if ( 'row' === item.type ) {
				// Collect description from any half field in the row.
				var rowDescription = '';
				item.fields.forEach( function ( f ) {
					if ( f.description ) {
						rowDescription = f.description;
					}
				} );
				return (
					<div key={ 'row-' + idx }>
						<div className="bb-admin-meta-field__row">
							{ item.fields.map( renderField ) }
						</div>
						{ rowDescription && (
							<p className="bb-admin-meta-field__description">{ rowDescription }</p>
						) }
					</div>
				);
			}
			return renderField( item.field );
		} );
	};

	/**
	 * Render the "after" fields split into runs by source metabox. Ungrouped
	 * fields render flat; a grouped run renders inside a bordered section with
	 * the metabox title as a heading.
	 *
	 * @param {Array} segments Segments from splitFieldsByMetaboxGroup().
	 * @returns {Array} Rendered elements.
	 */
	var renderAfterSegments = function ( segments ) {
		return segments.map( function ( segment, segIdx ) {
			var grouped = groupFieldsWithLayout( segment.fields );

			if ( ! segment.group ) {
				return (
					<Fragment key={ 'seg-flat-' + segIdx }>
						{ renderGroupedFields( grouped ) }
					</Fragment>
				);
			}

			return (
				<div key={ 'seg-group-' + segIdx } className="bb-admin-meta-field__group" data-group-id={ segment.group }>
					{ segment.label && (
						<h3 className="bb-admin-meta-field__group-title">{ segment.label }</h3>
					) }
					<div className="bb-admin-meta-field__group-fields">
						{ renderGroupedFields( grouped ) }
					</div>
				</div>
			);
		} );
	};

	var handleSave = function () {
		setError( '' );

		var payload = {
			activity_id: activity.id,
		};

		// Build payload from all registered fields.
		if ( activity.registered_fields && Array.isArray( activity.registered_fields ) ) {
			activity.registered_fields.forEach( function ( field ) {
				if ( field.readonly ) {
					return;
				}

				var val = registeredValues[ field.id ];

				// For richtext fields, pull latest content from TinyMCE.
				if ( 'richtext' === field.type && window.tinymce ) {
					var editorInstance = window.tinymce.get( 'bb-admin-edit-' + field.id + '-' + activity.id );
					if ( editorInstance ) {
						val = editorInstance.getContent();
					}
				}

				payload[ 'registered_field_' + field.id ] = null != val ? val : '';
			} );
		}

		onSave( payload );
	};

	return (
		<Modal
			title={ __( 'Edit Activity', 'buddyboss' ) + ' #' + activity.id }
			onRequestClose={ onClose }
			className="bb-activity-edit-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-activity-edit-modal__body bb-admin-settings-modal__body">
				{ error && (
					<p className="bb-activity-edit-modal__error" role="alert">{ error }</p>
				) }

				{ renderGroupedFields( groupedNormalFields ) }

				{ renderAfterSegments( afterSegments ) }
			</div>

			<div className="bb-activity-edit-modal__footer bb-admin-settings-modal__footer">
				<div className="bb-activity-edit-modal__footer-left">
					<a
						href={ safeUrl( activity.permalink ) }
						target="_blank"
						rel="noopener noreferrer"
						className="bb-activity-edit-modal__view-link"
					>
						{ __( 'View Activity', 'buddyboss' ) }
						<i className="bb-icons-rl bb-icons-rl-arrow-up-right"></i>
					</a>
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
