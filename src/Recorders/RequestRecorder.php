<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
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
                'size' => strlen($request->getContent())
            ],
            'response' => [
                'status_code' => $event->response->status(),
                'size' => strlen($event->response->getContent())
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

    private function isBot(Request $request)
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
        $user = Auth::user();

        return [
            'id' => $user ? $user->id : null,
            'name' => $user ? ( config('laritor.anonymize.user') ? 'User '.$user->id : $user->name) : '',
            'email' => $user ? ( config('laritor.anonymize.user') ? 'user'.$user->id.'@laritor.com' : $user->email) : '',
        ];
    }

    private function getUrl($request)
    {
        if (config('laritor.requests.query_string')) {
            $query = $request->getQueryString();

            $question = $request->getPathInfo() === '/' ? '/?' : '?';

            return $query ? $question.$query : $request->path();
        }

        return $request->path();
    }

    public function shouldRecordRequest(Request $request, $isBot)
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
