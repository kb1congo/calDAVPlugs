<?php

namespace Ginov\CaldavPlugs;


interface PlateformUserInterface{

    public function __toString():string;
    

    public static function parseCredentials(string $credentials): PlateformUserInterface;

}