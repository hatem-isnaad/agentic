<?php

namespace Agentic\Http\Controllers\Channels;

use Agentic\Channels\ChannelAccountLocator;
use Agentic\Channels\ChannelInboundService;
use Agentic\Channels\Messenger\MessengerWebhookParser;
use Agentic\Channels\WhatsApp\MetaWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class MessengerMetaWebhookController
{
    public function __construct(
        private ChannelAccountLocator $accounts,
        private MessengerWebhookParser $parser,
        private MetaWebhookVerifier $verifier,
        private ChannelInboundService $inbound,
    ) {}

    public function verify(Request $request): Response
    {
        $hub = $this->hubQuery($request);
        $mode = $hub['mode'];
        $token = $hub['token'];
        $challenge = $hub['challenge'];

        if ($mode !== 'subscribe' || ! $this->accounts->matchesMessengerVerifyToken($token)) {
            return response('Forbidden', SymfonyResponse::HTTP_FORBIDDEN);
        }

        return response($challenge, SymfonyResponse::HTTP_OK)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request): Response
    {
        $payload = $request->all();
        $messages = $this->parser->inboundTexts(is_array($payload) ? $payload : []);
        $account = $this->accounts->findMessengerMeta((string) ($messages[0]['page_id'] ?? ''));

        if ((bool) config('agentic.channels.verify_signatures', true)
            && ! $this->verifier->validSignature($request->getContent(), (string) $request->header('X-Hub-Signature-256', ''), $account)) {
            return response('Forbidden', SymfonyResponse::HTTP_FORBIDDEN);
        }

        foreach ($messages as $message) {
            $row = $this->accounts->findMessengerMeta($message['page_id']);
            if ($row === null) {
                continue;
            }
            $this->inbound->handleText($row, $message['from'], $message['text']);
        }

        return response('EVENT_RECEIVED', SymfonyResponse::HTTP_OK);
    }

    /** @return array{mode: string, token: string, challenge: string} */
    private function hubQuery(Request $request): array
    {
        $hub = $request->query('hub');

        return [
            'mode' => (string) (is_array($hub) ? ($hub['mode'] ?? '') : $request->query('hub_mode', '')),
            'token' => (string) (is_array($hub) ? ($hub['verify_token'] ?? '') : $request->query('hub_verify_token', '')),
            'challenge' => (string) (is_array($hub) ? ($hub['challenge'] ?? '') : $request->query('hub_challenge', '')),
        ];
    }
}
