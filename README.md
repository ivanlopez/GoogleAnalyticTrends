GoogleAnalyticTrends
====================

Popular content based on Google Analytics

## Installation
This module can be easily installed by adding `10up/googleanalytictrends` to your `composer.json` file. Then, either autoload your Composer dependencies or manually `include()` the `GoogleAnalyticTrends.php` bootstrap file.

Additionally, you must define the following contestants:

* `GAT_CLIENT_ID` Client ID provided by Google API
* `GAT_SERVICE_ACCOUNT_NAME` Service account name provided by Google API
* `GAT_CLIENT_ID` Path to the `privatekey.p12` provided by Google API
* `GAT_VIEW_ID` View ID from from view setting section of Google Analytics dashboard

## Setting up Google API
1. Go to https://console.developers.google.com and create a new project
2. Click APIs & Auth on the left rail then click the API submenu item
3. Turn on the `Analytics API`
4. Click the Credentials button on the left rail
5. Create a new Client ID
6. From the Create Client ID modal select Service account
7. You will be prompted to download a .p12 file that you need to add anywhere in your WordPress install
8. On the final screen you will see the Client ID and Email that is needed for setting up this module


