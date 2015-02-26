# -*- coding: utf-8 -*-
"""
Created on Tue Sep  2 19:26:15 2014

@author: agrigoryev
"""
from binascii import crc32
import struct
import socket
import re
from datetime import datetime
import sys
import io
import configparser

current_module = sys.modules[__name__]

def vis(bs):
    l = len(bs)
    n = len(bs) // 8
    for i in range(n):
        print(" ".join(["%02X" % b for b in bs[i*8:i*8+8]]))
    print(" ".join(["%02X" % b for b in bs[i*8+8:]])+"\n")


class TlElement:
    def __init__(self, type_string):
        tl_re = re.compile("""([a-z]\w*)           #name
                              \#?([0-9a-f]{8})?\s+ #id
                              (\{.*\}\s+)?         #subtype
                              (.*)\s+              #arguments list
                              =\s+([A-Z]\w*)       #result
                              (\s+(\w+))?;         #subresult""", re.X)
        assert isinstance(type_string, str)
        x = tl_re.match(type_string)
        if x is not None:
            self.name = x.groups()[0]
            if x.groups()[1] is not None:
                self.id = int(x.groups()[1], 16)
            else:
                self.id = crc32(re.sub("(#[0-9a-f]{8})|([;\n{}])", "", type_string).encode())
            self.args = self.get_arg_list(x.groups()[3])
            self.result = x.groups()[4]
        else:
            raise SyntaxError

    @staticmethod
    def get_arg_list(arg_string):
        arg_re = re.compile("([\w0-9]+)?:?([\w0-9]+)(<([\w0-0]+)>)?")
        res = []
        for s in arg_re.findall(arg_string):
            d = {'name': s[0], 'type': s[1], 'subtype': s[3] if s[2] is not None else None}
            res.append(d)
        return res


class TL:
    def __init__(self):
        self.func_dict_id = {}
        self.func_dict_name = {}
        self.obj_dict_id = {}
        self.obj_dict_name = {}

        # Read constructors

        f = open("TL_schema", 'r')
        for line in f:
            if line.startswith("---functions---"):
                break
            try:
                z = TlElement(line)
                self.obj_dict_id[z.id] = z
                self.obj_dict_name[z.result] = z
            except SyntaxError:
                pass

        # Read methods

        for line in f:
            if line.startswith("---functions---"):
                break
            try:
                z = TlElement(line)
                self.func_dict_id[z.id] = z
                self.func_dict_name[z.name] = z
            except SyntaxError:
                pass


    def tl_serialize(self, elem, kwargs):
        assert isinstance(elem, TlElement)
        for arg in elem.args:
            pass
        return struct.pack("<L", )

    def tl_function_generator(elem, session, tl):
        def dummy(**kwargs):
            message = tl_serialize(elem, kwargs)
            session.send_message(message)
            answer = session.recv_message()
            return deserialize(answer, TL)

    def class_generator(self, tl_element):
        class Dummy:
            def __init__(self, bstring):
                assert isinstance(bstring, bytes)
                f = io.BytesIO(bstring)
                dict = self.deserialize(f, type=tl_element.type)

    def serialize(self, type=None, subtype=None):
        pass

    def deserialize(self, string, type=None, subtype=None):

        if isinstance(string, io.BytesIO):
            bytes_io = string
        elif isinstance(string, bytes):
            bytes_io = io.BytesIO(string)
        else:
            raise Exception("Bad input type, use bytes string or BytesIO object")

        # Built-in bare types

        if type == 'int':
            x = struct.unpack('<i', bytes_io.read(4))[0]
        elif type == '#':
            x = struct.unpack('<I', bytes_io.read(4))[0]
        elif type == 'long':
            x = struct.unpack('<q', bytes_io.read(8))[0]
        elif type == 'double':
            x = struct.unpack('<d', bytes_io.read(8))[0]
        elif type == 'int128':
            t = struct.unpack('<16s', bytes_io.read(16))[0]
            x = int.from_bytes(t, 'little')
        elif type == 'int256':
            t = struct.unpack('<32s', bytes_io.read(32))[0]
            x = int.from_bytes(t, 'little')
        elif type == 'bytes':
            l = int.from_bytes(bytes_io.read(1), 'little')
            x = bytes_io.read(l)
            bytes_io.read(-(l+1) % 4)  # skip padding bytes
        elif type == 'string':
            l = int.from_bytes(bytes_io.read(1), 'little')
            assert l <=254
            if l == 254:
                # We have a long string
                long_len = int.from_bytes(bytes_io.read(3), 'little')
                x = bytes_io.read(long_len)
                bytes_io.read(-long_len % 4)  # skip padding bytes
            else:
                # We have a short string
                x = bytes_io.read(l)
                bytes_io.read(-(l+1) % 4)  # skip padding bytes
            assert isinstance(x, bytes)
        elif type == 'vector':
            assert subtype is not None
            count = int.from_bytes(bytes_io.read(4), 'little')
            x = [self.deserialize(bytes_io, type=subtype) for i in range(count)]
        else:
            # Boxed types

            i = struct.unpack('<i', bytes_io.read(4))[0]  # read type ID
            try:
                tl_elem = self.obj_dict_id[i]
            except:
                raise Exception("Could not extract type: %s" % type)
            base_boxed_types = ["Vector", "Int", "Long", "Double", "String", "Int128", "Int256"]
            if tl_elem.result in base_boxed_types:
                x = self.deserialize(bytes_io, type=tl_elem.name, subtype=subtype)

            else:  # other types
                x = {}
                for arg in tl_elem.args:
                    x[arg['name']] = self.deserialize(bytes_io, type=arg['type'], subtype=arg['subtype'])
        return x



class Session:
    def __init__(self, credentials):
        config = configparser.ConfigParser()
        config.read(credentials)
        self.sock = socket.socket()
        self.sock.connect((config['App data']['ip_adress'], config['App data'].getint('port')))
        self.api_id = config['App data'].getint('api_id')
        self.api_hash = config['App data']['api_hash']
        self.auth_key_id = b'\x00\x00\x00\x00\x00\x00\x00\x00'
        self.number = 0
        self.TL = TL()

    def header(self, message):
        return (self.auth_key_id +
                struct.pack('<Q', int(datetime.utcnow().timestamp()*2**32)) +
                struct.pack('<L', len(message)))

    def send_message(self, message):
        print('>>')
        vis(message)
        #self.number += 1
        data = self.header(message) + message
        step1 = struct.pack('<LL', len(data)+12, self.number) + data
        step2 = step1 + struct.pack('<L', crc32(step1))
        self.sock.send(step2)

    def recv_message(self):
        packet_length_data = self.sock.recv(4)
        if len(packet_length_data) > 0:  # if we have smth. in the socket
            packet_length = struct.unpack("<L", packet_length_data)[0]
            packet = self.sock.recv(packet_length - 4)
            self.number = struct.unpack("<L", packet[0:4])[0]
            auth_key_id = struct.unpack("<8s", packet[4:12])[0]
            message_id =  struct.unpack("<8s", packet[12:20])[0]
            message_length = struct.unpack("<I", packet[20:24])[0]
            data = packet[24:24+message_length]
            crc = packet[-4:]
            if crc32(packet_length_data + packet[0:-4]).to_bytes(4,'little') == crc:
                print('<<')
                vis(data)
                return data
