/* jshint node:true */
/* global module */

const fs = require('fs');
const potFile = 'src/languages/buddyboss.pot';
module.exports = function (grunt) {
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
			'!**/endpoints/**/*.js',
		],

		BP_EXCLUDED_MISC = [],

		// SASS generated "Twenty*"" CSS files.
		BP_SCSS_CSS_FILES = [
			// '!bp-templates/bp-legacy/css/twenty*.css',
			'!bp-templates/bp-nouveau/css/buddypress.css',
			'!bp-core/admin/css/hello.css',
			'!bp-core/css/medium-editor-beagle.css',
			'!bp-core/css/medium-editor.css',
			'!**/endpoints/**/*.css'
		];

	const languages = [
		{ code: 'hi_IN', locale: 'hi' },
		{ code: 'bg_BG', locale: 'bg' },
		{ code: 'bn_BD', locale: 'bn' },
		{ code: 'hsb', locale: 'hsb' },
		{ code: 'zh_CN', locale: 'zh' },
		// Add more languages as needed
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
				admin: {
					cwd: SOURCE_DIR,
					extDot: 'last',
					expand: true,
					ext: '.css',
					flatten: true,
					src: ['bp-core/admin/sass/*.scss'],
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
					src: ['**/*.php'].concat( BP_EXCLUDED_MISC ),
					expand: true
				}
			},
			makepot: {
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
			},
			copy: {
				files: {
					files: [
					{
						cwd: SOURCE_DIR,
						dest: BUILD_DIR,
						dot: true,
						expand: true,
						src: ['**', '!**/.{svn,git}/**'].concat( BP_EXCLUDED_MISC )
					},
					{
						dest: BUILD_DIR,
						dot: true,
						expand: true,
						src: ['composer.json']
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
					'**/bp-integrations/**'
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
					'!**/bp-integrations/**'
					],
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
						'!**/emojione-edited.js',
						'!**/emojionearea-edited.js',
						'!**/node_modules/**/*.js',
						'!**/endpoints/**/*.js',
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
						'!**/endpoints/**/*.css'
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
				cli: {
					command: 'git add . && git commit -am "grunt release build"',
					cwd: '.',
					stdout: false
				},
				rest_api: {
					command: 'git clone https://github.com/buddyboss/buddyboss-platform-api.git',
					cwd: SOURCE_DIR,
					stdout: false
				},
				rest_performance: {
					command: 'git clone https://github.com/buddyboss/buddyboss-platform-api.git',
					cwd: SOURCE_DIR,
					stdout: false
				},
				fetch_bb_icons: {
					command: 'git clone https://github.com/buddyboss/bb-icons.git',
					cwd: SOURCE_DIR + 'bp-templates/bp-nouveau/icons/',
					stdout: false,
				},
				composer: {
					command: 'composer update',
					cwd: SOURCE_DIR,
					stdout: false
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
						'!**/emojione-edited.js',
						'!**/emojionearea-edited.js',
						'!**/vendor/**/*.js',
						'!**/node_modules/**/*.js',
						'!**/endpoints/**/*.js'
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
			},
			po2mo: {
				options: {
					domainPath: 'src/languages',
					languages: languages,
				},
				files: {
					src: [ 'src/languages/*.po'],
					expand: true,
				},
			},
			shell: {
				extract: {
					command: 'xgettext -o ' + potFile + ' --keyword=_ --keyword=__ --keyword=n__ --keyword=pgettext:1c,2 --keyword=npgettext:1c,2,3 --from-code=UTF-8 bp-loader.php',
				},
				generatePoMo: {
					command: function (lang) {
						return 'msginit --input=' + potFile + ' --locale=' + lang + ' --output=src/languages/buddyboss-'+lang+'.po && msgfmt -o src/languages/buddyboss-'+lang+'.mo src/languages/buddyboss-'+lang+'.po';
					}
				},
				updatePo: {
					command: function (lang) {
						return 'msgmerge --update --backup=off --force-po src/languages/buddyboss-' + lang + '.po ' + potFile;
					}
				},
			},
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
	grunt.registerTask('pre-commit', ['checkDependencies', 'jsvalidate', 'jshint', 'stylelint']);
	grunt.registerTask('src', ['checkDependencies', 'jsvalidate', 'jshint', 'stylelint', 'sass', 'rtlcss', 'checktextdomain', /*'imagemin',*/ 'uglify', 'cssmin', 'makepot:src']);
	grunt.registerTask('bp_rest', ['clean:bp_rest', 'exec:rest_api', 'copy:bp_rest_components', 'copy:bp_rest_core', 'clean:bp_rest', 'apidoc' ]);
	grunt.registerTask('bp_performance', ['clean:bp_rest', 'exec:rest_performance', 'copy:bp_rest_performance', 'copy:bp_rest_mu', 'clean:bp_rest']);
	grunt.registerTask('build', ['string-replace:dist', 'exec:composer', 'exec:cli', 'clean:all', 'copy:files', 'compress', 'clean:all']);
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


	grunt.registerTask('addUpdatePoAndMo', function () {
		languages.forEach(async function (lang) {
			const existingFile = 'src/languages/buddyboss-' + lang.code + '.po';
			if (fs.existsSync(existingFile)) {
				// If translation file already exists, update it based on the .pot file
				grunt.task.run(['shell:updatePo:' + lang.code]);
				grunt.log.writeln(`Translation file for ${lang.code} updated successfully.`);
			} else {
				// If translation file doesn't exist, generate a new one
				grunt.task.run(['shell:generatePoMo:' + lang.code]);
				grunt.log.writeln(`Translation file for ${lang.code} created successfully.`);
			}

		});
	});

	// Define a function to generate tasks for each language
	function generateTranslationTask(language) {
		const taskName = `syncTranslation_${language.locale}`;
		grunt.registerTask(taskName, () => {
			const command = `node translate-script.js --project_id=<GET_FROM_GOOGLE_CREDENTIALS_JSON_FILE> --po_source=src/languages/buddyboss-${language.code}.po --po_dest=src/languages/buddyboss-${language.code}.po --mo=src/languages/buddyboss-${language.code}.mo --lang=${language.locale} --whitelist=whitelist.txt`;
			const done = grunt.task.current.async();
			grunt.util.spawn({ cmd: 'sh', args: ['-c', command] }, () => done());
		});
	}

	languages.forEach((language) => {
		// Create a task for each language
		generateTranslationTask(language);
	});

	// Create a master task to run all language tasks
	grunt.registerTask('sync-translation', languages.map((language) => `syncTranslation_${language.locale}`));

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
