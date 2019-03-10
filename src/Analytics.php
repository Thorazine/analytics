<?php

namespace Thorazine\Analytics;

use Carbon\Carbon;
use Google_Client;
use Google_Service_Analytics;
use Illuminate\Support\Collection;
use Cache;

class Analytics
{


    /** @var string */
    protected $viewId;

    /** @var string */
    protected $developerKey;


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

        return ($response['rows'] ?? []);
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
    public function period($metrics = '', $dimensions = '', $filters = '', $cacheTime = null)
    {
        $params = $this->getParams($metrics, $dimensions, $filters);
        $endDate = ($to) ? $to : $this->endDate->format('Y-m-d');
        $startDate = ($from) ? $from : $this->startDate->format('Y-m-d');

        $result = Cache::remember('key', 0, function() use ($metrics, $params, $startDate, $endDate) {
            $response = $this->analytics->data_ga->get(
                'ga:'.$this->viewId,
                $startDate,
                $endDate,
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
    }
}
