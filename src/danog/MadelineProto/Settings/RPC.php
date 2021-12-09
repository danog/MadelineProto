<?php

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * RPC settings.
 */
class RPC extends SettingsAbstract
{
    /**
     * RPC timeout.
     */
    protected int $rpcTimeout = 5*60;

    /**
     * Flood timeout: if FLOOD_WAIT_ time is bigger than this, throw exception instead of waiting asynchronously.
     */
    protected int $floodTimeout = 10*60;

    /**
     * Maximum number of message IDs to consider when using call queues.
     */
    protected int $limitCallQueue = 100;

    /**
     * Encode payload with GZIP if bigger than.
     */
    protected int $gzipEncodeIfGt = 1024 * 1024;

    public function mergeArray(array $settings): void
    {
        if (isset($settings['connection_settings']['all']['drop_timeout'])) {
            $this->setRpcTimeout($settings['connection_settings']['all']['drop_timeout']);
        }
        if (isset($settings['flood_timeout']['wait_if_lt'])) {
            $this->setFloodTimeout($settings['flood_timeout']['wait_if_lt']);
        }
        if (isset($settings['msg_array_limit']['call_queue'])) {
            $this->setLimitCallQueue($settings['msg_array_limit']['call_queue']);
        }
        if (isset($settings['requests']['gzip_encode_if_gt'])) {
            $this->setLimitCallQueue($settings['requests']['gzip_encode_if_gt']);
        }
    }

    /**
     * Get RPC timeout.
     *
     * @return int
     */
    public function getRpcTimeout(): int
    {
        return $this->rpcTimeout;
    }

    /**
     * Set RPC timeout.
     *
     * @param int $rpcTimeout RPC timeout.
     *
     * @return self
     */
    public function setRpcTimeout(int $rpcTimeout): self
    {
        $this->rpcTimeout = $rpcTimeout;

        return $this;
    }

    /**
     * Get flood timeout: if FLOOD_WAIT_ time is bigger than this, throw exception instead of waiting asynchronously.
     *
     * @return int
     */
    public function getFloodTimeout(): int
    {
        return $this->floodTimeout;
    }

    /**
     * Set flood timeout: if FLOOD_WAIT_ time is bigger than this, throw exception instead of waiting asynchronously.
     *
     * @param int $floodTimeout Flood timeout: if FLOOD_WAIT_ time is bigger than this, throw exception instead of waiting asynchronously
     *
     * @return self
     */
    public function setFloodTimeout(int $floodTimeout): self
    {
        $this->floodTimeout = $floodTimeout;

        return $this;
    }

    /**
     * Get maximum number of messages to consider when using call queues.
     *
     * @return int
     */
    public function getLimitCallQueue(): int
    {
        return $this->limitCallQueue;
    }

    /**
     * Set maximum number of messages to consider when using call queues.
     *
     * @param int $limitCallQueue Maximum number of messages to consider when using call queues
     *
     * @return self
     */
    public function setLimitCallQueue(int $limitCallQueue): self
    {
        $this->limitCallQueue = $limitCallQueue;

        return $this;
    }

    /**
     * Get encode payload with GZIP if bigger than.
     *
     * @return int
     */
    public function getGzipEncodeIfGt(): int
    {
        return $this->gzipEncodeIfGt;
    }

    /**
     * Set encode payload with GZIP if bigger than.
     *
     * @param int $gzipEncodeIfGt Encode payload with GZIP if bigger than
     *
     * @return self
     */
    public function setGzipEncodeIfGt(int $gzipEncodeIfGt): self
    {
        $this->gzipEncodeIfGt = $gzipEncodeIfGt;

        return $this;
    }
}
