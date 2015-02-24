# -*- coding: utf-8 -*-
"""
Created on Tue Sep  2 19:26:15 2014

@author: agrigoryev
"""
import binascii
import struct

def sendpacket(socket, data, number):
    step1 = (len(data)+12).to_bytes(4,'little') + number.to_bytes(4,'little') + data
    step2 = step1 + binascii.crc32(step1).to_bytes(4,'little')
    socket.send(step2)
    
def recvpacket(socket):
    packet_length_data = socket.recv(4)
    if len(packet_length_data)>0:
        packet_length = struct.unpack("<L", packet_length_data)[0]
        packet = socket.recv(packet_length - 4)
        number = struct.unpack("<L", packet[0:4])[0]
        data = packet[4:-4]
        crc = packet[-4:]
        if binascii.crc32(packet_length_data + packet[0:-4]).to_bytes(4,'little') == crc:
            return (number, data)