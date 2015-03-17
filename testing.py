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

i = 0
while True:
    f = Session.method_call('ping', ping_id=i)
    print(f)
    i += 1