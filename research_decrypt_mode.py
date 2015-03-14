# -*- coding: utf-8 -*-
# THIS MODULE HAS ALL THE CONVERSIONS WE NEED! (I think)
from Crypto.Util import number
def vis(bs):
    """
    Function to visualize byte streams. Split into bytes, print to console.
    :param bs: BYTE STRING
    """
    bs = bytearray(bs)
    symbols_in_one_line = 8
    n = len(bs) // symbols_in_one_line
    for i in range(n):
        print(str(i*symbols_in_one_line)+" | "+" ".join(["%02X" % b for b in bs[i*symbols_in_one_line:(i+1)*symbols_in_one_line]])) # for every 8 symbols line
    if not len(bs) % symbols_in_one_line == 0:
        print(str((i+1)*symbols_in_one_line)+" | "+" ".join(["%02X" % b for b in bs[(i+1)*symbols_in_one_line:]])+"\n") # for last line

def hex_string_to_str_bytes(val):
    """Given a String like
    tmp_aes_key_str = "F011280887C7BB01DF0FC4E17830E0B91FBB8BE4B2267CB985AE25F33B527253"
    Convert it to it's byte representation, stored in py2 in a str, like:
    tmp_aes_key_hex = '\xf0\x11(\x08\x87\xc7\xbb\x01\xdf\x0f\xc4\xe1x0\xe0\xb9\x1f\xbb\x8b\xe4\xb2&|\xb9\x85\xae%\xf3;RrS'
    """
    return val.decode("hex")

def str_bytes_to_hex_string(val):
    """Given a str_bytes (so str())  like
    tmp_aes_key_hex = '\xf0\x11(\x08\x87\xc7\xbb\x01\xdf\x0f\xc4\xe1x0\xe0\xb9\x1f\xbb\x8b\xe4\xb2&|\xb9\x85\xae%\xf3;RrS'
    Convert it back to it's uppercase string representation, like:
    tmp_aes_key_str = "F011280887C7BB01DF0FC4E17830E0B91FBB8BE4B2267CB985AE25F33B527253" """
    return val.encode("hex").upper()

def hex_string_to_long(val):
    """Given a String like
    tmp_aes_key_str = "F011280887C7BB01DF0FC4E17830E0B91FBB8BE4B2267CB985AE25F33B527253"
    Convert it to int, which is actually long"""
    return int(val, 16)

def long_to_hex_string(val):
    """Given a long like:

    Convert it to hex_string like:

    from: http://stackoverflow.com/questions/4358285/is-there-a-faster-way-to-convert-an-arbitrary-large-integer-to-a-big-endian-seque/4358429#4358429
    """
    # number.long_to_bytes(val)
    # number.bytes_to_long()
    # number.tobytes()
    # number.bstr()
    return


