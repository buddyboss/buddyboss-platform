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
        }
    };
    
    /**
     * DOM ready
     */
    $(document).ready(function() {
        BBReadyLaunchLearnDash.init();
    });
    
})(jQuery); 