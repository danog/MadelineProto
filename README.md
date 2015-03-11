# About this repo
This is Telegram API for python. 
Main aim is to implement MTProto protocol Telegram API on pure Python (not wrapped CLI)
We'll try to make it work on Python 2 as well as 3.

Detailed description of API and protocol can be found here:
https://core.telegram.org/api
https://core.telegram.org/mtproto

# Structure

- mtproto.py 
- testing.py
- prime.py
- credentials
- rsa.pub

## Credentials
Repo doesn't contain any credentials to connect Telegram servers.
You can get yours from http://my.telegram.org

You should place 2 files in the root of your repo:
- credentials
- rsa.pub

Config example for "credentials" file:

```
[App data]
api_id: 12345
api_hash: 1234567890abcdef1234567890abcdef
ip_address: 112.134.156.178
port: 443
```
rsa.pub contains your RSA key.

## mtproto.py

Contains functions to work with MTproto protocol:
- TL schema parsing
- serializing and deserializing
- manage connections to server
- send and receive messages

## testing.py

testing.py is used to test functionality of modules.
Now it makes steps from https://core.telegram.org/mtproto/samples-auth_key:
- sends PQ request to server
- parses the result
- factorizes PQ
 
## prime.py
prime.py is used in PQ factorization. It has been copied from https://stackoverflow.com/questions/4643647/fast-prime-factorization-module

## TL schema
We use JSON format TL Shema. TL Schema file contains information about objects and methods, it is located in TL_schema.JSON file in the root of repo. It is fully equivalent to JSON TL Schema from
https://core.telegram.org/schema/mtproto-json
