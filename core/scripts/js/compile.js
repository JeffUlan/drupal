const babel = require('babel-core');

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
            `DO NOT EDIT THIS FILE.\nAll changes should be applied to ${filePath}\nSee the following change record for more information,\nhttps://www.drupal.org/node/2873849\n@preserve`
          ]
        }]
      ]
    },
    (err, result) => {
      if (err) {
        throw new Error(err);
      }
      callback(result.code);
    }
  );
}
