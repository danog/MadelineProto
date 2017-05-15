<?php

if (!extension_loaded('pthreads')) {
    class Volatile implements ArrayAccess, Countable, IteratorAggregate
    {
        const NOTHING = (0);
        const STARTED = (1 << 0);
        const RUNNING = (1 << 1);
        const JOINED = (1 << 2);
        const ERROR = (1 << 3);

        public function offsetSet($offset, $value)
        {
            $this->__set($offset, $value);
        }

        public function offsetGet($offset)
        {
            return $this->{$offset};
        }

        public function offsetUnset($offset)
        {
            $this->__unset($offset);
        }

        public function offsetExists($offset)
        {
            return isset($this->{$offset});
        }

        public function count()
        {
            return count(get_object_vars($this));
        }

        public function getIterator()
        {
            return new ArrayIterator(get_object_vars($this));
        }

        public function __set($offset, $value)
        {
            if ($offset === null) {
                $offset = count(get_object_vars($this));
            }

            if (!$this instanceof self) {
                if (isset($this->{$offset}) &&
                    $this->{$offset} instanceof Threaded) {
                    throw new \RuntimeException();
                }
            }

            return $this->{$offset} = $value;
        }

        public function __unset($offset)
        {
            if (!$this instanceof self) {
                if (isset($this->{$offset}) && $this->{$offset} instanceof Threaded) {
                    throw new \RuntimeException();
                }
            }
            unset($this->{$offset});
        }

        public function wait($timeout = 0)
        {
            return true;
        }

        public function notify()
        {
            return true;
        }

        public function synchronized(Closure $closure, ...$args)
        {
            return $closure(...$args);
        }

        public function isRunning()
        {
            return $this->state & THREAD::RUNNING;
        }

        public function isTerminated()
        {
            return $this->state & THREAD::ERROR;
        }

        public static function extend($class)
        {
            return true;
        }

        public function addRef()
        {
        }

        public function delRef()
        {
        }

        public function getRefCount()
        {
        }

        public function lock()
        {
            return true;
        }

        public function unlock()
        {
            return true;
        }

        public function isWaiting()
        {
            return false;
        }

        public function run()
        {
        }

        public function isGarbage()
        {
            return true;
        }

        protected $state;
    }
}
