import { useEffect, useRef, useState } from '@wordpress/element';
import { extractH2Headings } from '../../utils/headingExtractor';
import { __ } from '@wordpress/i18n';

export const HelpSliderModal = ({ isOpen, onClose, children, title }) => {
	const contentRef = useRef(null);
	const [toc, setToc] = useState([]);

	// Close on Escape key — only when no WordPress Modal is open above us.
	useEffect(() => {
		if (!isOpen) return;
		function handleKeyDown(e) {
			if ('Escape' !== e.key) return;
			// If a WordPress Modal is open, let it handle Escape first.
			if (document.querySelector('.components-modal__frame')) return;
			onClose();
		}
		document.addEventListener('keydown', handleKeyDown);
		return () => document.removeEventListener('keydown', handleKeyDown);
	}, [isOpen, onClose]);

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
			<div className="bb-rl-help-modal" onClick={(e) => e.stopPropagation()}>
				<div className="bb-rl-help-modal-header">
					<h2>{title}</h2>
					<button className="bb-rl-help-modal-close" onClick={onClose} aria-label={__('Close', 'buddyboss-platform')}>
						<span>&times;</span>
					</button>
				</div>
				<div className="bb-rl-help-modal-content">
					{toc.length > 0 && (
						<nav className="bb-rl-help-modal-toc">
							<h3>{__('Contents', 'buddyboss-platform')}</h3>
							<ol>
								{toc.map(({ text, anchor }) => (
									<li key={anchor}>
										<a
											href={`#${anchor}`}
											onClick={(e) => handleTocClick(anchor, e)}
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
		</div>
	);
}; 