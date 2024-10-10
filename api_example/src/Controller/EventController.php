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

class EventController extends AbstractController
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    #[IsGranted('ROLE_USER', message: 'Acces denied', statusCode: Response::HTTP_UNAUTHORIZED)]
    #[Route('/{plateform}/events/{calID}/{time_max}/{time_min}', name: 'api_events', methods: ['GET'])]
    public function getEvents(string $plateform, string $calID, int $time_max = 0, int $time_min = 0): JsonResponse
    {
        /** @var \App\Security\User */
        $user = $this->getUser();

        $plateformInstance = Factory::create($plateform, $this->params);

        $events = $plateformInstance->getEvents($user->getCredentials(), $calID, $time_min, $time_max);

        return $this->json([
            'event' => $events,
            'token' => 'token'
        ], Response::HTTP_OK);
    }

    #[IsGranted('ROLE_USER', message: 'Acces denied', statusCode: Response::HTTP_UNAUTHORIZED)]
    #[Route('/{plateform}/event/{calID}/{eventID}', name: 'api_event', methods: ['GET'])]
    public function getEvent(string $plateform, string $calID, string $eventID): JsonResponse
    {
        /** @var \App\Security\User */
        $user = $this->getUser();

        $plateformInstance = Factory::create($plateform, $this->params);

        $event = $plateformInstance->getEvent($user->getCredentials(), $calID, $eventID);

        return $this->json([
            'event' => $event,
            'token' => 'token'
        ], Response::HTTP_OK);
    }

    #[IsGranted('ROLE_USER', message: 'access denied', statusCode: Response::HTTP_UNAUTHORIZED)]
    #[Route('/{plateform}/event/{calID}', 'api_add_event', methods: ['POST'])]
    public function addEvent(
        string $plateform,
        string $calID,
        ValidatorInterface $validator,
        SerializerInterface $serializer, 
        #[MapRequestPayload] EventCalDAV $event
    ): JsonResponse {
        /** @var App\Security\User */
        $user = $this->getUser();

        $errors = $validator->validate($event);

        if (count($errors) > 0) {
            $this->parseError($errors);
            return $this->json($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST);
        }

        if ($event->getDateEnd() <= $event->getDateStart())
            return $this->json('Invalide date', Response::HTTP_BAD_REQUEST);

        $plateformInstance = Factory::create($plateform, $this->params);
        $newEventOnServer = $plateformInstance->createEvent($user->getCredentials(), $calID, $event);

        return $this->json([
            'token' => 'token',
            'cal_id' => $calID,
            'event' => $newEventOnServer
        ], Response::HTTP_CREATED);
    }

    #[IsGranted('ROLE_USER', message: 'Acces denied', statusCode: Response::HTTP_UNAUTHORIZED)]
    #[Route('/{plateform}/calendars/{calendar_id}/events/{event_id}', 'api_maj_event', methods: ['PUT'])]
    public function updateEvent(string $plateform, string $calendar_id, string $event_id, #[MapRequestPayload] EventCalDAV $event): JsonResponse
    {
        /** @var \App\Security\User */
        $user = $this->getUser();

        $plateformInstance = Factory::create($plateform, $this->params);

        return $this->json([
            'token' => 'token',
            'calendar' => $plateformInstance->updateEvent(
                $user->getCredentials(),
                $calendar_id,
                $event_id,
                $event,
            )
        ]);
    }

    #[IsGranted('ROLE_USER', message: 'Acces denied', statusCode: Response::HTTP_UNAUTHORIZED)]
    #[Route('/{plateform}/event/{calID}/{eventID}', name: 'api_del_event', methods: ['DELETE'])]
    public function deleteEvent(string $plateform, string $calID, string $eventID): JsonResponse
    {
        /** @var \App\Security\User */
        $user = $this->getUser();

        $plateformInstance = Factory::create($plateform, $this->params);

        $event = $plateformInstance->deleteEvent($user->getCredentials(), $calID, $eventID);

        return $this->json([
            'event' => $event,
            'token' => 'token'
        ], Response::HTTP_OK);
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
