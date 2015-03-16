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
from Crypto.Util.strxor import strxor

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
# TODO: selecting RSA public key based on this fingerprint
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

server_DH_params = Session.method_call('req_DH_params',
                        nonce=client_nonce, # 16 bytes
                        server_nonce=server_nonce,
                        p=P_bytes,
                        q=Q_bytes,
                        public_key_fingerprint=public_key_fingerprint,
                        encrypted_data=encrypted_data)
assert client_nonce == server_DH_params['nonce']
assert server_nonce == server_DH_params['server_nonce']

encrypted_answer = server_DH_params['encrypted_answer']

tmp_aes_key = SHA.new(new_nonce + server_nonce).digest() + SHA.new(server_nonce + new_nonce).digest()[0:12]
tmp_aes_iv = SHA.new(server_nonce + new_nonce).digest()[12:20] + SHA.new(new_nonce + new_nonce).digest() + new_nonce[0:4]

crypter = crypt.IGE(tmp_aes_key, tmp_aes_iv)

answer_with_hash = crypter.decrypt(encrypted_answer)

answer_hash = answer_with_hash[:20]
answer = answer_with_hash[20:]
mtproto.vis(answer) # To start off BA0D89 ...

server_DH_inner_data = mtproto.deserialize(io.BytesIO(answer))
assert client_nonce == server_DH_inner_data['nonce']
assert server_nonce == server_DH_inner_data['server_nonce']
dh_prime_str = server_DH_inner_data['dh_prime']
g = server_DH_inner_data['g']
g_a_str = server_DH_inner_data['g_a']
server_time = server_DH_inner_data['server_time']


dh_prime = int.from_bytes(dh_prime_str,'big')
g_a = int.from_bytes(g_a_str,'big')
print(dh_prime)
print(g)
print(g_a)
print(prime.isprime(dh_prime))

b_str = os.urandom(256)
b = int.from_bytes(b_str,'big')
g_b = pow(g,b,dh_prime)

g_b_str = int.to_bytes(g_b, g_b.bit_length() // 8 + 1, 'big')


retry_id = 0
z = io.BytesIO()
mtproto.serialize_obj(z, 'client_DH_inner_data',
                      nonce=client_nonce,
                      server_nonce=server_nonce,
                      retry_id=retry_id,
                      g_b=g_b_str)
data = z.getvalue()
data_with_sha = SHA.new(data).digest()+data
data_with_sha_padded = data_with_sha + os.urandom(-len(data_with_sha) % 16)
encrypted_data = crypter.encrypt(data_with_sha_padded)

Set_client_DH_params_answer = Session.method_call('set_client_DH_params',
                                                   nonce=client_nonce,
                                                   server_nonce=server_nonce,
                                                   encrypted_data=encrypted_data)
print(encrypted_data)
print(Set_client_DH_params_answer)

auth_key = pow(g_a, b, dh_prime)
auth_key_str = int.to_bytes(auth_key, auth_key.bit_length() // 8 + 1, 'big')
print("auth_key = %d" % auth_key)

auth_key_sha = SHA.new(auth_key_str).digest()
auth_key_hash = auth_key_sha[-8:]
auth_key_aux_hash = auth_key_sha[:8]

new_nonce_hash1 = SHA.new(new_nonce+b'\x01'+auth_key_aux_hash).digest()[-16:]
new_nonce_hash2 = SHA.new(new_nonce+b'\x02'+auth_key_aux_hash).digest()[-16:]
new_nonce_hash3 = SHA.new(new_nonce+b'\x03'+auth_key_aux_hash).digest()[-16:]

assert Set_client_DH_params_answer['nonce'] == client_nonce
assert Set_client_DH_params_answer['server_nonce'] == server_nonce
assert Set_client_DH_params_answer['new_nonce_hash1'] == new_nonce_hash1

Session.__del__()

server_salt = strxor(new_nonce[0:8], server_nonce[0:8])
mtproto.vis(server_salt)