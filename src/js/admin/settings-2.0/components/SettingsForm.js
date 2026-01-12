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
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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
	
	/**
	 * Check if a field should be visible based on its conditional logic
	 */
	const isFieldVisible = (field) => {
		// If no parent_field, always show
		if (!field.parent_field) {
			return true;
		}
		
		// Check parent field value
		const parentValue = values[field.parent_field];
		
		// If parent_value is specified, check for exact match
		if (field.parent_value !== undefined) {
			return parentValue === field.parent_value || parentValue == field.parent_value;
		}
		
		// Default: show if parent is truthy (for toggles)
		return !!parentValue;
	};

	/**
	 * Render the field input control
	 */
	const renderFieldControl = (field) => {
		const value = values[field.name] !== undefined ? values[field.name] : field.default;

		switch (field.type) {
			case 'toggle':
			case 'checkbox':
				// Figma: Toggle with inline label on the right
				return (
					<ToggleControl
						key={field.name}
						label={field.inline_label || ''} // Support inline label like "Allow members to edit..."
						checked={!!value}
						onChange={(checked) => onChange(field.name, checked ? 1 : 0)}
						__nextHasNoMarginBottom
					/>
				);

			case 'checkbox_list':
				// Checkbox list for multiple selections (e.g., Activity Feed Filters)
				const selectedValues = Array.isArray(value) ? value : [];
				return (
					<div key={field.name} className="bb-admin-settings-field__checkbox-list">
						{(field.options || []).map((option) => (
							<CheckboxControl
								key={option.value}
								label={option.label}
								checked={selectedValues.includes(option.value)}
								onChange={(checked) => {
									let newValues;
									if (checked) {
										newValues = [...selectedValues, option.value];
									} else {
										newValues = selectedValues.filter((v) => v !== option.value);
									}
									onChange(field.name, newValues);
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

			default:
				return (
					<p className="bb-admin-settings-field__unsupported">
						{__('Field type not yet supported in React UI.', 'buddyboss')}
					</p>
				);
		}
	};

	/**
	 * Render a single field row with optional prefix/suffix text
	 */
	const renderField = (field, isChild = false) => {
		// Check visibility
		if (!isFieldVisible(field)) {
			return null;
		}

		// Get child fields that depend on this field
		const childFields = fields.filter(f => f.parent_field === field.name);

		// Build field class names
		const fieldClasses = [
			'bb-admin-settings-form__field',
			isChild ? 'bb-admin-settings-form__field--child' : '',
			field.parent_field ? 'bb-admin-settings-form__field--nested' : '',
		].filter(Boolean).join(' ');

		return (
			<div key={field.name} className={fieldClasses}>
				<div className="bb-admin-settings-form__field-label">
					<label>{field.label}</label>
				</div>
				<div className="bb-admin-settings-form__field-content">
					{/* Field with optional prefix/suffix */}
					<div className="bb-admin-settings-form__field-input-wrapper">
						{field.prefix && (
							<span className="bb-admin-settings-form__field-prefix">{field.prefix}</span>
						)}
						{renderFieldControl(field)}
						{field.suffix && (
							<span className="bb-admin-settings-form__field-suffix">{field.suffix}</span>
						)}
					</div>
					
					{/* Description */}
					{field.description && (
						<p className="bb-admin-settings-form__field-description">{field.description}</p>
					)}
					
					{/* Render child fields inline/nested */}
					{childFields.length > 0 && (
						<div className="bb-admin-settings-form__child-fields">
							{childFields.map(childField => renderField(childField, true))}
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
