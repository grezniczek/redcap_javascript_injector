<?php namespace DE\RUB\JSInjectorExternalModule;

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

    // Workaround for the redcap_module_system_change_version hook not working
    function redcap_module_link_check_display($project_id, $link) {
        if ($this->getSystemSetting("v1-upgrade") != "done") {
            $this->convert_v1_settings();
        }
        return null;
    }

    // Perform settings upgrade to v2 model
    function redcap_module_system_change_version($version, $old_version) {
        $major = explode(".", trim($old_version, "v"))[0] * 1;
        if ($major == 1) {
            $this->convert_v1_settings();
        }
    }

    // Insert JSMO name into config dialog
    function redcap_module_configuration_settings($project_id, $settings) {
        $key = $project_id == null ? "sys-jsmo-info" : "proj-jsmo-info";
        $jsmo = $this->getJavascriptModuleObjectName();
        foreach ($settings as &$setting) {
            foreach ($setting["sub_settings"] as &$sub_setting) {
                if ($sub_setting["key"] === $key) {
                    $sub_setting["name"] = str_replace("#JSMO#", $jsmo, $sub_setting["name"]);
                }
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
                else if (self::IsSystemExternalModulesManager($page)) {
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
        $snippets = $this->parse_settings($project_id, array_keys($context));

        // Determine if this is a "named" context
        $named_context = array_reduce($context, function($carry, $item) {
            return $carry || $item;
        }, false);
        // Then, get the context name
        $context_names = array_keys(array_filter($context, function($v) {
            return $v;
        }));

        $inject_jsmo = false;
        $debug_jsmo = false;

        // Verify snippets
        $snippets_to_inject = [];
        foreach ($snippets as $snippet) {
            $inject = false;
            $reject = false;

            // Project context? Check project list and enabled state, but not for "login"
            if ($project_id != null && !in_array("login", $context_names, true)) {
                if (!$snippet["proj-enabled"]) {
                    $reject = true;
                }
                else if ($snippet["proj-limit"] == "include") {
                    if (!in_array($project_id, $snippet["proj-list"], true)) {
                        $reject = true;
                    }
                }
                else if ($snippet["proj-limit"] == "exclude") {
                    if (in_array($project_id, $snippet["proj-list"], true)) {
                        $reject = true;
                    }
                }
            }
            else {
                if (!$snippet["sys-enabled"]) {
                    $reject = true;
                }
            }
            // Rejected? Continue to next item
            if ($reject) continue;

            // Page type / context
            if ($named_context) {
                // Check if there is a named match
                foreach ($context_names as $this_ctx) {
                    // Additionally evaluate form for data entry and survey pages
                    $instrument_check = true;
                    if ($snippet["type"] == "proj" && in_array($this_ctx, ["data_entry","survey"], true)) {
                        if (count($snippet["form-list"])) {
                            $instrument_check = in_array($instrument, $snippet["form-list"], true);
                        }
                    }
                    $inject = $inject || ($snippet["ctx"][$this_ctx] && $instrument_check);
                }
            }
            else {
                // Check if the snippet should always be injected
                $inject = $snippet["ctx"][$project_id == null ? "sysall" : "projall"];
            }

            if ($inject) {
                $snippets_to_inject[] = $snippet;
                $inject_jsmo = $inject_jsmo || $snippet["jsmo"];
                $debug_jsmo = $debug_jsmo || ($snippet["jsmo"] && $snippet["debug"]);
            }
        }

        // JSMO
        if ($inject_jsmo) {
            $this->initializeJavascriptModuleObject();
            if ($debug_jsmo) {
                $jsmo_name = $this->fw->getJavascriptModuleObjectName();
                print "<script>console.log('JS Injector: Injected JavascriptModuleObject \"{$jsmo_name}\"', {$jsmo_name});</script>\n";
            }
        }

        // Inject snippets (after JSMO)
        foreach ($snippets_to_inject as $snippet) {
            // Add debug info
            $info = $snippet["debug"] ? " data-from=\"REDCap JavaScript Injector {$this->VERSION}\"" : "";
            $context = $snippet["debug"] ? " data-context=\"{$snippet["type"]}\"" : "";
            $name = $snippet["debug"] ? (" data-name=\"" . js_escape($snippet["name"]) . "\"") : "";
            // Actual injection
            print "<script{$info}{$context}{$name}>\n\t{$snippet["code"]}\n</script>\n";
            // More debug info, after the fact
            if ($snippet["debug"]) {
                print "<script>console.log('JS Injector: Injected snippet \"' + ".json_encode($snippet["name"]) . " + '\"');</script>\n";
            }
        }
    }

    #region Settings Parser

    /**
     * Parses project and system settings into a useable format.
     * @param string|null $project_id 
     * @return array 
     */
    function parse_settings($project_id = null, $contexts) {
        // Make a list of all snippets
        $snippets = [];
        // Load settings
        $ss = $this->getSystemSettings();
        $ps = $project_id == null ? [ "proj-snippet" => []] : $this->getProjectSettings($project_id);
        // Parse system settings
        foreach ($ss["sys-snippet"]["system_value"] as $i => $_) {
            $snippet = [
                "type" => "sys"
            ];
            $snippet["name"] = $ss["sys-name"]["system_value"][$i];
            if (empty($snippet["name"])) {
                $snippet["name"] = "<unnamed>";
            }
            $snippet["jsmo"] = $ss["sys-jsmo"]["system_value"][$i] == true;
            $snippet["debug"] = $ss["sys-debug"]["system_value"][$i] == true;
            $snippet["code"] = $ss["sys-code"]["system_value"][$i] ?? "";
            $snippet["sys-enabled"] = $ss["sys-enabled"]["system_value"][$i] == true;
            $snippet["proj-enabled"] = $ss["sys-proj-enabled"]["system_value"][$i] == true;
            $snippet["proj-limit"] = "all";
            $snippet["proj-list"] = [];
            $snippet["form-list"] = [];
            if ($snippet["proj-enabled"]) {
                $limit = $ss["sys-proj-limit"]["system_value"][$i];
                $snippet["proj-limit"] = in_array($limit, ["include","exclude"], true) ? $limit : "all";
                $snippet["proj-list"] = array_unique(explode(",", trim($ss["sys-proj-list"]["system_value"][$i] ?? "")));
            }
            $snippet["ctx"]["projall"] = $ss["sys-proj-context_all"]["system_value"][$i] == true;
            $snippet["ctx"]["sysall"] = $ss["sys-context_all"]["system_value"][$i] == true;
            foreach ($contexts as $this_context) {
                $snippet["ctx"][$this_context] = false;
            }
            foreach ($ss as $this_key => $_) {
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
        foreach ($ps["proj-snippet"] as $i => $_) {
            $snippet = [
                "type" => "proj"
            ];
            $snippet["name"] = $ps["proj-name"][$i];
            if (empty($snippet["name"])) {
                $snippet["name"] = "<unnamed>";
            }
            $snippet["sys-enabled"] = false;
            $snippet["proj-enabled"] = $ps["proj-enabled"][$i] == true;
            $snippet["jsmo"] = $ps["proj-jsmo"][$i] == true;
            $snippet["debug"] = $ps["proj-debug"][$i] == true;
            $snippet["code"] = $ps["proj-code"][$i];
            $snippet["proj-limit"] = "include";
            $snippet["proj-list"] = [$project_id];
            $snippet["form-list"] = $ps["proj-instruments"][$i];
            $snippet["ctx"]["projall"] = $ps["proj-context_all"][$i] == true;
            $snippet["ctx"]["sysall"] = false;
            foreach ($contexts as $this_context) {
                $snippet["ctx"][$this_context] = false;
            }
            foreach ($ps as $this_key => $this_val) {
                if (starts_with($this_key, "proj-context")) {
                    $this_context = array_pop(explode("_", $this_key, 2));
                    if (in_array($this_context, $contexts, true)) {
                        if ($this_val[$i] == "include") {
                            $snippet["ctx"][$this_context] = true;
                        }
                        else if ($this_val[$i] <> "exclude") {
                            $snippet["ctx"][$this_context] = $snippet["ctx"]["projall"];
                        }
                    }
                }
            }
            $snippets[] = $snippet;
        }
        return $snippets;
    }

    #endregion

    #region Legacy (v1) Settings Conversion

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
                    // Removes legacy settings
                    "js" => null,
                    "js_enabled" => null,
                    "js_type" => null,
                    "js_instruments" => null,
                    "js_code" => null
                ];
                foreach ($old["js"] as $i => $_) {
                    $new["proj-snippet"][$i] = true;
                    $new["proj-enabled"][$i] = $old["js_enabled"][$i];
                    $new["proj-jsmo"][$i] = false;
                    $new["proj-debug"][$i] = false;
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
        // Workaround for version-change hook not working
        $this->setSystemSetting("v1-upgrade", "done");
    }

    #endregion

    #region Helpers

    public static function IsSystemExternalModulesManager($page) {
        return (strpos($page, "manager/control_center.php") !== false);
    }

    public static function IsProjectExternalModulesManager($page) {
        return (strpos($page, "manager/project.php") !== false);
    }

    #endregion

}