# Got from https://core.telegram.org/mtproto/samples-auth_key#conversion-of-encrypted-answer-into-answer
# They say they use AES 256 IGE
# Infinite Garble Extension (IGE) is a block cipher mode. (http://www.links.org/files/openssl-ige.pdf)
encrypted_answer_str = "28A92FE20173B347A8BB324B5FAB2667C9A8BBCE6468D5B509A4CBDDC186240AC912CF7006AF8926DE606A2E74C0493CAA57741E6C82451F54D3E068F5CCC49B4444124B9666FFB405AAB564A3D01E67F6E912867C8D20D9882707DC330B17B4E0DD57CB53BFAAFA9EF5BE76AE6C1B9B6C51E2D6502A47C883095C46C81E3BE25F62427B585488BB3BF239213BF48EB8FE34C9A026CC8413934043974DB03556633038392CECB51F94824E140B98637730A4BE79A8F9DAFA39BAE81E1095849EA4C83467C92A3A17D997817C8A7AC61C3FF414DA37B7D66E949C0AEC858F048224210FCC61F11C3A910B431CCBD104CCCC8DC6D29D4A5D133BE639A4C32BBFF153E63ACA3AC52F2E4709B8AE01844B142C1EE89D075D64F69A399FEB04E656FE3675A6F8F412078F3D0B58DA15311C1A9F8E53B3CD6BB5572C294904B726D0BE337E2E21977DA26DD6E33270251C2CA29DFCC70227F0755F84CFDA9AC4B8DD5F84F1D1EB36BA45CDDC70444D8C213E4BD8F63B8AB95A2D0B4180DC91283DC063ACFB92D6A4E407CDE7C8C69689F77A007441D4A6A8384B666502D9B77FC68B5B43CC607E60A146223E110FCB43BC3C942EF981930CDC4A1D310C0B64D5E55D308D863251AB90502C3E46CC599E886A927CDA963B9EB16CE62603B68529EE98F9F5206419E03FB458EC4BD9454AA8F6BA777573CC54B328895B1DF25EAD9FB4CD5198EE022B2B81F388D281D5E5BC580107CA01A50665C32B552715F335FD76264FAD00DDD5AE45B94832AC79CE7C511D194BC42B70EFA850BB15C2012C5215CABFE97CE66B8D8734D0EE759A638AF013"
tmp_aes_key_str = "F011280887C7BB01DF0FC4E17830E0B91FBB8BE4B2267CB985AE25F33B527253"
tmp_aes_iv_str = "3212D579EE35452ED23E0D0C92841AA7D31B2E9BDEF2151E80D15860311C85DB"
answer_str = "BA0D89B53E0549828CCA27E966B301A48FECE2FCA5CF4D33F4A11EA877BA4AA57390733002000000FE000100C71CAEB9C6B1C9048E6C522F70F13F73980D40238E3E21C14934D037563D930F48198A0AA7C14058229493D22530F4DBFA336F6E0AC925139543AED44CCE7C3720FD51F69458705AC68CD4FE6B6B13ABDC9746512969328454F18FAF8C595F642477FE96BB2A941D5BCD1D4AC8CC49880708FA9B378E3C4F3A9060BEE67CF9A4A4A695811051907E162753B56B0F6B410DBA74D8A84B2A14B3144E0EF1284754FD17ED950D5965B4B9DD46582DB1178D169C6BC465B0D6FF9CA3928FEF5B9AE4E418FC15E83EBEA0F87FA9FF5EED70050DED2849F47BF959D956850CE929851F0D8115F635B105EE2E4E15D04B2454BF6F4FADF034B10403119CD8E3B92FCC5BFE000100262AABA621CC4DF587DC94CF8252258C0B9337DFB47545A49CDD5C9B8EAE7236C6CADC40B24E88590F1CC2CC762EBF1CF11DCC0B393CAAD6CEE4EE5848001C73ACBB1D127E4CB93072AA3D1C8151B6FB6AA6124B7CD782EAF981BDCFCE9D7A00E423BD9D194E8AF78EF6501F415522E44522281C79D906DDB79C72E9C63D83FB2A940FF779DFB5F2FD786FB4AD71C9F08CF48758E534E9815F634F1E3A80A5E1C2AF210C5AB762755AD4B2126DFA61A77FA9DA967D65DFD0AFB5CDF26C4D4E1A88B180F4E0D0B45BA1484F95CB2712B50BF3F5968D9D55C99C0FB9FB67BFF56D7D4481B634514FBA3488C4CDA2FC0659990E8E868B28632875A9AA703BCDCE8FCB7AE551"
print("encrypted_answer_str:")
print(encrypted_answer_str)
print("tmp_aes_key_str:")
print(tmp_aes_key_str)
print("tmp_aes_iv_str:")
print(tmp_aes_iv_str)
print("answer_str:")
print(answer_str)

# Convert them to bytes (strings in py2 anyways)
# http://stackoverflow.com/questions/5649407/hexadecimal-string-to-byte-array-in-python
encrypted_answer_hex = encrypted_answer_str.decode("hex") # int(encrypted_answer_str, 16) for py3
tmp_aes_key_hex = tmp_aes_key_str.decode("hex")
tmp_aes_iv_hex = tmp_aes_iv_str.decode("hex")
answer_hex = answer_str.decode("hex")
print("encrypted_answer_hex:")
print(encrypted_answer_hex.__repr__())
print("tmp_aes_key_hex:")
print(tmp_aes_key_hex.__repr__())
print("tmp_aes_iv_hex:")
print(tmp_aes_iv_hex.__repr__())
print("answer_hex:")
print(answer_hex.__repr__())
# Re-convert them to string
encrypted_answer_hex_to_str = encrypted_answer_hex.encode("hex").upper() # int(encrypted_answer_str, 16) for py3
tmp_aes_key_hex_to_str = tmp_aes_key_hex.encode("hex").upper()
tmp_aes_iv_hex_to_str = tmp_aes_iv_hex.encode("hex").upper()
answer_hex_to_str = answer_hex.encode("hex").upper()
print("encrypted_answer_hex_to_str:")
print(encrypted_answer_hex_to_str)
print("tmp_aes_key_hex_to_str:")
print(tmp_aes_key_hex_to_str)
print("tmp_aes_iv_hex_to_str:")
print(tmp_aes_iv_hex_to_str)
print("answer_hex_to_str:")
print(answer_hex_to_str)


