# MadelineProto
[![StyleCI](https://styleci.io/repos/61838413/shield)](https://styleci.io/repos/61838413)
[![Build Status](https://travis-ci.org/danog/MadelineProto.svg?branch=master)](https://travis-ci.org/danog/MadelineProto)  

Licensed under AGPLv3.

PHP implementation of MTProto, based on [telepy](https://github.com/griganton/telepy_old).

This project can run on PHP 7, PHP 5.6 and HHVM.  

This is a WIP.
Here all of the things that still have to be done in this library.  
You can (and you are also encouraged to) contribute by completing any of the following points.  
The importance of each item will range from 1 to 5. It's better to start from the most important items.

* In Session.php and TL, manage rpc errors, notifications, error codes and basically everything that isn't a normal response (4).
* In Connection.php and Session.php, add support for http, https and (maybe) udp connections (3).
* In API.php, complete a decent authorization flow that supports both bots and normal users (2).
* In PrimeModule.php, fix the mess in it, choose one of (the fastest) native php prime factorization function and implement it without biginteger (1.5).


The name of this project is inspired by [this person](https://s-media-cache-ak0.pinimg.com/736x/f0/a1/70/f0a170718baeb0e3817c612d96f5d1cf.jpg).
