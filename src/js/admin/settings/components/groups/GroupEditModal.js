/**
 * BuddyBoss Admin Settings 2.0 - Group Edit Modal
 *
 * Tabbed modal for editing groups. Tabs are built dynamically from
 * registered_fields[].tab values (Details, Permissions, Integrations, + Pro tabs).
 * Members tab is a custom component, not registry-based.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useMemo, useCallback, useRef, Fragment } from '@wordpress/element';
import { splitFieldsByMetaboxGroup } from '../../utils/format';
import {
	Modal,
	Button,
	TabPanel,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { RegisteredMetaField } from '../common/RegisteredMetaField';
import { GroupMembersTab } from './GroupMembersTab';
import { GroupTopicsTab } from './GroupTopicsTab';

/**
 * Tab label mapping. Known tab keys get friendly labels; unknown tabs
 * use their key capitalized.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Object}
 */
var tabLabels = {
	details: __( 'Details', 'buddyboss' ),
	members: __( 'Members', 'buddyboss' ),
	permissions: __( 'Permissions', 'buddyboss' ),
	integrations: __( 'Integrations', 'buddyboss' ),
	topics: __( 'Topics', 'buddyboss' ),
};

/**
 * Tab order for known tabs.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Object}
 */
var tabOrder = {
	details: 1,
	members: 2,
	permissions: 3,
	integrations: 4,
	topics: 5,
};

/**
 * Group consecutive fields with layout='half' into row wrappers.
 *
 * @since BuddyBoss [BBVERSION]
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
 * Group Edit Modal Component
 *
 * @param {Object}   props          Component props.
 * @param {boolean}  props.isOpen   Whether the modal is open.
 * @param {Object}   props.group    Group object with registered_fields.
 * @param {Function} props.onClose  Close handler.
 * @param {Function} props.onSave   Save handler.
 * @param {boolean}  props.isSaving Whether save is in progress.
 * @returns {JSX.Element|null} Modal component or null.
 */
