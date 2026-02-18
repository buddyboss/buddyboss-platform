/**
 * BuddyBoss Admin Settings 2.0 - ImageRadioField Component
 *
 * Visual radio cards (like Default Group Cover Image or Avatar).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

/**
 * ImageRadioField component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field definition.
 * @param {*}        props.value    Current field value.
 * @param {Function} props.onChange Change handler (fieldName, newValue).
 * @param {boolean}  props.disabled Whether the field is disabled.
 * @returns {JSX.Element} ImageRadioField component.
 */
export function ImageRadioField( { field, value, onChange, disabled } ) {
	return (
		<div className="bb-admin-settings-field__image-radio">
			{ ( field.options || [] ).map( ( option ) => (
				<button
					key={ option.value }
					type="button"
					className={ `bb-admin-settings-field__image-radio-option ${ value === option.value ? 'bb-admin-settings-field__image-radio-option--selected' : '' }` }
					onClick={ () => onChange( field.name, option.value ) }
					disabled={ disabled }
				>
					<div className="bb-admin-settings-field__image-radio-preview">
						{/* Cover Image Icons */}
						{ 'cover-buddyboss' === option.image && (
							<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--buddyboss">
								<span className="dashicons dashicons-format-image"></span>
							</div>
						) }
						{ 'cover-none' === option.image && (
							<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--none">
								<span className="dashicons dashicons-no-alt"></span>
							</div>
						) }
						{ 'cover-custom' === option.image && (
							<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--custom">
								<span className="dashicons dashicons-admin-generic"></span>
							</div>
						) }
						{/* Avatar Icons */}
						{ 'avatar-buddyboss' === option.image && (
							<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--avatar-group">
								<span className="dashicons dashicons-groups"></span>
							</div>
						) }
						{ 'avatar-name' === option.image && (
							<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--avatar-name">
								<span className="bb-admin-settings-field__avatar-initials">BB</span>
							</div>
						) }
						{ 'avatar-custom' === option.image && (
							<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--custom">
								<span className="dashicons dashicons-admin-generic"></span>
							</div>
						) }
						{/* Header Style Previews */}
						{ 'header-left-group' === option.image && (
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
						) }
						{ 'header-centered-group' === option.image && (
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
						) }
					</div>
					<span className="bb-admin-settings-field__image-radio-label">{ option.label }</span>
				</button>
			) ) }
		</div>
	);
}
