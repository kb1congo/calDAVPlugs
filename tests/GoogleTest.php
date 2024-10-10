<?php

namespace Ginov\CaldavPlugs\Tests;

use JsonException;
use PHPUnit\Framework\TestCase;
use Ginov\CaldavPlugs\Utils\Http;
use Ginov\CaldavPlugs\Plateforms\Google\Google;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GoogleTest extends TestCase{

   public function getGoogle()
   {
      return $this->getMockBuilder(Google::class)
      ->disableOriginalConstructor()
      ->getMock();
   }

   public function testLoginWithoutTokenParam()
   {
      /** @var Google */
      $google = $this->getGoogle();

      $this->expectException(\Exception::class);

      $google->login(new Request());
   }

   public function testLoginWithTokenParam()
   {
      /** @var Google */
      $google = $this->getGoogle();

      $this->expectException(\Exception::class);

      $google->login(new Request([], ['token' => 'token']));
   }

   public function testGetCalendarWithBadCredentials()
   {
      $http = $this->getMockBuilder(Http::class)
         ->onlyMethods(['sendHttpRequest'])
         ->getMock();
      
      $http->method('sendHttpRequest')->willReturn((new Response('', Response::HTTP_FORBIDDEN)));
      
      $http->expects($this->once())->method('sendHttpRequest')->with('GET', '');

      /** @var Google */
      $google = $this->getGoogle();

      $google->getCalendars('maltazar');

   }
}