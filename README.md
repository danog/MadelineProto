[![Join the chat at https://gitter.im/griganton/telepy](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/griganton/telepy?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

# About this repo
This is Telegram API for python. 
Main aim is to implement MTProto protocol Telegram API on pure Python (not wrapped CLI)

### Plan
- [ ] Make it work on Python 2 as well as 3.
- [ ] Follow up the same functionality of CLI API.
  - [x] Serialize/Deserialize
  - [x] Send and receive PQ authorization with server  [[doc]](https://core.telegram.org/mtproto/samples-auth_key)
  - [ ] Send and receive service messages with server like logging in to server [[doc]](https://core.telegram.org/mtproto/service_messages)

### Useful start points to join
Detailed description of API and protocol can be found here:
* https://core.telegram.org/api
* https://core.telegram.org/mtproto

API registration is needed to be done by yourself at http://my.telegram.org
Follow Structure - Credentials for provide it your API information.

# Structure

- tests 
 - Serialization and SHA.py
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
