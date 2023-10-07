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
use danog\MadelineProto\FileRedirect;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\MTProtoIncomingMessage;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\PTSException;
use danog\MadelineProto\RPCError\FloodWaitError;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\SecretPeerNotInDbException;
use Revolt\EventLoop;
use Throwable;

use const PHP_EOL;

/**
 * Manages responses.
 *
 * @internal
 */
trait ResponseHandler
{
    public function handleMessages(): void
    {
        while ($this->new_incoming) {
            reset($this->new_incoming);
            $current_msg_id = key($this->new_incoming);

            /** @var MTProtoIncomingMessage */
            $message = $this->new_incoming[$current_msg_id];
            unset($this->new_incoming[$current_msg_id]);

            $this->API->logger($message->log($this->datacenter), Logger::ULTRA_VERBOSE);

            $type = $message->getPredicate();
            if ($type !== 'msg_container') {
                $this->checkInSeqNo($message);
            }
            switch ($type) {
                case 'msgs_ack':
                    foreach ($message->read()['msg_ids'] as $msg_id) {
                        // Acknowledge that the server received my message
                        $this->ackOutgoingMessageId($msg_id);
                    }
                    break;
                case 'rpc_result':
                    $this->ackIncomingMessage($message);
                    // no break
                case 'future_salts':
                case 'msgs_state_info':
                case 'bad_server_salt':
                case 'bad_msg_notification':
                case 'pong':
                    $this->handleResponse($message);
                    break;
                case 'new_session_created':
                    $this->ackIncomingMessage($message);
                    $this->shared->getTempAuthKey()->setServerSalt($message->read()['server_salt']);
                    if ($this->API->authorized === \danog\MadelineProto\API::LOGGED_IN
                        && isset($this->API->updaters[UpdateLoop::GENERIC])
                    ) {
                        $this->API->updaters[UpdateLoop::GENERIC]->resume();
                    }
                    break;
                case 'msg_container':
                    foreach ($message->read()['messages'] as $msg) {
                        $this->msgIdHandler->checkMessageId($msg['msg_id'], outgoing: false, container: true);
                        $newMessage = new MTProtoIncomingMessage($msg['body'], $msg['msg_id'], $message->unencrypted, true);
                        $newMessage->setSeqNo($msg['seqno']);
                        $this->checkInSeqNo($newMessage);
                        $newMessage->setSeqNo(null);
                        $this->new_incoming[$msg['msg_id']] = $this->incoming_messages[$msg['msg_id']] = $newMessage;
                    }
                    $this->checkInSeqNo($message);
                    unset($newMessage, $message, $msg);
                    ksort($this->new_incoming);
                    break;
                case 'msg_copy':
                    $this->ackIncomingMessage($message);
                    $content = $message->read();
                    $referencedMsgId = $content['msg_id'];
                    if (isset($this->incoming_messages[$referencedMsgId])) {
                        $this->ackIncomingMessage($this->incoming_messages[$referencedMsgId]);
                    } else {
                        $this->msgIdHandler->checkMessageId($referencedMsgId, outgoing: false, container: true);
                        $message = new MTProtoIncomingMessage($content['orig_message'], $referencedMsgId, $message->unencrypted);
                        $this->new_incoming[$referencedMsgId] = $this->incoming_messages[$referencedMsgId] = $message;
                        unset($message);
                    }
                    unset($content, $referencedMsgId);
                    break;
                case 'http_wait':
                    $this->API->logger($message->read(), Logger::NOTICE);
                    break;
                case 'msgs_state_req':
                    $this->sendMsgsStateInfo($message->read()['msg_ids'], $current_msg_id);
                    break;
                case 'msgs_all_info':
                    $this->onMsgsAllInfo($message->read());
                    break;
                case 'msg_detailed_info':
                    $this->onMsgDetailedInfo($message->read());
                    break;
                case 'msg_new_detailed_info':
                    $this->onNewMsgDetailedInfo($message->read());
                    break;
                case 'msg_resend_req':
                    $this->onMsgResendReq($message->read(), $current_msg_id);
                    break;
                case 'msg_resend_ans_req':
                    $this->onMsgResendAnsReq($message->read(), $current_msg_id);
                    break;
                default:
                    $this->ackIncomingMessage($message);
                    $response_type = $this->API->getTL()->getConstructors()->findByPredicate($message->getContent()['_'])['type'];
                    if ($response_type == 'Updates') {
                        if (!$this->isCdn()) {
                            EventLoop::queue($this->API->handleUpdates(...), $message->read());
                        }
                        break;
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
                            break 2;
                        }
                        $this->API->logger('No', Logger::VERBOSE);
                    }
                    $this->API->logger('Dunno how to handle ' . PHP_EOL . var_export($message->read(), true), Logger::FATAL_ERROR);
                    break;
            }
        }
        $this->new_incoming = [];
        if ($this->pendingOutgoing) {
            $this->flush();
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
        if (!isset($this->outgoing_messages[$requestId])) {
            $this->API->logger("Got a reponse $message with message ID $requestId, but there is no request!", Logger::FATAL_ERROR);
            return;
        }
        /** @var MTProtoOutgoingMessage */
        $request = $this->outgoing_messages[$requestId];
        if ($request->getState() & MTProtoOutgoingMessage::STATE_REPLIED) {
            $this->API->logger("Already got a response to $request, but there is another reply $message with message ID $requestId!", Logger::FATAL_ERROR);
            return;
        }
        if ($response['_'] === 'rpc_result') {
            $response = $response['result'];
        }
        $constructor = $response['_'] ?? '';
        if ($constructor === 'rpc_error') {
            try {
                $exception = $this->handleRpcError($request, $response);
            } catch (Throwable $e) {
                $exception = fn () => $e;
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
                    $this->methodRecall(message_id: $requestId, postpone: true);
                    return;
                case 20:
                    $request->setMsgId(null);
                    $request->setSeqNo(null);
                    $this->methodRecall(message_id: $requestId, postpone: true);
                    return;
                case 16:
                case 17:
                    $this->time_delta = ($message->getMsgId() >> 32) - time();
                    $this->API->logger('Set time delta to ' . $this->time_delta, Logger::WARNING);
                    $this->API->resetMTProtoSession();
                    $this->shared->setTempAuthKey(null);
                    EventLoop::queue($this->shared->initAuthorization(...));
                    EventLoop::queue($this->methodRecall(...), $requestId);
                    return;
            }
            $this->handleReject($request, fn () => new RPCErrorException('Received bad_msg_notification: ' . MTProto::BAD_MSG_ERROR_CODES[$response['error_code']], $response['error_code'], $request->getConstructor()));
            return;
        }

        if ($request->isMethod && $request->getConstructor() !== 'auth.bindTempAuthKey'
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
                $response['request'] = ['_' => $request->getConstructor(), 'body' => $trimmed];
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

        EventLoop::queue($request->reply(...), $response);
    }
    /**
     * @return (callable(): Throwable)|null
     */
    private function handleRpcError(MTProtoOutgoingMessage $request, array $response): ?callable
    {
        if ($request->isMethod
            && $request->getConstructor() !== 'auth.bindTempAuthKey'
            && $this->shared->hasTempAuthKey()
            && !$this->shared->getTempAuthKey()->isInited()
        ) {
            $this->shared->getTempAuthKey()->init(true);
        }
        if (\in_array($response['error_message'], ['PERSISTENT_TIMESTAMP_EMPTY', 'PERSISTENT_TIMESTAMP_INVALID'], true)) {
            return fn () => new PTSException($response['error_message']);
        }
        if ($response['error_message'] === 'PERSISTENT_TIMESTAMP_OUTDATED') {
            $response['error_code'] = 500;
        }
        if (str_starts_with($response['error_message'], 'FILE_REFERENCE_')) {
            $this->API->logger("Got {$response['error_message']}, refreshing file reference and repeating method call...");
            $this->gotResponseForOutgoingMessage($request);
            $msgId = $request->getMsgId();
            $request->setRefreshReferences(true);
            $request->setMsgId(null);
            $request->setSeqNo(null);
            $this->methodRecall(message_id: $msgId, postpone: true);
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
                    $request->setSent(time() + 5*60);
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
                if ((($response['error_code'] === -503 || $response['error_message'] === '-503') && !\in_array($request->getConstructor(), ['messages.getBotCallbackAnswer', 'messages.getInlineBotResults'], true))
                    || (\in_array($response['error_message'], ['MSGID_DECREASE_RETRY', 'HISTORY_GET_FAILED', 'RPC_CONNECT_FAILED', 'RPC_CALL_FAIL', 'RPC_MCGET_FAIL', 'PERSISTENT_TIMESTAMP_OUTDATED', 'RPC_MCGET_FAIL', 'no workers running', 'No workers running'], true))) {
                    $this->API->logger("Resending $request in 1 second due to {$response['error_message']}");
                    $msgId = $request->getMsgId();
                    $request->setMsgId(null);
                    $request->setSeqNo(null);
                    EventLoop::delay(1.0, fn () => $this->methodRecall($msgId));
                    return null;
                }
                return fn () => new RPCErrorException($response['error_message'], $response['error_code'], $request->getConstructor());
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
                EventLoop::queue(closure: $this->methodRecall(...), message_id: $request->getMsgId(), datacenter: $datacenter);
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
                    $request->setSent(time() + 5*60);
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
                return fn () => new RPCErrorException($response['error_message'], $response['error_code'], $request->getConstructor());
            case 401:
                switch ($response['error_message']) {
                    case 'USER_DEACTIVATED':
                    case 'USER_DEACTIVATED_BAN':
                    case 'SESSION_REVOKED':
                    case 'SESSION_EXPIRED':
                        $this->API->logger($response['error_message'], Logger::FATAL_ERROR);
                        if (\in_array($response['error_message'], ['USER_DEACTIVATED', 'USER_DEACTIVATED_BAN'], true)) {
                            $phone = isset($this->API->authorization['user']['phone']) ? '+' . $this->API->authorization['user']['phone'] : '???';
                            $this->API->logger(sprintf(Lang::$current_lang['account_banned'], $phone), Logger::FATAL_ERROR);
                        }
                        $this->API->logout();
                        throw new SignalException(sprintf(Lang::$current_lang['account_banned'], $phone ?? '?'));
                    case 'AUTH_KEY_UNREGISTERED':
                    case 'AUTH_KEY_INVALID':
                        if ($this->API->authorized !== \danog\MadelineProto\API::LOGGED_IN) {
                            $this->gotResponseForOutgoingMessage($request);
                            EventLoop::queue(
                                $this->handleReject(...),
                                $request,
                                fn () => new RPCErrorException($response['error_message'], $response['error_code'], $request->getConstructor())
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
                            throw new SignalException(sprintf(Lang::$current_lang['account_banned'], $phone));
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
                return fn () => new RPCErrorException($response['error_message'], $response['error_code'], $request->getConstructor());
            case 420:
                $seconds = preg_replace('/[^0-9]+/', '', $response['error_message']);
                $limit = $request->floodWaitLimit ?? $this->API->settings->getRPC()->getFloodTimeout();
                if (is_numeric($seconds) && $seconds < $limit) {
                    $this->API->logger("Flood, waiting $seconds seconds before repeating async call of $request...", Logger::NOTICE);
                    $this->gotResponseForOutgoingMessage($request);
                    $msgId = $request->getMsgId();
                    $request->setSent(time() + $seconds);
                    $request->setMsgId(null);
                    $request->setSeqNo(null);
                    \assert($msgId !== null);
                    $id = EventLoop::delay((float) $seconds, fn () => $this->methodRecall($msgId));
                    $request->cancellation?->subscribe(fn () => EventLoop::cancel($id));
                    return null;
                }
                if (str_starts_with($response['error_message'], 'FLOOD_WAIT_')) {
                    return fn () => new FloodWaitError($response['error_message'], $response['error_code'], $request->getConstructor());
                }
                // no break
            default:
                return fn () => new RPCErrorException($response['error_message'], $response['error_code'], $request->getConstructor());
        }
    }
}
