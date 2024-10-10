<?php

namespace Ginov\CaldavPlugs\Plateforms\Google;

use App\HttpTools;
use DateTime;
use Sabre\VObject\Reader;
use Ginov\CaldavPlugs\Utils\Http;
use Ginov\CaldavPlugs\Factory;
use Ginov\CaldavPlugs\Plateform;
use Ginov\CaldavPlugs\OAuthInterface;
use Ginov\CaldavPlugs\Dto\EventCalDAV;
use Ginov\CaldavPlugs\Dto\CalendarCalDAV;
use Ginov\CaldavPlugs\Dto\Attendee;
use Ginov\CaldavPlugs\PlateformUserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\sendHttpRequest;
use Ginov\CaldavPlugs\Plateforms\Google\GoogleUser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class Google extends Factory implements OAuthInterface
{
    private string $calDAVUrl;
    private string $certPath;

    private string $clientID;
    private string $secret;
    private string $redirectUri;
    private string $scope;

    public function __construct(private ParameterBagInterface $parameters)
    {
        $this->srvUrl = $parameters->get('google.srv.url');
        $this->calDAVUrl = $parameters->get('google.caldav.url');

        $this->clientID = $parameters->get('google.client.id');
        $this->secret = $parameters->get('google.client.secret');
        $this->redirectUri = $parameters->get('google.redirect.uri');
        $this->scope = $parameters->get('google.scope');
    }

    public function getOAuthUrl(): string
    {
        return "https://accounts.google.com/o/oauth2/v2/auth?scope=" . $this->scope . "&access_type=offline&include_granted_scopes=true&response_type=code&redirect_uri=" . $this->redirectUri . "&client_id=" . $this->clientID;
    }

    public function getOAuthToken(Request $request): array
    {
        //******* verifie le state $request->query->get('state')*/
        $response = (new Http('https://oauth2.googleapis.com'))
            ->http()
            ->sendHttpRequest(
                'POST',
                "/token",
                ["Content-Type" => "application/x-www-form-urlencoded"],
                http_build_query([
                    'code' => $request->query->get('code'),
                    'client_id' => $this->clientID,
                    'client_secret' => $this->secret,
                    'redirect_uri' => $this->redirectUri,
                    'grant_type' => 'authorization_code',
                ])
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        return json_decode($response->getBodyAsString(), true);
    }

    public function refreshOAuthToken() {}

    public function ___login(PlateformUserInterface $user): PlateformUserInterface
    {
        /** @var GoogleUser $user */
        $user = $user;

        dd($user);

        return $user;
    }

    public function login(Request $request): PlateformUserInterface
    {
        /** @var GoogleUser $user */
        $user = (new GoogleUser())
            ->setToken($request->request->get('token'));

        return $user;
    }

    public function getCalendar(string $credentials, string $calID): CalendarCalDAV
    {
        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'GET',
                "calendars/$calID",
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $credentials]
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString());


        return (new CalendarCalDAV($calID))
            ->setCtag($json->etag)
            ->setDisplayName($json->summary)
            ->setDescription($json->summary)
            ->setTimeZone($json->timeZone);
    }

    public function getCalendars(string $credentials): array
    {
        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'GET',
                'users/me/calendarList',
                [
                    "Content-Type" => "application/json",
                    'Authorization' => 'Bearer ' . $credentials
                ]
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString());

        $items = [];
        foreach ($json->items as $value) {
            $items[] = (new CalendarCalDAV($value->summary))
                ->setCalendarID($value->id)
                ->setCtag($value->etag)
                ->setDisplayName($value->summary)
                ->setDescription($value->summary)
                ->setTimeZone($value->timeZone)
                ->setRBGcolor($value->backgroundColor);
        }

        return array(
            'nextSyncToken' => $json->nextSyncToken,
            'items' => $items
        );
    }

    public function createCalendar(string $credentials, CalendarCalDAV $calendar): CalendarCalDAV
    {
        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'POST',
                'calendars',
                ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $credentials],
                (json_encode([
                    'summary' => $calendar->getDisplayName(),
                    'timeZone' => $calendar->getTimeZone(),
                    'description' => $calendar->getDescription()
                ]))
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString());

        return (new CalendarCalDAV($json->summary))
            ->setCalendarID($json->id)
            ->setCtag($json->etag)
            ->setDisplayName($json->summary)
            ->setDescription($json->description)
            ->setTimeZone($json->timeZone);
    }

    public function updateCalendar(string $credentials, CalendarCalDAV $calendar): CalendarCalDAV
    {
        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'PUT',
                'calendars/' . $calendar->getCalendarID(),
                ['Content-Type' => 'application/x-www-form-urlencoded', 'Authorization' => 'Bearer ' . $credentials],
                (json_encode([
                    'summary' => $calendar->getDisplayName(),
                    'timeZone' => $calendar->getTimeZone(),
                    'description' => $calendar->getDescription()
                ]))
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString());

        return (new CalendarCalDAV($calendar->getDisplayName()))
            ->setCalendarID($json->id)
            ->setCtag($json->etag)
            ->setDisplayName($json->summary)
            ->setDescription($json->description)
            ->setTimeZone($json->timeZone);
    }

    public function deleteCalendar(string $credentials, string $calID)
    {
        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'DELETE',
                "calendars/$calID",
                [
                    "Content-Type" => "application/json",
                    'Authorization' => 'Bearer ' . $credentials
                ]
            );

        if ($response->getStatus() != Response::HTTP_NO_CONTENT)
            throw new \Exception($response->getStatusText(), $response->getStatus());


        return  $calID;
    }

    public function getEvents(string $credentials, string $calID, int $timeMin, int $timeMax): array
    {
        $params = [];
        if ($timeMin) $params['timeMin'] = date('Y-m-d\TH:i:sP', $timeMin);
        if ($timeMax) $params['timeMax'] = date('Y-m-d\TH:i:sP', $timeMax);

        // dd("calendars/$calID/events?".http_build_query($params));

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'GET',
                "calendars/$calID/events?" . http_build_query($params),
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $credentials]
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString());

        $items = [];

        foreach ($json->items as $event) {

            $items[] = self::parseEvent($event);
        }

        return [
            'nextSyncToken' => $json->nextSyncToken,
            'events' => $items
        ];
    }

    public function getEvent(string $credentials, string $calID, string $eventID): EventCalDAV
    {
        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'GET',
                "calendars/$calID/events/$eventID",
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $credentials]
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        return self::parseEvent(json_decode($response->getBodyAsString()));
    }

    public function createEvent(string $credentials, string $calID, EventCalDAV $event): EventCalDAV
    {
        dd(
            [
                'summary' => $event->getSummary(),
                'description' => $event->getDescription() ? $event->getDescription() : $event->getSummary(),
                'start' => [
                    'dateTime' => self::parseTime($event->getDateStart()),
                    'timeZone' => $event->getTimeZoneID(),
                ],
                'end' => [
                    'dateTime' => self::parseTime($event->getDateEnd()),
                    'timeZone' => $event->getTimeZoneID(),
                ],
                'attendees' => self::toPlateformAttendees($event->getAttendees()),
                "sendUpdates" => "all",
                // "sendNotifications" => true
            ]
        );

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'POST',
                "calendars/$calID/events",
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $credentials],
                (json_encode(
                    [
                        'summary' => $event->getSummary(),
                        'description' => $event->getDescription() ? $event->getDescription() : $event->getSummary(),
                        'start' => [
                            'dateTime' => self::parseTime($event->getDateStart()),
                            'timeZone' => $event->getTimeZoneID(),
                        ],
                        'end' => [
                            'dateTime' => self::parseTime($event->getDateEnd()),
                            'timeZone' => $event->getTimeZoneID(),
                        ],
                        'attendees' => self::toPlateformAttendees($event->getAttendees()),
                        "sendUpdates" => "all",
                        "sendNotifications" => true
                    ]
                ))
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        return self::parseEvent(json_decode($response->getBodyAsString()));
    }

    public function updateEvent(string $credentials, string $calID, string $eventID, EventCalDAV $event): EventCalDAV
    {
        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'PUT',
                "calendars/$calID/events/$eventID",
                ['Content-Type' => 'application/x-www-form-urlencoded', 'Authorization' => 'Bearer ' . $credentials],
                (json_encode([
                    'summary' => $event->getSummary(),
                    'description' => $event->getDescription() ? $event->getDescription() : $event->getSummary(),
                    'start' => [
                        'dateTime' => self::parseTime($event->getDateStart()),
                        'timeZone' => $event->getTimeZoneID(),
                    ],
                    'end' => [
                        'dateTime' => self::parseTime($event->getDateEnd()),
                        'timeZone' => $event->getTimeZoneID(),
                    ],
                    'attendees' => $event->getAttendees(),
                    "sendUpdates" => "all"
                ]))
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        return self::parseEvent(json_decode($response->getBodyAsString()));
    }

    public function  deleteEvent(string $credentials, string $calID, string $eventID): string
    {
        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'DELETE',
                "calendars/$calID/events/$eventID?sendUpdates=all",
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $credentials]
            );

        if ($response->getStatus() != Response::HTTP_NO_CONTENT)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        return $eventID;
    }

    protected static function toPlateformAttendees(array $attendees): array
    {
        $results = array_map(function ($n) {
            return [
                'email' => $n->getEmail(),
                'displayName' => $n->getName(),
                // 'responseStatus' => $n->getRvps() ? 'accepted' : 'needsAction'
            ];
        }, $attendees);

        return $results;
    }

    public static function parseAttendees(array $attendees): array
    {
        return [];
    }

    private static function parseTime(mixed $date): string
    {
        return (is_int($date))
            ? date('Y-m-d\TH:i:sP', $date)
            : (new \DateTime($date))->format('Y-m-d\TH:i:sP');
    }

    protected static function parseEvent($googleEvent): EventCalDAV
    {
        // a reecrire plus proprement***************
        $attendees = array_map(function ($n) {
            return new Attendee($n->email);
        }, $googleEvent->attendees ?? []);

        return (new EventCalDAV())
            ->setSummary($googleEvent->summary ?? '')
            ->setDescription($googleEvent->description ?? '')
            ->setLocation($googleEvent->location ?? '')
            ->setDateStart($googleEvent->start->dateTime)
            ->setDateEnd($googleEvent->end->dateTime)
            ->setTimeZoneID($googleEvent->start->timeZone)
            ->setRrule($googleEvent->recurrence[0] ?? null) // a vérifier****************
            ->setAttendees($attendees)
            ->setUid($googleEvent->id);
    }

    protected static function parseCalendar($googleCalendar, string $calID): CalendarCalDAV
    {
        return (new CalendarCalDAV($googleCalendar['displayname']))
            ->setCalendarID($calID)
            ->setCtag($googleCalendar['etag'])
            ->setDisplayName($googleCalendar['summary'])
            ->setDescription($googleCalendar['summary'])
            ->setTimeZone($googleCalendar['timeZone']);
    }

    public function setCalendar(string $calID): self
    {
        $this->calendarID = $calID;
        return $this;
    }
}
