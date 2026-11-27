/* global bbHeicConverter */
/**
 * Converts HEIC/HEIF photos to JPEG in the browser before a Dropzone uploads them.
 *
 * Browsers other than Safari can't display HEIC, so the file is converted before it is
 * queued and the server only ever receives a JPEG. The heic2any library (~1.3 MB) is
 * loaded on demand, the first time a HEIC file is added.
 *
 * @since BuddyBoss [BBVERSION]
 */
window.bp = window.bp || {};

( function ( exports, $ ) {
	'use strict';

	var settings       = window.bbHeicConverter || {},
		strings        = settings.strings || {},
		libraryPromise = null;

	exports.HeicConverter = {

		/**
		 * Whether the file is a HEIC/HEIF image, by mime type or by extension when the
		 * browser reports no type (Windows/Android).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {File} file File object.
		 *
		 * @return {boolean}
		 */
		isHeic: function ( file ) {
			if ( ! file || 'string' !== typeof file.name ) {
				return false;
			}

			if ( /^image\/hei[cf](-sequence)?$/i.test( file.type || '' ) ) {
				return true;
			}

			return /\.(heic|heif)$/i.test( file.name );
		},

		/**
		 * Whether this browser can run the conversion.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {boolean}
		 */
		isSupported: function () {
			return !! settings.library_url &&
				'function' === typeof window.Promise &&
				'function' === typeof window.Worker &&
				'function' === typeof window.Blob;
		},

		/**
		 * Whether the dropzone converts this file (used by forms to keep the submit button busy).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {Object} dropzone Dropzone instance.
		 * @param {File}   file     File object.
		 *
		 * @return {boolean}
		 */
		handles: function ( dropzone, file ) {
			// Mock files (saved media restored in edit mode) are plain objects and never convert.
			return !! ( dropzone && dropzone.bbHeicConverter ) && file instanceof window.Blob && this.isHeic( file );
		},

		/**
		 * Message shown when a HEIC/HEIF file can't be converted.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {string}
		 */
		getErrorMessage: function () {
			return strings.convert_error || 'This HEIC image could not be converted.';
		},

		/**
		 * Load heic2any once, on demand.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {Promise}
		 */
		loadLibrary: function () {
			if ( 'function' === typeof window.heic2any ) {
				return Promise.resolve( window.heic2any );
			}

			if ( libraryPromise ) {
				return libraryPromise;
			}

			libraryPromise = new Promise(
				function ( resolve, reject ) {
					var script    = document.createElement( 'script' ),
						amdDefine = window.define;

					// heic2any is UMD: hide an AMD loader so it registers the window global.
					if ( 'function' === typeof amdDefine && amdDefine.amd ) {
						window.define = undefined;
					}

					function restoreDefine() {
						if ( amdDefine ) {
							window.define = amdDefine;
						}
					}

					script.async   = true;
					script.src     = settings.library_url;
					script.onload  = function () {
						restoreDefine();
						if ( 'function' === typeof window.heic2any ) {
							resolve( window.heic2any );
						} else {
							libraryPromise = null;
							reject( new Error( 'heic2any is not available.' ) );
						}
					};
					script.onerror = function () {
						restoreDefine();
						libraryPromise = null;
						reject( new Error( 'Unable to load heic2any.' ) );
					};

					document.head.appendChild( script );
				}
			);

			return libraryPromise;
		},

		/**
		 * Convert a HEIC/HEIF file to a JPEG File.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {File} file HEIC/HEIF file.
		 *
		 * @return {Promise} Resolves with the JPEG File.
		 */
		convert: function ( file ) {
			var self = this,
				name = this.getJpegName( file.name );

			return this.loadLibrary().then(
				function ( heic2any ) {
					return heic2any(
						{
							blob    : file,
							toType  : 'image/jpeg',
							quality : settings.quality ? parseFloat( settings.quality ) : 0.9
						}
					).then(
						function ( result ) {
							// Multi-image HEIC (bursts, live photos) gives an array; keep the primary image.
							return self.toFile( $.isArray( result ) ? result[ 0 ] : result, name );
						},
						function ( error ) {
							// iOS sometimes names a JPEG ".heic"; heic2any refuses it but it can be uploaded as-is.
							var message = error && error.message ? error.message : String( error );
							if ( -1 !== message.indexOf( 'already browser readable' ) ) {
								return self.toFile( file, name );
							}

							throw error;
						}
					);
				}
			);
		},

		/**
		 * Wrap a Blob in a File with the given name.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {Blob}   blob Image data.
		 * @param {string} name File name.
		 *
		 * @return {File|Blob}
		 */
		toFile: function ( blob, name ) {
			var type = /^image\/(jpeg|png|gif)$/i.test( blob.type || '' ) ? blob.type : 'image/jpeg',
				converted;

			try {
				converted = new File( [ blob ], name, { type: type, lastModified: Date.now() } );
			} catch ( e ) {
				// Old Edge has no File constructor.
				converted      = new Blob( [ blob ], { type: type } );
				converted.name = name;
			}

			return converted;
		},

		/**
		 * Replace the .heic/.heif extension with .jpg.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {string} name Original file name.
		 *
		 * @return {string}
		 */
		getJpegName: function ( name ) {
			return String( name || 'image' ).replace( /\.(heic|heif)$/i, '' ) + '.jpg';
		},

		/**
		 * Flag a preview as converting and hide its remove link meanwhile, like during an upload,
		 * so forms never lose track of their busy state.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {File}    file       Dropzone file.
		 * @param {boolean} converting Whether the conversion is running.
		 */
		setConverting: function ( file, converting ) {
			if ( ! file.previewElement ) {
				return;
			}

			file.previewElement.classList.toggle( 'bb-heic-converting', converting );
			$( file.previewElement ).find( '[data-dz-remove], .dz-remove' ).css( 'visibility', converting ? 'hidden' : '' );
		},

		/**
		 * Make a Dropzone convert HEIC/HEIF files before they are queued.
		 *
		 * The file stays in the dropzone with its preview while it converts; Dropzone's
		 * `accept` waits for the conversion and `transformFile` uploads the JPEG.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {Object} dropzone Dropzone instance.
		 */
		attach: function ( dropzone ) {
			var self = this,
				options,
				originalAccept,
				originalTransformFile,
				originalEnqueueThumbnail;

			if ( ! dropzone || ! dropzone.options || dropzone.bbHeicConverter || ! this.isSupported() ) {
				return;
			}

			dropzone.bbHeicConverter = true;
			options                  = dropzone.options;
			originalAccept           = options.accept;
			originalTransformFile    = options.transformFile;

			// Accept HEIC by extension too: some browsers report an empty mime type for it.
			if ( options.acceptedFiles && ! /\.heic/i.test( options.acceptedFiles ) ) {
				options.acceptedFiles += ',.heic,.heif';
				if ( dropzone.hiddenFileInput ) {
					dropzone.hiddenFileInput.setAttribute( 'accept', options.acceptedFiles );
				}
			}

			options.accept = function ( file, done ) {
				var dz = this;

				if ( ! self.isHeic( file ) ) {
					return originalAccept.call( dz, file, done );
				}

				self.setConverting( file, true );

				self.convert( file ).then(
					function ( jpeg ) {
						var maxSize = dz.options.maxFilesize;

						// Removed while converting (uploader closed): never queue it.
						if ( -1 === dz.files.indexOf( file ) ) {
							return;
						}

						self.setConverting( file, false );

						// The JPEG is usually bigger than the HEIC, so check the limit again.
						if ( maxSize && jpeg.size > maxSize * 1024 * 1024 ) {
							return done(
								dz.options.dictFileTooBig
									.replace( '{{filesize}}', Math.round( jpeg.size / 1024 / 10.24 ) / 100 )
									.replace( '{{maxFilesize}}', maxSize )
							);
						}

						file.bbHeicJpeg       = jpeg;
						file.upload.filename  = jpeg.name;
						file.upload.total     = jpeg.size;

						$( file.previewElement ).find( '[data-dz-name]' ).text( jpeg.name );

						dz.createThumbnail(
							jpeg,
							dz.options.thumbnailWidth,
							dz.options.thumbnailHeight,
							dz.options.thumbnailMethod,
							true,
							function ( dataUrl ) {
								if ( dataUrl ) {
									dz.emit( 'thumbnail', file, dataUrl );
								}
							}
						);

						return originalAccept.call( dz, file, done );
					}
				).catch(
					function ( error ) {
						if ( -1 === dz.files.indexOf( file ) ) {
							return;
						}

						self.setConverting( file, false );

						if ( window.console ) {
							window.console.error( 'HEIC conversion failed:', error );
						}

						done( self.getErrorMessage() );
					}
				);
			};

			// Most browsers can't decode HEIC, and Dropzone's failed decode requests "[object Event]"; the JPEG's thumbnail is shown instead.
			if ( 'function' === typeof dropzone._enqueueThumbnail ) {
				originalEnqueueThumbnail   = dropzone._enqueueThumbnail;
				dropzone._enqueueThumbnail = function ( file ) {
					if ( self.isHeic( file ) ) {
						return;
					}

					return originalEnqueueThumbnail.apply( this, arguments );
				};
			}

			options.transformFile = function ( file, done ) {
				if ( file.bbHeicJpeg ) {
					return done( file.bbHeicJpeg );
				}

				return originalTransformFile.call( this, file, done );
			};
		}
	};

}( window.bp, jQuery ) );