# Check if they are the same
if encrypted_answer_hex_to_str == encrypted_answer_str:
    print("encrypted_answer_hex_to_str == encrypted_answer_str")
else:
    print("encrypted_answer_hex_to_str != encrypted_answer_str")

encrypted_answer = int(encrypted_answer_str, 16) # int(encrypted_answer_str, 16) for py3
tmp_aes_key = int(tmp_aes_key_str, 16)
tmp_aes_iv = int(tmp_aes_iv_str, 16)
answer = int(answer_str, 16)
print("longtohexstring")
print(long_to_hex_string(tmp_aes_key))

# print("len(encrypted_answer): " + str(len(encrypted_answer)))
# print("len(answer): " + str(len(answer)))

# Got from testing.py
# encrypted_answer = 'L\xd7\xddI\x0b\xc3\xeay\xf1\x07]\x93\x7fY\x0cmVAX\x03\xeb\n}\x99\xd6\x99\xaa\xba\x05\x9d\xaaB\xe2\x97\xb3\xf2\xf8\xd8\x9f\xa6\x13\x177a\xb45A\x0f}\xb3\x99\xa3D?L\x94\xa3\xbcG\xe8\xf2\x14 \xb9.\x8b\xa0\xf1\xa5\xf1\x18\x9aZ2\x8f\xae\x05\xd9\x84H\xa3&\xad\x84\x82w\x9e\xe8\xba\x8a\x87QT\xdd\x12\x8c\x86\xde\xd8\x7fLM\xb9\x81H;JX\x85\x14\x1af!\xb20\xea)\xa8>(\xa9\xce5,\x96\x14\xd7P\x0c\xb3\x02\x9a\x16\xfc\x94\xacT\xa0\xd4\x82\xe5S1\xf4\xe1\x8cB\xad\x89\xc3C\xa6\rt)\xfa\x0b\xfe3\t\xdd\x02\xbe\xecP\xd6\xd7p\xf6\xf3\xb5\xdf6\xfc\x90l\xaa\x06\x8a\xc0XO\x96>\x85\x18\xebN\x08\x13x\xc0\x1ah\xbd\xedO\x99T\xfd\xed\x87C}\x89!\x99Oz\xfe\x927z~ &"\x0e/\x01N\x13\xfa\xd1\x96\x87\x0f\x83\x98d\x12X\xa7\x8c\xa8\x1c/\xbc\xab\xb2:\x07\xa6\x14\xfa\xe3\xd2\x8cG\xd6\x84\xe4[\x8f\x9e\x8f\xbb\x9c\x8a\x80\xbd\xcf\xaf!\xf7E\x1b\x1f\x91\x18\xc2\x8eBE\xfb\x84\xb6\xc5e5Q\xfd\xb8\xcb\xbc\xb4\x9f\xb7\x92\xfe\xae\xda,\xfaA\x94\x7fq\x1e\xd1\x05\xe8=\x9d#,\xe6\xb7y1\xe6\xc7!\xa0\x0bx\xd1\xb3\xad9\xc4\xdd\x99Y\xca\r\x07+\x903\x1e?\x1d_\x8b\xb0M\xff\x14\xc3:\x95\xa8\xee\xc1\xb5\xff\xfb1\x95\xe1\xcaT\xe4D\xcf\xd2%\x11\xeb@`Att\xbe\x11\xfc/\x05\x9a\xd2\x15\r\xb6\x9d\x88\xae\xa8\xd1q\xe5\x9b\x05A\x8d[\xbf\xaaN\x1b\xee\xbf#4\x1c\xd5\xa4\x1f\x0fo\xaf\xd0\x00g\xc1\x9a\x82\x00\x8c_\xd4\xac!{K\xca\x89x\xde\xf9\x8d\x19\xec\x12\x8epY\xdb\x9f\x98\xe6\x88\xe7\xc1\x92\x90\x17\x80\x03Ry\xf1n\x97e\xe2\x8c\xe9\x8c\xd6<\xba2:\x9f\x06g\x05\xaa#\xf4\xca1\x16o\xb5\x8b\xcd\xfe\x814h\xac\xcd\x0e\xd0\x1c\x0c\xc71\x11\xbe\xa5\xb3#\xcfh\x07)\x91\xc7\xc8iy!\x03\xc8\xf0\xb2\x02\xf3\xc7\xdf\xafXm\xf5\xaf\xdd\xc8\xeb\xb3n7\xe34\xa7R\x8c\xaf\xa3\xb7y\xe7\x12\x0f\x0c\xc2\xa8v\x12E\xc3u\xc8Y\x1fh.\xcf\x01\xae\x8c\x00"v\x99V\xad>\xaf\x08)\x83V*\x9b\xad\xc0\x9c\x94\xa5D[\x08s\x88\xd1\xcb\xf6\xf8j\xa1c\xc1yb\xda\x12\xa1~\xf6\xd1"\x14\x11a\x02\xc1\xd3\xf5'
# tmp_aes_key = b'\x82\xeb\x12\x0e\xbeT\x80>!\xaa\x01\xac\xc8\xe1u#d\x1b\x08\xf5G\xc7\xe5g\xa9\xc3\x1d*BC;6'
# tmp_aes_iv = b'r\xbb/\xe8\x0bb,T\x19\x17\xf20WsTf\x1d_C\x83|2h\xd3s\x82\xaeVW\x10v\xff'

