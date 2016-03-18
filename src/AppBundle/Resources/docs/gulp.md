18 March 2016

Wanted to be able to transfer various css/js files from node_modules and src to the web directory

Spent some time researching webpack but there is still too much majic involved.  Especially with bootstrap.

gulp seems reasonably straight forward to understand.  Try it.

    $ node --version == v5.6.0
    $ npm --version  ==  3.6.0
    $ npm init --yes # makes package.json
    
    $ npm install --save-dev gulp gulp-concat gulp-concat-css
    
    $ node_modules/.bin/gulp --version
    [09:37:21] CLI version 3.9.1
    [09:37:21] Local version 3.9.1
    
    package.json scripts
        "gulp": "./node_modules/.bin/gulp"
    # npm run gulp
    
gulp-concat-css throws an error.  concat works fine.  I suspect an error in the css files.

