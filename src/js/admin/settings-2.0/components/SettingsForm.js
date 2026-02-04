/**
 * BuddyBoss Admin Settings 2.0 - Settings Form Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import {
	ToggleControl,
	TextControl,
	TextareaControl,
	SelectControl,
	RadioControl,
	CheckboxControl,
	DropdownMenu,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ReactionModeField, useReactionCallbacks } from './reaction';

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
	const { serverEmotionsRef } = useReactionCallbacks(onChange, values);

	/**
	 * Check if a field should be visible based on its conditional logic
	 */
	const isFieldVisible = (field) => {
		// If field has a "conditional" property, check it
		if (field.conditional) {
			const condValue = values[field.conditional.field];
			// Only show if conditional field matches the expected value
			return condValue === field.conditional.value || condValue == field.conditional.value;
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
		const isParentInverted = parentField?.invert_value === true;

		// If parent_value is specified, check for exact match
		if (field.parent_value !== undefined) {
			return !(parentValue === field.parent_value || parentValue == field.parent_value);
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

    const BBIcon = ( { name } ) => (
        <span className={ `bb-icons-rl-${ name }` } />
    );

	/**
	 * Render the field input control
	 */
	const renderFieldControl = (field, disabled = false) => {
		const value = values[field.name] !== undefined ? values[field.name] : field.default;

		switch (field.type) {
			case 'toggle':
			case 'checkbox':
				// Figma: Toggle with toggle_label displayed next to the switch
				const toggleLabel = field.toggle_label || field.inline_label || '';
				// Handle inverted values (e.g., "disable" options shown as "enable" toggles)
				const isInverted = field.invert_value === true;
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

			case 'checkbox_list':
				// Checkbox list for multiple selections (e.g., Activity Feed Filters)
				// Value can be either:
				// - An object like {"just-me": 1, "favorites": 0, ...} (from AJAX)
				// - An array like ["just-me", "favorites", ...] (legacy)
				const isObjectValue = value && typeof value === 'object' && !Array.isArray(value);
				const checkboxValue = isObjectValue ? value : {};
				
				// Helper to check if option is selected
				const isOptionChecked = (optionKey) => {
					if (isObjectValue) {
						// Object format: check if value is truthy (1, "1", true)
						return !!checkboxValue[optionKey] && checkboxValue[optionKey] !== '0' && checkboxValue[optionKey] !== 0;
					}
					// Array format
					return Array.isArray(value) && value.includes(optionKey);
				};
				
				return (
					<div key={field.name} className="bb-admin-settings-field__checkbox-list">
						{(field.options || []).map((option) => (
							<CheckboxControl
								key={option.value}
								label={option.label}
								checked={isOptionChecked(option.value)}
								onChange={(checked) => {
									// Always save as object format for consistency
									const newValue = { ...checkboxValue, [option.value]: checked ? 1 : 0 };
									onChange(field.name, newValue);
								}}
								__nextHasNoMarginBottom
							/>
						))}
					</div>
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
						type={field.type === 'email' ? 'email' : field.type === 'url' ? 'url' : 'text'}
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
				// Visual radio cards (like Default Group Cover Image or Avatar)
				return (
					<div className="bb-admin-settings-field__image-radio">
						{(field.options || []).map((option) => (
							<button
								key={option.value}
								type="button"
								className={`bb-admin-settings-field__image-radio-option ${value === option.value ? 'bb-admin-settings-field__image-radio-option--selected' : ''}`}
								onClick={() => onChange(field.name, option.value)}
								disabled={disabled}
							>
								<div className="bb-admin-settings-field__image-radio-preview">
									{/* Cover Image Icons */}
									{option.image === 'cover-buddyboss' && (
										<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--buddyboss">
											<span className="dashicons dashicons-format-image"></span>
										</div>
									)}
									{option.image === 'cover-none' && (
										<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--none">
											<span className="dashicons dashicons-no-alt"></span>
										</div>
									)}
									{option.image === 'cover-custom' && (
										<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--custom">
											<span className="dashicons dashicons-admin-generic"></span>
										</div>
									)}
									{/* Avatar Icons */}
									{option.image === 'avatar-buddyboss' && (
										<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--avatar-group">
											<span className="dashicons dashicons-groups"></span>
										</div>
									)}
									{option.image === 'avatar-name' && (
										<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--avatar-name">
											<span className="bb-admin-settings-field__avatar-initials">BB</span>
										</div>
									)}
									{option.image === 'avatar-custom' && (
										<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--custom">
											<span className="dashicons dashicons-admin-generic"></span>
										</div>
									)}
									{/* Header Style Previews */}
									{option.image === 'header-left-group' && (
										<div className="bb-admin-settings-field__header-preview bb-admin-settings-field__header-preview--left">
											<div className="bb-admin-settings-field__header-preview-cover"></div>
											<div className="bb-admin-settings-field__header-preview-content">
												<div className="bb-admin-settings-field__header-preview-avatar">
													<span className="dashicons dashicons-groups"></span>
												</div>
												<div className="bb-admin-settings-field__header-preview-lines">
													<div className="bb-admin-settings-field__header-preview-line bb-admin-settings-field__header-preview-line--short"></div>
													<div className="bb-admin-settings-field__header-preview-line bb-admin-settings-field__header-preview-line--long"></div>
												</div>
											</div>
										</div>
									)}
									{option.image === 'header-centered-group' && (
										<div className="bb-admin-settings-field__header-preview bb-admin-settings-field__header-preview--centered">
											<div className="bb-admin-settings-field__header-preview-cover"></div>
											<div className="bb-admin-settings-field__header-preview-content">
												<div className="bb-admin-settings-field__header-preview-avatar">
													<span className="dashicons dashicons-groups"></span>
												</div>
												<div className="bb-admin-settings-field__header-preview-lines">
													<div className="bb-admin-settings-field__header-preview-line bb-admin-settings-field__header-preview-line--short"></div>
													<div className="bb-admin-settings-field__header-preview-line bb-admin-settings-field__header-preview-line--long"></div>
												</div>
											</div>
										</div>
									)}
								</div>
								<span className="bb-admin-settings-field__image-radio-label">{option.label}</span>
							</button>
						))}
					</div>
				);

			case 'toggle_list':
			case 'toggle_list_array':
				// Multiple stacked toggle switches (like Group Header Elements)
				// toggle_list: each option stored as separate WP option
				// toggle_list_array: stored as single array of enabled values
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
				// Dimensions field (Width x Height in one row)
				const subFields = field.fields || [];
				return (
					<div className="bb-admin-settings-field__dimensions">
						{subFields.map((subField, index) => {
							const subValue = values[subField.name] !== undefined ? values[subField.name] : subField.default;
							return (
								<div key={subField.name} className="bb-admin-settings-field__dimension-item">
									<label className="bb-admin-settings-field__dimension-label">{subField.label}</label>
									<div className="bb-admin-settings-field__dimension-input-wrap">
										<input
											type="number"
											value={subValue || ''}
											onChange={(e) => onChange(subField.name, e.target.value)}
											min={subField.min}
											max={subField.max}
											className="bb-admin-settings-field__dimension-input"
										/>
										{subField.suffix && (
											<span className="bb-admin-settings-field__dimension-suffix">{subField.suffix}</span>
										)}
									</div>
									{index < subFields.length - 1 && (
										<span className="bb-admin-settings-field__dimension-separator">×</span>
									)}
								</div>
							);
						})}
					</div>
				);

			case 'child_render':
				// Child render field - renders child fields inline (e.g., Width select + Height select)
				const childFields = field.fields || [];
				return (
					<div className="bb-admin-settings-field__child-render">
						{childFields.map((childField) => {
							const childValue = values[childField.name] !== undefined ? values[childField.name] : childField.default;

							// Render based on child field type
							const renderChildControl = () => {
								switch (childField.type) {
									case 'select':
										return (
											<SelectControl
												value={childValue || ''}
												options={childField.options || []}
												onChange={(newValue) => onChange(childField.name, newValue)}
												__nextHasNoMarginBottom
											/>
										);
									case 'number':
										return (
											<div className="bb-admin-settings-field__child-number-wrap">
												<input
													type="number"
													value={childValue || ''}
													onChange={(e) => onChange(childField.name, e.target.value)}
													min={childField.min}
													max={childField.max}
													className="bb-admin-settings-field__child-number-input"
												/>
												{childField.suffix && (
													<span className="bb-admin-settings-field__child-suffix">{childField.suffix}</span>
												)}
											</div>
										);
									case 'text':
									default:
										return (
											<TextControl
												value={childValue || ''}
												onChange={(newValue) => onChange(childField.name, newValue)}
												__nextHasNoMarginBottom
											/>
										);
								}
							};

							return (
								<div key={childField.name} className="bb-admin-settings-field__child-item">
									<label className="bb-admin-settings-field__child-label">{childField.label}</label>
									<div className="bb-admin-settings-field__child-control">
										{renderChildControl()}
									</div>
								</div>
							);
						})}
					</div>
				);

			case 'reaction_mode':
				// Delegate to ReactionModeField component
				return (
					<ReactionModeField
						field={field}
						value={value}
						values={values}
						onChange={onChange}
						serverEmotionsRef={serverEmotionsRef}
					/>
				);

			case 'reaction_button':
				// Reaction button: Pro-only field.
				const isProLocked = !!field.pro_notice?.show;

				// Get button settings (icon and text)
				const buttonValue = value || {};
				const buttonIcon = buttonValue.icon || field.icon || 'thumbs-up';
				const buttonText = buttonValue.text || field.text || __('Like', 'buddyboss');
				return (
					<div key={field.name} className={`bb-reaction-button-field${isProLocked ? ' bb-reaction-button-field--disabled' : ''}`}>
						<div className="bb-reaction-button-card">
							<div className="bb-reaction-button-card__preview">
								<div className="bb-reaction-button-card__icon-wrapper">
									<button
										type="button"
										className="bb-reaction-button-card__icon-btn"
										id="bb-reaction-button-chooser"
										disabled={isProLocked}
									>
										<i className={`bb-icon-rf bb-icon-${buttonIcon}`}></i>
									</button>
								</div>
								<div className="bb-reaction-button-card__footer">
									<input
										name="bb_reactions_button[text]"
										id="bb-reaction-button-text"
										type="text"
										maxLength="12"
										value={buttonText}
										placeholder={__('Like', 'buddyboss')}
										className="bb-reaction-button-card__text-input"
										disabled={isProLocked}
										readOnly={isProLocked}
									/>
									<DropdownMenu
										icon={ <i className="bb-icons-rl-dots-three"></i> }
										label={ __( 'More options', 'buddyboss' ) }
										className="bb-reaction-button-card__menu-btn"
									>
										{ ( { onClose } ) => (
											<MenuGroup className="bb_dropdown_menu_group">
												<MenuItem
													icon={ <BBIcon name="note-pencil" /> }
													iconPosition="left"
													onClick={ () => {
														onClose();
													} }
												>
													{ __( 'Edit', 'buddyboss' ) }
												</MenuItem>
												<MenuItem
													icon={ <BBIcon name="trash" /> }
													iconPosition="left"
													onClick={ () => {
														onClose();
													} }
												>
													{ __( 'Delete', 'buddyboss' ) }
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
								value={buttonIcon}
							/>
						</div>
					</div>
				);

			case 'notice':
				// Notice field: displays an informational/warning/error notice banner.
				// Uses notice_type (info, warning, error, success) for styling.
				return (
					<div
						key={field.name}
						className={`bb-admin-notice bb-admin-notice--${field.notice_type || 'info'}`}
						dangerouslySetInnerHTML={{ __html: field.description }}
					/>
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

		// Check if field should be disabled (parent toggle is OFF)
		const disabled = isFieldDisabled(field);

		// Render the control first — if it returns null, skip the entire field row.
		const controlOutput = renderFieldControl(field, disabled);
		if (controlOutput === null) {
			return null;
		}

		// Notice fields render full-width without the label column.
		if (field.type === 'notice') {
			return (
				<div key={field.name} className="bb-admin-settings-form__field bb-admin-settings-form__field--full-width">
					{controlOutput}
				</div>
			);
		}

		// Get child fields that depend on this field
		const childFields = fields.filter(f => f.parent_field === field.name);

		// Check if this is a toggle with children (special layout)
		const isToggleWithChildren = (field.type === 'toggle' || field.type === 'checkbox') && childFields.length > 0;

		// Build field class names
		const fieldClasses = [
			'bb-admin-settings-form__field',
			isChild ? 'bb-admin-settings-form__field--child' : '',
			field.parent_field ? 'bb-admin-settings-form__field--nested' : '',
			disabled ? 'bb-admin-settings-form__field--disabled' : '',
			isToggleWithChildren ? 'bb-admin-settings-form__field--has-children' : '',
		].filter(Boolean).join(' ');

		return (
			<div key={field.name} className={fieldClasses + (field.type !== 'reaction_mode' && field.pro_notice?.show ? ' bb-admin-settings-form__field--pro-locked' : '')}>
				<div className="bb-admin-settings-form__field-label">
					<label>
						<span className="bb-admin-settings-form__field-label-text">{field.label}</span>
						{field.type !== 'reaction_mode' && field.pro_notice?.show && (
							<>
								<span className="bb-pro-badge">
									<i className={field.pro_notice.badge_icon || ''} />
									<span>{field.pro_notice.badge_text || 'PRO'}</span>
								</span>
								{field.pro_notice.link_url && (
									<a
										href={field.pro_notice.link_url}
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
				<div className="bb-admin-settings-form__field-content">
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

					{/* Description (skip for notice type — already rendered inside the field) */}
					{field.description && field.type !== 'notice' && (
						<p
							className="bb-admin-settings-form__field-description"
							dangerouslySetInnerHTML={{ __html: field.description }}
						/>
					)}

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
		<div className="bb-admin-settings-form">
			{topLevelFields.map((field) => renderField(field))}
		</div>
	);
}
