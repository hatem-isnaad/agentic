<?php

namespace Agentic\Http\Controllers\Channels;

use Agentic\Channels\ChannelAccountLocator;
use Agentic\Channels\ChannelInboundService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class WhatsAppWebJsWebhookController
{
    public function __construct(
        private ChannelAccountLocator $accounts,
        private ChannelInboundService $inbound,
    ) {}

    public function receive(Request $request): Response
    {
        $session = (string) $request->input('session', '');
        $from = (string) $request->input('from', $request->input('phone', ''));
        $text = trim((string) $request->input('text', $request->input('message', '')));
        $account = $this->accounts->findWhatsAppWebJs($session);

        if ($account === null) {
            return response('Unknown session', SymfonyResponse::HTTP_NOT_FOUND);
        }

        if ((bool) config('agentic.channels.verify_signatures', true)
            && ! $this->accounts->matchesWebJsSecret($account, (string) $request->header('X-Agentic-Channel-Secret', ''))) {
            return response('Forbidden', SymfonyResponse::HTTP_FORBIDDEN);
        }

        if ($from === '' || $text === '') {
            return response('Invalid payload', SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->inbound->handleText($account, $from, $text);

        return response('EVENT_RECEIVED', SymfonyResponse::HTTP_OK);
    }
}
