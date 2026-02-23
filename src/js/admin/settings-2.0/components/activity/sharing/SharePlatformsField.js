/**
 * BuddyBoss Admin Settings 2.0 - Share Platforms Field Component
 *
 * Renders the sharing platform cards with checkboxes.
 * Same code flow as ReactionModeField emotion cards.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { CheckboxControl } from '@wordpress/components';
import { RawHTML } from '@wordpress/element';
import { sanitizeHtml } from '../../../utils/sanitize';

/**
 * Share Platforms Field Component
 *
 * @param {Object}   props          Component props
 * @param {Object}   props.field    Field configuration
 * @param {*}        props.value    Current field value
 * @param {Function} props.onChange Change handler
 * @returns {JSX.Element} Share platforms field
 */
export function SharePlatformsField({ field, value, onChange }) {
	const platformsValue = value && typeof value === 'object' && !Array.isArray(value) ? value : {};

	const isPlatformChecked = (optionKey) => {
		if ( typeof value === 'object' && !Array.isArray(value) ) {
			return !!platformsValue[optionKey] && platformsValue[optionKey] !== '0' && platformsValue[optionKey] !== 0;
		}
		return Array.isArray(value) && value.includes(optionKey);
	};

	return (
		<div>
			<div className="bb-admin-settings-field__checkbox-list-cards">
				{(field.options || []).map((option) => (
					<div
						key={option.value}
						className={`bb_share_item bb_share_item_${option.label}`}
					>
						<div className="bb_share_icon">
							{option.icon && (
								<i className={option.icon}></i>
							)}
						</div>

						<div className="bb_share_footer">
							<span>{option.label}</span>
							<CheckboxControl
								checked={isPlatformChecked(option.value)}
								className="bb_share_checkbox"
								onChange={(checked) => {
									const newValue = {};
									(field.options || []).forEach(function( opt ) {
										if ( opt.value === option.value ) {
											newValue[opt.value] = checked ? 1 : 0;
										} else {
											newValue[opt.value] = platformsValue[opt.value] !== undefined
												? ( typeof platformsValue[opt.value] === 'string' ? parseInt(platformsValue[opt.value], 10) : platformsValue[opt.value] )
												: 0;
										}
									});
									onChange(field.name, newValue);
								}}
								__nextHasNoMarginBottom
							/>
						</div>
					</div>
				))}
			</div>
			{field.description && (
				<RawHTML className="bb-admin-settings-form__field-description">
					{sanitizeHtml( field.description )}
				</RawHTML>
			)}
		</div>
	);
}
