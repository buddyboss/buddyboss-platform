import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

// Placeholder ImageUploader component
export const ImageUploader = ({ label, value, onChange, description }) => {
	// TODO: Implement actual image upload logic using WordPress Media Library

	const handleUploadClick = () => {
		// Placeholder: Simulate selecting an image
		console.log(`Upload initiated for ${label}`);
		// Replace with actual media library opening and handling
		// Example: onChange({ url: 'path/to/uploaded/image.jpg', id: 123 }); 
	};

	const handleRemoveClick = () => {
		onChange(null); // Clear the image
	};

	return (
		<div className="image-uploader-component">
			<label>{label}</label>
			<div className="image-uploader-control">
				{value && value.url ? (
					<div className="image-preview-wrapper">
						<img src={value.url} alt={label} className="image-preview" />
						<Button isSmall isDestructive onClick={handleRemoveClick}>
							{__('Remove', 'buddyboss')}
						</Button>
					</div>
				) : (
					<Button variant="secondary" onClick={handleUploadClick} className="upload-button">
						<span className="dashicons dashicons-plus"></span>
					</Button>
				)}
			</div>
			{description && <p className="field-description">{description}</p>}
		</div>
	);
}; 