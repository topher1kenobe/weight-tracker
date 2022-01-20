# Topher's Weight Tracker

**Contributors:** topher1kenobe

**Requires at least:** 4.5

**Tested up to:** 5.8.4

**Stable tag:** 1.0.0

**License:** GPLv3 or later

**License URI:** http://www.gnu.org/licenses/gpl-3.0.html

This is a WordPress plugin that gets weight data from a google sheet and renders it with google charts. It uses shortcodes.

## Installation

Download the zip file from Github and use WordPress' install tool.

## Requires

A Google Docs API key. You'll need to use a special URL for your sheet with the API key attached. The process of getting the API key shows how. https://developers.google.com/sheets/api

## Usage

Google sheets should be in this format: https://docs.google.com/spreadsheets/d/1f_sIAhWUElvSP1AyzRBJPjhQCDv23nW6fPNldzR-JWk/edit#gid=0

### returns an integer of the difference from beginning to end.
[topher_total_loss url='YOURSHEETAPIURL/?key=YOURAPIKEY']

### returns an integer showing the time span from beginning to end
[topher_loss_timespan url='YOURSHEETAPIURL/?key=YOURAPI KEY']

### returns google chart
[topher_weight_loss_chart url='YOURSHEETAPIURL/?key=YOURAPI KEY']

