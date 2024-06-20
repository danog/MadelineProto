<?php

declare(strict_types=1);

/**
 * ResponseHandler module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

use Amp\SignalException;
use danog\BetterPrometheus\BetterHistogram;
use danog\Loop\Loop;
use danog\MadelineProto\FileRedirect;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\MTProtoIncomingMessage;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\PTSException;
use danog\MadelineProto\RPCError\FloodPremiumWaitError;
use danog\MadelineProto\RPCError\FloodWaitError;
use danog\MadelineProto\RPCError\RateLimitError;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\SecretPeerNotInDbException;
use danog\MadelineProto\SecurityException;
use Revolt\EventLoop;
use SplQueue;
use Throwable;

use const PHP_EOL;

/**
 * Manages responses.
 *
 * @property ?BetterHistogram $requestLatencies
 * @internal
 */
trait ResponseHandler
{
    /**
     * @param iterable<array-key, MTProtoIncomingMessage> $messages
     */
    private function handleMessages(iterable $messages): ?float
    {
        foreach ($messages as $message) {
            $this->API->logger($message->log($this->datacenter), Logger::ULTRA_VERBOSE);

            $type = $message->getPredicate();
            if ($type !== 'msg_container') {
                $this->checkInSeqNo($message);
            }
            try {
                match ($type) {
                    'msgs_ack' => $this->handleAck($message),

                    'rpc_result',
                    'future_salts',
                    'msgs_state_info',
                    'bad_server_salt',
                    'bad_msg_notification',
                    'pong' => $this->handleResponse($message),

                    'new_session_created' => $this->handleNewSession($message),
                    'msg_container' => $this->handleContainer($message),
                    'msg_copy' => $this->handleMsgCopy($message),
                    'http_wait' => $this->API->logger($message->read(), Logger::NOTICE),
                    'msgs_state_req' => $this->sendMsgsStateInfo($message->read()['msg_ids'], $message->getMsgId()),
                    'msgs_all_info' => $this->onMsgsAllInfo($message->read()),
                    'msg_detailed_info' => $this->onMsgDetailedInfo($message->read()),
                    'msg_new_detailed_info' => $this->onNewMsgDetailedInfo($message->read()),
                    'msg_resend_req' => $this->onMsgResendReq($message->read(), $message->getMsgId()),
                    'msg_resend_ans_req' => $this->onMsgResendAnsReq($message->read(), $message->getMsgId()),
                    default => $this->handleFallback($message)
                };
            } catch (\Throwable $e) {
                $this->API->logger("An error occurred while handling $message: $e", Logger::FATAL_ERROR);
            }
        }
        return Loop::PAUSE;
    }
    private function handleAck(MTProtoIncomingMessage $message): void
    {
        foreach ($message->read()['msg_ids'] as $msg_id) {
            // Acknowledge that the server received my message
            $this->ackOutgoingMessageId($msg_id);
        }
    }
    private function handleFallback(MTProtoIncomingMessage $message): void
    {
        $this->ackIncomingMessage($message);
        $response_type = $this->API->getTL()->getConstructors()->findByPredicate($message->getContent()['_'])['type'];
        if ($response_type == 'Updates') {
            if ($message->unencrypted) {
                throw new SecurityException("Can't accept unencrypted update!");
            }
            if (!$this->isCdn()) {
                EventLoop::queue($this->API->handleUpdates(...), $message->read());
            }
            return;
        }

        $this->API->logger('Trying to assign a response of type ' . $response_type . ' to its request...', Logger::VERBOSE);
        foreach ($this->new_outgoing as $expecting_msg_id => $expecting) {
            if (!$expecting->type) {
                continue;
            }
            $this->API->logger("Does the request of return type {$expecting->type} match?", Logger::VERBOSE);
            if ($response_type === $expecting->type) {
                $this->API->logger('Yes', Logger::VERBOSE);
                $this->handleResponse($message, $expecting_msg_id);
                return;
            }
            $this->API->logger('No', Logger::VERBOSE);
        }
        $this->API->logger('Dunno how to handle ' . PHP_EOL . var_export($message->read(), true), Logger::FATAL_ERROR);
    }
    private function handleNewSession(MTProtoIncomingMessage $message): void
    {
        $this->ackIncomingMessage($message);
        $this->shared->getTempAuthKey()->setServerSalt($message->read()['server_salt']);
        if ($this->API->authorized === \danog\MadelineProto\API::LOGGED_IN
            && isset($this->API->updaters[UpdateLoop::GENERIC])
        ) {
            $this->API->updaters[UpdateLoop::GENERIC]->resume();
        }
    }
    private function handleContainer(MTProtoIncomingMessage $message): void
    {
        $tmp = new SplQueue;
        $tmp->setIteratorMode(SplQueue::IT_MODE_DELETE);
        foreach ($message->read()['messages'] as $msg) {
            $this->msgIdHandler->checkIncomingMessageId($msg['msg_id'], true);
            $newMessage = new MTProtoIncomingMessage($msg['body'], $msg['msg_id'], $message->unencrypted, true);
            $newMessage->setSeqNo($msg['seqno']);
            $this->checkInSeqNo($newMessage);
            $newMessage->setSeqNo(null);
            $tmp->enqueue($newMessage);
            $this->incomingCtr?->inc();
            $this->incoming_messages[$msg['msg_id']] = $newMessage;
        }
        $this->checkInSeqNo($message);
        $this->handleMessages($tmp);
    }
    private function handleMsgCopy(MTProtoIncomingMessage $message): void
    {
        $this->ackIncomingMessage($message);
        $content = $message->read();
        $referencedMsgId = $content['msg_id'];
        if (isset($this->incoming_messages[$referencedMsgId])) {
            $this->ackIncomingMessage($this->incoming_messages[$referencedMsgId]);
        } else {
            $this->msgIdHandler->checkIncomingMessageId($referencedMsgId, true);
            $message = new MTProtoIncomingMessage($content['orig_message'], $referencedMsgId, $message->unencrypted);
            $this->incomingCtr?->inc();
            $this->incoming_messages[$referencedMsgId] = $message;
            $this->handleMessages([$message]);
        }
    }
    /**
     * @param callable(): \Throwable $data
     */
    private function handleReject(MTProtoOutgoingMessage $message, callable $data): void
    {
        $this->gotResponseForOutgoingMessage($message);
        $message->reply($data);
    }

