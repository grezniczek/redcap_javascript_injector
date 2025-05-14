# REDCap JavaScript Injector

[![DOI](https://zenodo.org/badge/DOI/10.5281/zenodo.15120311.svg)](https://doi.org/10.5281/zenodo.15120311)

A REDCap External Module that allows injection of JavaScript on pages.

## Requirements

- REDCap with EM Framework v14 support.

## Installation

- Clone this repo into `<redcap-root>/modules/redcap_javascript_injector_v<version-number>`, or
- Obtain this module from the Consortium [REDCap Repo](https://redcap.vumc.org/consortium/modules/index.php) via the Control Center.
- Go to _Control Center > Technical / Developer Tools > External Modules_ and enable REDCap JavaScript Injector.

### Upgrading from version 1.x

Configuration data from version 1 of this module will be automatically converted to the new configuration model used by version 2.

**Warning**: Once upgraded, there is no way going back to the previous configuration! Thus, it is strongly advised to make a backup of the module settings in all projects using _JavaScript Injector_ before upgrading.

## Project Configuration

In a project, go to _Applications > External Modules_ and click the _Configure_ button for the REDCap JS Injector module.

In the configuration dialog, you can define JavaScript snippets for your project that are injected in _All project pages_ or limited to a choice of different project pages, including 
- _Project Home Page_,
- _Project Setup Page_,
- _Record Status Dashboard_,
- _Add / Edit Records_,
- _Record Home Page_, 
- _Data Entry Pages_,
- _Surveys_,
- _Reports_, and
- _Project Dashboards_.

For data entry and survey pages, injection can be further limited by specifying one or more instruments.

Additionally, project injection pages can be controlled by adding parts of the URL to inclusion (_Enable_) and exclusion (_Disable_) list. When any inclusion items are found in the URL, a snippet is included. When any exclusion item matches, the injection will be rejected. Exclusion has priority over inclusion (and over any of the other settings). Add one item per line. When a line consists of multiple character sequences separated by spaces, all of them have to match, but the order or overlaps are not important.
E.g., to match the _"Create New Report"_ and _"Edit Existing Report"_ pages, add this to the inclusion box: `DataExport/index.php addedit=1`. To target the _"Create New Report"_ specifically, you could add the term `create=1` to the inclusion box (on the same line), or alternatively add `report_id=` to the exclusion box.

If more than one snippet is injected on the same page, the injections occur in the order the snippets are defined in the configuration dialog.

## System Configuration

Admins can set this module's project configurations to be accessible only to super users with the **Allow only super-users to configure this module in projects** option.

**New since version 2:** Admins can define _global_ JavaScript injections and optionally limit their scope to certain pages.

> **NOTE:** Note that for any injections targeted at project contexts, these will only work if the module is enabled in the projects. To achieve this without exposing the module's project configuration too widely, it is recommended to turn on **Enable module on all projects by default** and **Hide this module from non-admins in the list of enabled modules on each project** (and then enable visibility in the projects where the module should be exposed). 

Snippets can be turned on/off for system/projects contexts with the **Enabled in system/project contexts** options. For both, system and project pages, the pages where injection occurs can then be set to include all such context pages or to be limited to a few selected pages.

In addition to the projects pages listed above, the following non-project pages can be targeted:
- _Control Center_ (this will include any of the other listed options),
- _To-Do List_,
- _Language File Creator/Updater_,
- _Browse Projects_,
- _Create/Edit Single User_,
- _Email Users_,
- _Login Page_,
- _Home_,
- _My Projects_, 
- _New Project_, 
- _Help & FAQ_, 
- _Training Videos_, 
- _Send-It_, and
- _Sponsor Dashboard_

If more than one snippet is injected on the same page, the injections occur in the order the snippets are defined in the configuration dialog.

## JavascriptModuleObject and Dynamic Pages

When injecting into dynamic pages, the effect of a JavaScript snippet may have to be re-applied after a re-render of a page (without reloading). This is often the case on survey pages and data entry forms with Multi-Language Management (MLM) enabled, as switching between languages redraws parts of the screen. To detect such changes, the EM Framework's _JavascriptModuleObject_ (JSMO) provides a convenient mechanism (`JSMO.afterRender()`), where a callback function can be regisered that will then be executed each time after REDCap has redrawn (parts of) the page. While `afterRender` will be mostly triggered by MLM, this mechanism is not dependent on MLM being active, and thus can be used on any page, with MLM turned on or off. Access to the JSMO in custom JavaScript snippets can now be obtained by enabling the **Add the JavascriptModuleObject** option (the concrete name of the JSMO is shown under this option). This option should be enabled for each JavaScript snippet that uses the JSMO (the module and _EM Framework_ will ensure that the JSMO is injected only once).

Thus, to have JavaScript snippet do something each time after a re-render of the page, inject code such as this:

```JS
ExternalModules.DE.RUB.JSInjectorExternalModule.afterRender(function() {
    console.log('Rendered'); // Replace with your code
});
```


## Acknowledgments

The original version of this external module was basically just a modified version of the [REDCap CSS Injector](https://github.com/ctsit/redcap_css_injector) module. Version 2 is a major overhaul. 

## Changelog

Version | Description
------- | ------------------
2.3.1   | Fixed Repository link in README.
2.3.0   | New feature: Add JS from a file in addition to entering it into directly into module config.<br>New options to direct injections.
2.2.1   | Added support for new page: Project Setup Page.
2.2.0   | Require EM Framework v14.<br>Added "enable-every-page-hooks-on-login-form" to config.json.
2.1.1   | Minor code change to prevent a PHP strict mode warning.
2.1.0   | Support for addition all system page: Home, My Projects, New Project, Help & FAQ, Training Videos, Send-It, Sponsor Dashboard.<br>Bumped framework version to 12.
2.0.2   | Removed class constructor; PHP8-related fix.
2.0.1   | Bugfix (broken form list limitation).
2.0.0   | Major new feature: System and pan-project injections.<br>Redesigned page limitation setup (old settings will be migrated automatically).<br>Debug logging.
1.1.4   | Added additional injection options.
1.1.3   | Fix an issue where a (silent, but logged) exception would be thrown.
1.1.2   | Add system setting for limiting project configuration access to super users only.
1.1.1   | Fix the _All project pages_ behavior that was broken in version 1.1.0.<br>Disable branching logic.<br>Add instructions for testing the module.
1.1.0   | New feature: Inject JS on more project pages: _Project Home Page_, _Record Home Page_, _Add / Edit Records_, and _Record Status Dashboard_.
1.0.0   | Initial release.

## How to cite this work

If you use this external module for a project that generates a research output, please cite this software in addition to [citing REDCap](https://projectredcap.org/resources/citations/). You can do so using the APA referencing style as below:

> Rezniczek, G. A. (2025). REDCap JavaScript Injector (Version 2.3.0) [Computer software]. https://doi.org/10.5281/zenodo.15120311

Or by adding this reference to your BibTeX database:

```bibtex
@software{Rezniczek_REDCap_JavaScript_Injector_2025,
  author = {Rezniczek, Günther A.},
  title = {{REDCap JavaScript Injector}},
  version = {2.3.1},
  year = {2025}
  month = {5},
  doi = {10.5281/zenodo.15120311},
  url = {https://github.com/grezniczek/redcap_javascript_injector},
}
```

These instructions are also available in [GitHub](https://github.com/grezniczek/redcap_javascript_injector) under 'Cite This Repository'.

## Support this work

If you find this software useful, you can [buy me a coffee or a beer](https://www.paypal.com/donate/?hosted_button_id=6VRC2JFRCBGRN). Your support is purely voluntary and helps me continue improving this project. Of course, you are not entitled to any special benefits—except my silent appreciation while enjoying the drink! 🍻☕  

You can use the link or the QR code below to make a donation via PayPal.

![PayPal QR Code](/images/qr-paypal.png)

_Please note that donations are purely voluntary and not tax-deductible._

