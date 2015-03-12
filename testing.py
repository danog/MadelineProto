# -*- coding: utf-8 -*-
import mtproto
import os, io
import prime
import configparser
from Crypto.Hash import SHA
from Crypto.PublicKey import RSA

config = configparser.ConfigParser()
config.read('credentials')
ip = config['App data']['ip_address']
port = config['App data'].getint('port')

Session = mtproto.Session(ip, port)
client_nonce = os.urandom(16)
x = Session.method_call('req_pq', nonce=client_nonce)

server_nonce = x['server_nonce']
public_key_fingerprint = x['server_public_key_fingerprints'][0]
PQ_bytes = x['pq']

PQ = int.from_bytes(PQ_bytes, 'big')
[p, q] = prime.primefactors(PQ)
if p > q: (p, q) = (q, p) # swap values in way p<q

print("PQ = %d\np = %d, q = %d" % (PQ, p, q))


P_bytes = int.to_bytes(p, p.bit_length()//8+1, 'big')
Q_bytes = int.to_bytes(q, q.bit_length()//8+1, 'big')

f = open('rsa.pub', 'r')
key = RSA.importKey(f.read())

z= io.BytesIO()

new_nonce = os.urandom(32)

mtproto.serialize_obj(z, 'p_q_inner_data',
                      pq=PQ_bytes,
                      p=P_bytes,
                      q=Q_bytes,
                      nonce=client_nonce,
                      server_nonce=server_nonce,
                      new_nonce=new_nonce)
data = z.getvalue()

sha_digest = SHA.new(data).digest()
random_bytes = os.urandom(255-len(data)-len(sha_digest))
to_encrypt = sha_digest + data + random_bytes
encrypted_data = key.encrypt(to_encrypt, 0)[0]

z = Session.method_call('req_DH_params',
                        nonce=client_nonce,
                        server_nonce=server_nonce,
                        p=P_bytes,
                        q=Q_bytes,
                        public_key_fingerprint=public_key_fingerprint,
                        encrypted_data=encrypted_data)

print(z)