from Crypto.Cipher import AES
# # try all modes
# aes_modes = [AES.MODE_CBC, AES.MODE_CFB, AES.MODE_CTR, AES.MODE_ECB, AES.MODE_OFB, AES.MODE_OPENPGP, AES.MODE_PGP]
# aes_modes_names = ["AES.MODE_CBC", "AES.MODE_CFB", "AES.MODE_CTR", "AES.MODE_ECB", "AES.MODE_OFB", "AES.MODE_OPENPGP", "AES.MODE_PGP"]
#
# working_modes = []
# working_modenames = []
# for mode, modename in zip(aes_modes, aes_modes_names):
#     print("\n\nTrying mode: " + modename + "(" + str(mode) +  ")")
#     try:
#         crypting_object = AES.new(tmp_aes_key, mode, tmp_aes_iv) # encrypter thing
#         decrypted_answer = crypting_object.decrypt(encrypted_answer)
#         print("decrypted_answer: ")
#         print(decrypted_answer.__repr__())
#         vis(decrypted_answer)
#         working_modes.append(mode)
#         working_modenames.append(modename)
#         print("Which should look the same than: ")
#         print(answer.__repr__())
#         vis(answer)
#         if answer == decrypted_answer:
#             print("THEY ARE THE SAME!!")
#         else:
#             print("THEY ARE DIFFERENT :(((((")
#     except Exception as e:
#         print("Exception: " + str(e))
#
# print("\n\nModes " + str(working_modenames) + " (" + str(working_modes) + ") succesfully unencrypted the answer!")


# From http://stackoverflow.com/questions/17797582/java-aes-256-decrypt-with-ige
# public static final byte[] ige(final byte[] key, final byte[] IV,
#         final byte[] Message) throws Exception {
def ige(key, iv, message, blocksize=16):#32):
    """given a key, ive and message, decrypt it. blocksize is the default one used in the javascript implementation"""
    # print("len(key): " + str(len(key)))
    # print("len(iv): " + str(len(iv)))
    # print("len(message): " + str(len(message)))
    # key = bytearray(key)
    # iv = bytearray(iv)
    # message = bytearray(message)
#
#     final Cipher cipher = Cipher.getInstance("AES/ECB/NoPadding");
    cipher = AES.new(key, AES.MODE_ECB, iv)
    blocksize = cipher.block_size

#     cipher.init(Cipher.DECRYPT_MODE, new SecretKeySpec(key, "AES"));
#
#     final int blocksize = cipher.getBlockSize();
#
#     byte[] xPrev = Arrays.copyOfRange(IV, 0, blocksize);
    xPrev = iv[0:blocksize]
#     byte[] yPrev = Arrays.copyOfRange(IV, blocksize, IV.length);
    yPrev = iv[blocksize:]
#
#     byte[] decrypted = new byte[0];
    decrypted = None
#
#     byte[] y, x;
#     y = bytearray()
#     x = bytearray()
#     for (int i = 0; i < Message.length; i += blocksize) {


    def xor_strings(a, b):     # xor two strings of different lengths
        if len(a) > len(b):
            return "".join([chr(ord(x) ^ ord(y)) for (x, y) in zip(a[:len(b)], b)])
        else:
            return "".join([chr(ord(x) ^ ord(y)) for (x, y) in zip(a, b[:len(a)])])
    def add_strings(a, b):
        if len(a) > len(b):
            return "".join([chr(ord(x) + ord(y)) for (x, y) in zip(a[:len(b)], b)])
        else:
            return "".join([chr(ord(x) + ord(y)) for (x, y) in zip(a, b[:len(a)])])
        #return sum([a, b]) & 0xFFFFFFFF

    for i in range(0, len(message), blocksize):
        #print("i: " + str(i))
