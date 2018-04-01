# Lua binding

[Examples](https://github.com/danog/MadelineProto/tree/master/lua)  

The lua binding makes use of the Lua php extension.

When istantiating the `\danog\MadelineProto\Lua` class, the first parameter provided to the constructor must be the path to the lua script, and the second parameter a logged in instance of MadelineProto.

The class is basically a wrapper for the lua environment, so by setting an attribute you're setting a variable in the Lua environment, by reading an attribute you're reading a variable from the lua environment, and by calling a function you're actually calling a Lua function you declared in the script.

By assigning a callable to an attribute, you're actually assigning a new function in the lua environment that once called, will call the php callable.

Passing lua callables to a parameter of a PHP callable will throw an exception due to a bug in the PHP lua extension that I gotta fix (so passing the usual cb and cb_extra parameters to the td-cli wrappers isn't yet possible).

All MadelineProto wrapper methods (for example upload, download, upload_encrypted, get_self, and others) are imported in the Lua environment, as well as all MTProto wrappers (see the API docs for more info).  

td-cli wrappers are also present: you can use the tdcli_function in lua and pass mtproto updates to the tdcli_update_callback via PHP, they will be automatically converted to/from td objects. Please note that the object conversion is not complete, feel free to contribute to the conversion module in [`src/danog/MadelineProto/Conversion/TD.php`](https://github.com/danog/MadelineProto/raw/master/src/danog/MadelineProto/TL/Conversion/TD.php).

<amp-form method="GET" target="_top" action="https://docs.madelineproto.xyz/docs/SECRET_CHATS.html"><input type="submit" value="Previous section" /></amp-form><amp-form action="https://docs.madelineproto.xyz/docs/PROXY.html" method="GET" target="_top"><input type="submit" value="Next section" /></amp-form>