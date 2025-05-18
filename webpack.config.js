const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const CopyPlugin = require('copy-webpack-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  
  return {
    entry: {
      'admin': './admin/js/wp-dual-ai-admin.js',
      'public': './public/js/wp-dual-ai-public.js',
      'text-chat': './public/js/wp-dual-ai-text-chat.js',
      'voice-chat': './public/js/wp-dual-ai-voice-chat.js',
      'admin-style': './admin/css/wp-dual-ai-admin.css',
      'public-style': './public/css/wp-dual-ai-public.css',
      'chat-style': './public/css/wp-dual-ai-chat.css'
    },
    output: {
      filename: ({ chunk }) => {
        // JS files go to their respective folders
        return chunk.name.includes('style') ? '[name].css' : 
          chunk.name.includes('admin') ? 'admin/js/[name].min.js' : 'public/js/[name].min.js';
      },
      path: path.resolve(__dirname, 'dist'),
      clean: true
    },
    module: {
      rules: [
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env']
            }
          }
        },
        {
          test: /\.css$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader'
          ]
        },
        {
          test: /\.(png|svg|jpg|jpeg|gif)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'assets/images/[name][ext]'
          }
        },
        {
          test: /\.(woff|woff2|eot|ttf|otf)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'assets/fonts/[name][ext]'
          }
        },
        {
          test: /\.(mp3|wav)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'assets/audio/[name][ext]'
          }
        }
      ]
    },
    plugins: [
      new MiniCssExtractPlugin({
        filename: ({ chunk }) => {
          return chunk.name.includes('admin') ? 'admin/css/[name].min.css' : 'public/css/[name].min.css';
        }
      }),
      new CopyPlugin({
        patterns: [
          { 
            from: 'assets',
            to: 'assets'
          },
          {
            from: 'admin/partials',
            to: 'admin/partials'
          },
          {
            from: 'public/partials',
            to: 'public/partials'
          },
          {
            from: '*.php',
            to: '[name][ext]'
          },
          {
            from: 'includes/*.php',
            to: '[path][name][ext]'
          },
          {
            from: 'admin/*.php',
            to: '[path][name][ext]'
          },
          {
            from: 'public/*.php',
            to: '[path][name][ext]'
          },
          {
            from: 'api/*.php',
            to: '[path][name][ext]'
          },
          {
            from: 'admin/reports/*.php',
            to: '[path][name][ext]'
          }
        ]
      })
    ],
    optimization: {
      minimizer: [
        new TerserPlugin({
          extractComments: false,
          terserOptions: {
            format: {
              comments: false,
            },
          },
        }),
        new CssMinimizerPlugin()
      ],
      minimize: isProduction
    },
    devtool: isProduction ? false : 'source-map'
  };
};
