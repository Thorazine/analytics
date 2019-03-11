<?php

namespace Thorazine\Analytics;

use Carbon\Carbon;
use Google_Client;
use Google_Service_Analytics;
use Illuminate\Support\Collection;
use Cache;

class Analytics
{

    public $timezone;

    /** @var string */
    protected $viewId;

    /** @var string */
    protected $developerKey;

    /** @var string */
    protected $startDate;

    /** @var string */
    protected $endDate;


    public function __construct()
    {
        $this->timezone = config('app.timezone');
        $this->reset();
    }


    public function client($applicationName, $developerKey, $viewId)
    {
        $this->viewId = $viewId;

        $client = new Google_Client();
        $client->setApplicationName($applicationName);
        $client->setAuthConfig($developerKey);

        $client->setScopes([
            Google_Service_Analytics::ANALYTICS_READONLY,
        ]);
        $this->analytics = new Google_Service_Analytics($client);

        return $this;
    }

    /**
     * Get "realtime" data
     * @param  string $metrics    rt:activeUsers
     * @param  string $dimensions rt:pagePath
     * @param  string $filters    rt:pagePath=~/tests/22/question
     * @return array
     */
    public function realtime($metrics = '', $dimensions = '', $filters = '')
    {
        $params = $this->getParams($metrics, $dimensions, $filters);

        $response = $this->analytics->data_realtime->call('get', [['ids' => "ga:".$this->viewId]+$params], 'Google_Service_Analytics_RealtimeData');

        $this->reset();

        return [
            'total' => (int)$response['totalResults'],
            'rows' => ($response['rows'] ?? []),
        ];
    }

    /**
     * Get periodical data
     * @param  string $metrics    rt:activeUsers
     * @param  string $metrics    rt:activeUsers
     * @param  string $metrics    rt:activeUsers
     * @param  string $dimensions rt:pagePath
     * @param  string $filters    rt:pagePath=~/tests/22/question
     * @return array
     */
    public function period($metrics = '', $dimensions = '', $filters = '', $from = '', $to = '', $cacheTime = null)
    {
        $params = $this->getParams($metrics, $dimensions, $filters);
        $this->endDate = ($to) ? $to : $this->endDate->format('Y-m-d');
        $this->startDate = ($from) ? $from : $this->startDate->format('Y-m-d');

        $result = Cache::remember($this->cacheKey($metrics, $params), 0, function() use ($metrics, $params) {
            $response = $this->analytics->data_ga->get(
                'ga:'.$this->viewId,
                $this->startDate,
                $this->endDate,
                $metrics,
                $params
            );

            return ($response['rows'] ?? []);
        });

        $this->reset();

        return $result;
    }


    private function getParams($metrics, $dimensions, $filters)
    {
        $params = [];
        $params = ($metrics) ? array_merge($params, ['metrics' => $metrics]) : $params;
        $params = ($dimensions) ? array_merge($params, ['dimensions' => $dimensions]) : $params;
        $params = ($filters) ? array_merge($params, ['filters' => $filters]) : $params;
        return $params;
    }


    public function days(int $numberOfDays): self
    {
        $this->endDate = Carbon::today()->subDays(1)->startOfDay(); // do not include today
        $this->startDate = Carbon::today()->subDays($numberOfDays)->startOfDay();
        return $this;
    }

    public function months(int $numberOfMonths): self
    {
        $this->endDate = Carbon::today()->subDays(1)->startOfDay(); // do not include today
        $this->startDate = Carbon::today()->subMonths($numberOfMonths)->startOfDay();
        return $this;
    }

    public function years(int $numberOfYears): self
    {
        $this->endDate = Carbon::today()->subDays(1)->startOfDay(); // do not include today
        $this->startDate = Carbon::today()->subYears($numberOfYears)->startOfDay();
        return $this;
    }

    public function timezone($timezone)
    {
        $this->timezone = $timezone;
        return $this;
    }


    /**
     * Calculate the time till the end of the day
     * @return [type] [description]
     */
    private function calculateSecondsTillEndOfDayWithUsersTimezone()
    {
        // get user timezone
        // $timezone = DateTimeZone::listIdentifiers(DateTimeZone::ALL)[Cms::user()->timezone];
        // calculate seconds till end of day in the timezone of the user
        return Carbon::now($this->timezone)->endOfDay()->diffInSeconds(Carbon::now($this->timezone));
    }

    /**
     * Create a cache key
     * @param  string $metrics
     * @param  mixed $params
     * @return string
     */
    private function cacheKey($metrics, $params)
    {
        $variables = [Carbon::now($this->timezone)->utcOffset(), $this->startDate, $this->endDate, $this->viewId, $params, $metrics];
        return serialize($variables);
    }

    /**
     * Reset the main functionality after a run
     * @return class this
     */
    private function reset()
    {
        $this->days(7);
        $this->cacheTime = 0;
        return $this;
    }

    /**
     * Make it easy to extend with your own functions on this class
     * @return class this
     */
    public function extend()
    {
        return $this;
    }
}