export function GroupEditModal( { isOpen, group, onClose, onSave, isSaving } ) {
	// All field values keyed by field ID.
	var registeredValuesState = useState( {} );
	var registeredValues = registeredValuesState[ 0 ];
	var setRegisteredValues = registeredValuesState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	var noticeState = useState( null );
	var notice = noticeState[ 0 ];
	var setNotice = noticeState[ 1 ];

	// Ref for the members tab save function (called on modal Save).
	var membersSaveRef = useRef( null );

	// Reset form when group changes.
	useEffect( function () {
		if ( isOpen && group ) {
			var initialValues = {};
			if ( group.registered_fields && Array.isArray( group.registered_fields ) ) {
				group.registered_fields.forEach( function ( field ) {
					initialValues[ field.id ] = field.value;
				} );
			}
			setRegisteredValues( initialValues );
			setError( '' );
			setNotice( null );
		}
	}, [ isOpen, group ] );

	/**
	 * Handle change for a registered field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} fieldId Field ID.
	 * @param {*}      val     New value.
	 */
	var handleRegisteredFieldChange = useCallback( function ( fieldId, val ) {
		setRegisteredValues( function ( prev ) {
			var next = {};
			Object.keys( prev ).forEach( function ( k ) {
				next[ k ] = prev[ k ];
			} );
			next[ fieldId ] = val;
			return next;
		} );
	}, [] );

	// Build tabs from registered fields.
	var tabs = useMemo( function () {
		if ( ! group || ! group.registered_fields ) {
			return [];
		}

		// Collect unique tab keys from visible fields.
		var tabKeys = {};
		group.registered_fields.forEach( function ( field ) {
			if ( field.tab && field.visible ) {
				tabKeys[ field.tab ] = true;
			}
		} );

		// Always include 'members' tab.
		tabKeys.members = true;

		// Build tab array sorted by tabOrder.
		var tabArray = Object.keys( tabKeys ).map( function ( key ) {
			return {
				name: key,
				title: tabLabels[ key ] || key.charAt( 0 ).toUpperCase() + key.slice( 1 ),
				order: tabOrder[ key ] || 100,
			};
		} );

		tabArray.sort( function ( a, b ) {
			return a.order - b.order;
		} );

		return tabArray;
	}, [ group ] );

	if ( ! isOpen || ! group ) {
		return null;
	}

	/**
	 * Check if a field's conditional dependency is met.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} field Field object with optional conditional property.
	 * @returns {boolean} True if field should be visible.
	 */
	var isConditionalMet = function ( field ) {
		if ( ! field.conditional ) {
			return true;
		}
		var currentVal = registeredValues[ field.conditional.field ];
		var expectedVal = field.conditional.value;

		// Boolean comparison: handle '1'/'0'/true/false.
		if ( true === expectedVal || false === expectedVal ) {
			var isTruthy = !! currentVal && '0' !== currentVal && 0 !== currentVal;
			return isTruthy === expectedVal;
		}

		return String( currentVal ) === String( expectedVal );
	};

	/**
	 * Render registered meta fields for a specific tab.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} tabName Tab name to render fields for.
	 * @returns {Array|null} Rendered field elements or null.
	 */
	var renderTabFields = function ( tabName ) {
		if ( ! group.registered_fields ) {
			return null;
		}

		var tabFields = group.registered_fields.filter( function ( field ) {
			return field.tab === tabName && field.visible && isConditionalMet( field );
		} );

		if ( 0 === tabFields.length ) {
			return (
				<p className="bb-group-edit-modal__empty-tab">
					{ __( 'No settings available for this tab.', 'buddyboss' ) }
				</p>
			);
		}

		// Render one grouped layout item (row of half/third fields, or single).
		var renderItem = function ( item, idx ) {
			if ( 'row' === item.type ) {
				return (
					<div key={ 'row-' + idx } className="bb-admin-meta-field__row">
						{ item.fields.map( function ( field ) {
							return (
								<RegisteredMetaField
									key={ field.id + '-' + group.id }
									field={ field }
									value={ registeredValues[ field.id ] }
									onChange={ function ( val ) {
										handleRegisteredFieldChange( field.id, val );
									} }
									itemId={ group.id }
								/>
							);
						} ) }
					</div>
				);
			}
			return (
				<RegisteredMetaField
					key={ item.field.id + '-' + group.id }
					field={ item.field }
					value={ registeredValues[ item.field.id ] }
					onChange={ function ( val ) {
						handleRegisteredFieldChange( item.field.id, val );
					} }
					itemId={ group.id }
				/>
			);
		};

		// Split this tab's fields into runs by source metabox so a bridged
		// third-party metabox (e.g. a child-theme box) renders inside a
		// bordered section headed by its title — parity with the Forums modal.
		var segments = splitFieldsByMetaboxGroup( tabFields );

		return segments.map( function ( segment, segIdx ) {
			var grouped = groupFieldsWithLayout( segment.fields );

			if ( ! segment.group ) {
				return (
					<Fragment key={ 'seg-flat-' + segIdx }>
						{ grouped.map( function ( item, idx ) {
							return renderItem( item, idx );
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
							return renderItem( item, idx );
						} ) }
					</div>
				</div>
			);
		} );
	};

	/**
	 * Handle group save — collects all registered field values and sends to server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSave = function () {
		setError( '' );

		var payload = {
			group_id: group.id,
		};

		// Build payload from all registered fields.
		if ( group.registered_fields && Array.isArray( group.registered_fields ) ) {
			group.registered_fields.forEach( function ( field ) {
				if ( field.readonly ) {
					return;
				}

				var val = registeredValues[ field.id ];

				// For richtext fields, pull latest content from TinyMCE.
				if ( 'richtext' === field.type && window.tinymce ) {
					var editorInstance = window.tinymce.get( 'bb-admin-edit-' + field.id + '-' + group.id );
					if ( editorInstance ) {
						val = editorInstance.getContent();
					}
				}

				payload[ 'registered_field_' + field.id ] = null !== val && undefined !== val ? val : '';
			} );
		}

		// Attach member save ref so parent can process member changes after main save.
		payload._membersSave = membersSaveRef.current;

		onSave( payload );
	};

	/**
	 * Render tab content.
	 *
	 * @param {Object} tab Tab object with name property.
	 * @returns {JSX.Element} Tab content.
	 */
	var renderTabContent = function ( tab ) {
		if ( 'members' === tab.name ) {
			return (
				<GroupMembersTab
					groupId={ group.id }
					setNotice={ setNotice }
					saveRef={ membersSaveRef }
				/>
			);
		}

		if ( 'topics' === tab.name ) {
			return (
				<GroupTopicsTab
					groupId={ group.id }
					setNotice={ setNotice }
				/>
			);
		}

		return (
			<div className={ 'bb-group-edit-modal__tab-content bb-group-edit-modal__tab-content--' + tab.name }>
				{ renderTabFields( tab.name ) }
			</div>
		);
	};

	return (
		<Modal
			title={ __( 'Edit Group', 'buddyboss' ) }
			onRequestClose={ function () {
				if ( ! isSaving ) {
					onClose();
				}
			} }
			className="bb-group-edit-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-group-edit-modal__body bb-admin-settings-modal__body">
				{ error && (
					<p className="bb-group-edit-modal__error">{ error }</p>
				) }

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

				{ tabs.length > 0 && (
					<TabPanel
						className="bb-group-edit-modal__tabs"
						tabs={ tabs }
					>
						{ renderTabContent }
					</TabPanel>
				) }
			</div>

			<div className="bb-group-edit-modal__footer bb-admin-settings-modal__footer">
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
		</Modal>
	);
}
