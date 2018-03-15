# Installation

## Simple

Download [madeline.php](https://phar.madelineproto.xyz/madeline.php).

## Composer

Once you have all the requirements installed properly (on dev as well as production), add this to the ```composer.json``` file:

```
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/danog/phpseclib"
    }
],
```

Make sure you also have these set in the composer.json:

```
"minimum-stability": "dev",
```

Then you can require the package by addding the following line to the require section:

```
"danog/madelineproto":"dev-master"
```

## git

Run the following commands in a console:

```
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


