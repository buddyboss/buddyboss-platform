import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { HelpIcon } from './HelpIcon';

/**
 * Accordion component for collapsible sections.
 * 
 * @param {Object} props - Component props
 * @param {string} props.title - Title of the accordion section
 * @param {boolean} props.isExpanded - Whether the accordion is expanded
 * @param {Function} props.onToggle - Function to call when accordion is toggled
 * @param {Function} props.onHelpClick - Function to call when help icon is clicked
 * @param {React.ReactNode} props.children - Content to display when expanded
 * @returns {JSX.Element} Accordion component
 */
export const Accordion = ({ title, isExpanded, onToggle, onHelpClick, children }) => {
    return (
        <div className={`settings-accordion ${isExpanded ? 'expanded' : 'collapsed'}`}>
            <div className="accordion-header">
                <div className="bb-rl-accordion-toggle" onClick={onToggle}>
                    <h3>{title}</h3>
                </div>
                <HelpIcon onClick={onHelpClick} />
            </div>
            {isExpanded && (
                <div className="bb-rl-accordion-content">
                    {children}
                </div>
            )}
        </div>
    );
}; 