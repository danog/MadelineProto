# -*- coding: utf-8 -*-
"""
Created on Mon Sep  1 16:24:47 2014

@author: agrigoryev

"""

#!/usr/bin/env python
# -*- coding: utf-8 -*-

import socket
import mtproto
import os
import time
import math
import prime
import io


Session = mtproto.Session('credentials')
nonce = os.urandom(16)
tosend = b'\x78\x97\x46\x60' + nonce
Session.send_message(tosend)
z = Session.recv_message()
TL=mtproto.TL()
x = TL.deserialize(io.BytesIO(z))

PQ = int.from_bytes(x['pq'], 'big')
[p, q] = prime.primefactors(PQ)

print("PQ = %d\np = %d, q = %d" % (PQ, p, q))
#sock = socket.socket()
# sock.connect(("149.154.167.40", 443))
# nonce = os.urandom(16)
# tosend = b'\x78\x97\x46\x60' + nonce
# good=(b'\x00\x00\x00\x00\x00\x00\x00\x00'
#      #+b'\x00\x00\x00\x00\x0f\x35\xe8\x51
#      +int(time.time()*2**32).to_bytes(8, 'little')
#      +len(tosend).to_bytes(4, 'little')
#      +tosend)
#      #+b'\x14\x00\x00\x00'
#      #+b'\x78\x97\x46\x60'
#      #+b'\x15\x3e\xa9\xbe\xc5\x4f\xfc\x16'
#      #+b'\x66\x23\x4a\xd0\x31\xc0\xf1\x3d')
# mtproto.sendpacket(sock, good, 0)
# (number, data) = mtproto.recvpacket(sock)
# auth_key_id = data[0:8]
# message_id =  data[8:16]
# message_length = int.from_bytes(data[16:20], 'little')
# resPQ = data[20:24]
# nonce2 = data[24:40]
# server_nonce = data[40:56]
# pq = data[56:68]
# fingerprint_count = int.from_bytes(data[72:76], 'little')
# fingerprints = []
# for i in range(fingerprint_count):
#     fingerprints.append(data[76+i*8:76+8*(i+1)])
# PQ = int.from_bytes(pq[1:9],'big')
# server_public_key = fingerprints[0]
# [p, q] = prime.primefactors(PQ)
# #(p, q)=factorize(PQ)
# def num_to_string(number, endianness):
#     number_bytes = math.ceil(number.bit_length() / 8)
#     return (number_bytes.to_bytes(3, endianness) +
#             number.to_bytes(number_bytes, endianness) +
#             b'\x00' * ((number_bytes + 3) %4) )
# new_nonce = os.urandom(32)
# data = (b'\x83\xc9\x5a\xec' +  #(p_q_inner_data)
#         num_to_string(PQ, 'big') +
#         num_to_string(p, 'big') +
#         num_to_string(q, 'big') +
#         nonce +
#         server_nonce +
#         new_nonce )
# sock.close()
# #34 00 00 00 - len
# #00 00 00 00- num in the session
# #00 00 00 00 00 00 00 00
# #00 00 00 00 0f 35 e8 51 - message id (unixtime << 32)
# #14 00 00 00 - message len
# #78 97 46 60 - function
# #15 3e a9 be c5 4f fc 16 66 23 4a d0 31 c0 f1 3d - nonce (random number 16 bytes)
# #dd fe f0 07 - crc32
