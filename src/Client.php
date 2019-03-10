<?php

namespace Thorazine\Analytics;

use Google_Service_Analytics;

class AnalyticsClient
{
    /** @var \Google_Service_Analytics */
    protected $service;

    public function __construct(Google_Service_Analytics $service)
    {
        $this->service = $service;
    }

    public function getService(): Google_Service_Analytics
    {
        return $this->service;
    }
}
