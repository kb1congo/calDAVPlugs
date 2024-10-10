<?php

namespace Ginov\CaldavPlugs;

use Ginov\CaldavPlugs\Dto\CalendarCalDAV;
use Ginov\CaldavPlugs\Dto\EventCalDAV;
use Ginov\CaldavPlugs\Dto\Attendee;
use Ginov\CaldavPlugs\PlateformUserInterface;
use Symfony\Component\HttpFoundation\Request;

interface PlateformInterface
{
    /**
     * Singin and get your credentials for next connexion
     *
     * @param Request $request
     * @return PlateformUserInterface
     */
    public function login(Request $request): PlateformUserInterface;

    /**
     * Get one calendar by his ID
     *
     * @param string $credentials
     * @param string $calID
     * @return CalendarCalDAV
     */
    public function getCalendar(string $credentials, string $calID): CalendarCalDAV;

    /**
     * Get all calendars
     *
     * @param string $credentials
     * @return CalendarCalDAV[]
     */
    public function getCalendars(string $credentials): array;

    /**
     * Create a new Calendar
     *
     * @param string $credentials
     * @param CalendarCalDAV $calendar
     * @return CalendarCalDAV
     */
    public function createCalendar(string $credentials, CalendarCalDAV $calendar):CalendarCalDAV;

    /**
     * Delete a calendar by his ID
     *
     * @param string $credentials
     * @param string $calID
     * @return void
     */
    public function deleteCalendar(string $credentials, string $calID);

    /**
     * Update a calendar
     *
     * @param string $credentials
     * @param CalendarCalDAV $calendar
     * @return void
     */
    public function updateCalendar(string $credentials, CalendarCalDAV $calendar): CalendarCalDAV;

    /**
     * Get all events beetwen timeMin and timeMax
     *
     * @param string $credentials
     * @param string $calID
     * @return array
     */
    public function getEvents(string $credentials, string $calID, int $timeMin, int $timeMax): array;

    /**
     * Get on event by his ID 
     * 
     * @param string $credentials
     *  @param string $calID
     *  @param string $eventID
     *  @return EventCalDAV
     */
    public function getEvent(string $credentials, string $eventID, string $calID): EventCalDAV;

    /**
     * Create a new Event
     *
     * @param string $credentials
     * @param EventCalDAV $event
     * @return EventCalDAV
     */
    public function createEvent(string $credentials, string $calID, EventCalDAV $event): EventCalDAV;

    /**
     * Update an event by his ID and his calender_id
     *  
     * @param string $credentials
     *  @param string $calID
     *  @param string $eventID
     *  @param EventCalDAV $event
     *  @return EventCalDAV
     */
    public function updateEvent(string $credentials, string $calID, string $eventID, EventCalDAV $event): EventCalDAV;

    /**
     * Delete an event by his ID and his calender_id
     * 
     *  @param string $credentials
     *  @param string $calID
     *  @param string $eventID
     *  @return string
     */
    public function deleteEvent(string $credentials, string $calID, string $eventID): string;

    /**
     * (depricate)
     * 
     *  @param string $calID
     *  @return PlateformInterface
     */
    public function setCalendar(string $calID): PlateformInterface;

}