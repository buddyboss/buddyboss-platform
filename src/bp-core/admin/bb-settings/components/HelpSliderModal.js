import React, { useEffect } from 'react';

export const HelpSliderModal = ({ isOpen, onClose, children, title }) => {
  // Add/remove class to body when modal open state changes
  useEffect(() => {
    if (isOpen) {
      document.body.classList.add('bb-rl-help-modal-open');
    } else {
      document.body.classList.remove('bb-rl-help-modal-open');
    }
    
    // Cleanup on unmount
    return () => {
      document.body.classList.remove('bb-rl-help-modal-open');
    };
  }, [isOpen]);

  if (!isOpen) return null;
  return (
    <div className="bb-rl-help-modal-overlay" onClick={onClose}>
      <div className="bb-rl-help-modal" onClick={e => e.stopPropagation()}>
        <div className="bb-rl-help-modal-header">
          <h2>{title}</h2>
          <button className="bb-rl-help-modal-close" onClick={onClose} aria-label="Close"><i className="bb-icons-rl-x"></i></button>
        </div>
        <div className="bb-rl-help-modal-content">
          {children}
        </div>
      </div>
    </div>
  );
}; 