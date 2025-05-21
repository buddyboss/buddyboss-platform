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
            this.setupViewToggle();
            this.setupCourseItemEvents();
            this.setupCourseFilters();
            this.setupSidebarToggle();
        },
        
        /**
         * Setup course grid/list view toggle
         */
        setupViewToggle: function() {
            var self = this;
            
            $('.bb-courses-view-toggle button').on('click', function(e) {
                e.preventDefault();
                
                var $this = $(this),
                    view = $this.data('view'),
                    $courseItems = $('.bb-course-items');
                
                // Remove active class from buttons
                $('.bb-courses-view-toggle button').removeClass('active');
                
                // Add active class to clicked button
                $this.addClass('active');
                
                // Remove view classes from course items
                $courseItems.removeClass('grid-view list-view');
                
                // Add current view class
                $courseItems.addClass(view + '-view');
                
                // Save view preference
                self.saveViewPreference(view);
            });
        },
        
        /**
         * Save user's course view preference via AJAX
         */
        saveViewPreference: function(view) {
            if (typeof bbReadylaunchLearnDash === 'undefined') {
                return;
            }
            
            $.ajax({
                url: bbReadylaunchLearnDash.ajax_url,
                type: 'POST',
                data: {
                    action: 'bb_readylaunch_learndash_save_view',
                    view: view,
                    nonce: bbReadylaunchLearnDash.nonce
                }
            });
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