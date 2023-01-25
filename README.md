# REDCap JavaScript Injector

A REDCap External Module that allows injection of JavaScript on pages.

## Requirements

- REDCap 12.0.0 or newer (tested with REDCap 13.2.4).

## Installation

- Clone this repo into `<redcap-root>/modules/redcap_js_injector_v<version-number>`, or
- Obtain this module from the Consortium [REDCap Repo](https://redcap.vanderbilt.edu/consortium/modules/index.php) via the Control Center.
- Go to _Control Center > Technical / Developer Tools > External Modules_ and enable REDCap JavaScript Injector.

## System Configuration

Admins can set this module's project configurations to be accessible only to super users with the **Allow only super-users to configure this module in projects** option.

New since version 2, admins can define _global_ JavaScript injections and optionally limit their scope to certain pages with the **Enable system-defined JavaScript injections** option.

> **NOTE:** Note that for any injections targeted at project contexts, these will only work if the module is enabled in the projects. To achieve this without exposing the module's project configuration too widely, it is recommended to turn on **Enable module on all projects by default** and **Hide this module from non-admins in the list of enabled modules on each project** (and then enable visibility in the projects where the module should be exposed). 

Snippets can be turned on/off for system/projects context with the **Enabled in system/project contexts** options. For both, system and project pages, the pages where injection occurs can then be set to include all such context pages or be limited to a few selected pages.

If more than one snippet is injected into the same page, the injection occurs in the order the snippets are defined in the configuration dialog.

## Project Configuration

In a project, go to _Applications > External Modules_ and click the _Configure_ button for the REDCap JS Injector module.

In the configuration dialog, you can define JavaScript snippets for your project that are injected in different contexts. Each context is defined by a page type (_Project Home Page_, _Record Status Dashboard_, _Add / Edit Records_, _Record Home Page_, and _Surveys_, _Data Entry Pages_, _Both, Surveys and Data Entry Pages_, _Project Dashboards_, _Reports_, or _All project pages_. For data entry and survey pages, the context can be further limited by specifying one or more instruments.

The configuration options include a checkbox to enable/disable each of the JavaScript snippets. Make sure to enable the ones you want to be injected.

If more than one snippet is injected into the same page, the injection occurs in the order the snippets are defined in the configuration dialog.

_Note:_ Due to a limitation in the EM configuration dialog, branching logic does not work for nested elements, and thus the instrument selection box cannot be hidden when not applicable (in case of _Project Home Page_, _Record Status Dashboard_, _Add / Edit Records_, and _Record Home Page_).

## Acknowledgments

The original version of this external module was basically just a modified version of the [REDCap CSS Injector](https://github.com/ctsit/redcap_css_injector) module. Version 2 is a major overhaul. 

## Changelog

Version | Description
------- | ------------------
2.0.0   | Major new feature: System and pan-project injections. Redesigned page limitation setup (old settings will be migrated automatically).
1.1.4   | Added additional injection options.
1.1.3   | Fix an issue where a (silent, but logged) exception would be thrown.
1.1.2   | Add system setting for limiting project configuration access to super users only.
1.1.1   | Fix the _All project pages_ behavior that was broken in version 1.1.0.<br>Disable branching logic.<br>Add instructions for testing the module.
1.1.0   | New feature: Inject JS on more project pages: _Project Home Page_, _Record Home Page_, _Add / Edit Records_, and _Record Status Dashboard_.
1.0.0   | Initial release.
