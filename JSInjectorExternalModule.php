<?php

namespace DE\RUB\JSInjectorExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

/**
 * ExternalModule class for Javascript Injector.
 */
class JSInjectorExternalModule extends AbstractExternalModule {

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

    function redcap_module_configure_button_display() {
        if ($this->getSystemSetting("su_only") && !SUPER_USER) return null;
        return true;
    }

    function redcap_data_entry_form_top($project_id, $record = null, $instrument, $event_id, $group_id = null, $repeat_instance = 1) {
        $this->injectJS("data_entry", $instrument);
    }

    function redcap_survey_page_top($project_id, $record = null, $instrument, $event_id, $group_id = null, $survey_hash, $response_id = null, $repeat_instance = 1) {
        $this->injectJS("survey", $instrument);
    }

    function redcap_project_home_page ($project_id) {
        $this->injectJS("php", null);
    }

    function redcap_every_page_top ($project_id) {
        if (PAGE === "DataEntry/record_status_dashboard.php") {
            $this->injectJS("rsd", null);
        }
        else if (PAGE === "DataEntry/record_home.php" && !isset($_GET["id"])) {
            $this->injectJS("aer", null);
        }
        else if (PAGE === "DataEntry/record_home.php" && isset($_GET["id"])) {
            $this->injectJS("rhp", null);
        }
        else if (strpos(PAGE, "ProjectDashController:view") !== false && isset($_GET["dash_id"])) {
            $this->injectJS("db", null);
        }
        else if (strpos(PAGE, "surveys/index.php") !== false && isset($_GET["__dashboard"])) {
            $this->injectJS("dbp", null);
        }
        else if (strpos(PAGE, "DataExport/index.php") !== false && isset($_GET["report_id"])) {
            $this->injectJS("report", null);
        }
        // All project pages.
        if ($project_id !== null) {
            $this->injectJS("all", null);
        }
    }

    /**
     * Inject JS code.
     *
     * @param string $type
     *   Accepted types: 'data_entry' or 'survey'.
     * @param string $instrument
     *   The instrument name.
     */
    function injectJS($type, $instrument) {
        $settings = $this->getFormattedSettings(PROJECT_ID);

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
