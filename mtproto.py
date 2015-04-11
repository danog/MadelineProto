# -*- coding: utf-8 -*-
"""
Created on Tue Sep  2 19:26:15 2014

@author: Anton Grigoryev
@author: Sammy Pfeiffer
"""
from binascii import crc32 as originalcrc32
from time import time
import io
import os.path
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
    def __init__(self, ip, port, auth_key=None, server_salt=None):
        # creating socket
        self.sock = socket.socket()
        self.sock.connect((ip, port))
        self.number = 0
        self.timedelta = 0
        self.session_id = os.urandom(8)
        self.auth_key = auth_key
        self.auth_key_id = SHA.new(self.auth_key).digest()[-8:] if self.auth_key else None
        self.sock.settimeout(5.0)
        self.MAX_RETRY = 5;
        self.AUTH_MAX_RETRY = 5;

    def __del__(self):
        # closing socket when session object is deleted
        self.sock.close()

    def send_message(self, message_data):
        """
        Forming the message frame and sending message to server
        :param message: byte string to send
        """

        message_id = struct.pack('<Q', int((time()+self.timedelta)*2**30)*4)

        if self.auth_key is None or self.server_salt is None:
            # Unencrypted data send
            message = (b'\x00\x00\x00\x00\x00\x00\x00\x00' +
                       message_id +
                       struct.pack('<I', len(message_data)) +
                       message_data)
        else:
            # Encrypted data send
            encrypted_data = (self.server_salt +
                              self.session_id +
                              message_id +
                              struct.pack('<II', self.number, len(message_data)) +
                              message_data)
            message_key = SHA.new(encrypted_data).digest()[-16:]
            padding = os.urandom((-len(encrypted_data)) % 16)
            print(len(encrypted_data+padding))
            aes_key, aes_iv = self.aes_calculate(message_key)

            message = (self.auth_key_id + message_key +
                       crypt.ige_encrypt(encrypted_data+padding, aes_key, aes_iv))

        step1 = struct.pack('<II', len(message)+12, self.number) + message
        step2 = step1 + struct.pack('<I', crc32(step1))
        self.sock.send(step2)
        self.number += 1

    def recv_message(self):
        """
        Reading socket and receiving message from server. Check the CRC32.
        """
        packet_length_data = self.sock.recv(4)  # reads how many bytes to read

        if len(packet_length_data) < 4:
            raise Exception("Nothing in the socket!")
        packet_length = struct.unpack("<I", packet_length_data)[0]
        packet = self.sock.recv(packet_length - 4)  # read the rest of bytes from socket

        # check the CRC32
        if not crc32(packet_length_data + packet[0:-4]) == struct.unpack('<I', packet[-4:])[0]:
            raise Exception("CRC32 was not correct!")
        x = struct.unpack("<I", packet[:4])
        auth_key_id = packet[4:12]
        if auth_key_id == b'\x00\x00\x00\x00\x00\x00\x00\x00':
            # No encryption - Plain text
            (message_id, message_length) = struct.unpack("<8sI", packet[12:24])
            data = packet[24:24+message_length]
        elif auth_key_id == self.auth_key_id:
            message_key = packet[12:28]
            encrypted_data = packet[28:-4]
            aes_key, aes_iv = self.aes_calculate(message_key, direction="from server")
            decrypted_data = crypt.ige_decrypt(encrypted_data, aes_key, aes_iv)
            assert decrypted_data[0:8] == self.server_salt
            assert decrypted_data[8:16] == self.session_id
            message_id = decrypted_data[16:24]
            seq_no = struct.unpack("<I", decrypted_data[24:28])[0]
            message_data_length = struct.unpack("<I", decrypted_data[28:32])[0]
            data = decrypted_data[32:32+message_data_length]
        else:
            raise Exception("Got unknown auth_key id")
        return data

    def method_call(self, method, **kwargs):
        for i in range(1, self.MAX_RETRY):
            try:
                self.send_message(TL.serialize_method(method, **kwargs))
                server_answer = self.recv_message()
            except socket.timeout:
                print("Retry call method")
                continue
            return TL.deserialize(io.BytesIO(server_answer))

    def create_auth_key(self):

        nonce = os.urandom(16)
        print("Requesting pq")

        ResPQ = self.method_call('req_pq', nonce=nonce)
        server_nonce = ResPQ['server_nonce']

        # TODO: selecting RSA public key based on this fingerprint
        public_key_fingerprint = ResPQ['server_public_key_fingerprints'][0]

        pq_bytes = ResPQ['pq']
        pq = bytes_to_long(pq_bytes)

        [p, q] = prime.primefactors(pq)
        if p > q: (p, q) = (q, p)
        assert p*q == pq and p < q

        print("Factorization %d = %d * %d" % (pq, p, q))
        p_bytes = long_to_bytes(p)
        q_bytes = long_to_bytes(q)
        f = open(os.path.join(os.path.dirname(__file__), "rsa.pub"))
        key = RSA.importKey(f.read())

        new_nonce = os.urandom(32)
        data = TL.serialize_obj('p_q_inner_data',
                                pq=pq_bytes,
                                p=p_bytes,
                                q=q_bytes,
                                nonce=nonce,
                                server_nonce=server_nonce,
                                new_nonce=new_nonce)

        sha_digest = SHA.new(data).digest()
        random_bytes = os.urandom(255-len(data)-len(sha_digest))
        to_encrypt = sha_digest + data + random_bytes
        encrypted_data = key.encrypt(to_encrypt, 0)[0]

        print("Starting Diffie Hellman key exchange")
        server_dh_params = self.method_call('req_DH_params',
                                            nonce=nonce,
                                            server_nonce=server_nonce,
                                            p=p_bytes,
                                            q=q_bytes,
                                            public_key_fingerprint=public_key_fingerprint,
                                            encrypted_data=encrypted_data)
        assert nonce == server_dh_params['nonce']
        assert server_nonce == server_dh_params['server_nonce']

        encrypted_answer = server_dh_params['encrypted_answer']

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

        data = TL.serialize_obj('client_DH_inner_data',
                                nonce=nonce,
                                server_nonce=server_nonce,
                                retry_id=retry_id,
                                g_b=g_b_str)
        data_with_sha = SHA.new(data).digest()+data
        data_with_sha_padded = data_with_sha + os.urandom(-len(data_with_sha) % 16)
        encrypted_data = crypt.ige_encrypt(data_with_sha_padded, tmp_aes_key, tmp_aes_iv)

        for i in range(1, self.AUTH_MAX_RETRY): # retry when dh_gen_retry or dh_gen_fail
            Set_client_DH_params_answer = self.method_call('set_client_DH_params',
                                                       nonce=nonce,
                                                       server_nonce=server_nonce,
                                                       encrypted_data=encrypted_data)

            # print Set_client_DH_params_answer
            auth_key = pow(g_a, b, dh_prime)
            auth_key_str = long_to_bytes(auth_key)
            auth_key_sha = SHA.new(auth_key_str).digest()
            auth_key_aux_hash = auth_key_sha[:8]

            new_nonce_hash1 = SHA.new(new_nonce+b'\x01'+auth_key_aux_hash).digest()[-16:]
            new_nonce_hash2 = SHA.new(new_nonce+b'\x02'+auth_key_aux_hash).digest()[-16:]
            new_nonce_hash3 = SHA.new(new_nonce+b'\x03'+auth_key_aux_hash).digest()[-16:]

            assert Set_client_DH_params_answer['nonce'] == nonce
            assert Set_client_DH_params_answer['server_nonce'] == server_nonce

            if Set_client_DH_params_answer.name == 'dh_gen_ok':
                assert Set_client_DH_params_answer['new_nonce_hash1'] == new_nonce_hash1
                print("Diffie Hellman key exchange processed successfully")

                self.server_salt = strxor(new_nonce[0:8], server_nonce[0:8])
                self.auth_key = auth_key_str
                self.auth_key_id = auth_key_sha[-8:]
                print("Auth key generated")
                return "Auth Ok"
            elif Set_client_DH_params_answer.name == 'dh_gen_retry':
                assert Set_client_DH_params_answer['new_nonce_hash2'] == new_nonce_hash2
                print ("Retry Auth")
            elif Set_client_DH_params_answer.name == 'dh_gen_fail':
                assert Set_client_DH_params_answer['new_nonce_hash3'] == new_nonce_hash3
                print("Auth Failed")
                raise Exception("Auth Failed")
            else: raise Exception("Response Error")

    def aes_calculate(self, msg_key, direction="to server"):
        x = 0 if direction == "to server" else 8
        sha1_a = SHA.new(msg_key + self.auth_key[x:x+32]).digest()
        sha1_b = SHA.new(self.auth_key[x+32:x+48] + msg_key + self.auth_key[48+x:64+x]).digest()
        sha1_c = SHA.new(self.auth_key[x+64:x+96] + msg_key).digest()
        sha1_d = SHA.new(msg_key + self.auth_key[x+96:x+128]).digest()
        aes_key = sha1_a[0:8] + sha1_b[8:20] + sha1_c[4:16]
        aes_iv = sha1_a[8:20] + sha1_b[0:8] + sha1_c[16:20] + sha1_d[0:8]
        return aes_key, aes_iv
