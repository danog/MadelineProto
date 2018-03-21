
dataJSON#7d748d04 data:string = DataJSON;

socketMessageRequest request_id:int method:vector<string> args:vector<%DataJSON> = SocketMessage;
socketMessageResponse request_id:int data:%DataJSON = SocketMessage;
socketMessageException request_id:int exception:SocketException = SocketMessage;
socketMessageUpdate data:%DataJSON = SocketMessage;
socketMessageLog flags:# thread:flags.0?true process:flags.1?true file:flags.2?string level:int data:string = SocketMessage;
socketMessageRawData stream_id:int data:bytes = SocketMessage;

socketException message:string code:int trace:%SocketTLTrace = SocketException;
socketRPCErrorException flags:# rpc_message:flags.0?string message:flags.1?string code:int trace:%SocketTLTrace = SocketException;
socketTLException message:string code:int trace:%SocketTLTrace = SocketException;
socketDOMException message:string code:int trace:%SocketTLTrace = SocketException;

socketTLTrace frames:vector<%SocketTLFrame> = SocketTLTrace; 

socketTLFrame flags:# file:flags.0?string line:flags.1?string function:flags.2?string args:flags.3?string tl_param:flags.4?string = SocketTLFrame;