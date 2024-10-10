<?php

namespace Ginov\CaldavPlugs\Dto;

use JsonSerializable;

class Attendee implements JsonSerializable{

    private ?string $name;
    private string $email;
    private bool $rvps;

    public function __construct($email, bool $rvps = FALSE, $name='no name')
    {
        $this->rvps = $rvps;
        $this->email = $email;
        $this->name = $name;
    }

    public function getName(){
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail():string
    {
        return $this->email;
    }

    public function setEmail(string $email):self
    {
        $this->email = $email;
        return $this;
    }

    public function getRvps():bool
    {
        return $this->rvps;
    }

    public function setRvps(bool $rvps):self
    {
        $this->rvps = $rvps;
        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'rvps' => $this->rvps
        ];
    }

}