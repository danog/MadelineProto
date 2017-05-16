<?php
if (!extension_loaded("pthreads")) {

	class Thread extends Threaded {
		public function isStarted() { return (bool) ($this->state & THREAD::STARTED); }
		public function isJoined() { return (bool) ($this->state & THREAD::JOINED); }
		public function kill() { 
			$this->state |= THREAD::ERROR;
			return true;  
		}

		public static function 	getCurrentThreadId() 	{ return 1; }
		public function 			getThreadId() 			{ return 1; }

		public function start() {
			if ($this->state & THREAD::STARTED) {
				throw new \RuntimeException();
			}

			$this->state |= THREAD::STARTED;		
			$this->state |= THREAD::RUNNING;

			try {
				$this->run();
			} catch(Exception $t) {
				$this->state |= THREAD::ERROR;
			}

			$this->state &= ~THREAD::RUNNING;
			return true;
		}

		public function join() {
			if ($this->state & THREAD::JOINED) {
				throw new \RuntimeException();
			}

			$this->state |= THREAD::JOINED;
			return true;
		}
	}
}
