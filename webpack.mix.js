const mix = require('laravel-mix')
const path = require('path')

mix.webpackConfig(webpack => {
    return {
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'resources/js')
            }
        },
        module: {
            rules: [
                {
                    test: /\.s[ac]ss$/i,
                    use: [
                        'postcss-loader',
                        'sass-loader',
                    ],
                }
            ],
        },
    }
})

mix.js('resources/js/Woo/woo.js', `public/assets/woo/scripts/gang-sheet-admin.js`).vue()
mix.sass('resources/css/woo.scss', `public/assets/woo/css/gang-sheet-admin.css`)

mix.js('resources/js/Woo/woo-gs-product.js', `public/assets/woo/scripts/gang-sheet-product.js`)
mix.js('resources/js/Woo/woo-gs-edit.js', `public/assets/woo/scripts/gang-sheet-edit.js`)
mix.js('resources/js/Woo/woo-gs-login.js', `public/assets/woo/scripts/gang-sheet-login.js`)
mix.sass('resources/css/woo-gs-product.scss', `public/assets/woo/css/gang-sheet-product.css`)

mix.js('resources/js/V1/gs-builder.js', `public/assets/v1/scripts/gs-builder.min.js`)
