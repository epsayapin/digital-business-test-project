<?php

namespace App\Http\Controllers;

use App\Facades\AmoCRMService;
use Illuminate\Http\Request;

class AmocrmController extends Controller
{
    public function index()
    {
        return view("amocrm.index", ["button" => AmoCRMService::getIntegrationButton()]);
    }

    public function redirectUri()
    {
        AmoCRMService::createAccessTokenByRedirectUriRequest();

        return redirect(route("amocrm.get-leads"));
    }

    public function getLeads()
    {
        return view("amocrm.leads", [
            "leads" => AmoCRMService::getLeads(),
            "users" => AmoCRMService::getUsers(),
            "contacts" => AmoCRMService::getContacts(),
            "companies" => AmoCRMService::getCompanies()
        ]);
    }

    public function sendTestLead()
    {
        AmoCRMService::sendTestLead();

        return redirect(route("amocrm.get-leads"));
    }
}
