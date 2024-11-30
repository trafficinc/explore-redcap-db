<?php

require_once('libs/Database.php');

class KnowRedCap {
    private $db;
    private string $cachfilePath;

    public array $tables = ['redcap_actions','redcap_alerts','redcap_alerts_recurrence','redcap_alerts_sent','redcap_alerts_sent_log','redcap_auth','redcap_auth_history','redcap_auth_questions','redcap_cache','redcap_cde_cache','redcap_cde_field_mapping','redcap_config','redcap_crons','redcap_crons_datediff','redcap_crons_history','redcap_custom_queries','redcap_custom_queries_folders','redcap_custom_queries_folders_items','redcap_dashboard_ip_location_cache','redcap_data','redcap_data2','redcap_data3','redcap_data4','redcap_data5','redcap_data6','redcap_data_access_groups','redcap_data_access_groups_users','redcap_data_dictionaries','redcap_data_import','redcap_data_import_rows','redcap_data_quality_resolutions','redcap_data_quality_rules','redcap_data_quality_status','redcap_ddp_log_view','redcap_ddp_log_view_data','redcap_ddp_mapping','redcap_ddp_preview_fields','redcap_ddp_records','redcap_ddp_records_data','redcap_descriptive_popups','redcap_docs','redcap_docs_attachments','redcap_docs_folders','redcap_docs_folders_files','redcap_docs_share','redcap_docs_to_edocs','redcap_econsent','redcap_econsent_forms','redcap_edocs_metadata','redcap_ehr_access_tokens','redcap_ehr_datamart_revisions','redcap_ehr_fhir_logs','redcap_ehr_import_counts','redcap_ehr_settings','redcap_ehr_user_map','redcap_ehr_user_projects','redcap_error_log','redcap_esignatures','redcap_events_arms','redcap_events_calendar','redcap_events_calendar_feed','redcap_events_forms','redcap_events_metadata','redcap_events_repeat','redcap_external_links','redcap_external_links_dags','redcap_external_links_exclude_projects','redcap_external_links_users','redcap_external_module_settings','redcap_external_modules','redcap_external_modules_downloads','redcap_external_modules_log','redcap_external_modules_log_parameters','redcap_folders','redcap_folders_projects','redcap_form_display_logic_conditions','redcap_form_display_logic_targets','redcap_history_size','redcap_history_version','redcap_instrument_zip','redcap_instrument_zip_authors','redcap_instrument_zip_origins','redcap_ip_banned','redcap_ip_cache','redcap_library_map','redcap_locking_data','redcap_locking_labels','redcap_locking_records','redcap_locking_records_pdf_archive','redcap_log_event','redcap_log_event10','redcap_log_event11','redcap_log_event12','redcap_log_event2','redcap_log_event3','redcap_log_event4','redcap_log_event5','redcap_log_event6','redcap_log_event7','redcap_log_event8','redcap_log_event9','redcap_log_view','redcap_log_view_old','redcap_log_view_requests','redcap_messages','redcap_messages_recipients','redcap_messages_status','redcap_messages_threads','redcap_metadata','redcap_metadata_archive','redcap_metadata_prod_revisions','redcap_metadata_temp','redcap_mobile_app_devices','redcap_mobile_app_files','redcap_mobile_app_log','redcap_multilanguage_config','redcap_multilanguage_config_temp','redcap_multilanguage_metadata','redcap_multilanguage_metadata_temp','redcap_multilanguage_snapshots','redcap_multilanguage_ui','redcap_multilanguage_ui_temp','redcap_mycap_aboutpages','redcap_mycap_contacts','redcap_mycap_links','redcap_mycap_messages','redcap_mycap_participants','redcap_mycap_projectfiles','redcap_mycap_projects','redcap_mycap_syncissuefiles','redcap_mycap_syncissues','redcap_mycap_tasks','redcap_mycap_tasks_schedules','redcap_mycap_themes','redcap_new_record_cache','redcap_outgoing_email_counts','redcap_outgoing_email_sms_identifiers','redcap_outgoing_email_sms_log','redcap_page_hits','redcap_pdf_image_cache','redcap_pdf_snapshots','redcap_pdf_snapshots_triggered','redcap_project_checklist','redcap_project_dashboards','redcap_project_dashboards_access_dags','redcap_project_dashboards_access_roles','redcap_project_dashboards_access_users','redcap_project_dashboards_folders','redcap_project_dashboards_folders_items','redcap_projects','redcap_projects_external','redcap_projects_templates','redcap_projects_user_hidden','redcap_pub_articles','redcap_pub_authors','redcap_pub_matches','redcap_pub_mesh_terms','redcap_pub_sources','redcap_queue','redcap_randomization','redcap_randomization_allocation','redcap_record_counts','redcap_record_dashboards','redcap_record_list','redcap_reports','redcap_reports_access_dags','redcap_reports_access_roles','redcap_reports_access_users','redcap_reports_edit_access_dags','redcap_reports_edit_access_roles','redcap_reports_edit_access_users','redcap_reports_fields','redcap_reports_filter_dags','redcap_reports_filter_events','redcap_reports_folders','redcap_reports_folders_items','redcap_sendit_docs','redcap_sendit_recipients','redcap_sessions','redcap_surveys','redcap_surveys_emails','redcap_surveys_emails_recipients','redcap_surveys_emails_send_rate','redcap_surveys_erase_twilio_log','redcap_surveys_login','redcap_surveys_participants','redcap_surveys_pdf_archive','redcap_surveys_phone_codes','redcap_surveys_queue','redcap_surveys_queue_hashes','redcap_surveys_response','redcap_surveys_scheduler','redcap_surveys_scheduler_queue','redcap_surveys_scheduler_recurrence','redcap_surveys_short_codes','redcap_surveys_themes','redcap_todo_list','redcap_twilio_error_log','redcap_two_factor_response','redcap_user_allowlist','redcap_user_information','redcap_user_rights','redcap_user_roles','redcap_validation_types','redcap_web_service_cache'];


