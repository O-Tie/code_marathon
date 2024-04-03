<?php

namespace App\Services\Bookhus;

use App\Models\Bookhus\{CalendarMapper, HouseMapper};
use App\Models\DataSource;
use App\Repositories\Bookhus\{BookhusCalendarRepositoryInterface, BookhusHouselistRepositoryInterface};
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Model;

class BookhusService
{
    protected $houselistRepository;
    protected $calendarRepository;

    /**
     * @param BookhusHouselistRepositoryInterface $houselist
     * @param BookhusCalendarRepositoryInterface $calendar
     */
    public function __construct(
        BookhusHouselistRepositoryInterface $houselist,
        BookhusCalendarRepositoryInterface $calendar
    ) {
        $this->houselistRepository = $houselist;
        $this->calendarRepository = $calendar;
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function importHouselist(): void
    {
        $bookhus = new BookhusSource(DataSource::find(DataSource::INTEGRATION_BOOKHUS));

        $housesResponse = $bookhus->getHouses();

        foreach ($housesResponse as $houseResponse) {
            $data = (new HouseMapper($houseResponse))->getMappedData();
            $house = $this->houselistRepository->getOne($data['id']);
            if (empty($house)) {
                $this->houselistRepository->create($data);
            } else {
                $this->checkForUpdate($house, $data, HouseMapper::fields());
            }
        }
        $this->checkHousesForDelete($housesResponse);
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function importCalendars(): void
    {
        $bookhus = new BookhusSource(DataSource::find(DataSource::INTEGRATION_BOOKHUS));

        $houseIds = $this->houselistRepository->getIds();

        foreach ($houseIds as $houseId) {
            $calendarsResponse = $bookhus->getCalendar($houseId);
            foreach ($calendarsResponse as $calendarResponse) {
                if ($calendarResponse->id <= 0) {
                    continue;
                }
                $data = (new CalendarMapper($calendarResponse))->getMappedData();
                $calendar = $this->calendarRepository->getOneByBookingId($data['booking_id'], $houseId);
                if (empty($calendar)) {
                    $this->calendarRepository->create($data + ['house_id' => $houseId]);
                } else {
                    $this->checkForUpdate($calendar, $data, CalendarMapper::fields());
                }
            }
            $this->checkCalendarsForDelete($calendarsResponse, $houseId);
        }
    }

    /**
     * @param Model $entity
     * @param array $newData
     * @param array $fields
     * @return void
     */
    protected function checkForUpdate(Model $entity, array $newData, array $fields): void
    {
        $changedFields = [];

        foreach ($fields as $databaseColumn => $responseColumn) {
            $value = $newData[$databaseColumn];
            if ($databaseColumn === "start_date" || $databaseColumn === "end_date") {
                $value = Carbon::parse($newData[$databaseColumn])->toDateTimeString();
            }
            if ($databaseColumn === "raffle" || $databaseColumn === "bookings_viewing") {
                $value = (int) $newData[$databaseColumn];
            }
            if ($entity->$databaseColumn !== $value) {
                $changedFields[$databaseColumn] = $value;
            }
        }
        if (!empty($changedFields)) {
            $entity->update($changedFields);
        }
    }

    /**
     * @param $response
     * @return void
     */
    protected function checkHousesForDelete($response)
    {
        $responseIds = array_map(function($item) {
            return $item->id;
        }, $response);

        $databaseIds = $this->houselistRepository->getIds();
        $idsToDelete = array_diff($databaseIds, $responseIds);
        $this->houselistRepository->deleteByIds($idsToDelete);
    }

    /**
     * @param $response
     * @param $houseId
     * @return void
     */
    protected function checkCalendarsForDelete($response, $houseId)
    {
        $responseIds = array_map(function($item) {
            return $item->id;
        }, $response);

        $databaseIds = $this->calendarRepository->getBookingIds($houseId);
        $idsToDelete = array_diff($databaseIds, $responseIds);
        $this->calendarRepository->deleteByBookingIds($idsToDelete);
    }
}
