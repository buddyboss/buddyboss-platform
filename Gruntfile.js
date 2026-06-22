/* jshint node:true */
/* global module */
module.exports = function (grunt) {
	// Build a GitHub clone URL for the buddyboss org, choosing between SSH
	// (`git@github.com:...`) and HTTPS (`https://github.com/...`) at runtime.
	//
	// Order of precedence:
	//   1. `GIT_PROTOCOL` env var, if set to "ssh" or "https" (explicit override).
	//   2. Default: SSH — `buddyboss-platform-api` is a private repo and the
	//      typical contributor has an SSH key registered with GitHub. HTTPS to
	//      a private repo only works when the shell has credentials configured
	//      (GH_TOKEN env, credential helper, or a token in the URL), so it's
	//      not a safe default for a fresh clone.
	//
	// Use cases:
	//   Default (SSH):                    grunt bp_rest
	//   CI runner with token, no key:     GIT_PROTOCOL=https grunt bp_rest
	//                                     (caller is responsible for ensuring
	//                                      git credential auth is set up)
	function bbGithubCloneUrl( repo ) {
		var proto = ( process.env.GIT_PROTOCOL || '' ).toLowerCase();
		if ( 'https' === proto ) {
			return 'https://github.com/buddyboss/' + repo + '.git';
		}
		return 'git@github.com:buddyboss/' + repo + '.git';
	}

	var sass       = require( 'node-sass' ),
		SOURCE_DIR = 'src/',
		BUILD_DIR  = 'buddyboss-platform/',

		BP_CSS = [
			'**/*.css',
			'!**/*.min.css',
			'!**/vendor/**/*.css',
			'!**/endpoints/**/*.css'
		],

		// CSS exclusions, for excluding files from certain tasks, e.g. rtlcss.
		BP_EXCLUDED_CSS = [
			'!**/*-rtl.css',
			// '!bp-forums/**/*.css',
			'!**/endpoints/**/*.css'
		],

		BP_JS = [
			'**/*.js',
			'!**/*.min.js',
			// '!bp-forums/**/*.js',
			'!**/vendor/**/*.js',
			'!**/endpoints/**/*.js'
		],

		BP_EXCLUDED_MISC = ['!js/**'],

		// SASS generated "Twenty*"" CSS files.
		BP_SCSS_CSS_FILES = [
			// '!bp-templates/bp-legacy/css/twenty*.css',
			'!bp-templates/bp-nouveau/css/buddypress.css',
			'!bp-core/admin/css/hello.css',
			'!bp-core/css/medium-editor-beagle.css',
			'!bp-core/css/medium-editor.css',
			'!**/endpoints/**/*.css'
		];

	require( 'matchdep' ).filterDev( ['grunt-*', '!grunt-legacy-util'] ).forEach( grunt.loadNpmTasks );
	grunt.util = require( 'grunt-legacy-util' );

	grunt.initConfig(
		{
			pkg: grunt.file.readJSON( 'package.json' ),
			checkDependencies: {
				options: {
					packageManager: 'npm'
				},
				src: {}
			},
			jshint: {
				options: grunt.file.readJSON( '.jshintrc' ),
				grunt: {
					src: ['Gruntfile.js']
				},
				core: {
					expand: true,
					cwd: SOURCE_DIR,
					src: BP_JS.concat(
						[
						'!**/vendor/*.js',
						'!**/lib/*.js',
						'!**/*.min.js',
						'!**/emojione-edited.js',
						'!**/emojionearea-edited.js',
						'!**/node_modules/**/*.js',
						'!**/jquery.atwho.js',
						]
					),

				/**
				 * Limit JSHint's run to a single specified file:
				 *
				 * grunt jshint:core --file=filename.js
				 *
				 * Optionally, include the file path:
				 *
				 * grunt jshint:core --file=path/to/filename.js
				 *
				 * @param {String} filepath
				 * @returns {Bool}
				 */
				filter: function (filepath) {
					var index, file = grunt.option( 'file' );

					// Don't filter when no target file is specified
					if ( ! file) {
						return true;
					}

					// Normalise filepath for Windows
					filepath = filepath.replace( /\\/g, '/' );
					index    = filepath.lastIndexOf( '/' + file );

					// Match only the filename passed from cli
					if (filepath === file || (-1 !== index && index === filepath.length - (file.length + 1))) {
						return true;
					}

					return false;
				}
				}
			},
			sass: {
				options: {
					outputStyle: 'expanded',
					implementation: sass,
					//indentType: 'tab',
					//indentWidth: '1'
				},
				nouveau: {
					cwd: SOURCE_DIR,
					extDot: 'last',
					expand: true,
					ext: '.css',
					flatten: true,
					src: ['bp-templates/bp-nouveau/sass/buddypress.scss'],
					dest: SOURCE_DIR + 'bp-templates/bp-nouveau/css/'
				},
				ready_launch: {
					cwd: SOURCE_DIR,
					extDot: 'last',
					expand: true,
					ext: '.css',
					flatten: true,
					src: ['bp-templates/bp-nouveau/readylaunch//css/sass/*.scss'],
					dest: SOURCE_DIR + 'bp-templates/bp-nouveau/readylaunch/css'
				},
				admin: {
					cwd: SOURCE_DIR,
					extDot: 'last',
					expand: true,
					ext: '.css',
					flatten: true,
					src: [
						'bp-core/admin/sass/*.scss',
						'!bp-core/admin/sass/tooltips.scss'
					],
					dest: SOURCE_DIR + 'bp-core/admin/css/'
				},
				pusher: {
					cwd: SOURCE_DIR,
					expand: true,
					extDot: 'last',
					ext: '.css',
					flatten: true,
					src: ['bp-integrations/pusher/assets/css/scss/*.scss'],
					dest: SOURCE_DIR + 'bp-integrations/pusher/assets/css/',
				},
				recaptcha: {
					cwd: SOURCE_DIR,
					expand: true,
					extDot: 'last',
					ext: '.css',
					flatten: true,
					src: ['bp-integrations/recaptcha/assets/css/scss/*.scss'],
					dest: SOURCE_DIR + 'bp-integrations/recaptcha/assets/css/',
				},
			},
			rtlcss: {
				options: {
					opts: {
						processUrls: false,
						autoRename: false,
						clean: true
					},
					saveUnmodified: false
				},
				core: {
					expand: true,
					cwd: SOURCE_DIR,
					dest: SOURCE_DIR,
					extDot: 'last',
					ext: '-rtl.css',
					src: BP_CSS.concat( BP_EXCLUDED_CSS, BP_EXCLUDED_MISC )
				}
			},
			checktextdomain: {
				options: {
					correct_domain: false,
					text_domain: ['buddyboss'],
					keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'_n:1,2,4d',
					'_ex:1,2c,3d',
					'_nx:1,2,4c,5d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
					]
				},
				files: {
					cwd: SOURCE_DIR,
					src: ['**/*.php', '!vendor/**', '!src/vendor/**'].concat( BP_EXCLUDED_MISC ),
					expand: true
				}
			},
			makepot_grunt: {
				src: {
					options: {
						cwd: SOURCE_DIR,
						domainPath: '/languages',
						exclude: ['node_modules/*'], // List of files or directories to ignore.
						mainFile: 'bp-loader.php',
						potFilename: 'buddyboss.pot',
						potHeaders: { // Headers to add to the generated POT file.
							poedit: true, // Includes common Poedit headers.
							'Last-Translator': 'BuddyBoss <support@buddyboss.com>',
							'Language-Team': 'BuddyBoss <support@buddyboss.com>',
							'report-msgid-bugs-to': 'https://www.buddyboss.com/contact/',
							'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
						},
						type: 'wp-plugin',
						updateTimestamp: true, // Whether the POT-Creation-Date should be updated without other changes.
						updatePoFiles: false // Whether to update PO files in the same directory as the POT file.
					}
				}
			},
			imagemin: {
				core: {
					expand: true,
					cwd: SOURCE_DIR,
					src: ['**/*.{gif,jpg,jpeg,png}'].concat( BP_EXCLUDED_MISC ),
					dest: SOURCE_DIR
				}
			},
			clean: {
				all: [BUILD_DIR],
				bp_rest: [SOURCE_DIR + 'buddyboss-platform-api/'],
				bb_icons: [SOURCE_DIR + 'bp-templates/bp-nouveau/icons/bb-icons/'],
				composer: [ BUILD_DIR + 'composer.json', BUILD_DIR + 'composer.lock', BUILD_DIR + 'scoper.inc.php', BUILD_DIR + 'apidoc.json' ],
			},
			copy: {
				files: {
					files: [
					{
						cwd: SOURCE_DIR,
						dest: BUILD_DIR,
						dot: true,
						expand: true,
						src: ['**', '!**/.{svn,git}/**', '!**/readylaunch/css/sass/**'].concat( BP_EXCLUDED_MISC )
					},
					{
						dest: BUILD_DIR,
						dot: true,
						expand: true,
						src: ['composer.json', '!CLAUDE.md']
					}
					]
				},
				bp_rest_components: {
					cwd: SOURCE_DIR + 'buddyboss-platform-api/includes/',
					dest: SOURCE_DIR,
					dot: true,
					expand: true,
					src: [
					'**/bp-activity/**',
					'**/bp-blogs/**',
					'**/bp-forums/**',
					'**/bp-friends/**',
					'**/bp-groups/**',
					'**/bp-invites/**',
					'**/bp-media/**',
					'**/bp-document/**',
					'**/bp-video/**',
					'**/bp-members/**',
					'**/bp-messages/**',
					'**/bp-moderation/**',
					'**/bp-notifications/**',
					'**/bp-settings/**',
					'**/bp-xprofile/**',
					'**/bp-integrations/**',
					// Do NOT re-import any LearnDash REST files. The LD
					// integration was extracted from Platform in PROD-9792
					// and now lives in the buddyboss-learndash addon plugin.
					// The platform-api repo still ships its own copy of the
					// LD REST controller at
					// buddyboss-platform-api/includes/bp-integrations/learndash/
					// — without this exclusion `grunt bp_rest` would sweep
					// that file back into src/bp-integrations/learndash/
					// and silently recreate the directory we deleted.
					'!**/bp-integrations/learndash/**'
					],
					options: {
						process : function( content ) {
							return content.replace( /\, 'buddypress'/g, ', \'buddyboss\'' ); // update text-domain.
						}
					}
				},
				bp_rest_core: {
					cwd: SOURCE_DIR + 'buddyboss-platform-api/includes/',
					dest: SOURCE_DIR + 'bp-core/classes/',
					dot: true,
					expand: true,
					flatten: true,
					filter: 'isFile',
					src: [
					'**',
					'!actions.php',
					'!filters.php',
					'!functions.php',
					'!**/bp-activity/**',
					'!**/bp-blogs/**',
					'!**/bp-forums/**',
					'!**/bp-friends/**',
					'!**/bp-groups/**',
					'!**/bp-invites/**',
					'!**/bp-media/**',
					'!**/bp-document/**',
					'!**/bp-video/**',
					'!**/bp-members/**',
					'!**/bp-messages/**',
					'!**/bp-moderation/**',
					'!**/bp-notifications/**',
					'!**/bp-settings/**',
					'!**/bp-xprofile/**',
					'!**/bp-integrations/**',
					// Reactions REST endpoint lives under the new feature
					// directory layout (`src/bb-features/community/reactions/classes/`).
					// Excluded here so the flatten-to-bp-core sweep doesn't
					// recreate a stale duplicate at `bp-core/classes/`. See
					// the dedicated `bp_rest_reactions` task below.
					'!**/class-bb-rest-reactions-endpoint.php'
					],
					options: {
						process : function( content ) {
							return content.replace( /\, 'buddypress'/g, ', \'buddyboss\'' ); // update text-domain.
						}
					}
				},
				bp_rest_reactions: {
					// The reactions feature is the first one fully migrated to the
					// feature-based architecture (`src/bb-features/community/reactions/`).
					// Its REST controller belongs alongside the rest of the feature's
					// classes, not in the legacy `bp-core/classes/` flat namespace.
					// Source path mirrors where buddyboss-platform-api stores it
					// today: `bp-components/classes/`. If the API repo moves the file,
					// update `cwd`/`src` here and the matching exclusion in `bp_rest_core`.
					cwd: SOURCE_DIR + 'buddyboss-platform-api/includes/bp-components/classes/',
					dest: SOURCE_DIR + 'bb-features/community/reactions/classes/',
					expand: true,
					flatten: true,
					filter: 'isFile',
					src: ['class-bb-rest-reactions-endpoint.php'],
					options: {
						process : function( content ) {
							return content.replace( /\, 'buddypress'/g, ', \'buddyboss\'' ); // update text-domain.
						}
					}
				},
				bp_rest_performance: {
					cwd: SOURCE_DIR + 'buddyboss-platform-api/Performance/',
					dest: SOURCE_DIR + 'bp-performance/classes/',
					expand: true,
					src: '**',
					options: {
						process : function( content ) {
							return content.replace( /\, 'buddypress'/g, ', \'buddyboss\'' ); // update text-domain.
						}
					}
				},
				bp_rest_mu: {
					cwd: SOURCE_DIR + 'buddyboss-platform-api/MuPlugin/',
					dest: SOURCE_DIR + 'bp-performance/mu-plugins/',
					expand: true,
					src: '**',
					options: {
						process : function( content ) {
							return content.replace( /\, 'buddypress'/g, ', \'buddyboss\'' ); // update text-domain.
						}
					}
				},
				bb_icons: {
					cwd: SOURCE_DIR + 'bp-templates/bp-nouveau/icons/bb-icons/output/',
					dest: SOURCE_DIR + 'bp-templates/bp-nouveau/icons/',
					dot: true,
					expand: true,
					src: [
						'css/**',
						'fonts/**',
						'!example.html',
						'font-map.json',
						'!svg/**',
					],
				},
			},
			uglify: {
				core: {
					cwd: SOURCE_DIR,
					dest: SOURCE_DIR,
					extDot: 'last',
					expand: true,
					ext: '.min.js',
					src: BP_JS.concat(
						[
							'!**/vendor/*.js',
							'!**/lib/*.js',
							'!**/*.min.js',
							'!**/node_modules/**/*.js',
							'!**/endpoints/**/*.js',
							'!**/js/lib/Chart.js',
							'!**/js/blocks/**/*.js',
							'!**/bp-core/admin/bb-settings/**/*.js',
							'!**/bp-core/blocks/**/*.js',
							'!**/js/admin/**/*.js',
						]
					)
				}
			},
			stylelint: {
				css: {
					options: {
						configFile: '.stylelintrc',
						format: 'css'
					},
					expand: true,
					cwd: SOURCE_DIR,
					src: BP_CSS.concat(
						BP_EXCLUDED_CSS,
						BP_EXCLUDED_MISC,
						BP_SCSS_CSS_FILES,
						[
							'!**/*.min.css',
							'!**/admin/**/*.css',
							'!**/emojionearea-edited.css',
							'!**/pusher/**/*.css',
							'!**/recaptcha/**/*.css',
							'!**/endpoints/**/*.css',
							'!**/readylaunch/**/*.css',
							'!**/js/blocks/**/*.css',
							'!**/js/admin/**/*.css',
							'!**/bp-core/admin/bb-settings/**/*.css',
							'!**/bp-core/blocks/**/*.css'
						]
					)
				},
				scss: {
					options: {
						configFile: '.stylelintrc',
						format: 'scss'
					},
					expand: true,
					cwd: SOURCE_DIR,
					src: [
						'**/*.scss',
						'!**/vendors/**/*.scss',
						'!bp-templates/bp-nouveau/common-styles/_codemirror.scss'
					]
				}
			},
			cssmin: {
				minify: {
					cwd: SOURCE_DIR,
					dest: SOURCE_DIR,
					extDot: 'last',
					expand: true,
					ext: '.min.css',
					src: BP_CSS
				},
				rtl: {
					cwd: SOURCE_DIR,
					dest: SOURCE_DIR,
					extDot: 'last',
					expand: true,
					ext: '.min.css',
					src: [
						'**/*-rtl.css',
						'!**/*.min.css',
						'!**/vendor/**/*.css',
						'!**/endpoints/**/*.css'
					]
				}
			},
			phpunit: {
				'default': {
					cmd: 'phpunit',
					args: ['-c', 'phpunit.xml.dist']
				},
				'multisite': {
					cmd: 'phpunit',
					args: ['-c', 'tests/phpunit/multisite.xml']
				},
				'codecoverage': {
					cmd: 'phpunit',
					args: ['-c', 'tests/phpunit/codecoverage.xml']
				}
			},
			exec: {
				options: {
					maxBuffer: 1024 * 1024 * 10, // 10MB buffer (global default)
					timeout: 600000 // 10 minutes (global default for all exec tasks)
				},
				build_blocks: {
					command: 'npm run build:block:core',
					cwd: '.',
					stdout: true
				},
				build_admin: {
					command: 'npm run build:admin',
					cwd: '.',
					stdout: true
				},
				cli: {
					command: 'git add . && git commit -am "grunt release build"',
					cwd: '.',
					stdout: false
				},
				init_build_dir_git: {
					command: 'mkdir -p buddyboss-platform && cd buddyboss-platform && git init && git remote add origin $(git -C .. remote get-url origin) && git fetch origin production && git checkout -B production origin/production && cd ..',
					cwd: '.',
					stdout: true
				},
				// Local-only counterpart to `init_build_dir_git`. Used by
				// the `build_test` task — creates the buddyboss-platform/
				// directory the rest of the build flow expects to exist,
				// WITHOUT any git initialisation, remote fetch, or branch
				// checkout. The empty build dir + copy:files + compress
				// steps that follow are purely filesystem operations, so
				// they work fine on a plain directory. No `.git` means
				// `empty_build_dir`'s find pattern has nothing to skip —
				// files just get removed cleanly.
				init_build_dir_clean: {
					command: 'mkdir -p buddyboss-platform',
					cwd: '.',
					stdout: true
				},
				empty_build_dir: {
					command: 'cd buddyboss-platform && find . -not -path "./.git*" -not -name "." -not -name ".." -delete && cd ..',
					cwd: '.',
					stdout: true
				},
				commit_build_to_mothership_release: {
					command: 'cd buddyboss-platform && git add . && git commit -m "Production build - $(date)" && git push origin production && cd ..',
					cwd: '.',
					stdout: true
				},
				// Generate the unminified-asset manifest consumed at runtime
				// by BB_Debug_Asset_Fetcher. Runs AFTER the production push
				// so it can stamp the manifest with the pushed commit SHA;
				// the manifest is written into BUILD_DIR only, so it ships
				// inside the customer zip but stays out of the production
				// branch commit (production carries the unminified files,
				// the zip's manifest pins back to that exact commit).
				//
				// Safe to run when BUILD_DIR is not a git repo (the
				// build_test flow) — the script falls back to a sentinel
				// SHA and the runtime fetcher refuses to act on it, so
				// test zips gracefully degrade to .min loading.
				generate_debug_manifest: {
					command: 'node bin/generate-debug-manifest.js ' + BUILD_DIR + ' <%= pkg.BBVersion %>',
					cwd: '.',
					stdout: true
				},

				rest_api: {
					command: 'git clone ' + bbGithubCloneUrl( 'buddyboss-platform-api' ),
					cwd: SOURCE_DIR,
					stdout: false
				},
				rest_performance: {
					command: 'git clone ' + bbGithubCloneUrl( 'buddyboss-platform-api' ),
					cwd: SOURCE_DIR,
					stdout: false
				},
				fetch_bb_icons: {
					command: 'git clone ' + bbGithubCloneUrl( 'bb-icons' ),
					cwd: SOURCE_DIR + 'bp-templates/bp-nouveau/icons/',
					stdout: false,
				},
				composer: {
					command: 'composer update; composer scoper;',
					cwd: SOURCE_DIR,
					stdout: false
				},
				// WP-CLI makepot with header fixing
				makepot_wp: {
					command: 'wp i18n make-pot src/ src/languages/buddyboss.pot --domain=buddyboss --ignore-domain --exclude="node_modules/*, vendor/*, src/vendor/*, js/*"',
					cwd: '.',
					stdout: true
				},
				// Fix POT file headers to match grunt-wp-i18n format
				fix_wp_cli_headers: {
					command: 'node bin/fix-wp-cli-headers.js',
					cwd: '.',
					stdout: true
				}
			},
			jsvalidate: {
				options: {
					globals: {},
					esprimaOptions: {},
					verbose: false
				},
				src: {
					files: {
						src: [
							SOURCE_DIR + '/**/*.js',
							'!**/*.min.js',
							'!**/emojione-edited.js',
							'!**/emojionearea-edited.js',
							'!**/vendor/**/*.js',
							'!**/node_modules/**/*.js',
							'!**/endpoints/**/*.js',
							'!**/js/lib/Chart.js',
							'!' + SOURCE_DIR + 'js/**/*.js',
							'!' + SOURCE_DIR + 'bp-core/admin/bb-settings/**/*.js',
							'!' + SOURCE_DIR + 'bp-core/blocks/**/*.js'
						].concat( BP_EXCLUDED_MISC )
					}
				}
			},
			compress: {
				main: {
					options: {
						archive: 'buddyboss-platform-plugin.zip'
					},
					files: [{
						src: BUILD_DIR + '**',
						dest: '.'
					}]
				}
			},
			apidoc: {
				api: {
					src: SOURCE_DIR,
					dest: SOURCE_DIR + 'endpoints/',
					options : {
						includeFilters: ['.*\\.php$'],
						excludeFilters : ['assets/', 'bin/','languages/', 'node_modules/', 'vendor/', 'src/vendor/', 'src/bp-core/admin/js/lib/'],
					},
				}
			},
			json2php: {
				convert: {
					expand: true,
					cwd: SOURCE_DIR + 'bp-templates/bp-nouveau/icons/',
					dest: SOURCE_DIR + 'bp-templates/bp-nouveau/icons/',
					ext: '.php',
					src: [
						'font-map.json'
					]
				}
			},
			'string-replace': {
				dist: {
					files: [{
						src: '**/*.php',
						expand: true,
					}],
					options: {
						replacements: [{
							pattern: /\[BBVERSION]/g,
							replacement: '<%= pkg.BBVersion %>'
						}]
					}
				},
				'icon-translate': {
					files: [{
						src: SOURCE_DIR + 'bp-templates/bp-nouveau/icons/font-map.php',
						expand: true,
					}],
					options: {
						replacements: [
							{
								pattern: /return/g,
								replacement: '$bb_icons_data ='
							}
						]
					}
				}
			}
		}
	);

	/**
	 * Register tasks.
	 */
	// Fetch bb icons.
	grunt.registerTask(
		'fetch_bb_icons',
		[
			'clean:bb_icons',
			'exec:fetch_bb_icons',
			'copy:bb_icons',
			'clean:bb_icons',
			'json2php',
			'string-replace:icon-translate',
			'rtlcss',
			'cssmin'
		]
	);

	// WP-CLI makepot task with grunt-wp-i18n compatible headers
	grunt.registerTask('makepot', ['exec:makepot_wp', 'exec:fix_wp_cli_headers']);

	grunt.registerTask('pre-commit', ['checkDependencies', 'jsvalidate', 'jshint', 'stylelint']);
	grunt.registerTask('webpack', ['exec:build_blocks', 'exec:build_admin']);
	grunt.registerTask('src', ['checkDependencies', 'jsvalidate', 'jshint', 'stylelint', 'webpack', 'sass', 'rtlcss', 'checktextdomain', /*'imagemin',*/ 'uglify', 'cssmin:minify', 'cssmin:rtl', 'makepot']);
	grunt.registerTask('bp_rest', ['clean:bp_rest', 'exec:rest_api', 'copy:bp_rest_components', 'copy:bp_rest_core', 'copy:bp_rest_reactions', 'clean:bp_rest', 'apidoc' ]);
	grunt.registerTask('bp_performance', ['clean:bp_rest', 'exec:rest_performance', 'copy:bp_rest_performance', 'copy:bp_rest_mu', 'clean:bp_rest']);

	// Static list of font-format dead weight to strip from the customer zip.
	// Files remain in src/ and on the production branch — only the shipped
	// archive drops them.  Two classes of file:
	//
	//   1. EOT format — only needed for IE 6-8 and shipped only as a legacy
	//      fallback inside @font-face cascades. Modern WordPress minimums
	//      put this years past relevance.
	//   2. TTF copies of icon fonts that ALSO ship woff2 + woff. The browser
	//      cascade prefers woff2 first; ttf was a 2014-era fallback for
	//      pre-woff2 browsers. Coverage is now > 97 % globally.
	//
	// Excludes specific files only — never `**/*.ttf`, because
	// SFUIText-Medium.ttf is consumed server-side by PHP/GD for PNG avatar
	// rendering and must remain in the zip even though no browser loads it.
	// Future cleanup of that font requires a permissive-licensed replacement,
	// not a strip.
	//
	// @since BuddyBoss [BBVERSION]
	var FONT_STRIP_GLOBS = [
		// 1. Every EOT in the build, anywhere. EOT was an IE 6-8 fallback
		//    format. Modern WordPress minimums put this past relevance.
		'**/*.eot',

		// 2. Every WOFF in the build, anywhere. WOFF2 has been universally
		//    supported across Chrome/Firefox/Safari/Edge since 2015-2016;
		//    coverage is > 98 % of WP traffic globally. The few clients
		//    that still need WOFF (iOS Safari ≤ 11, IE) get the system
		//    font cascade as a graceful degradation. None of the platform
		//    WOFFs live under vendor/ or any third-party tree — safe to
		//    blanket-strip.
		'**/*.woff',

		// 3. Icon-font TTFs whose woff2 + woff siblings already cover the
		//    browser cascade. Listed explicitly so unrelated TTFs (server-
		//    side fonts under bp-core/fonts/) stay shipped.
		'bp-templates/bp-nouveau/icons/fonts/box-filled.ttf',
		'bp-templates/bp-nouveau/icons/fonts/box-lined.ttf',
		'bp-templates/bp-nouveau/icons/fonts/filled.ttf',
		'bp-templates/bp-nouveau/icons/fonts/lined.ttf',
		'bp-templates/bp-nouveau/icons/fonts/round-filled.ttf',
		'bp-templates/bp-nouveau/icons/fonts/round-lined.ttf',
		'bp-templates/bp-nouveau/readylaunch/icons/fonts/bb-icons.ttf',
		'bp-templates/bp-nouveau/readylaunch/icons/fonts/bb-icons-Bold.ttf',
		'bp-templates/bp-nouveau/readylaunch/icons/fonts/bb-icons-Duotone.ttf',
		'bp-templates/bp-nouveau/readylaunch/icons/fonts/bb-icons-Fill.ttf',
		'bp-templates/bp-nouveau/readylaunch/icons/fonts/bb-icons-Light.ttf',
		'bp-templates/bp-nouveau/readylaunch/icons/fonts/bb-icons-Thin.ttf',

		// 3. Duplicate Glyphicons. endpoints/fonts/ is the actually-used set
		//    (bootstrap.min.css references via ../fonts/). endpoints/assets/
		//    is a byte-identical copy that nothing references.
		'endpoints/assets/glyphicons-halflings-regular.eot',
		'endpoints/assets/glyphicons-halflings-regular.svg',
		'endpoints/assets/glyphicons-halflings-regular.ttf',
		'endpoints/assets/glyphicons-halflings-regular.woff',
		'endpoints/assets/glyphicons-halflings-regular.woff2',

		// 4. Inter UI font TTFs. Each Inter weight ships ttf + woff + woff2
		//    after the WOFF2 conversion; the woff2 file is ~20% of the TTF
		//    size with identical rendering, so the TTF copy is no longer
		//    needed in the customer zip. WOFF stays as the cascade fallback
		//    for browsers without WOFF2 support (< 1 % of WP traffic).
		'bp-templates/bp-nouveau/readylaunch/assets/fonts/Inter-Bold.ttf',
		'bp-templates/bp-nouveau/readylaunch/assets/fonts/Inter-Italic.ttf',
		'bp-templates/bp-nouveau/readylaunch/assets/fonts/Inter-Light.ttf',
		'bp-templates/bp-nouveau/readylaunch/assets/fonts/Inter-LightItalic.ttf',
		'bp-templates/bp-nouveau/readylaunch/assets/fonts/Inter-Medium.ttf',
		'bp-templates/bp-nouveau/readylaunch/assets/fonts/Inter-MediumItalic.ttf',
		'bp-templates/bp-nouveau/readylaunch/assets/fonts/Inter-Regular.ttf',
		'bp-templates/bp-nouveau/readylaunch/assets/fonts/Inter-SemiBold.ttf'
	];

	// Compiled translation bundles and source `.po` files. WordPress fetches
	// these on demand from translate.wordpress.org for plugins hosted on the
	// .org repo, so shipping per-locale `.po`/`.mo` inside the zip is dead
	// weight for the typical online install. The `.pot` template stays so
	// translators (and third-party tooling) can derive new locales locally.
	//
	// Files remain in src/ and on the production branch — only the customer
	// zip drops them. A site running entirely offline (no outbound HTTPS to
	// translate.wordpress.org) will fall back to English on first load until
	// an admin installs locales manually or wp-cron re-fetches.
	//
	// @since BuddyBoss [BBVERSION]
	var TRANSLATION_STRIP_GLOBS = [
		'languages/*.po',
		'languages/*.mo'
	];

	// Dev-only build artefacts that never serve a runtime purpose for the
	// shipped plugin: SCSS sources (consumed by `grunt sass` at build time
	// to emit the compiled CSS that DOES ship) and CSS source maps (consumed
	// only by browser DevTools when a developer happens to be inspecting
	// the running plugin's compiled styles, which never happens on a
	// customer install).
	//
	// Source SCSS lives in src/ and ships to the production branch so devs
	// can edit and recompile from a checkout. The .map files reference
	// source paths that the customer zip never had anyway, so dropping the
	// .map causes zero functional change.
	//
	// No vendor SCSS / .map in the platform build dir — vendor PHP under
	// vendor/ doesn't ship SCSS. Safe to blanket-glob both extensions.
	//
	// @since BuddyBoss [BBVERSION]
	var DEV_SOURCE_STRIP_GLOBS = [
		'**/*.scss',
		'**/*.map'
	];

	// Paid-only component directories. Video and Document are not part of the
	// free Platform — they are delivered only with a paid license via the
	// Mothership build (which ships them bundled). PROD-9826 made the Platform
	// load cleanly when these folders are absent (component-availability scrub +
	// per-component guards), so dropping them from a zip produces no fatals.
	//
	// IMPORTANT: this strip applies to `build_test` ONLY (the free-zip test
	// build), gated by `stripPaidComponents` below. The production `build` keeps
	// these folders so the paid Mothership build still bundles video/document.
	//
	// Root-anchored on purpose (e.g. `bp-video/**`, not `**/bp-video/**`) so the
	// component directories are excluded without catching unrelated nested paths
	// such as bp-templates assets. Files remain in src/ and on the production
	// branch; only the build_test customer zip drops them.
	//
	// @since BuddyBoss [BBVERSION]
	var PAID_COMPONENT_STRIP_GLOBS = [
		'bp-video/**',
		'bp-document/**'
	];

	// Toggled true only by the `enable_paid_component_strip` task, which is wired
	// into the `build_test` chain. When false (the default, and the production
	// `build` path) PAID_COMPONENT_STRIP_GLOBS is ignored and video/document ship.
	//
	// @since BuddyBoss [BBVERSION]
	var stripPaidComponents = false;

	// Image assets are served from an external S3 bucket (see BB_S3_Image_Offload
	// in bp-core). Every image is uploaded to the bucket mirroring the plugin
	// source tree under a `src/` key prefix, so the shipped zip drops them all to
	// stay small. Two things have to happen before they can be stripped:
	//   1. The runtime PHP rewriter handles image URLs emitted into the HTML.
	//   2. `rewrite_css_image_refs_to_s3` (below) rewrites the relative
	//      `url(...)` image refs baked into compiled CSS — those are served as
	//      static files and the PHP rewriter can never reach them.
	// Both must be in place or CSS background images / inline images would 404.
	//
	// Keep this base URL in lockstep with BB_S3_Image_Offload::DEFAULT_BASE_URL +
	// DEFAULT_KEY_PREFIX so build-time and runtime rewrites resolve to the same
	// object keys.
	//
	// @since BuddyBoss [BBVERSION]
	var S3_IMAGE_BASE_URL = 'https://buddyboss-platform-assets.s3.us-east-1.amazonaws.com/src/';

	// Image file types to strip from the customer zip (offloaded to S3). Matches
	// BB_S3_Image_Offload::EXTENSIONS. Root-relative globs evaluated against
	// BUILD_DIR. Files remain in src/ and on the production branch; only the
	// customer zip drops them.
	//
	// @since BuddyBoss [BBVERSION]
	var IMAGE_STRIP_GLOBS = [
		'**/*.png',
		'**/*.jpg',
		'**/*.jpeg',
		'**/*.gif',
		'**/*.svg',
		'**/*.webp',
		'**/*.ico',
		'**/*.bmp'
	];

	// Enable the paid-component strip for the current task run. Inserted into
	// `build_test` before `configure_compress_exclusions` so only the test zip
	// drops bp-video / bp-document.
	//
	// @since BuddyBoss [BBVERSION]
	grunt.registerTask( 'enable_paid_component_strip', 'Flag bp-video / bp-document for exclusion from this build_test zip.', function () {
		stripPaidComponents = true;
		grunt.log.writeln( '[build_test] paid-component strip enabled — bp-video / bp-document will be excluded from the zip.' );
	} );

	// Rewrite CSS inside BUILD_DIR to drop `url(...)` refs that would 404
	// against fonts about to be excluded from the customer zip. Production
	// branch keeps the original CSS (this runs AFTER the production push);
	// only the staged BUILD_DIR copies are mutated, which then feed the
	// compress step.
	//
	// Skipped silently when no font files match the strip globs in BUILD_DIR
	// (e.g. someone runs `compress` standalone without prior copy:files).
	//
	// @since BuddyBoss [BBVERSION]
	grunt.registerTask( 'strip_orphan_font_refs', 'Rewrite BUILD_DIR CSS to drop refs to about-to-be-stripped fonts.', function () {
		var done = this.async();

		// Expand FONT_STRIP_GLOBS against BUILD_DIR into concrete file paths.
		// Each entry's path passed to the script is RELATIVE to BUILD_DIR so
		// the script can resolve CSS-side url() references in the same frame.
		var expanded = grunt.file.expand(
			{ cwd: BUILD_DIR, dot: false },
			FONT_STRIP_GLOBS
		);

		if ( expanded.length === 0 ) {
			grunt.log.writeln( '[strip_orphan_font_refs] no matching fonts in BUILD_DIR — skipping.' );
			return done();
		}

		grunt.util.spawn(
			{
				cmd:  'node',
				args: [ 'bin/strip-orphan-font-refs.js', BUILD_DIR, expanded.join( ',' ) ],
				opts: { stdio: 'inherit' }
			},
			function ( err, result, code ) {
				if ( err || code !== 0 ) {
					grunt.fail.warn( 'strip-orphan-font-refs failed (exit ' + code + ')' );
				}
				done();
			}
		);
	} );

	// Rewrite relative `url(...)` image refs in BUILD_DIR CSS to absolute S3
	// URLs, so the compiled CSS keeps working once the local image files are
	// excluded from the customer zip. Runs AFTER copy:files (CSS + images are
	// staged in BUILD_DIR, so the existence check resolves) and BEFORE
	// configure_compress_exclusions/compress (which drop the images). The
	// production branch keeps the original relative CSS — only the staged
	// BUILD_DIR copy is mutated.
	//
	// @since BuddyBoss [BBVERSION]
	grunt.registerTask( 'rewrite_css_image_refs_to_s3', 'Rewrite BUILD_DIR CSS image url() refs to the external S3 bucket.', function () {
		var done = this.async();

		grunt.util.spawn(
			{
				cmd:  'node',
				args: [ 'bin/rewrite-css-image-refs-to-s3.js', BUILD_DIR, S3_IMAGE_BASE_URL ],
				opts: { stdio: 'inherit' }
			},
			function ( err, result, code ) {
				if ( err || code !== 0 ) {
					grunt.fail.warn( 'rewrite-css-image-refs-to-s3 failed (exit ' + code + ')' );
				}
				done();
			}
		);
	} );

	// Read the just-written debug manifest and rebuild `compress.main.files` so
	// every paired unminified asset is excluded from the shipped zip. Customer
	// zips ship `.min.{js,css}` only; the unminified counterparts live on the
	// production branch and are fetched at runtime when WP_DEBUG && SCRIPT_DEBUG.
	//
	// Also folds in the static font-format dead weight (FONT_STRIP_GLOBS above)
	// so the EOT + duplicate-icon-TTF + glyphicons-duplicate trim happens in
	// the same compress pass. All stripped files remain in src/ and on the
	// production branch; only the customer zip drops them.
	//
	// Idempotent and safe to skip — when the manifest is absent (someone ran
	// `grunt compress` standalone) the existing static config is left alone
	// and the zip contains everything in BUILD_DIR.
	//
	// @since BuddyBoss [BBVERSION]
	grunt.registerTask( 'configure_compress_exclusions', 'Rebuild compress:main file list using debug-manifest pair set + static font strip list.', function () {
		var manifestPath = BUILD_DIR + 'unminified-manifest.json';
		var pairExclusions = [];

		if ( grunt.file.exists( manifestPath ) ) {
			var manifest = grunt.file.readJSON( manifestPath );
			var paths    = Object.keys( manifest.files || {} );
			pairExclusions = paths.map( function ( rel ) {
				return '!' + BUILD_DIR + rel;
			} );
		} else {
			grunt.log.warn( '[compress] ' + manifestPath + ' missing — paired unminified files will ship in the zip.' );
		}

		// Font + translation + dev-source strip globs are evaluated against
		// the BUILD_DIR root, same as the pair exclusions. Use forward
		// slashes regardless of host OS so minimatch works on Windows too.
		var fontExclusions = FONT_STRIP_GLOBS.map( function ( g ) {
			return '!' + BUILD_DIR + g;
		} );
		var translationExclusions = TRANSLATION_STRIP_GLOBS.map( function ( g ) {
			return '!' + BUILD_DIR + g;
		} );
		var devSourceExclusions = DEV_SOURCE_STRIP_GLOBS.map( function ( g ) {
			return '!' + BUILD_DIR + g;
		} );
		// Paid components are stripped only when the build_test flag is set.
		var paidComponentExclusions = stripPaidComponents
			? PAID_COMPONENT_STRIP_GLOBS.map( function ( g ) {
				return '!' + BUILD_DIR + g;
			} )
			: [];

		// Image assets are offloaded to S3 (CSS refs already rewritten by
		// rewrite_css_image_refs_to_s3, HTML refs rewritten at runtime).
		var imageExclusions = IMAGE_STRIP_GLOBS.map( function ( g ) {
			return '!' + BUILD_DIR + g;
		} );

		var allExclusions = pairExclusions
			.concat( fontExclusions )
			.concat( translationExclusions )
			.concat( devSourceExclusions )
			.concat( paidComponentExclusions )
			.concat( imageExclusions );

		grunt.config.set( 'compress.main.files', [ {
			src:  [ BUILD_DIR + '**' ].concat( allExclusions ),
			dest: '.'
		} ] );

		grunt.log.writeln(
			'[compress] excluded ' + pairExclusions.length + ' paired-unminified files + '
			+ FONT_STRIP_GLOBS.length + ' font-strip globs + '
			+ TRANSLATION_STRIP_GLOBS.length + ' translation globs + '
			+ DEV_SOURCE_STRIP_GLOBS.length + ' dev-source globs + '
			+ paidComponentExclusions.length + ' paid-component globs + '
			+ imageExclusions.length + ' image globs (offloaded to S3) from zip.'
		);
	} );

	// Build task: Creates production build in BUILD_DIR, initializes git, performs build operations, then commits to production.
	// `exec:generate_debug_manifest` runs AFTER the production push but BEFORE compress, so the manifest ships in the
	// customer zip while production keeps the unminified files but not the per-version manifest pointer.
	// `configure_compress_exclusions` rewrites compress.main.files from the manifest so the zip stays in lockstep.
	grunt.registerTask('build', ['string-replace:dist', 'exec:composer', 'clean:all', 'exec:init_build_dir_git', 'exec:empty_build_dir', 'copy:files', 'clean:composer', 'exec:commit_build_to_mothership_release', 'exec:generate_debug_manifest', 'strip_orphan_font_refs', 'rewrite_css_image_refs_to_s3', 'configure_compress_exclusions', 'compress', 'clean:all']);

	// Build-test task: identical to `build` except it never touches the
	// production branch — no git init, no fetch, no checkout, no commit,
	// no push. Produces the same `buddyboss-platform-plugin.zip` as
	// `build` so QA can ship the artefact to staging without altering
	// the canonical release branch. Use this for ad-hoc test builds,
	// CI dry-runs, or local "what would the next release look like"
	// inspection. Steps:
	//   1. string-replace:dist             — substitute [BBVERSION] etc.
	//   2. exec:composer                   — install prod composer deps
	//   3. clean:all                       — wipe any prior build artefact
	//   4. exec:init_build_dir_clean       — `mkdir -p buddyboss-platform/` (NO git)
	//   5. exec:empty_build_dir            — clear any leftover files (no .git to skip — fine)
	//   6. copy:files                      — stage built sources
	//   7. clean:composer                  — drop dev composer state from the staged dir
	//   8. compress                        — zip → buddyboss-platform-plugin.zip
	//   9. clean:all                       — final tidy
	grunt.registerTask('build_test', ['string-replace:dist', 'exec:composer', 'clean:all', 'exec:init_build_dir_clean', 'exec:empty_build_dir', 'copy:files', 'clean:composer', 'exec:generate_debug_manifest', 'strip_orphan_font_refs', 'rewrite_css_image_refs_to_s3', 'enable_paid_component_strip', 'configure_compress_exclusions', 'compress', 'clean:all']);

	grunt.registerTask('release', ['src', 'build']);

	// Testing tasks.
	grunt.registerMultiTask(
		'phpunit',
		'Runs PHPUnit tests, including the ajax and multisite tests.',
		function () {
			grunt.util.spawn(
				{
					args: this.data.args,
					cmd: this.data.cmd,
					opts: {stdio: 'inherit'}
				},
				this.async()
			);
		}
	);

	grunt.registerTask( 'test', 'Run all unit test tasks.', ['phpunit:default', 'phpunit:multisite'] );

	grunt.registerTask( 'jstest', 'Runs all JavaScript tasks.', ['jsvalidate', 'jshint'] );

	// Travis CI Tasks.
	grunt.registerTask( 'travis:grunt', 'Runs Grunt build task.', ['src'] );
	grunt.registerTask( 'travis:phpunit', ['jsvalidate', 'jshint', 'checktextdomain', 'test'] );
	grunt.registerTask( 'travis:codecoverage', 'Runs PHPUnit tasks with code-coverage generation.', ['phpunit:codecoverage'] );

	// Patch task.
	grunt.renameTask( 'patch_wordpress', 'patch' );

	// Default task.
	grunt.registerTask( 'default', ['src'] );
};
