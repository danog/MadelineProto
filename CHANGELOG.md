# Changelog


## 4.0.0 Full async

**Fully asynchronous MadelineProto, thanks to [amphp](https://github.com/danog/amphp)!**

* Fully rewritten connection stack, with support for websockets, stuff
* updates
* Improved message splitting algorithm: performance improvements, and it will now notify you via the logs if there are too many entities in the logs, or if the entities are too long.  
* Improved get_self method.  
* Rewritten proxy stack


Things to expect in the next releases:
Document async apis
optional max_id and min_id
async iterators
Method name changes
#MadelineProtoForNode async
lua async