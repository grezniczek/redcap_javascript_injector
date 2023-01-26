<?php

namespace DE\RUB\JSInjectorExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

/**
 * ExternalModule class for Javascript Injector.
 */
class JSInjectorExternalModule extends AbstractExternalModule {

    /**
     * EM Framework (tooling support)
     * @var \ExternalModules\Framework
     */
    private $fw;

    function __construct() {
        parent::__construct();
        $this->fw = $this->framework;
    }


    #region Hooks

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
        $this->inject_js($project_id, $context, $instrument);
    }

    #endregion

    /**
     * Inject JS code based on context.
     *
     * @param string|null $project_id
     * @param array $context
     * @param string $instrument
     */
    function inject_js($project_id, $context, $instrument) {

        // Get "work schedule" ;)
        $settings = $this->parse_settings($project_id, array_keys($context));

        // JSMO
        if ($settings["jsmo"]) {
            $this->initializeJavascriptModuleObject();
            if ($settings["sys-debug"] || $settings["proj-debug"]) {
                $jsmo_name = $this->fw->getJavascriptModuleObjectName();
                print "<script>console.log('JS Injector: Injected JavascriptModuleObject \"{$jsmo_name}\"', {$jsmo_name});</script>\n";
            }
        }
        
        // Snippets
        foreach ($settings["snippets"] as $snippet) {
            $inject = true;
            $debug = $settings["{$snippet["type"]}-debug"];
            
            
            
            if ($inject) {
                // Add debug info
                $info = $debug ? " data-from=\"REDCap JavaScript Injector {$this->VERSION}\"" : "";
                $context = $debug ? " data-context=\"{$snippet["type"]}\"" : "";
                $name = $debug ? (" data-name=\"" . js_escape($snippet["name"]) . "\"") : "";
                // Actual injection
                print "<script{$info}{$context}{$name}>\n\t{$snippet["code"]}\n</script>\n";
                // More debug info, after the fact
                if ($debug) {
                    print "<script>console.log('JS Injector: Injected snippet \"' + ".json_encode($snippet["name"]) . " + '\"');</script>\n";
                }
            }
        }
    }

    #region Settings Parser

    /**
     * Parses project and system settings into a useable format
     * @param string|null $project_id 
     * @return array 
     */
    function parse_settings($project_id = null, $contexts) {
        // Make a list of all snippets
        $snippets = [];
        // Load settings
        $ss = $this->getSystemSettings();
        $ps = $this->getProjectSettings($project_id);
        $jsmo = ($ss["sys-jsmo"]["system_value"] == true) || ($ps["proj-jsmo"] == true);
        // Parse system settings
        foreach ($ss["sys-injections"]["system_value"] as $i => $_) {
            $snippet["type"] = "sys";
            $snippet["name"] = $ss["sys-name"]["system_value"][$i];
            if (empty($snippet["name"])) {
                $snippet["name"] = "<unnamed>";
            }
            $snippet["sys-enabled"] = $ss["sys-enabled"]["system_value"][$i] == true;
            $snippet["proj-enabled"] = $ss["sys-proj-enabled"]["system_value"][$i] == true;
            $snippet["proj-limit"] = "all";
            $snippet["proj-list"] = [];
            if ($snippet["proj-enabled"]) {
                $limit = $ss["sys-proj-limit"]["system_value"][$i];
                $snippet["proj-limit"] = in_array($limit, ["include","exclude"], true) ? $limit : "all";
                $snippet["proj-list"] = array_unique(explode(",", trim($ss["sys-proj-list"]["system_value"][$i] ?? "")));
            }
            $snippet["code"] = $ss["sys-code"]["system_value"][$i];
            $snippet["ctx"]["projall"] = $ss["sys-proj-context_all"]["system_value"][$i] == true;
            $snippet["ctx"]["sysall"] = $ss["sys-context_all"]["system_value"][$i] == true;
            foreach ($contexts as $this_context) {
                $snippet["ctx"][$this_context] = false;
            }
            foreach ($ss as $this_key => $this_val) {
                $this_context = array_pop(explode("_", $this_key, 2));
                if (in_array($this_context, $contexts, true)) {
                    $val = $ss[$this_key]["system_value"][$i];
                    if ($val == "include") {
                        $snippet["ctx"][$this_context] = true;
                    }
                    else if ($val <> "exclude") {
                        $snippet["ctx"][$this_context] = contains($this_key, "proj-context") ? $snippet["ctx"]["projall"] : $snippet["ctx"]["sysall"];
                    }
                }
            }
            $snippets[] = $snippet;
        }
        // Parse project settings
        foreach ($ps["proj-injections"] as $i => $_) {
            $snippet["type"] = "proj";
            $snippet["name"] = $ps["proj-name"][$i];
            if (empty($snippet["name"])) {
                $snippet["name"] = "<unnamed>";
            }
            $snippet["sys-enabled"] = false;
            $snippet["proj-enabled"] = $ps["proj-enabled"][$i] == true;
            $snippet["proj-limit"] = "include";
            $snippet["proj-list"] = [$project_id];
            $snippet["code"] = $ps["proj-code"][$i];
            $snippet["ctx"]["projall"] = $ps["proj-context_all"][$i] == true;
            $snippet["ctx"]["sysall"] = false;
            foreach ($contexts as $this_context) {
                $snippet["ctx"][$this_context] = false;
            }
            foreach ($ps as $this_key => $this_val) {
                if (starts_with($this_key, "proj-context")) {
                    $this_context = array_pop(explode("_", $this_key, 2));
                    if (in_array($this_context, $contexts, true)) {
                        $val = $ps[$this_key][$i];
                        if ($val == "include") {
                            $snippet["ctx"][$this_context] = true;
                        }
                        else if ($val <> "exclude") {
                            $snippet["ctx"][$this_context] = $snippet["ctx"]["projall"];
                        }
                    }
                }
            }
            $snippets[] = $snippet;
        }
        return [ 
            "sys-debug" => $ss["sys-debug"]["system_value"] == true,
            "proj-debug" => $ps["proj-debug"] == true,
            "jsmo" => $jsmo, 
            "snippets" => $snippets
        ];
    }

    #endregion

    #region Legacy Conversion

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

    #endregion


}
