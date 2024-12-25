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

            $this->contactToCompanyRestoreRelationship();
            $this->companyToDealRestoreRelationship();
            $this->contactToDealRestoreRelationship();
		}
	}

    public function contactToDealRestoreRelationship()
    {
        $deals = $this->sendRequest($this->portal_domain, '/crm.deal.list', 'meq70aww95tlhjt2', '1');
        $contacts = $this->sendRequest($this->portal_domain, '/crm.contact.list', 'meq70aww95tlhjt2', '1');
        foreach ($deals['result'] as $key => $deal) {
            if (!empty($deal["CONTACT_ID"])) {
                foreach ($contacts['result'] as $ind => $contact) {
                    if ($deal["CONTACT_ID"] == $contact["ID"]) {
                        $dealResult = \CCrmDeal::GetListEx(
                            [
                                'SOURCE_ID' => 'DESC'
                            ],
                            [
                                'TITLE' => $deal['TITLE'],
                                'TYPE_ID' => $deal['TYPE_ID'],
                                'STAGE_ID' => $deal['STAGE_ID'],
                                'OPPORTUNITY' => $deal['OPPORTUNITY'],
                                'CHECK_PERMISSIONS' => 'N'
                            ],
                            false,
                            false,
                            [
                                'ID'
                            ]
                        );

                        if ($arDeal = $dealResult->fetch()) {
                            //\Bitrix\Main\Diag\Debug::dumpToFile($arDeal["ID"], "arDeal ID", "/log.txt");
                            $contactResult = \CCrmContact::GetListEx(
                                [
                                    'SOURCE_ID' => 'DESC'
                                ],
                                [
                                    'NAME' => $contact['NAME'],
                                    'SECOND_NAME' => $contact['SECOND_NAME'],
                                    'LAST_NAME' => $contact['LAST_NAME'],
                                    'POST' => $contact['POST'],
                                    'CHECK_PERMISSIONS' => 'N'
                                ],
                                false,
                                false,
                                [
                                    'ID'
                                ]
                            );

                            if ($arContact = $contactResult->fetch()) {
                                //\Bitrix\Main\Diag\Debug::dumpToFile($arContact["ID"], "arContact ID", "/log.txt");
                                $bCheckRight = false;
                                $dealId = $arDeal['ID'];
                                $dealFields = [
                                    'CONTACT_ID'   => $arContact["ID"],
                                ];
                                $entityObject = new \CCrmDeal($bCheckRight);
                                $isUpdateSuccess = $entityObject->Update(
                                    $dealId,
                                    $dealFields,
                                    $bCompare = true,
                                    $bUpdateSearch = true,
                                    $arOptions = [
                                        'CURRENT_USER' => \CCrmSecurityHelper::GetCurrentUserID(),
                                        'IS_SYSTEM_ACTION' => false,
                                        'REGISTER_SONET_EVENT' => false,
                                        'ENABLE_SYSTEM_EVENTS' => true,
                                        'SYNCHRONIZE_STAGE_SEMANTICS' => true,
                                        'DISABLE_USER_FIELD_CHECK' => false,
                                        'DISABLE_REQUIRED_USER_FIELD_CHECK' => false,
                                    ]
                                );

                                if (!$isUpdateSuccess) {
                                    /**
                                     * Произошла ошибка при обновлении элемента, посмотреть ее можно
                                     * через любой из способов ниже:
                                     * 1. $entityFields['RESULT_MESSAGE']
                                     * 2. $entityObject->LAST_ERROR
                                     */
                                    //\Bitrix\Main\Diag\Debug::dumpToFile($entityFields['RESULT_MESSAGE'], "RESULT_MESSAGE", "/log.txt");
                                }
                            }
                        }
                        break;
                    }
                }
            }
        }
    }
    public function companyToDealRestoreRelationship()
    {
        $deals = $this->sendRequest($this->portal_domain, '/crm.deal.list', 'meq70aww95tlhjt2', '1');
        $companies = $this->sendRequest($this->portal_domain, '/crm.company.list', 'meq70aww95tlhjt2', '1');
        foreach ($deals['result'] as $key => $deal) {
            if (!empty($deal["COMPANY_ID"])) {
                foreach ($companies['result'] as $ind => $company) {
                    if ($deal["COMPANY_ID"] == $company["ID"]) {
                        $dealResult = \CCrmDeal::GetListEx(
                            [
                                'SOURCE_ID' => 'DESC'
                            ],
                            [
                                'TITLE' => $deal['TITLE'],
                                'TYPE_ID' => $deal['TYPE_ID'],
                                'STAGE_ID' => $deal['STAGE_ID'],
                                'OPPORTUNITY' => $deal['OPPORTUNITY'],
                                'CHECK_PERMISSIONS' => 'N'
                            ],
                            false,
                            false,
                            [
                                'ID'
                            ]
                        );

                        if ($arDeal = $dealResult->fetch()) {
                            //\Bitrix\Main\Diag\Debug::dumpToFile($arDeal["ID"], "arDeal ID","/log.txt");
                            //////////////
                            $companyResult = \CCrmCompany::GetListEx(
                                [
                                    'SOURCE_ID' => 'DESC'
                                ],
                                [
                                    'TITLE' => $company['TITLE'],
                                    'CHECK_PERMISSIONS' => 'N'
                                ],
                                false,
                                false,
                                [
                                    'ID'
                                ]
                            );

                            if ($arCompany = $companyResult->fetch()) {
                                // $arCompany["ID"]
                                //\Bitrix\Main\Diag\Debug::dumpToFile($arCompany["ID"], "arCompany ID","/log.txt");
                                $bCheckRight = false;
                                $dealId = $arDeal['ID'];
                                $dealFields = [
                                    'COMPANY_ID'   => $arCompany["ID"],
                                ];
                                $entityObject = new \CCrmDeal( $bCheckRight );
                                $isUpdateSuccess = $entityObject->Update(
                                    $dealId,
                                    $dealFields,
                                    $bCompare = true,
                                    $bUpdateSearch = true,
                                    $arOptions = [
                                        'CURRENT_USER' => \CCrmSecurityHelper::GetCurrentUserID(),
                                        'IS_SYSTEM_ACTION' => false,
                                        'REGISTER_SONET_EVENT' => false,
                                        'ENABLE_SYSTEM_EVENTS' => true,
                                        'SYNCHRONIZE_STAGE_SEMANTICS' => true,
                                        'DISABLE_USER_FIELD_CHECK' => false,
                                        'DISABLE_REQUIRED_USER_FIELD_CHECK' => false,
                                    ]
                                );

                                if (!$isUpdateSuccess) {
                                    /**
                                     * Произошла ошибка при обновлении элемента, посмотреть ее можно
                                     * через любой из способов ниже:
                                     * 1. $entityFields['RESULT_MESSAGE']
                                     * 2. $entityObject->LAST_ERROR
                                     */
                                    //\Bitrix\Main\Diag\Debug::dumpToFile($entityFields['RESULT_MESSAGE'], "RESULT_MESSAGE", "/log.txt");
                                }
                            }
                        }
                        break;
                    }
                }
            }
        }
    }
    public function contactToCompanyRestoreRelationship()
    {
        $contacts = $this->sendRequest($this->portal_domain, '/crm.contact.list', 'meq70aww95tlhjt2', '1');
        $companies = $this->sendRequest($this->portal_domain, '/crm.company.list', 'meq70aww95tlhjt2', '1');
        foreach ($contacts['result'] as $key => $contact) {
            if (!empty($contact["COMPANY_ID"])) {
                foreach ($companies['result'] as $ind => $company) {
                    if ($contact["COMPANY_ID"] == $company["ID"]) {
                        // получаем айди контакта в срм2 по уникальным полям
                        //Bitrix\Main\Diag\Debug::dumpToFile($contact["COMPANY_ID"], "COMPANY_ID","/log.txt");
                        $contactResult = \CCrmContact::GetListEx(
                            [
                                'SOURCE_ID' => 'DESC'
                            ],
                            [
                                'NAME' => $contact['NAME'],
                                'SECOND_NAME' => $contact['SECOND_NAME'],
                                'LAST_NAME' => $contact['LAST_NAME'],
                                'POST' => $contact['POST'],
                                'CHECK_PERMISSIONS' => 'N'
                            ],
                            false,
                            false,
                            [
                                'ID'
                            ]
                        );

                        if ($arContact = $contactResult->fetch()) {
                            // $arContact["ID"]
                            //\Bitrix\Main\Diag\Debug::dumpToFile($arContact["ID"], "arContact ID","/log.txt");
                            $companyResult = \CCrmCompany::GetListEx(
                                [
                                    'SOURCE_ID' => 'DESC'
                                ],
                                [
                                    'TITLE' => $company['TITLE'],
                                    'CHECK_PERMISSIONS' => 'N'
                                ],
                                false,
                                false,
                                [
                                    'ID'
                                ]
                            );

                            if ($arCompany = $companyResult->fetch()) {
                                // $arCompany["ID"]
                                //\Bitrix\Main\Diag\Debug::dumpToFile($arCompany["ID"], "arCompany ID","/log.txt");
                                $bCheckRight = false;
                                $contactId = $arContact["ID"];
                                $contactFields = [
                                    "COMPANY_ID" => $arCompany["ID"],
                                ];
                                $contactEntity = new \CCrmContact($bCheckRight);
                                $isUpdateSuccess = $contactEntity->Update(
                                    $contactId,
                                    $contactFields,
                                    $bCompare = true,
                                    $arOptions = [
                                        'CURRENT_USER' => \CCrmSecurityHelper::GetCurrentUserID(),
                                        'IS_SYSTEM_ACTION' => false,
                                        'ENABLE_DUP_INDEX_INVALIDATION' => true,
                                        'REGISTER_SONET_EVENT' => false,
                                        'DISABLE_USER_FIELD_CHECK' => false,
                                        'DISABLE_REQUIRED_USER_FIELD_CHECK' => false,
                                    ]
                                );
//                                if (!$isUpdateSuccess) {
//                                    /**
//                                     * Произошла ошибка при обновлении контакта, посмотреть ее можно
//                                     * через любой из способов ниже:
//                                     * 1. $contactFields['RESULT_MESSAGE']
//                                     * 2. $contactEntity->LAST_ERROR
//                                     */
//                                    \Bitrix\Main\Diag\Debug::dumpToFile($contactEntity->LAST_ERROR, "contactEntity->LAST_ERROR","/log.txt");
//                                    \Bitrix\Main\Diag\Debug::dumpToFile($contactFields['RESULT_MESSAGE'], "RESULT_MESSAGE","/log.txt");
//                                }
                            }
                        }
                        break;
                    }
                }
            }
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
                "HONORIFIC" => $lead['HONORIFIC'],
                "COMPANY_TITLE" => $lead['COMPANY_TITLE'],
                "NAME" => $lead['NAME'],
                "LAST_NAME" => $lead['LAST_NAME'],
                "SECOND_NAME" => $lead['SECOND_NAME'],
                "SOURCE_ID" => $lead['SOURCE_ID'],
                "SOURCE_DESCRIPTION" => $lead['SOURCE_DESCRIPTION'],
                "POST" => $lead['POST'],
                "ADDRESS" => $lead['ADDRESS'],
                "STATUS_ID" => $lead['STATUS_ID'],
                "STATUS_DESCRIPTION" => $lead['STATUS_DESCRIPTION'],
                "COMMENTS" => $lead['COMMENTS'],
                "OPENED" => $lead['OPENED'],
                "ASSIGNED_BY_ID" => '1',
                "CURRENCY_ID" => $lead['CURRENCY_ID'],
                "OPPORTUNITY" => $lead['OPPORTUNITY'],
                "IS_RETURN_CUSTOMER" => $lead['IS_RETURN_CUSTOMER'],
                "BIRTHDATE" => $lead['BIRTHDATE'],
                "IS_MANUAL_OPPORTUNITY" => $lead['IS_MANUAL_OPPORTUNITY'],
                "HAS_PHONE" => $lead['HAS_PHONE'],
                "HAS_EMAIL" => $lead['HAS_EMAIL'],
                "HAS_IMOL" => $lead['HAS_IMOL'],
                "ASSIGNED_BY_ID" => "1",
                "CREATED_BY_ID" => "1",
                "MODIFY_BY_ID" => "1",
                "DATE_CREATE" => $lead['DATE_CREATE'],
                "DATE_MODIFY" => $lead['DATE_MODIFY'],
                "DATE_CLOSED" => $lead['DATE_CLOSED'],
                "STATUS_SEMANTIC_ID" => $lead['STATUS_SEMANTIC_ID'],
                "MOVED_BY_ID" => $lead['MOVED_BY_ID'],
                "MOVED_TIME" => $lead['MOVED_TIME'],
                "ADDRESS" => $lead['ADDRESS'],
                "ADDRESS_2" => $lead['ADDRESS_2'],
                "ADDRESS_CITY" => $lead['ADDRESS_CITY'],
                "ADDRESS_POSTAL_CODE" => $lead['ADDRESS_POSTAL_CODE'],
                "ADDRESS_REGION" => $lead['ADDRESS_REGION'],
                "ADDRESS_PROVINCE" => $lead['ADDRESS_PROVINCE'],
                "ADDRESS_COUNTRY" => $lead['ADDRESS_COUNTRY'],
                "ADDRESS_COUNTRY_CODE" => $lead['ADDRESS_COUNTRY_CODE'],
                "ADDRESS_LOC_ADDR_ID" => $lead['ADDRESS_LOC_ADDR_ID'],
                "UTM_SOURCE" => $lead['UTM_SOURCE'],
                "UTM_MEDIUM" => $lead['UTM_MEDIUM'],
                "UTM_CAMPAIGN" => $lead['UTM_CAMPAIGN'],
                "UTM_CONTENT" => $lead['UTM_CONTENT'],
                "UTM_TERM" => $lead['UTM_TERM'],
                "UTM_TERM" => $lead['UTM_TERM'],
                "LAST_ACTIVITY_BY" => $lead['LAST_ACTIVITY_BY'],
                "LAST_ACTIVITY_TIME" => $lead['LAST_ACTIVITY_TIME'],
            ];
            $leadId = $lead2->Add($arFields);
        }
    }

    public function transferDeals()
    {
        $deals = $this->sendRequest($this->portal_domain, '/crm.deal.list', 'meq70aww95tlhjt2', '1');
        foreach ($deals['result'] as $key => $deal) {
            //\Bitrix\Main\Diag\Debug::dumpToFile($deal['TITLE'], "deal", "/log.txt");
            $arFields = [
                "TITLE" => $deal['TITLE'],
                "TYPE_ID" => $deal['TYPE_ID'],
                "STAGE_ID" => $deal['STAGE_ID'],
                //"COMPANY_ID" => $deal['COMPANY_ID'],
                "OPENED" => $deal['OPENED'],
                "PROBABILITY" => $deal['PROBABILITY'],
                "CURRENCY_ID" => $deal['CURRENCY_ID'],
                "OPPORTUNITY" => $deal['OPPORTUNITY'],
                "IS_MANUAL_OPPORTUNITY" => $deal['IS_MANUAL_OPPORTUNITY'],
                "TAX_VALUE" => $deal['TAX_VALUE'],
                "ASSIGNED_BY_ID" => "1",
                "CREATED_BY_ID" => "1",
                "DATE_CREATE" => $deal['DATE_CREATE'],
                "DATE_MODIFY" => $deal['DATE_MODIFY'],
                //"BEGINDATE" => $deal['BEGINDATE'],
                //"CLOSEDATE" => $deal['CLOSEDATE'],
                "CLOSED" => $deal['CLOSED'],
                "COMMENTS" => $deal['COMMENTS'],
                "ADDITIONAL_INFO" => $deal['ADDITIONAL_INFO'],
                "CATEGORY_ID" => $deal['CATEGORY_ID'],
                "STAGE_SEMANTIC_ID" => $deal['STAGE_SEMANTIC_ID'],
                "IS_NEW" => $deal['IS_NEW'],
                "IS_RECURRING" => $deal['IS_RECURRING'],
                "IS_RETURN_CUSTOMER" => $deal['IS_RETURN_CUSTOMER'],
                "IS_REPEATED_APPROACH" => $deal['IS_REPEATED_APPROACH'],
                "MOVED_BY_ID" => $deal['MOVED_BY_ID'],
                "MOVED_TIME" => $deal['MOVED_TIME'],
                "LAST_ACTIVITY_TIME" => $deal['LAST_ACTIVITY_TIME'],
                "UTM_SOURCE" => $deal['UTM_SOURCE'],
                "UTM_MEDIUM" => $deal['UTM_MEDIUM'],
                "UTM_CAMPAIGN" => $deal['UTM_CAMPAIGN'],
                "UTM_CONTENT" => $deal['UTM_CONTENT'],
                "UTM_TERM" => $deal['UTM_TERM'],
            ];

            $options = ['CURRENT_USER' => 1]; //из под админа
            $deal2 = new \CCrmDeal(false);
            $dealId = $deal2->Add($arFields, true, $options);
            if (!$dealId)
            {
                /**
                 * Произошла ошибка при добавлении сущности, посмотреть ее можно
                 * через любой из способов ниже:
                 * 1. $entityFields['RESULT_MESSAGE']
                 * 2. $entityObject->LAST_ERROR
                 */
                //\Bitrix\Main\Diag\Debug::dumpToFile($arFields['RESULT_MESSAGE'], "RESULT_MESSAGE", "/log.txt");
                //\Bitrix\Main\Diag\Debug::dumpToFile($deal2->LAST_ERROR, "LAST_ERROR", "/log.txt");
            }
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
                "COMMENTS" => $contact['COMMENTS'],
                "HONORIFIC" => $contact['HONORIFIC'],
                "POST" => $contact['POST'],
                "SOURCE_ID" => $contact['SOURCE_ID'],
                "SOURCE_DESCRIPTION" => $contact['SOURCE_DESCRIPTION'],
                "TYPE_ID" => $contact['TYPE_ID'],
                "ASSIGNED_BY_ID" => "1",
                "BIRTHDATE" => $contact['BIRTHDATE'],
                "EXPORT" => $contact['EXPORT'],
                "HAS_PHONE" => $contact['HAS_PHONE'],
                "HAS_EMAIL" => $contact['HAS_EMAIL'],
                "HAS_IMOL" => $contact['HAS_IMOL'],
                "CREATED_BY_ID" => "1",
                "MODIFY_BY_ID" => "1",
                "OPENED" => $contact['OPENED'],
                "LAST_ACTIVITY_TIME" => $contact['LAST_ACTIVITY_TIME'],
                "ADDRESS" => $contact['ADDRESS'],
                "ADDRESS_2" => $contact['ADDRESS_2'],
                "ADDRESS_CITY" => $contact['ADDRESS_CITY'],
                "ADDRESS_POSTAL_CODE" => $contact['ADDRESS_POSTAL_CODE'],
                "ADDRESS_REGION" => $contact['ADDRESS_REGION'],
                "ADDRESS_PROVINCE" => $contact['ADDRESS_PROVINCE'],
                "ADDRESS_COUNTRY" => $contact['ADDRESS_COUNTRY'],
                "UTM_SOURCE" => $contact['UTM_SOURCE'],
                "UTM_MEDIUM" => $contact['UTM_MEDIUM'],
                "UTM_CAMPAIGN" => $contact['UTM_CAMPAIGN'],
                "UTM_CONTENT" => $contact['UTM_CONTENT'],
                "UTM_TERM" => $contact['UTM_TERM'],
                "LAST_ACTIVITY_BY" => $contact['LAST_ACTIVITY_BY'],
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
                "ASSIGNED_BY_ID" => "1",
                "HAS_PHONE" => $company['HAS_PHONE'],
                "HAS_EMAIL" => $company['HAS_EMAIL'],
                "HAS_IMOL" => $company['HAS_IMOL'],
                "CREATED_BY_ID" => "1",
                "MODIFY_BY_ID" => "1",
                "BANKING_DETAILS" => $company['BANKING_DETAILS'],
                "INDUSTRY" => $company['INDUSTRY'],
                "REVENUE" => $company['REVENUE'],
                "CURRENCY_ID" => $company['CURRENCY_ID'],
                "EMPLOYEES" => $company['EMPLOYEES'],
                "COMMENTS" => $company['COMMENTS'],
                "IS_MY_COMPANY" => $company['IS_MY_COMPANY'],
                "LAST_ACTIVITY_TIME" => $company['LAST_ACTIVITY_TIME'],
                "ADDRESS" => $company['ADDRESS'],
                "ADDRESS_2" => $company['ADDRESS_2'],
                "ADDRESS_CITY" => $company['ADDRESS_CITY'],
                "ADDRESS_POSTAL_CODE" => $company['ADDRESS_POSTAL_CODE'],
                "ADDRESS_REGION" => $company['ADDRESS_REGION'],
                "ADDRESS_PROVINCE" => $company['ADDRESS_PROVINCE'],
                "ADDRESS_COUNTRY" => $company['ADDRESS_COUNTRY'],
                "ADDRESS_COUNTRY_CODE" => $company['ADDRESS_COUNTRY_CODE'],
                "ADDRESS_LOC_ADDR_ID" => $company['ADDRESS_LOC_ADDR_ID'],
                "ADDRESS_LEGAL" => $company['ADDRESS_LEGAL'],
                "REG_ADDRESS" => $company['REG_ADDRESS'],
                "REG_ADDRESS_2" => $company['REG_ADDRESS_2'],
                "REG_ADDRESS_CITY" => $company['REG_ADDRESS_CITY'],
                "REG_ADDRESS_POSTAL_CODE" => $company['REG_ADDRESS_POSTAL_CODE'],
                "REG_ADDRESS_REGION" => $company['REG_ADDRESS_REGION'],
                "REG_ADDRESS_PROVINCE" => $company['REG_ADDRESS_PROVINCE'],
                "REG_ADDRESS_COUNTRY" => $company['REG_ADDRESS_COUNTRY'],
                "REG_ADDRESS_COUNTRY_CODE" => $company['REG_ADDRESS_COUNTRY_CODE'],
                "REG_ADDRESS_LOC_ADDR_ID" => $company['REG_ADDRESS_LOC_ADDR_ID'],
                "UTM_SOURCE" => $company['UTM_SOURCE'],
                "UTM_MEDIUM" => $company['UTM_MEDIUM'],
                "UTM_CAMPAIGN" => $company['UTM_CAMPAIGN'],
                "UTM_CONTENT" => $company['UTM_CONTENT'],
                "UTM_TERM" => $company['UTM_TERM'],
                "LAST_ACTIVITY_BY" => $company['LAST_ACTIVITY_BY'],
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
