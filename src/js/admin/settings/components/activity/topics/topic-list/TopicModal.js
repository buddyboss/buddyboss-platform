/**
 * BuddyBoss Admin Settings 2.0 - Topic Add/Edit Modal
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect } from '@wordpress/element';
import {
	Modal,
	TextControl,
	RadioControl,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Topic Add/Edit Modal Component
 *
 * @param {Object}   props              Component props.
 * @param {boolean}  props.isOpen       Whether the modal is open.
 * @param {Function} props.onClose      Close handler.
 * @param {Function} props.onSave       Save handler.
 * @param {Object}   props.topic        Topic object for editing (null for add).
 * @param {boolean}  props.isSaving     Whether save is in progress.
 * @returns {JSX.Element|null} Modal component or null.
 */
export function TopicModal( { isOpen, onClose, onSave, topic, isSaving } ) {
	var isEditing = !! topic;
	var initialName = isEditing ? ( topic.name || '' ) : '';
	// Normalize permission_type: the AJAX response may return display labels
	// ('Admins', 'Anyone') instead of raw values ('mods_admins', 'anyone').
	var rawPermission = isEditing ? ( topic.permission_type || 'anyone' ) : 'anyone';
	var initialPermission = 'mods_admins' === rawPermission || 'Admins' === rawPermission
		? 'mods_admins'
		: 'anyone';

	var nameState = useState( initialName );
	var name = nameState[ 0 ];
	var setName = nameState[ 1 ];

	var permissionState = useState( initialPermission );
	var permission = permissionState[ 0 ];
	var setPermission = permissionState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	// Reset form when topic changes.
	useEffect( function () {
		if ( isOpen ) {
			setName( isEditing ? ( topic.name || '' ) : '' );
			var resetPermission = isEditing ? ( topic.permission_type || 'anyone' ) : 'anyone';
			setPermission(
				'mods_admins' === resetPermission || 'Admins' === resetPermission
					? 'mods_admins'
					: 'anyone'
			);
			setError( '' );
		}
	}, [ isOpen, topic ] );

	if ( ! isOpen ) {
		return null;
	}

	var handleSave = function () {
		var trimmedName = name.trim();
		if ( ! trimmedName ) {
			setError( __( 'Topic name is required.', 'buddyboss-platform' ) );
			return;
		}
		setError( '' );
		onSave( {
			name: trimmedName,
			permission_type: permission,
			topic_id: isEditing ? topic.topic_id : 0,
		} );
	};

	return (
		<Modal
			title={ isEditing ? __( 'Edit Topic', 'buddyboss-platform' ) : __( 'Add New Topic', 'buddyboss-platform' ) }
			onRequestClose={ onClose }
			className="bb-topic-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-topic-modal__body">
				<div className='bb-admin-settings-modal__row--separator'>
					<TextControl
						label={ __( 'Topic Name', 'buddyboss-platform' ) }
						value={ name }
						onChange={ function ( val ) {
							setName( val );
							if ( error ) {
								setError( '' );
							}
						} }
						placeholder={ __( 'Enter topic name', 'buddyboss-platform' ) }
						__nextHasNoMarginBottom
					/>
					{ error && (
						<p className="bb-topic-modal__error">{ error }</p>
					) }
				</div>

				<div className="bb-topic-modal__permission">
					<label className="bb-topic-modal__permission-label">
						{ __( 'Posting Permissions', 'buddyboss-platform' ) }
					</label>
					<RadioControl
						selected={ permission }
						options={ [
							{ label: __( 'Anyone', 'buddyboss-platform' ), value: 'anyone' },
							{ label: __( 'Admins', 'buddyboss-platform' ), value: 'mods_admins' },
						] }
						onChange={ setPermission }
					/>
				</div>
			</div>

			<div className="bb-topic-modal__footer bb-admin-settings-modal__footer">
				<Button
					variant="secondary"
					onClick={ onClose }
					disabled={ isSaving }
				>
					{ __( 'Cancel', 'buddyboss-platform' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ handleSave }
					isBusy={ isSaving }
					disabled={ isSaving }
				>
					{ isEditing ? __( 'Save', 'buddyboss-platform' ) : __( 'Add Topic', 'buddyboss-platform' ) }
				</Button>
			</div>
		</Modal>
	);
}
