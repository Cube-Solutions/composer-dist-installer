# Composer Dist Installer
[![Build Status](https://travis-ci.org/Cube-Solutions/composer-dist-installer.svg?branch=master)](https://travis-ci.org/Cube-Solutions/composer-dist-installer)
[![Code Coverage](https://scrutinizer-ci.com/g/Cube-Solutions/composer-dist-installer/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Cube-Solutions/composer-dist-installer/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Cube-Solutions/composer-dist-installer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Cube-Solutions/composer-dist-installer/?branch=master)

Automatically installs .dist files when you run composer install in your project, and optionally populates them with
data using a very simple and intuitive templating syntax.

This project is designed to be framework-agnostic. All you need is to be using Composer to manage your project's dependencies.

## Installation

Simply use Composer to install by running:

    composer require cube/composer-dist-installer:~1.0@beta

## Usage

Add the following into your root `composer.json` file:

```json
{
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

* `QUESTION`: should contain the entire question, including question marks etc. Optionally include `[]` anywhere in the 
    string to specify the location of the default value within the question (otherwise there will be no indication that
    there IS a default value).
* `DEFAULT`: specify the default value. To use an environment variable as default use the following syntax: 
    `=ENV[VARIABLE_NAME]`

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
                    'host'     => '{{Database host []?|localhost}}',
                    'port'     => '{{Database port []?|3306}}',
                    'user'     => '{{Database user []?|=ENV[USER]}}',
                    'password' => '{{Database password?}}',
                    'dbname'   => '{{Database name?}}',
                )
            ),
        )
    ),
);
```

When installing the project, composer will ask you for all the information and use defaults where necessary. The prompt
would look something like this:

```
$ composer install

# .... some composer output

Creating the config/autoload/database.config.php file
Destination file already exists, overwrite (y/n)? y
A copy of the old configuration file was saved to config/autoload/database.config.php.old

Database host [localhost]? test.db.acme.be
Database port [3306]?    # <enter> to accept the default
Database user [staging]? # this default value was pulled from
                         # the "USER" environment variable.
Database password? 1234test
Database name? stage
```

And the final file will look like this:

```php
<?php
return array(
    'doctrine' => array(
        'connection' => array(
            // default connection name
            'orm_default' => array(
                'driverClass' => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
                'params' => array(
                    'host'     => 'test.db.acme.be',
                    'port'     => '3306',
                    'user'     => 'staging',
                    'password' => '1234test',
                    'dbname'   => 'stage',
                )
            ),
        )
    ),
);
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
