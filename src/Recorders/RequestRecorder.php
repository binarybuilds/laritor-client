<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Auth;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

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

        $isBot = $this->isBot($request);

        if ( ! $this->shouldRecordRequest($request, $isBot)) {
            return;
        }

        $this->laritor->responseRenderCompleted($event->response->exception);

        $startTime = defined('LARAVEL_START') ? LARAVEL_START : $event->request->server('REQUEST_TIME_FLOAT');
        $duration =  $startTime ? floor((microtime(true) - $startTime) * 1000) : null;

        $controller = $request->route() ? explode('@', optional($request->route())->getActionName()) : [];
        $this->laritor->pushEvent(static::$eventType, [
            'request' => [
                'completed_at' => now()->format('Y-m-d H:i:s'),
                'duration' => $duration,
                'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 1),
                'url' => $this->getUrl($request),
                'size' => strlen($request->getContent()),
                'headers' => $this->getRequestHeaders($request),
                'body' => $this->getRequestBody($request),
            ],
            'response' => [
                'status_code' => $event->response->status(),
                'size' => strlen($event->response->getContent()),
                'headers' => $this->getResponseHeaders($event->response),
                'body' => $this->getResponseBody($event->response),
            ],
            'user' => [
                'authenticated' => $this->getAuthenticatedUser(),
                'ip' => config('laritor.anonymize.ip') ? '127.0.0.1' : $request->getClientIp(),
                'user_agent' => config('laritor.anonymize.user_agent') ? 'anonymous-agent' : $request->userAgent(),
                'is_bot' => $isBot,
            ],
            'route' => [
                'name' => optional($request->route())->getName(),
                'uri' => optional($request->route())->uri(),
                'controller' => isset($controller[0]) ? $controller[0] : 'closure',
                'controller_method' => isset($controller[1]) ? $controller[1] : 'closure',
                'method' => $request->method(),
            ],
        ]);
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

    private function isBot($request)
    {
        $userAgent = $request->userAgent();
        $crawler = new CrawlerDetect();
        $isBot = $crawler->isCrawler($userAgent);

        return $isBot &&
            ! in_array($request->userAgent(), (array)config('laritor.bots.whitelist.user_agents') ) &&
           ! in_array($request->ip(), (array)config('laritor.bots.whitelist.ips') );
    }

    private function getAuthenticatedUser()
    {
        $user = DataHelper::getRedactedUser();

        return [
            'id' => isset($user['id']) ? $user['id'] : null,
            'name' =>  isset($user['name']) ? $user['name'] : null,
            'email' =>  isset($user['email']) ? $user['email'] : null,
        ];
    }

    private function getUrl($request)
    {
        $query = '';
        if (config('laritor.requests.query_string')) {
            $query = $request->getQueryString();

            $query = $query ? '?'.$query : '';
        }

        return $request->path().$query;
    }

    public function shouldRecordRequest($request, $isBot)
    {
        foreach ((array)config('laritor.requests.ignore') as $ignore) {
            if ($request->is($ignore)) {
                return false;
            }
        }

        if ($isBot && config('laritor.bots.ignore')) {
            return false;
        }

        return true;
    }
}
