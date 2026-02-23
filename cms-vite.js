import { viteStaticCopy } from 'vite-plugin-static-copy';

/**
 * Usage example in vite.config.js:
 *
 * import { defineConfig } from 'vite';
 * import laravel from 'laravel-vite-plugin';
 * import concat from 'rollup-plugin-concat';
 * import { viteStaticCopy } from 'vite-plugin-static-copy';
 * import { mergeViteConfigs } from './path-to-plugin/mergeViteConfigs';
 *
 * let config = defineConfig({
 *     plugins: [
 *         laravel({ ... }),
 *         concat({ ... }),
 *         viteStaticCopy({ ... }),
 *     ],
 * });
 *
 * config = mergeViteConfigs(config);
 *
 * export default config;
 */


/**
 * Merges missing Vite configuration from an extended config into a base config
 * This function adds missing Laravel inputs, concat groups, and static copy targets
 * @param {Object} baseConfig - The base Vite config object
 * @param {string} packagePath - The path to the package (e.g., 'vendor/javaabu/cms')
 */
export function mergeViteConfigs(baseConfig, packagePath = './vendor/javaabu/cms') {
    // Additional Laravel inputs from the extended config
    const additionalInputs = [
        'resources/js/select2-custom.js',
        'resources/js/editor.js',
        'resources/js/dv.js',
        'resources/js/jquery-menu-editor.js',
        'resources/js/select2-thaana.full.js',
        'resources/js/utilities.js',
    ].map(file => `${packagePath}/${file}`);

    // Additional concat groups (admin-vendors and app-vendors)
    const additionalConcatGroups = [
        {
            files: [
                'node_modules/dropzone/dist/min/dropzone.min.js',
                'node_modules/bootbox/dist/bootbox.all.min.js',
                'node_modules/jquery.scrollbar/jquery.scrollbar.min.js',
                'node_modules/jquery-scroll-lock/dist/jquery-scrollLock.min.js',
            ],
            groupIndex: 3, // Add to existing admin-vendors group
        },
        {
            files: [
                'node_modules/jq-simple-connect/source/jqSimpleConnect.js',
            ],
            outputFile: 'public/js/jqSimpleConnect.js',
        },
        {
            files: [
                'node_modules/dropzone/dist/min/dropzone.min.js',
                'node_modules/select2/dist/js/select2.full.min.js',
                'node_modules/aos/dist/aos.js',
                'node_modules/jscroll/dist/jquery.jscroll.min.js',
                'node_modules/lightbox2/dist/js/lightbox.min.js',
                'node_modules/raphael/raphael.min.js',
                'node_modules/jquery-mapael/js/jquery.mapael.min.js',
                'node_modules/moment-timezone/builds/moment-timezone-with-data.min.js',
                'node_modules/odometer/odometer.min.js',
                'node_modules/simplebar/dist/simplebar.min.js',
                'node_modules/slick-carousel/slick/slick.min.js',
            ],
            outputFile: 'public/js/app-vendors.js',
        },
        {
            files: [
                'node_modules/select2/dist/css/select2.min.css',
                'node_modules/aos/dist/aos.css',
                'node_modules/lightbox2/dist/css/lightbox.min.css',
                'node_modules/slick-carousel/slick/slick.css',
                'node_modules/slick-carousel/slick/slick-theme.css',
            ],
            outputFile: 'public/css/app-vendors.css',
        },
    ];

    // Additional static copy targets
    const additionalStaticTargets = [
        {
            src: 'node_modules/jscroll/dist',
            dest: 'public/vendors/jscroll'
        },
        {
            src: 'node_modules/aos/dist/',
            dest: 'public/vendors/aos/dist'
        },
        {
            src: 'node_modules/apexcharts/dist/',
            dest: 'public/vendors/apexcharts/'
        },
        {
            src: 'node_modules/simplebar/dist/simplebar.min.css',
            dest: 'public/vendors/simplebar'
        },
        {
            src: 'node_modules/slick-carousel/slick/fonts',
            dest: 'public/css/fonts'
        },
        {
            src: 'node_modules/slick-carousel/slick/ajax-loader.gif',
            dest: 'public/css'
        },
        {
            src: 'node_modules/lightbox2/dist/images',
            dest: 'public/images/'
        },
        {
            src: `${packagePath}/${file}/`+'resources/js/jquery-menu-editor.js',
            dest: 'public/js'
        }
    ];

    // Merge additional inputs into Laravel config
    const laravel = baseConfig.plugins.find(p => p.name === 'laravel-vite-plugin');
    if (laravel && laravel.config) {
        laravel.config.input = Array.isArray(laravel.config.input)
            ? [...laravel.config.input, ...additionalInputs]
            : [laravel.config.input, ...additionalInputs];
    }

    // Merge additional concat groups
    const concatPlugin = baseConfig.plugins.find(p => p.name === 'rollup-plugin-concat');
    if (concatPlugin && concatPlugin.options && concatPlugin.options.groupedFiles) {
        concatPlugin.options.groupedFiles = [
            ...concatPlugin.options.groupedFiles,
            ...additionalConcatGroups,
        ];
    }

    // Add viteStaticCopy plugin if it doesn't exist
    const hasStaticCopy = baseConfig.plugins.some(p => p.name === 'vite-plugin-static-copy');
    if (!hasStaticCopy) {
        // Import viteStaticCopy at the top of your config file
        // Then add this plugin configuration
        baseConfig.plugins.push({
            name: 'vite-plugin-static-copy',
            options: {
                targets: additionalStaticTargets,
            },
        });
    } else {
        // Merge with existing static copy targets
        const staticPlugin = baseConfig.plugins.find(p => p.name === 'vite-plugin-static-copy');
        if (staticPlugin && staticPlugin.options && staticPlugin.options.targets) {
            staticPlugin.options.targets = [
                ...staticPlugin.options.targets,
                ...additionalStaticTargets,
            ];
        }
    }

    return baseConfig;
}

