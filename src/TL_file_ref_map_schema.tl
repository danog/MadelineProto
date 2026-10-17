boolFalse#bc799737 = Bool;
boolTrue#997275b5 = Bool;
true#3fedd339 = True;

vector#1cb5c415 {t:Type} # [ t ] = Vector t;

// Root
fileReferenceMap#72a99250 layer:int db_schema:string db_schema_json:string traversers_incoming:Vector<TraverserIncoming> traversers_outgoing:Vector<TraverserOutgoing> refresh_actions:Vector<RefreshAction> skipped_incoming_sources:Vector<SkippedSource> = FileReferenceMap;

// Traversers (incoming)
traverseParam#62161b67 flags:# name:string is_flag:flags.0?true is_vector:flags.1?true type:string = TraverseParam;

traverseMethodResult#fda9f236 name:string push_sources:Vector<Source> is_needed_parent:Bool = TraverserIncoming;
traverseIncomingConstructor#6e4d00b6 type:string predicate:string params:Vector<TraverseParam> push_sources:Vector<Source> is_needed_parent:Bool = TraverserIncoming;
traverseCommitSourceLocation#94cc59c5 type:string predicate:string push_sources:Vector<Source> stored_constructor:string = TraverserIncoming;

// Points to a constructor or method
predicate#de3bc05a predicate:string is_constructor:Bool = Predicate;

source#4ec4751a flags:# predicate:Predicate stored_constructor:string stored_params:Vector<FieldExtractor> skipped_flags:Vector<string> needs_parent:flags.3?Predicate needs_not_parents:Vector<Predicate> = Source;

// Traversers (outgoing)
traverseOutgoingConstructor#3fc5026d type:string predicate:string params:Vector<TraverseParam> = TraverserOutgoing;
traverseSwapLocation#032bf95d type:string predicate:string stored_constructor:string = TraverserOutgoing;
traverseMethodCall#9244f5bb name:string params:Vector<TraverseParam> = TraverserOutgoing;

// Skipped sources
skippedSource#2b543f6f predicate:Predicate why:string = SkippedSource;

// Refresh actions
refreshAction#d4e15689 stored_constructor:string action:ActionOp = RefreshAction;

// Field extraction path
paramNotFlag#acd9d5cf = ParamFlag;
paramIsFlagAbortIfEmpty#f8fe9fee = ParamFlag;
paramIsFlagFallback#202b77a1 fallback:TypedOp = ParamFlag;
paramIsFlagPassthrough#1dc6e17d = ParamFlag;

pathPart#19a10fbf type:string constructor:string param:string param_type:string flag:ParamFlag = PathPart;

path#0c3586a2 parts:Vector<PathPart> = Path;
pathParent#58f13684 parts:Vector<PathPart> = Path;

// Extractor
extractAndStore#72069549 from:Path to:string = FieldExtractor;

extractInputStickerSetFromDocumentAttributesAndStore#369d8d14 from:Path to:string = FieldExtractor;
extractInputStickerSetFromStickerSetAndStore#c167d470 from:Path to:string = FieldExtractor;

extractPeerIdFromPeerAndStore#7d33019c from:Path to:string = FieldExtractor;
extractPeerIdFromInputPeerAndStore#a51acfb4 from:Path to:string = FieldExtractor;

extractChannelIdFromChannelAndStore#5675bc97 from:Path to:string = FieldExtractor;
extractChannelIdFromInputChannelAndStore#b662660e from:Path to:string = FieldExtractor;

extractUserIdFromUserAndStore#4778ec63 from:Path to:string = FieldExtractor;
extractUserIdFromInputUserAndStore#7720aa2e from:Path to:string = FieldExtractor;

// Actions
callOp#c2ff3383 method:string args:Vector<TypedOpArg> = ActionOp;
getMessageOp#8da4c2ae peer:TypedOp id:TypedOp quick_reply_shortcut_id:TypedOp = ActionOp;

// For string => TypedOp dictionaries
typedOpArg#3a2930c2 key:string value:TypedOp = TypedOpArg;

// Typed constructors, the type is specified to simplify codegen,
// but isn't strictly necessary as it can be inferred from the TypedOpOp.
// It is fully pre-validated during the generation of the definition file.
typedOp#705b10ec type:string op:TypedOpOp = TypedOp;

// The actual ops
copyOp#f48f418f from:string = TypedOpOp;

getInputChannelByIdOp#3cb47531 from:string = TypedOpOp;
getInputUserByIdOp#c0ee4326 from:string = TypedOpOp;
getInputPeerByIdOp#19813750 from:string = TypedOpOp;

// Literals & constructors (methods not allowed or needed here)
constructorOp#107f8d8a constructor:string args:Vector<TypedOpArg> = TypedOpOp;

vectorOp#f8fb8f72 values:Vector<TypedOp> = TypedOpOp;

intLiteralOp#cbfabe7c value:int = TypedOpOp;
longLiteralOp#d08b8d3a value:long = TypedOpOp;
stringLiteralOp#2b56ea8e value:string = TypedOpOp;
bytesLiteralOp#fdb395a4 value:bytes = TypedOpOp;
boolLiteralOp#37e07911 value:Bool = TypedOpOp;
doubleLiteralOp#3651e3bf value:double = TypedOpOp;
themeFormatLiteralOp#8e4f9208 = TypedOpOp;
