__author__ = 'sam'

from ige import ige

# AES 256 IGE is using AES ECB internally, it implies (extract from PyCrypto.cipher.AES):
#   key : byte string
#     The secret key to use in the symmetric cipher.
#     It must be 16 (*AES-128*), 24 (*AES-192*), or 32 (*AES-256*) bytes long.
#   IV : byte string
#     The initialization vector to use for encryption or decryption.
#
#     It is ignored for `MODE_ECB` and `MODE_CTR`.
#
#     For all other modes, it must be `block_size` bytes longs.

# message must be a multiple of 16 in size
msg_to_encrypt = "This is a secret message"
padding_needed = 16 - len(msg_to_encrypt) % 16
msg_to_encrypt_padded = msg_to_encrypt + b'0' * padding_needed
print("Encrypting: '" + str(msg_to_encrypt) + "'")
print("With padding: '" + str(msg_to_encrypt_padded) + "'")
# 32 bytes long key
aes_key = b'12345678901234567890123456789012'
print("With key for AES 256 ECB: '" + str(aes_key) + "'")
# Initialization Vector must be 32 bytes
aes_iv =  b'01234567890123456789012345678901'
print("And initialization vector: '" + str(aes_iv) + "'")
encrypted_msg = ige(msg_to_encrypt_padded, aes_key, aes_iv, operation="encrypt")
print("\nEncrypted msg: '" + str(encrypted_msg) + "'")
print("In hex: " + encrypted_msg.__repr__())
decrypted_msg = ige(encrypted_msg, aes_key, aes_iv, operation="decrypt")
print("\nDecrypted msg: '" + str(decrypted_msg) + "'")
print("In hex: " + decrypted_msg.__repr__())

if msg_to_encrypt_padded == decrypted_msg:
    print("Encrypt + Decrypt process, completed succesfully.")

# Let's test incorrect inputs
print("\n\nTesting incorrect inputs:")
# Message with length not multiple of 16
msg_not_multiple_of_16 = "6bytes"
print("Trying to encrypt: '" + msg_not_multiple_of_16 +
      "' of size: " + str(len(msg_not_multiple_of_16)))
try:
    encrypted_msg = ige(msg_not_multiple_of_16, aes_key, aes_iv, operation="encrypt")
except ValueError as ve:
    print("  Correctly got ValueError: '" + str(ve) + "'")

# key not being 32 bytes
aes_key_not_32_bytes = b'0123456789'
print("Trying to use key: '" + str(aes_key_not_32_bytes) + "'")
try:
    encrypted_msg = ige(msg_to_encrypt_padded, aes_key_not_32_bytes, aes_iv, operation="encrypt")
except ValueError as ve:
    print("  Correctly got ValueError: '" + str(ve) + "'")

# iv not being 32 bytes
iv_key_not_32_bytes = b'0123456789'
print("Trying to use iv: '" + str(iv_key_not_32_bytes) + "'")
try:
    encrypted_msg = ige(msg_to_encrypt_padded, aes_key, iv_key_not_32_bytes, operation="encrypt")
except ValueError as ve:
    print("  Correctly got ValueError: '" + str(ve) + "'")

