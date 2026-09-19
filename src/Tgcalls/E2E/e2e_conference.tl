// TL schema for Telegram end-to-end encrypted conference calls, loaded into a dedicated TL instance
// by {@see \danog\MadelineProto\Tgcalls\E2E\BlockCodec}. Mirrors td/generate/scheme/e2e_api.tl; the
// inner chain constructors carry no explicit id, so their constructor id is the CRC32 of the
// definition, byte-identical to TDLib (which the block hash and signature depend on).
//
// IMPORTANT: keep these definitions byte-identical to TDLib's e2e_api.tl — the block hash is the
// SHA256 of the TL serialization and the signature is over the serialization with the signature
// field zeroed, so any divergence makes every block we build be rejected by the server and peers.

boolFalse#bc799737 = Bool;
boolTrue#997275b5 = Bool;

true#3fedd339 = True;

vector#1cb5c415 {t:Type} # [ t ] = Vector t;

e2e.chain.groupBroadcastNonceCommit#d1512ae7 signature:int512 user_id:int64 chain_height:int32 chain_hash:int256 nonce_hash:int256 = e2e.chain.GroupBroadcast;
e2e.chain.groupBroadcastNonceReveal#83f4f9d8 signature:int512 user_id:int64 chain_height:int32 chain_hash:int256 nonce:int256 = e2e.chain.GroupBroadcast;

e2e.chain.groupParticipant user_id:long public_key:int256 flags:# add_users:flags.0?true remove_users:flags.1?true version:int = e2e.chain.GroupParticipant;
e2e.chain.groupState participants:vector<e2e.chain.GroupParticipant> external_permissions:int = e2e.chain.GroupState;
e2e.chain.sharedKey ek:int256 encrypted_shared_key:string dest_user_id:vector<long> dest_header:vector<bytes> = e2e.chain.SharedKey;

e2e.chain.changeNoop nonce:int256 = e2e.chain.Change;
e2e.chain.changeSetValue key:bytes value:bytes = e2e.chain.Change;
e2e.chain.changeSetGroupState group_state:e2e.chain.GroupState = e2e.chain.Change;
e2e.chain.changeSetSharedKey shared_key:e2e.chain.SharedKey = e2e.chain.Change;

e2e.chain.stateProof flags:# kv_hash:int256 group_state:flags.0?e2e.chain.GroupState shared_key:flags.1?e2e.chain.SharedKey = e2e.chain.StateProof;

e2e.chain.block#639a3db6 signature:int512 flags:# prev_block_hash:int256 changes:vector<e2e.chain.Change> height:int state_proof:e2e.chain.StateProof signature_public_key:flags.0?int256 = e2e.chain.Block;

---functions---

e2e.nop = Bool;
