# About this repo
This is Telegram API for python. 
Main aim is to implement MTProto protocol Telegram API on pure Python (not wrapped CLI)
We'll try to make it work on Python 2 as well as 3.

Detailed description of API and protokol can be found here:
https://core.telegram.org/api
https://core.telegram.org/mtproto

Repo doesn't contain any credentials to connect Telegram servers.
You can get your credentials from http://my.telegram.org

You shoud plase in the root of your repo 2 files:
- credentials
- rsa.pub

Config example for "credentials" file:

```
[App data]
api_id: 12345
api_hash: 1234567890abcdef1234567890abcdef
ip_adress: 112.134.156.178
port: 443
```
rsa.pub contains your RSA key.


# testing.py

testing.py is used to test functionality of modules.
Now it makes steps from https://core.telegram.org/mtproto/samples-auth_key:
- sends PQ request to server
- parses the result
- factorizes PQ
