<?php

namespace Spatie\Analytics;

use Carbon\Carbon;
use Google_Service_Analytics;
use Illuminate\Support\Traits\Macroable;

class Analytics
{
    use Macroable;

    /** @var \Spatie\Analytics\AnalyticsClient */
    protected $client;

    /** @var string */
    protected $viewId;

    /**
     * @param \Spatie\Analytics\AnalyticsClient $client
     * @param string                            $viewId
     */
    public function __construct(Client $client, string $viewId)
    {
        $this->client = $client;

        $this->viewId = $viewId;
    }

    /**
     * @param string $viewId
     *
     * @return $this
     */
    public function setViewId(string $viewId)
    {
        $this->viewId = $viewId;

        return $this;
    }

    public function fetchVisitorsAndPageViews(Period $period): Collection
    {
        $response = $this->performQuery(
            $period,
            'ga:users,ga:pageviews',
            ['dimensions' => 'ga:date,ga:pageTitle']
        );

        return collect($response['rows'] ?? [])->map(function (array $dateRow) {
            return [
                'date' => Carbon::createFromFormat('Ymd', $dateRow[0]),
                'pageTitle' => $dateRow[1],
                'visitors' => (int) $dateRow[2],
                'pageViews' => (int) $dateRow[3],
            ];
        });
    }

    /**
     * Call the query method on the authenticated client.
     *
     * @param Period $period
     * @param string $metrics
     * @param array  $others
     *
     * @return array|null
     */
    public function performQuery(Period $period, string $metrics, array $others = [])
    {
        return $this->client->performQuery(
            $this->viewId,
            $period->startDate,
            $period->endDate,
            $metrics,
            $others
        );
    }

    /*
     * Get the underlying Google_Service_Analytics object. You can use this
     * to basically call anything on the Google Analytics API.
     */
    public function getAnalyticsService(): Google_Service_Analytics
    {
        return $this->client->getAnalyticsService();
    }
}