    /**
     * Handle RPC response.
     */
    private function handleResponse(MTProtoIncomingMessage $message, ?int $requestId = null): void
    {
        $requestId ??= $message->getRequestId();
        $response = $message->read();
        if ($response['_'] === 'rpc_result') {
            if ($message->unencrypted) {
                throw new SecurityException("Can't accept unencrypted result!");
            }
            $this->ackIncomingMessage($message);
            $response = $response['result'];
        }
        if (!isset($this->outgoing_messages[$requestId])) {
            $this->API->logger("Got a response $message with message ID $requestId, but there is no request!", Logger::ERROR);
            return;
        }
        /** @var MTProtoOutgoingMessage */
        $request = $this->outgoing_messages[$requestId];
        if ($request->getState() & MTProtoOutgoingMessage::STATE_REPLIED) {
            $this->API->logger("Already got a response to $request, but there is another reply $message with message ID $requestId!", Logger::FATAL_ERROR);
            return;
        }
        $constructor = $response['_'] ?? '';
        if ($constructor === 'rpc_error') {
            try {
                $exception = $this->handleRpcError($request, $response);
            } catch (Throwable $e) {
                $exception = static fn (): \Throwable => $e;
            }
            if ($exception) {
                $this->handleReject($request, $exception);
            }
            return;
        }
        if ($constructor === 'bad_server_salt' || $constructor === 'bad_msg_notification') {
            $this->API->logger('Received bad_msg_notification: ' . MTProto::BAD_MSG_ERROR_CODES[$response['error_code']], Logger::WARNING);
            switch ($response['error_code']) {
                case 48:
                    $this->shared->getTempAuthKey()->setServerSalt($response['new_server_salt']);
                    $this->methodRecall($requestId);
                    return;
                case 20:
                    $request->setMsgId(null);
                    $request->setSeqNo(null);
                    $this->methodRecall($requestId);
                    return;
                case 16:
                case 17:
                    $this->time_delta = ($message->getMsgId() >> 32) - time();
                    $this->API->logger('Set time delta to ' . $this->time_delta, Logger::WARNING);
                    $this->API->resetMTProtoSession("time delta update");
                    $this->shared->setTempAuthKey(null);
                    EventLoop::queue($this->shared->initAuthorization(...));
                    EventLoop::queue($this->methodRecall(...), $requestId);
                    return;
            }
            $this->handleReject($request, static fn () => RPCErrorException::make('Received bad_msg_notification: ' . MTProto::BAD_MSG_ERROR_CODES[$response['error_code']], $response['error_code'], $request->constructor));
            return;
        }

        if ($request->isMethod && $request->constructor !== 'auth.bindTempAuthKey'
            && $this->shared->hasTempAuthKey()
            && !$this->shared->getTempAuthKey()->isInited()
        ) {
            $this->shared->getTempAuthKey()->init(true);
        }
        if (isset($response['_']) && !$this->isCdn()) {
            $responseType = $this->API->getTL()->getConstructors()->findByPredicate($response['_'])['type'];
            if ($responseType === 'Updates') {
                $body = $request->getBodyOrEmpty();
                $trimmed = $body;
                if (isset($trimmed['peer']) && (
                    !\is_array($trimmed['peer'])
                    || (($trimmed['peer']['_'] ?? null) !== 'inputPhoneCall')
                )) {
                    try {
                        $trimmed['peer'] = \is_string($body['peer']) ? $body['peer'] : $this->API->getIdInternal($body['peer']);
                    } catch (Throwable $e) {
                    }
                }
                if (isset($trimmed['message'])) {
                    $trimmed['message'] = (string) $body['message'];
                }
                $response['request'] = ['_' => $request->constructor, 'body' => $trimmed];
                unset($body);
                EventLoop::queue($this->API->handleUpdates(...), $response);
            } elseif ($responseType === 'messages.SentEncryptedMessage') {
                $body = $request->getBodyOrEmpty();
                try {
                    $response = $this->API->getSecretChatController($body['peer'])->handleSent($body, $response);
                } catch (SecretPeerNotInDbException) {
                }
            }
        }
        $this->gotResponseForOutgoingMessage($request);

        $this->requestResponse?->inc([
            'method' => $request->constructor,
            'error_message' => 'OK',
            'error_code' => '200',
        ]);

        EventLoop::queue($request->reply(...), $response);
    }
    /**
     * @param array{error_message: string, error_code: int} $response
     * @return (callable(): Throwable)|null
     */
    private function handleRpcError(MTProtoOutgoingMessage $request, array $response): ?callable
    {
        $this->requestResponse?->inc([
            'method' => $request->constructor,
            'error_message' => preg_replace('/\d+/', 'X', $response['error_message']),
            'error_code' => (string) $response['error_code'],
        ]);

        if ($request->isMethod
            && $request->constructor !== 'auth.bindTempAuthKey'
            && $this->shared->hasTempAuthKey()
            && !$this->shared->getTempAuthKey()->isInited()
        ) {
            $this->shared->getTempAuthKey()->init(true);
        }
        if (\in_array($response['error_message'], ['PERSISTENT_TIMESTAMP_EMPTY', 'PERSISTENT_TIMESTAMP_INVALID'], true)) {
            return static fn () => new PTSException($response['error_message']);
        }
        if ($response['error_message'] === 'PERSISTENT_TIMESTAMP_OUTDATED') {
            $response['error_code'] = 500;
        }
        if (str_starts_with($response['error_message'], 'FILE_REFERENCE_')
            && !$request->shouldRefreshReferences()
        ) {
            $this->API->logger("Got {$response['error_message']}, refreshing file reference and repeating method call...");
            $this->gotResponseForOutgoingMessage($request);
            $msgId = $request->getMsgId();
            $request->setRefreshReferences(true);
            $request->setMsgId(null);
            $request->setSeqNo(null);
            $this->methodRecall($msgId);
            return null;
        }

        switch ($response['error_code']) {
            case 500:
            case -500:
            case -503:
                if ($request->previousQueuedMessage !== null &&
                    (
                        $response['error_message'] === 'MSG_WAIT_FAILED'
                        || $response['error_message'] === 'MSG_WAIT_TIMEOUT'
                    )
                ) {
                    $this->API->logger("Resending $request due to {$response['error_message']}");
                    $this->gotResponseForOutgoingMessage($request);
                    $msgId = $request->getMsgId();
                    $request->setSent(hrtime(true) + (5*60 * 1_000_000_000));
                    $request->setMsgId(null);
                    $request->setSeqNo(null);
                    $prev = $request->previousQueuedMessage;
                    if ($prev->hasReply()) {
                        $this->methodRecall($msgId);
                    } else {
                        $prev->getResultPromise()->finally(
                            fn () => $this->methodRecall($msgId)
                        );
                    }
                    return null;
                }
                if ((($response['error_code'] === -503 || $response['error_message'] === '-503') && !\in_array($request->constructor, ['messages.getBotCallbackAnswer', 'messages.getInlineBotResults'], true))
                    || (\in_array($response['error_message'], ['MSGID_DECREASE_RETRY', 'HISTORY_GET_FAILED', 'RPC_CONNECT_FAILED', 'RPC_CALL_FAIL', 'RPC_MCGET_FAIL', 'PERSISTENT_TIMESTAMP_OUTDATED', 'RPC_MCGET_FAIL', 'no workers running', 'No workers running'], true))) {
                    $this->API->logger("Resending $request in 1 second due to {$response['error_message']}");
                    $msgId = $request->getMsgId();
                    $request->setMsgId(null);
                    $request->setSeqNo(null);
                    EventLoop::delay(1.0, fn () => $this->methodRecall($msgId));
                    return null;
                }
                return static fn () => RPCErrorException::make($response['error_message'], $response['error_code'], $request->constructor);
            case 303:
                $datacenter = (int) preg_replace('/[^0-9]+/', '', $response['error_message']);
                if ($this->API->isTestMode()) {
                    $datacenter += 10_000;
                }
                if ($request->fileRelated) {
                    return fn () => new FileRedirect(
                        $this->API->datacenter->has(-$datacenter)
                            ? -$datacenter
                            : $datacenter
                    );
                }
                $this->API->datacenter->currentDatacenter = $datacenter;
                if ($request->userRelated) {
                    $this->API->authorized_dc = $this->API->datacenter->currentDatacenter;
                }
                $this->API->logger("Resending $request to new DC $datacenter...");
                $this->methodRecall($request->getMsgId(), $datacenter);
                return null;
            case 400:
                if ($request->previousQueuedMessage &&
                    (
                        $response['error_message'] === 'MSG_WAIT_FAILED'
                        || $response['error_message'] === 'MSG_WAIT_TIMEOUT'
                    )
                ) {
                    $this->API->logger("Resending $request due to {$response['error_message']}");
                    $this->gotResponseForOutgoingMessage($request);
                    $msgId = $request->getMsgId();
                    $request->setSent(hrtime(true) + (5*60 * 1_000_000_000));
                    $request->setMsgId(null);
                    $request->setSeqNo(null);
                    \assert($msgId !== null);
                    $prev = $request->previousQueuedMessage;
                    if ($prev->hasReply()) {
                        $this->methodRecall($msgId);
                    } else {
                        $prev->getResultPromise()->finally(
                            fn () => $this->methodRecall($msgId)
                        );
                    }
                    return null;
                }
                return static fn () => RPCErrorException::make($response['error_message'], $response['error_code'], $request->constructor);
            case 401:
                switch ($response['error_message']) {
                    case 'USER_DEACTIVATED':
                    case 'USER_DEACTIVATED_BAN':
                    case 'SESSION_REVOKED':
                    case 'SESSION_EXPIRED':
                        $this->API->logger($response['error_message'], Logger::FATAL_ERROR);
                        $phone = null;
                        if (\in_array($response['error_message'], ['USER_DEACTIVATED', 'USER_DEACTIVATED_BAN'], true)) {
                            $phone = isset($this->API->authorization['user']['phone']) ? '+' . $this->API->authorization['user']['phone'] : '???';
                            $this->API->logger(sprintf(Lang::$current_lang['account_banned'], $phone), Logger::FATAL_ERROR);
                        }
                        $this->API->logout();
                        return static fn () => new SignalException(sprintf(Lang::$current_lang['account_banned'], $phone ?? '?'));
                    case 'AUTH_KEY_UNREGISTERED':
                    case 'AUTH_KEY_INVALID':
                        if ($this->API->authorized !== \danog\MadelineProto\API::LOGGED_IN) {
                            $this->gotResponseForOutgoingMessage($request);
                            EventLoop::queue(
                                $this->handleReject(...),
                                $request,
                                static fn () => RPCErrorException::make($response['error_message'], $response['error_code'], $request->constructor)
                            );
                            return null;
                        }
                        $this->session_id = null;
                        $this->session_in_seq_no = 0;
                        $this->session_out_seq_no = 0;
                        $this->shared->setTempAuthKey(null);
                        $this->shared->setPermAuthKey(null);
                        $this->API->logger("Auth key not registered in DC {$this->datacenter} with RPC error {$response['error_message']}, resetting temporary and permanent auth keys...", Logger::ERROR);
                        if ($this->API->authorized_dc == $this->datacenter && $this->API->authorized === \danog\MadelineProto\API::LOGGED_IN) {
                            $this->API->logger('Permanent auth key was main authorized key, logging out...', Logger::FATAL_ERROR);
                            $phone = isset($this->API->authorization['user']['phone']) ? '+' . $this->API->authorization['user']['phone'] : 'you are currently using';
                            $this->API->logger(sprintf(Lang::$current_lang['account_banned'], $phone), Logger::FATAL_ERROR);
                            $this->API->logout();
                            return static fn () => new SignalException(sprintf(Lang::$current_lang['account_banned'], $phone));
                        }
                        EventLoop::queue($this->shared->initAuthorization(...));
                        EventLoop::queue($this->methodRecall(...), $request->getMsgId());
                        return null;
                    case 'AUTH_KEY_PERM_EMPTY':
                        $this->API->logger('Temporary auth key not bound, resetting temporary auth key...', Logger::ERROR);
                        $this->shared->setTempAuthKey(null);
                        EventLoop::queue($this->shared->initAuthorization(...));
                        EventLoop::queue($this->methodRecall(...), $request->getMsgId());
                        return null;
                }
                return static fn () => RPCErrorException::make($response['error_message'], $response['error_code'], $request->constructor);
            case 420:
                $seconds = (int) preg_replace('/[^0-9]+/', '', $response['error_message']);
                $limit = $request->floodWaitLimit ?? $this->API->settings->getRPC()->getFloodTimeout();
                if ($seconds < $limit) {
                    $this->API->logger("Flood, waiting $seconds seconds before repeating async call of $request...", Logger::NOTICE);
                    $this->gotResponseForOutgoingMessage($request);
                    $msgId = $request->getMsgId();
                    $request->setSent(hrtime(true) + ($seconds * 1_000_000_000));
                    $request->setMsgId(null);
                    $request->setSeqNo(null);
                    \assert($msgId !== null);
                    $id = EventLoop::delay((float) $seconds, fn () => $this->methodRecall($msgId));
                    $request->cancellation?->subscribe(static fn () => EventLoop::cancel($id));
                    return null;
                }
                if (str_starts_with($response['error_message'], 'FLOOD_WAIT_')) {
                    return static fn () => new FloodWaitError(
                        $response['error_message'],
                        $seconds,
                        $response['error_code'],
                        $request->constructor
                    );
                }
                if (str_starts_with($response['error_message'], 'FLOOD_PREMIUM_WAIT_')) {
                    return static fn () => new FloodPremiumWaitError(
                        $response['error_message'],
                        $seconds,
                        $response['error_code'],
                        $request->constructor
                    );
                }
                return static fn () => new RateLimitError(
                    $response['error_message'],
                    $seconds,
                    $response['error_code'],
                    $request->constructor
                );
            default:
                return static fn () => RPCErrorException::make($response['error_message'], $response['error_code'], $request->constructor);
        }
    }
}
