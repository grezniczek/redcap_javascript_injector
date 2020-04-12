# JavaScript Injector - Manual Testing Procedure

Version 1 - 2020-04-12

## Prerequisites

- A project with **two** instruments, both enabled as surveys.
- JavaScript Injector is enabled for this project.
- No other external modules should be enabled, except those with which this module's interaction should be tested.

## Test Procedure

1. Using an admin account, configure the module:
   - Create JS Code snippets for each for the pages listed under _Apply to_.
   - Turn on _Enabled_ for each.
   - The snippet should read `console.log('Page type')`, where _Page type_ is the same as the active choice in _Apply to_.
   - For the _Surveys_ injection, limit to the **first** survey instrument.
   - For the _Data Entry Pages_ injection, limit to the second survey instrument.
1. Press F12 to open the browser tools and switch to the console.
1. Click the _Project Home_ link on the main menu and verify the following:
   - The _All project pages_ alert is displayed.
   - The _Project Home Page_ alert is displayed.
1. Click the _Record Status Dashboard_ link on the main menu and verify the following:
   - The _Record Status Dashboard_ alert is displayed.
   - The _All project pages_ alert is displayed.
1. Click the _Add new record_ button on the _Record Status Dashboard_ and verify the following:
   - The _Record Home Page_ alert is displayed.
   - The _All project pages_ alert is displayed.
1. Open the first instrument (by clicking the gray icon) and verify the following:
   - The _All project pages_ alert is displayed.
   - The _Both, Surveys and Data Entry Pages_ alert is displayed.
1. Click the _Save & Exit Form_ button and verify the following:
   - The _Record Home Page_ alert is displayed.
   - The _All project pages_ alert is displayed.
1. Open the second instrument (by clicking the gray icon) and verify the following:
   - The _All project pages_ alert is displayed.
   - The _Data Entry Pages_ alert is displayed.
   - The _Both, Surveys and Data Entry Pages_ alert is displayed.
1. Using the _Survey options_ button, open the instrument in survey mode, press F12, and verify the following:
   - The _All project pages_ alert is displayed.
   - The _Both, Surveys and Data Entry Pages_ alert is displayed.
1. Submit the survey and verify the following:
   - The _All project pages_ alert is displayed.
1. Close the survey.
1. Click on _Leave without saving changes_ and verify the following:
   - The _Record Home Page_ alert is displayed.
   - The _All project pages_ alert is displayed.
1. Click the _Add / Edit Records_ link on the main menu and verify the following:
   - The _Add / Edit Records_ alert is displayed.
   - The _All project pages_ alert is displayed.
1. Click the _Survey Distribution Tools_ link on the main menu and verify the following:
   - The _All project pages_ alert is displayed.
1. Click the _Open public survey_ button, press F12, and verify the following:
   - The _All project pages_ alert is displayed.
   - The _Surveys_ alert is displayed.
   - The _Both, Surveys and Data Entry Pages_ alert is displayed.

Done.

## Reporting Errors

Before reporting errors:
- Make sure there is no interference with any other external module by turning off all others and repeating the tests.
- Check if you are using the latest version of the module. If not, see if updating fixes the issue.

To report an issue:
- Please report errors by opening an issue on [GitHub](https://github.com/grezniczek/redcap_javascript_injector/issues) or on the community site (please tag @gunther.rezniczek). 
- Include essential details about your REDCap installation such as **version** and platform (operating system, PHP version).
- If the problem occurs only in conjunction with another external module, please provide its details (you may also want to report the issue to that module's authors).
