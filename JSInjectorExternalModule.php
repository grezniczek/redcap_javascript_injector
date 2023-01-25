<?php

namespace DE\RUB\JSInjectorExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

/**
 * ExternalModule class for Javascript Injector.
 */
class JSInjectorExternalModule extends AbstractExternalModule {

    // Perform settings upgrade to v2 model
    function redcap_module_system_change_version($version, $old_version) {
        if (explode(".", $old_version)[0] * 1 < 2) {
            $this->convert_v1_settings();
        }
    }

    // Insert JSMO name into config dialog
    function redcap_module_configuration_settings($project_id, $settings) {
        $key = $project_id == null ? "sys-jsmo" : "proj-jsmo";
        $jsmo = $this->getJavascriptModuleObjectName();
        foreach ($settings as &$setting) {
            if ($setting["key"] === $key) {
                $setting["name"] = str_replace("#JSMO#", $jsmo, $setting["name"]);
            }
        }
        return $settings;
    }

    // Set visibility of Configure button in projects
    function redcap_module_configure_button_display() {
        if ($this->getSystemSetting("su_only") && !SUPER_USER) return null;
        return true;
    }

    function redcap_project_home_page ($project_id) {
        $this->injectJS("php", null);
    }
    
    // Determine context and inject
    function redcap_every_page_top ($project_id) {
        $page = defined("PAGE") ? PAGE : "";
        $instrument = null;
        $context = [
            "cc" => false,
            "todo" => false,
            "langs" => false,
            "browseprojects" => false,
            "browseusers" => false,
            "edituser" => false,
            "emailusers" => false,
            "login" => false,
            "php" => false,
            "rsd" => false,
            "aer" => false,
            "rhp" => false,
            "data_entry" => false,
            "survey" => false,
            "report" => false,
            "db" => false,
            "dbp" => false,
        ];

        // System context
        if ($project_id == null) {
            if (!defined("USERID")) {
                $context["login"] = true;
            }
            else {
                if ($page === "ToDoList/index.php") {
                    $context["todo"] = true;
                    $context["cc"] = true;
                }
                else if ($page === "LanguageUpdater/index.php") {
                    $context["langs"] = true;
                    $context["cc"] = true;
                }
                else if ($page === "ControlCenter/view_projects.php") {
                    $context["browseprojects"] = true;
                    $context["cc"] = true;
                }
                else if ($page === "ControlCenter/view_users.php") {
                    $context["browseusers"] = true;
                    $context["cc"] = true;
                }
                else if ($page === "ControlCenter/create_user.php") {
                    $context["edituser"] = true;
                    $context["cc"] = true;
                }
                else if ($page === "ControlCenter/email_users.php") {
                    $context["emailusers"] = true;
                    $context["cc"] = true;
                }
                else if (starts_with($page, "ControlCenter/")) {
                    $context["cc"] = true;
                }
                else if ($page === "FhirStatsController:index") {
                    $context["cc"] = true;
                }
                else if ($page === "MultiLanguageController:systemConfig") {
                    $context["cc"] = true;
                }
            }
        }
        // Project context
        else {
            $Proj = new \Project();
            if ($page === "surveys/index.php") {
                if (isset($_GET["page"])) {
                    $context["survey"] = true;
                    $instrument = isset($Proj->forms[$_GET["page"]]) ? $_GET["page"] : null;
                }
                else if (isset($_GET["__dashboard"])) {
                    $context["dbp"] = true;
                }
            }
            else if (!defined("USERID")) {
                $context["login"] = true;
            }
            else {
                if ($page === "DataEntry/index.php") {
                    $context["data_entry"] = true;
                    $instrument = isset($Proj->forms[$_GET["page"]]) ? $_GET["page"] : null;
                }
                else if ($page == "index.php") {
                    $context["php"] = true;
                }
                else if ($page == "DataEntry/record_status_dashboard.php") {
                    $context["rsd"] = true;
                }
                else if ($page == "DataEntry/record_home.php") {
                    if (isset($_GET["id"])) {
                        $context["rhp"] = true;
                    }
                    else {
                        $context["aer"] = true;
                    }
                }
                else if ($page == "ProjectDashController:view") {
                    $context["db"] = true;
                }
                else if ($page == "DataExport/index.php" && isset($_GET["report_id"]) && !isset($_GET["addedit"])) {
                    $context["report"] = true;
                }
            }
        }
        // Inject
        $this->injectJS($project_id, $context, $instrument);
    }

