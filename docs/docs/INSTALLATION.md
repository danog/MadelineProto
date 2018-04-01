# Installation

There are various ways to install MadelineProto:

* [Simple](#simple)
* [Simple (manual)](#simple-manual)
* [Composer from scratch](#composer-from-scratch)
* [Composer from existing project](#composer-from-existing-project)
* [Git](#git)


## Simple

```php
<?php
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz', 'madeline.php');
}
require_once 'madeline.php';
```

This code will automatically download, auto-update and include MadelineProto.


## Simple (manual)

Download [madeline.php](https://phar.madelineproto.xyz/madeline.php), put it in the same directory as your script, and then put the following code in your PHP file:
```php
<?php
require_once 'madeline.php';
```

## Composer from scratch

composer.json:
```json
{
    "name": "yourname/yourproject",
    "description": "Project description",
    "type": "project",
    "require": {
        "danog/madelineproto": "dev-master"
    },
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/danog/phpseclib"
        }
    ],
    "minimum-stability": "dev",
    "license": "AGPL-3.0-only",
    "authors": [
        {
            "name": "Daniil Gentili",
            "email": "daniil.gentili.dg@gmail.com"
        }
    ],
    "autoload": {
        "psr-0": {
            "Your\\Project\\": "src/"
        }
    }
}
```

Then run:
```bash
composer update
```

Put the following code in your PHP file:
```php
<?php
require_once 'vendor/autoload.php';
```

## Composer from existing project

Once you have all the requirements installed properly (on dev as well as production), add this to the ```composer.json``` file:

```json
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/danog/phpseclib"
    }
],
```

Make sure you also have these set in the composer.json:

```json
"minimum-stability": "dev",
```

Then you can require the package by addding the following line to the require section:

```json
"danog/madelineproto":"dev-master"
```



## git

Run the following commands in a console:

```bash
mkdir MadelineProtoBot
cd MadelineProtoBot
git init .
git submodule add https://github.com/danog/MadelineProto
cd MadelineProto
composer update
cp .env.example .env
cp -a *php tests userbots .env* ..
```

Now open `.env` and edit its values as needed.

<amp-form method="GET" target="_top" action="https://docs.madelineproto.xyz/docs/REQUIREMENTS.html"><input type="submit" value="Previous section" /></amp-form><amp-form action="https://docs.madelineproto.xyz/docs/UPDATES.html" method="GET" target="_top"><input type="submit" value="Next section" /></amp-form>