<?php

namespace Ginov\CaldavPlugs\Plateforms\Outlook;

use App\HttpTools;
use Sabre\VObject\Reader;
use Ginov\CaldavPlugs\Utils\Http;
use Ginov\CaldavPlugs\Factory;
use Ginov\CaldavPlugs\Plateform;
use Ginov\CaldavPlugs\Dto\Attendee;
use Ginov\CaldavPlugs\Dto\EventCalDAV;
use Ginov\CaldavPlugs\Dto\CalendarCalDAV;
use Ginov\CaldavPlugs\OAuthInterface;
use Ginov\CaldavPlugs\PlateformInterface;
use Ginov\CaldavPlugs\PlateformUserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\sendHttpRequest;
use Ginov\CaldavPlugs\Plateforms\Outlook\OutlookUser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class Outlook extends Factory implements OAuthInterface
{
    private string $loginUrl;
    private string $certPath;
    private string $tenant;
    private string $client_id;
    private string $scope;
    private  string $secret;
    private string $redirectUri;

    const ERROR_INVALID_GRANT = 'invalid_grant';
    const ATTENDEE_ACCEPTED = 'accepted';
    const ATTENDEE_NONE = 'none';

    public function __construct(private ParameterBagInterface $parameters)
    {
        $this->srvUrl = $parameters->get('outlook.srv.url');
        $this->loginUrl = $parameters->get('outlook.login.url');
        $this->tenant = $this->parameters->get('outlook.client.tenant');
        $this->client_id = $this->parameters->get('outlook.client.id');
        $this->scope = $parameters->get('outlook.scope');
        $this->secret = $this->parameters->get('outlook.client.secret');
        $this->redirectUri = $this->parameters->get('outlook.redirect.uri');
    }

    public function getAdminAuthorization(): string
    {
        return 'https://login.microsoftonline.com/' . $this->tenant . '/adminconsent?client_id=' . $this->client_id . '&redirect_uri=' . $this->redirectUri;
    }

    public function getOAuthUrl(): string
    {
        return $this->loginUrl . $this->tenant . "/oauth2/v2.0/authorize?client_id=" . $this->client_id . "&response_type=code&redirect_uri=" . $this->redirectUri . "&response_mode=query&scope=" . $this->scope . "&state=12345";
    }

    public function getOAUthToken(Request $request): array
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
                'url' => urldecode($this->getAdminAuthorization()),
                'message' => 'In your browser go to url above'
            ];
        }

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        return $json;
    }

    public function refreshOAuthToken() {}

    public function callback() {}

    public function ___login(PlateformUserInterface $user): PlateformUserInterface
    {
        /** @var GoogleUser $user */
        $user = $user;

        dd($user);

        return $user;
    }

    public function login(Request $request): PlateformUserInterface
    {
        /** @var OutlookUser $user */
        $user = (new OutlookUser())
            ->setToken($request->request->get('token'))
            ->setUsername($request->request->get('owner_name'))
            ->setEmail($request->request->get('owner_email'));

        return $user;
    }

    public function getCalendar(string $credentials, string $calID): CalendarCalDAV
    {
        /** @var OutlookUser */
        $user = OutlookUser::parseCredentials($credentials);

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

        return (new CalendarCalDAV($json['name']))
            ->setCalendarID($calID)
            ->setCtag($json['changeKey'])
            // ->setDisplayName($json['name'])
            ->setDescription($json['name'])
            // ->setTimeZone($json['timeZone'])
            ->setRBGcolor($json['hexColor']);
    }

    public function getCalendars(string $credentials): array
    {
        /** @var OutlookUser */
        $user = OutlookUser::parseCredentials($credentials);

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'GET',
                'me/calendars',
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $user->getToken()]
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString(), true);

        $items = [];
        foreach ($json['value'] as $value) {
            $items[] = (new CalendarCalDAV($value['name']))
                ->setCalendarID($value['id'])
                // ->setCtag($value['ctag'])
                ->setCtag($value['changeKey'])
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
        /** @var OutlookUser */
        $user = OutlookUser::parseCredentials($credentials);

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
                        "address" => $user->getEmail()
                    ]
                ]))
            );

        if ($response->getStatus() != Response::HTTP_CREATED)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString());


        return (new CalendarCalDAV($json->name))
            ->setCalendarID($json->id)
            ->setCtag($json->changeKey)
            ->setDisplayName($json->name)
            ->setDescription($json->name)
            // ->setTimeZone($json['timeZone'])
            ->setRBGcolor($json->hexColor);
    }

    public function updateCalendar(string $credentials, CalendarCalDAV $calendar): CalendarCalDAV
    {
        /** @var OutlookUser */
        $user = OutlookUser::parseCredentials($credentials);

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'PATCH',
                'me/calendars/' . $calendar->getCalendarID(),
                ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $user->getToken()],
                (json_encode([
                    "name" => $calendar->getDisplayName(),
                    // "color" => $calendar->getRBGcolor(),
                    // "isDefaultCalendar" => false,
                ]))
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        return self::parseCalendar(
            json_decode($response->getBodyAsString()),
            $calendar->getCalendarID()
        );
    }

    public function deleteCalendar(string $credentials, string $calID)
    {
        /** @var OutlookUser */
        $user = OutlookUser::parseCredentials($credentials);

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'DELETE',
                "/me/calendars/$calID",
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $user->getToken()]
            );

        dd($response);

        if ($response->getStatus() != Response::HTTP_NO_CONTENT)
            throw new \Exception($response->getStatusText(), $response->getStatus());


        return  $calID;
    }

    public function getEvents(string $credentials, string $calID, int $timeMin, int $timeMax): array
    {
        $params = [];
        if ($timeMin) $params['startDateTime'] = date('Y-m-d\TH:i:sP', $timeMin);
        if ($timeMax) $params['endDateTime'] = date('Y-m-d\TH:i:sP', $timeMax);

        /** @var OutlookUser */
        $user = OutlookUser::parseCredentials($credentials);

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'GET',
                "me/calendars/$calID/calendarView?".http_build_query($params),
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $user->getToken()]
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        $json = json_decode($response->getBodyAsString());

        $items = array_map(function ($n) {
            return self::parseEvent($n);
        }, $json->value ?? []);

        return array(
            'nextPage' => ($json->{'@odata.nextLink'} ?? null),
            'calendar_id' => $calID,
            'items' => $items
        );
    }

    public function getEvent(string $credentials, string $calID, string $eventID): EventCalDAV
    {
        /** @var OutlookUser */
        $user = OutlookUser::parseCredentials($credentials);

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'GET',
                "me/calendars/$calID/events/$eventID",
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $user->getToken()]
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        return self::parseEvent(json_decode($response->getBodyAsString()));
    }

    public function createEvent(string $credentials, string $calID, EventCalDAV $event): EventCalDAV
    {
        /** @var OutlookUser */
        $user = OutlookUser::parseCredentials($credentials);

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'POST',
                "me/calendars/$calID/events",
                [
                    "Content-Type" => "application/json",
                    'Authorization' => 'Bearer ' . $user->getToken()
                ],
                (json_encode([
                    'subject' => $event->getSummary(),
                    'start' => [
                        'dateTime' => self::toPlateformTime($event->getDateStart()),
                        'timeZone' => $event->getTimeZoneID(),
                    ],
                    'end' => [
                        'dateTime' => self::toPlateformTime($event->getDateEnd()),
                        'timeZone' => $event->getTimeZoneID(),
                    ],
                    'attendees' => self::toPlateformAttendees($event->getAttendees())
                ]))
            );

        if ($response->getStatus() != Response::HTTP_CREATED)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        return self::parseEvent(json_decode($response->getBodyAsString()));
    }

    public function deleteEvent(string $credentials, string $calID, string $eventID): string
    {
        /** @var OutlookUser */
        $user = OutlookUser::parseCredentials($credentials);

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'DELETE',
                "me/calendars/$calID/events/$eventID",
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $user->getToken()]
            );

        if ($response->getStatus() != Response::HTTP_NO_CONTENT)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        return $eventID;
    }

    public function updateEvent(string $credentials, string $calID, string $eventID, EventCalDAV $event): EventCalDAV
    {
        /** @var OutlookUser */
        $user = OutlookUser::parseCredentials($credentials);

        $response = (new Http($this->srvUrl))
            ->http()
            ->sendHttpRequest(
                'PATCH',
                "me/calendars/$calID/events/$eventID",
                ["Content-Type" => "application/json", 'Authorization' => 'Bearer ' . $user->getToken()],
                (json_encode([
                    'subject' => $event->getSummary(),
                    'start' => [
                        'dateTime' => self::toPlateformTime($event->getDateStart()),
                        'timeZone' => $event->getTimeZoneID(),
                    ],
                    'end' => [
                        'dateTime' => self::toPlateformTime($event->getDateEnd()),
                        'timeZone' => $event->getTimeZoneID(),
                    ],
                    'attendees' => $event->getAttendees(),
                ]))
            );

        if ($response->getStatus() != Response::HTTP_OK)
            throw new \Exception($response->getStatusText(), $response->getStatus());

        return self::parseEvent(json_decode($response->getBodyAsString()));
    }

    protected static function parseCalendar($outlookCalendar, string $calID): CalendarCalDAV
    {
        return (new CalendarCalDAV($outlookCalendar->name))
            ->setCalendarID($outlookCalendar->id)
            ->setCtag($outlookCalendar->changeKey)
            ->setDescription($outlookCalendar->name)
            // ->setTimeZone($json['timeZone'])
            ->setRBGcolor($outlookCalendar->hexColor);
    }

    protected static function parseEvent($outlookEvent): EventCalDAV
    {
        // a reecrire plus proprement***************
        $attendees = array_map(function ($n) {
            return new Attendee($n->emailAddress->address);
        }, $outlookEvent->attendees ?? []);

        return (new EventCalDAV())
            ->setSummary($outlookEvent->subject)
            ->setDescription($outlookEvent->description ?? ($outlookEvent->subject ?? ''))
            ->setLocation($outlookEvent->location->displayName ?? '')
            ->setDateStart($outlookEvent->start->dateTime)
            ->setDateEnd($outlookEvent->end->dateTime)
            ->setTimeZoneID($outlookEvent->start->timeZone)
            ->setRrule($outlookEvent->recurrence[0] ?? null) // a vérifier****************
            ->setAttendees($attendees)
            ->setUid($outlookEvent->id);
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

    protected static function toPlateformAttendees(array $attendees): array
    {
        $outlookAttendees = [];

        foreach ($attendees as $attendee) {

            /** @var Attendee */
            $attendee = $attendee;

            $outlookAttendees[] = [
                'emailAddress' => [
                    'address' => $attendee->getEmail(),
                    'name' => $attendee->getName(),
                    // 'accepted' => $attendee->getRvps() ? self::ATTENDEE_ACCEPTED : self::ATTENDEE_NONE
                ],
                'type' => 'required'

            ];
        }

        return $outlookAttendees;
    }

    private static function toPlateformTime(string $date): string
    {
        return (new \DateTime($date))->format('Y-m-d\TH:i:sP');
    }

    public function setCalendar(string $calID): self
    {
        $this->calendarID = $calID;
        return $this;
    }

}
