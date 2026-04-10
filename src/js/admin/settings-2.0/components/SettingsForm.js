/**
 * BuddyBoss Admin Settings 2.0 - Settings Form Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useMemo, useEffect, useRef } from '@wordpress/element';
import {
	ToggleControl,
	TextControl,
	TextareaControl,
	SelectControl,
	RadioControl,
	CheckboxControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	ReactionModeField,
	useReactionCallbacks,
	ReactionMigration,
	ReactionNotice,
	ReactionInfo,
	MigrationModal,
	ReactionButtonField,
} from './reaction';
import { decodeEntities } from '@wordpress/html-entities';
import { sanitizeHtml, safeUrl } from '../utils/sanitize';
import { TopicListField } from './activity/topics/topic-list';
import { SharePlatformsField } from './activity/sharing';
import { SsoProvidersField } from './fields/SsoProvidersField';
import { ProfileTypeRedirectsField } from './fields/ProfileTypeRedirectsField';
import { AccessControlField } from './access-control/AccessControlField';
import { NotificationTypesField } from './notifications';
import { CheckboxListField } from './fields/CheckboxListField';
import { ImageRadioField } from './fields/ImageRadioField';
import { DimensionsField } from './fields/DimensionsField';
import { ConfirmToggleModal } from './modals/ConfirmToggleModal';
import { AsyncSelectField } from './fields/AsyncSelectField';
import { ExtensionListField } from './fields/ExtensionListField';
import { DocumentExtensionsField } from './fields/DocumentExtensionsField';
import { InputButtonField } from './fields/InputButtonField';
import { DomainRestrictionsField } from './fields/DomainRestrictionsField';
import { EmailRestrictionsField } from './fields/EmailRestrictionsField';
import { PasswordField } from './fields/PasswordField';
import { StatusCheckField } from './fields/StatusCheckField';
import { ImageUploadField } from './fields/ImageUploadField';
import { RecaptchaVerifyField } from './recaptcha/RecaptchaVerifyField';
import { RecaptchaBypassField } from './recaptcha/RecaptchaBypassField';
import { VerifyPopupField } from './fields/VerifyPopupField';
import { useFetchOnChange } from '../hooks/useFetchOnChange';

/**
 * Settings Form Component (matching Figma settingsSection)
 *
 * @param {Object} props Component props
 * @param {Array} props.fields Fields array
 * @param {Object} props.values Current field values
 * @param {Function} props.onChange Change handler
 * @returns {JSX.Element} Settings form component
 */
