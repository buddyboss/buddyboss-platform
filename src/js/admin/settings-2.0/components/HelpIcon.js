import React from 'react';

export const HelpIcon = ({ onClick, contentId }) => {
    const handleClick = () => {
        if (onClick) {
            onClick(contentId);
        }
    };

    return (
        <button 
            className="help-icon" 
            onClick={handleClick}
            aria-label="Help"
        >
            <i className="bb-icons-rl-question"></i>
        </button>
    );
};
