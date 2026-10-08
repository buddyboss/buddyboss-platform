/**
 * BuddyBoss Admin Settings 2.0 - ReactionButtonField Component
 *
 * Reaction button: Pro-only field with icon picker and jQuery bridge.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BBIcon } from '../common/BBIcon';

/**
 * ReactionButtonField component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field definition.
 * @param {*}        props.value    Current field value.
 * @param {Function} props.onChange Change handler (fieldName, newValue).
 * @returns {JSX.Element} ReactionButtonField component.
 */
export function ReactionButtonField( { field, value, onChange } ) {
	const isProLocked = !!field.pro_notice?.show;

	// Get button settings (icon and text).
	const buttonValue = value || {};
	const buttonIcon = buttonValue.icon || field.icon || 'thumbs-up';
	const buttonText = buttonValue.text || field.text || __( 'Like', 'buddyboss-platform' );

	/**
	 * Handle edit reaction button click - opens the icon picker modal.
	 */
	var handleEditReactionButton = function () {
		var iconChooser = document.getElementById( 'bb-reaction-button-chooser' );
		if ( iconChooser && window.jQuery ) {
			window.jQuery( iconChooser ).trigger( 'click' );
		}
	};

	/**
	 * Handle reaction button text change.
	 */
	var handleButtonTextChange = function ( newText ) {
		var currentButtonValue = ( typeof value === 'object' && value !== null )
			? { ...value }
			: {};
		currentButtonValue.text = newText;
		// Preserve the icon if it exists.
		if ( ! currentButtonValue.icon ) {
			currentButtonValue.icon = buttonIcon;
		}
		onChange( field.name, currentButtonValue );
	};

	return (
		<div key={ field.name } className={ `bb-reaction-button-field${ isProLocked ? ' bb-reaction-button-field--disabled' : '' }` }>
			<div className="bb-reaction-button-card">
				<div className="bb-reaction-button-card__preview">
					<div className="bb-reaction-button-card__icon-wrapper">
						<button
							type="button"
							className="bb-reaction-button-card__icon-btn"
							id="bb-reaction-button-chooser"
							disabled={ isProLocked }
						>
							<i className={ `bb-icon-rf bb-icon-${ buttonIcon }` }></i>
						</button>
					</div>
					<div className="bb-reaction-button-card__footer">
						<input
							name="bb_reactions_button[text]"
							id="bb-reaction-button-text"
							type="text"
							maxLength="12"
							value={ buttonText }
							placeholder={ __( 'Like', 'buddyboss-platform' ) }
							className="bb-reaction-button-card__text-input"
							disabled={ isProLocked }
							readOnly={ isProLocked }
							onChange={ ( e ) => handleButtonTextChange( e.target.value ) }
						/>
						<DropdownMenu
							icon={ <i className="bb-icons-rl-dots-three"></i> }
							label={ __( 'More options', 'buddyboss-platform' ) }
							className="bb-reaction-button-card__menu-btn"
						>
							{ ( { onClose } ) => (
								<MenuGroup className="bb_dropdown_menu_group">
									<MenuItem
										icon={ <BBIcon name="note-pencil" /> }
										iconPosition="left"
										onClick={ () => {
											onClose();
											handleEditReactionButton();
										} }
									>
										{ __( 'Edit', 'buddyboss-platform' ) }
									</MenuItem>
								</MenuGroup>
							) }
						</DropdownMenu>
					</div>
				</div>
				<input
					type="hidden"
					name="bb_reactions_button[icon]"
					id="bb-reaction-button-hidden-field"
					value={ buttonIcon }
				/>
			</div>
		</div>
	);
}
