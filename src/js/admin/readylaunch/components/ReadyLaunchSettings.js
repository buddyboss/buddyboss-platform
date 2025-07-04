import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ToggleControl, TextControl, Spinner, Notice, ColorPicker, RadioControl, Button, SelectControl, ColorIndicator, Popover } from '@wordpress/components';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import { Sidebar } from './Sidebar';
import { fetchSettings, saveSettings, debounce, fetchMenus, fetchHelpContent, clearHelpContentCache, fetchHelpCategories } from '../../utils/api';
import { Accordion } from '../../components/Accordion';
import { LinkItem } from '../../components/LinkItem';
import { LinkModal } from '../../components/LinkModal';
import { HelpIcon } from '../../components/HelpIcon';
import { HelpSliderModal } from '../../components/HelpSliderModal';
import { createInterpolateElement } from '@wordpress/element';
import { Toast } from '../../components/Toast';

// Initial structure for base menu items that are always included
const baseMenuItems = [
	{ id: 'members', label: __('Members', 'buddyboss'), icon: 'users', enabled: true, order: 1 },
];
if (window?.BP_ADMIN?.courses_integration === '1') {
	baseMenuItems.unshift({
		id: 'courses',
		label: __('Courses', 'buddyboss'),
		icon: 'graduation-cap',
		enabled: true,
		order: 1
	});
}

// Helper function to get component-based menu items
const getComponentMenuItems = () => {
	const items = [...baseMenuItems];
	let currentOrder = 0;

	// Add activity feed if component is active
	if (window?.BP_ADMIN?.components?.activity === 1) {
		items.unshift({
			id: 'activity_feed',
			label: __('Activity Feed', 'buddyboss'),
			icon: 'pulse',
			enabled: true,
			order: currentOrder++
		});
	}

	// Update order for base items
	items.forEach(item => {
		if (item.id === 'members') {
			item.order = currentOrder++;
		}
	});

	// Add groups if component is active
	if (window?.BP_ADMIN?.components?.groups === 1) {
		items.push({
			id: 'groups',
			label: __('Groups', 'buddyboss'),
			icon: 'users-three',
			enabled: true,
			order: currentOrder++
		});
	}

	// Add forums if component is active
	if (window?.BP_ADMIN?.components?.forums === 1) {
		items.push({
			id: 'forums',
			label: __('Forums', 'buddyboss'),
			icon: 'chat-text',
			enabled: true,
			order: currentOrder++
		});
	}

	// Update order for remaining base items
	items.forEach(item => {
		if (item.id === 'courses') {
			item.order = currentOrder++;
		}
	});

	// Add messages if component is active
	if (window?.BP_ADMIN?.components?.messages === 1) {
		items.push({
			id: 'messages',
			label: __('Messages', 'buddyboss'),
			icon: 'chat-teardrop-text',
			enabled: false,
			order: currentOrder++
		});
	}

	// Add notifications if component is active
	if (window?.BP_ADMIN?.components?.notifications === 1) {
		items.push({
			id: 'notifications',
			label: __('Notifications', 'buddyboss'),
			icon: 'bell',
			enabled: false,
			order: currentOrder
		});
	}

	return items;
};

// Helper function to safely check component status
const isComponentActive = (componentName) => {
	return window?.BP_ADMIN?.components?.[componentName] === 1;
};

const themeModeDescriptions = {
	light: __('The site will be shown in light mode.', 'buddyboss'),
	dark: __('The site will be shown in dark mode.', 'buddyboss'),
	choice: __('Users will be able switch between the modes.', 'buddyboss'),
};

