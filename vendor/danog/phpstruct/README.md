# PHPStruct class

[![Build Status](https://travis-ci.org/danog/PHPStruct.svg?branch=master)](https://travis-ci.org/danog/PHPStruct)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/7b91e30ec89a4313bdb34766ea990113)](https://www.codacy.com/app/daniil-gentili-dg/PHPStruct?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=danog/PHPStruct&amp;utm_campaign=Badge_Grade)
[![License](https://img.shields.io/packagist/l/danog/phpstruct.svg?maxAge=2592000?style=flat-square)](https://opensource.org/licenses/MIT)
[![Packagist download count](https://img.shields.io/packagist/dm/danog/phpstruct.svg?maxAge=2592000?style=flat-square)](https://packagist.org/packages/danog/phpstruct)
[![Packagist](https://img.shields.io/packagist/v/danog/PHPStruct.svg?maxAge=2592000?style=flat-square)](https://packagist.org/packages/danog/phpstruct)
[![HHVM Status](http://hhvm.h4cc.de/badge/danog/phpstruct.svg?style=flat-square)](http://hhvm.h4cc.de/package/danog/phpstruct)
[![StyleCI](https://styleci.io/repos/62454134/shield)](https://styleci.io/repos/62454134)

Licensed under MIT.

PHP implementation of Python's struct module.

This library was created to help me develop a [client for the mtproto protocol](https://github.com/danog/MadelineProto).  

The functions and the formats are exactly the ones used in python's struct 
(https://docs.python.org/3/library/struct.html)

This library can be used to pack/unpack strings, ints, floats, chars and bools into bytes.
It has lots of advantages over PHP's native implementation of pack and unpack, such as:  
* Custom byte endianness.
* Lots of useful formats that aren't present in the native implementation.
* The syntax of the format string of pack and unpack is the same as in python's struct module.
* The result of unpack is normal numerically indexed array that starts from 0 like it should.
* The result of unpack has type casted values (int for integer formats, bool for boolean formats, float for float formats and string for all of the other formats).
* The calcsize function is implemented.
* The q and Q formats can be used even on 32 bit systems (the downside is limited precision).
* Padding is supported for the @ modifier.

For now custom byte size may not work properly on certain machines for the f and d formats.

## Installation

Install using composer:
```
composer require danog/phpstruct
```

# Usage
Dynamic (recommended)  
```
require('vendor/autoload.php');
$struct = new \danog\PHP\StructClass();
$pack = $struct->pack("2cxi", "ab", 44);
$unpack = $struct->unpack("2cxi", $pack);
var_dump($unpack);
$count = $struct->calcsize("2cxi");
```  

Dynamic (while specifying format string during istantiation)  
```
require('vendor/autoload.php');
$struct = new \danog\PHP\StructClass("2cxi");
$pack = $struct->pack("ab", 44);
$unpack = $struct->unpack($pack);
var_dump($unpack);
$count = $struct->size;
$formatstring = $struct->format;
```

Static


```
require('vendor/autoload.php');
$pack = \danog\PHP\Struct::pack("2cxi", "ab", 44);
$unpack = \danog\PHP\Struct::unpack("2cxi", $pack);
var_dump($unpack);
$count = \danog\PHP\Struct::calcsize("2cxi");
```


[Daniil Gentili](http://daniil.it)