export function SettingsForm({ fields, values, onChange }) {
	// Use reaction callbacks hook for jQuery emotion picker integration
	const { defaultEmotionsRef } = useReactionCallbacks(onChange, values);

	// Fetch-on-change: watches fields and triggers AJAX to refresh select options dynamically.
	var fetchOnChange = useFetchOnChange( fields, values );

	// Track migration modal state
	const [isMigrationModalOpen, setIsMigrationModalOpen] = useState(false);
	const [currentMigrationData, setCurrentMigrationData] = useState(null);

	// Track confirm toggle modal state (for fields with confirm_message).
	const [confirmModalState, setConfirmModalState] = useState({
		isOpen: false,
		message: '',
		fieldName: '',
		saveValue: 0,
		title: '',
		confirmLabel: '',
		cancelLabel: '',
		isDestructive: false,
	});

	// Memoize sanitized HTML to avoid DOMParser overhead on every re-render.
	const sanitizedHtml = useMemo( () => {
		const cache = {};
		fields.forEach( ( field ) => {
			if ( field.description && 'string' === typeof field.description ) {
				cache[ field.name + '__desc' ] = sanitizeHtml( field.description );

				// Pre-split and sanitize parts for fields with inline description controls.
				if ( field.description.indexOf( '%s' ) !== -1 && field.description_controls && field.description_controls.length > 0 ) {
					cache[ field.name + '__parts' ] = field.description.split( '%s' ).map( function ( part ) {
						return sanitizeHtml( part );
					} );
				}
			}
			if ( field.help_text ) {
				cache[ field.name + '__help' ] = sanitizeHtml( field.help_text );
			}
			if ( field.empty_state_title && 'string' === typeof field.empty_state_title ) {
				cache[ field.name + '__empty_title' ] = sanitizeHtml( field.empty_state_title );
			}
			// Pre-sanitize per-option descriptions for fields with option_descriptions.
			if ( field.option_descriptions && 'object' === typeof field.option_descriptions ) {
				Object.keys( field.option_descriptions ).forEach( function ( key ) {
					cache[ field.name + '__optdesc__' + key ] = sanitizeHtml( field.option_descriptions[ key ] );
				} );
			}
		} );
		return cache;
	}, [ fields ] );

	/**
	 * Evaluate a single condition object against current values.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} cond Single condition: { field, value }.
	 * @return {boolean} Whether the condition is met.
	 */
	const evaluateCondition = (cond) => {
		const condValue = values[cond.field];
		const expectedValue = cond.value;

		// When expected value is boolean, use truthy/falsy comparison
		// because DB values can be 1, 0, "1", "0" while conditional uses true/false.
		if (expectedValue === true || expectedValue === false) {
			const isTruthy = !!condValue && condValue !== '0' && condValue !== 0;
			return isTruthy === expectedValue;
		}

		// When expected value is an array, check if current value is in the array.
		if (Array.isArray(expectedValue)) {
			return expectedValue.indexOf(condValue) !== -1;
		}

		// For non-boolean values, use strict comparison.
		return condValue === expectedValue;
	};

	/**
	 * Check if a field's conditional is met.
	 *
	 * Supports two formats:
	 *   1. Single condition:   { field: 'name', value: expected }
	 *   2. Multiple conditions: { conditions: [ {field, value}, ... ], operator: 'AND'|'OR' }
	 *      operator defaults to 'AND'.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} field Field config with optional conditional property.
	 * @return {boolean} True when condition is met (or no conditional exists).
	 */
	const isConditionalMet = ( field ) => {
		if ( ! field.conditional ) {
			return true;
		}

		// Skip visibility check for disable-only conditionals — they stay visible.
			if ( 'disable' === field.conditional.action ) {
				return true;
			}

			// Multiple conditions with operator.
		if ( Array.isArray( field.conditional.conditions ) ) {
			var operator = ( field.conditional.operator || 'AND' ).toUpperCase();
			var conditions = field.conditional.conditions;

			if ( 'OR' === operator ) {
				return conditions.some( evaluateCondition );
			}
			// Default: AND — all conditions must match.
			return conditions.every( evaluateCondition );
		}

		// Single condition (backward-compatible).
		return evaluateCondition( field.conditional );
	};

	/**
	 * Check if a field should be visible based on its conditional logic.
	 * Fields with action:'disable' stay visible but get disabled instead.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} field Field config with optional conditional property.
	 * @return {boolean} True when the field should be rendered.
	 */
	const isFieldVisible = (field) => {
		if ( field.conditional ) {
			// When action is 'disable', keep visible (handled by isFieldConditionallyDisabled).
			if ( 'disable' === field.conditional.action ) {
				return true;
			}
			return isConditionalMet( field );
		}

		// For parent_field (nesting), always show the field but it may be disabled
		// Fields with parent_field are rendered as children of their parent
		return true;
	};

	/**
	 * Check if a field should be disabled based on its conditional logic
	 * when action is 'disable' instead of 'hide'.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	const isFieldConditionallyDisabled = (field) => {
		if ( ! field.conditional || 'disable' !== field.conditional.action ) {
			return false;
		}

		// Multiple conditions with operator.
		if (Array.isArray(field.conditional.conditions)) {
			var operator = (field.conditional.operator || 'AND').toUpperCase();
			var conditions = field.conditional.conditions;

			if ('OR' === operator) {
				return ! conditions.some(evaluateCondition);
			}
			return ! conditions.every(evaluateCondition);
		}

		// Single condition.
		return ! evaluateCondition(field.conditional);
	};

	/**
	 * Check if a field should be disabled based on its parent toggle
	 */
	const isFieldDisabled = (field) => {
		if (!field.parent_field) {
			return false;
		}

		const parentValue = values[field.parent_field];

		// Find parent field to check if it's inverted
		const parentField = fields.find(f => f.name === field.parent_field);

		// Hidden parents are label-only groupings — never disable their children.
		if ( 'hidden' === parentField?.type ) {
			return false;
		}

		const isParentInverted = true === parentField?.invert_value;

		// If parent_value is specified, check for exact match
		if (field.parent_value !== undefined && field.parent_value !== null) {
			return parentValue !== field.parent_value;
		}

		// For inverted parent: enabled when parent actual value is falsy (display is truthy)
		// For normal parent: enabled when parent value is truthy
		if (isParentInverted) {
			// Parent is inverted: child is enabled when parent's actual value is falsy
			return !!parentValue;
		}

		// Default: disabled if parent is falsy (for toggles)
		return !parentValue;
	};

	/**
	 * Render the field input control
	 */
	const renderFieldControl = (field, disabled = false) => {
		const value = values[field.name] !== undefined ? values[field.name] : field.default;

		switch (field.type) {
			case 'toggle':
				// Handle inverted values (e.g., "disable" options shown as "enable" toggles)
				const isInverted = true === field.invert_value;
				const displayValue = isInverted ? !value : !!value;
				return (
					<div className="bb-admin-settings-form__toggle-wrapper">
						<ToggleControl
							key={field.name}
							label={ field.description_controls && field.description_controls.length > 0 ? '' : decodeEntities( field.description || '' ) }
							checked={displayValue}
							onChange={(checked) => {
								// If inverted, save the opposite of what's displayed
								const saveValue = isInverted ? !checked : checked;

								// Show confirm modal when turning ON and field has confirm_message.
								if ( checked && field.confirm_message ) {
									setConfirmModalState({
										isOpen: true,
										message: field.confirm_message,
										fieldName: field.name,
										saveValue: saveValue ? 1 : 0,
										title: field.confirm_title || '',
										confirmLabel: field.confirm_ok || '',
										cancelLabel: field.confirm_cancel || '',
										isDestructive: !!field.confirm_destructive,
									});
									return;
								}

								onChange(field.name, saveValue ? 1 : 0);
							}}
							disabled={disabled}
							__nextHasNoMarginBottom
						/>
					</div>
				);

			case 'checkbox':
				// Render as actual checkbox (square) control.
				const cbIsInverted = true === field.invert_value;
				const cbDisplayValue = cbIsInverted ? !value : !!value;
				return (
					<CheckboxControl
						key={field.name}
						label=""
						checked={cbDisplayValue}
						onChange={(checked) => {
							const saveValue = cbIsInverted ? !checked : checked;
							onChange(field.name, saveValue ? 1 : 0);
						}}
						disabled={disabled}
						__nextHasNoMarginBottom
					/>
				);

			case 'checkbox_list':
				return (
					<CheckboxListField
						field={field}
						value={value}
						onChange={onChange}
						disabled={disabled}
						sanitizedDescription={sanitizedHtml[ field.name + '__desc' ]}
					/>
				);

			case 'share_platforms':
				// Delegate to SharePlatformsField component (same pattern as reaction_mode).
				return (
					<SharePlatformsField
						field={field}
						value={value}
						onChange={onChange}
					/>
				);

			case 'sso_providers':
				// SSO provider cards (Google, Facebook, X, LinkedIn, Apple).
				return (
					<SsoProvidersField
						field={field}
						value={value}
						onChange={onChange}
						disabled={disabled}
					/>
				);

			case 'profile_type_redirects':
				// Profile type redirect list with per-type login/logout dropdowns.
				return (
					<ProfileTypeRedirectsField />
				);

			case 'input_button':
				return (
					<InputButtonField
						field={field}
						value={value}
						onChange={onChange}
						disabled={disabled}
						values={values}
					/>
				);

			case 'status_check':
				return (
					<StatusCheckField
						field={field}
						values={values}
						disabled={disabled}
					/>
				);

			case 'recaptcha_verify':
				return (
					<RecaptchaVerifyField
						field={field}
						values={values}
						disabled={disabled}
					/>
				);

			case 'recaptcha_bypass':
				return (
					<RecaptchaBypassField
						field={field}
						value={value}
						values={values}
						onChange={onChange}
						disabled={disabled}
					/>
				);

			case 'password':
				return (
					<PasswordField
						key={field.name}
						field={field}
						value={value}
						onChange={onChange}
						disabled={disabled}
					/>
				);

			case 'text':
			case 'email':
			case 'url': {
				var hasCopy = field.field_class && -1 !== field.field_class.indexOf( 'bb-admin-settings-form__field--copy' );

				return (
					<div className={ ( field.maxlength > 0 ? 'bb-admin-settings-form__field-text-wrapper' : '' ) + ( hasCopy ? ' bb-admin-settings-form__field-text-copy' : '' ) }>
						<TextControl
							key={field.name}
							label=""
							value={value || ''}
							onChange={function( newValue ) {
								if ( field.maxlength && newValue.length > field.maxlength ) {
									newValue = newValue.substring( 0, field.maxlength );
								}
								onChange( field.name, newValue );
							}}
							type={ 'email' === field.type ? 'email' : 'url' === field.type ? 'url' : 'text' }
							disabled={disabled}
							placeholder={field.placeholder || ''}
							maxLength={ field.maxlength > 0 ? field.maxlength : undefined }
							__nextHasNoMarginBottom
						/>
						{ hasCopy && (
							<button
								type="button"
								className="bb-admin-settings-form__copy-btn"
								title={ __( 'Copy to clipboard', 'buddyboss' ) }
								onClick={ function() {
									if ( navigator.clipboard && value ) {
										navigator.clipboard.writeText( value );
									}
								} }
							>
								<i className="bb-icons-rl bb-icons-rl-copy" />
							</button>
						) }
						{ field.maxlength > 0 && (
							<span className="bb-admin-settings-form__textarea-counter">
								{ ( value || '' ).length + '/' + field.maxlength }
							</span>
						) }
					</div>
				);
			}

			case 'textarea':
				return (
					<div className="bb-admin-settings-form__textarea-wrapper">
						<TextareaControl
							key={field.name}
							label=""
							value={value || ''}
							onChange={function( newValue ) {
								if ( field.maxlength && newValue.length > field.maxlength ) {
									newValue = newValue.substring( 0, field.maxlength );
								}
								onChange( field.name, newValue );
							}}
							placeholder={field.placeholder || ''}
							__nextHasNoMarginBottom
						/>
						{ field.maxlength > 0 && (
							<span className="bb-admin-settings-form__textarea-counter">
								{ ( value || '' ).length + '/' + field.maxlength }
							</span>
						) }
					</div>
				);

			case 'select': {
				// Apply fetch_on_change overrides (dynamic options from AJAX).
				var selectOverrides = fetchOnChange.getFieldOverrides( field.name );
				var selectOptions   = ( selectOverrides && selectOverrides.options ) ? selectOverrides.options : ( field.options || [] );
				var selectDisabled  = disabled || ( selectOverrides ? selectOverrides.disabled : false );
				var selectLoading   = selectOverrides && selectOverrides.loading;

				// Auto-select default value when fetch returns one and current value is empty.
				if ( selectOverrides && selectOverrides.defaultValue && ! value ) {
					onChange( field.name, selectOverrides.defaultValue );
				}

				if ( selectLoading ) {
					return (
						<SelectControl
							key={field.name}
							label=""
							value=""
							options={ [ { value: '', label: selectOverrides.loadingText || __( 'Loading...', 'buddyboss' ) } ] }
							disabled
							__nextHasNoMarginBottom
						/>
					);
				}

				return (
					<SelectControl
						key={field.name}
						label=""
						value={value != null ? String(value) : ''}
						options={selectOptions}
						onChange={(newValue) => onChange(field.name, newValue)}
						disabled={selectDisabled}
						__nextHasNoMarginBottom
					/>
				);
			}

			case 'async_select':
				return (
					<AsyncSelectField
						key={field.name}
						value={value != null ? String(value) : ''}
						onChange={(newValue) => onChange(field.name, newValue)}
						asyncAction={field.async_action || ''}
						placeholder={field.placeholder || ''}
						disabled={disabled}
					/>
				);

			case 'radio': {
				var radioOptions = field.options || [];
				var disabledOptionValues = radioOptions
					.filter( function ( opt ) { return !! opt.disabled; } )
					.map( function ( opt ) { return String( opt.value ); } );

				return (
					<div key={field.name} ref={ function ( el ) {
						if ( ! el ) {
							return;
						}
						// Apply field-level and per-option disabled states to radio inputs.
						el.querySelectorAll( 'input[type="radio"]' ).forEach( function ( input ) {
							var optionWrap = input.closest( '.components-radio-control__option' );
							if ( disabled || ( disabledOptionValues.length && -1 !== disabledOptionValues.indexOf( input.value ) ) ) {
								input.disabled = true;
								if ( optionWrap ) {
									optionWrap.style.opacity = '0.5';
									optionWrap.style.pointerEvents = 'none';
								}
							} else {
								input.disabled = false;
								if ( optionWrap ) {
									optionWrap.style.opacity = '';
									optionWrap.style.pointerEvents = '';
								}
							}
						} );
					} }>
						<RadioControl
							label=""
							selected={value != null ? String(value) : ''}
							options={radioOptions}
							onChange={ function ( newValue ) {
								if ( disabledOptionValues.length && -1 !== disabledOptionValues.indexOf( newValue ) ) {
									return;
								}
								onChange( field.name, newValue );
							} }
							disabled={disabled}
						/>
					</div>
				);
			}

			case 'number':
				return (
					<TextControl
						key={field.name}
						label=""
						value={value || 0}
						onChange={(newValue) => onChange(field.name, newValue)}
						type="number"
						min={field.min}
						max={field.max}
						__nextHasNoMarginBottom
					/>
				);

			case 'color':
				return (
					<input
						type="color"
						value={value || '#000000'}
						onChange={(e) => onChange(field.name, e.target.value)}
						className="bb-admin-settings-field__color-input"
					/>
				);

			case 'image_radio':
				return (
					<ImageRadioField
						field={field}
						value={value}
						onChange={onChange}
						disabled={disabled}
						descriptionHtml={ sanitizedHtml[ field.name + '__desc' ] || '' }
					/>
				);

			case 'toggle_list':
			case 'toggle_list_array':
				// Multiple stacked toggle switches (like Group Header Elements)
				// toggle_list: each option stored as separate WP option
				// toggle_list_array: stored as single array of enabled values

				// Extension list fields with "Add Extension" button use dedicated component.
				if ( field.allow_add && field.extension_data ) {
					return (
						<ExtensionListField
							field={field}
							value={value}
							onChange={onChange}
							disabled={disabled}
							sanitizedDescription={sanitizedHtml[ field.name + '__desc' ]}
						/>
					);
				}

				const listValue = typeof value === 'object' ? value : {};
				return (
					<div className="bb-admin-settings-field__toggle-list">
						{(field.options || []).map((option) => (
							<div key={option.value} className="bb-admin-settings-field__toggle-list-item">
								<ToggleControl
									label={option.label}
									checked={!!listValue[option.value]}
									onChange={(checked) => {
										const newValue = { ...listValue, [option.value]: checked ? 1 : 0 };
										onChange(field.name, newValue);
									}}
									disabled={disabled}
									__nextHasNoMarginBottom
								/>
							</div>
						))}
					</div>
				);

			case 'dimensions':
				return (
					<DimensionsField
						field={field}
						values={values}
						onChange={onChange}
					/>
				);

			case 'reaction_mode':
				// Delegate to ReactionModeField component
				return (
					<ReactionModeField
						field={field}
						value={value}
						values={values}
						onChange={onChange}
						defaultEmotionsRef={defaultEmotionsRef}
					/>
				);

			case 'reaction_button':
				return (
					<ReactionButtonField
						field={field}
						value={value}
						onChange={onChange}
					/>
				);

			case 'notice':
				// Notice field: displays an informational/warning/error notice banner.
				// Uses notice_type (info, warning, error, success) for styling.
				// Content is wrapped in a <span> so the icon (:before pseudo) and text
				// are two flex items — preventing <a> tags from creating extra columns.
				return (
					<div
						key={field.name}
						className={`bb-admin-notice bb-admin-notice--${field.notice_type || 'info'}`}
					>
						<span dangerouslySetInnerHTML={{ __html: sanitizedHtml[ field.name + '__desc' ] || '' }} />
					</div>
				);

			case 'empty_state':
				// Reusable empty state card: centered icon + title + description + optional button.
				// Used for placeholder states (e.g., OneSignal disabled, Pro update required,
				// feature not installed, upgrade prompts).
				//
				// PHP registration example:
				//   'type'                    => 'empty_state',
				//   'icon'                    => 'bb-icons-rl bb-icons-rl-warning-circle', // optional, default warning icon
				//   'empty_state_title'       => 'Title Text',
				//   'empty_state_description' => 'Description text (supports HTML via description field)',
				//   'button_label'            => 'Button Text',       // optional
				//   'button_url'              => 'https://...',        // optional
				//   'button_target'           => '_blank',             // optional, default '_self'
				//   'notice_type'             => 'warning',            // optional, adds modifier class
				return (
					<div key={field.name} className={ 'bb-admin-empty-state' + ( field.notice_type ? ' bb-admin-empty-state--' + field.notice_type : '' ) }>
						{ ( field.icon !== false ) && (
							<div className="bb-admin-empty-state__icon">
								<i className={ field.icon || 'bb-icons-rl bb-icons-rl-warning-circle' }></i>
							</div>
						) }
						{ field.empty_state_title && (
							<h3
								className="bb-admin-empty-state__title"
								dangerouslySetInnerHTML={{ __html: sanitizedHtml[ field.name + '__empty_title' ] || '' }}
							/>
						) }
						{ field.empty_state_description && (
							<p className="bb-admin-empty-state__description">
								{ decodeEntities( field.empty_state_description ) }
							</p>
						) }
						{ ( ! field.empty_state_description && field.description ) && (
							<div
								className="bb-admin-empty-state__description"
								dangerouslySetInnerHTML={{ __html: sanitizedHtml[ field.name + '__desc' ] || '' }}
							/>
						) }
						{ field.button_label && field.button_url && (
							<a
								href={ safeUrl( field.button_url ) }
								className="bb-admin-empty-state__button"
								target={ field.button_target || '_self' }
								rel={ '_blank' === field.button_target ? 'noopener noreferrer' : undefined }
							>
								{ field.button_label }
							</a>
						) }
					</div>
				);

			case 'reaction_migration': {
				// Reaction migration: warning notice for pending migration with "Start Conversion" button.
				// Check conditions here to avoid rendering empty wrapper div.
				const migrationData = field.migration_data || {};
				const migrationStatus = field.migration_status || '';
				// Don't show pending notice if migration is running or completed
				const isMigrationRunning = 'inprogress' === migrationStatus || 'running' === migrationData.status;
				const isMigrationCompleted = 'completed' === migrationStatus;
				const hasPendingMigration =
					migrationData.action &&
					migrationData.total_reactions > 0 &&
					!isMigrationRunning &&
					!isMigrationCompleted;

				if ( ! hasPendingMigration ) {
					return null;
				}

				return (
					<ReactionMigration
						key={field.name}
						field={field}
						onStartConversion={(migrationData) => {
							setCurrentMigrationData(migrationData);
							setIsMigrationModalOpen(true);
						}}
					/>
				);
			}

			case 'reaction_notice': {
				// Reaction notice: status display for in-progress or completed migrations.
				// Check conditions here to avoid rendering empty wrapper div.
				const noticeStatus = field.migration_status || '';
				const noticeMigrationData = field.migration_data || {};
				// Also check migration_data.status === 'running' as fallback for in-progress
				const isNoticeInProgress = 'inprogress' === noticeStatus || 'running' === noticeMigrationData.status;
				const isNoticeCompleted = 'completed' === noticeStatus;

				if ( !isNoticeInProgress && !isNoticeCompleted ) {
					return null;
				}

				return (
					<ReactionNotice
						key={field.name}
						field={field}
					/>
				);
			}

			case 'reaction_info':
				// Reaction info: informational text with inline link
				// Opens migration modal on click instead of navigating to separate page
				return (
					<ReactionInfo
						key={field.name}
						field={field}
						onOpenMigrationWizard={() => {
							setCurrentMigrationData({ wizardType: 'footer' });
							setIsMigrationModalOpen(true);
						}}
					/>
				);

			case 'topic_list':
				return (
					<TopicListField
						field={field}
						value={value}
						values={values}
						onChange={onChange}
					/>
				);

			case 'access_control':
				return (
					<AccessControlField
						field={field}
						value={value}
						onChange={(newValue) => onChange(field.name, newValue)}
					/>
				);

			case 'static_text':
				// Description-only field: renders the field row with label + description,
				// no input control. Used for informational text like OneSignal image hint.
				// Return empty string (truthy) so the field row renders, but no visible element.
				return '';

			case 'hidden':
				// With description_controls: render hidden span so the field row shows
				// and description_controls handles the inline select/input.
				// Without description_controls: return null to skip the entire field (true hidden).
				if ( field.description_controls && field.description_controls.length > 0 ) {
					return <span className="bb-admin-settings-field__control--hidden" aria-hidden="true" />;
				}
				return null;

			case 'document_extensions':
				return (
					<DocumentExtensionsField
						field={field}
						value={value}
						onChange={onChange}
						disabled={disabled}
					/>
				);

			case 'image_upload':
				return (
					<ImageUploadField
						uploadConfig={ field.upload_config || {} }
						uploadUrl={ value || '' }
						onUpload={ function ( newUrl ) {
							onChange( field.name, newUrl );
						} }
						onRemove={ function () {
							onChange( field.name, '' );
						} }
						disabled={ disabled }
					/>
				);

			case 'manage_link':
				return (
					<button
						type="button"
						className="bb-admin-settings-field__manage-btn"
						onClick={ function() {
							if ( field.manage_url ) {
								window.location.href = safeUrl( field.manage_url );
							}
						} }
						disabled={ disabled }
					>
						{ field.manage_icon && (
							<i className={ field.manage_icon } />
						) }
						<span>{ field.manage_label || __( 'Manage', 'buddyboss' ) }</span>
					</button>
				);

			case 'notification_types':
				// Delegate to NotificationTypesField component.
				return (
					<NotificationTypesField
						field={field}
						value={value}
						onChange={function( newValue ) { onChange( field.name, newValue ); }}
					/>
				);

			case 'domain_restrictions':
				return (
					<DomainRestrictionsField
						field={field}
						value={value}
						onChange={onChange}
						disabled={disabled}
					/>
				);

			case 'email_restrictions':
				return (
					<EmailRestrictionsField
						field={field}
						value={value}
						onChange={onChange}
						disabled={disabled}
					/>
				);

			case 'bb_verify_popup':
				return (
					<VerifyPopupField
						field={field}
						values={values}
						disabled={disabled}
					/>
				);

			default: {
				// Allow external plugins to render custom field types via wp.hooks.
				var customFieldComponent = wp.hooks.applyFilters(
					'bb_admin_settings_custom_field',
					null,
					field,
					value,
					function ( newValue ) {
						onChange( field.name, newValue );
					},
					disabled,
					values
				);

				if ( customFieldComponent ) {
					return customFieldComponent;
				}

				return (
					<p className="bb-admin-settings-field__unsupported">
						{__('Field type not yet supported in React UI.', 'buddyboss')}
					</p>
				);
			}
		}
	};

	/**
	 * Render a child field inline (without full row layout)
	 */
	const renderChildField = (field, parentDisabled = false) => {
		// Check visibility (for conditional fields)
		if (!isFieldVisible(field)) {
			return null;
		}

		const disabled = parentDisabled || !!field.disabled || isFieldDisabled(field) || isFieldConditionallyDisabled(field);

		// Checkbox children: render CheckboxControl with inline label (no separate label element).
		// When description_controls are present (e.g., "Auto hide after %s reports"),
		// render the checkbox with inline controls replacing %s placeholders.
		if ( 'checkbox' === field.type ) {
			const cbInverted = true === field.invert_value;
			const cbVal = values[ field.name ] !== undefined ? values[ field.name ] : field.default;
			const cbDisplay = cbInverted ? ! cbVal : !! cbVal;
			const cbDesc = field.description || '';
			const cbControls = field.description_controls;
			const cbHasControls = cbDesc.indexOf( '%s' ) !== -1 && cbControls && cbControls.length > 0;

			if ( cbHasControls ) {
				const cachedParts = sanitizedHtml[ field.name + '__parts' ];
				const parts = cachedParts || cbDesc.split( '%s' ).map( function ( part ) {
					return sanitizeHtml( part );
				} );
				const cbControlDisabled = disabled || ! cbDisplay;

				return (
					<div key={field.name} className={`bb-admin-settings-form__child-field bb-admin-settings-form__child-field--checkbox bb-admin-settings-form__child-field--has-controls ${disabled ? 'bb-admin-settings-form__child-field--disabled' : ''}`}>
						<CheckboxControl
							checked={ cbDisplay }
							onChange={ function( checked ) {
								var saveVal = cbInverted ? ! checked : checked;
								onChange( field.name, saveVal ? 1 : 0 );
							} }
							disabled={ disabled }
							__nextHasNoMarginBottom
						/>
						<span className="bb-admin-settings-form__child-field-inline-desc">
							{ parts.map( function ( part, index ) {
								var control = index < cbControls.length ? cbControls[ index ] : null;
								var controlName = control ? control.name : null;
								var controlDefault = control ? ( control.value ?? control.default ?? '' ) : '';
								var controlVal = controlName && values[ controlName ] !== undefined
									? values[ controlName ]
									: controlDefault;

								return (
									<span key={ index }>
										<span dangerouslySetInnerHTML={ { __html: part } } />
										{ control && 'number' === control.type && (
											<input
												type="number"
												name={ controlName }
												className="bb-admin-settings-form__inline-number"
												value={ controlVal }
												min={ control.min }
												max={ control.max }
												step={ control.step }
												aria-label={ controlName }
												onChange={ function ( e ) { onChange( controlName, parseInt( e.target.value, 10 ) || 0 ); } }
												disabled={ cbControlDisabled }
											/>
										) }
										{ control && 'select' === control.type && (
											<select
												name={ controlName }
												className="bb-admin-settings-form__inline-select"
												value={ controlVal }
												onChange={ function ( e ) { onChange( controlName, e.target.value ); } }
												disabled={ cbControlDisabled }
											>
												{ ( control.options || [] ).map( function ( opt ) {
													return <option key={ opt.value } value={ opt.value }>{ opt.label }</option>;
												} ) }
											</select>
										) }
									</span>
								);
							} ) }
						</span>
					</div>
				);
			}

			return (
				<div key={field.name} className={`bb-admin-settings-form__child-field bb-admin-settings-form__child-field--checkbox ${disabled ? 'bb-admin-settings-form__child-field--disabled' : ''}`}>
					<CheckboxControl
						label={ field.label || cbDesc }
						checked={ cbDisplay }
						onChange={ function( checked ) {
							var saveVal = cbInverted ? ! checked : checked;
							onChange( field.name, saveVal ? 1 : 0 );
						} }
						disabled={ disabled }
						__nextHasNoMarginBottom
					/>
				</div>
			);
		}

		// Toggle children: render ToggleControl with inline label (toggle + label on same row).
		if ( 'toggle' === field.type ) {
			const tgInverted = true === field.invert_value;
			const tgVal = values[ field.name ] !== undefined ? values[ field.name ] : field.default;
			const tgDisplay = tgInverted ? ! tgVal : !! tgVal;
			return (
				<div key={field.name} className={`bb-admin-settings-form__child-field bb-admin-settings-form__child-field--toggle ${disabled ? 'bb-admin-settings-form__child-field--disabled' : ''}`}>
					<ToggleControl
						label={ field.label || field.description || '' }
						checked={ tgDisplay }
						onChange={ function( checked ) {
							var saveVal = tgInverted ? ! checked : checked;
							onChange( field.name, saveVal ? 1 : 0 );
						} }
						disabled={ disabled }
						__nextHasNoMarginBottom
					/>
				</div>
			);
		}

		var childClasses = [
			'bb-admin-settings-form__child-field',
			disabled ? 'bb-admin-settings-form__child-field--disabled' : '',
		].filter( Boolean ).join( ' ' );

		return (
			<div key={field.name} className={childClasses}>
				{field.label && (
					<label className="bb-admin-settings-form__child-field-label">{field.label}</label>
				)}
				<div className="bb-admin-settings-form__child-field-control">
					{renderFieldControl(field, disabled)}
				</div>
				{field.description && 'toggle' !== field.type && 'checkbox' !== field.type && (
					<p className="bb-admin-settings-form__child-field-description">{field.description}</p>
				)}
			</div>
		);
	};

	/**
	 * Render a single field row with optional prefix/suffix text
	 */
	const renderField = (field, isChild = false) => {
		// Check visibility (for conditional fields)
		if (!isFieldVisible(field)) {
			return null;
		}

		// Check if field should be disabled (parent toggle is OFF, field-level disabled flag, or conditional disable).
		const disabled = isFieldDisabled(field) || !!field.disabled || isFieldConditionallyDisabled(field);

		// Render the control first — if it returns null, skip the entire field row
		// unless the field has child fields (e.g., hidden parent used as a label-only grouping).
		const controlOutput = renderFieldControl(field, disabled);
		const childFields = fields.filter(f => f.parent_field === field.name);
		if ( null === controlOutput && 0 === childFields.length ) {
			return null;
		}

		// Notice fields and fields with explicit full_width render without the label column.
		// This includes standard notices, custom migration/info notice components,
		// and status checks marked as full_width (e.g., FFmpeg check).
		// Note: reaction_migration and reaction_notice handle their own wrapper internally
		// so they can return null without leaving an empty wrapper div.
		if ( 'notice' === field.type || 'reaction_info' === field.type || field.full_width ) {
			// Grouped notices render inline within their group (no full-width).
			if ( ! field.group?.key ) {
				return (
					<div key={field.name} className="bb-admin-settings-form__field bb-admin-settings-form__field--full-width">
						{controlOutput}
					</div>
				);
			}
		}

		// reaction_migration and reaction_notice return their own wrapper or null
		if ( 'reaction_migration' === field.type || 'reaction_notice' === field.type ) {
			return controlOutput;
		}

		// Check if this is a toggle with children (special layout)
		const isToggleWithChildren = ( 'toggle' === field.type || 'checkbox' === field.type ) && childFields.length > 0;

		// Build field class names
		const fieldClasses = [
			'bb-admin-settings-form__field',
			isChild ? 'bb-admin-settings-form__field--child' : '',
			field.parent_field ? 'bb-admin-settings-form__field--nested' : '',
			disabled ? 'bb-admin-settings-form__field--disabled' : '',
			isToggleWithChildren ? 'bb-admin-settings-form__field--has-children' : '',
			field.group?.key ? 'bb-admin-settings-form__field--grouped' : '',
			field.group?.key && groupLastNames[ field.group.key ] === field.name ? 'bb-admin-settings-form__field--group-last' : '',
			field.field_class || '',
		].filter(Boolean).join(' ');

		const hasLabel = field.label && field.label.trim() !== '';

		return (
			<div key={field.name} className={fieldClasses + ( ! hasLabel ? ' bb-admin-settings-form__field--no-label' : '' ) + ( 'reaction_mode' !== field.type && field.pro_notice?.show ? ' bb-admin-settings-form__field--pro-locked' : '' )} data-field-name={field.name} data-group={field.group?.key || undefined} data-group-inline={ field.group && field.group.inline ? 'true' : undefined }>
				{ hasLabel && (
					<div className="bb-admin-settings-form__field-label">
						<label htmlFor={ 'bb-field-' + field.name }>
							<span className="bb-admin-settings-form__field-label-text">{field.label}</span>
							{ 'reaction_mode' !== field.type && field.pro_notice?.show && (
								<>
									<span className="bb-pro-badge">
										<i className={field.pro_notice.badge_icon || ''} />
										<span>{field.pro_notice.badge_text || 'PRO'}</span>
									</span>
									{field.pro_notice.link_url && (
										<a
											href={safeUrl(field.pro_notice.link_url)}
											target="_blank"
											rel="noopener noreferrer"
											className="bb-pro-badge__play-link"
											aria-label={__('Learn more about PRO', 'buddyboss')}
										>
											<i className={field.pro_notice.link_icon || ''} />
										</a>
									)}
								</>
							)}
						</label>
					</div>
				)}
				<div className={ 'bb-admin-settings-form__field-content' + ( ( 'toggle' === field.type || 'checkbox' === field.type ) && field.description && ! isToggleWithChildren ? ' bb-admin-settings-form__field-content--inline' : '' ) }>
					{/* Group sub-label (e.g. "Width", "Height" within a grouped field) */}
					{ field.group?.label && (
						<label className="bb-admin-settings-form__field-group-label">{field.group.label}</label>
					) }
					{/* Field with optional prefix/suffix — skip wrapper when control is null (e.g., hidden parent fields). */}
					{ null !== controlOutput && false !== controlOutput && (
						<div className="bb-admin-settings-form__field-input-wrapper">
							{field.prefix && (
								<span className="bb-admin-settings-form__field-prefix">{field.prefix}</span>
							)}
							{controlOutput}
							{field.suffix && (
								<span className="bb-admin-settings-form__field-suffix">{field.suffix}</span>
							)}
						</div>
					) }

					{/* Description: skip for notice type (rendered by notice component itself).
				    When description contains %s and field has description_controls,
				    render inline controls (select, text, number) in place of each %s placeholder. */}
					{ field.description && -1 === [ 'notice', 'checkbox_list', 'share_platforms', 'topic_list', 'image_radio' ].indexOf( field.type ) && ! ( field.allow_add && field.extension_data ) && ( 'toggle' !== field.type || ( field.description_controls && field.description_controls.length > 0 ) ) && ( () => {
						const desc = field.description;
						const controls = field.description_controls;
						const hasControls = desc.indexOf( '%s' ) !== -1 && controls && controls.length > 0;

						if ( hasControls ) {
							const cachedParts = sanitizedHtml[ field.name + '__parts' ];
							const parts = cachedParts || desc.split( '%s' );
							const isToggleOff = ( 'toggle' === field.type || 'checkbox' === field.type ) && ! values[ field.name ];

							return (
								<p className="bb-admin-settings-form__field-description bb-admin-settings-form__field-description--has-controls">
									{ parts.map( ( part, index ) => {
										const control = index < controls.length ? controls[ index ] : null;
										// 'self' type: use the field's own name, options, and value.
										const isSelf = control && 'self' === control.type;
										const controlName = isSelf ? field.name : ( control ? control.name : null );
										const controlOptions = isSelf ? field.options : ( control ? control.options : [] );
										const controlDefault = isSelf ? field.default : ( control ? ( control.value ?? control.default ?? '' ) : '' );
										const controlVal = controlName && values[ controlName ] !== undefined
											? values[ controlName ]
											: controlDefault;
										const controlDisabled = disabled || isToggleOff;

										return (
											<span key={ index }>
												<span dangerouslySetInnerHTML={{ __html: cachedParts ? part : sanitizeHtml( part ) }} />
												{ control && ( control.type === 'select' || control.type === 'self' ) && (
													<select
														name={ controlName }
														className="bb-admin-settings-form__inline-select"
														value={ controlVal }
														onChange={ ( e ) => onChange( controlName, e.target.value ) }
														disabled={ controlDisabled }
													>
														{ ( controlOptions || [] ).map( ( opt ) => (
															<option key={ opt.value } value={ opt.value }>{ opt.label }</option>
														) ) }
													</select>
												) }
												{ control && 'text' === control.type && (
													<input
														type="text"
														name={ controlName }
														className="bb-admin-settings-form__inline-text"
														value={ controlVal }
														onChange={ ( e ) => onChange( controlName, e.target.value ) }
														disabled={ controlDisabled }
													/>
												) }
												{ control && 'number' === control.type && (
													<input
														type="number"
														name={ controlName }
														className="bb-admin-settings-form__inline-number"
														value={ controlVal }
														min={ control.min }
														max={ control.max }
														step={ control.step }
														aria-label={ controlName }
														onChange={ ( e ) => onChange( controlName, parseInt( e.target.value, 10 ) || 0 ) }
														disabled={ controlDisabled }
													/>
												) }
											</span>
										);
									} ) }
								</p>
							);
						}

						// For fields with option_descriptions, use the description
						// matching the currently selected value (dynamic swap on change).
						var descKey = field.name + '__desc';
						var currentVal = values[ field.name ];
						if ( field.option_descriptions && currentVal != null ) {
							var optDescKey = field.name + '__optdesc__' + String( currentVal );
							if ( sanitizedHtml[ optDescKey ] ) {
								descKey = optDescKey;
							}
						}

						return (
							<p
								className="bb-admin-settings-form__field-description"
								dangerouslySetInnerHTML={{ __html: sanitizedHtml[ descKey ] || '' }}
							/>
						);
					} )() }

					{/* Help text: lighter sub-description below the main description. */}
					{ field.help_text && (
						<p
							className="bb-admin-settings-form__field-help-text"
							dangerouslySetInnerHTML={{ __html: sanitizedHtml[ field.name + '__help' ] || '' }}
						/>
					) }

					{/* Render child fields inline/nested */}
					{childFields.length > 0 && (
						<div className="bb-admin-settings-form__child-fields">
							{childFields.reduce(function (acc, childField, idx) {
								var groupLabel = childField.child_group_label || null;
								var prevLabel  = idx > 0 ? ( childFields[ idx - 1 ].child_group_label || null ) : null;

								// Insert a group heading when the label changes.
								if ( groupLabel && groupLabel !== prevLabel ) {
									acc.push(
										<div key={ 'group-label-' + groupLabel + '-' + idx } className="bb-admin-settings-form__child-group-label">
											{ groupLabel }
										</div>
									);
								}

								acc.push( renderChildField( childField, disabled ) );
								return acc;
							}, [])}
						</div>
					)}
				</div>
			</div>
		);
	};

	// Filter out child fields from top level (they'll be rendered inside their parents).
	const topLevelFields = fields.filter(field => !field.parent_field);

	// Compute which fields are the last visible field in their group (for CSS border).
	// Re-computed on every render since conditional visibility depends on current values.
	const groupLastNames = useMemo( function () {
		var visible = topLevelFields.filter( isFieldVisible );
		var lastMap = {};
		for ( var gi = visible.length - 1; gi >= 0; gi-- ) {
			var gf = visible[ gi ];
			if ( gf.group?.key && ! lastMap[ gf.group.key ] ) {
				lastMap[ gf.group.key ] = gf.name;
			}
		}
		return lastMap;
	}, [ topLevelFields, values ] ); // eslint-disable-line react-hooks/exhaustive-deps

	return (
		<>
			<div className="bb-admin-settings-form">
				{topLevelFields.map((field) => renderField(field))}
			</div>
			{isMigrationModalOpen && (
				<MigrationModal
					isOpen={isMigrationModalOpen}
					onClose={() => setIsMigrationModalOpen(false)}
					migrationData={currentMigrationData}
				/>
			)}
			<ConfirmToggleModal
				isOpen={confirmModalState.isOpen}
				message={confirmModalState.message}
				title={confirmModalState.title}
				confirmLabel={confirmModalState.confirmLabel}
				cancelLabel={confirmModalState.cancelLabel}
				isDestructive={confirmModalState.isDestructive}
				onConfirm={() => {
					onChange(confirmModalState.fieldName, confirmModalState.saveValue);
					setConfirmModalState({ isOpen: false, message: '', fieldName: '', saveValue: 0, title: '', confirmLabel: '', cancelLabel: '', isDestructive: false });
				}}
				onCancel={() => {
					setConfirmModalState({ isOpen: false, message: '', fieldName: '', saveValue: 0, title: '', confirmLabel: '', cancelLabel: '', isDestructive: false });
				}}
			/>
		</>
	);
}
