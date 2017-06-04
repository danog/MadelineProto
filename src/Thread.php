<?php

if (!extension_loaded('pthreads')) {
    class Thread extends Threaded
    {
        public function isStarted()
        {
            return (bool) ($this->state & self::STARTED);
        }

        public function isJoined()
        {
            return (bool) ($this->state & self::JOINED);
        }

        public function kill()
        {
            $this->state |= self::ERROR;

            return true;
        }

        public static function getCurrentThreadId()
        {
            return 1;
        }

        public function getThreadId()
        {
            return 1;
        }

        public function start()
        {
            if (!isset($this->state)) {
                $this->state = 0;
            }
            if ($this->state & self::STARTED) {
                throw new \RuntimeException();
            }

            $this->state |= self::STARTED;
            $this->state |= self::RUNNING;

            try {
                $this->run();
            } catch (Exception $t) {
                $this->state |= self::ERROR;
            }

            $this->state &= ~self::RUNNING;

            return true;
        }

        public function join()
        {
            if ($this->state & self::JOINED) {
                throw new \RuntimeException();
            }

            $this->state |= self::JOINED;

            return true;
        }
    }
}
