// Livestamp.js / v1.1.2 / (c) 2012 Matt Bradley / MIT License
/* global moment, bb_livestamp */
(function ($, moment) {
	var updateInterval = 1e3,
		paused         = false,
		$livestamps    = $( [] ),

	init = function () {
		livestampGlobal.resume();
	},

	prep = function ( $el, timestamp ) {
		var oldData = $el.data( 'livestampdata' );
		if ( typeof timestamp == 'number' ) {
			timestamp *= 1e3;
		}

		$el.removeAttr( 'data-livestamp' ).removeData( 'livestamp' );

		timestamp = moment( timestamp );
		if ( moment.isMoment( timestamp ) && ! isNaN( +timestamp ) ) {
			var newData = $.extend( {}, {'original': $el.contents()}, oldData );

			newData.moment = moment( timestamp );

			$el.data( 'livestampdata', newData ).empty();
			$livestamps.push( $el[0] );
		}
	},

	run = function () {
		if ( paused ) {
			return;
		}
		livestampGlobal.update();
		setTimeout( run, updateInterval );
	},

	livestampGlobal = {
		update: function () {
			$( '[data-livestamp]' ).each(
				function () {
					var $this = $( this );
					prep( $this, $this.data( 'livestamp' ) );
				}
			);

			var toRemove = [];
			$livestamps.each(
				function () {
					var $this = $( this ),
						data  = $this.data( 'livestampdata' );

					if ( data === undefined ) {
						toRemove.push( this );
					} else if ( moment.isMoment( data.moment ) ) {
						var from = $this.html();
						var to   = livestampGlobal.bbHumanizeFormat( data.moment ); // Do not update this function because it's updated based on this PR https://github.com/buddyboss/buddyboss-platform/pull/3339.

						if ( from != to ) {
							var e = $.Event( 'change.livestamp' );
							$this.trigger( e, [from, to] );
							if ( ! e.isDefaultPrevented() ) {
								$this.html( to );
							}
						}
					}
				}
			);

			$livestamps = $livestamps.not( toRemove );
		},

		pause: function () {
			paused = true;
		},

		resume: function () {
			paused = false;
			run();
		},

		interval: function (interval) {
			if ( interval === undefined ) {
				return updateInterval;
			}
			updateInterval = interval;
		},

		// Do not remove this function. This is a custom function to render based on platform bp_core_current_time function.
		bbHumanizeFormat: function ( timestamp ) {
			var currentDate = moment();
			var fromDate 	= moment( timestamp ).utc();

			var duration = moment.duration( currentDate.diff( fromDate ) );
			var since    = duration.asSeconds();
			var output   = '';

			if ( 0 > since ) {
				output = bb_livestamp.unknown_text;
			} else {
				var count   = 0;
				var seconds = 0;

				for ( var i = 0, j = bb_livestamp.chunks.length; i < j; ++i ) {
					seconds = bb_livestamp.chunks[ i ];

					// Finding the biggest chunk (if the chunk fits, break).
					count = Math.floor( since / seconds );
					if ( 0 !== count ) {
						break;
					}
				}

				var floor_count = Math.floor( count );

				if ( 'undefined' === typeof bb_livestamp.chunks[ i ] ) {
					output = bb_livestamp.right_now_text;
				} else {

					switch ( seconds ) {
						case parseInt( bb_livestamp.year_in_seconds ):
							output = ( count < 2 ) ? bb_livestamp.year_text : livestampGlobal.bbConcateString( count, bb_livestamp.years_text );
							break;
						case ( parseInt( bb_livestamp.year_in_seconds ) / 6 ):
							var month_seconds = Math.floor( since / ( 30 * parseInt( bb_livestamp.day_in_seconds ) ) );

							output = ( month_seconds < 2 ) ? bb_livestamp.month_text : livestampGlobal.bbConcateString( month_seconds, bb_livestamp.months_text );
							break;
						case ( 30 * parseInt( bb_livestamp.day_in_seconds ) ):
							var week_seconds = Math.floor( since / parseInt( bb_livestamp.week_in_seconds ) );

							if ( count < 2 ) {
								output = ( week_seconds < 2 ) ? bb_livestamp.week_text : livestampGlobal.bbConcateString( week_seconds, bb_livestamp.weeks_text );
							} else {
								output = ( count < 2 ) ? bb_livestamp.month_text : livestampGlobal.bbConcateString( floor_count, bb_livestamp.months_text );
							}
							break;
						case parseInt( bb_livestamp.week_in_seconds ):
							output = ( count < 2 ) ? bb_livestamp.week_text : livestampGlobal.bbConcateString( floor_count, bb_livestamp.weeks_text );
							break;
						case parseInt( bb_livestamp.day_in_seconds ):
							output = ( count < 2 ) ? bb_livestamp.day_text : livestampGlobal.bbConcateString( floor_count, bb_livestamp.days_text );
							break;
						case parseInt( bb_livestamp.hour_in_seconds ):
							output = ( count < 2 ) ? bb_livestamp.hour_text : livestampGlobal.bbConcateString( floor_count, bb_livestamp.hours_text );
							break;
						case parseInt( bb_livestamp.minute_in_seconds ):
							output = ( count < 2 ) ? bb_livestamp.minute_text : livestampGlobal.bbConcateString( floor_count, bb_livestamp.minutes_text );
							break;
						default:
							output = ( count < 2 ) ? bb_livestamp.second_text : livestampGlobal.bbConcateString( floor_count, bb_livestamp.seconds_text );
					}

					// No output, so happened right now.
					if ( ! parseInt( count ) ) {
						output = bb_livestamp.right_now_text;
					}
				}
			}

			output = bb_livestamp.ago_text.replace( '%s', output );

			return output;
		},

		// Do not remove this function.
		bbConcateString: function ( string1, string2 ) {
			return string1 + ' ' + string2;
		},
	},

	livestampLocal = {
		add: function ( $el, timestamp ) {
			if ( typeof timestamp == 'number' ) {
				timestamp *= 1e3;
			}

			timestamp = moment( timestamp );

			if ( moment.isMoment( timestamp ) && ! isNaN( +timestamp ) ) {
				$el.each(
					function () {
						prep( $( this ), timestamp );
					}
				);
				livestampGlobal.update();
			}

			return $el;
		},

		destroy: function ( $el ) {
			$livestamps = $livestamps.not( $el );
			$el.each(
				function () {
					var $this = $( this ),
						data  = $this.data( 'livestampdata' );

					if ( data === undefined ) {
						return $el;
					}

					$this.html( data.original ? data.original : '' ).removeData( 'livestampdata' );
				}
			);

			return $el;
		},

		isLivestamp: function ($el) {
			return $el.data( 'livestampdata' ) !== undefined;
		}
	};

	$.livestamp = livestampGlobal;
	$( init );
	$.fn.livestamp = function (method, options) {
		if ( ! livestampLocal[method] ) {
			options = method;
			method  = 'add';
		}

		return livestampLocal[method]( this, options );
	};
})( jQuery, moment );
