<?php

namespace Ginov\CaldavPlugs\Plateforms\Bluemind;

use Ginov\CaldavPlugs\PlateformUserInterface;
use JsonSerializable;

class BluemindUser implements PlateformUserInterface
{
    private string $uid;
    
    private string $domainUid;

    private string $token;

    private string $username;

    private string $password;

    public function getUid():string
    {
        return $this->uid;
    }

    public function setUid(string $uid):self
    {
        $this->uid = $uid;
        return $this;
    }

    public function getDomainUid():string
    {
        return $this->domainUid;
    }

    public function setDomainUid(string $domainUid):self
    {
        $this->domainUid = $domainUid;
        return $this;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function __toString(): string
    {
        return $this->token . ';' . $this->uid . ';' . $this->domainUid;
    }

    public static function parseCredentials(string $credentials): PlateformUserInterface
    {
        $tmp = explode(';', $credentials);

        return (new BluemindUser())
            ->setToken($tmp[1]);
    }
}