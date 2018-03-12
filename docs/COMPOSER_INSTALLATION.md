# Composer

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