#         x = java.util.Arrays.copyOfRange(Message, i, i + blocksize);
        x = message[i:i+blocksize]
        #print(" x: " + x.__repr__())
#         y = xor(cipher.doFinal(xor(x, yPrev)), xPrev);

        y = xor_strings(cipher.decrypt(xor_strings(x, yPrev)), xPrev)
        #print(" y: " + y.__repr__())
        #y = xor(cipher.decrypt(xor(x, yPrev)), xPrev)
#         xPrev = x;
        xPrev = x
#         yPrev = y;
        yPrev = y
#
#         decrypted = sumBytes(decrypted, y);
        if decrypted is None:
            decrypted = y
        else:
            decrypted_ba = bytearray(decrypted)
            decrypted_ba.extend(y)
            decrypted = str(decrypted_ba)
            # all this did not work
            #decrypted = int(decrypted, 16) + int(y, 16)
            #decrypted.append(y)
            #add_strings(decrypted, y)
            #decrypted = decrypted + y # this is wrong
        #print(" decrypted: " + decrypted.__repr__())
#     }
#
#     return decrypted;
    print("len(key): " + str(len(key)))
    print("len(iv): " + str(len(iv)))
    print("len(message): " + str(len(message)))
    print("!!!!!!!!!!!!!!!!!!!!!! ---> cipher.block_size: " + str(cipher.block_size))
    return decrypted
# }

#result = ige(tmp_aes_key, tmp_aes_iv, encrypted_answer)
result = ige(tmp_aes_key_hex, tmp_aes_iv_hex, encrypted_answer_hex)
print("result:")
print(result)
vis(result)
if answer == result:
    print("THEY ARE THE SAME!!")
else:
    print("THEY ARE DIFFERENT :(((((")
    print("len(result): " + str(len(result)))
    print("len(answer):" + str(len(answer)))

vis(encrypted_answer)

# Inspiration: http://passingcuriosity.com/2009/aes-encryption-in-python-with-m2crypto/
# sudo apt-get install swig
# sudo pip install M2Crypto
# This DOES NOT work. openssl wrapper m2crypto does not have IGE available
# from base64 import b64encode, b64decode
# from M2Crypto.EVP import Cipher
# ENC=1
# DEC=0
#
# def build_cipher(key, iv, op=ENC):
#     """"""""
#     #return Cipher(alg='aes_256_ecb', key=key, iv=iv, op=op)
#     return Cipher(alg='aes_256 ige', key=key, iv=iv, op=op)
#
# def encryptor(key, iv=None):
#     """"""
#     # Decode the key and iv
#     key = b64decode(key)
#     if iv is None:
#         iv = '\0' * 16
#     else:
#         iv = b64decode(iv)
#
#    # Return the encryption function
#     def encrypt(data):
#         cipher = build_cipher(key, iv, ENC)
#         v = cipher.update(data)
#         v = v + cipher.final()
#         del cipher
#         v = b64encode(v)
#         return v
#     return encrypt
#
# def decryptor(key, iv=None):
#     """"""
#     # Decode the key and iv
#     #key = b64decode(key)
#     if iv is None:
#         iv = '\0' * 16
#     else:
#         #iv = b64decode(iv)
#         pass
#
#    # Return the decryption function
#     def decrypt(data):
#         #data = b64decode(data)
#         cipher = build_cipher(key, iv, DEC)
#         v = cipher.update(data)
#         v = v + cipher.final()
#         del cipher
#         return v
#     return decrypt
#
# print("Decrypting with m2...")
# decryptor_m2= decryptor(tmp_aes_key, tmp_aes_iv)
# m2_decrypt_ans =decryptor_m2(encrypted_answer)
# vis(m2_decrypt_ans)

