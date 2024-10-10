# CalDAV-plugs

### 1. **Introduction**
   - **Description générale** : `CalDAV-plugs` est une bibliothèque PHP qui permet d'interagir via une interface unique avec les API calendriers de Google, Outlook, Zimbra, et BlueMind, avec la possibilité d'ajouter facilement d'autres plateformes non supportées.
   - **Fonctionnalités principales** : `CalDAV-plugs` permet de se connecter à un serveur de calendrier et de gérer les calendriers et événements : ajout, édition, suppression, et consultation. Cela fonctionne indépendamment des spécificités des plateformes (Google, Outlook, etc.).
   - **Technologies** : La bibliothèque a été testée et conçue avec `PHP version 8.1.29` et `Composer version 2.7.7`. L'environnement de test utilise `Symfony 7`.
   - **Installation rapide** : `CalDAV-plugs` est compatible PSR-4, elle s'installe donc aisément grâce à la commande :

   ```bash
   composer require ginov/caldav-plugs
   ```

   Mais étant en phase de developpement nous vous invitons à consulter la section **Guide d'installation** de ce document pour bien configurer l'application

### 2. **Prérequis**
   - **Environnement minimum requis** : PHP version 8.1.29

### 3. **Guide d'installation**
1. (en dev) Placez `CalDAV-plugs` dans le même dossier que votre application de test.

**Structure :**
```text
/mon-dossier
├── caldav-plugs/
│   ├── Plateforms
│   │   ├── Google/
│   │   ├── Outlook/
│   │   └── Bluemind/
│   ├── PlateformUserInterface.php
│   ├── PlateformInterface.php
│   └── Factory.php
└── MonProjetTest/  ##api/
    └── composer.json
```

2. (en dev) Dans le `composer.json` de votre application de test, ajoutez les nœuds `repositories` et `"ginov/caldav-plugs": "dev-main"` dans la section `require`.

**Exemple :**
```json
{
    "require": {
        "ginov/caldav-plugs": "1.0.0"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../../calDAV/caldav-plugs"
        }
    ],
    "autoload": {
        "psr-4": {
            "MonProjetTest\\": "src/"
        }
    }
}
```

3. Pour installer le plugin dans votre application de test, exécutez :

```bash
composer install
```

4. **Configuration** :

Dans le fichier `.env` de votre application de test, ajoutez les constantes suivantes :

