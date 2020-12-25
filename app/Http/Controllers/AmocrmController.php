<?php

namespace App\Http\Controllers;

use AmoCRM\Models\NoteModel;
use Illuminate\Http\Request;
use League\OAuth2\Client\Token\AccessToken;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\NullTagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NullCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use League\OAuth2\Client\Token\AccessTokenInterface;
use AmoCRM\Models\NoteType\ServiceMessageNote;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\LinkModel;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Collections\NotesCollection;
use AmoCRM\Models\NoteType\CommonNote;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\TaskModel;

define('TOKEN_FILE', DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'token_info.json');

class AmocrmController extends Controller
{
    //
    private $testFieldId = 281921;
    private $apiClient;

    public function __construct()
    {
        $clientId = "c0c00301-d897-40f5-b81a-9e290212d37f";
        $clientSecret = "FRdNO6sY3nb4IhWUUvb2uS4m66051vmcQwIb9s2fL8O5zVXRWtRjW9brYJVnlUTt";
        $redirectUri = "https://google.com";
        $redirectUri = "https://digital-business-test-project.herokuapp.com/redirect-uri";

        $this->apiClient = new \AmoCRM\Client\AmoCRMApiClient($clientId, $clientSecret, $redirectUri);
    }

    public function index()
    {
        $state = "";

        $button = $this->apiClient->getOAuthClient()->getOAuthButton(
            [
                'title' => 'Установить интеграцию',
                'compact' => false,
                'class_name' => 'className',
                'color' => 'default',
                'error_callback' => 'handleOauthError',
                'state' => $state,
            ]
        );

        return view("amocrm.index", ["button" => $button]);
    }

    public function redirectUriHandle()
    {
        if (isset($_GET['referer'])) {
            $this->apiClient->setAccountBaseDomain($_GET['referer']);
        }

        try {
            $accessToken = $this->apiClient->getOAuthClient()->getAccessTokenByCode($_GET['code']);

            if (!$accessToken->hasExpired()) {
                $this->saveToken([
                    'accessToken' => $accessToken->getToken(),
                    'refreshToken' => $accessToken->getRefreshToken(),
                    'expires' => $accessToken->getExpires(),
                    'baseDomain' => $this->apiClient->getAccountBaseDomain(),
                ]);
            }

        } catch (Exception $e) {
            die((string)$e);
        }

        return redirect(route("amocrm.get-leads"));
    }

    public function getLeads()
    {
        $this->apiClient->setAccessToken($this->getToken())
            ->setAccountBaseDomain($this->getToken()->getValues()['baseDomain'])
            ->onAccessTokenRefresh(
                function (AccessTokenInterface $accessToken, string $baseDomain) {
                    saveToken(
                        [
                            'accessToken' => $accessToken->getToken(),
                            'refreshToken' => $accessToken->getRefreshToken(),
                            'expires' => $accessToken->getExpires(),
                            'baseDomain' => $baseDomain,
                        ]
                    );
                }
            );

        try {
            $leads = $this->apiClient->leads()->get(null, [LeadModel::CONTACTS]);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        try {
            $usersCollection = $this->apiClient->users()->get();
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        try {
            $contacts = $this->apiClient->contacts()->get();
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        try {
            $companies = $this->apiClient->companies()->get();
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        return view("amocrm.leads", [
            "leads" => $leads,
            "users" => $usersCollection,
            "contacts" => $contacts,
            "companies" => $companies
        ]);
    }

    public function sendTestLead()
    {
        $apiClient = $this->apiClient;

        $this->apiClient->setAccessToken($this->getToken())->setAccountBaseDomain($this->getToken()->getValues()['baseDomain'])
        ;
        $leadsService = $this->apiClient->leads();

        $lead = new LeadModel();
        $leadCustomFieldsValues = new CustomFieldsValuesCollection();
        $textCustomFieldValueModel = new TextCustomFieldValuesModel();
        $textCustomFieldValueModel->setFieldId(281921);
        $textCustomFieldValueModel->setValues(
            (new TextCustomFieldValueCollection())
                ->add((new TextCustomFieldValueModel())->setValue('Текст'))
        );
        $leadCustomFieldsValues->add($textCustomFieldValueModel);
        $lead->setCustomFieldsValues($leadCustomFieldsValues);
        $lead->setName('Example');

        $leadsCollection = new LeadsCollection();
        $leadsCollection->add($lead);



        $company = new CompanyModel();
        $company->setName('Example company');

        try {
            $company = $apiClient->companies()->addOne($company);
        } catch (AmoCRMApiException $e) {
            dump($e);
            die;
        }

        //dump($company);

        $contact = new ContactModel();
        $contact->setName('Example Contact');

        try {
            $contactModel = $apiClient->contacts()->addOne($contact);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        $lead->setCompany($company);

        try {
            $lead = $leadsService->addOne($lead);
        } catch (AmoCRMApiException $e) {
            dump($e);
            die;
        }

        //dump($lead);

        $usersService = $this->apiClient->users();

        try {
            $usersCollection = $usersService->get();
           // dump($usersCollection);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        date_default_timezone_set('Europe/Moscow');

        $notesCollection = new NotesCollection();
        $serviceMessageNote = new CommonNote();
        $serviceMessageNote->setEntityId($lead->getId())
            ->setText('Текст примечания')
            ->setCreatedBy(0);

        $notesCollection->add($serviceMessageNote);

        try {
            $leadNotesService = $this->apiClient->notes(EntityTypesInterface::LEADS);
            $notesCollection = $leadNotesService->addOne($serviceMessageNote);
        } catch (AmoCRMApiException $e) {
            dump($e);
            die;
        }

        $task = new TaskModel();
        $task->setTaskTypeId(TaskModel::TASK_TYPE_ID_CALL)
            ->setText('Тестовая задача')
            ->setCompleteTill(strtotime('tomorrow'))
            ->setEntityType(EntityTypesInterface::LEADS)
            ->setEntityId($lead->getId())
            ->setResponsibleUserId(0);
        try {
            $apiClient->tasks()->addOne($task);
        } catch (AmoCRMApiException $e) {
            dump($e);
            die;
        }

        $links = new LinksCollection();
        $links->add($lead);
        //$links->add($company);
        try {
            $apiClient->contacts()->link($contactModel, $links);
            $apiClient->companies()->link($company, $links);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        return redirect(route("amocrm.get-leads"));
    }

    /**
     * @param array $accessToken
     */
    function saveToken($accessToken)
    {
        if (
            isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            $data = [
                'accessToken' => $accessToken['accessToken'],
                'expires' => $accessToken['expires'],
                'refreshToken' => $accessToken['refreshToken'],
                'baseDomain' => $accessToken['baseDomain'],
            ];

            file_put_contents(TOKEN_FILE, json_encode($data));
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }

    function getToken()
    {
        if (!file_exists(TOKEN_FILE)) {
            exit('Access token file not found');
        }

        $accessToken = json_decode(file_get_contents(TOKEN_FILE), true);

        if (
            isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            return new AccessToken([
                'access_token' => $accessToken['accessToken'],
                'refresh_token' => $accessToken['refreshToken'],
                'expires' => $accessToken['expires'],
                'baseDomain' => $accessToken['baseDomain'],
            ]);
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }


}
