import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Accordion component for collapsible sections.
 * 
 * @param {Object} props - Component props
 * @param {string} props.title - Title of the accordion section
 * @param {boolean} props.isExpanded - Whether the accordion is expanded
 * @param {Function} props.onToggle - Function to call when accordion is toggled
 * @param {React.ReactNode} props.children - Content to display when expanded
 * @returns {JSX.Element} Accordion component
 */
export const Accordion = ({ title, isExpanded, onToggle, children }) => {
    return (
        <div className={`settings-accordion ${isExpanded ? 'expanded' : 'collapsed'}`}>
            <div className="accordion-header" onClick={onToggle}>
                <h3>{title}</h3>
                <span className={`dashicons ${isExpanded ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'}`}></span>
            </div>
            {isExpanded && (
                <div className="accordion-content">
                    {children}
                </div>
            )}
        </div>
    );
}; 