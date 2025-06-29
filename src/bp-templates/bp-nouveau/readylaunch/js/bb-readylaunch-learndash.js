/* global bbReadylaunchLearnDash */
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
            this.addEvents();
            this.autoExpandCurrentLesson();
        },

        addEvents: function() {
            $( document ).on( 'click', '.bb-rl-ld-lesson-list .ld-expand-button', this.handleLessonExpand );
            $( document ).on( 'click', '.bb-rl-course-content-header .ld-expand-button', this.handleAllLessonsExpand );
            $( document ).on( 'click', '.bb-rl-video-play-overlay', this.handleVideoPlayOverlay );
        },

        handleLessonExpand: function(e) {
            e.preventDefault();
            var $parentEl = $(this).closest('.ld-item-list-item');
            var $containerElm = $parentEl.find('.ld-item-list-item-expanded');
            var $expandElm = $parentEl.find('.ld-item-list-item-preview:has(.ld-expand-button)');
            $expandElm.toggleClass('ld-expanded');
            $containerElm.toggleClass('ld-expanded').slideToggle( 300 );
        },

        handleAllLessonsExpand: function(e) {
            e.preventDefault();
            $( this ).toggleClass( 'ld-expanded' );
            if( $( this ).hasClass( 'ld-expanded' ) ) {
                $( this ).find( '.ld-text' ).text( $(this).data( 'ld-collapse-text' ) );
            } else {
                $( this ).find( '.ld-text' ).text( $(this).data( 'ld-expand-text' ) );
            }
            var $parentEl = $(this).closest( '.bb-rl-course-content-header' ).next( '.ld-lesson-list' ).find( '.ld-item-list-item' );
            var $containerElm = $parentEl.find( '.ld-item-list-item-expanded' );
            var $expandElm = $parentEl.find( '.ld-item-list-item-preview:has(.ld-expand-button)' );
            $expandElm.toggleClass('ld-expanded');
            $containerElm.toggleClass('ld-expanded').slideToggle( 300 );
        },

        handleVideoPlayOverlay : function ( e ) {
            e.preventDefault();
            var $overlay        = $( this );
            var $courseFigure   = $overlay.closest( '.bb-rl-course-figure' );
            var $videoPreview   = $overlay.closest( '.bb-rl-video-preview-container' );
            var $videoContainer = $videoPreview.find( '.bb-rl-video-embed-container' );

            if ( $courseFigure.length ) {
                $courseFigure.addClass( 'video-active' );
            }

            // Show the video container and hide the preview image
            if ( $videoContainer.length ) {

                // Handle YouTube video playback
                var $iframe = $videoContainer.find( 'iframe' );
                if ( $iframe.length ) {
                    var iframeSrc = $iframe.attr( 'src' );
                    var separator, autoplaySrc;

                    // Check if it's a YouTube video
                    if ( iframeSrc && iframeSrc.indexOf( 'youtube.com' ) !== -1 ) {
                        // Add autoplay parameter to YouTube URL
                        separator   = iframeSrc.indexOf( '?' ) !== -1 ? '&' : '?';
                        autoplaySrc = iframeSrc + separator + 'autoplay=1&mute=1';
                        $iframe.attr( 'src', autoplaySrc );
                    }
                    // Check if it's a Vimeo video
                    else if ( iframeSrc && iframeSrc.indexOf( 'vimeo.com' ) !== -1 ) {
                        // Add autoplay parameter to Vimeo URL
                        separator   = iframeSrc.indexOf( '?' ) !== -1 ? '&' : '?';
                        autoplaySrc = iframeSrc + separator + 'autoplay=1&muted=1';
                        $iframe.attr( 'src', autoplaySrc );
                    }
                }
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
                    
                    if ( BBReadyLaunchLearnDash.ajax_request ) {
						BBReadyLaunchLearnDash.ajax_request.abort();
					}

					var rlContainer = $( this ).closest( '.bb-rl-container' );
                    var gridFilters = $( this ).closest( '.bb-rl-grid-filters' );

					courseLoopSelector = rlContainer.find( '.bb-rl-courses-grid' );
					if ( $( this ).hasClass( 'layout-list-view' ) ) {
						gridFilters.find( '.layout-view-course' ).removeClass( 'active' );
						courseLoopSelector.removeClass( 'grid' );
						$( this ).addClass( 'active' );
                        courseLoopSelector.addClass('list');
                        BBReadyLaunchLearnDash.ajax_request = $.ajax(
							{
								method  : 'POST',
								url     : bbReadylaunchLearnDash.ajaxurl,
								nonce   : bbReadylaunchLearnDash.nonce_list_grid,
								data    : 'action=bb_rl_lms_save_view&option=bb_layout_view&object=' + $( this ).parent().attr( 'data-view' ) + '&type=list&nonce=' + bbReadylaunchLearnDash.nonce_list_grid,
								success : function () {
								}
							}
						);
					} else {
						gridFilters.find( '.layout-view-course' ).removeClass( 'active' );
						courseLoopSelector.removeClass( 'list' );
						$( this ).addClass( 'active' );
                        courseLoopSelector.addClass('grid');
                        BBReadyLaunchLearnDash.ajax_request = $.ajax(
							{
								method  	: 'POST',
								url     	: bbReadylaunchLearnDash.ajaxurl,
								nonce       : bbReadylaunchLearnDash.nonce_list_grid,
								data    	: 'action=bb_rl_lms_save_view&option=bb_layout_view&object=' + $( this ).parent().attr( 'data-view' ) + '&type=grid&nonce=' + bbReadylaunchLearnDash.nonce_list_grid,
								success 	: function () {
								}
							}
						);
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
            $('#ld-course-cats').on('change', function() {
                self.filterCourses();
            });
            
            // Course instructor filter
            $('#ld-course-instructors').on('change', function() {
                self.filterCourses();
            });
            
            // Course sort filter
            $('#ld-course-orderby').on('change', function() {
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
        filterCourses : function () {
            var $courseList = $( '.bb-rl-courses-list' ),
                category    = $( '#ld-course-cats' ).val(),
                instructor  = $( '#ld-course-instructors' ).val(),
                orderby     = $( '#ld-course-orderby' ).val();

            // Show loading state.
            $courseList.addClass( 'loading' );

            // Build query string.
            var params = [];
            if ( orderby ) {
                params.push( 'orderby=' + encodeURIComponent( orderby ) );
            }
            if ( category ) {
                params.push( 'categories=' + encodeURIComponent( category ) );
            }
            if ( instructor ) {
                params.push( 'instructors=' + encodeURIComponent( instructor ) );
            }
            var newUrl = bbReadylaunchLearnDash.courses_url + (
                params.length ? '?' + params.join( '&' ) : ''
            );

            var view = $( '.bb-rl-grid-filters .layout-view.active' ).data( 'view' );

            // Fetch the new HTML
            $.get( newUrl, function ( response ) {
                // Parse the response and extract the grid.
                var html          = $( '<div>' ).html( response );
                var newGrid       = html.find( '.bb-rl-courses-list' ).html();
                var newCount      = html.find('.bb-rl-heading-count').text();
                var newPagination = html.find('.bb-rl-course-pagination').html();

                // Update the grid and count
                if ( $courseList.length ) {
                    $courseList.html( newGrid );
                } else {
                    // Replace the whole .bb-rl-courses-list with the new HTML.
                    $( '.bb-rl-courses-list' ).html( newGrid );
                }
                $( '.bb-rl-heading-count' ).text( newCount );
                $( '.bb-rl-course-pagination' ).html( newPagination );
                $courseList.find( '.bb-rl-courses-grid' ).addClass( view );
                $courseList.removeClass( 'loading' );

                // Update the browser URL
                window.history.replaceState( {}, '', newUrl );
            } );
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

        /**
         * Automatically expand current lesson on page load
         */
        autoExpandCurrentLesson: function() {
            $('.ld-item-list-item.bb-rl-current-lesson .ld-expand-button').each(function() {
                $(this).trigger('click');
            });
        },
    };
    
    /**
     * DOM ready
     */
    $(document).ready(function () {
        BBReadyLaunchLearnDash.ajax_request = null;
        BBReadyLaunchLearnDash.init();
    });
    
})(jQuery); 