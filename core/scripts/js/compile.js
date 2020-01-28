const chalk = require('chalk');
const log = require('./log');
const babel = require('@babel/core');

module.exports = (filePath, callback) => {
  // Transform the file.
  // Check process.env.NODE_ENV to see if we should create sourcemaps.
  babel.transformFile(
    filePath,
    {
      sourceMaps: process.env.NODE_ENV === 'development' ? 'inline' : false,
      comments: false,
      plugins: [
        ['add-header-comment', {
          'header': [
            `DO NOT EDIT THIS FILE.\nSee the following change record for more information,\nhttps://www.drupal.org/node/2815083\n@preserve`
          ]
        }]
      ]
    },
    (err, result) => {
      if (err) {
        log(chalk.red(err));
        process.exitCode = 1;
      }
      else {
        callback(result.code);
      }
    }
  );
};
