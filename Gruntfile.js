module.exports = function(grunt) {
	'use strict';

	// Load all grunt tasks matching the `grunt-*` pattern
	require('load-grunt-tasks')(grunt);

	grunt.initConfig({
		// Package info
		pkg: grunt.file.readJSON('package.json'),

		// Make POT file
		makepot: {
			target: {
				options: {
					cwd: '',
					domainPath: '/languages',
					exclude: ['node_modules/.*', 'vendor/.*', 'tests/.*'],
					mainFile: 'buddypress-followers.php',
					potComments: 'Copyright (C) {year} {package-author}\nThis file is distributed under the {package-license}.',
					potFilename: 'buddypress-followers.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true,
						'report-msgid-bugs-to': 'https://wordpress.org/support/plugin/buddypress-followers',
						'last-translator': 'FULL NAME <EMAIL@ADDRESS>',
						'language-team': 'LANGUAGE <LL@li.org>'
					},
					processPot: function(pot, options) {
						pot.headers['project-id-version'] = 'BuddyPress Follow ' + grunt.config('pkg.version');
						return pot;
					},
					type: 'wp-plugin',
					updateTimestamp: true,
					updatePoFiles: false
				}
			}
		},

		// Watch for changes
		watch: {
			makepot: {
				files: ['**/*.php', '!node_modules/**', '!vendor/**', '!tests/**'],
				tasks: ['makepot']
			}
		}
	});

	// Register tasks
	grunt.registerTask('default', ['makepot']);
	grunt.registerTask('build', ['makepot']);
	grunt.registerTask('i18n', ['makepot']);

	grunt.util.linefeed = '\n';
};
