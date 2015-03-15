# -*- coding: utf-8 -*-
# Author: Sammy Pfeiffer
# This file implements the AES 256 IGE cipher
# working in Python 2.7 and Python 3.4 (other versions untested)
# as it's needed for the implementation of Telegram API
# It's based on PyCryto
from __future__ import print_function
from Crypto.Util import number
from Crypto.Cipher import AES
from sys import version_info
if version_info >= (3, 4, 0):
    from binascii import hexlify
    long = int


# Some color codes for printing
ENDC = '\033[0m' # To end a color
REDFAIL = '\033[91m' # RED
GREENOK = '\033[92m' # GREEN

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
    if version_info >= (3, 4, 0):
        return str(hexlify(val).upper())
    return val.encode("hex").upper()

def hex_string_to_long(val):
    """Given a String like
    tmp_aes_key_str = "F011280887C7BB01DF0FC4E17830E0B91FBB8BE4B2267CB985AE25F33B527253"
    Convert it to int, which is actually long"""
    return int(val, 16)

def xor_stuff(a, b):
    """XOR applied to every element of a with every element of b.
    Depending on python version and depeding on input some arrangements need to be done."""
    if version_info < (3, 4, 0):
        if len(a) > len(b):
            return "".join([chr(ord(x) ^ ord(y)) for (x, y) in zip(a[:len(b)], b)])
        else:
            return "".join([chr(ord(x) ^ ord(y)) for (x, y) in zip(a, b[:len(a)])])
    else:
        if type(a) == str and type(b) == bytes:# cipher.encrypt returns string
            return bytes(ord(x) ^ y for x, y in zip(a, b))
        elif type(a) == bytes and type(b) == str:
            return bytes(x ^ ord(y) for x, y in zip(a, b))
        else:
            return bytes(x ^ y for x, y in zip(a, b))

def ige(message, key, iv, operation="decrypt"):
    """Given a key, given an iv, and message
     do whatever operation asked in the operation field.
     Operation will be checked for: "decrypt" and "encrypt" strings.
     Returns the message encrypted/decrypted.
     message must be a multiple by 16 bytes (for division in 16 byte blocks)
     key must be 32 byte
     iv must be 32 byte (it's not internally used in AES 256 ECB, but it's
     needed for IGE)"""
    if type(message) == long:
        message = number.long_to_bytes(message)
    if type(key) == long:
        key = number.long_to_bytes(key)
    if type(iv) == long:
        iv = number.long_to_bytes(iv)

    if len(key) != 32:
        raise ValueError("key must be 32 bytes long (was " +
                         str(len(key)) + " bytes)")
    if len(iv) != 32:
        raise ValueError("iv must be 32 bytes long (was " +
                         str(len(iv)) + " bytes)")

    cipher = AES.new(key, AES.MODE_ECB, iv)
    blocksize = cipher.block_size
    if len(message) % blocksize != 0:
        raise ValueError("message must be a multiple of 16 bytes (try adding " +
                        str(16 - len(message) % 16) + " bytes of padding)")

    ivp = iv[0:blocksize]
    ivp2 = iv[blocksize:]

    ciphered = None

    for i in range(0, len(message), blocksize):
        indata = message[i:i+blocksize]
        if operation == "decrypt":
            xored = xor_stuff(indata, ivp2)
            decrypt_xored = cipher.decrypt(xored)
            outdata = xor_stuff(decrypt_xored, ivp)
            ivp = indata
            ivp2 = outdata
        elif operation == "encrypt":
            xored = xor_stuff(indata, ivp)
            encrypt_xored = cipher.encrypt(xored)
            outdata = xor_stuff(encrypt_xored, ivp2)
            ivp = outdata
            ivp2 = indata
        else:
            raise ValueError("operation must be either 'decrypt' or 'encrypt'")

        if ciphered is None:
            ciphered = outdata
        else:
            ciphered_ba = bytearray(ciphered)
            ciphered_ba.extend(outdata)
            if version_info >= (3, 4, 0):
                ciphered = bytes(ciphered_ba)
            else:
                ciphered = str(ciphered_ba)

    return ciphered


