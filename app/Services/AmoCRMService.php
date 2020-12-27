<?php


namespace App\Services;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CompaniesCollection;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\UsersCollection;
use AmoCRM\Exceptions\AmoCRMApiNoContentException;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use League\OAuth2\Client\Token\AccessToken;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\LeadModel;
use League\OAuth2\Client\Token\AccessTokenInterface;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\LinkModel;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\NoteType\CommonNote;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\TaskModel;

define('TOKEN_FILE', DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'token_info.json');

class AmoCRMService
{
    public function __construct()
    {
        $this->apiClient = new AmoCRMApiClient(
            env("AMOCRM_CLIENT_ID"),
            env("AMOCRM_CLIENT_SECRET"),
            env("AMOCRM_REDIRECT_URI")
        );

        date_default_timezone_set('Europe/Moscow');
    }

    public function getLeads()
    {
        $this->setAccessTokenAndBaseDomainToApiClient();

        try {
            $leads = $this->apiClient->leads()->get(null, [EntityTypesInterface::CONTACTS]);
        } catch (AmoCRMApiNoContentException $e) {
            $leads = new LeadsCollection();
        } catch (AmoCRMApiException $e) {
            $leads = new LeadsCollection();
            dump($e);
        }

        return $leads;
    }

    public function getUsers()
    {
        $this->setAccessTokenAndBaseDomainToApiClient();

        try {
            $users = $this->apiClient->users()->get();
        } catch (AmoCRMApiNoContentException $e) {
            $users = new UsersCollection();
        } catch (AmoCRMApiException $e) {
            $users = new UsersCollection();
            dump($e);
        }

        return $users;
    }

    public function getCompanies()
    {
        $this->setAccessTokenAndBaseDomainToApiClient();

        try {
            $companies = $this->apiClient->companies()->get();
        } catch (AmoCRMApiNoContentException $e) {
            $companies = new CompaniesCollection();
        } catch (AmoCRMApiException $e) {
            $companies = new CompaniesCollection();
            dump($e);
        }

        return $companies;
    }

    public function getContacts()
    {
        $this->setAccessTokenAndBaseDomainToApiClient();

        try {
            $contacts = $this->apiClient->contacts()->get();
        } catch (AmoCRMApiNoContentException $e) {
            $contacts = new ContactsCollection();
        } catch (AmoCRMApiException $e) {
            $contacts = new ContactsCollection();
            dump($e);
        }

        return $contacts;
    }

    public function sendTestLead()
    {
        $this->setAccessTokenAndBaseDomainToApiClient();

        $lead = $this->createTestLead();
        $this->addTestNote($lead);
        $this->addTestTask($lead);
        $this->linkEntitiesToLead(
            [
                $this->createTestCompany(),
                $this->createTestContact(),
                $this->createTestContact(),
                $this->createTestContact(),
            ],
            $lead
        );
    }

    public function getIntegrationButton()
    {
        try{
            return $this->apiClient->getOAuthClient()->getOAuthButton(
                [
                    'title' => 'Установить интеграцию',
                    'compact' => false,
                    'class_name' => 'className',
                    'color' => 'default',
                    'error_callback' => 'handleOauthError',
                    'state' => "",
                ]
            );
        } catch (AmoCRMApiException $e){
            dump($e);
        }
    }

    public function createAccessTokenByRedirectUriRequest()
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

        } catch (AmoCRMApiException $e) {
            dump($e);
        }
    }

    protected function setAccessTokenAndBaseDomainToApiClient()
    {
        $this->apiClient->setAccessToken($this->getToken())
            ->setAccountBaseDomain($this->getToken()->getValues()['baseDomain']);
    }

    protected function saveToken($accessToken)
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

    protected function getToken()
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

    protected function linkEntitiesToLead($entities, $lead)
    {
        $links = (new LinksCollection());

        foreach ($entities as $entity)
        {
            $links->add(
                (new LinkModel())
                    ->setEntityId($lead->getId())
                    ->setEntityType(EntityTypesInterface::LEADS)
                    ->setToEntityId($entity->getId())
                    ->setToEntityType($entity->getType())
            );
        }

        try {
            $this->apiClient->links(EntityTypesInterface::LEADS)->add($links);
        } catch (AmoCRMApiException $exception) {
            dump($exception);
        }
    }

    protected function createTestLead()
    {
        $lead = new LeadModel();
        $lead->setName('Example Lead');

        try {
            return $this->apiClient->leads()->addOne($lead);
        } catch (AmoCRMApiException $e) {
            dd($e);
        }
    }

    protected function addTestNote($lead)
    {
        $commonNote = new CommonNote();
        $commonNote->setEntityId($lead->getId())
            ->setText('Текст примечания')
            ->setCreatedBy(0);

        try {
            return $this->apiClient->notes(EntityTypesInterface::LEADS)->addOne($commonNote);
        } catch (AmoCRMApiException $e) {
            dump($e);
        }
    }

    protected function addTestTask($lead)
    {
        $task = new TaskModel();
        $task->setTaskTypeId(TaskModel::TASK_TYPE_ID_CALL)
            ->setText('Тестовая задача')
            ->setCompleteTill(strtotime('tomorrow'))
            ->setEntityType(EntityTypesInterface::LEADS)
            ->setEntityId($lead->getId())
            ->setResponsibleUserId(0);
        try {
            return $this->apiClient->tasks()->addOne($task);
        } catch (AmoCRMApiException $e) {
            dump($e);
        }
    }

    protected function createTestContact()
    {
        $contact = new ContactModel();
        $contact->setName('Example Contact ' . rand(1, 100));

        $this->addCustomFieldValueToEntityByFieldCode("+79061" . rand(100000, 999999), $contact, "PHONE");
        $this->addCustomFieldValueToEntityByFieldCode("example-contact" . rand(1, 100) . "@test.com", $contact, "EMAIL");

        try {
            $contact = $this->apiClient->contacts()->addOne($contact);
        } catch (AmoCRMApiException $e) {
            dump($e);
        }

        return $contact;
    }

    protected function createTestCompany()
    {
        $company = new CompanyModel();
        $company->setName('Example company ' . rand(1, 100));

        $this->addCustomFieldValueToEntityByFieldCode("+79061" . rand(100000, 999999), $company, "PHONE");
        $this->addCustomFieldValueToEntityByFieldCode("example-company" . rand(1, 100) . "@test.com", $company, "EMAIL");

        try {
            return $this->apiClient->companies()->addOne($company);
        } catch (AmoCRMApiException $e) {
            dump($e);
        }
    }

    protected function addCustomFieldValueToEntityByFieldCode($value, $entity, $fieldCode)
    {
        $customFields = $entity->getCustomFieldsValues() ?? new CustomFieldsValuesCollection();

        $field = $customFields->getBy('fieldCode', $fieldCode);

        if (empty($field)) {
            $field = (new MultitextCustomFieldValuesModel())->setFieldCode($fieldCode);
            $customFields->add($field);
        }

        $field->setValues(
            (new MultitextCustomFieldValueCollection())
                ->add(
                    (new MultitextCustomFieldValueModel())
                        ->setEnum('WORK')
                        ->setValue($value)
                )
        );

        $entity->setCustomFieldsValues($customFields);
    }
}
