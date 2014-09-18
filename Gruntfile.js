//Gruntfile
// TODO: Add php lint tasks (grunt-phplint, grunt-phpcs, grunt-phpmd)

module.exports = function( grunt ) {
    'use strict';

    //Initializing the configuration object
    grunt.initConfig( {

        // Project configuration
        pkg: grunt.file.readJSON( 'package.json' ),

        // Task configuration

        addtextdomain: {
            options: {
                textdomain: '<%= pkg.name %>'
            },
            all: [ '**/*.php', '!vendor/**', '!node_modules/**' ]
        },
        autoprefixer: {
            options: {
                browsers: [ '> 2%', 'last 2 versions', 'Firefox ESR', 'Opera 12.1' ]
            },
            all: {
                src: [ './admin/css/**/*.css', './public/css/**/*.css' ] // Overwrite current file
            }
        },
        csscomb: {
            options: {
                config: '.csscomb.json'
            },
            all: {
                src: [ './admin/css/**/*.css', './public/css/**/*.css' ],
                expand: true
            }
        },
        csslint: {
            options: {
                ids: false,
                'import': 2
            },
            all: {
                src: [ './admin/css/**/*.css', './public/css/**/*.css' ]
            }
        },
        exec: {
            updatePo: {
                cmd: 'tx pull -a --minimum-perc=100'
            }
        },
        jscs: {
            options: {
                preset: 'jquery',
                reporter: 'console'
            },
            all: [ './public/js/**/*.js', './admin/js/**/*.js' ],
            grunt: {
                options: {
                    config: true,
                    maximumLineLength: { 'value': 120 },
                    validateQuoteMarks: { 'mark': '\'', 'escape': true }
                },
                src: [ 'Gruntfile.js' ]
            }
        },
        jshint: {
            options: {
                reporter: require( 'jshint-stylish' ),

                // Using WordPress "official" options here
                // http://develop.svn.wordpress.org/trunk/.jshintrc
                boss: false,            // Varying because it's dangerous. http://www.jshint.com/docs/options/#boss
                curly: true,
                eqeqeq: true,
                eqnull: true,
                es3: true,
                expr: false,            // Varying. http://www.jshint.com/docs/options/#expr
                immed: true,
                noarg: true,
                onevar: true,
                quotmark: 'double',     // Varying. jQuery uses double quotes. uglify, too.
                trailing: true,
                undef: true,
                unused: true,

                browser: true,

                globals: {
                    _: false,
                    Backbone: false,
                    jQuery: false,
                    wp: false
                },

                strict: true        // Varying because I prefer it that way. :)
            },
            grunt: {
                options: {
                    devel: false,
                    browser: false,
                    node: true,
                    quotmark: 'single'     // Single quotes are much more beautiful. :3 Let's use them here.
                },
                files: {
                    src: [ 'Gruntfile.js' ]
                }
            },
            dev: {
                options: {
                    devel: true
                },
                files: {
                    src: [ './public/js/**/*.js', './admin/js/**/*.js', '!*.min.js' ]
                }
            },
            deploy: [ './public/js/**/*.js', './admin/js/**/*.js', '!*.min.js' ]
        },
        makepot: {
            options: {
                domainPath: '/languages',
                potComments: 'Copyright (C) {year} <%= pkg.displayName %> by <%= pkg.author %>\n' +
                'This file is distributed under the same license as the Limit Login Countries package.',
                potHeaders: {
                    poedit: true,
                    'x-poedit-keywordslist': true,
                    'report-msgid-bugs-to': 'https://github.com/wedi/limit-login-countries/issues',
                    'language': 'en_US',
                    'language-team': 'WP-Translators on Transifex ' +
                    '(http://www.transifex.com/projects/p/limit-login-countries/)',
                    'last-translator': 'WP-Translators (http://WP-Translators.org/)'
                },
                processPot: function( pot ) {
                    delete pot.headers['x-generator'];
                    return pot;
                },
                type: 'wp-plugin',              // Type of project (wp-plugin or wp-theme).
                updateTimestamp: false          // Whether the POT-Creation-Date should be updated without other changes
            },
            all: [ '**/*.php', '!vendor/**', '!node_modules/**' ]
        },
        po2mo: {
            all: {
                src: './languages/**/*.po',
                expand: true
            }
        },
        uglify: {
            deploy: {
                options: {
                    banner: '/*! <%= pkg.name %> */',
                    footer: '\n',
                    mangle: true,
                    compress: true,
                    preserveComments: 'some'
                },
                files: {
                    './admin/js/limit-login-countries.min.js': './admin/js/limit-login-countries.js'
                }
            }
        },
        watch: {
            options: {
                livereload: true,
                spawn: false,
                interrupt: true
            },
            po2mo: {
                files: [ './languages/**/*.po' ],
                tasks: [ 'po2mo' ]
            },
            php: {
                files: [ './**/*.php' ],
                tasks: [ 'php' ]
            },
            js: {
                files: [ './admin/js/**/*.js', './public/js/**/*.js' ],
                tasks: [ 'js' ]
            },
            css: {
                files: [ './admin/css/**/*.css', './public/css/**/*.css' ],
                tasks: [ 'css' ]
            },
            grunt: {
                options: {
                    atBegin: true
                },
                files: [ 'Gruntfile.js' ],
                tasks: [ 'grunt' ]
            }
        }
    } );

    // Plugin loading
    require( 'load-grunt-tasks' )( grunt );

    // Compile developer friendly environment
    grunt.registerTask( 'grunt', [ 'jshint:grunt', 'jscs:grunt' ] );

    grunt.registerTask( 'css', [ 'autoprefixer', 'csscomb', 'csslint' ] );
    grunt.registerTask( 'js', [ 'jshint:dev', 'jscs' ] );
    grunt.registerTask( 'l18n:dev', [ 'addtextdomain', 'makepot' ] );
    grunt.registerTask( 'l18n:deploy', [ 'exec:updatePo', 'po2mo' ] );

    grunt.registerTask( 'dev', [ 'grunt', 'css', 'js', 'l18n:dev' ] );
    grunt.registerTask( 'deploy', [ 'css', 'js', 'l18n:deploy' ] );

    grunt.registerTask( 'default', [ 'dev', 'watch' ] );

};
