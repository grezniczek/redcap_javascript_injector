# REDCap JavaScript Injector

A REDCap External Module that allows injection of JavaScript on pages.

## Requirements

- REDCap 8.1.0 or newer (tested with REDCap 8.11.7).

## Installation

- Clone this repo into `<redcap-root>/modules/redcap_js_injector_v<version-number>`, or
- Obtain this module from the Consortium [REDCap Repo](https://redcap.vanderbilt.edu/consortium/modules/index.php) via the Control Center.
- Go to _Control Center > Technical / Developer Tools > External Modules_ and enable REDCap JavaScript Injector.

## Configuration

In a project, go to _Applications > External Modules_ and click the _Configure_ button for the REDCap JS Injector module.

In the configuration dialog, you can either define a global JavaScript snippet for your project or add multiple snippets that are injected in different contexts. Each context is defined by a list of instruments and/or limiting the scope to survey pages, data entry pages, or the Project Home Page, the Record Status Dashboard, the Record Home Page, and the Add / Edit Records pages.

The configuration options include a checkbox to enable/disable each of the JavaScript snippets. Make sure to enable the ones you want to be injected.

If more than one snippet is injected into the same page, the injection occurs in the order the snippets are defined in the configuration dialog.

_Note:_ Due to a limitation in the EM configuration dialog, branching logic does not work for nested elements, and thus the instrument selection box cannot be hidden when not applicable (in case of Project Home Page, Record Home Page, Record Status Dashboard, and Add / Edit Records).

## Acknowledgments

This external module is basically just a modification of the [REDCap CSS Injector](https://github.com/ctsit/redcap_css_injector) module.

## Changelog

Version | Description
------- | ------------------
v1.1.0  | New feature: Inject JS on more project pages: Project Home Page, Record Home Page, Add / Edit Records, and Record Status Dashboard
v1.0.0  | Initial release.
