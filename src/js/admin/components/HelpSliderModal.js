import React, { useEffect, useRef, useState } from 'react';
import { extractH2Headings } from '../utils/headingExtractor';
import { __ } from '@wordpress/i18n';

export const HelpSliderModal = ({ isOpen, onClose, children, title }) => {
  const contentRef = useRef(null);
  const [toc, setToc] = useState([]);

  // Extract headings and inject IDs after content renders
  useEffect(() => {
    if (isOpen && contentRef.current) {
      const headings = extractH2Headings(contentRef.current);
      // Inject IDs
      headings.forEach(({ el, anchor }) => {
        el.id = anchor;
      });
      setToc(headings);
    }
  }, [isOpen, children]);

  // Click handler for TOC
  const handleTocClick = (anchor, e) => {
    e.preventDefault();
    const el = document.getElementById(anchor);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth' });
    }
  };

  if (!isOpen) return null;

  return (
    <div className="bb-rl-help-modal-overlay" onClick={onClose}>
      <div className="bb-rl-help-modal" onClick={e => e.stopPropagation()}>
        <div className="bb-rl-help-modal-header">
          <h2>{title}</h2>
          <button className="bb-rl-help-modal-close" onClick={onClose} aria-label="Close">
            <span>&times;</span>
          </button>
        </div>
        <div className="bb-rl-help-modal-content">
          {toc.length > 0 && (
            <nav className="bb-rl-help-modal-toc" style={{
              marginBottom: 20, padding: 15, background: '#f8f9fa', borderRadius: 0, borderLeft: '4px solid #007cba'
            }}>
              <h3 style={{ margin: '0 0 10px 0', fontSize: 16 }}>{__('Contents', 'buddyboss')}</h3>
              <ol style={{ listStyle: 'none', padding: 0, margin: 0 }}>
                {toc.map(({ text, anchor }) => (
                  <li key={anchor} style={{ marginBottom: 8 }}>
                    <a
                      href={`#${anchor}`}
                      onClick={e => handleTocClick(anchor, e)}
                      style={{
                        color: '#007cba',
                        textDecoration: 'none',
                        padding: '6px 8px',
                        borderRadius: 4,
                        display: 'block',
                        fontWeight: 500
                      }}
                    >
                      {text}
                    </a>
                  </li>
                ))}
              </ol>
            </nav>
          )}
          <div ref={contentRef} className="bb-rl-help-modal-main-content">
            {children}
          </div>
        </div>
      </div>
      <style>{`
        .bb-rl-help-modal-header {
          display: flex; align-items: center; justify-content: space-between; padding: 16px 24px 0 24px;
        }
        .bb-rl-help-modal-close {
          background: none; border: none; font-size: 28px; cursor: pointer; color: #888;
        }
        .bb-rl-help-modal-content {
          padding: 24px;
        }
        .bb-rl-help-modal-main-content h2 {
          scroll-margin-top: 10px;
        }
      `}</style>
    </div>
  );
}; 