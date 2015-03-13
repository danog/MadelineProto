# -*- coding: utf-8 -*-
"""
MTProto data serialization and SHA hash test

@author: Anton Grigoryev
@author: Sammy Pfeiffer
"""
from Crypto.Hash import SHA
import io
import mtproto

# byte strings got from
# https://core.telegram.org/mtproto/samples-auth_key - step 4

z = io.BytesIO()
mtproto.serialize_obj(z, 'p_q_inner_data',
                      pq=b"\x17\xED\x48\x94\x1A\x08\xF9\x81",
                      p=b"\x49\x4C\x55\x3B",
                      q=b"\x53\x91\x10\x73",
                      nonce=b"\x3E\x05\x49\x82\x8C\xCA\x27\xE9\x66\xB3\x01\xA4\x8F\xEC\xE2\xFC",
                      server_nonce=b"\xA5\xCF\x4D\x33\xF4\xA1\x1E\xA8\x77\xBA\x4A\xA5\x73\x90\x73\x30",
                      new_nonce=b"\x31\x1C\x85\xDB\x23\x4A\xA2\x64\x0A\xFC\x4A\x76\xA7\x35\xCF\x5B\x1F\x0F\xD6\x8B\xD1\x7F\xA1\x81\xE1\x22\x9A\xD8\x67\xCC\x02\x4D")
x = z.getvalue()
if SHA.new(x).digest() == b'\xDB\x76\x1C\x27\x71\x8A\x23\x05\x04\x4F\x71\xF2\xAD\x95\x16\x29\xD7\x8B\x24\x49':
    print("Test passed successfully")
else:
    print("Test not passed")