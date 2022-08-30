;(function($, $$) {
	var giphy = function(key) {
		var self = this;
		self.key = key;
		self.version = 1;
		self.url = 'https://api.giphy.com/v' + self.version + '/';
		self.stickers = {};

		// Search
		// --
		// https://github.com/Giphy/GiphyAPI#search-endpoint
		self.search = function(params, succCb, errCb) {
			var url = 'gifs/search';

			// Check for required parameters
			if('q' in params) {
				url += '?q=' + params.q;
				delete params.q;
			} else {
				var eMsg = 'giphy.js: No query.';
				if(errCb) errCb(eMsg);
				else console.error(eMsg);
			}

			return self.request(url, params, succCb, errCb);
		};

		// Get GIF by ID
		// --
		// https://github.com/Giphy/GiphyAPI#get-gifs-by-id-endpoint
		self.gif = function(params, succCb, errCb) {
			var url = 'gifs/';

			// Check for required parameters
			if('id' in params) {
				url += params.id;
				delete params.id;
			} else {
				var eMsg = 'giphy.js: No ID.';
				if(errCb) errCb(eMsg);
				else console.error(eMsg);
			}
			self.request(url, params, succCb, errCb);
		};

		// Get GIFs by ID
		// --
		// https://github.com/Giphy/GiphyAPI#get-gif-by-id-endpoint
		self.gifs = function(params, succCb, errCb) {
			var url = 'gifs?ids=';

			// Check for required parameters
			if('ids' in params) {
				params.ids.forEach(function(id, idx) {
					url += id;
					if(idx+1 != params.ids.length) url += ',';
				});
				delete params.ids;
			} else {
				var eMsg = 'giphy.js: No IDs.';
				if(errCb) errCb(eMsg);
				else console.error(eMsg);
			}

			self.request(url, params, succCb, errCb);
		};

		// Translate
		// --
		// https://github.com/Giphy/GiphyAPI#translate-endpoint
		self.translate = function(params, succCb, errCb) {
			var url = 'gifs/translate';

			// Check for required parameters
			if('s' in params) {
				url += '?s=' + params.s.replace(' ', '+');
				delete params.s;
			} else {
				var eMsg = 'giphy.js: No query.';
				if(errCb) errCb(eMsg);
				else console.error(eMsg);
			}

			self.request(url, params, succCb, errCb);
		};

		// Random
		// --
		// https://github.com/Giphy/GiphyAPI#random-endpoint
		self.random = function(params, succCb, errCb) {
			var url = 'gifs/random';

			// Check for required parameters
			if('tag' in params) {
				url += '?tag=' + params.tag;
				delete params.tag;
			}

			self.request(url, params, succCb, errCb);
		};

		// Trending GIFs
		// --
		// https://github.com/Giphy/GiphyAPI#trending-gifs-endpoint
		self.trending = function(params, succCb, errCb) {
			var url = 'gifs/trending';

			return self.request(url, params, succCb, errCb);
		};

		// STICKER API
		// --
		// https://github.com/Giphy/GiphyAPI#giphy-sticker-api

		// STICKER Search
		// --
		// https://github.com/Giphy/GiphyAPI#sticker-search-endpoint
		self.stickers.search = function(params, succCb, errCb) {
			var url = 'stickers/search';

			// Check for required parameters
			if('q' in params) {
				url += '?q=' + params.q;
				delete params.q;
			} else {
				var eMsg = 'giphy.js: No query.';
				if(errCb) errCb(eMsg);
				else console.error(eMsg);
			}

			self.request(url, params, succCb, errCb);
		};

		// STICKER Roulette (Random)
		// --
		// https://github.com/Giphy/GiphyAPI#sticker-roulette-random-endpoint
		self.stickers.roulette = function(params, succCb, errCb) {
			var url = 'stickers/roulette';

			// Check for required parameters
			if('tag' in params) {
				url += '?tag=' + params.tag;
				delete params.tag;
			} else {
				var eMsg = 'giphy.js: No query.';
				if(errCb) errCb(eMsg);
				else console.error(eMsg);
			}

			self.request(url, params, succCb, errCb);
		};

		// STICKER Trending
		// --
		// https://github.com/Giphy/GiphyAPI#sticker-trending-endpoint
		self.stickers.trending = function(params, succCb, errCb) {
			var url = 'stickers/trending';

			// Check for required parameters
			if('s' in params) {
				url += '?s=' + params.s;
				delete params.s;
			} else {
				var eMsg = 'giphy.js: No query.';
				if(errCb) errCb(eMsg);
				else console.error(eMsg);
			}

			self.request(url, params, succCb, errCb);
		};

		// STICKER Translate
		// --
		// https://github.com/Giphy/GiphyAPI#sticker-translate-endpoint
		self.stickers.translate = function(params, succCb, errCb) {
			var url = 'stickers/translate';

			// Check for required parameters
			if('s' in params) {
				url += '?s=' + params.s;
				delete params.s;
			} else {
				var eMsg = 'giphy.js: No query.';
				if(errCb) errCb(eMsg);
				else console.error(eMsg);
			}

			self.request(url, params, succCb, errCb);
		};

		// Request
		self.request = function(urlParams, params, succCb, errCb) {
			var self = this;
			var url = self.url;
			var hasStartingValue = false;

			url += urlParams;

			// Check for starting '?'
			if(url.indexOf('?') > -1) hasStartingValue = true;

			for(var key in params) {
				if(hasStartingValue) {
					url += '&' + key + '=' + params[key];
				} else {
					url += '?' + key + '=' + params[key];
					hasStartingValue = true;
				}
			}

			if(hasStartingValue) {
				url += '&api_key=' + self.key;
			} else {
				url += '?api_key=' + self.key;
			}

			var req = new XMLHttpRequest();
			req.open("GET", url, true);
			req.responseType = "json";
			req.onload = function () {
				var status = req.status;
				if (status == 200) {

					if(succCb) succCb(req.response);

				} else {
					if(errCb) errCb(status);
			 		else console.log(status);
				}
			};
			req.onerror = function() {
				var status = req.status;
				if ( errCb ) {
					errCb( status );
				}
			};
			req.send();

			return req;
		};

	};


	$$['Giphy'] = giphy; // jshint ignore:line
})(document, window);
