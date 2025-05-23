/**
 * LearnDash JavaScript for ReadyLaunch
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

(function($) {
    'use strict';
    
    /**
     * LearnDash ReadyLaunch functionality
     */
    var BBReadyLaunchLearnDash = {
        
        /**
         * Initialization
         */
        init: function() {
            this.switchLdGridList();
            this.setupCourseItemEvents();
            this.setupCourseFilters();
            this.setupSidebarToggle();
            this.setupCourseCardPopups();
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
         * Setup course item events
         */
        setupCourseItemEvents: function() {
            // Course item hover effects
            $('.bb-course-item').on({
                mouseenter: function() {
                    $(this).addClass('hover');
                },
                mouseleave: function() {
                    $(this).removeClass('hover');
                }
            });
        },
        
        /**
         * Setup course filters
         */
        setupCourseFilters: function() {
            var self = this;
            
            // Course category filter
            $('#bb-courses-category-filter').on('change', function() {
                self.filterCourses();
            });
            
            // Course instructor filter
            $('#bb-courses-instructor-filter').on('change', function() {
                self.filterCourses();
            });
            
            // Course sort filter
            $('#bb-courses-sort-filter').on('change', function() {
                self.filterCourses();
            });
            
            // Search courses
            $('#bb-courses-search-form').on('submit', function(e) {
                e.preventDefault();
                self.filterCourses();
            });
        },
        
        /**
         * Filter courses via AJAX
         */
        filterCourses: function() {
            var $courseItems = $('.bb-course-items'),
                category = $('#bb-courses-category-filter').val(),
                instructor = $('#bb-courses-instructor-filter').val(),
                sort = $('#bb-courses-sort-filter').val(),
                search = $('#bb-courses-search').val();
            
            // Show loading state
            $courseItems.addClass('loading');
            
            // Make AJAX request
            $.ajax({
                url: bbReadylaunchLearnDash.ajax_url,
                type: 'POST',
                data: {
                    action: 'bb_readylaunch_learndash_filter_courses',
                    category: category,
                    instructor: instructor,
                    sort: sort,
                    search: search,
                    nonce: bbReadylaunchLearnDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Replace course items
                        $courseItems.html(response.data.content);
                        
                        // Update course count
                        $('.bb-courses-count').text(response.data.count);
                        
                        // Initialize course item events
                        BBReadyLaunchLearnDash.setupCourseItemEvents();
                    }
                },
                complete: function() {
                    // Remove loading state
                    $courseItems.removeClass('loading');
                }
            });
        },
        
        /**
         * Setup sidebar toggle for mobile
         */
        setupSidebarToggle: function() {
            // Mobile sidebar toggle
            $('.bb-course-sidebar-toggle').on('click', function(e) {
                e.preventDefault();
                
                var $sidebar = $('.bb-learndash-sidebar');
                
                $sidebar.toggleClass('active');
                $('body').toggleClass('bb-sidebar-open');
            });
            
            // Close sidebar when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.bb-learndash-sidebar, .bb-course-sidebar-toggle').length) {
                    $('.bb-learndash-sidebar').removeClass('active');
                    $('body').removeClass('bb-sidebar-open');
                }
            });
        },
        
        /**
         * Setup dynamic positioning for course card popups
         */
        setupCourseCardPopups: function() {
            var self = this;
            
            // Handle popup positioning on hover
            $('.bb-rl-course-card').on({
                mouseenter: function() {
                    var $card = $(this);
                    var $popup = $card.find('.bb-rl-course-card-popup');
                    
                    if ($popup.length) {
                        self.positionCoursePopup($card, $popup);
                    }
                }
            });
            
            // Reposition popups on window resize
            $(window).on('resize', function() {
                $('.bb-rl-course-card:hover').each(function() {
                    var $card = $(this);
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
                $popup.removeClass('bb-rl-popup-left bb-rl-popup-right');
                return;
            }
            
            var cardRect = $card[0].getBoundingClientRect();
            var popupWidth = 296;
            var windowWidth = $(window).width();
            var spaceRight = windowWidth - cardRect.right;
            var spaceLeft = cardRect.left;
            var minSpaceRequired = popupWidth + 20;
            var scrollOffset = $(window).scrollLeft();
            
            // Reset positioning classes
            $popup.removeClass('bb-rl-popup-left bb-rl-popup-right');
            
            // Check if there's enough space on the right
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
            
            // Ensure popup doesn't go off-screen vertically
            var cardTop = cardRect.top;
            var cardHeight = cardRect.height;
            var popupHeight = 200; // Approximate popup height
            var windowHeight = $(window).height();
            
            // Adjust vertical position if needed
            if (cardTop + popupHeight/2 > windowHeight) {
                $popup.css('transform', 'translateY(calc(-50% - ' + (cardTop + popupHeight/2 - windowHeight + 20) + 'px))');
            } else if (cardTop - popupHeight/2 < 0) {
                $popup.css('transform', 'translateY(calc(-50% + ' + Math.abs(cardTop - popupHeight/2 + 20) + 'px))');
            } else {
                $popup.css('transform', 'translateY(-50%)');
            }
        }
    };
    
    /**
     * DOM ready
     */
    $(document).ready(function() {
        BBReadyLaunchLearnDash.init();
    });
    
})(jQuery); 