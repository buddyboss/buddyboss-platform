/**
 * BuddyBoss Admin Settings 2.0 - DimensionsField Component
 *
 * Dimensions field (Width x Height in one row).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

/**
 * DimensionsField component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field definition.
 * @param {Object}   props.values   All current field values.
 * @param {Function} props.onChange Change handler (fieldName, newValue).
 * @returns {JSX.Element} DimensionsField component.
 */
export function DimensionsField( { field, values, onChange } ) {
	var subFields = field.fields || [];
	return (
		<div className="bb-admin-settings-field__dimensions">
			{ subFields.map( ( subField, index ) => {
				var subValue = values[ subField.name ] !== undefined ? values[ subField.name ] : subField.default;
				return (
					<div key={ subField.name } className="bb-admin-settings-field__dimension-item">
						<label className="bb-admin-settings-field__dimension-label">{ subField.label }</label>
						<div className="bb-admin-settings-field__dimension-input-wrap">
							<input
								type="number"
								value={ subValue || '' }
								onChange={ ( e ) => onChange( subField.name, e.target.value ) }
								min={ subField.min }
								max={ subField.max }
								className="bb-admin-settings-field__dimension-input"
							/>
							{ subField.suffix && (
								<span className="bb-admin-settings-field__dimension-suffix">{ subField.suffix }</span>
							) }
						</div>
						{ index < subFields.length - 1 && (
							<span className="bb-admin-settings-field__dimension-separator">&times;</span>
						) }
					</div>
				);
			} ) }
		</div>
	);
}