if __name__ == "__main__":
    # Example data from https://core.telegram.org/mtproto/samples-auth_key#conversion-of-encrypted-answer-into-answer
    encrypted_answer_str = "28A92FE20173B347A8BB324B5FAB2667C9A8BBCE6468D5B509A4CBDDC186240AC912CF7006AF8926DE606A2E74C0493CAA57741E6C82451F54D3E068F5CCC49B4444124B9666FFB405AAB564A3D01E67F6E912867C8D20D9882707DC330B17B4E0DD57CB53BFAAFA9EF5BE76AE6C1B9B6C51E2D6502A47C883095C46C81E3BE25F62427B585488BB3BF239213BF48EB8FE34C9A026CC8413934043974DB03556633038392CECB51F94824E140B98637730A4BE79A8F9DAFA39BAE81E1095849EA4C83467C92A3A17D997817C8A7AC61C3FF414DA37B7D66E949C0AEC858F048224210FCC61F11C3A910B431CCBD104CCCC8DC6D29D4A5D133BE639A4C32BBFF153E63ACA3AC52F2E4709B8AE01844B142C1EE89D075D64F69A399FEB04E656FE3675A6F8F412078F3D0B58DA15311C1A9F8E53B3CD6BB5572C294904B726D0BE337E2E21977DA26DD6E33270251C2CA29DFCC70227F0755F84CFDA9AC4B8DD5F84F1D1EB36BA45CDDC70444D8C213E4BD8F63B8AB95A2D0B4180DC91283DC063ACFB92D6A4E407CDE7C8C69689F77A007441D4A6A8384B666502D9B77FC68B5B43CC607E60A146223E110FCB43BC3C942EF981930CDC4A1D310C0B64D5E55D308D863251AB90502C3E46CC599E886A927CDA963B9EB16CE62603B68529EE98F9F5206419E03FB458EC4BD9454AA8F6BA777573CC54B328895B1DF25EAD9FB4CD5198EE022B2B81F388D281D5E5BC580107CA01A50665C32B552715F335FD76264FAD00DDD5AE45B94832AC79CE7C511D194BC42B70EFA850BB15C2012C5215CABFE97CE66B8D8734D0EE759A638AF013"
    tmp_aes_key_str = "F011280887C7BB01DF0FC4E17830E0B91FBB8BE4B2267CB985AE25F33B527253"
    tmp_aes_iv_str = "3212D579EE35452ED23E0D0C92841AA7D31B2E9BDEF2151E80D15860311C85DB"
    answer_str = "BA0D89B53E0549828CCA27E966B301A48FECE2FCA5CF4D33F4A11EA877BA4AA57390733002000000FE000100C71CAEB9C6B1C9048E6C522F70F13F73980D40238E3E21C14934D037563D930F48198A0AA7C14058229493D22530F4DBFA336F6E0AC925139543AED44CCE7C3720FD51F69458705AC68CD4FE6B6B13ABDC9746512969328454F18FAF8C595F642477FE96BB2A941D5BCD1D4AC8CC49880708FA9B378E3C4F3A9060BEE67CF9A4A4A695811051907E162753B56B0F6B410DBA74D8A84B2A14B3144E0EF1284754FD17ED950D5965B4B9DD46582DB1178D169C6BC465B0D6FF9CA3928FEF5B9AE4E418FC15E83EBEA0F87FA9FF5EED70050DED2849F47BF959D956850CE929851F0D8115F635B105EE2E4E15D04B2454BF6F4FADF034B10403119CD8E3B92FCC5BFE000100262AABA621CC4DF587DC94CF8252258C0B9337DFB47545A49CDD5C9B8EAE7236C6CADC40B24E88590F1CC2CC762EBF1CF11DCC0B393CAAD6CEE4EE5848001C73ACBB1D127E4CB93072AA3D1C8151B6FB6AA6124B7CD782EAF981BDCFCE9D7A00E423BD9D194E8AF78EF6501F415522E44522281C79D906DDB79C72E9C63D83FB2A940FF779DFB5F2FD786FB4AD71C9F08CF48758E534E9815F634F1E3A80A5E1C2AF210C5AB762755AD4B2126DFA61A77FA9DA967D65DFD0AFB5CDF26C4D4E1A88B180F4E0D0B45BA1484F95CB2712B50BF3F5968D9D55C99C0FB9FB67BFF56D7D4481B634514FBA3488C4CDA2FC0659990E8E868B28632875A9AA703BCDCE8FCB7AE551"

    if version_info < (3, 4, 0):
        # Crypto.Cipher.AES needs it's parameters to be 32byte str
        # So we can either give 'str' type like this ONLY WORKS ON PYTHON2.7
        encrypted_answer_hex = encrypted_answer_str.decode("hex")
        tmp_aes_key_hex = tmp_aes_key_str.decode("hex")
        tmp_aes_iv_hex = tmp_aes_iv_str.decode("hex")
        answer_hex = answer_str.decode("hex")
        decrypted_answer_in_str = ige(encrypted_answer_hex, tmp_aes_key_hex, tmp_aes_iv_hex)
        print("decrypted_answer using string version of input: ")
        print(decrypted_answer_in_str)

    # Or give it long's representing the big numbers (ige will take care of the conversion)
    encrypted_answer = int(encrypted_answer_str, 16)
    tmp_aes_key = int(tmp_aes_key_str, 16)
    tmp_aes_iv = int(tmp_aes_iv_str, 16)
    answer = int(answer_str, 16)
    decrypted_answer_in_int = ige(encrypted_answer, tmp_aes_key, tmp_aes_iv)
    print("decrypted_answer using int version of input: ")
    print(decrypted_answer_in_int)

    if version_info < (3, 4, 0):
        if decrypted_answer_in_str == decrypted_answer_in_int:
            print("\nBoth str input and int input give the same result")
        else:
            print("\nDifferent result!!")


    decrypt_ans_hex_str = str_bytes_to_hex_string(decrypted_answer_in_int)
    print("Human friendly view of decrypted_answer:")
    print(decrypt_ans_hex_str)
    print("\nAnd we should expect inside of it:")
    print(answer_str)

    if answer_str in decrypt_ans_hex_str:
        print("\n\nanswer_str is in decrypt_ans_hex_str!")
        idx = decrypt_ans_hex_str.index(answer_str)
        print(decrypt_ans_hex_str[:idx], end="")
        print(GREENOK + decrypt_ans_hex_str[idx:idx+len(answer_str)] + ENDC, end="")
        print(decrypt_ans_hex_str[idx+len(answer_str):])
        print("There are " + str(idx/2) + " bytes at the start that are not part of the answer")
        print("Plus " + str(len(decrypt_ans_hex_str[len(answer_str)+idx:]) / 2) + " at the end not forming part")
        print("answer_str is: " + str(len(answer_str) / 2) + " bytes")
        print("decrypt_ans_hex_str is: " + str(len(decrypt_ans_hex_str) / 2) + " bytes")
        print("In total: " + str( (len(decrypt_ans_hex_str) - len(answer_str)) / 2) + " bytes that do not pertain")
    else:
        print("answer_str is not in decrypt_ans_hex_str :(")


    print("This is because the header (SHA1(answer)) is included and is 20 bytes long.")
    print("And in the end there are 0 to 15 bytes random to fill up the gap.")
    print("This means that we can safely ignore the starting 20bytes and all the extra bytes in the end")
    # answer_with_hash := SHA1(answer) + answer + (0-15 random bytes); such that the length be divisible by 16;
    # This... divisible by 16 is because of the blocksize of AES-256-ECB (yay!)

