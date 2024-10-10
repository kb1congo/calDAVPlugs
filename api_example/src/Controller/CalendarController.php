<?php

namespace App\Controller;

use DateTime;
use App\JwtTool;
use App\HttpTools;
use App\Security\User;
use Ginov\CaldavPlugs\Factory;
use Ginov\CaldavPlugs\OAuthInterface;
use Ginov\CaldavPlugs\Dto\EventCalDAV;
use Ginov\CaldavPlugs\Plateforms\Google;
use Ginov\CaldavPlugs\Dto\CalendarCalDAV;
use Ginov\CaldavPlugs\Plateforms\Outlook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CalendarController extends AbstractController
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    #[IsGranted('ROLE_USER', message: 'Acces denied', statusCode: Response::HTTP_UNAUTHORIZED)]
    #[Route('/{plateform}/calendars', name: 'api_calendars', methods: ['GET'])]
    public function getCalendars(string $plateform): JsonResponse
    {
        /** @var \App\Security\User */
        $user = $this->getUser();

        $plateformInstance = Factory::create($plateform, $this->parameterBag);

        return $this->json([
            'token' => 'token',
            'calendars' => $plateformInstance->getCalendars($user->getCredentials())
        ], Response::HTTP_OK);
    }

    #[IsGranted('ROLE_USER', message: 'Acces denied', statusCode: Response::HTTP_UNAUTHORIZED)]
    #[Route('/{plateform}/calendar/{calendar_id}', name: 'api_calendar', methods: ['GET'])]
    public function getCalendar(string $plateform, string $calendar_id): JsonResponse
    {
        /** @var \App\Security\User */
        $user = $this->getUser();

        $plateformInstance = Factory::create($plateform, $this->parameterBag);

        return $this->json([
            'token' => 'token',
            'calendars' => $plateformInstance->getCalendar($user->getCredentials(), $calendar_id)
        ], Response::HTTP_OK);
    }

    #[IsGranted('ROLE_USER', message: 'Acces denied', statusCode: Response::HTTP_UNAUTHORIZED)]
    #[Route('/{plateform}/calendar', 'api_add_cal', methods: ['POST'])]
    public function addCalendar(string $plateform, #[MapRequestPayload] CalendarCalDAV $calendar): JsonResponse 
    {
        /** @var \App\Security\User */
        $user = $this->getUser();

        $plateformInstance = Factory::create($plateform, $this->parameterBag);

        return $this->json([
            'token' => 'token',
            'calendar' => $plateformInstance->createCalendar($user->getCredentials(), $calendar)
        ], Response::HTTP_CREATED);
    }

    #[IsGranted('ROLE_USER', message: 'Acces denied', statusCode: Response::HTTP_UNAUTHORIZED)]
    #[Route('/{plateform}/calendar/{calendar_id}', 'api_del_cal', methods: ['DELETE'])]
    public function delCalendar(string $plateform, string $calendar_id): JsonResponse
    {
        /** @var \App\Security\User */
        $user = $this->getUser();

        $plateformInstance = Factory::create($plateform, $this->parameterBag);

        return $this->json([
            'token' => 'token',
            'calendar_id' => $plateformInstance->deleteCalendar($user->getCredentials(), $calendar_id)
        ], Response::HTTP_OK);
    }

    #[IsGranted('ROLE_USER', message: 'Acces denied', statusCode: Response::HTTP_UNAUTHORIZED)]
    #[Route('/{plateform}/calendar/{calendar_id}', 'api_maj_cal', methods: ['PUT'])]
    public function updateCalendar(string $plateform, string $calendar_id, #[MapRequestPayload] CalendarCalDAV $calendar): JsonResponse
    {
        /** @var \App\Security\User */
        $user = $this->getUser();

        $plateformInstance = Factory::create($plateform, $this->parameterBag);

        return $this->json([
            'token' => 'token',
            'calendar' => $plateformInstance->updateCalendar($user->getCredentials(), $calendar->setCalendarID($calendar_id))
        ]);
    }

    private function parseError(ConstraintViolationListInterface $errors): array
    {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }

        return $errorMessages;
    }
}
