<?php

namespace Ginov\CaldavPlugs;

use Ginov\CaldavPlugs\Dto\CalendarCalDAV;
use Ginov\CaldavPlugs\Dto\EventCalDAV;
use Ginov\CaldavPlugs\PlateformUserInterface;
use Symfony\Component\HttpFoundation\Request;

interface OAuthInterface
{
    /**
     *  @return string
     */
    public function getOAuthUrl():string;

    /**
     * @param Request $request
     * @return array
     */
    public function getOAuthToken(Request $request):array;

    // public function oAuthCallback();

    public function refreshOAUthToken();

}