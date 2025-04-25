import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal, Button, TextControl } from '@wordpress/components';

/**
 * Modal component for adding/editing links
 * 
 * @param {Object} props Component properties
 * @param {boolean} props.isOpen Whether the modal is open
 * @param {Function} props.onClose Function to call when closing the modal
 * @param {Object} props.linkData Link data (id, title, url) for editing, or empty for adding
 * @param {Function} props.onSave Function to call when saving the link
 * @returns {JSX.Element} LinkModal component
 */
export const LinkModal = ({ isOpen, onClose, linkData = {}, onSave }) => {
  const [title, setTitle] = useState('');
  const [url, setUrl] = useState('');
  const [errors, setErrors] = useState({});
  
  // Reset form when linkData changes
  useEffect(() => {
    if (isOpen) {
      setTitle(linkData.title || '');
      setUrl(linkData.url || '');
      setErrors({});
    }
  }, [isOpen, linkData]);
  
  const handleSave = () => {
    // Validate form
    const newErrors = {};
    
    if (!title.trim()) {
      newErrors.title = __('Title is required', 'buddyboss');
    }
    
    if (!url.trim()) {
      newErrors.url = __('URL is required', 'buddyboss');
    } else if (!isValidUrl(url)) {
      newErrors.url = __('Please enter a valid URL', 'buddyboss');
    }
    
    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }
    
    // Save link
    onSave({
      id: linkData.id,
      title: title.trim(),
      url: url.trim()
    });
    
    // Close modal
    onClose();
  };
  
  // Simple URL validation
  const isValidUrl = (string) => {
    try {
      new URL(string);
      return true;
    } catch (_) {
      return false;
    }
  };
  
  if (!isOpen) {
    return null;
  }
  
  return (
    <Modal
      title={linkData.id ? __('Edit Link', 'buddyboss') : __('Add Link', 'buddyboss')}
      onRequestClose={onClose}
      className="link-modal"
    >
      <div className="link-modal-content">
        <div className="link-modal-form">
          <TextControl
            label={__('Title', 'buddyboss')}
            value={title}
            onChange={setTitle}
            help={errors.title}
            className={errors.title ? 'has-error' : ''}
          />
          
          <TextControl
            label={__('URL', 'buddyboss')}
            value={url}
            onChange={setUrl}
            help={errors.url}
            className={errors.url ? 'has-error' : ''}
          />
        </div>
        
        <div className="link-modal-actions">
          <Button
            variant="secondary"
            onClick={onClose}
          >
            {__('Cancel', 'buddyboss')}
          </Button>
          <Button
            variant="primary"
            onClick={handleSave}
          >
            {__('Save', 'buddyboss')}
          </Button>
        </div>
      </div>
    </Modal>
  );
}; 