# Composer Dist Installer

Automatically installs .dist files when you run composer install in your project, and optionally populates them with
data using a very simple and intuitive templating syntax.

## Usage

Add the following into your root `composer.json` file:

```json
{
    "require": {
        "cube/composer-dist-installer": "~0.2"
    },
    "scripts": {
        "post-install-cmd": [
            "Cube\\ComposerDistInstaller\\Bootstrap::install"
        ]
    },
    "extra": {
        "dist-installer-params": {
            "file": "config/autoload/database.config.php.dist"
        }
    }
}
```

The file `config/autoload/database.config.php` will then be created based on the template provided at
`config/autoload/database.config.php.dist` and by asking you for any parameters requested in the `.dist` file.

By default, the dist file is assumed to be in the same place than the parameters file, suffixed by ``.dist``. 
This can be changed in the configuration:

```json
{
    "extra": {
        "dist-installer-params": {
            "file": "config/autoload/database.config.php",
            "dist-file": "some/other/folder/file/database.dist"
        }
    }
}
```

The script handler will ask you interactively for parameters which are requested in the `.dist` file, using optional
default values.

If composer is run in a non-interactive mode, the default values will be used for missing parameters.

**Warning:** If a configuration file already exists in the destination, you will be promted whether you want to override
it. If you choose to overwrite, a backup file will be created right next to the config file. You're in charge of 
manually merging the differences between the new and the old file - and then deleting the old file.

### Multiple Files
You can specify more than one file to be processed by using the following alternate syntax:

```json
{
    "extra": {
        "dist-installer-params": [
            {
                "file": "config/autoload/database.config.php",
                "dist-file": "some/other/folder/file/database.dist"
            },
            {
                "file": "config/autoload/session.config.php",
                "dist-file": "some/other/folder/file/session.dist"
            }
        ]
    }
}
```

## Template Syntax
Before the template files (`.dist` files) are copied to the final destination, a processor will look for any parameters
you may have included in the template and ask you for their value.
 
The syntax for a parameter is `{{QUESTION|DEFAULT}}`.

For example, consider the following template:
```php
<?php
return array(
    'doctrine' => array(
        'connection' => array(
            // default connection name
            'orm_default' => array(
                'driverClass' => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
                'params' => array(
                    'host'     => '{{Database host?|localhost}}',
                    'port'     => '{{Database port?|3306}}',
                    'user'     => '{{Database user?|=ENV[USER]}}', // <-- more on this one later
                    'password' => '{{Database password?}}',
                    'dbname'   => '{{Database name?}}',
                )
            ),
        )
    ),
);
```

When installing the project, composer will ask you for all the information and use defaults where necessary. The prompt
could look like this:

```
$ composer install

# .... some composer output

Creating the config/autoload/database.config.php file
Destination file already exists, overwrite (y/n)? y
A copy of the old configuration file was saved to config/autoload/database.config.php.old

Database host [localhost]? test.db.acme.be
Database port [3306]? #<enter>
Database user [test3210]? #<enter> to accept the default,  which was pulled from the USER environment variable>
Database password? 1234test
```

### Environment Variables
It is possible to use environment values in order to set default values for parameters. The syntax for that is
`{{Question|=ENV[VARIABLE_NAME]}}`, where `VARIABLE_NAME` is the environment value that will provide the default.

In the example above, the following line used the `USER` environment variable as a default value:

# LICENSE
See `LICENSE.txt` file in this same package.

# Credits

Copyright (c) 2015 by Cu.be Solutions

Authors:
* Gabriel Somoza (gabriel.somoza@cu.be)