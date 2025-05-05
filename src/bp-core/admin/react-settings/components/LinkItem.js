import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * LinkItem component for displaying and editing custom menu links.
 * 
 * @param {Object} props - Component props
 * @param {Object} props.link - Link object with id, title, url, and isEditing properties
 * @param {Function} props.onEdit - Function to call when editing is requested
 * @param {Function} props.onDelete - Function to call when deleting the link
 * @param {React.Ref} props.innerRef - Ref for the draggable element
 * @param {Object} props.draggableProps - Props for the draggable element
 * @param {Object} props.dragHandleProps - Props for the drag handle
 * @param {boolean} props.isDragging - Whether the item is currently being dragged
 * @returns {JSX.Element} LinkItem component
 */
export const LinkItem = ({ link, onEdit, onDelete, innerRef, draggableProps, dragHandleProps, isDragging }) => {
    return (
        <div 
            className={`link-item bb-rl-link-item ${isDragging ? 'is-dragging' : ''}`}
            ref={innerRef} 
            {...draggableProps} 
            {...dragHandleProps}
        >
            <div className="link-item-content">
                <div className="link-details">
                    <span className="link-icon">
                        <i className="bb-icons-rl-link" />
                        <span className="link-title">{link.title}</span>
                        <div className="link-actions">
                            <Button
                                className="edit-link-button"
                                icon={<i className="bb-icons-rl-pencil-simple" />}
                                onClick={onEdit}
                                label={__('Edit', 'buddyboss')}
                                isSmall
                            />
                            <Button
                                className="delete-link-button" 
                                icon={<i className="bb-icons-rl-trash" />}
                                onClick={onDelete}
                                label={__('Delete', 'buddyboss')}
                                isSmall
                            />
                        </div>
                    </span>
                    <span className="link-url">{link.url}</span>
                </div>
            </div>
        </div>
    );
}; 