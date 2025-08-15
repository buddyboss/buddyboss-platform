import { useState, useCallback, useEffect } from '@wordpress/element';
import useEmblaCarousel from 'embla-carousel-react';
import AutoHeight from 'embla-carousel-auto-height';
import { PreviewPages } from './previewPages';

export const PreviewSlider = ({ formData = {} }) => {
    const [emblaRef, emblaApi] = useEmblaCarousel({ 
        loop: false,
        startIndex: 0,
        skipSnaps: false,
        dragFree: false
    }, [AutoHeight()]);
    
    const [selectedIndex, setSelectedIndex] = useState(0);
    const [scrollSnaps, setScrollSnaps] = useState([]);

    const scrollTo = useCallback((index) => emblaApi && emblaApi.scrollTo(index), [emblaApi]);

    const onInit = useCallback((emblaApi) => {
        setScrollSnaps(emblaApi.scrollSnapList());
    }, []);

    const onSelect = useCallback((emblaApi) => {
        setSelectedIndex(emblaApi.selectedScrollSnap());
    }, []);

    useEffect(() => {
        if (!emblaApi) return;

        onInit(emblaApi);
        onSelect(emblaApi);
        emblaApi.on('reInit', onInit);
        emblaApi.on('select', onSelect);
    }, [emblaApi, onInit, onSelect]);

    const slides = [
        { page: 'activity', label: 'Activity Feed' },
        { page: 'members', label: 'Members' },
        { page: 'groups', label: 'Groups' }
    ];

    return (
        <div className="bb-rl-preview-slider">
            {/* Dot Navigation */}
            <div className="bb-rl-preview-slider-dots">
                {scrollSnaps.map((_, index) => (
                    <button
                        key={index}
                        className={`bb-rl-preview-slider-dot ${
                            index === selectedIndex ? 'bb-rl-preview-slider-dot-active' : ''
                        }`}
                        onClick={() => scrollTo(index)}
                        aria-label={`Go to slide ${index + 1}`}
                    />
                ))}
            </div>

            <div className="bb-rl-preview-slider-buttons">
            <button className={`bb-rl-preview-slider-prev ${selectedIndex === 0 ? 'bb-rl-preview-slider-button-disabled' : ''}`} onClick={() => scrollTo(selectedIndex - 1)}><i className='bb-icons-rl-caret-left'></i></button>
            <button className={`bb-rl-preview-slider-next ${selectedIndex === slides.length - 1 ? 'bb-rl-preview-slider-button-disabled' : ''}`} onClick={() => scrollTo(selectedIndex + 1)}><i className='bb-icons-rl-caret-right'></i></button>
            </div>

            {/* Slider Container */}
            <div className="bb-rl-preview-slider-viewport" ref={emblaRef}>
                <div className="bb-rl-preview-slider-container">
                    {slides.map((slide, index) => (
                        <div key={index} className="bb-rl-preview-slider-slide">
                            <PreviewPages page={slide.page} formData={formData} />
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}; 