    public function __construct($db) {
        $this->db = $db;
        $this->cachfilePath = __DIR__.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
    }

    public function beforeSnapshot() {
        $tableMatrix = $this->getRecordsCount();

        $this->createJsonFile('old_snapshot.json', $tableMatrix);
    }

    public function afterSnapshot() {
        $tableMatrix = $this->getRecordsCount();
        $this->createJsonFile('new_snapshot.json', $tableMatrix);
    }

    public function compare() {
        // Load JSON files into PHP arrays
        $file1 = file_get_contents($this->cachfilePath.'old_snapshot.json');
        $file2 = file_get_contents($this->cachfilePath.'new_snapshot.json');

        $json1 = json_decode($file1, true);
        $json2 = json_decode($file2, true);

        // Check if both files are valid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            die("Error decoding JSON files.");
        }

        // Compare the two JSON arrays
        $differences = $this->compareArrays($json1, $json2);

        // Output the differences
        if (empty($differences['differences'])) {
            echo "\e[32mThere are no changes.\e[0m\n";
        } else {
            echo "Differences found:\n";
            foreach ($differences['differences'] as $difference) {
                echo $difference . "\n";
            }
            echo "Deletes found:\n";
            foreach ($differences['deletes'] as $delete) {
                echo $delete . "\n";
            }
            echo "Inserts found:\n";
            foreach ($differences['inserts'] as $inserts) {
                echo $inserts . "\n";
            }
        }
    }


    private function compareArrays($array1, $array2, $path = '') {
        $differences = [];
        $deletes = [];
        $inserts = [];
    
        // Compare arrays' keys
        foreach ($array1 as $key => $value) {
            $currentPath = $path ? "$path.$key" : $key;
    
            // If the key exists in array2, compare values
            if (array_key_exists($key, $array2)) {
                // If both values are arrays, recurse into them
                if (is_array($value) && is_array($array2[$key])) {
                    $result = compareArrays($value, $array2[$key], $currentPath);
                    if (!empty($result)) {
                        $differences = array_merge($differences, $result);
                    }
                } else {
                    // If values are different, store the difference
                    if ($value !== $array2[$key]) {
                        $differences[] = "  Difference at table '$currentPath' : $value vs {$array2[$key]}";
                        if ($array2[$key] < $value) {
                            // delete
                            $deletes[] = "  table '$currentPath' [DELETE] : OLD: $value vs. NEW: {$array2[$key]}";
                        }
                        if ($array2[$key] > $value) {
                            // insert
                            $inserts[] = "  table '$currentPath' [INSERT] : OLD: $value vs. NEW: {$array2[$key]}";
                        }
                    }
                }
            } else {
                // If the key doesn't exist in array2
                $differences[] = "Key '$currentPath' is missing in the second JSON file.";
            }
        }
    
        // Check for any extra keys in array2 that are not in array1
        foreach ($array2 as $key => $value) {
            $currentPath = $path ? "$path.$key" : $key;
            if (!array_key_exists($key, $array1)) {
                $differences[] = "Key '$currentPath' is extra in the second JSON file.";
            }
        }
    
        return ['differences' => $differences,'deletes' => $deletes, 'inserts' => $inserts];
    }

    public function getRecordsCount() {
        $coll = [];
        foreach($this->tables as $table) {
            $this->db->query("SELECT * from $table");
            $coll[$table] = $this->db->rowCount();
        }
        return $coll;
    }

    public function createJsonFile($fileName, $data) {
        $file = $this->cachfilePath.$fileName;
		if (file_exists($file)) {
			$this->deleteFile($file);
		}
        $data = $this->formatArrayToJson($data);
		file_put_contents($file, $data);
    }

    public function deleteFile($fileName) {
		unlink($fileName);
	}

    private function formatArrayToJson($data) {
        $coll = [];
        foreach($data as $key => $value) {
            $coll[] = "\"$key\":$value";
        }
        return "{".implode(",",$coll)."}";
    }

}

function helpMenu() {
    echo "\e[32mThis is a small tool to get to know which tables in Redcap are affected by actions via DB inserts and deletes.\n" . 
                "Before you make a change (ex. fill out and submit a form) in Redcap run: $ php knowRedCap.php before\n" . 
                "After you make a change in Redcap run: $ php knowRedCap.php after\n" . 
                "To see what was changed in the DB tables, run: $ php knowRedCap.php compare \e[0m \n";
}


$db = new Database;
$rc = new KnowRedCap($db);

if ($argc > 1) {
    for ($i = 1; $i < $argc; $i++) {
        switch($argv[$i]) {
            case "before":
                $rc->beforeSnapshot();
            break;
            case "after":  
                $rc->afterSnapshot();
            break;
            case "compare":
                $rc->compare();
            break;
            case "help":
                helpMenu();
            break;
            case "h":
                helpMenu();
            break;
        }
    }
} else {
    echo "No arguments passed.\n";
}




