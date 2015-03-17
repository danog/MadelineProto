# -*- coding: utf-8 -*-
"""
Created on Tue Sep  2 19:26:15 2014

@author: Anton Grigoryev
@author: Sammy Pfeiffer
"""
from binascii import crc32 as originalcrc32
import numbers
from datetime import datetime
from time import time
import io
import os.path
import json
import socket
import struct

# pycrypto module
from Crypto.Hash import SHA
from Crypto.PublicKey import RSA
from Crypto.Util.strxor import strxor
from Crypto.Util.number import long_to_bytes, bytes_to_long

# local modules
import crypt
import prime
import TL

def crc32(data):
    return originalcrc32(data) & 0xffffffff

def vis(bs):
    """
    Function to visualize byte streams. Split into bytes, print to console.
    :param bs: BYTE STRING
    """
    bs = bytearray(bs)
    symbols_in_one_line = 8
    n = len(bs) // symbols_in_one_line
    i = 0
    for i in range(n):
        print(str(i*symbols_in_one_line)+" | "+" ".join(["%02X" % b for b in bs[i*symbols_in_one_line:(i+1)*symbols_in_one_line]])) # for every 8 symbols line
    if not len(bs) % symbols_in_one_line == 0:
        print(str((i+1)*symbols_in_one_line)+" | "+" ".join(["%02X" % b for b in bs[(i+1)*symbols_in_one_line:]])+"\n") # for last line


