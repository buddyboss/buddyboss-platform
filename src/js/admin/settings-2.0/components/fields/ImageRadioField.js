/**
 * BuddyBoss Admin Settings 2.0 - ImageRadioField Component
 *
 * Visual radio cards (like Default Group Cover Image or Avatar).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect } from '@wordpress/element';
import { ImageUploadField } from './ImageUploadField';

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
	var [ selected, setSelected ] = useState( value );
	var [ uploadUrl, setUploadUrl ] = useState( field.upload_url || '' );

	// Sync local state when parent value changes (e.g., after save or initial load).
	useEffect( function () {
		setSelected( value );
	}, [ value ] );

	var handleClick = function ( optionValue ) {
		setSelected( optionValue );
		onChange( field.name, optionValue );
	};

	var uploadConditional = field.upload_config && field.upload_config.conditional;
	var showUpload = uploadConditional && selected === uploadConditional.value;

	return (
		<div className="bb-admin-settings-field__image-radio-wrapper">
		<div className="bb-admin-settings-field__image-radio">
			{ ( field.options || [] ).map( ( option ) => (
				<button
					key={ option.value }
					type="button"
					className={ `bb-admin-settings-field__image-radio-option ${ selected === option.value ? 'bb-admin-settings-field__image-radio-option--selected' : '' }` }
					onClick={ () => handleClick( option.value ) }
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
		{ showUpload && (
			<ImageUploadField
				uploadConfig={ field.upload_config }
				uploadUrl={ uploadUrl }
				onUpload={ function ( newUrl ) {
					setUploadUrl( newUrl );
				} }
				onRemove={ function () {
					setUploadUrl( '' );
				} }
				disabled={ disabled }
			/>
		) }
		</div>
	);
}
