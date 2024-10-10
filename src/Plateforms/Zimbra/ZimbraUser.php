<?php

namespace Ginov\CaldavPlugs\Plateforms\Credentials;

use Ginov\CaldavPlugs\Plateforms\Zimbra\Zimbra;
use Ginov\CaldavPlugs\PlateformUserInterface;
use JsonSerializable;

class ZimbraUser implements PlateformUserInterface
{
    public function __toString(): string
    {
        return '';
    }
    
    public static function parseCredentials(string $credentials): PlateformUserInterface
    {
        $tmp = explode(';', $credentials);

        return (new ZimbraUser());
    }
}