    /**
     * Inject JS code based on context.
     *
     * @param string|null $project_id
     * @param array $context
     * @param string $instrument
     */
    function injectJS($project_id, $context, $instrument) {

        return;
        $settings = $this->getFormattedSettings($project_id);

        if (empty($settings["js"])) {
            return;
        }

        foreach ($settings["js"] as $row) {
            if (empty($row["js_enabled"])) continue;
            $inject = strpos($row["js_type"], $type) !== false;
            if (strpos("survey,data_entry", $row["js_type"]) !== false) {
                // Check instrument.
                $inject = $inject && (!array_filter($row["js_instruments"]) || in_array($instrument, $row["js_instruments"], true));
            }
            if ($inject) {
                echo "<script>" . $row["js_code"] . "</script>";
            }
        }
    }


    /**
     * Converts legacy v1 project settings to the new v2 model
     * @return void 
     */
    private function convert_v1_settings() {
        $projects = $this->getProjectsWithModuleEnabled(true);
        // Process each project where the module is enabled
        foreach ($projects as $pid) {
            $old = $this->getProjectSettings($pid);
            if (is_array($old["js"]) && count($old["js"])) {
                // Prepare new settings format
                $new = [
                    "enabled" => true,
                    "reserved-hide-from-non-admins-in-project-list" => $old["reserved-hide-from-non-admins-in-project-list"],
                    "proj-jsmo" => false,
                    // Removes legacy settings
                    "js" => null,
                    "js_enabled" => null,
                    "js_type" => null,
                    "js_instruments" => null,
                    "js_code" => null
                ];
                foreach ($old["js"] as $i => $_) {
                    $new["proj-injections"][$i] = true;
                    $new["proj-enabled"][$i] = $old["js_enabled"][$i];
                    // Set default contexts
                    $new["proj-context_all"][$i] = false;
                    $new["proj-context_php"][$i] = null;
                    $new["proj-context_rsd"][$i] = null;
                    $new["proj-context_aer"][$i] = null;
                    $new["proj-context_rhp"][$i] = null;
                    $new["proj-context_data_entry"][$i] = null;
                    $new["proj-context_survey"][$i] = null;
                    $new["proj-context_report"][$i] = null;
                    $new["proj-context_db"][$i] = null;
                    $new["proj-context_dbp"][$i] = null;
                    // Convert legacy type to contexts
                    switch ($old["js_type"][$i]) {
                        case "all":
                            $new["proj-context_all"][$i] = true;
                            break;
                        case "survey,data_entry":
                            $new["proj-context_data_entry"][$i] = "include";
                            $new["proj-context_survey"][$i] = "include";
                            break;
                        default:
                            $new["proj-context_" . $old["js_type"][$i]][$i] = "include";
                            break;
                    }
                    $new["proj-instruments"][$i] = $old["js_instruments"][$i];
                    $new["proj-code"][$i] = $old["js_code"][$i];
                }
                // Store converted settings
                $this->setProjectSettings($new, $pid);
            }
        }
    }

    /**
     * The code for getFormattedSettings and _getFormattedSettings 
     * originates from https://github.com/ctsit/redcap_css_injector
     * as of March 27, 2019.
     */

    /**
     * Formats settings into a hierarchical key-value pair array.
     * 
     * @param int $project_id
     *   Enter a project ID to get project settings.
     *   Leave blank to get system settings.
     *
     * @return array
     *   The formatted settings.
     */
    function getFormattedSettings($project_id = null) {
        $settings = $this->getConfig();

        if ($project_id) {
            $settings = $settings['project-settings'];
            $values = ExternalModules::getProjectSettingsAsArray($this->PREFIX, $project_id);
        }
        else {
            $settings = $settings['system-settings'];
            $values = ExternalModules::getSystemSettingsAsArray($this->PREFIX);
        }

        return $this->_getFormattedSettings($settings, $values);
    }

    /**
     * Auxiliary function for getFormattedSettings().
     */
    protected function _getFormattedSettings($settings, $values, $inherited_deltas = []) {
        $formatted = [];

        foreach ($settings as $setting) {
            $key = $setting['key'];
            $value = $values[$key]['value'];
            if ($value == null) continue;
            
            foreach ($inherited_deltas as $delta) {
                $value = $value[$delta];
            }

            if ($setting['type'] == 'sub_settings') {
                $deltas = array_keys($value);
                $value = [];

                foreach ($deltas as $delta) {
                    $sub_deltas = array_merge($inherited_deltas, [$delta]);
                    $value[$delta] = $this->_getFormattedSettings($setting['sub_settings'], $values, $sub_deltas);
                }

                if (empty($setting['repeatable'])) {
                    $value = $value[0];
                }
            }

            $formatted[$key] = $value;
        }

        return $formatted;
    }
}
