/**
 * BuddyBoss Admin Settings 2.0 - Single Topic Item Row
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import {
	DropdownMenu,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Single topic row component.
 *
 * @param {Object}   props                Component props.
 * @param {Object}   props.topic          Topic object.
 * @param {Function} props.onEdit         Edit handler.
 * @param {Function} props.onDelete       Delete handler.
 * @param {Object}   props.dragHandleProps Drag handle event props.
 * @param {boolean}  props.isDragging     Whether item is currently being dragged.
 * @param {boolean}  props.isDragOver     Whether another item is dragged over this.
 * @returns {JSX.Element} Topic item component.
 */
export function TopicItem( { topic, onEdit, onDelete, dragHandleProps, isDragging, isDragOver } ) {
	var pType = topic.permission_type;
	var permissionLabel = 'mods_admins' === pType || 'Admins' === pType
		? __( 'Admins', 'buddyboss' )
		: __( 'Anyone', 'buddyboss' );

	var BBIcon = function ( { name } ) {
		return <span className={ 'bb-icons-rl-' + name } />;
	};

	var classNames = 'bb-topic-list__item';
	if ( isDragging ) {
		classNames += ' bb-topic-list__item--dragging';
	}
	if ( isDragOver ) {
		classNames += ' bb-topic-list__item--drag-over';
	}

	return (
		<div className={ classNames } data-topic-id={ topic.topic_id }>
			<span
				className="bb-topic-list__drag-handle"
				{ ...dragHandleProps }
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
										onEdit( topic );
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
										onDelete( topic );
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
	);
}
