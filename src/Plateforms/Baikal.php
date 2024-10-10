<?php

namespace Ginov\CaldavPlugs\Plateforms;

use App\Security\User;
use Ginov\CaldavPlugs\Dto\CalendarCalDAV;
use Ginov\CaldavPlugs\Dto\EventCalDAV;
use Ginov\CaldavPlugs\Dto\Attendee;
use Ginov\CaldavPlugs\Factory;
use Ginov\CaldavPlugs\PlateformInterface;
use Ginov\CaldavPlugs\Utils\Http;
use Ginov\CaldavPlugs\PlateformUserInterface;
use Symfony\Component\HttpFoundation\Request;
use Ginov\CaldavPlugs\Plateforms\Credentials\BasicUser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class Baikal extends Factory
{

    public function __construct(ParameterBagInterface $parameter)
    {
        $this->srvUrl = $parameter->get('baikal.srv.url');
    }

    public function getOAuthUrl(): string
    {
        return '';
    }

    public function getToken(Request $request): array
    {
        return [];
    }

    /**
     * Singin and get your credentials
     *
     * @param PlateformUserInterface $userDto
     * @return PlateformUserInterface
     */
    public function login(Request $request): PlateformUserInterface
    {
        // dd($request->request);

        /** @var BasicUser $user */
        $user = (new BasicUser())
            ->setUsername($request->request->get('username'))
            ->setPassword($request->request->get('password'));

        return $user;
    }

    /**
     * Get one calendar by ID
     *
     * @param string $credentials
     * @param string $calID
     * @return CalendarCalDAV
     */
    public function getCalendar(string $credentials, string $calID): CalendarCalDAV
    {
        $user = $this->parseCredentials($credentials);
        $response = (new Http($this->srvUrl))
            ->dav([$user->getUsername(), $user->getPassword()])
            ->sendDavRequest('GET', '/calendars/user/' . $calID);

        dd($response);


        return new CalendarCalDAV($calID);
    }

    /**
     * Undocumented function
     *
     * @param PlateformUserInterface $user
     * @return array
     */
    public function __calendars(string $credentials): array
    {
        $user = self::parseCredentials($credentials);
        $username = $user->getUsername();
        $password = $user->getPassword();

        // URL du répertoire contenant les calendriers
        $requestUrl = $this->parseUrl([$username]);

        // dd($requestUrl);

        // Initialiser cURL
        $ch = curl_init();

        // Configurer les options de la requête cURL
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/xml; charset=utf-8',
            'Depth: 1'
        ]);

        // XML de la requête PROPFIND pour obtenir la liste des calendriers
        $propfindData = '<?xml version="1.0" encoding="utf-8" ?>
      <d:propfind xmlns:d="DAV:" xmlns:cs="urn:ietf:params:xml:ns:caldav">
        <d:prop>
          <d:displayname/>
          <cs:calendar-description/>
          <cs:supported-calendar-component-set/>
        </d:prop>
      </d:propfind>';

        curl_setopt($ch, CURLOPT_POSTFIELDS, $propfindData);

        // Exécuter la requête cURL
        $response = curl_exec($ch);

        if (curl_errno($ch))
            throw new \Exception(curl_error($ch));

        // dd($response);

        // Parse la réponse XML
        $xml = simplexml_load_string($response);
        // dd($xml);
        $json = json_encode($xml);

        // Afficher la réponse en JSON
        echo $json;

        // Fermer la session cURL
        curl_close($ch);
        return [];
    }

    /**
     * Get all calendars
     *
     * @param string $credentials
     * @return array
     */
    public function getCalendars(string $credentials): array
    {
        /* $user = $this->parseCredentials($credentials);
        $response = (new Http($this->srvUrl))
            ->dav(['userName' => $user->getUsername(), 'password' => $user->getPassword()])
            ->sendDavRequest('PROPFIND', $user->getUsername()); */


        $user = $this->parseCredentials($credentials);

        $xml =<<<XML
        <?xml version="1.0"?>
        <d:propfind xmlns:d="DAV:">
            <d:prop>
                <d:displayname />
            </d:prop>
        </d:propfind>
        XML;

        $response = (new Http('https://future.zimbra.tech/dav/'))
            ->dav(['username' => $user->getUsername(), 'password' => $user->getPassword(), 'authType' => 2])
            ->sendDavRequest(
                'PROPFIND', 
                $user->getUsername().'/', 
                ['Depth' => '1', 'Content-Type' => 'application/xml'], 
                $xml
            );

        dd($response);


        return new CalendarCalDAV($calID);
    }

    /**
     * Get all events between timeMin and timMax
     *
     * @param string $credentials
     * @param string $calID
     * @param integer $timeMin
     * @param integer $timeMax
     * @return array
     */
    public function getEvents(string $credentials, string $calID, int $timeMin, int $timeMax): array
    {
        return [];
    }

    public function getEvent(string $credentials, string $eventID, string $calID): EventCalDAV
    {
        return new EventCalDAV();
    }

    public function createEvent(string $credentials, string $calID, EventCalDAV $event): EventCalDAV
    {
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

    public function createCalendar(string $credentials, CalendarCalDAV $calendar): CalendarCalDAV
    {
        $user = self::parseCredentials($credentials);
        $username = $user->getUsername();
        $password = $user->getPassword();

        $url = $this->srvUrl . $username . '/' . $calendar->getCalendarID() . '/';

        $xmlData = <<<EOD
      <?xml version="1.0" encoding="utf-8" ?>
      <C:mkcalendar xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
      <D:set>
         <D:prop>
            <D:displayname>{$calendar->getDisplayName()}</D:displayname>
            <C:calendar-description xml:lang="en">{$calendar->getDescription()}</C:calendar-description>
            <C:supported-calendar-component-set>
            <C:comp name="VEVENT"/>
            </C:supported-calendar-component-set>
            <C:calendar-timezone><![CDATA[BEGIN:VCALENDAR
      PRODID:-//Example Corp.//CalDAV Client//EN
      VERSION:2.0 
      BEGIN:VTIMEZONE
      TZID:US-Eastern
      LAST-MODIFIED:19870101T000000Z
      BEGIN:STANDARD
      DTSTART:19671029T020000
      RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10
      TZOFFSETFROM:-0400
      TZOFFSETTO:-0500
      TZNAME:Eastern Standard Time (US & Canada)
      END:STANDARD
      BEGIN:DAYLIGHT
      DTSTART:19870405T020000
      RRULE:FREQ=YEARLY;BYDAY=1SU;BYMONTH=4
      TZOFFSETFROM:-0500
      TZOFFSETTO:-0400
      TZNAME:Eastern Daylight Time (US & Canada)
      END:DAYLIGHT
      END:VTIMEZONE
      END:VCALENDAR
      ]]></C:calendar-timezone>
         </D:prop>
      </D:set>
      </C:mkcalendar>
      EOD;

        // Initialiser cURL
        $ch = curl_init();

        // Configurer les options de la requête cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'MKCALENDAR');
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/xml; charset=utf-8',
            'Depth: 1'
        ));

        // Exécuter la requête cURL
        $response = curl_exec($ch);

        if (curl_errno($ch))
            throw new \Exception(curl_error($ch));

        curl_close($ch);

        return (new CalendarCalDAV('TODO'));
    }

    public function updateCalendar(string $credentials, CalendarCalDAV $calendar): CalendarCalDAV
    {
        return new CalendarCalDAV('');
    }

    public function deleteCalendar(string $credentials, string $calID)
    {
        $user = self::parseCredentials($credentials);
        $username = $user->getUsername();
        $password = $user->getPassword();

        $url = $this->srvUrl . $username . '/' . $calID . '/';

        // Initialiser une session cURL
        $ch = curl_init();

        // Configurer les options cURL pour une requête DELETE
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

        // Exécuter la requête cURL
        $response = curl_exec($ch);

        if (curl_errno($ch))
            throw new \Exception(curl_error($ch));

        curl_close($ch);

        return $response;
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
        /** @var CalendarCalDav */
        $baikalCalendar = $plateformCalendar;
        return new $baikalCalendar;
    }

    protected static function parseEvent($plateformEvent): EventCalDAV
    {
        return new EventCalDAV();
    }

    protected static function toPlateformAttendees(array $attendees): array
    {
        return [];
    }

    /**
     * @param string $credentials
     * @return BasicUser
     */
    protected static function parseCredentials(string $credentials): BasicUser
    {
        $tmp = explode(';', $credentials);

        return (new BasicUser())
            ->setUsername($tmp[0])
            ->setPassword($tmp[1]);
    }

    /**
     * @param array $parts
     * @return string
     */
    private function parseUrl(array $parts): string
    {
        $url = $this->srvUrl;
        foreach ($parts as $part) {
            $url .= urlencode($part) . '/';
        }

        return $url;
    }

    public function setCalenedar(string $calID): self
    {
        $this->calendarID = $calID;
        return $this;
    }
}
