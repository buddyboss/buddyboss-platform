/**
 * BuddyBoss Admin Settings 2.0 - Settings Form Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useMemo } from '@wordpress/element';
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
import { sanitizeHtml, safeUrl } from '../utils/sanitize';
import { TopicListField } from './activity/topics/topic-list';
import { SharePlatformsField } from './activity/sharing';
import { AccessControlField } from './access-control/AccessControlField';
import { CheckboxListField } from './fields/CheckboxListField';
import { ImageRadioField } from './fields/ImageRadioField';
import { ChildRenderField } from './fields/ChildRenderField';
import { DimensionsField } from './fields/DimensionsField';
import { ExtensionListField } from './fields/ExtensionListField';
import { DocumentExtensionsField } from './fields/DocumentExtensionsField';

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

	// Track migration modal state
	const [isMigrationModalOpen, setIsMigrationModalOpen] = useState(false);
	const [currentMigrationData, setCurrentMigrationData] = useState(null);

	// Memoize sanitized HTML to avoid DOMParser overhead on every re-render.
	const sanitizedHtml = useMemo( () => {
		const cache = {};
		fields.forEach( ( field ) => {
			if ( field.description ) {
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
		} );
		return cache;
	}, [ fields ] );

	/**
	 * Check if a field should be visible based on its conditional logic
	 */
	const isFieldVisible = (field) => {
		// If field has a "conditional" property, check it
		if (field.conditional) {
			const condValue = values[field.conditional.field];
			const expectedValue = field.conditional.value;

			// When expected value is boolean, use truthy/falsy comparison
			// because DB values can be 1, 0, "1", "0" while conditional uses true/false.
			if (expectedValue === true || expectedValue === false) {
				const isTruthy = !!condValue && condValue !== '0' && condValue !== 0;
				return isTruthy === expectedValue;
			}

			// For non-boolean values, use strict comparison.
			return condValue === expectedValue;
		}

		// For parent_field (nesting), always show the field but it may be disabled
		// Fields with parent_field are rendered as children of their parent
		return true;
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
				// Figma: Toggle with toggle_label displayed next to the switch
				const toggleLabel = field.toggle_label || field.inline_label || '';
				// Handle inverted values (e.g., "disable" options shown as "enable" toggles)
				const isInverted = true === field.invert_value;
				const displayValue = isInverted ? !value : !!value;
				return (
					<div className="bb-admin-settings-form__toggle-wrapper">
						<ToggleControl
							key={field.name}
							label={toggleLabel}
							checked={displayValue}
							onChange={(checked) => {
								// If inverted, save the opposite of what's displayed
								const saveValue = isInverted ? !checked : checked;
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

			case 'text':
			case 'email':
			case 'url':
				return (
					<TextControl
						key={field.name}
						label=""
						value={value || ''}
						onChange={(newValue) => onChange(field.name, newValue)}
						type={ 'email' === field.type ? 'email' : 'url' === field.type ? 'url' : 'text' }
						disabled={disabled}
						__nextHasNoMarginBottom
					/>
				);

			case 'textarea':
				return (
					<TextareaControl
						key={field.name}
						label=""
						value={value || ''}
						onChange={(newValue) => onChange(field.name, newValue)}
						__nextHasNoMarginBottom
					/>
				);

			case 'select':
				return (
					<SelectControl
						key={field.name}
						label=""
						value={value || ''}
						options={field.options || []}
						onChange={(newValue) => onChange(field.name, newValue)}
						disabled={disabled}
						__nextHasNoMarginBottom
					/>
				);

			case 'radio':
				return (
					<RadioControl
						key={field.name}
						label=""
						selected={value || ''}
						options={field.options || []}
						onChange={(newValue) => onChange(field.name, newValue)}
					/>
				);

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

			case 'child_render':
				return (
					<ChildRenderField
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
				return (
					<div
						key={field.name}
						className={`bb-admin-notice bb-admin-notice--${field.notice_type || 'info'}`}
						dangerouslySetInnerHTML={{ __html: sanitizedHtml[ field.name + '__desc' ] || '' }}
					/>
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

			case 'manage_link':
				return (
					<button
						type="button"
						className="bb-admin-settings-field__manage-btn"
						onClick={ function() {
							if ( field.manage_url ) {
								window.location.href = field.manage_url;
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

			default:
				return (
					<p className="bb-admin-settings-field__unsupported">
						{__('Field type not yet supported in React UI.', 'buddyboss')}
					</p>
				);
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

		const disabled = parentDisabled || isFieldDisabled(field);

		return (
			<div key={field.name} className={`bb-admin-settings-form__child-field ${disabled ? 'bb-admin-settings-form__child-field--disabled' : ''}`}>
				{field.label && (
					<label className="bb-admin-settings-form__child-field-label">{field.label}</label>
				)}
				<div className="bb-admin-settings-form__child-field-control">
					{renderFieldControl(field, disabled)}
				</div>
				{field.description && (
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

		// Check if field should be disabled (parent toggle is OFF or field-level disabled flag).
		const disabled = isFieldDisabled(field) || !!field.disabled;

		// Render the control first — if it returns null, skip the entire field row.
		const controlOutput = renderFieldControl(field, disabled);
		if ( null === controlOutput ) {
			return null;
		}

		// Notice fields render full-width without the label column.
		// This includes standard notices and custom migration/info notice components.
		// Note: reaction_migration and reaction_notice handle their own wrapper internally
		// so they can return null without leaving an empty wrapper div.
		if ( 'notice' === field.type || 'reaction_info' === field.type ) {
			// Grouped notices render inline within their group (no full-width).
			if ( ! field.group ) {
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

		// Get child fields that depend on this field
		const childFields = fields.filter(f => f.parent_field === field.name);

		// Check if this is a toggle with children (special layout)
		const isToggleWithChildren = ( 'toggle' === field.type || 'checkbox' === field.type ) && childFields.length > 0;

		// Build field class names
		const fieldClasses = [
			'bb-admin-settings-form__field',
			isChild ? 'bb-admin-settings-form__field--child' : '',
			field.parent_field ? 'bb-admin-settings-form__field--nested' : '',
			disabled ? 'bb-admin-settings-form__field--disabled' : '',
			isToggleWithChildren ? 'bb-admin-settings-form__field--has-children' : '',
			field.group ? 'bb-admin-settings-form__field--grouped' : '',
		].filter(Boolean).join(' ');

		const hasLabel = field.label && field.label.trim() !== '';

		return (
			<div key={field.name} className={fieldClasses + ( ! hasLabel ? ' bb-admin-settings-form__field--no-label' : '' ) + ( 'reaction_mode' !== field.type && field.pro_notice?.show ? ' bb-admin-settings-form__field--pro-locked' : '' )} data-group={field.group || undefined}>
				{ hasLabel && (
					<div className="bb-admin-settings-form__field-label">
						<label>
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
				<div className={ 'bb-admin-settings-form__field-content' + ( ( 'toggle' === field.type || 'checkbox' === field.type ) && field.description ? ' bb-admin-settings-form__field-content--inline' : '' ) }>
					{/* Field with optional prefix/suffix */}
					<div className="bb-admin-settings-form__field-input-wrapper">
						{field.prefix && (
							<span className="bb-admin-settings-form__field-prefix">{field.prefix}</span>
						)}
						{controlOutput}
						{field.suffix && (
							<span className="bb-admin-settings-form__field-suffix">{field.suffix}</span>
						)}
					</div>

					{/* Description: skip for notice type (rendered by notice component itself).
				    When description contains %s and field has description_controls,
				    render inline controls (select, text, number) in place of each %s placeholder. */}
					{ field.description && -1 === [ 'notice', 'checkbox_list', 'share_platforms', 'topic_list' ].indexOf( field.type ) && ! ( field.allow_add && field.extension_data ) && ( () => {
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
														onChange={ ( e ) => onChange( controlName, e.target.value ) }
														disabled={ controlDisabled }
													/>
												) }
											</span>
										);
									} ) }
								</p>
							);
						}

						return (
							<p
								className="bb-admin-settings-form__field-description"
								dangerouslySetInnerHTML={{ __html: sanitizedHtml[ field.name + '__desc' ] || '' }}
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
							{childFields.map(childField => renderChildField(childField, disabled))}
						</div>
					)}
				</div>
			</div>
		);
	};

	// Filter out child fields from top level (they'll be rendered inside their parents)
	const topLevelFields = fields.filter(field => !field.parent_field);

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
		</>
	);
}
