/**
 * BuddyBoss Admin Settings 2.0 - Notification Types Field
 *
 * Renders the notification types grouped table with toggle switches,
 * Email/Web sub-type checkboxes, and Email Template links.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { CheckboxControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { safeUrl } from '../../utils/sanitize';

/**
 * Notification Types Field component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} props            Component props.
 * @param {Object} props.field      Field definition from PHP.
 * @param {Object} props.value      Current value (bb_enabled_notification array).
 * @param {Function} props.onChange Callback to update value.
 * @return {JSX.Element} Rendered notification types table.
 */
const NotificationTypesField = ( props ) => {
	const { field, onChange } = props;
	const value = props.value || {};
	const groups = field.notification_groups || [];

	/**
	 * Handle main toggle change for a notification type.
	 *
	 * @param {string}  key     Notification type key.
	 * @param {boolean} checked New checked state.
	 */
	const handleMainToggle = ( key, checked ) => {
		const newValue = Object.assign( {}, value );
		if ( ! newValue[ key ] ) {
			newValue[ key ] = {};
		}
		newValue[ key ] = Object.assign( {}, newValue[ key ], {
			main: checked ? 'yes' : 'no',
		} );
		onChange( newValue );
	};

	/**
	 * Handle sub-type checkbox change (email, web, app).
	 *
	 * @param {string}  key      Notification type key.
	 * @param {string}  subType  Sub-type key (email, web, app).
	 * @param {boolean} checked  New checked state.
	 */
	const handleSubTypeChange = ( key, subType, checked ) => {
		const newValue = Object.assign( {}, value );
		if ( ! newValue[ key ] ) {
			newValue[ key ] = {};
		}
		newValue[ key ] = Object.assign( {}, newValue[ key ] );
		newValue[ key ][ subType ] = checked ? 'yes' : 'no';
		onChange( newValue );
	};

	/**
	 * Get the current main toggle state for a notification type.
	 *
	 * @param {Object} fieldData Field data from notification_groups.
	 * @return {boolean} Whether the main toggle is checked.
	 */
	const isMainChecked = ( fieldData ) => {
		if ( value[ fieldData.key ] && 'undefined' !== typeof value[ fieldData.key ].main ) {
			return 'yes' === value[ fieldData.key ].main;
		}
		return fieldData.checked;
	};

	/**
	 * Get the current sub-type checkbox state.
	 *
	 * @param {Object} fieldData Field data from notification_groups.
	 * @param {string} subType   Sub-type key (email, web, app).
	 * @return {boolean} Whether the sub-type is checked.
	 */
	const isSubTypeChecked = ( fieldData, subType ) => {
		if ( value[ fieldData.key ] && 'undefined' !== typeof value[ fieldData.key ][ subType ] ) {
			return 'yes' === value[ fieldData.key ][ subType ];
		}
		if ( fieldData.sub_types && fieldData.sub_types[ subType ] ) {
			return 'yes' === fieldData.sub_types[ subType ].is_checked;
		}
		return false;
	};

	/**
	 * Check if a sub-type should be disabled.
	 *
	 * @param {Object}  fieldData  Field data from notification_groups.
	 * @param {string}  subType    Sub-type key (email, web, app).
	 * @param {boolean} mainActive Whether the main toggle is active.
	 * @return {boolean} Whether the sub-type is disabled.
	 */
	const isSubTypeDisabled = ( fieldData, subType, mainActive ) => {
		// When main toggle is OFF, all sub-types should be disabled.
		if ( ! mainActive ) {
			return true;
		}
		if ( fieldData.sub_types && fieldData.sub_types[ subType ] ) {
			return fieldData.sub_types[ subType ].disabled;
		}
		return false;
	};

	/**
	 * Render the email template link for a notification type.
	 *
	 * @param {Object} emailTemplate Email template data.
	 * @return {JSX.Element|null} Rendered link or null.
	 */
	const renderEmailTemplateLink = ( emailTemplate ) => {
		if ( ! emailTemplate || ! emailTemplate.has_templates ) {
			return null;
		}

		const isMissing = emailTemplate.missing;
		const label = isMissing
			? ( emailTemplate.count > 1 ? __( 'Missing Email Templates', 'buddyboss' ) : __( 'Missing Email Template', 'buddyboss' ) )
			: ( emailTemplate.count > 1 ? __( 'Email Templates', 'buddyboss' ) : __( 'Email Template', 'buddyboss' ) );

		let className = 'bb-notification-types__email-link';
		if ( isMissing ) {
			className += ' bb-notification-types__email-link--missing';
		}

		return (
			<a
				href={ safeUrl( emailTemplate.url || '#' ) }
				className={ className }
				target="_blank"
				rel="noopener noreferrer"
			>
				{ label }
			</a>
		);
	};

	if ( ! groups.length ) {
		return (
			<p className="bb-notification-types__empty">
				{ __( 'No notification types registered.', 'buddyboss' ) }
			</p>
		);
	}

	return (
		<div className="bb-notification-types">
			{ groups.map( ( group ) => {
				if ( ! group.fields || ! group.fields.length ) {
					return null;
				}

				return (
					<div key={ group.key } className="bb-notification-types__group">
						{ group.admin_label && (
							<div className="bb-notification-types__group-header">
								{ group.admin_label }
							</div>
						) }
						<div className="bb-notification-types__rows">
							{ group.fields.map( ( fieldData ) => {
								const mainChecked = isMainChecked( fieldData );
								const subTypes = fieldData.sub_types || {};
								const subTypeKeys = Object.keys( subTypes ).filter(
									( key ) => subTypes[ key ] && subTypes[ key ].is_render
								);

								return (
									<div
										key={ fieldData.key }
										className={
											'bb-notification-types__row' +
											( ! mainChecked ? ' bb-notification-types__row--disabled' : '' )
										}
										aria-disabled={ ! mainChecked ? 'true' : undefined }
									>
										<div className="bb-notification-types__toggle">
											<ToggleControl
												label={ decodeEntities( fieldData.label ) }
												checked={ mainChecked }
												onChange={ ( checked ) => {
													if ( ! fieldData.read_only ) {
														handleMainToggle( fieldData.key, checked );
													}
												} }
												disabled={ fieldData.read_only }
												__nextHasNoMarginBottom
											/>
											{ fieldData.tooltip && (
												<span
													className="bb-notification-types__tooltip"
													title={ decodeEntities( fieldData.tooltip ) }
												>
													<i className="bb-icons-rl bb-icons-rl-info-circle"></i>
												</span>
											) }
										</div>
										<div className="bb-notification-types__sub-types">
											{ subTypeKeys.map( ( subKey ) => {
												const subData = subTypes[ subKey ];
												const subChecked = isSubTypeChecked( fieldData, subKey );
												const subDisabled = isSubTypeDisabled( fieldData, subKey, mainChecked );

												return (
													<div
														key={ subKey }
														className={
															'bb-notification-types__sub-type' +
															( subDisabled ? ' bb-notification-types__sub-type--disabled' : '' )
														}
													>
														<CheckboxControl
															label={ decodeEntities( subData.label ) }
															checked={ subChecked }
															onChange={ ( checked ) => {
																if ( ! fieldData.read_only ) {
																	handleSubTypeChange( fieldData.key, subKey, checked );
																}
															} }
															disabled={ subDisabled || fieldData.read_only }
															__nextHasNoMarginBottom
														/>
													</div>
												);
											} ) }
										</div>
										<div className="bb-notification-types__email-template">
											{ renderEmailTemplateLink( fieldData.email_template ) }
										</div>
									</div>
								);
							} ) }
						</div>
					</div>
				);
			} ) }
		</div>
	);
};

export { NotificationTypesField };