export const ReadyLaunchSettings = () => {
	const [ activeTab, setActiveTab ] = useState( 'activation' );
	const [ settings, setSettings ] = useState( {} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ toast, setToast ] = useState(null);
	const [ initialLoad, setInitialLoad ] = useState(true);
	const [ hasUserMadeChanges, setHasUserMadeChanges ] = useState(false);
	const [ changedFields, setChangedFields ] = useState({});
	const [ expandedSections, setExpandedSections ] = useState({
		pages: true,
		sidebars: true,
		menus: true
	});
	const [ isLinkModalOpen, setIsLinkModalOpen ] = useState(false);
	const [ currentEditingLink, setCurrentEditingLink ] = useState(null);
	// Initialize with base items, will be updated in useEffect
	const [ sideMenuItems, setSideMenuItems ] = useState(baseMenuItems);
	const [menus, setMenus] = useState([]);
	const [isHelpOpen, setHelpOpen] = useState(false);
	const [helpContent, setHelpContent] = useState(null);
	const [isHelpLoading, setHelpLoading] = useState(false);
	const [helpError, setHelpError] = useState(null);

	// Create debounced functions using useRef to maintain references
	const debouncedSaveRef = useRef();
	const debouncedTextChangeRef = useRef();

	// Load settings and initialize menu items
	useEffect(() => {
		const initializeSettings = async () => {
			setIsLoading(true);
			const data = await fetchSettings();

			if (data && data.platform) {
				setSettings(data.platform);

				// Get the complete menu items based on enabled components
				const completeMenuItems = getComponentMenuItems();

				// Apply any saved settings
				setSideMenuItems(prevItems => {
					if (data.platform.bb_rl_side_menu) {
						return completeMenuItems.map(item => {
							const savedItem = data.platform.bb_rl_side_menu[item.id];
							return {
								...item,
								enabled: savedItem ? savedItem.enabled : item.enabled,
								order: savedItem ? savedItem.order : item.order
							};
						}).sort((a, b) => a.order - b.order);
					}
					return completeMenuItems;
				});
			}

			setIsLoading(false);
			setInitialLoad(false);
			setHasUserMadeChanges(false);
		};

		initializeSettings();
		fetchMenus().then(setMenus);
	}, []);

	useEffect(() => {
		debouncedSaveRef.current = debounce((fieldsToSave) => {
			if (Object.keys(fieldsToSave).length === 0) {
				return;
			}

			saveSettings(fieldsToSave)
				.then((data) => {
					if (data) {
						setToast({
							status: 'success',
							message: __('Settings saved.', 'buddyboss'),
						});
						setChangedFields({});
					} else {
						setToast({
							status: 'error',
							message: __('Something went wrong. Please try again', 'buddyboss'),
						});
					}
				})
				.catch(() => {
					setToast({
						status: 'error',
						message: __('Something went wrong. Please try again', 'buddyboss'),
					});
				});
		}, 1000);

		return () => {
			if (debouncedSaveRef.current?.cancel) {
				debouncedSaveRef.current.cancel();
			}
		};
	}, []);

	useEffect(() => {
		if (!initialLoad && Object.keys(changedFields).length > 0) {
			debouncedSaveRef.current(changedFields);
		}
	}, [changedFields, initialLoad]);

	useEffect(() => {
		if (!toast) return;

		if (toast.status === 'success') {
			const timer = setTimeout(() => {
				setToast(null);
			}, 3000);
			return () => clearTimeout(timer);
		}
	}, [toast]);

	// Generic handler for simple value changes
	const handleSettingChange = (name) => (value) => {
		setToast({ status: 'saving', message: __('Saving changes...', 'buddyboss') });
		setSettings(prevSettings => ({
			...prevSettings,
			[name]: value
		}));
		setChangedFields(prev => ({ ...prev, [name]: value }));
		setHasUserMadeChanges(true);
	};

	// Specific handler for text input changes
	const handleTextChange = (name) => (value) => {
		// Update visual state immediately
		setToast({ status: 'saving', message: __('Saving changes...', 'buddyboss') });
		setSettings(prevSettings => ({
			...prevSettings,
			[name]: value
		}));

		setChangedFields(prev => ({ ...prev, [name]: value }));
		setHasUserMadeChanges(true);
	};

	// Handler for nested settings (for pages and sidebars)
	const handleNestedSettingChange = (category, name) => (value) => {
		setHasUserMadeChanges(true);
		setToast({ status: 'saving', message: __('Saving changes...', 'buddyboss') });

		if (category === 'bb_rl_side_menu') {
			setSideMenuItems(prevItems => {
				const updatedItems = prevItems.map(item =>
					item.id === name ? { ...item, enabled: value } : item
				);

				// Update settings and changedFields with the new structure
				const bb_rl_side_menu = {};
				updatedItems.forEach(item => {
					bb_rl_side_menu[item.id] = {
						enabled: item.enabled,
						order: item.order,
						icon: item.icon
					};
				});

				setSettings(prev => ({
					...prev,
					bb_rl_side_menu
				}));
				setChangedFields(prev => ({ ...prev, bb_rl_side_menu }));

				return updatedItems;
			});
		} else {
			setSettings(prevSettings => {
				// Build the updated category object
				const updatedCategory = {
					...prevSettings[category],
					[name]: value
				};
				setChangedFields(prev => ({ ...prev, [category]: updatedCategory }));
				return {
					...prevSettings,
					[category]: updatedCategory
				};
			});
		}
	};

	// Toggle section expansion
	const toggleSection = (section) => {
		setExpandedSections(prev => ({
			...prev,
			[section]: !prev[section]
		}));
	};

	// Handle custom link operations
	const handleAddLinkClick = () => {
		setCurrentEditingLink(null);
		setIsLinkModalOpen(true);
	};

	const handleEditLinkClick = (link) => {
		setCurrentEditingLink(link);
		setIsLinkModalOpen(true);
	};

	const handleSaveLink = (linkData) => {
		setHasUserMadeChanges(true); // Set flag when user makes a change
		setToast({ status: 'saving', message: __('Saving changes...', 'buddyboss') });

		let updatedLinks;
		if (currentEditingLink) {
			// Update existing link
			updatedLinks = settings.bb_rl_custom_links.map(link =>
				link.id === currentEditingLink.id
					? { ...link, title: linkData.title, url: linkData.url }
					: link
			);
		} else {
			// Add new link
			const newLink = {
				id: Date.now(), // Simple unique ID
				title: linkData.title,
				url: linkData.url
			};
			updatedLinks = [...(settings.bb_rl_custom_links || []), newLink];
		}

		// Update both settings and changedFields
		setSettings(prevSettings => ({
			...prevSettings,
			bb_rl_custom_links: updatedLinks
		}));
		setChangedFields(prev => ({ ...prev, bb_rl_custom_links: updatedLinks }));

		setIsLinkModalOpen(false);
	};

	const handleDeleteLink = (id) => {
		setHasUserMadeChanges(true); // Set flag when user makes a change
		setToast({ status: 'saving', message: __('Saving changes...', 'buddyboss') });

		const updatedLinks = settings.bb_rl_custom_links ? settings.bb_rl_custom_links.filter(link => link.id !== id) : [];

		setSettings(prevSettings => ({
			...prevSettings,
			// Ensure bb_rl_custom_links exists before filtering
			bb_rl_custom_links: updatedLinks
		}));
		setChangedFields(prev => ({ ...prev, bb_rl_custom_links: updatedLinks }));
	};

	// Specific handler for image uploads using WordPress media library
	const handleImageUpload = (name) => (imageData) => {
		setHasUserMadeChanges(true); // Set flag when user makes a change
		setToast({ status: 'saving', message: __('Saving changes...', 'buddyboss') });

		// If imageData is null (i.e., Remove button clicked), set to []
		const valueToSet = imageData === null ? [] : imageData;

		// Update both settings and changedFields
		setSettings(prevSettings => ({
			...prevSettings,
			[name]: valueToSet
		}));
		setChangedFields(prev => ({ ...prev, [name]: valueToSet }));
	};

	// Helper function to open the WordPress media library
	const openMediaLibrary = (name, onSelect) => {
		// Check if wp is defined and media is available
		if (typeof window.wp === 'undefined' || !window.wp.media) {
			console.error('WordPress Media API is not available');
			alert('WordPress Media API is not available. Please make sure WordPress Media is properly loaded.');
			return;
		}

		// Create the media frame
		const mediaFrame = window.wp.media({
			title: __('Select or Upload Media', 'buddyboss'),
			button: {
				text: __('Use this media', 'buddyboss'),
			},
			multiple: false,
			library: {
				type: 'image'
			}
		});

		mediaFrame.on('select', function() {
			const attachment = mediaFrame.state().get('selection').first().toJSON();
			const imageData = {
				id: attachment.id, // Save the WordPress attachment ID
				url: attachment.url,
				alt: attachment.alt || '',
				title: attachment.title || ''
			};
			onSelect(imageData);
		});

		mediaFrame.open();
	};

	// Component for image selection and preview
	const ImageSelector = ({ label, value, onChange, description, customClass }) => {
		return (
			<div className={`image-selector-component ${customClass || ''}`}>
				<label>{label}</label>
				<div className="image-selector-control">
					{value && value.url ? (
						<div className="bb-rl-image-preview-wrapper">
							<img
								src={value.url}
								alt={value.alt || ''}
								className="image-preview"
							/>
							<div className="image-actions">
								<Button
									onClick={() => openMediaLibrary(label, onChange)}
									className="change-image-button bb-rl-button bb-rl-button--secondary bb-rl-button--small"
									icon={<i className="bb-icons-rl-upload-simple" />}
								>
									{__('Replace', 'buddyboss')}
								</Button>
								<Button
									onClick={() => onChange(null)}
									className="remove-image-button bb-rl-button bb-rl-button--outline bb-rl-button--small"
									icon={<i className="bb-icons-rl-x" />}
								>
									{__('Remove', 'buddyboss')}
								</Button>
							</div>
						</div>
					) : (
						<Button
							onClick={() => openMediaLibrary(label, onChange)}
							className="bb-rl-upload-image-button"
							icon={<i className="bb-icons-rl-plus" />}
						/>
					)}
					{description && (
						<p className="field-description">{description}</p>
					)}
				</div>
			</div>
		);
	};

	// Component for color picker with popover
	const ColorPickerButton = ({ label, color, onChange }) => {
		const [isPickerOpen, setIsPickerOpen] = useState(false);
		const [tempColor, setTempColor] = useState(color);

		const togglePicker = () => {
			setIsPickerOpen(!isPickerOpen);
			setTempColor(color); // Reset temp color when opening
		};

		const closePicker = () => setIsPickerOpen(false);

		const applyColor = () => {
			onChange(tempColor);
			closePicker();
		};

		// Ensure we have a valid color value
		const colorValue = color || '#3E34FF'; // Default to blue if no color is set

		return (
			<div className="color-picker-button-component bb-rl-color-picker-button-component">
				{label && <span className="color-picker-label">{label}</span>}
				<div className="color-picker-button-wrapper">
					<Button
						className="color-picker-button"
						onClick={togglePicker}
						aria-expanded={isPickerOpen}
						aria-label={__('Select color', 'buddyboss')}
					>
						<div className="color-indicator-wrapper">
							<ColorIndicator colorValue={colorValue} />
						</div>
						<span className="color-picker-value">{colorValue}</span>
					</Button>
					{isPickerOpen && (
						<Popover
							className="color-picker-popover"
							onClose={closePicker}
							position="bottom center"
						>
							<div className="color-picker-popover-content">
								<ColorPicker
									color={tempColor || colorValue}
									onChange={(newColor) => {
										setTempColor(newColor);
										// Don't call onChange here to keep the popover open
									}}
									enableAlpha={false}
									copyFormat="hex"
								/>
								<div className="color-picker-popover-footer">
									<Button
										onClick={applyColor}
										className="apply-color-button"
									>
										{__('Apply', 'buddyboss')}
									</Button>
								</div>
							</div>
						</Popover>
					)}
				</div>
			</div>
		);
	};

	// Drag and Drop Handler
	const onDragEnd = (result) => {
		const { source, destination, draggableId, type } = result;

		// Dropped outside the list
		if (!destination) {
			return;
		}

		// Dropped in the same place
		if (
			destination.droppableId === source.droppableId &&
			destination.index === source.index
		) {
			return;
		}

		setHasUserMadeChanges(true);
		setToast({ status: 'saving', message: __('Saving changes...', 'buddyboss') });

		// Reorder Side Menu Items
		if (source.droppableId === 'sideMenuItems') {
			const items = Array.from(sideMenuItems);
			const [reorderedItem] = items.splice(source.index, 1);
			items.splice(destination.index, 0, reorderedItem);

			// Update order property for all items
			const updatedItems = items.map((item, index) => ({
				...item,
				order: index
			}));

			setSideMenuItems(updatedItems);

			// Update settings and changedFields with the new structure
			const bb_rl_side_menu = {};
			updatedItems.forEach(item => {
				bb_rl_side_menu[item.id] = {
					enabled: item.enabled,
					order: item.order,
					icon: item.icon
				};
			});

			setSettings(prev => ({
				...prev,
				bb_rl_side_menu
			}));
			setChangedFields(prev => ({ ...prev, bb_rl_side_menu }));
		}

		// Reorder Custom Links
		if (source.droppableId === 'bb_rl_custom_links') {
			const items = Array.from(settings.bb_rl_custom_links || []);
			const [reorderedItem] = items.splice(source.index, 1);
			items.splice(destination.index, 0, reorderedItem);

			// Update both settings and changedFields
			setSettings(prevSettings => ({
				...prevSettings,
				bb_rl_custom_links: items
			}));
			setChangedFields(prev => ({ ...prev, bb_rl_custom_links: items }));
		}
	};

	// Handler for help icon click
	const handleHelpClick = async (contentId) => {
		setHelpOpen(true);
		setHelpLoading(true);
		setHelpError(null);

		try {
			const content = await fetchHelpContent(contentId);
			setHelpContent(content);
		} catch (error) {
			setHelpError('Failed to load help content. Please try again later.');
			// Clear cache for this content ID if there was an error
			clearHelpContentCache(contentId);
		} finally {
			setHelpLoading(false);
		}
	};

	// Clear help content when modal is closed
	const handleHelpClose = () => {
		setHelpOpen(false);
		setHelpContent(null);
		setHelpError(null);
	};

	// Update Accordion usage to include contentId
	const renderAccordion = (title, isExpanded, section, contentId) => (
		<Accordion
			title={__(title, 'buddyboss')}
			isExpanded={isExpanded}
			onToggle={() => toggleSection(section)}
			onHelpClick={() => handleHelpClick(contentId)}
		>
			{/* children */}
		</Accordion>
	);

	const renderContent = () => {
		if ( isLoading ) {
			return (
				<div className="settings-loading">
					<Spinner/>
					<p>{__( 'Loading settings...', 'buddyboss' )}</p>
				</div>
			);
		}

		 // Block all settings except Activation if ReadyLaunch is disabled.
		 if ( !settings.bb_rl_enabled && 'activation' !== activeTab ) {
			return (
				<div className="bb-rl-disabled-message">
					<div className="bb-rl-disabled-icon">
						<span className="bb-icons-rl-info" />
					</div>
					<h3>{__('ReadyLaunch is disabled', 'buddyboss')}</h3>
					<p>{__('To enable ReadyLaunch and access its features, go to the Activation menu.', 'buddyboss')}</p>
					<Button
						className="bb-rl-button bb-rl-button--primary bb-rl-button--small"
						onClick={() => setActiveTab('activation')}
					>
						{__('Enable ReadyLaunch', 'buddyboss')}
					</Button>
				</div>
			);
		}

		switch ( activeTab ) {
			case 'activation':
				return (
					<div className="settings-content">
						<WelcomeSection />
						<div className="settings-card settings-card--plain">
							<div className="settings-toggle-container">
								<div className="toggle-content">
									<h3>{__('Enable ReadyLaunch', 'buddyboss')}</h3>
									<p>{__('Turn on ReadyLaunch to override your theme styles on BuddyBoss pages.', 'buddyboss')}</p>
								</div>
								<ToggleControl
									checked={settings.bb_rl_enabled}
									onChange={handleSettingChange('bb_rl_enabled')}
								/>
							</div>
						</div>

						{ settings.bb_rl_enabled && (
							<div className="settings-card">
								<div className="settings-header">
									<h3>{__('Site Name', 'buddyboss')}</h3>
									<HelpIcon onClick={() => handleHelpClick('459612')} />
								</div>
								<div className="settings-form-field">
									<div className="field-label">
										<label>{__('Site Name', 'buddyboss')}</label>
										<p>{__('Displays in the browser title, search engine results and site header.', 'buddyboss')}</p>
									</div>
									<div className="field-input">
										<TextControl
											placeholder={__('Type your community/site title', 'buddyboss')}
											value={settings.blogname}
											onChange={handleTextChange('blogname')}
										/>
										<p className="field-description">{__('This matches the WordPress Site Title. Updating it here will update it site-wide.', 'buddyboss')}</p>
									</div>
								</div>
							</div>
						) }

						{ settings.bb_rl_enabled && ( <div className="settings-card">
							<div className="settings-header">
								<h3>{__('Platform Settings', 'buddyboss')}</h3>
								<HelpIcon onClick={() => handleHelpClick('459617')} />
							</div>
							<div className="settings-list-items-block">
								{
									[
										{
											id: 'activity',
											icon: 'bb-icons-rl-pulse',
											title: __('Activity', 'buddyboss'),
											description: __('Control activity streams and user engagement settings.', 'buddyboss'),
											actionLink: 'admin.php?page=bp-settings&tab=bp-activity'
										},
										{
											id: 'xprofile',
											icon: 'bb-icons-rl-user-square',
											title: __('Profiles', 'buddyboss'),
											description: __('Manage profile fields, visibility, and user profile options.', 'buddyboss'),
											actionLink: 'admin.php?page=bp-settings&tab=bp-xprofile'
										},
										{
											id: 'groups',
											icon: 'bb-icons-rl-users-three',
											title: __('Groups', 'buddyboss'),
											description: __('Configure group creation, privacy, and member roles.', 'buddyboss'),
											actionLink: 'admin.php?page=bp-settings&tab=bp-groups'
										},
										{
											id: 'media',
											icon: 'bb-icons-rl-image',
											title: __('Media', 'buddyboss'),
											description: __('Enable or restrict user-uploaded media across the platform.', 'buddyboss'),
											actionLink: 'admin.php?page=bp-settings&tab=bp-media'
										},
										{
											id: 'moderation',
											icon: 'bb-icons-rl-flag',
											title: __('Moderation', 'buddyboss'),
											description: __('Set rules and tools for reporting and content moderation.', 'buddyboss'),
											actionLink: 'admin.php?page=bp-settings&tab=bp-moderation'
										}
									].map(item => (
										window?.BP_ADMIN?.components &&
										window?.BP_ADMIN?.components[item.id] &&
										window?.BP_ADMIN?.components[item.id] === 1 && (
										<div className="settings-list-item" key={item.id}>
											<div className="settings-list-item-icon">
												<span className={item.icon} />
											</div>
											<div className="settings-list-item-content">
												<div className="settings-list-item-title">
													<h4>{item.title}</h4>
												</div>
												<div className="settings-list-item-description">
													<p>{item.description}</p>
												</div>
											</div>
											<div className="settings-list-item-actions">
													<Button
														className="bb-rl-button bb-rl-button--outline bb-rl-button--small"
														icon={<i className="bb-icons-rl-gear" />}
														href={item.actionLink}
														target="_blank"
														rel="noopener noreferrer"
													>
														{__('Settings', 'buddyboss')}
													</Button>
												</div>
										</div>
										)
									))
								}
							</div>
						</div>
						) }
					</div>
				);
			case 'styles':
				return (
					<div className="settings-content">
						<h1>{__('Style Settings', 'buddyboss')}</h1>
						<p className="settings-description">{__('Customize the appearance of your community to match your brand colors and logo.', 'buddyboss')}</p>

						<div className="settings-card">
							<div className="settings-header">
								<h3>{__('Branding', 'buddyboss')}</h3>
								<HelpIcon onClick={() => handleHelpClick('459621')} />
							</div>

							{/* Appearance Setting */}
							<div className="settings-form-field with-toggle">
								<div className="field-label">
									<label>{__('Appearance', 'buddyboss')}</label>
									<p>{__('Choose whether you wish to support light or dark mode.', 'buddyboss')}</p>
								</div>
								<div className="field-input">
									<div className="sub-field-input sub-field-input-inline">
										<RadioControl
											selected={settings.bb_rl_theme_mode}
											options={[
												{ label: __('Light Mode', 'buddyboss'), value: 'light' },
												{ label: __('Dark Mode', 'buddyboss'), value: 'dark' },
												{ label: __('User Preference', 'buddyboss'), value: 'choice' },
											]}
											onChange={handleSettingChange('bb_rl_theme_mode')}
										/>
										{themeModeDescriptions[settings.bb_rl_theme_mode] && (
											<p className="field-description">
												{themeModeDescriptions[settings.bb_rl_theme_mode]}
											</p>
										)}
									</div>
								</div>
							</div>

							{/* Logo Setting */}
							<div className="settings-form-field">
								<div className="field-label">
									<label>{__('Logo', 'buddyboss')}</label>
									<p>{__('Upload your logo which appears along the top site header.', 'buddyboss')}</p>
								</div>
								<div className="field-input logo-uploaders">
									{'dark' !== settings.bb_rl_theme_mode && (
										<ImageSelector
											label={__('Logo (Light mode)', 'buddyboss')}
											value={settings.bb_rl_light_logo}
											onChange={handleImageUpload('bb_rl_light_logo')}
											description={__('Recommended to use a dark-colored logo, 280x80 px, in JPG or PNG format.', 'buddyboss')}
											customClass="light-logo-mode"
										/>
									)}
									{'light' !== settings.bb_rl_theme_mode && (
										<ImageSelector
											label={__('Logo (Dark mode)', 'buddyboss')}
											value={settings.bb_rl_dark_logo}
											onChange={handleImageUpload('bb_rl_dark_logo')}
											description={__('Recommended to use a light-colored logo, 280x80 px, in JPG or PNG format.', 'buddyboss')}
											customClass="dark-logo-mode"
										/>
									)}
								</div>
							</div>

							{/* Theme Color Setting */}
							<div className="settings-form-field">
								<div className="field-label">
									<label>{__('Theme Color', 'buddyboss')}</label>
									<p>{__('Select the primary color of your community. This is used across buttons, links and secondary elements.', 'buddyboss')}</p>
								</div>
								<div className="field-input color-palettes bb-rl-color-palettes">
									{'dark' !== settings.bb_rl_theme_mode && (
									<div className="color-palette-item">
										<ColorPickerButton
											label={__('Primary Color (Light Mode)', 'buddyboss')}
											color={settings.bb_rl_color_light}
											onChange={handleSettingChange('bb_rl_color_light')}
										/>
									</div>
									)}
									{'light' !== settings.bb_rl_theme_mode && (
										<div className="color-palette-item">
											<ColorPickerButton
												label={__('Primary Color (Dark Mode)', 'buddyboss')}
												color={settings.bb_rl_color_dark}
												onChange={handleSettingChange('bb_rl_color_dark')}
											/>
										</div>
									)}
								</div>
							</div>

						</div>
					</div>
				);
			case 'pages':
				return (
					<div className="settings-content">
						<h1>{__('Pages and Widgets Settings', 'buddyboss')}</h1>
						<p className="settings-description">{__('Enable or disable page styles, and customize sidebar widgets for different sections of your community.', 'buddyboss')}</p>

						<div className="settings-card">
							<Accordion
								title={__('Pages', 'buddyboss')}
								isExpanded={expandedSections.pages}
								onToggle={() => toggleSection('pages')}
								onHelpClick={() => handleHelpClick('459627')}
							>
								<div className="settings-form-field with-multiple-toggles">
									<div className="field-label">
										<label>{__('Enable Pages', 'buddyboss')}</label>
										<p>{__('Apply ReadyLaunch styles to the following pages', 'buddyboss')}</p>
									</div>
									{window?.BP_ADMIN?.register_integration === '1' || window?.BP_ADMIN?.courses_integration === '1' ? (
										<div className="field-toggles">
											{window?.BP_ADMIN?.register_integration === '1' && (
												<div className="toggle-item">
													<ToggleControl
														label={__('Login & Registration', 'buddyboss')}
														checked={settings.bb_rl_enabled_pages.registration}
														onChange={handleNestedSettingChange('bb_rl_enabled_pages', 'registration')}
													/>
												</div>
											)}
											{window?.BP_ADMIN?.courses_integration === '1' && (
												<div className="toggle-item">
													<ToggleControl
														label={__('Courses', 'buddyboss')}
														checked={settings.bb_rl_enabled_pages.courses}
														onChange={handleNestedSettingChange('bb_rl_enabled_pages', 'courses')}
													/>
												</div>
											)}
										</div>
									) : (
										<div className="bb-rl-notice bb-rl-notice--info">
										  <span className="bb-rl-notice-icon">
											<i className="bb-icons-rl-info" />
										  </span>
										  <div className="bb-rl-notice-content">
											<strong>{__('No pages found', 'buddyboss')}</strong>
											<p>
											  {__('Enable the corresponding pages from your backend settings to manage them here with ReadyLaunch styles.', 'buddyboss')}
											</p>
										  </div>
										</div>
									)}
								</div>
							</Accordion>
						</div>

						<div className="settings-card">
							<Accordion
								title={__('Sidebar Widgets', 'buddyboss')}
								isExpanded={expandedSections.sidebars}
								onToggle={() => toggleSection('sidebars')}
								onHelpClick={() => handleHelpClick('459623')}
							>
								{
									BP_ADMIN.components &&
									BP_ADMIN.components.activity &&
									BP_ADMIN.components.activity === 1 && (
									<div className="settings-form-field with-multiple-toggles">
										<div className="field-label">
											<label>{__('Activity Feed', 'buddyboss')}</label>
											<p>{createInterpolateElement(
												__('Enable or disable widgets to appear on the <a>activity feed</a>.', 'buddyboss'),
												{
													a: <a href={window?.BP_ADMIN?.component_pages?.activity || ''} target="_blank" />
												}
											)}</p>
										</div>
										<div className="field-toggles">
											<div className="toggle-item">
												<ToggleControl
													label={__('Complete Profile', 'buddyboss')}
													checked={settings.bb_rl_activity_sidebars.complete_profile}
													onChange={handleNestedSettingChange('bb_rl_activity_sidebars', 'complete_profile')}
												/>
											</div>
											<div className="toggle-item">
												<ToggleControl
													label={__('Latest Updates', 'buddyboss')}
													checked={settings.bb_rl_activity_sidebars.latest_updates}
													onChange={handleNestedSettingChange('bb_rl_activity_sidebars', 'latest_updates')}
												/>
											</div>
											<div className="toggle-item">
												<ToggleControl
													label={__('Recent Blog Posts', 'buddyboss')}
													checked={settings.bb_rl_activity_sidebars.recent_blog_posts}
													onChange={handleNestedSettingChange('bb_rl_activity_sidebars', 'recent_blog_posts')}
												/>
											</div>
											<div className="toggle-item">
												<ToggleControl
													label={__('Active Members', 'buddyboss')}
													checked={settings.bb_rl_activity_sidebars.active_members}
													onChange={handleNestedSettingChange('bb_rl_activity_sidebars', 'active_members')}
												/>
											</div>
										</div>
									</div>
									)
								}

								{/* Member Profile */}
								<div className="settings-form-field with-multiple-toggles">
									<div className="field-label">
										<label>{__('Member Profile', 'buddyboss')}</label>
										<p>
											{createInterpolateElement(
												__('Enable or disable widgets to appear on the <a>member profile</a>.', 'buddyboss'),
												{
													a: <a href={window?.BP_ADMIN?.component_pages?.xprofile || ''} target="_blank" />
												}
											)}
										</p>
									</div>
									<div className="field-toggles">
										<div className="toggle-item">
											<ToggleControl
												label={__('Complete Profile', 'buddyboss')}
												checked={settings.bb_rl_member_profile_sidebars.complete_profile}
												onChange={handleNestedSettingChange('bb_rl_member_profile_sidebars', 'complete_profile')}
											/>
										</div>
										{
											BP_ADMIN.components &&
											BP_ADMIN.components.friends &&
											BP_ADMIN.components.friends === 1 && (
											<div className="toggle-item">
												<ToggleControl
													label={__('Connections', 'buddyboss')}
													checked={settings.bb_rl_member_profile_sidebars.connections}
													onChange={handleNestedSettingChange('bb_rl_member_profile_sidebars', 'connections')}
												/>
											</div>
											)
										}
										{
											BP_ADMIN.components &&
											BP_ADMIN.components.activity &&
											BP_ADMIN.components.activity === 1 &&
											settings.bp_enable_activity_follow === true &&
											(
											<div className="toggle-item">
												<ToggleControl
													label={__('My Network (Follow, Followers)', 'buddyboss')}
													checked={settings.bb_rl_member_profile_sidebars.my_network}
													onChange={handleNestedSettingChange('bb_rl_member_profile_sidebars', 'my_network')}
												/>
											</div>
											)
										}
									</div>
								</div>

								{/* Group */}
								{
									BP_ADMIN.components &&
									BP_ADMIN.components.groups &&
									BP_ADMIN.components.groups === 1 && (
									<div className="settings-form-field with-multiple-toggles">
									<div className="field-label">
										<label>{__('Group', 'buddyboss')}</label>
										<p>
											{createInterpolateElement(
												__('Enable or disable widgets to appear on the <a>group single</a> page.', 'buddyboss'),
												{
													a: <a href={window?.BP_ADMIN?.component_pages?.single_group || ''} target="_blank" />
												}
											)}
										</p>
									</div>
									<div className="field-toggles">
										<div className="toggle-item">
											<ToggleControl
												label={__('About Group', 'buddyboss')}
												checked={settings.bb_rl_groups_sidebars.about_group}
												onChange={handleNestedSettingChange('bb_rl_groups_sidebars', 'about_group')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Group Members', 'buddyboss')}
												checked={settings.bb_rl_groups_sidebars.group_members}
												onChange={handleNestedSettingChange('bb_rl_groups_sidebars', 'group_members')}
											/>
										</div>
									</div>
								</div>
									)
								}
							</Accordion>
						</div>
					</div>
				);
			case 'menus':
				return (
					<div className="settings-content">
						<h1>{__('Menu Settings', 'buddyboss')}</h1>
						<p className="settings-description">{__('Configure header, sidebar, and custom link menus to control navigation across your community.', 'buddyboss')}</p>

						<DragDropContext onDragEnd={onDragEnd}> {/* Wrap relevant sections */}
							<div className="settings-card">
								<Accordion
									title={__('Menus', 'buddyboss')}
									isExpanded={expandedSections.menus}
									onToggle={() => toggleSection('menus')}
									onHelpClick={() => handleHelpClick('459625')}
								>
									{/* Header Menu */}
									<div className="settings-form-field menu-header-field">
										<div className="field-label">
											<label>{__('Header', 'buddyboss')}</label>
											<p>{__('Choose a menu which displays in the top navigation bar', 'buddyboss')}</p>
										</div>
										<div className="field-input">
											<SelectControl
												value={settings.bb_rl_header_menu}
												options={[
													{ label: __('Select Menu', 'buddyboss'), value: '' },													...menus.map(menu => ({
														label: menu.name,
														value: menu.slug
													}))
												]}
												onChange={handleSettingChange('bb_rl_header_menu')}
												className="bb-rl-input-field"
											/>
											<p className="field-note">
												{createInterpolateElement(
													__('Update your header menu from Appearance > <a>Menus</a>. There you will find a Display Option of ReadyLaunch', 'buddyboss'),
													{
														a: <a href="/wp-admin/nav-menus.php" />
													}
												)}
											</p>
										</div>
									</div>

									{/* Side Menu - Now uses Draggable/Droppable */}
									<div className="settings-form-field with-icon-toggles">
										<div className="field-label">
											<label>{__('Side', 'buddyboss')}</label>
											<p>{__('Enable and re-order menu items shown on the left sidebar', 'buddyboss')}</p> {/* Added note */}
										</div>
										<Droppable droppableId="sideMenuItems">
											{(provided) => (
												<div
													className="field-toggles"
													{...provided.droppableProps}
													ref={provided.innerRef}
												>
													{sideMenuItems.map((item, index) => (
														<Draggable key={item.id} draggableId={item.id} index={index}>
															{(providedDraggable, snapshot) => (
																<div
																	ref={providedDraggable.innerRef}
																	{...providedDraggable.draggableProps}
																	{...providedDraggable.dragHandleProps}
																	className={`side-menu-item-draggable toggle-item ${snapshot.isDragging ? 'is-dragging' : ''}`}
																>
																	<i className="bb-icons-rl-list" />
																	<ToggleControl
																		checked={item.enabled}
																		// Use the updated handler, passing the item ID
																		onChange={(value) => handleNestedSettingChange('bb_rl_side_menu', item.id)(value)}
																		label={<><span className={`menu-icon bb-icons-rl-${item.icon}`}></span> {item.label}</>}
																	/>
																</div>
															)}
														</Draggable>
													))}
													{provided.placeholder}
												</div>
											)}
										</Droppable>
									</div>

									{/* Custom Links - Now uses Draggable/Droppable */}
									<div className="settings-form-field custom-links-field">
										<div className="field-label">
											<label>{__('Link', 'buddyboss')}</label>
											<p>{__('Add and re-order custom links which are shown on the left sidebar', 'buddyboss')}</p> {/* Added note */}
										</div>
										<Droppable droppableId="bb_rl_custom_links">
											{(provided) => (
												<div
													className="field-input custom-links-wrapper"
													{...provided.droppableProps}
													ref={provided.innerRef}
												>
													{settings.bb_rl_custom_links && settings.bb_rl_custom_links.map((link, index) => ( // Added check for settings.bb_rl_custom_links
														<Draggable key={link.id} draggableId={link.id.toString()} index={index}>
															{(providedDraggable, snapshot) => (
																<LinkItem
																	link={link}
																	onEdit={() => handleEditLinkClick(link)}
																	onDelete={() => handleDeleteLink(link.id)}
																	// Pass necessary props from Draggable
																	innerRef={providedDraggable.innerRef}
																	draggableProps={providedDraggable.draggableProps}
																	dragHandleProps={providedDraggable.dragHandleProps}
																	isDragging={snapshot.isDragging} // Pass dragging state for styling
																/>
															)}
														</Draggable>
													))}
													{provided.placeholder}

													{/* Add New Link Button - Moved inside Droppable but outside mapping */}
													<Button
														className="add-link-button bb-rl-button bb-rl-button--primary bb-rl-button--small"
														onClick={handleAddLinkClick}
														icon={<i className="bb-icons-rl-plus" />}
													>
														{__('Add New Link', 'buddyboss')}
													</Button>
												</div>
											)}
										</Droppable>
									</div>
								</Accordion>
							</div>
						</DragDropContext> {/* End DragDropContext */}

						{/* Link Modal */}
						<LinkModal
							isOpen={isLinkModalOpen}
							onClose={() => setIsLinkModalOpen(false)}
							onSave={handleSaveLink}
							linkData={currentEditingLink ? {
								title: currentEditingLink.title,
								url: currentEditingLink.url
							} : { title: '', url: '' }}
						/>
					</div>
				);
			default:
				return <div>Select a tab</div>;
		}
	};

	// --- Welcome Section ---
	const WelcomeSection = () => (
		<div className="bb-rl-welcome-section settings-card settings-card--plain">
			<div className="bb-rl-welcome-content">
				<div className="bb-rl-welcome-text">
					<h1>{__( 'Welcome to ReadyLaunch', 'buddyboss' )}</h1>
					<p>
						{__( 'We’re excited to reveal the brand new ReadyLaunch system; a page template design allowing you to use BuddyBoss Platform with ANY Theme. It’s easy to get started; just enable ReadyLaunch and configure the next 3 tabs - Style, Sidebars and Menus.', 'buddyboss' )}
					</p>
					<p>
						{__( 'This is not a replacement for BuddyBoss Theme, but an alternative solution if you’re looking for an out-the-box community platform with a focus on core functionality without third party customisation. See the video for full details and share your experience on our new roadmap.', 'buddyboss' )}
					</p>
					<Button
						className="bb-rl-feedback-btn"
						href="https://roadmap.buddyboss.com/p/new-ready-launch-buddyboss-platform-templates-Y8mV6D"
						target="_blank"
						rel="noopener noreferrer"
						icon={<i className="bb-icons-rl-rocket-launch" />}
					>
						{__( 'Leave Feedback', 'buddyboss' )} <i className="bb-icons-rl-arrow-right" />
					</Button>
				</div>
				<div className="bb-rl-welcome-video">
					<iframe width="560" height="315" src="https://www.youtube.com/embed/3-JhzDr1gLc" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
				</div>
			</div>
		</div>
	);

	useEffect(() => {
		const helpButton = document.querySelector('.bb-rl-header-actions-button');
		const helpOverlay = document.getElementById('bb-rl-help-overlay');
		const closeButton = document.getElementById('bb-rl-help-overlay-close');
		const accordionContainer = document.querySelector('.bb-rl-help-accordion');

		const renderAccordion = (categories) => {
			if (!accordionContainer) return;
			accordionContainer.innerHTML = ''; // Clear existing content
			categories.forEach(category => {
				const item = document.createElement('div');
				item.className = 'bb-rl-help-accordion-item';

				const link = document.createElement('a');
				link.href = category.link;
				link.target = '_blank';
				link.rel = 'noopener noreferrer';
				link.className = 'bb-rl-help-accordion-header';
				link.innerHTML = `
					<span><i class="bb-icons-rl-folder"></i> ${category.name}</span>
					<i class="bb-icons-rl-caret-double-right"></i>
				`;

				item.appendChild(link);
				accordionContainer.appendChild(item);
			});
		};

		const loadHelpCategories = async () => {
			if (!helpButton || !accordionContainer) {
				return;
			}
			const parentId = helpButton.getAttribute('data-help-cat-id');

			try {
				accordionContainer.innerHTML = `<div class="bb-rl-spinner-wrapper"><span class="spinner is-active"></span></div>`;
				const categories = await fetchHelpCategories(parentId);
				renderAccordion(categories);
			} catch (error) {
				accordionContainer.innerHTML = `<p>${__('Error loading help content.', 'buddyboss')}</p>`;
				console.error('Error fetching help categories:', error);
			}
		};

		loadHelpCategories();

		const openOverlay = (e) => {
			e.preventDefault();
			if (helpOverlay) {
				helpOverlay.style.display = 'flex';
				document.body.style.overflow = 'hidden';
			}
		};

		const closeOverlay = () => {
			if (helpOverlay) {
				helpOverlay.style.display = 'none';
				document.body.style.overflow = '';
			}
		};

		if (helpButton) {
			helpButton.addEventListener('click', openOverlay);
		}

		if (closeButton) {
			closeButton.addEventListener('click', closeOverlay);
		}

		return () => {
			if (helpButton) {
				helpButton.removeEventListener('click', openOverlay);
			}
			if (closeButton) {
				closeButton.removeEventListener('click', closeOverlay);
			}
		};
	}, []);

	return (
		<>
			<div className="bb-readylaunch-settings-container">
				<Sidebar activeTab={activeTab} setActiveTab={setActiveTab}/>
				<div className="bb-readylaunch-settings-content">
					{renderContent()}
				</div>
			</div>

			<div className="bb-rl-toast-container">
				{toast && (
					<Toast
						status={toast.status}
						message={toast.message}
						onDismiss={() => setToast(null)}
					/>
				)}
			</div>

			<HelpSliderModal
				isOpen={isHelpOpen}
				onClose={handleHelpClose}
				title={helpContent?.title || "Help"}
			>
				{isHelpLoading ? (
					<div className="help-content-loading">
						<Spinner />
						<p>{__('Loading help content...', 'buddyboss')}</p>
					</div>
				) : helpError ? (
					<div className="help-content-error">
						<p>{helpError}</p>
					</div>
				) : helpContent ? (
					<>
						{helpContent.videoId && (
							<div style={{marginBottom: 16}}>
								<iframe
									width="100%"
									height="315"
									src={`https://www.youtube.com/embed/${helpContent.videoId}`}
									title="YouTube video"
									frameBorder="0"
									allowFullScreen
								></iframe>
							</div>
						)}
						<div
							className="help-content"
							dangerouslySetInnerHTML={{ __html: helpContent.content }}
						/>
						{helpContent.imageUrl && (
							<img
								src={helpContent.imageUrl}
								alt="Help content illustration"
								style={{width: '100%', borderRadius: 8, marginBottom: 16}}
							/>
						)}
					</>
				) : (
					<p>{__('No help content available.', 'buddyboss')}</p>
				)}
			</HelpSliderModal>
		</>
	);
};