class Session:
    """ Manages TCP Transport. encryption and message frames """
    def __init__(self, ip, port, auth_key=None):
        # creating socket
        self.sock = socket.socket()
        self.sock.connect((ip, port))
        self.number = 0

        if auth_key is None:
            self.create_auth_key()
        else:
            self.auth_key = auth_key

    def __del__(self):
        # closing socket when session object is deleted
        self.sock.close()

    @staticmethod
    def header_unencrypted(message):
        """
        Creating header for the unencrypted message:
        :param message: byte string to send
        """
        # Basic instructions: https://core.telegram.org/mtproto/description#unencrypted-message

        # Message id: https://core.telegram.org/mtproto/description#message-identifier-msg-id
        # http://stackoverflow.com/questions/8777753/converting-datetime-date-to-utc-timestamp-in-python
        # to make it work in py2 and py3 (py3 has the timestamp() method but py2 doesnt)
        #curr_timestamp = (datetime.utcfromtimestamp(time()) - datetime(1970, 1, 1)).total_seconds()

        msg_id = int(time()*2**30)*4

        #msg_id = int(datetime.utcnow().timestamp()*2**30)*4

        return (b'\x00\x00\x00\x00\x00\x00\x00\x00' +
                struct.pack('<Q', msg_id) +
                struct.pack('<I', len(message)))

    # TCP Transport

    # Instructions may be found here: https://core.telegram.org/mtproto#tcp-transport
    # If a payload (packet) needs to be transmitted from server to client or from client to server,
    # it is encapsulated as follows: 4 length bytes are added at the front (to include the length,
    # the sequence number, and CRC32; always divisible by 4) and 4 bytes with the packet sequence number
    # within this TCP connection (the first packet sent is numbered 0, the next one 1, etc.),
    # and 4 CRC32 bytes at the end (length, sequence number, and payload together).

    def send_message(self, message):
        """
        Forming the message frame and sending message to server
        :param message: byte string to send
        """
        data = self.header_unencrypted(message) + message
        step1 = struct.pack('<II', len(data)+12, self.number) + data
        step2 = step1 + struct.pack('<I', crc32(step1))
        self.sock.send(step2)
        self.number += 1
        # Sending message visualisation to console
        #        print('>>')
        #        vis(step2)

    def recv_message(self):
        """
        Reading socket and receiving message from server. Check the CRC32.
        """
        packet_length_data = self.sock.recv(4)  # reads how many bytes to read

        if len(packet_length_data) > 0:  # if we have smth. in the socket
            packet_length = struct.unpack("<I", packet_length_data)[0]
            packet = self.sock.recv(packet_length - 4)  # read the rest of bytes from socket
            (x, auth_key_id, message_id, message_length)= struct.unpack("<I8s8sI", packet[0:24])
            data = packet[24:24+message_length]
            crc = packet[-4:]
            # Checking the CRC32 correctness of received data
            if crc32(packet_length_data + packet[0:-4]) == struct.unpack('<I', crc)[0]:
                return data
            else:
                raise Exception("CRC32 was not correct!")
        else:
            raise Exception("Nothing in the socket!")

    def method_call(self, method, **kwargs):
        z=io.BytesIO()
        TL.serialize_method(z, method, **kwargs)
        # z.getvalue() on py2.7 returns str, which means bytes
        # on py3.4 returns bytes
        # bytearray is closer to the same data type to be shared
        z_val = bytearray(z.getvalue())
        # print("z_val: " + z_val.__repr__())
        # print("z_val type: " + str(type(z_val)))
        # print("len of z_val: " + str(len(z_val)))
        self.send_message(z_val)
        server_answer = self.recv_message()
        return TL.deserialize(io.BytesIO(server_answer))

    def create_auth_key(self):

        nonce = os.urandom(16)
        print("Requesting pq")

        ResPQ = self.method_call('req_pq', nonce=nonce)
        server_nonce = ResPQ['server_nonce']

        # TODO: selecting RSA public key based on this fingerprint
        public_key_fingerprint = ResPQ['server_public_key_fingerprints'][0]

        pq_bytes = ResPQ['pq']

        # TODO: from_bytes here
        pq = struct.unpack('>q', pq_bytes)[0]
        [p, q] = prime.primefactors(pq)
        if p > q: (p, q) = (q, p)
        assert p*q == pq and p < q

        print("Factorization %d = %d * %d" % (pq, p, q))
        p_bytes = long_to_bytes(p)
        q_bytes = long_to_bytes(q)
        f = open(os.path.join(os.path.dirname(__file__), "rsa.pub"))
        key = RSA.importKey(f.read())

        z = io.BytesIO()

        new_nonce = os.urandom(32)

        TL.serialize_obj(z, 'p_q_inner_data',
                         pq=pq_bytes,
                         p=p_bytes,
                         q=q_bytes,
                         nonce=nonce,
                         server_nonce=server_nonce,
                         new_nonce=new_nonce)
        data = z.getvalue()

        sha_digest = SHA.new(data).digest()
        random_bytes = os.urandom(255-len(data)-len(sha_digest))
        to_encrypt = sha_digest + data + random_bytes
        encrypted_data = key.encrypt(to_encrypt, 0)[0]

        print("Starting Diffie Hellman key exchange")
        server_DH_params = self.method_call('req_DH_params',
                                            nonce=nonce, # 16 bytes
                                            server_nonce=server_nonce,
                                            p=p_bytes,
                                            q=q_bytes,
                                            public_key_fingerprint=public_key_fingerprint,
                                            encrypted_data=encrypted_data)
        assert nonce == server_DH_params['nonce']
        assert server_nonce == server_DH_params['server_nonce']

        encrypted_answer = server_DH_params['encrypted_answer']

        tmp_aes_key = SHA.new(new_nonce + server_nonce).digest() + SHA.new(server_nonce + new_nonce).digest()[0:12]
        tmp_aes_iv = SHA.new(server_nonce + new_nonce).digest()[12:20] + SHA.new(new_nonce + new_nonce).digest() + new_nonce[0:4]

        answer_with_hash = crypt.ige_decrypt(encrypted_answer, tmp_aes_key, tmp_aes_iv)

        answer_hash = answer_with_hash[:20]
        answer = answer_with_hash[20:]
        # TODO: SHA hash assertion here

        server_DH_inner_data = TL.deserialize(io.BytesIO(answer))
        assert nonce == server_DH_inner_data['nonce']
        assert server_nonce == server_DH_inner_data['server_nonce']
        dh_prime_str = server_DH_inner_data['dh_prime']
        g = server_DH_inner_data['g']
        g_a_str = server_DH_inner_data['g_a']
        server_time = server_DH_inner_data['server_time']
        self.timedelta = server_time - time()
        print("Server-client time delta = %.1f s" % self.timedelta)

        dh_prime = bytes_to_long(dh_prime_str)
        g_a = bytes_to_long(g_a_str)
        assert prime.isprime(dh_prime)
        retry_id = 0
        b_str = os.urandom(256)
        b = bytes_to_long(b_str)
        g_b = pow(g, b, dh_prime)

        g_b_str = long_to_bytes(g_b)

        z = io.BytesIO()
        TL.serialize_obj(z, 'client_DH_inner_data',
                              nonce=nonce,
                              server_nonce=server_nonce,
                              retry_id=retry_id,
                              g_b=g_b_str)
        data = z.getvalue()
        data_with_sha = SHA.new(data).digest()+data
        data_with_sha_padded = data_with_sha + os.urandom(-len(data_with_sha) % 16)
        encrypted_data = crypt.ige_encrypt(data_with_sha_padded, tmp_aes_key, tmp_aes_iv)

        Set_client_DH_params_answer = self.method_call('set_client_DH_params',
                                                           nonce=nonce,
                                                           server_nonce=server_nonce,
                                                           encrypted_data=encrypted_data)

        auth_key = pow(g_a, b, dh_prime)
        auth_key_str = long_to_bytes(auth_key)
        auth_key_sha = SHA.new(auth_key_str).digest()
        auth_key_hash = auth_key_sha[-8:]
        auth_key_aux_hash = auth_key_sha[:8]

        new_nonce_hash1 = SHA.new(new_nonce+b'\x01'+auth_key_aux_hash).digest()[-16:]
        new_nonce_hash2 = SHA.new(new_nonce+b'\x02'+auth_key_aux_hash).digest()[-16:]
        new_nonce_hash3 = SHA.new(new_nonce+b'\x03'+auth_key_aux_hash).digest()[-16:]

        assert Set_client_DH_params_answer['nonce'] == nonce
        assert Set_client_DH_params_answer['server_nonce'] == server_nonce
        assert Set_client_DH_params_answer['new_nonce_hash1'] == new_nonce_hash1
        print("Diffie Hellman key exchange processed successfully")

        self.server_salt = strxor(new_nonce[0:8], server_nonce[0:8])
        self.auth_key = auth_key_str
        print("Auth key generated")

    def aes_calculate(self, msg_key, direction="to server"):
        x = 0 if direction == "to server" else 8
        sha1_a = SHA.new(msg_key + self.auth_key[x:x+32]).digest()
        sha1_b = SHA.new(self.auth_key[x+32:x+48] + msg_key + self.auth_key[48+x:64+x]).digest()
        sha1_c = SHA.new(self.auth_key[x+64:x+96] + msg_key).digest()
        sha1_d = SHA.new(msg_key + self.auth_key[x+96:x+128]).digest()
        aes_key = sha1_a[0:8] + sha1_b[8:20] + sha1_c[4:16]
        aes_iv = sha1_a[8:20] + sha1_b[0:8] + sha1_c[16:20] + sha1_d[0:8]
        return aes_key, aes_iv
