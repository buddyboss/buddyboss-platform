import React from 'react';

export const HelpSliderModal = ({ isOpen, onClose, children, title }) => {
  if (!isOpen) return null;
  return (
    <div className="bb-rl-help-modal-overlay" onClick={onClose}>
      <div className="bb-rl-help-modal" onClick={e => e.stopPropagation()}>
        <div className="bb-rl-help-modal-header">
          <h2>{title}</h2>
          <button className="bb-rl-help-modal-close" onClick={onClose} aria-label="Close">&times;</button>
        </div>
        <div className="bb-rl-help-modal-content">
          {children}
        </div>
      </div>
    </div>
  );
}; 