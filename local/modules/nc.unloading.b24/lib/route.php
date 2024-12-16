<?php

namespace NC\UnloadingB24;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\ParameterDictionary;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Loader;

class Route
{
	protected string $module_id;

	protected string $incoming_hooks_token;
	protected string $outcoming_requests_token;
	protected string $portal_domain;
	protected string $requests_user_id;

	public function __construct($agent = false)
	{
		$this->includeModules();
		$this->module_id = pathinfo(dirname(__DIR__))['basename'];

		//$this->incoming_hooks_token = Option::get($this->module_id, "incoming_hooks_token");
		//$this->outcoming_requests_token = Option::get($this->module_id, "outcoming_requests_token");
		$this->portal_domain = Option::get($this->module_id, "portal_domain");
		//$this->requests_user_id = Option::get($this->module_id, "requests_user_id");

		if (Option::get($this->module_id, "module_on") != "Y") {
			return;
		}

		if (!$agent) {
            $this->transferContacts();
            $this->transferCompanies();
            $this->transferLeads();
            $this->transferDeals();
		}
	}

    public function transferLeads()
    {
        $leads = $this->sendRequest($this->portal_domain, '/crm.lead.list', 'meq70aww95tlhjt2', '1');
        //\Bitrix\Main\Diag\Debug::dumpToFile($leads, "$leads","/log.txt");
        foreach ($leads['result'] as $key => $lead) {
            //\Bitrix\Main\Diag\Debug::dumpToFile($lead, "lead", "/log.txt");
            $lead2 = new \CCrmLead;
            $arFields = [
                "TITLE" => $lead['TITLE'],
                "COMPANY_TITLE" => $lead['COMPANY_TITLE'],
                "NAME" => $lead['NAME'],
                "LAST_NAME" => $lead['LAST_NAME'],
                "SECOND_NAME" => $lead['SECOND_NAME'],
                "POST" => $lead['POST'],
                "ADDRESS" => $lead['ADDRESS'],
                "STATUS_ID" => $lead['STATUS_ID'],
                "OPENED" => $lead['OPENED'],
                "ASSIGNED_BY_ID" => '1',
                "CURRENCY_ID" => $lead['CURRENCY_ID'],
                "OPPORTUNITY" => $lead['OPPORTUNITY']
            ];
            $leadId = $lead2->Add($arFields);
        }
    }

    public function transferDeals()
    {
        $deals = $this->sendRequest($this->portal_domain, '/crm.deal.list', 'meq70aww95tlhjt2', '1');
        foreach ($deals['result'] as $key => $deal) {
            //\Bitrix\Main\Diag\Debug::dumpToFile($deal['TITLE'], "deal", "/log.txt");
            $arFields =[
                "TITLE" => $deal['TITLE'],
                "TYPE_ID" => $deal['TYPE_ID'],
                "STAGE_ID" => $deal['STAGE_ID'],
                "COMPANY_ID" => $deal['COMPANY_ID'],
                "OPENED" => $deal['OPENED'],
                "ASSIGNED_BY_ID" => $deal['ASSIGNED_BY_ID'],
                "CREATED_BY_ID" => $deal['CREATED_BY_ID']
            ];

            $options = ['CURRENT_USER' => 1]; //из под админа
            $deal2 = new \CCrmDeal(false);
            $dealId = $deal2->Add($arFields, true, $options);
        }
    }

    public function transferContacts()
    {
        $contacts = $this->sendRequest($this->portal_domain, '/crm.contact.list', 'meq70aww95tlhjt2', '1');
        foreach ($contacts['result'] as $key => $contact) {
            $arFields = [
                "NAME" => $contact['NAME'],
                "LAST_NAME" => $contact['LAST_NAME'],
                "SECOND_NAME" => $contact['SECOND_NAME'],
                "HONORIFIC" => $contact['HONORIFIC'],
                "POST" => $contact['POST'],
                "SOURCE_DESCRIPTION" => $contact['SOURCE_DESCRIPTION'],
                "SOURCE_ID" => $contact['SOURCE_ID'],
                "TYPE_ID" => $contact['TYPE_ID'],
                "ASSIGNED_BY_ID" => "1"
            ];
            $contact2 = new \CCrmContact(false);
            $contact2->add($arFields);
        }
    }

    public function transferCompanies()
    {
        $companies = $this->sendRequest($this->portal_domain, '/crm.company.list', 'meq70aww95tlhjt2', '1');
        foreach ($companies['result'] as $key => $company) {
            $arFields = [
                "TITLE" => $company['TITLE'],
                "OPENED" => $company['OPENED'],
                "ADDRESS" => $company['ADDRESS'],
                "COMPANY_TYPE" => $company['COMPANY_TYPE'],
                "ASSIGNED_BY_ID" => "1"
            ];
            $company2 = new \CCrmCompany();
            $company2->Add($arFields);
        }
    }

	protected function sendRequest($portal, $methodWithParams, $token = "", $userId = "")
	{
		if (empty($token)) $token = $this->outcoming_requests_token;
		if (empty($userId)) $userId = $this->requests_user_id;
		$curlQuery = curl_init();
		curl_setopt_array($curlQuery, [
			CURLOPT_URL => 'https://' . $portal . '/rest/' . $userId .
				'/' . $token . '/' . $methodWithParams,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true
		]);
		$response = curl_exec($curlQuery);
		curl_close($curlQuery);
		return json_decode($response, true);
	}

	protected function includeModules()
	{
		Loader::includeModule("main");
        Loader::includeModule("crm");
	}
}
