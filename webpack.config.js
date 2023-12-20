const Encore = require('@symfony/webpack-encore');
const FosRouting = require('fos-router/webpack/FosRouting');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .addPlugin(new FosRouting())
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or subdirectory deploy
    //.setManifestKeyPrefix('build/')

    /**
     * Pour trumbowyg
     */
    .copyFiles([
        {from: './node_modules/trumbowyg/dist/ui/', to: 'trumbowyg/[path][name].[ext]', pattern: /\.(svg)$/, includeSubdirectories: false}
    ])

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')
    .addEntry('admin/admin', './assets/js/admin/admin.js')
    .addEntry('admin/perimeter/perimeter-import', './assets/js/admin/perimeter/perimeter-import.js')
    .addEntry('admin/data-source/analyse', './assets/js/admin/data-source/analyse.js')

    .addEntry('front/home', './assets/js/front/home.js')
    .addEntry('front/cartography/cartography', './assets/js/front/cartography/cartography.js')
    .addEntry('front/cartography/details', './assets/js/front/cartography/details.js')
    .addEntry('front/blog/blog', './assets/js/front/blog/blog.js')
    .addEntry('front/aid/aid/index', './assets/js/front/aid/aid/index.js')
    .addEntry('front/aid/aid/edit', './assets/js/front/aid/aid/edit.js')
    .addEntry('front/project/project', './assets/js/front/project/project.js')
    .addEntry('front/reference/projets_subventionnes', './assets/js/front/reference/projets_subventionnes.js')
    .addEntry('front/program/index', './assets/js/front/program/index.js')
    .addEntry('front/aid/aid/detail', './assets/js/front/aid/aid/detail.js')
    .addEntry('front/user/register', './assets/js/front/user/register.js')
    .addEntry('front/user/project/index', './assets/js/front/user/project/index.js')
    .addEntry('front/user/project/fiche_projet', './assets/js/front/user/project/fiche_projet.js')
    .addEntry('form/checkbox-multiple-search', './assets/js/form/checkbox-multiple-search.js')
    .addEntry('form/entity-checkbox-absolute-type', './assets/js/form/entity-checkbox-absolute-type.js')

    /**
     * ATTENTION, pour ajouter des pages il faut compiler la première fois avec yarn encore dev (le --watch ne semble pas ajouter les scripts)
     */

    .addEntry('import-scss/admin/admin', './assets/js/import-scss/admin/admin.js')
    .addEntry('import-scss/home', './assets/js/import-scss/home.js')
    .addEntry('import-scss/static/sitemap', './assets/js/import-scss/static/sitemap.js')
    .addEntry('import-scss/blog/blogpost/details', './assets/js/import-scss/blog/blogpost/details.js')
    .addEntry('import-scss/cartography/details', './assets/js/import-scss/cartography/details.js')
    .addEntry('import-scss/aid/aid/index', './assets/js/import-scss/aid/aid/index.js')
    .addEntry('import-scss/project/project', './assets/js/import-scss/project/project.js')
    .addEntry('import-scss/project/public-details', './assets/js/import-scss/project/public-details.js')
    .addEntry('import-scss/aid/aid/detail', './assets/js/import-scss/aid/aid/detail.js')
    .addEntry('import-scss/aid/aid/edit', './assets/js/import-scss/aid/aid/edit.js')
    .addEntry('import-scss/program/program-details', './assets/js/import-scss/program/program-details.js')
    .addEntry('import-scss/user/register', './assets/js/import-scss/user/register.js')
    .addEntry('import-scss/user/project/fiche_projet', './assets/js/import-scss/user/project/fiche_projet.js')
    .addEntry('import-scss/reference/index', './assets/js/import-scss/reference/index.js')
    .addEntry('import-scss/reference/projets_subventionnes', './assets/js/import-scss/reference/projets_subventionnes.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // configure Babel
    // .configureBabel((config) => {
    //     config.plugins.push('@babel/a-babel-plugin');
    // })

    // enables and configure @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })

    // enables Sass/SCSS support
    .enableSassLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    .autoProvidejQuery()

    // permet de garder la structure du dossier images
    .configureFilenames({
        assets: '[path][name].[hash:8].[ext]'
    })

    .copyFiles({
        from: './assets/images',

        // optional target path, relative to the output dir
        // to: 'images/[path][name].[ext]',

        // if versioning is enabled, add the file hash too
        to: 'images/[path][name].[hash:8].[ext]',

        // only copy files matching this pattern
        //pattern: /\.(png|jpg|jpeg)$/
    })

    .copyFiles({
        from: './assets/pdfs',

        // optional target path, relative to the output dir
        // to: 'images/[path][name].[ext]',

        // if versioning is enabled, add the file hash too
        to: 'pdfs/[path][name].[hash:8].[ext]',

        // only copy files matching this pattern
        pattern: /\.(pdf)$/
    })
;

module.exports = Encore.getWebpackConfig();
