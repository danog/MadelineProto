# -*- coding: utf-8 -*-
import os
import io
import struct
# Deal with py2 and py3 differences
try: # this only works in py2.7
    import configparser
except ImportError:
    import ConfigParser as configparser
from Crypto.Hash import SHA
from Crypto.PublicKey import RSA

# local modules
import crypt
import mtproto
import prime


config = configparser.ConfigParser()
# Check if credentials is correctly loaded (when it doesn't read anything it returns [])
if not config.read('credentials'):
    print("File 'credentials' seems to not exist.")
    exit(-1)
ip = config.get('App data', 'ip_address')
port = config.getint('App data', 'port')

Session = mtproto.Session(ip, port)
client_nonce = os.urandom(16)
x = Session.method_call('req_pq', nonce=client_nonce)

server_nonce = x['server_nonce']
public_key_fingerprint = x['server_public_key_fingerprints'][0]
PQ_bytes = x['pq']

# doing len(PQ_bytes) I saw it was 8 bytes, so we unpack with Q
# as in the docs: https://docs.python.org/2/library/struct.html
PQ = struct.unpack('>q', PQ_bytes)[0]
[p, q] = prime.primefactors(PQ)
if p > q: (p, q) = (q, p)
assert p*q == PQ and p < q

print("PQ = %d\np = %d, q = %d" % (PQ, p, q))

P_bytes = struct.pack('>i', p)
Q_bytes = struct.pack('>i', q)
# print("p.bit_length()//8+1: " + str(p.bit_length()//8+1)) # 4
# print("q.bit_length()//8+1: " + str(q.bit_length()//8+1)) # 4
# P_bytes = int.to_bytes(p, p.bit_length()//8+1, 'big')
# Q_bytes = int.to_bytes(q, q.bit_length()//8+1, 'big')

f = open('rsa.pub', 'r')
key = RSA.importKey(f.read())

z = io.BytesIO()

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
                        nonce=client_nonce, # 16 bytes
                        server_nonce=server_nonce,
                        p=P_bytes,
                        q=Q_bytes,
                        public_key_fingerprint=public_key_fingerprint,
                        encrypted_data=encrypted_data)

encrypted_answer = z['encrypted_answer']
print("encrypted_answer:")
print(encrypted_answer.__repr__())
tmp_aes_key = SHA.new(new_nonce + server_nonce).digest() + SHA.new(server_nonce + new_nonce).digest()[0:12]
tmp_aes_iv = SHA.new(server_nonce + new_nonce).digest()[12:20] + SHA.new(new_nonce + new_nonce).digest() + new_nonce[0:4]

print("\ntmp_aes_key:")
mtproto.vis(tmp_aes_key)
print(tmp_aes_key.__repr__())

print("\ntmp_aes_iv:")
mtproto.vis(tmp_aes_iv)
print(tmp_aes_iv.__repr__())

decrypted_answer = crypt.ige(encrypted_answer, tmp_aes_key, tmp_aes_iv)
print("decrypted_answer is:")
print(decrypted_answer.__repr__())
mtproto.vis(decrypted_answer[20:]) # To start off BA0D89 ...

