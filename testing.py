# -*- coding: utf-8 -*-
import os
import io
import struct
# Deal with py2 and py3 differences
try: # this only works in py2.7
    import configparser
except ImportError:
    import ConfigParser as configparser
import mtproto



config = configparser.ConfigParser()
# Check if credentials is correctly loaded (when it doesn't read anything it returns [])
if not config.read('credentials'):
    print("File 'credentials' seems to not exist.")
    exit(-1)
ip = config.get('App data', 'ip_address')
port = config.getint('App data', 'port')

Session = mtproto.Session(ip, port)

Session.create_auth_key()

future_salts = Session.method_call('get_future_salts', num=3)
print(future_salts)
