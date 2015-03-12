# -*- coding: utf-8 -*-
import mtproto
import os
import prime
import configparser

config = configparser.ConfigParser()
config.read('credentials')
ip = config['App data']['ip_address']
port = config['App data'].getint('port')

Session = mtproto.Session(ip, port)
x = Session.method_call('req_pq', nonce=os.urandom(16))

PQ = int.from_bytes(x['pq'], 'big')
[p, q] = prime.primefactors(PQ)

print("PQ = %d\np = %d, q = %d" % (PQ, p, q))