# Output was:
# Trying mode: AES.MODE_CBC(2)
# Exception: IV must be 16 bytes long
#
#
# Trying mode: AES.MODE_CFB(3)
# Exception: IV must be 16 bytes long
#
#
# Trying mode: AES.MODE_CTR(6)
# Exception: 'counter' keyword parameter is required with CTR mode
#
#
# Trying mode: AES.MODE_ECB(1)
# decrypted_answer:
# '\xb4\x87<\xc5&\xe2J\x0e\x96\x1a\x08\xeaSR\xc4!.\x17\x0b]ZI\xd6\xdc\xbd5\x87i\x11\x1b\x9d\x04\x93;\xc5\xd7C\xe1[\xb9\xaa\x95a)\x14\xfd\x8f\xe8\xf8\xc7\xb1~\xdf\xd3\xf7\xf8\xdc\xc4v\xae \xd7\xff\x91\x0c}:\xf45T~\xad\x00w\xde\x98\xe7\xd5b\x7f\xafZg\xd0\x01\x1e\xcaF\xc7\xe4,@kS\xc8|\xe4\xedv\xa3g\x121.\x00\x10X$\x00\xd0\xea\x12\xe0\xc0n\xdd\x9c&\xb3\xd9\x15\x97\xc67\xaeH\xef\xfb\xd9\x8eC\x8b\x99\xb6P\x9f&\x9d\x95a\x00\xc3\xac\xd7@\x95\\\x127\x97\xca:\x9b\xfbA\xad\xc5K \xc7\xe1erK\x16\xd3m\xd4\x1b\xd3\xbf\x9e\xc2\x15\xd8\xd6^\xa7r9\xffC\xb0(\xb5W\xa0\n=\x8a\x0b"\x18&\x95l\xc4oF\xd9\xff\x98\xb5\xaf\xbd\xb89\x80p\xffC\xa3\xcb\xff\x8a1p\xd0t0\x13\x89\x1dG\x1e+l\xab\xd9AN$L\xd8~\xedE\xf7\x8c\x93\x0c\xc1\xfd\xdb\xc9\x9d\xe9\x0c\x1d\xbb}8\xf6j\x95>\xae\xee-\x9e?k\xaf\xaer\xe4`\xf9\t\x11\xb4\xd8\x03q*\x83\x13\x02\x0e\xe8\x9d?\xef\x0f\xd3^\xd82o4U\xc4l\xde\x17iJ\xfe\xd4\x8c`\xc6{H\x97\x16\xb7g\xb9\xddeUc\xd4\xc8\xa782IeK\x81\xc3r=!\x92\x97\x0f\x92\xd4\xf6\x01\x93\xa5\xbacL\x8b\x1a\xd5\x1d4\x9a2\x87m6\xc9\xf1\xb0&K\xdfo\xee\xeaQ\xf9\xc3\xa6\xfd?\n\xad\xfc\x9e\xaf\x8c\xfdJd\x84\xa5\x8bBl\xb1\xa0g\x8c\xb47\xf4\xc0`\x88\xe8\x88\n\x85\x81r\xf4J\xe3}\x89]\x8b\xfb|\x10\x05-)\xe2\xba\x96BT:\x16F\xaf\xd8\xa9\xcfew>\xc4QE\x91M\xffc\x07d\x1c\xf2\xb0G\xe5\x04\x03\x9bZ\xa0w\xa4\xd42\x0ex\xc3@\xdd\x9c\x15X\x0ey\x0e+\x12\x13ro\xda\xc2a\xfbH\xd0\x7f\x96\xad\xa7b&\xe7\xca+h\x1b\x13!\xf2\xf0cUw\xd7\x0f\xcd\x10>\x91\xcb\x0e\xba\xc1\xdec\xe6\x11\xdb\xba=y\x97\xe9\xc5\xfcW\x9b\x91)\xf1\x19\x12\xc4L\x83\xee"\xc2S\x9at\xd4\x01({\x01\xdc2e\xe7K\x10C\xa8J\xa3a\x1c=#\x03\x9b\xb2\x8e\xe0\x95\x9a\xf4R\x8d\xcf\xef\x88\xef\xce|\xe7\x9a5\xfe>\x13\x9d\x13\xe9 \xfc[\x02\xe2QP\xd4\x93\xe3\x15uJ3\xe6\xe1B\x12\xbdy\x81G\x9a*\x93K'
#
#
# Trying mode: AES.MODE_OFB(5)
# Exception: IV must be 16 bytes long
#
#
# Trying mode: AES.MODE_OPENPGP(7)
# Exception: Length of IV must be 16 or 18 bytes for MODE_OPENPGP
#
#
# Trying mode: AES.MODE_PGP(4)
# Exception: MODE_PGP is not supported anymore
#
# Modes ['AES.MODE_ECB'] ([1]) succesfully unencrypted the answer!

