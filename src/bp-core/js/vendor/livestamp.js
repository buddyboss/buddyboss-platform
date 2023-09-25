// Livestamp.js / v1.1.2 / (c) 2012 Matt Bradley / MIT License

/* global wp */
(function ($, moment) {
	var updateInterval = 1e3,
		paused         = false,
		$livestamps    = $( [] ),
		__             = wp.i18n.__,
		_n             = wp.i18n._n,
		sprintf        = wp.i18n.sprintf,

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
						var to   = bbHumanizeFormat( data.moment );

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
		}
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

	bbHumanizeFormat = function ( timestamp ) {
		var currentDate = moment();
		var fromDate 	= moment( timestamp ).utc();

		const duration = moment.duration( currentDate.diff( fromDate ) );
		const since    = duration.asSeconds();
		var output     = '';

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

			if ( 'undefined' === typeof bb_livestamp.chunks[ i ] ) {
				output = bb_livestamp.right_now_text;
			} else {

				switch ( seconds ) {
					case parseInt( bb_livestamp.year_in_seconds ):
						output = ( count < 2 ) ? __( 'a year', 'buddyboss' ) :
						sprintf(
							_n( '%s year', '%s years', Math.floor( count ), 'buddyboss' ),
							Math.floor( count )
						);
						break;
					case ( parseInt( bb_livestamp.year_in_seconds ) / 6 ):
						var month_seconds = Math.floor( since / ( 30 * parseInt( bb_livestamp.day_in_seconds ) ) );

						output = sprintf(
							_n( '%d month', '%d months', month_seconds, 'buddyboss' ),
							month_seconds
						);
						break;
					case ( 30 * parseInt( bb_livestamp.day_in_seconds ) ):
						var week_seconds = Math.floor( since / parseInt( bb_livestamp.week_in_seconds ) );

						if ( count < 2 ) {
							output = sprintf(
								_n( '%d week', '%d weeks', week_seconds, 'buddyboss' ),
								week_seconds
							);
						} else {
							output = sprintf(
								_n( '%d month', '%d months', Math.floor( count ), 'buddyboss' ),
								Math.floor( count )
							);
						}
						break;
					case parseInt( bb_livestamp.week_in_seconds ):
						output = ( count < 2 ) ? __( 'a week', 'buddyboss' ) :
							sprintf(
								_n( '%d week', '%d weeks', Math.floor( count ), 'buddyboss' ),
								Math.floor( count )
							);
						break;
					case parseInt( bb_livestamp.day_in_seconds ):
						output = ( count < 2 ) ? __( 'a day ago', 'buddyboss' ) :
							sprintf(
								_n( '%d day', '%d days', Math.floor( count ), 'buddyboss' ),
								Math.floor( count )
							);
						break;
					case parseInt( bb_livestamp.hour_in_seconds ):
						output = ( count < 2 ) ? __( 'an hour', 'buddyboss' ) :
							sprintf(
								_n( '%d hour', '%d hours', Math.floor( count ), 'buddyboss' ),
								Math.floor( count )
							);
						break;
					case parseInt( bb_livestamp.minute_in_seconds ):
						output = ( count < 2 ) ? __( 'a minute', 'buddyboss' ) :
							sprintf(
								_n( '%d minute', '%d minutes', Math.floor( count ), 'buddyboss' ),
								Math.floor( count )
							);
						break;
					default:
						output = ( count < 2 ) ? __( 'a second', 'buddyboss' ) :
							sprintf(
								_n( '%d second', '%d seconds', Math.floor( count ), 'buddyboss' ),
								Math.floor( count )
							);
				}

				// No output, so happened right now.
				if ( ! parseInt( count ) ) {
					output = bb_livestamp.right_now_text;
				}
			}
		}

		// Append 'ago' to the end of time-since if not 'right now'.
		output = sprintf( bb_livestamp.ago_text, output );

		return output;
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
