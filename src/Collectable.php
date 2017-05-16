<?php
if (!extension_loaded("pthreads")) {

	interface Collectable {
		public function isGarbage();
	}
}
