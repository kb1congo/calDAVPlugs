<?php

namespace Ginov\CaldavPlugs\Plateforms\Zimbra;

use Ginov\CaldavPlugs\Plateforms\Baikal;
use Ginov\CaldavPlugs\PlateformUserInterface;
use Symfony\Component\HttpFoundation\Request;
use Ginov\CaldavPlugs\Plateforms\Credentials\BasicUser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ZimbraTokenUser implements PlateformUserInterface
{
   private string $token;
   private string $calID;

   public function getSettings(): array
   {
      return [
         'settings' => [],
         'header' => [
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type' => 'application/xml; charset=utf-8',
            'Depth' => 1
         ]
      ];
   }

   public function __toString(): string
   {
      return $this->token . ';' . $this->calID;
   }

   /**
    * Undocumented function
    *
    * @param string $credentials
    * @return ZimbraUser
    */
   public static function parseCredentials(string $credentials): PlateformUserInterface
   {
      $tmp = explode(';', $credentials);

      return new ZimbraTokenUser();
   }

   /**
    * Undocumented function
    *
    * @return string|null
    */
   public function getToken(): ?string
   {
      return $this->token;
   }

   /**
    * Undocumented function
    *
    * @param string $token
    * @return self
    */
   public function setToken(string $token): self
   {
      $this->token = $token;
      return $this;
   }

   /**
    * Get the value of calID
    *
    * @return string
    */
   public function getCalID(): string
   {
      return $this->calID;
   }

   /**
    * Set the value of calID
    *
    * @param string $calID
    * @return self
    */
   public function setCalID(string $calID): self
   {
      $this->calID = $calID;

      return $this;
   }
}

class ZimbraUser implements PlateformUserInterface
{
   private string $username;
   private string $password;
   private string $calID;
   private string $token;

   /**
    * Get the value of username
    *
    * @return string
    */
   public function getUsername(): string
   {
      return $this->username;
   }

   /**
    * Set the value of username
    *
    * @param string $username
    * @return self
    */
   public function setUsername(string $username): self
   {
      $this->username = $username;

      return $this;
   }

   /**
    * Get the value of password
    *
    * @return string
    */
   public function getPassword(): string
   {
      return $this->password;
   }

   /**
    * Set the value of password
    *
    * @param string $password
    * @return self
    */
   public function setPassword(string $password): self
   {
      $this->password = $password;

      return $this;
   }

   /**
    * Get the value of calID
    *
    * @return string
    */
   public function getCalID(): string
   {
      return $this->calID;
   }

   /**
    * Set the value of calID
    *
    * @param string $calID
    * @return self
    */
   public function setCalID(string $calID): self
   {
      $this->calID = $calID;

      return $this;
   }

   public function __toString(): string
   {
      return $this->username . ';' . $this->password . ';' . $this->calID;
   }

   /**
    * Undocumented function
    *
    * @param string $credentials
    * @return ZimbraUser
    */
   public static function parseCredentials(string $credentials): PlateformUserInterface
   {
      $tmp = explode(';', $credentials);

      return new BasicUser();

      return (\count($tmp) == 3)
         ? (new ZimbraUser())
         ->setUsername($tmp[0])
         ->setPassword($tmp[1])
         ->setCalID($tmp[2])
         : (new ZimbraTokenUser())
         ->setCalID($tmp[0])
         ->setToken($tmp[1]);
   }
}

class Zimbra extends Baikal
{

   public function __construct(ParameterBagInterface $parameter)
   {
      $this->srvUrl = $parameter->get('baikal.srv.url');
   }

   /* public function login(Request $request): PlateformUserInterface
   {
      return new ZimbraUser();
   } */

   /**
    * Undocumented function
    *
    * @param Request $request
    * @return ZimbraUser
    */
   public function kokokoo(Request $request): PlateformUserInterface
   {
      /**@var ZimbraUser $user */
      $user = (new ZimbraUser())
         // ->setToken($request->request->get('token', null))
         ->setUsername($request->request->get('username'))
         ->setPassword($request->request->get('password'))
         ->setCalID($request->request->get('cal_name'));

      return $user;
   }

   /**
    * Undocumented function
    *
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

   public function setCalendar(string $calID): self
   {
      $this->calendarID = $calID;
      return $this;
   }
}