**Exemple pour Google et Outlook** :
```text
###> Google keys ###
GOOGLE_CLIENT_ID=idclient
GOOGLE_CLIENT_SECRET=secret
GOOGLE_SCOPE=https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/calendar.events
GOOGLE_REDIRECT_URI=oauth2callback_uri
GOOGLE_SRV_URL=https://www.googleapis.com/calendar/v3/
GOOGLE_CALDAV_URL=https://apidata.googleusercontent.com/caldav/v2/
GOOGLE_OAUTH_CALLBACK_URL=https://oauth2.googleapis.com
###< Google keys ###

###> Outlook keys ###
OUTLOOK_CLIENT_ID=idclient
OUTLOOK_CLIENT_SECRET=secret
OUTLOOK_CLIENT_TENANT=tenant
OUTLOOK_SCOPE=Calendars.ReadWrite.Shared
OUTLOOK_REDIRECT_URI=oauth2callback_uri
OUTLOOK_SRV_URL=https://graph.microsoft.com/v1.0/
OUTLOOK_LOGIN_URL=https://login.microsoftonline.com/
OUTLOOK_OAUTH_CALLBACK_URL=https://oauth2.googleapis.com
###< Outlook keys ###
```
**NB**: Pour obtenir les paramètres des différentes plateformes, il faut inscrire votre application sur la dite plateforme, puis récupérer, entre autres, le `clientID`, le `secret`, et le `tenant` (dans le cas d'Outlook).

Dans le fichier `services.yaml`, ajoutez les clés qui seront mises à disposition par le `ParameterBag`.

**Exemple pour Google et Outlook** :
```yaml
#Google
google.client.id: "%env(GOOGLE_CLIENT_ID)%"
google.client.secret: "%env(GOOGLE_CLIENT_SECRET)%"
google.scope: "%env(GOOGLE_SCOPE)%"
google.redirect.uri: "%env(GOOGLE_REDIRECT_URI)%"
google.srv.url: "%env(GOOGLE_SRV_URL)%"
google.caldav.url: "%env(GOOGLE_CALDAV_URL)%"
google.oauth.callback.url: "%env(GOOGLE_OAUTH_CALLBACK_URL)%"

#Outlook
outlook.client.id: "%env(OUTLOOK_CLIENT_ID)%"
outlook.client.secret: "%env(OUTLOOK_CLIENT_SECRET)%"
outlook.client.tenant: "%env(OUTLOOK_CLIENT_TENANT)%"
outlook.scope: "%env(OUTLOOK_SCOPE)%"
outlook.redirect.uri: "%env(OUTLOOK_REDIRECT_URI)%"
outlook.srv.url: "%env(OUTLOOK_SRV_URL)%"
outlook.login.url: "%env(OUTLOOK_LOGIN_URL)%"
outlook.oauth.callback.url: "%env(OUTLOOK_OAUTH_CALLBACK_URL)%"
```

5. Utilisez le plugin dans votre code :

**Exemple simple** :
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Ginov\CaldavPlugs\Plateforms\Google;

$objet = new Google();

// Afficher l'url oAuth 2.0 code de google
echo $plateformInstance->getOAuthUrl();
```

**Exemple précédent avec la factory** :
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Ginov\CaldavPlugs\Plateforms\Google;
use Ginov\CaldavPlugs\Factory;
use Ginov\CaldavPlugs\OAuthInterface;

$plateform = "google";

// Instancier une plateforme en fonction de la string $plateform
/** @var OAuthInterface */
$plateformInstance = Factory::create($plateform, $this->parameterBag);

// Afficher l'url oAuth 2.0 code de google
echo $plateformInstance->getOAuthUrl();
```

### 4. **Architecture de la bibliothèque**

1. **Classes principales** : 
   - `Ginov\CaldavPlugs\PlateformInterface` : Interface qui présente les méthodes pour manipuler un agenda.
   - `Ginov\CaldavPlugs\Factory` : Factory permettant d'instancier une plateforme (Google, Outlook, etc.).
   - `Ginov\CaldavPlugs\OAuthInterface` : Interface gérant les interactions avec l'environnement OAuth 2.0.
   - `Ginov\CaldavPlugs\Plateforms\Google` : Connecteur pour la plateforme Google.
   - `Ginov\CaldavPlugs\Plateforms\Outlook` : Connecteur pour la plateforme Outlook.
   - `Ginov\CaldavPlugs\Plateforms\Credentials\GoogleUser` : Classe représentant l'ApiToken de Google.
   - `Ginov\CaldavPlugs\Plateforms\Credential\OutlookUser` : Classe représentant l'ApiToken d'Outlook.

2. **Diagramme UML simplifié**
   ![Diagram](https://www.mermaidchart.com/raw/970c9c6f-c463-4ee4-84aa-f12515b9e5c3?theme=light&version=v0.1&format=svg)


### 5. **Utilisation détaillée**

1. **Authentification** :  
   Google et Facebook utilisent OAuth 2.0 comme système d'authentification.
   ![Diagram](https://www.mermaidchart.com/raw/aa248e03-ceae-481b-88e8-b0b3657f9952?theme=light&version=v0.1&format=svg)

   Lorsqu'on utilise la `Factory`, voici les étapes :

   (1). Le front émet une requête vers le middleware sous la forme `/code/{plateform_name}` pour récupérer l'URL OAuth.  
   (6). Le front envoie une requête vers l'URL OAuth, ce qui déclenche l'appel de l'URL de callback préalablement enregistrée sur le serveur, par exemple `localhost:8000/callback.php`. L'`ApiToken` et le `RefreshToken` s'affichent côté front en réponse à la requête émise.  
   (12). Le front doit sauvegarder le `RefreshToken` en cas d'expiration de l'`ApiToken`.  
   (13). Le front peut alors s'authentifier sur le middleware. Ce dernier appelle la méthode `login(Request $request):PlateformUserInterface`. La méthode `login()` renvoie les identifiants à utiliser pour les requêtes suivantes. Une fois connecté, les identifiants retournés par la méthode `login()` sont disponibles soit en session, soit dans un token JWT ou toute autre méthode de gestion de session.

   **Exemple**: Pour plus de détails, voir le projet dans `/api/Controller/AuthController`.

   (1) Obtenir l'URL OAuth pour le code :
   ```php
    /*
     * Obtenir l'URL OAuth pour le code 
     * 
     * GET /code/{plateform_name}
     * Request header: none
     * Request body: none
     * Response: string */

    //...

    /** @var OAuthInterface */
    $plateformInstance = Factory::create($plateform, $this->parametersBag);
    
    // Afficher l'URL OAuth code
    echo $plateformInstance->getOAuthUrl();

    //...
   ```

   (6) Obtenir l'`ApiToken` + `RefreshToken` sur la route GET `localhost:8000/{plateform}/callback.php` enregistrée sur la plateforme. Dans le fichier `callback.php` :
   ```php
    /* Obtenir l'ApiToken + RefreshToken */

    //...
    
    /** @var OAuthInterface */
    $plateformInstance = Factory::create($plateform, $this->parametersBag);
    
    // Afficher l'ApiToken
    echo $plateformInstance->getOAuthToken();

    //...
   ```

   (13) Authentification :
   ```php
    /*
     * Se connecter pour obtenir les identifiants 
     * 
     * POST /{plateform}/login
     * Request header: none
     * Request body: 
     *  + token: string (le token API Google ou Outlook)
     *  + owner_email: string (Outlook uniquement)
     *  + owner_name: string (Outlook uniquement)
     * Response: string */

    //...
    
    /** @var OAuthInterface */
    $plateformInstance = Factory::create($plateform, $this->parametersBag);
    // ou sans factory
    $plateformInstance = new Google($this->parametersBag);

    // Sauvegarder les identifiants pour la prochaine requête
    $_SESSION['credentials'] = $plateformInstance->login($request);

    //...
   ```

2. **Gestion des calendriers** :

   + Lister tous les calendriers une fois authentifié :
   ![Diagram](https://www.mermaidchart.com/raw/8d892347-a5e3-43c5-aee8-b2bb26edde53?theme=light&version=v0.1&format=svg)

   Lorsqu'on utilise la `Factory`, voici les étapes :

   (1) Une fois authentifié, le front émet une requête vers le middleware sous la forme `/{plateform}/calendars` pour récupérer la liste des agendas.  
   (2) Le middleware extrait les identifiants et appelle la méthode `getCalendars(credentials):CalendarCalDAV[]` du connecteur.  
   (3) La liste des calendriers est envoyée au front.

   **Exemple** :
   ```php
    /*
     * Récupérer tous les calendriers 
     * 
     * GET /calendars
     * Request header: none
     * Request body: none
     * Response: CalendarCalDAV[] */

    //...

    // L'utilisateur est authentifié

    $plateformInstance = Factory::create($plateform, $this->parametersBag);
    
    $credentials = $_SESSION['credentials'];

    $calendars = $plateformInstance->getCalendars($credentials);

    //...
   ```

   + Récupérer un calendrier par son ID une fois authentifié :
   ![Diagram](https://www.mermaidchart.com/raw/4366fa19-bfc2-456d-a53d-f8f5c2e5ba6a?theme=light&version=v0.1&format=svg)

   **Exemple** :
   ```php
    /*
     * Récupérer un calendrier
     * 
     * GET /{plateform}/calendar/{calendar_id}
     * Request header: none
     * Request body: none
     * Response: CalendarCalDAV */

    //...

    // L'utilisateur est authentifié

    $plateformInstance = Factory::create($plateform, $this->parametersBag);
    
    $credentials = $_SESSION['credentials'];

    $calendar = $plateformInstance->getCalendar($credentials, $calendar_id);

    //...
   ```

   + Créer un calendrier :

   **Exemple** :
   ```php
    /*
     * Créer un calendrier
     * 
     * POST /{plateform}/calendar
     * Request header: none
     * Request body: formData 
     *  + displayname: string (obligatoire)
     *  + description: string
     *  + timeZone: string (par défaut: Europe/Paris)
     * Response: status 201; CalendarCalDAV */

    //...

    // L'utilisateur est authentifié

    $plateformInstance = Factory::create($plateform, $this->parametersBag);
    
    $credentials = $_SESSION['credentials'];

    $calendar = (new CalendarCalDAV($request->request->get('displayname')))
        ->setDescription($request->request->get('description'))
        ->setTimeZone($request->request->get('timeZone', 'Europe/Paris'));

    $calendar = $plateformInstance->createCalendar($credentials, $calendar);

    //...
   ```

   + Modifier un calendrier :
   ```php
    /*
     * Modifier un calendrier
     * 
     * PUT /{plateform}/calendar/{calendar_id}
     * Request header: none
     * Request body: formData 
     *  + displayname: string (obligatoire)
     *  + description: string
     *  + timeZone: string (par défaut: Europe/Paris)
     * Response: status 200; CalendarCalDAV */

    //...

    // L'utilisateur est authentifié

    $plateformInstance = Factory::create($plateform, $this->parametersBag);
    
    $credentials = $_SESSION['credentials'];

    $calendar = (new CalendarCalDAV($request->request->get('displayname')))
        ->setCalendarId($calendar_id)
        ->setDescription($request->request->get('description'))
        ->setTimeZone($request->request->get('timeZone', 'Europe/Paris'));

    $calendar = $plateformInstance->updateCalendar($credentials, $calendar_id, $calendar);

    //...
   ```

   + Supprimer un calendrier :
   ```php
    /*
     * Supprimer un calendrier
     * 
     * DELETE /{plateform}/calendar/{calendar_id}
     * Request header: none
     * Request body: none
     * Response: status 200; string calendar_id */

    //...

    // L'utilisateur est authentifié

    $plateformInstance = Factory::create($plateform, $this->parametersBag);
    
    $credentials = $_SESSION['credentials'];

    $calendar_id = $plateformInstance->deleteCalendar($credentials, $calendar_id);

    //...
   ```

3. **Gestion des événements** :

   + Lister tous les événements une fois authentifié :
   ![Diagram](https://www.mermaidchart.com/raw/9d5db369-1340-4a29-ba77-709a6c6a57a7?theme=light&version=v0.1&format=svg)

   Lorsqu'on utilise la `Factory`, voici les étapes :

   (1) Une fois authentifié, le front émet une requête vers le middleware sous la forme `/{plateform}/events/{calID}/{time_max}/{time_min}` pour récupérer la liste des événements tels que `dateEnd < time_max AND dateStart > time_min AND time_max > time_min > 0` (time_min et time_max étant des timestamps).  
   (3) Le middleware extrait les identifiants et appelle la méthode `getEvents(credentials, calendar_id, time_min, time_max):EventCalDAV[]` du connecteur.  
   (8) La liste des événements est envoyée au front.

   **Exemple** :
   ```php
    /*
     * Récupérer tous les événements
     * 
     * GET /{plateform}/events/{calID}/{time_max}/{time_min}
     * Request header: none
     * Request body: none
     * Response: EventCalDAV[] */

    //...

    // L'utilisateur est authentifié

    $plateformInstance = Factory::create($plateform, $this->parametersBag);
    
    $credentials = $_SESSION['credentials'];

    $events = $plateformInstance->getEvents($credentials, $calendar_id, $time_min, $time_max);

    //...
   ```

+ Créer un événement.

**Exemple**:
```php
    /*
     * Créer un événement
     * 
     * POST /{plateform}/event/{calID}
     * Request header: none
     * Request body: formData 
     *  + summary: string
     *  + dateStart: DateTime (obligatoire)
     *  + dateEnd: DateTime (obligatoire)
     *  + timeZone: string (par défaut : Europe/Paris)
     *  + attendees[] (optionnel)
     *      attendees[0][email] string, 
     *      attendees[0][name] string (optionnel)
     * Response: status 201; EventCalDAV */

    //...

    // L'utilisateur est authentifié

    $plateformInstance = Factory::create($plateform, $this->parametersBag);
    
    $credentials = $_SESSION['credentials'];

    $event = (new EventCalDAV())
        ->setSummary($request->request->get('summary'))
        ->setDateStart(new \DateTime($request->request->get('dateStart')))
        ->setDateEnd(new \DateTime($request->request->get('dateEnd')))
        ->setTimeZone($request->request->get('timeZone', 'Europe/Paris'));

    $createdEvent = $plateformInstance->createEvent($credentials, $calID, $event);

    //...
```

+ Modifier un événement.

**Exemple**:
```php
    /*
     * Modifier un événement
     * 
     * PUT /{plateform}/calendars/{calendar_id}/events/{event_id}
     * Request header: none
     * Request body: formData 
     *  + summary: string
     *  + dateStart: DateTime (obligatoire)
     *  + dateEnd: DateTime (obligatoire)
     *  + timeZone: string (par défaut : Europe/Paris)
     *  + attendees[] (optionnel)
     *      attendees[0][email] string, 
     *      attendees[0][name] string (optionnel)
     * Response: status 200; EventCalDAV */

    //...

    // L'utilisateur est authentifié

    $plateformInstance = Factory::create($plateform, $this->parametersBag);
    
    $credentials = $_SESSION['credentials'];

    $event = (new EventCalDAV())
        ->setSummary($request->request->get('summary'))
        ->setDateStart(new \DateTime($request->request->get('dateStart')))
        ->setDateEnd(new \DateTime($request->request->get('dateEnd')))
        ->setTimeZone($request->request->get('timeZone', 'Europe/Paris'));

    $updatedEvent = $plateformInstance->updateEvent($credentials, $calendar_id, $event_id, $event);

    //...
```

+ Supprimer un événement.

**Exemple**:
```php
    /*
     * Supprimer un événement
     * 
     * DELETE /{plateform}/event/{calID}/{eventID}
     * Request header: none
     * Request body: none
     * Response: status 204; string eventID */

    //...

    // L'utilisateur est authentifié

    $plateformInstance = Factory::create($plateform, $this->parametersBag);
    
    $credentials = $_SESSION['credentials'];

    $deletedEventID = $plateformInstance->deleteEvent($credentials, $calID, $eventID);

    //...
```
