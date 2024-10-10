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

class AuthController extends AbstractController
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    #[Route('/code/{plateform}', name: 'oauth_code', methods: ['GET'])]
    public function oAuthCode(string $plateform): JsonResponse
    {
        /** @var OAuthInterface */
        $plateformInstance = Factory::create($plateform, $this->params);

        return $this->json([
            'url' => urldecode($plateformInstance->getOAuthUrl()),
            'message' => 'In your browser go to url above'
        ], Response::HTTP_OK);
    }

    #[Route('/{plateform}/oauth2callback.php', name: 'oauth_callback', methods: ['GET'])]
    public function oAuthCallback(Request $request, string $plateform): JsonResponse
    {
        /** @var OAuthInterface */
        $plateformInstance = Factory::create($plateform, $this->params);
        $token = $plateformInstance->getOAuthToken($request);

        return $this->json($token);
    }

    #[Route('/{plateform}/login', name: 'login', methods: ['POST'])]
    public function login(string $plateform, Request $request): JsonResponse
    {
        $plateformInstance = Factory::create($plateform, $this->params);

        $user = (new User)
            ->setCredentials($plateformInstance->login($request));

        // gen jwt token
        $jwt = JwtTool::encode($this->getParameter('jwt.api.key'), $user);

        return $this->json([
            'token' => $jwt,
            'calendars' => $plateformInstance->getCalendars($user->getCredentials())
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
