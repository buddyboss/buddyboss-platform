/**
 * BuddyBoss Admin Settings 2.0 - SSO Providers Field Component
 *
 * Renders social login provider cards with icons, labels, and checkboxes.
 * Follows the same pattern as SharePlatformsField for Activity Sharing.
 *
 * When Pro is not active, cards are rendered in a disabled/greyed state
 * via the pro_only flag on the field.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * SSO Providers Field Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field configuration with providers array.
 * @param {*}        props.value    Current field value (object: { google: 1, facebook: 0, ... }).
 * @param {Function} props.onChange Change handler.
 * @param {boolean}  props.disabled Whether the field is disabled.
 * @returns {JSX.Element} SSO providers field.
 */
export function SsoProvidersField( { field, value, onChange, disabled } ) {
	var providers = field.providers || [];
	var providersValue = value && 'object' === typeof value && ! Array.isArray( value ) ? value : {};

	/**
	 * Check if a provider is enabled.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} provider Provider data.
	 * @returns {boolean} Whether provider is enabled.
	 */
	var isProviderChecked = function ( provider ) {
		// When Pro enriches, use the enabled flag from provider data.
		if ( undefined !== provider.enabled ) {
			return !! provider.enabled;
		}
		// Fallback to value object.
		return !! providersValue[ provider.id ] && 0 !== providersValue[ provider.id ] && '0' !== providersValue[ provider.id ];
	};

	return (
		<div className="bb-admin-sso-providers">
			<div className="bb-admin-sso-providers__grid">
				{ providers.map( function ( provider ) {
					var checked = isProviderChecked( provider );

					return (
						<div
							key={ provider.id }
							className={ 'bb-admin-sso-providers__card' + ( ! checked ? ' bb-admin-sso-providers__card--disabled' : '' ) }
						>
							<div className="bb-admin-sso-providers__card-icon">
								{ provider.icon ? (
									<img
										src={ provider.icon }
										alt={ provider.label }
									/>
								) : (
									<span className="bb-admin-sso-providers__card-icon-placeholder">
										{ provider.label.charAt( 0 ) }
									</span>
								) }
								<CheckboxControl
									checked={ checked }
									className="bb-admin-sso-providers__card-checkbox"
									disabled={ disabled }
									onChange={ function () {
										// Provider enable/disable is managed via legacy SSO admin.
										// Cards are read-only in Settings 2.0 for now.
									} }
									__nextHasNoMarginBottom
								/>
							</div>

							<div className="bb-admin-sso-providers__card-footer">
								<span className="bb-admin-sso-providers__card-label">
									{ provider.label }
								</span>
								<button
									type="button"
									className="bb-admin-sso-providers__card-menu"
									disabled={ disabled }
									aria-label={ provider.label + ' ' + __( 'options', 'buddyboss' ) }
								>
									<i className="bb-icon-l bb-icon-ellipsis-h"></i>
								</button>
							</div>
						</div>
					);
				} ) }
			</div>
		</div>
	);
}
