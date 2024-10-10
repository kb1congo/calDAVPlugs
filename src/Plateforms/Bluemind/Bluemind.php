<?php

namespace Ginov\CaldavPlugs\Plateforms\Bluemind;

use App\HttpTools;
use Sabre\VObject\Reader;
use Ginov\CaldavPlugs\Utils\Http;
use Ginov\CaldavPlugs\Factory;
use Ginov\CaldavPlugs\Plateform;
use Ginov\CaldavPlugs\Dto\Attendee;
use Ginov\CaldavPlugs\Dto\EventCalDAV;
use Ginov\CaldavPlugs\Dto\CalendarCalDAV;
use Ginov\CaldavPlugs\PlateformUserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\sendHttpRequest;
use Ginov\CaldavPlugs\Plateforms\Bluemind\BluemindUser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class Bluemind extends Factory
{
    private string $loginUrl;
    private string $certPath;
    private string $tenant;
    private string $client_id;
    private string $scope;
    private  string $secret;
    private string $redirectUri;

    const ERROR_INVALID_GRANT = 'invalid_grant';

    public function __construct(private ParameterBagInterface $parameters)
    {
        $this->srvUrl = $parameters->get('bluemind.srv.url');
    }

    public function getAuthorization(): string
    {
        return 'https://login.microsoftonline.com/' . $this->tenant . '/adminconsent?client_id=' . $this->client_id . '&redirect_uri=' . $this->redirectUri;
    }

    public function getOAuthUrl(): string
    {
        return $this->loginUrl . $this->tenant . "/oauth2/v2.0/authorize?client_id=" . $this->client_id . "&response_type=code&redirect_uri=" . $this->parameters->get('outlook.redirect.uri') . "&response_mode=query&scope=" . $this->scope . "&state=12345";
    }

    public function getToken(Request $request): array
    {
        //******* verifie le state $request->query->get('state')*/
        $response = (new Http($this->loginUrl))
            ->http()
            ->sendHttpRequest(
                'POST',
                $this->tenant . "/oauth2/v2.0/token",
                ["Content-Type" => "application/x-www-form-urlencoded"],
                http_build_query([
                    'tenant' => $this->tenant,
                    'client_id' => $this->client_id,
                    'grant_type' => 'authorization_code',
                    'scope' => $this->scope,
                    'code' => $request->query->get('code'),
                    'redirect_uri' => $this->redirectUri,
                    'client_secret' => $this->secret
                ])
            );

        $json = json_decode($response->getBodyAsString(), true);

        if ($response->getStatus() == Response::HTTP_BAD_REQUEST && $json['error'] == self::ERROR_INVALID_GRANT) {
            return [
                'url' => urldecode($this->getAuthorization()),
                'message' => 'In your browser go to url above'
            ];
        }

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        return $json;
    }

    public function callback() {}

    public function http_login(Request $request): PlateformUserInterface
    {
        /** @var BluemindUser $user */
        $user = (new BluemindUser())
            ->setUsername($request->request->get('username'))
            ->setPassword($request->request->get('password'));

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'POST',
                'auth/login?login=' . $user->getUsername() . '&origin=' . $this->srvUrl,
                ['Content-Type' => 'text/plain'],
                $user->getPassword()
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString());

        dd($json);

        return $user
            ->setUid($json->authUser->uid)
            ->setDomainUid($json->authUser->domainUid)
            ->setToken($json->authKey);
    }

    public function login(Request $request): PlateformUserInterface
    {
        /** @var BluemindUser $user */
        $user = (new BluemindUser())
            ->setUsername($request->request->get('username'))
            ->setPassword($request->request->get('password'));

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'POST',
                'auth/login?login=' . $user->getUsername() . '&origin=' . $this->srvUrl,
                ['Content-Type' => 'text/plain'],
                $user->getPassword()
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString());
        
        return new BluemindUser();
    }

    public function getCalendar(string $credentials, string $calID): CalendarCalDAV
    {
        /** @var BluemindUser */
        $user = $this->parseCredentials($credentials);

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'GET',
                "/me/calendars/$calID",
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $user->getToken()]
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString(), true);

        return (new CalendarCalDAV($calID))
            ->setCtag($json['changeKey'])
            ->setDisplayName($json['name'])
            ->setDescription($json['name'])
            // ->setTimeZone($json['timeZone'])
            ->setRBGcolor($json['hexColor']);
    }

    public function getCalendars(string $credentials): array
    {
        /** @var BluemindUser */
        $user = $this->parseCredentials($credentials);

        // dd($user);

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'GET',
                'users/'.$user->getDomainUid().'/'.$user->getUid().'/calendar-views/_list',
                ['Content-Type' => 'application/json', 'X-BM-ApiKey' => $user->getToken()]
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString());

        dd($json);

        $items = [];
        foreach ($json['value'] as $value) {
            $items[] = (new CalendarCalDAV($value['id']))
                // ->setCtag($value['ctag'])
                ->setCtag($value['changeKey'])
                ->setDisplayName($value['name'])
                ->setDescription($value['name'])
                // ->setTimeZone($value['timeZone'])
                ->setRBGcolor($value['hexColor']);
        }

        return array(
            'nextPage' => '',
            'items' => $items
        );
    }

    public function createCalendar(string $credentials, CalendarCalDAV $calendar): CalendarCalDAV
    {
        /** @var BluemindUser */
        $user = $this->parseCredentials($credentials);

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'POST',
                'me/calendars',
                ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $user->getToken()],
                (json_encode([
                    "name" => $calendar->getDisplayName(),
                    "color" => "auto",
                    "hexColor" => $calendar->getRBGcolor(),
                    "isDefaultCalendar" => false,
                    "changeKey" => $calendar->getCTag(),
                    "canShare" => true,
                    "canViewPrivateItems" => true,
                    "canEdit" => true,
                    "allowedOnlineMeetingProviders" => ["teamsForBusiness"],
                    "defaultOnlineMeetingProvider" => "teamsForBusiness",
                    "isTallyingResponses" => true,
                    "isRemovable" => false,
                    "owner" => [
                        "name" => $user->getUsername(),
                        "address" => $user->getPassword()
                    ]
                ]))
            );

        if ($response->getStatus() != Response::HTTP_CREATED)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString(), true);

        // dd($json);

        return (new CalendarCalDAV($json['id']))
            ->setCtag($json['changeKey'])
            ->setDisplayName($json['name'])
            ->setDescription($json['name'])
            // ->setTimeZone($json['timeZone'])
            ->setRBGcolor($json['hexColor']);
    }

    public function updateCalendar(string $credentials, CalendarCalDAV $calendar): CalendarCalDAV
    {
        return new CalendarCalDAV($calendar->getCalendarID());
    }

    public function deleteCalendar(string $credentials, string $calID)
    {
        /** @var BluemindUser */
        $user = $this->parseCredentials($credentials);

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'DELETE',
                "/me/calendars/$calID",
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $user->getToken()]
            );

        if ($response->getStatus() != Response::HTTP_NO_CONTENT)
            throw new \Exception($response->getStatusText(), $response->getStatus());


        return  $calID;
    }

    public function getEvents(string $credentials, string $calID, int $timeMin, int $timeMax): array
    {
        /** @var BluemindUser */
        $user = $this->parseCredentials($credentials);

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'GET',
                "me/calendars/$calID/events",
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $user->getToken()]
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString());

        $items = [];

        foreach ($json->value as $event) {
            $items[] = (new EventCalDAV())
                ->setSummary($event->subject)
                ->setDescription($event->subject)
                ->setLocation($event->location->displayName)
                ->setDateStart($event->start->dateTime)
                ->setDateEnd($event->start->dateTime)
                ->setTimeZoneID($event->start->timeZone)
                ->setRrule($event->recurrence)
                // ->setAttendees($this->parseAttendess($event->attendees))
                ->setUid($event->id);
        }

        return array(
            'nextPage' => $json->{'@odata.nextLink'},
            'items' => $items
        );
    }

    public function getEvent(string $credentials, string $eventID, string $calID): EventCalDAV
    {
        return new EventCalDAV();
    }

    public function createEvent(string $credentials, string $calID, EventCalDAV $event): EventCalDAV
    {
        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'POST',
                "calendars/$calID/events",
                [
                    "Content-Type" => "application/json",
                    'Authorization' => 'Bearer ' . $credentials
                ],
                (json_encode([
                    'start' => [
                        'dateTime' => '2024-09-28T10:00:00',
                        'timeZone' => 'Europe/Paris',
                    ],
                    'end' => [
                        'dateTime' => '2024-09-30T11:00:00',
                        'timeZone' => 'Europe/Paris',
                    ],
                    'attendees' => $event->getAttendees(),
                    "sendUpdates" => "all"
                ]))
                /* (json_encode([
                    'start' => [
                        'dateTime' => $event->getDateStart(),
                        'timeZone' => 'Europe/Paris'
                    ],
                    'end' => [
                        'dateTime' => $event->getDateEnd(),
                        'timeZone' => 'Europe/Paris'
                    ],
                    'attendees' => $event->getAttendees()
                ])) */
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        dd($response);

        return new EventCalDAV();
    }

    public function deleteEvent(string $credentials, string $calID, string $eventID): string
    {
        return '';
    }

    public function updateEvent(string $credentials, string $calID, string $eventID, EventCalDAV $event): EventCalDAV
    {
        return new EventCalDAV();
    }

    protected static function parseAttendees(array $attendees): array
    {
        $attendees = [];

        foreach ($attendees as $attendee) {
            $rsvp = $attendee->status->response == 'none' ? FALSE : TRUE;
            $attendees[] = new Attendee($attendee->emailAddress->address, $rsvp, $attendee->emailAddress->name);
        }

        return $attendees;
    }

    protected static function parseCalendar($plateformCalendar, string $calID): CalendarCalDAV
    {
        return new CalendarCalDAV('');
    }

    protected static function parseEvent($plateformEvent): EventCalDAV
    {
        return new EventCalDAV();
    }

    protected static function toPlateformAttendees(array $attendees): array
    {
        return [];
    } 

    protected static function parseCredentials(string $credentials): PlateformUserInterface
    {
        $tmp = explode(';', $credentials);

        return (new BluemindUser())
            ->setToken($tmp[0])
            ->setUid($tmp[1])
            ->setDomainUid($tmp[2]);
    }

    public function setCalendar(string $calID): self
    {
        $this->calendarID = $calID;
        return $this;
    }

}
