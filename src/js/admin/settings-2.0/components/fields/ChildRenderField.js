/**
 * BuddyBoss Admin Settings 2.0 - ChildRenderField Component
 *
 * Renders child fields inline (e.g., Width select + Height select).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { SelectControl, TextControl, ToggleControl } from '@wordpress/components';

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
						case 'toggle':
							// ToggleControl renders its own label inline — return full markup
							// to avoid the external label/control wrapper used by other types.
							return null;
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

				// Toggle sub-fields use ToggleControl with its built-in label for same-line layout.
				if ( 'toggle' === childField.type ) {
					return (
						<div key={ childField.name } className="bb-admin-settings-field__child-item bb-admin-settings-field__child-item--toggle">
							<ToggleControl
								label={ childField.label }
								checked={ !! childValue }
								onChange={ ( newValue ) => {
									if ( ! childField.disabled ) {
										onChange( childField.name, newValue );
									}
								} }
								disabled={ !! childField.disabled }
								__nextHasNoMarginBottom
							/>
						</div>
					);
				}

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
