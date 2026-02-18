/**
 * BuddyBoss Admin Settings 2.0 - ChildRenderField Component
 *
 * Renders child fields inline (e.g., Width select + Height select).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { SelectControl, TextControl } from '@wordpress/components';

/**
 * ChildRenderField component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field definition.
 * @param {Object}   props.values   All current field values.
 * @param {Function} props.onChange Change handler (fieldName, newValue).
 * @returns {JSX.Element} ChildRenderField component.
 */
export function ChildRenderField( { field, values, onChange } ) {
	var childFields = field.fields || [];
	return (
		<div className="bb-admin-settings-field__child-render">
			{ childFields.map( ( childField ) => {
				var childValue = values[ childField.name ] !== undefined ? values[ childField.name ] : childField.default;

				// Render based on child field type.
				var renderChildControl = function () {
					switch ( childField.type ) {
						case 'select':
							return (
								<SelectControl
									value={ childValue || '' }
									options={ childField.options || [] }
									onChange={ ( newValue ) => onChange( childField.name, newValue ) }
									__nextHasNoMarginBottom
								/>
							);
						case 'number':
							return (
								<div className="bb-admin-settings-field__child-number-wrap">
									<input
										type="number"
										value={ childValue || '' }
										onChange={ ( e ) => onChange( childField.name, e.target.value ) }
										min={ childField.min }
										max={ childField.max }
										className="bb-admin-settings-field__child-number-input"
									/>
									{ childField.suffix && (
										<span className="bb-admin-settings-field__child-suffix">{ childField.suffix }</span>
									) }
								</div>
							);
						case 'text':
						default:
							return (
								<TextControl
									value={ childValue || '' }
									onChange={ ( newValue ) => onChange( childField.name, newValue ) }
									__nextHasNoMarginBottom
								/>
							);
					}
				};

				return (
					<div key={ childField.name } className="bb-admin-settings-field__child-item">
						<label className="bb-admin-settings-field__child-label">{ childField.label }</label>
						<div className="bb-admin-settings-field__child-control">
							{ renderChildControl() }
						</div>
					</div>
				);
			} ) }
		</div>
	);
}
