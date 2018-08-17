# advanced-synchronisation
Advanced synchronisation feature for the Wordpress MultilingualPress plugin

## Purpose
This feature extends the Wordpress MultilingualPress plugin (https://wordpress.org/plugins/multilingual-press/) by providing synchronisation of:
* Publish dates for posts and pages
* Tags and categories for posts

## Installation
Copy the files of the inc folder into the inc folder of your MultilingualPress plugin directory.

## Configuration
Go to your MultilingualPress settings page, enable the Advanced Synchronisation and save changes. Now on the same page a dedicated Advanced Synchronisation Settings box should be visible where you can choose which advanced synchronisation you would like to enable. Check the one you want to enable and save changes.

## Usage
When updating a post or a page the publish date, categories and tags will be copied to the related translation if the corresponding categories and tags exists in the translated site. This feature works both while editing a post/page or trough quick edit.
