<?php

declare(strict_types=1);

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * RPC settings.
 */
final class RPC extends SettingsAbstract
{
    /**
     * RPC resend timeout.
     */
    protected int $rpcResendTimeout = 10;
    /**
     * RPC drop timeout.
     */
    protected int $rpcDropTimeout = 60;

    /**
     * Flood timeout: if FLOOD_WAIT_ time is bigger than this, throw exception instead of waiting asynchronously.
     */
    protected int $floodTimeout = 30;

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
            $this->setRpcDropTimeout($settings['connection_settings']['all']['drop_timeout']);
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
     * Get RPC drop timeout.
     */
    public function getRpcDropTimeout(): int
    {
        return $this->rpcDropTimeout;
    }

    /**
     * Set RPC drop timeout.
     *
     * @param int $rpcDropTimeout RPC timeout
     */
    public function setRpcDropTimeout(int $rpcDropTimeout): self
    {
        $this->rpcDropTimeout = $rpcDropTimeout;

        return $this;
    }

    /**
     * Get RPC resend timeout.
     */
    public function getRpcResendTimeout(): int
    {
        return $this->rpcResendTimeout;
    }

    /**
     * Set RPC resend timeout.
     *
     * @param int $rpcResendTimeout RPC timeout.
     */
    public function setRpcResendTimeout(int $rpcResendTimeout): self
    {
        $this->rpcResendTimeout = $rpcResendTimeout;

        return $this;
    }

    /**
     * Get flood timeout: if FLOOD_WAIT_ time is bigger than this, throw exception instead of waiting asynchronously.
     */
    public function getFloodTimeout(): int
    {
        return \max(5, $this->floodTimeout);
    }

    /**
     * Set flood timeout: if FLOOD_WAIT_ time is bigger than this, throw exception instead of waiting asynchronously.
     *
     * Must be bigger than 5.
     *
     * @param int $floodTimeout Flood timeout: if FLOOD_WAIT_ time is bigger than this, throw exception instead of waiting asynchronously
     */
    public function setFloodTimeout(int $floodTimeout): self
    {
        $this->floodTimeout = $floodTimeout;

        return $this;
    }

    /**
     * Get maximum number of messages to consider when using call queues.
     */
    public function getLimitCallQueue(): int
    {
        return $this->limitCallQueue;
    }

    /**
     * Set maximum number of messages to consider when using call queues.
     *
     * @param int $limitCallQueue Maximum number of messages to consider when using call queues
     */
    public function setLimitCallQueue(int $limitCallQueue): self
    {
        $this->limitCallQueue = $limitCallQueue;

        return $this;
    }

    /**
     * Get encode payload with GZIP if bigger than.
     */
    public function getGzipEncodeIfGt(): int
    {
        return $this->gzipEncodeIfGt;
    }

    /**
     * Set encode payload with GZIP if bigger than.
     *
     * @param int $gzipEncodeIfGt Encode payload with GZIP if bigger than
     */
    public function setGzipEncodeIfGt(int $gzipEncodeIfGt): self
    {
        $this->gzipEncodeIfGt = $gzipEncodeIfGt;

        return $this;
    }
}
