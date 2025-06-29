/**
 * MemberPress Courses JavaScript for ReadyLaunch
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

(function($) {
    'use strict';
    
    /**
     * MemberPress Courses ReadyLaunch functionality
     */
    var BBReadyLaunchMeprlms = {
        
        /**
         * Initialization
         */
        init: function() {
            this.switchLdGridList();
            this.setupCourseCardPopups();
            this.addEvents();
            this.sideNavToggle();
        },

        addEvents: function() {
            $( document ).on( 'click', '.bb-rl-ld-lesson-list .ld-expand-button', this.handleLessonExpand );
        },

        handleLessonExpand: function(e) {
            e.preventDefault();
        },

        sideNavToggle: function() {
            if ( $('body').hasClass('mpcs-sidebar-with-accordion') ) {
                var headers = $( '.mpcs-sidebar-content .mpcs-section-header' );
                var current = $( '.mpcs-sidebar-content .mpcs-lesson.current' );
                
                if ( current.length ) {
                    var header = current.closest( '.mpcs-section' ).find( '.mpcs-section-header' );
                    header.addClass( 'active' );
                    header.next( '.mpcs-lessons' ).css( 'display', 'block' );

                    var $currentLesson = header.closest( '.mpcs-section' ).find( '.mpcs-lesson.current' );
                    $( '.mpcs-sidebar-content' ).animate({
                        scrollTop: $currentLesson.offset().top - 400
                    }, 1000);
                }
                
                $( headers ).on( 'click', function() {
                    var $this = $( this );
                    $this.toggleClass( 'active' );
                    
                    if ( $this.hasClass( 'active' ) ) {
                        $this.next( '.mpcs-lessons' ).css( 'display', 'block' );
                    } else {
                        $this.next( '.mpcs-lessons' ).css( 'display', 'none' );
                    }
                });
            }
        },

        /**
         * Setup course grid/list view toggle
         */
        switchLdGridList: function() {

			var courseLoopSelector = $( '.bb-rl-courses-grid' );
			$( document ).on(
				'click',
				'.bb-rl-grid-filters .layout-view-course:not(.active)',
				function(e) {
					e.preventDefault();

					if (
						'undefined' === typeof $( this ).parent().attr( 'data-view' ) ||
						'ld-course' !== $( this ).parent().attr( 'data-view' )
					) {
						return;
					}

					var rlContainer = $( this ).closest( '.bb-rl-container' );
                    var gridFilters = $( this ).closest( '.bb-rl-grid-filters' );

					courseLoopSelector = rlContainer.find( '.bb-rl-courses-grid' );
					if ( $( this ).hasClass( 'layout-list-view' ) ) {
						gridFilters.find( '.layout-view-course' ).removeClass( 'active' );
						courseLoopSelector.removeClass( 'grid' );
						$( this ).addClass( 'active' );
						courseLoopSelector.addClass( 'list' );
					} else {
						gridFilters.find( '.layout-view-course' ).removeClass( 'active' );
						courseLoopSelector.removeClass( 'list' );
						$( this ).addClass( 'active' );
						courseLoopSelector.addClass( 'grid' );
					}
				}
			);
		},

        /**
         * Setup dynamic positioning for course card popups
         */
        setupCourseCardPopups: function() {
            var self = this;
            
            // Handle popup positioning on hover
            $('.bb-rl-course-card .bb-rl-course-title a').on({
                mouseenter: function() {
                    var $card = $(this).closest('.bb-rl-course-card');
                    var $popup = $card.find('.bb-rl-course-card-popup');
                    
                    // Clear any existing timeout for this card
                    var cardTimeout = $card.data('hoverTimeout');
                    if (cardTimeout) {
                        clearTimeout(cardTimeout);
                        $card.removeData('hoverTimeout');
                    }
                    
                    // Close all other active popups
                    $('.bb-rl-course-card.bb-rl-card-popup-active').not($card).each(function() {
                        var $activeCard = $(this);
                        var otherTimeout = $activeCard.data('hoverTimeout');
                        if (otherTimeout) {
                            clearTimeout(otherTimeout);
                            $activeCard.removeData('hoverTimeout');
                        }
                        $activeCard.removeClass('bb-rl-card-popup-active');
                    });
                    
                    // Show current popup
                    $card.addClass('bb-rl-card-popup-active');
                    
                    if ($popup.length) {
                        self.positionCoursePopup($card, $popup);
                    }
                },
                mouseleave: function() {
                    var $card = $(this).closest('.bb-rl-course-card');
                    
                    // Clear any existing timeout for this card
                    var cardTimeout = $card.data('hoverTimeout');
                    if (cardTimeout) {
                        clearTimeout(cardTimeout);
                    }
                    
                    // Add delay before hiding popup
                    var timeout = setTimeout(function() {
                        $card.removeClass('bb-rl-card-popup-active');
                        $card.removeData('hoverTimeout');
                    }, 300);
                    
                    $card.data('hoverTimeout', timeout);
                }
            });

            // Handle popup hover events to keep popup open
            $(document).on({
                mouseenter: function() {
                    var $card = $(this).closest('.bb-rl-course-card');
                    
                    // Clear any existing timeout for this card
                    var cardTimeout = $card.data('hoverTimeout');
                    if (cardTimeout) {
                        clearTimeout(cardTimeout);
                        $card.removeData('hoverTimeout');
                    }
                },
                mouseleave: function() {
                    var $card = $(this).closest('.bb-rl-course-card');
                    
                    // Clear any existing timeout for this card
                    var cardTimeout = $card.data('hoverTimeout');
                    if (cardTimeout) {
                        clearTimeout(cardTimeout);
                    }
                    
                    // Add delay before hiding popup
                    var timeout = setTimeout(function() {
                        $card.removeClass('bb-rl-card-popup-active');
                        $card.removeData('hoverTimeout');
                    }, 300);
                    
                    $card.data('hoverTimeout', timeout);
                }
            }, '.bb-rl-course-card-popup');
            
            // Reposition popups on window resize
            $(window).on('resize', function() {
                $('.bb-rl-course-card .bb-rl-course-title a:hover').each(function() {
                    var $card = $(this).closest('.bb-rl-course-card');
                    var $popup = $card.find('.bb-rl-course-card-popup');
                    
                    if ($popup.length) {
                        self.positionCoursePopup($card, $popup);
                    }
                });
            });
        },
        
        /**
         * Position course popup dynamically based on available space
         */
        positionCoursePopup: function($card, $popup) {
            // Don't position on mobile - let CSS handle it
            if ($(window).width() <= 768) {
                $popup.removeClass('bb-rl-popup-left bb-rl-popup-right bb-rl-popup-top bb-rl-popup-bottom');
                return;
            }
            
            var $coursesGrid = $card.closest('.bb-rl-courses-grid');
            var isListView = $coursesGrid.hasClass('list');
            
            // Reset all positioning classes
            $popup.removeClass('bb-rl-popup-left bb-rl-popup-right bb-rl-popup-top bb-rl-popup-bottom');
            
            if (isListView) {
                // List view: position above/below
                this.positionPopupVertical($card, $popup);
            } else {
                // Grid view: position left/right
                this.positionPopupHorizontal($card, $popup);
            }
        },
        
        /**
         * Position popup horizontally (left/right) for grid view
         */
        positionPopupHorizontal: function($card, $popup) {
            var cardRect = $card[0].getBoundingClientRect();
            var popupWidth = 296; // Width from CSS
            var windowWidth = $(window).width();
            var spaceRight = windowWidth - cardRect.right;
            var spaceLeft = cardRect.left;
            var minSpaceRequired = popupWidth + 20; // Add some padding
            
            // Check if there's enough space on the right (preferred)
            if (spaceRight >= minSpaceRequired) {
                // Position on the right
                $popup.addClass('bb-rl-popup-right');
            } else if (spaceLeft >= minSpaceRequired) {
                // Position on the left
                $popup.addClass('bb-rl-popup-left');
            } else {
                // If neither side has enough space, choose the side with more space
                if (spaceRight >= spaceLeft) {
                    $popup.addClass('bb-rl-popup-right');
                } else {
                    $popup.addClass('bb-rl-popup-left');
                }
            }
        },
        
        /**
         * Position popup vertically for list view
         */
        positionPopupVertical: function($card, $popup) {
            var cardRect = $card[0].getBoundingClientRect();
            var popupHeight = 250; // Approximate popup height
            var windowHeight = $(window).height();
            var spaceAbove = cardRect.top;
            var spaceBelow = windowHeight - cardRect.bottom;
            var minSpaceRequired = popupHeight + 20; // Add some padding
            
            // Check if there's enough space above (preferred for list view)
            if (spaceAbove >= minSpaceRequired) {
                // Position above
                $popup.addClass('bb-rl-popup-top');
            } else if (spaceBelow >= minSpaceRequired) {
                // Position below
                $popup.addClass('bb-rl-popup-bottom');
            } else {
                // If neither position has enough space, choose the one with more space
                if (spaceAbove >= spaceBelow) {
                    $popup.addClass('bb-rl-popup-top');
                } else {
                    $popup.addClass('bb-rl-popup-bottom');
                }
            }
            
            // Reset any previous transform adjustments
            $popup.css('transform', '');
        },
    };
    
    /**
     * DOM ready
     */
    $(document).ready(function() {
        BBReadyLaunchMeprlms.init();
    });
    
})(jQuery); 