/**
 * BuddyBoss Admin Settings 2.0 - Group Type Modal
 *
 * Modal for creating/editing group types.
 * Design based on Figma: https://www.figma.com/design/XS2Hf0smlEnhWfoKyks7ku/Backend-Settings-2.0?node-id=2617-129542
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TextControl, SelectControl, CheckboxControl, Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Close icon component
 */
function CloseIcon() {
	return (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M18 6L6 18M6 6L18 18" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
		</svg>
	);
}

/**
 * Group Type Modal Component
 *
 * @param {Object} props Component props
 * @param {boolean} props.isOpen Whether the modal is open
 * @param {Function} props.onClose Close callback
 * @param {Function} props.onSave Save callback
 * @param {Object} props.groupType Existing group type data (for edit mode)
 * @returns {JSX.Element|null} Modal component
 */
export default function GroupTypeModal({ isOpen, onClose, onSave, groupType = null }) {
	const [formData, setFormData] = useState({
		name: '',
		singular_label: '',
		plural_label: '',
		show_in_directory: false,
		hide_from_directory: false,
		restrict_invites_by_type: false,
		profile_type_invites: [],
		profile_type_joining: [],
		visibility: 'public',
		label_color: 'default',
	});
	const [isSaving, setIsSaving] = useState(false);
	const [profileTypes, setProfileTypes] = useState([]);

	// Load profile types
	useEffect(() => {
		if (isOpen) {
			// Load available profile types from API
			// For now, using mock data
			setProfileTypes([
				{ value: 'teacher', label: __('Teacher', 'buddyboss') },
				{ value: 'student', label: __('Student', 'buddyboss') },
				{ value: 'male', label: __('Male', 'buddyboss') },
				{ value: 'female', label: __('Female', 'buddyboss') },
			]);
		}
	}, [isOpen]);

	// Initialize form data when editing
	useEffect(() => {
		if (groupType) {
			setFormData({
				name: groupType.name || '',
				singular_label: groupType.singular_label || '',
				plural_label: groupType.plural_label || '',
				show_in_directory: groupType.show_in_directory || false,
				hide_from_directory: groupType.hide_from_directory || false,
				restrict_invites_by_type: groupType.restrict_invites_by_type || false,
				profile_type_invites: groupType.profile_type_invites || [],
				profile_type_joining: groupType.profile_type_joining || [],
				visibility: groupType.visibility || 'public',
				label_color: groupType.label_color || 'default',
			});
		} else {
			// Reset form for new group type
			setFormData({
				name: '',
				singular_label: '',
				plural_label: '',
				show_in_directory: false,
				hide_from_directory: false,
				restrict_invites_by_type: false,
				profile_type_invites: [],
				profile_type_joining: [],
				visibility: 'public',
				label_color: 'default',
			});
		}
	}, [groupType, isOpen]);

	const handleInputChange = (field, value) => {
		setFormData((prev) => ({
			...prev,
			[field]: value,
		}));
	};

	const handleProfileTypeToggle = (field, value) => {
		setFormData((prev) => {
			const currentValues = prev[field] || [];
			const newValues = currentValues.includes(value)
				? currentValues.filter((v) => v !== value)
				: [...currentValues, value];
			return {
				...prev,
				[field]: newValues,
			};
		});
	};

	const handleSave = () => {
		setIsSaving(true);

		const nonce = bbAdminData?.nonce || '';
		const endpoint = groupType
			? `/buddyboss/v1/groups/types/${groupType.id}`
			: `/buddyboss/v1/groups/types`;
		const method = groupType ? 'PUT' : 'POST';

		apiFetch({
			path: endpoint,
			method: method,
			headers: {
				'X-WP-Nonce': nonce,
			},
			data: formData,
		})
			.then((response) => {
				setIsSaving(false);
				onSave(response);
				onClose();
			})
			.catch((error) => {
				console.error('Failed to save group type:', error);
				setIsSaving(false);
			});
	};

	if (!isOpen) {
		return null;
	}

	return (
		<div className="bb-admin-modal-overlay" onClick={onClose}>
			<div className="bb-admin-group-type-modal" onClick={(e) => e.stopPropagation()}>
				{/* Modal Header */}
				<div className="bb-admin-group-type-modal__header">
					<h2>{groupType ? __('Edit Group Type', 'buddyboss') : __('Add New Group Type', 'buddyboss')}</h2>
					<button className="bb-admin-group-type-modal__close" onClick={onClose} aria-label={__('Close', 'buddyboss')}>
						<CloseIcon />
					</button>
				</div>

				{/* Modal Body */}
				<div className="bb-admin-group-type-modal__body">
					{/* Name */}
					<div className="bb-admin-group-type-modal__section">
						<TextControl
							label={__('Name', 'buddyboss')}
							value={formData.name}
							onChange={(value) => handleInputChange('name', value)}
						/>
					</div>

					{/* Labels */}
					<div className="bb-admin-group-type-modal__section">
						<TextControl
							label={__('Singular Label', 'buddyboss')}
							value={formData.singular_label}
							onChange={(value) => handleInputChange('singular_label', value)}
						/>
						<TextControl
							label={__('Plural Label', 'buddyboss')}
							value={formData.plural_label}
							onChange={(value) => handleInputChange('plural_label', value)}
						/>
					</div>

					{/* Groups Directory Permissions */}
					<div className="bb-admin-group-type-modal__section">
						<div className="bb-admin-group-type-modal__section-label">
							{__('Groups Directory Permissions', 'buddyboss')}
						</div>
						<CheckboxControl
							label={__('Display this group type in "Types" filter in Groups Directory', 'buddyboss')}
							checked={formData.show_in_directory}
							onChange={(value) => handleInputChange('show_in_directory', value)}
						/>
						<CheckboxControl
							label={__('Hide all groups of this type from Groups Directory', 'buddyboss')}
							checked={formData.hide_from_directory}
							onChange={(value) => handleInputChange('hide_from_directory', value)}
						/>
					</div>

					{/* Group Type Invites */}
					<div className="bb-admin-group-type-modal__section">
						<div className="bb-admin-group-type-modal__section-label">
							{__('Group Type Invites', 'buddyboss')}
						</div>
						<CheckboxControl
							label={__("Members already in a group of this type can't be invited to another", 'buddyboss')}
							checked={formData.restrict_invites_by_type}
							onChange={(value) => handleInputChange('restrict_invites_by_type', value)}
						/>
					</div>

					{/* Profile Type Invites */}
					<div className="bb-admin-group-type-modal__section">
						<div className="bb-admin-group-type-modal__section-label">
							{__('Profile Type Invites', 'buddyboss')}
						</div>
						<div className="bb-admin-group-type-modal__checkboxes">
							{profileTypes.map((type) => (
								<CheckboxControl
									key={type.value}
									label={type.label}
									checked={formData.profile_type_invites.includes(type.value)}
									onChange={() => handleProfileTypeToggle('profile_type_invites', type.value)}
								/>
							))}
						</div>
						<p className="bb-admin-group-type-modal__description">
							{__('Only members of the selected profile types may be sent requests to join this group. (Leave blank for unrestricted invites).', 'buddyboss')}
						</p>
					</div>

					{/* Profile Type Joining */}
					<div className="bb-admin-group-type-modal__section">
						<div className="bb-admin-group-type-modal__section-label">
							{__('Profile Type Joining', 'buddyboss')}
						</div>
						<div className="bb-admin-group-type-modal__checkboxes">
							{profileTypes.map((type) => (
								<CheckboxControl
									key={type.value}
									label={type.label}
									checked={formData.profile_type_joining.includes(type.value)}
									onChange={() => handleProfileTypeToggle('profile_type_joining', type.value)}
								/>
							))}
						</div>
						<p className="bb-admin-group-type-modal__description">
							{__('Select which profile types can join private groups of this type without approval. Members restricted by Group Access settings cannot join, even if their profile type is allowed above.', 'buddyboss')}
						</p>
					</div>

					{/* Visibility */}
					<div className="bb-admin-group-type-modal__section">
						<SelectControl
							label={__('Visibility', 'buddyboss')}
							value={formData.visibility}
							options={[
								{ label: __('Public', 'buddyboss'), value: 'public' },
								{ label: __('Private', 'buddyboss'), value: 'private' },
								{ label: __('Hidden', 'buddyboss'), value: 'hidden' },
							]}
							onChange={(value) => handleInputChange('visibility', value)}
						/>
					</div>

					{/* Label Color */}
					<div className="bb-admin-group-type-modal__section bb-admin-group-type-modal__section--last">
						<SelectControl
							label={__('Label Color', 'buddyboss')}
							value={formData.label_color}
							options={[
								{ label: __('Default', 'buddyboss'), value: 'default' },
								{ label: __('Red', 'buddyboss'), value: 'red' },
								{ label: __('Blue', 'buddyboss'), value: 'blue' },
								{ label: __('Green', 'buddyboss'), value: 'green' },
								{ label: __('Yellow', 'buddyboss'), value: 'yellow' },
							]}
							onChange={(value) => handleInputChange('label_color', value)}
						/>
					</div>
				</div>

				{/* Modal Footer */}
				<div className="bb-admin-group-type-modal__footer">
					<Button
						variant="secondary"
						onClick={onClose}
						disabled={isSaving}
					>
						{__('Cancel', 'buddyboss')}
					</Button>
					<Button
						variant="primary"
						onClick={handleSave}
						disabled={isSaving || !formData.name}
						isBusy={isSaving}
					>
						{__('Save', 'buddyboss')}
					</Button>
				</div>
			</div>
		</div>
	);
}
