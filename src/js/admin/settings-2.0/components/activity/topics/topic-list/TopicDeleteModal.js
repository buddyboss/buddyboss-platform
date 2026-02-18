/**
 * BuddyBoss Admin Settings 2.0 - Topic Delete Confirmation Modal
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState } from '@wordpress/element';
import {
	Modal,
	RadioControl,
	SelectControl,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Topic Delete Confirmation Modal
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
export function TopicDeleteModal( { isOpen, onClose, onConfirm, topic, availableTopics, migrateNonce, isSaving } ) {
	var migrateTypeState = useState( 'migrate' );
	var migrateType = migrateTypeState[ 0 ];
	var setMigrateType = migrateTypeState[ 1 ];

	var newTopicIdState = useState( '' );
	var newTopicId = newTopicIdState[ 0 ];
	var setNewTopicId = newTopicIdState[ 1 ];

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
