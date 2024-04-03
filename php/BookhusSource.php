<?php

namespace App\Services\Bookhus;

use App\Models\DataSource;
use App\Services\Bookhus\Api;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;

class BookhusSource
{
    protected const HOUSES_PATH = '/overview/houselist';
    protected const CALENDAR_PATH = '/calendar';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var DataSource
     */
    protected $dataSource;

    /**
     * @param DataSource $dataSource
     * @throws Exception
     */
    public function __construct(DataSource $dataSource)
    {
        if (isset($dataSource->id)) {
            $this->api = new Api($dataSource->credentials);
            $this->dataSource = $dataSource;
        } else {
            $this->api = new Api();
        }
    }

    /**
     * @throws GuzzleException
     */
    public function getHouses()
    {
        $params['locale'] = 'da';
        $params['userId'] = $this->api->getUserId();

        $options['query'] = http_build_query($params);

        return $this->api->request(self::HOUSES_PATH, 'GET', $options);
    }

    /**
     * @throws GuzzleException
     */
    public function getCalendar(int $houseId)
    {
        $params['startdate'] = Carbon::today()->format('Y-m-d');
        $params['enddate'] = Carbon::today()->addDays(90)->format('Y-m-d');
        $params['houseId'] = $houseId;

        $options['query'] = http_build_query($params);

        return $this->api->request(self::CALENDAR_PATH, 'GET', $options);
    }
}
