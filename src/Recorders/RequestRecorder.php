<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Str;

class RequestRecorder extends Recorder
{
    /**
     * @var string
     */
    public static $eventType = 'requests';

    /**
     * @var string[]
     */
    public static $events = [
        RequestHandled::class
    ];

    /**
     * Handle the event.
     *
     * @param  RequestHandled $event
     * @return void
     */
    public function trackEvent($event)
    {
        $request = $event->request;
        $response = $event->response;

        $isBot = FilterHelper::isBot($request);

        $this->laritor->responseRenderCompleted(isset($event->response->exception) ? $event->response->exception : null);

        $duration = $this->laritor->getDurationFromStart();

        $session = [
            'id' => null,
            'name' => null,
            'data' => []
        ];

        if ($request->hasSession()) {
            $session['id'] = $request->session()->getId();
            $session['name'] = $request->session()->getName();
            $session['data'] = config('laritor.session.data') ? $request->session()->all() : [];
        }

        /** @phpstan-ignore-next-line  */
        $controller = $request->route() ? explode('@', optional($request->route())->getActionName()) : [];
        $this->laritor->pushEvent(static::$eventType, [
            'request_instance' => $request,
            'response_instance' => $response,
            'request' => [
                'started_at' => now()->subMilliseconds($duration)->format('Y-m-d H:i:s'),
                'completed_at' => now()->format('Y-m-d H:i:s'),
                'duration' => $duration,
                'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 1),
                'url' => $this->getUrl($request),
                'size' => strlen($request->getContent()),
                'headers' => $this->getRequestHeaders($request),
                'body' => $this->getRequestBody($request),
            ],
            'response' => [
                'status_code' => $this->getStatusCode($response),
                'size' => strlen($response->getContent()),
                'headers' => $this->getResponseHeaders($response),
                'body' => $this->getResponseBody($response),
            ],
            'session' => $session,
            'user' => [
                'authenticated' => $this->getAuthenticatedUser(),
                'ip' => DataHelper::redactIPAddress($request->getClientIp()),
                'user_agent' => DataHelper::redactUserAgent($request->userAgent()),
                'is_bot' => $isBot,
            ],
            'route' => [
                /** @phpstan-ignore-next-line  */
                'name' => optional($request->route())->getName(),
                /** @phpstan-ignore-next-line  */
                'uri' => optional($request->route())->uri(),
                'controller' => isset($controller[0]) ? $controller[0] : 'closure',
                'controller_method' => isset($controller[1]) ? $controller[1] : 'closure',
                'method' => $request->method(),
            ],
            'custom_context' => $this->getContext($request),
        ]);
    }

    private function getStatusCode($response)
    {
        if (method_exists($response, 'status')) {
            return $response->status();
        }

        return $response->getStatusCode();
    }

    private function getContext($request)
    {
        $context = [];

        if ($this->isLivewireUpdateRequest($request)) {
            $components = $request->input('components', []);
            if (is_array($components)) {
                foreach ($components as $component) {
                    if (isset($component['snapshot'])) {
                        $snapshot = json_decode($component['snapshot'], true);
                        $context['livewire-components'][] = isset($snapshot['memo']['name']) ? $snapshot['memo']['name'] : '';
                    }
                }
            }
        }

        return array_merge($context, DataHelper::getRedactedContext());
    }

    protected function getRequestBody($request)
    {
        if (config('laritor.requests.body')) {
            $payload = $request->post();
            return ! empty($payload) ?
                DataHelper::redactArray($payload) :
                DataHelper::redactData(trim($request->getContent()));
        }

        return false;
    }

    protected function getRequestHeaders($request)
    {
        if (config('laritor.requests.headers')) {
            return DataHelper::redactHeaders($request->headers->all());
        }

        return false;
    }

    protected function getResponseBody($response)
    {
        if (config('laritor.requests.response_body')) {

            $body = $response->getContent();

            $json = json_decode($body, true);

            if (is_array($json)) {
                return DataHelper::redactArray($json);
            }

            return DataHelper::redactData($body);
        }

        return false;
    }

    protected function getResponseHeaders($response)
    {
        if (config('laritor.requests.response_headers')) {
            return DataHelper::redactHeaders($response->headers->all());
        }

        return false;
    }

    private function getAuthenticatedUser()
    {
        $user = DataHelper::getRedactedUser();

        $user = is_array($user) ? $user : [];

        if (!isset($user['id'])) {
            $user['id'] = null;
        }

        if (!isset($user['name'])) {
            $user['name'] = null;
        }

        if (!isset($user['email'])) {
            $user['email'] = null;
        }

        return $user;
    }

    private function getUrl($request)
    {
        if ($this->isLivewireUpdateRequest($request)) {
            $url = '';
            $referer = $request->headers->get('referer');
            if ($referer) {
                $fragments = parse_url($referer);
                if (isset($fragments['path'])) {
                    $url = rtrim($fragments['path'], '/');
                }

                if (config('laritor.requests.query_string') && isset($fragments['query'])) {
                    $url .= '?' . $fragments['query'];
                }

                if ($url) {
                    return $url;
                }
            }
        }

        $query = '';
        if (config('laritor.requests.query_string')) {
            $query = $request->getQueryString();

            $query = $query ? '?'.$query : '';
        }

        return $request->path().$query;
    }

    public function isLivewireUpdateRequest($request): bool
    {
        $route = $request->route() ? $request->route()->getName() : '';

        return ($route && Str::endsWith($route, 'livewire.update')) ||
            preg_match('#^livewire(?:-[^/]+)?/update$#', ltrim($request->path(), '/')) === 1;
    }
}
