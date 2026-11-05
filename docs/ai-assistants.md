---
title: AI assistants
sidebar_position: 1.27
---

The package ships what AI coding assistants need to use it correctly.

## Laravel Boost

With [Laravel Boost](https://github.com/laravel/boost) installed, run `php artisan boost:install`. It picks up:

- the guideline `resources/boost/guidelines/core.blade.php`: the rules for using the CMS;
- the skill `javaabu-cms` (`resources/boost/skills/javaabu-cms/SKILL.md`): setting up the routes, the content model, the navigation, the theme, the import from v1, with references for every API and the common mistakes.

## llms.txt

`vendor/javaabu/cms/llms.txt` lists the rules and every docs page. Point your assistant at it.
