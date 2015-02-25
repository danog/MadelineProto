# -*- coding: utf-8 -*-
"""
Created on Tue Sep  2 19:26:15 2014

@author: agrigoryev
"""
import binascii
import struct
import socket
import re
from datetime import datetime
import sys

current_module = sys.modules[__name__]

def vis(bs):
    l = len(bs)
    n = len(bs) // 8
    for i in range(n):
        print(" ".join(["%02X" % b for b in bs[i*8:i*8+8]]))
    print(" ".join(["%02X" % b for b in bs[i*8+8:]]))


class TlElement:
    def __init__(self, type_string):
        tl_re = re.compile("""([a-z]\w*)        #name
                              \#([0-9a-f]{8})   #id
                              \s+(.*)\s+        #arguments list
                              =\s+([A-Z]\w*);   #result""", re.X)
        assert isinstance(type_string, str)
        x = tl_re.match(type_string)
        if x is not None:
            self.name = x.groups()[0]
            self.id = x.groups()[1]
            self.args = self.get_arg_list(x.groups()[2])
            self.result = x.groups()[3]
        else:
            raise SyntaxError

    @staticmethod
    def get_arg_list(arg_string):
        arg_re = re.compile("(\w+):(\w+)(<(\w+)>)?")
        res = []
        for s in arg_re.findall(arg_string):
            d = {'name': s[0], 'type': s[1]}
            if s[2]:
                d['subtype'] = s[3]
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
                self.obj_dict_name[z.name] = z
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


class Session:
    def __init__(self, IP_adress, port):
        self.sock = socket.socket()
        self.sock.connect((IP_adress, port))
        self.auth_key_id = b'\x00\x00\x00\x00\x00\x00\x00\x00'
        self.number = 0
        self.TL = TL()

    def header(self, message):
        return (self.auth_key_id +
                struct.pack('<Q', int(datetime.utcnow().timestamp()*2**32)) +
                struct.pack('<L', len(message)))

    def send_message(self, message):
        vis(message)
        print()
        #self.number += 1
        data = self.header(message) + message
        step1 = struct.pack('<LL', len(data)+12, self.number) + data
        step2 = step1 + struct.pack('<L', binascii.crc32(step1))
        vis(step2)
        self.sock.send(step2)


    def recv_message(self):
        packet_length_data = self.sock.recv(4)
        if len(packet_length_data) > 0:  # if we have smth. in the socket
            packet_length = struct.unpack("<L", packet_length_data)[0]
            packet = self.sock.recv(packet_length - 4)
            self.number = struct.unpack("<L", packet[0:4])[0]
            data = packet[4:-4]
            crc = packet[-4:]
            if binascii.crc32(packet_length_data + packet[0:-4]).to_bytes(4,'little') == crc:
                return data
