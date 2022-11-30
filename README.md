# Laravel Auto-Reg

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/laravel-auto-reg.svg?style=flat-square)](https://packagist.org/packages/code-distortion/laravel-auto-reg)
![PHP Version](https://img.shields.io/badge/PHP-7.4%20to%208.1-blue?style=flat-square)
![Laravel](https://img.shields.io/badge/laravel-8%20%26%209-blue?style=flat-square)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/code-distortion/laravel-auto-reg/run-tests?label=tests&style=flat-square)](https://github.com/code-distortion/laravel-auto-reg/actions)
[![Buy The World a Tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://plant.treeware.earth/code-distortion/laravel-auto-reg)
[![Contributor Covenant](https://img.shields.io/badge/contributor%20covenant-v2.0%20adopted-ff69b4.svg?style=flat-square)](CODE_OF_CONDUCT.md)

***code-distortion/laravel-auto-reg*** is a [Laravel](https://github.com/laravel/laravel) package that registers your service-providers, configs, commands, routes, broadcast channels, migrations, blade-templates and translations etc for you, in a project with a non-standard directory structure.



## Table of Contents

* [Introduction](#introduction)
* [Installation](#installation)
    * [Config](#config)
* [A Laravel Project With a Non-Standard Directory Structure](#a-laravel-project-with-a-non-standard-directory-structure)
  * [Update *composer.json*](#update-composerjson)
  * [Update *bootstrap/app.php*](#update-bootstrapappphp)
  * [Update *config/app.php*](#update-configappphp)
* [Directory Structure](#directory-structure)
* [Usage](#usage)
  * [Config Files](#config-files)
  * [Service-Providers](#service-providers)
  * [Routes](#routes)
  * [Command Classes](#command-classes)
  * [Command Closures (console.php)](#command-closures-consolephp)
  * [Broadcast Channels (channels.php)](#broadcast-channels-channelsphp)
  * [View Directories](#view-directories)
  * [View Component Classes](#view-component-classes)
  * [Laravel Livewire Components](#laravel-livewire-components)
  * [Translations](#translations)
  * [Migrations](#migrations)
* [Console Commands](#console-commands)
  * [List](#list)
  * [Save Cache](#save-cache)
  * [Clear Cache](#clear-cache)
  * [Stats](#stats)
* [Testing](#testing)
* [Changelog](#changelog)
  * [SemVer](#semver)
* [Treeware](#treeware)
* [Contributing](#contributing)
  * [Code of Conduct](#code-of-conduct)
  * [Security](#security)
* [Credits](#credits)
* [License](#license)



## Introduction

By default, Laravel is designed to consume your resources from certain places. For example, when you access view `view('homepage')` it's resolved from the `resources/views` directory, config files are loaded automatically from `config/*` and routes are registered from `routes/web.php` and `routes/api.php`.

If you change the structure of your codebase you'll need to tell Laravel where they are. This is actually quite normal in the case of packages that have their own resources.

To do this within *your* own Laravel project you would use the tools [Laravel makes available](https://laravel.com/docs/8.x/packages) for packages to register their package's resources.

The aim of Laravel Auto-Reg is to allow you to structure your Laravel project differently to Laravel's default, without needing to manage resource registration yourself.

Auto-Reg is also a great way to [oversee your resources](#console-commands).

> Laravel Auto-Reg was inspired by the [Laravel Beyond Crud book](https://laravel-beyond-crud.com/) by [Brent Roose](https://twitter.com/brendt_gd) from [Spatie](https://spatie.be/) which describes a Laravel codebase that is broken into *application* and *domain* layers. The "apps" are kind of mini-Laravel applications but are intended to only contain scaffolding code like *Controllers*, *Middleware*, *Requests*, and *Commands*, whose sole purpose is to be the go-between between requests (or commands) and domain business-logic which is stored in the "domains". 

> Further inspiration came from [Laravel Modules](https://github.com/nWidart/laravel-modules) by [Nicolas Widart](https://twitter.com/NicolasWidart) which introduces *modules* and are also like mini Laravel projects. The difference being that a "module" includes *everything* you'd normally find in a Laravel `app` directory - **both** the scaffolding "app" code mentioned above but the business "domain" logic too.



## Installation

Install the package via composer:

``` bash
composer require code-distortion/laravel-auto-reg
```

The package will automatically register itself.



### Config

Publish the `config/code_distortion.laravel_auto_reg.php` config file:

``` bash
php artisan vendor:publish --provider="CodeDistortion\LaravelAutoReg\LaravelAutoRegServiceProvider" --tag="config"
```

And then update the `source_dir` config value to point to the base of your code. `base_path('src/App')`.



## A Laravel Project With a Non-Standard Directory Structure

Here are the steps you could follow to set your Laravel project up with a non-standard directory structure. In this case moving things from `app` into `src/App` and using that as a base to put the rest of your code.



### Update *composer.json*

For your project to recognise files inside the `src/App` directory instead of the usual `app` directory, you'll need to tell Laravel.

You can move the existing `App` namespace to a different directory, as well as add *new namespaces* by updating `composer.json`. The below example moves the `App` namespace into `src/App` and adds a new `Domain` namespace housed within `src/Domain`:

```
// composer.json

{
  // …
  "autoload" : {
    "psr-4" : {
      "App\\" : "src/App/",
      "Domain\\" : "src/Domain/"
    }
  }
}
```



### Update *bootstrap/app.php*

When moving Laravel's `App` namespace (like above), you'll also need to update `bootstrap/app.php` so Laravel uses it when starting up.

``` php
// bootstrap/app.php

// Original
$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

// Replace with
$app = (new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
))->useAppPath(realpath(__DIR__.'/../src/App'));
```

> Laravel Beyond CRUD suggests using `->useAppPath('src/App');` but in my experience this isn't compatible with [Laravel Livewire](https://laravel-livewire.com/).

Laravel uses its `HTTP Kernel`, `Console Kernel`, and `Exceptions Handler` classes to handle web requests, console commands and exceptions respectively. These need to exist but you can move them by updating `bootstrap/app.php`:

``` php
// bootstrap/app.php

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class // <--- update to the new location
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class // <--- update to the new location
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class // <--- update to the new location
);
```



### Update *config/app.php*

Auto-Reg will load your Application Service Providers for you, so you'll need to turn them off in `config/app.php`.

``` php
// config/app.php

    'providers' => [
    
        // …
    
        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,          // <--- comment these out
        App\Providers\AuthServiceProvider::class,         // <--- comment these out
        // App\Providers\BroadcastServiceProvider::class, // <--- comment these out
        App\Providers\EventServiceProvider::class,        // <--- comment these out
        App\Providers\RouteServiceProvider::class,        // <--- comment these out
    ],
```



## Directory Structure

***Note:*** From here on an ***app*** will refer to a group of related files in a structure similar the one below. Multiple *apps* will exist in your project.

An "app" worth of files may look something like this. It's up to you what you'd like to include or exclude:

```
src/App/Home/
├─┬ Commands
│ └── NotifyPostSubscribers.php
├─┬ Configs
│ └── blog.php
├─┬ Controllers
│ └── BlogController.php
├─┬ Migrations
│ └── 2020_12_12_000000_create_blog_posts_table.php
├─┬ Requests
│ └── SubmitPostRequest.php
├─┬ Routes
│ ├── api.php
│ ├── channels.php
│ ├── console.php
│ └── web.php
├─┬ ServiceProviders
│ └── EventServiceProvider.php
└─┬ Resources
  ├─┬ Lang
  │ └─┬ en
  │   ├── general.php
  │   └── home.php
  ├─┬ Views
  │ ├─┬ components
  │ │ ├── comment.php
  │ │ └── post.php
  │ └── view-posts.php
  └─┬ ViewComponents
    └── Post.php
```

Auto-Reg isn't very concerned with how you structure code *inside* your apps, you can choose what you'd like to put in them.

You can group *apps* together by grouping them into sub-directories like the example below. It's up to you how granular you'd like to make your project.

A blog website designed to let users register, make + view posts and write comments, as well as have an admin area for admins to manage its users might look something like this:

```
src/App/Admin/Auth/…
├── Commands
├─┬ Configs
│ ├── admin.php
…
src/App/Admin/Dashboard/…
src/App/Admin/UserManagement/…
src/App/Front/ContactUs/…
src/App/Front/Home/…
src/App/Front/Posts/…
src/App/Front/Registration/…
```

Provided you have config `source_dir` value set to `base_path('src/App')`, Auto-Reg will detect these *apps* from directory structure:

```
admin.auth
admin.dashboard
admin.user-management
front.contact
front.home
front.posts
front.registration
```



## Usage

First, add the location of your apps to `source_dir` in the [config file](#config). This is the base directory where Auto-Reg will look for files.

> You can specify multiple source directories by adding an array instead of a string.

> Laravel Auto-Reg finds your resources by matching your directory structure to patterns. You can change these patterns in the [config file](#config).

Auto-Reg uses a search-pattern for each type of file. Below are the different things that can be registered.

> The `**` in a search pattern represents zero or more wildcard directories.



### Config Files

> Search-pattern: `Configs/**/*.php`

Config files are added to Laravel's config, with the addition of the *app's* name being added as a prefix.

The `src/App/Home/Configs/blog.php` file's values would be available to you using `config('home.blog.my_value')`.

> You can turn the app-prefix off and instead access values using `config('blog.my_value')`  



### Service-Providers

> Search-pattern: `Providers/**/*.php`

Service-Provider classes (that extend `Illuminate\Support\ServiceProvider`) are picked up and registered. You won't need to add them to your `configs/app.php`.



### Routes

> Api-routes search-pattern: `Routes/api.php`<br/>
> Web-routes search-pattern: `Routes/web.php`

Route files are normally registered via a RouteServiceProvider class. Whilst you can still do this if you'd like, Auto-Reg registers your `api.php` and `web.php` route files for you (along with the "api" and "web" middleware respectively).

You can customise which middleware is added.



### Command Classes

> Command-class search-pattern: `Commands/**/*.php`

Commands classes (that extend `Illuminate\Console\Command`) are picked up and registered. You won't need to add them to your `app/Console/Kernel.php`.



### Command Closures (console.php)

> Command-closure file search-pattern: `Routes/console.php`

Commands can also be registered via a `console.php` file, similar to the way routes are registered. Auto-Reg finds these files and registers them for you as well.



### Broadcast Channels (channels.php)

> Search-pattern: `Routes/channels.php`

Broadcast channels are registered via a `channels.php` file, similar to the way routes are registered.

Broadcasting isn't always needed in a Laravel project and initialising it is relatively slow. These might be the reason why the `BroadcastServiceProvider` is disabled by default in `config/app.php` in a normal Laravel project.

The broadcast type is also disabled by default in Laravel Auto-Reg, but you can turn it on again via the config file.



### View Directories

> Search-pattern: `Resources/Views/**/*.php`<br/>
> If files are found, the `Resources/Views` directory will be registered

Blade directories are registered, with the addition of the *app's* name being added as a prefix.

e.g. The `src/App/Home/Resources/Views/blog.php` file would be available to you using `view('home::blog')`.

Anonymous Blade components are also available if you put them in the `components` directory.

e.g. The `src/App/Home/Resources/Views/components/button.php` file would be available to you using `<x-home::button />`.

***Note:*** If you find that a blade template isn't picked up by another one, you may need to re-save the parent template file to trigger a change.



### View Component Classes

> Search-pattern: `Resources/ViewComponents/**/*.php`

Commands classes (that extend `Illuminate\View\Component`) are picked up and registered.

Like view directories above, the *app's* name is added as a prefix.

e.g. The `src/App/Home/Resources/ViewComponents/Button.php` file would be available to you using `<x-home::button />`.

***Note:*** If you find that a blade template isn't picked up by another one, you may need to re-save the parent template file to trigger a change.



### Laravel Livewire Components

> Search-pattern: `Resources/Livewire/**/*.php`

If you use [Laravel Livewire](https://laravel-livewire.com/), your Livewire components are also registered similarly to View Component classes.

The *app's* name is added as a prefix.

e.g. The `src/App/Home/Resources/Livewire/button.php` file would be available to you using `<livewire:home::button />`.



### Translations

> Search-pattern: `Resources/Lang/**/*.php`<br/>
> If files are found, the `Resources/Lang` directory will be registered

Translation directories are registered, with the addition of the *app's* name being added as a prefix.

e.g. The `src/App/Home/Resources/Lang/en/blog.php` file would be available to you using `__('home::blog.success')`.



### Migrations

> Search-pattern: `Database/Migrations/*.php`<br/>
> If files are found, the `Database/Migrations` directory will be registered



## Console Commands

### List

`php artisan auto-reg:list`

This lists the resources that are registered.

> You can narrow down the results by:
>
> - Passing in a specific app. e.g. `php artisan auto-reg:list --app=home`
> - Passing in a specific file type. e.g. `php artisan auto-reg:list --type=config`

> You can also specify how you'd like the results to be grouped. e.g. `php artisan auto-reg:list --group-by=type`



### Save Cache

`php artisan auto-reg:cache`

Auto-Reg looks through your filesystem to find the files to register, and this can take a little time. This might not be noticeable in a development environment but it's recommended that you run this as part of your deployment process, the same way that you would run `php artisan config:cache` and `php artisan route:cache`.

Auto-Reg will cache the list of resource files it detected so it won't need to look for them again.

> Please note that when cached, additions or removals of resource files won't be detected until you clear the cache.



### Clear Cache

`php artisan auto-reg:clear`

This will clear Auto-Reg's cache, the same way that `php artisan config:clear` and `php artisan route:clear` clear the config and route caches.



### Stats

`php artisan auto-reg:stats`

This lists how long the registation steps take and how many things are registered.

> You can reduce the time taken by:
>
> - Caching the list of files that Auto-Reg detects `php artisan auto-reg:cache` (described [above](#save-cache))
> - Caching Laravel's config using `php artisan config:cache`
> - Caching Laravel's routes using `php artisan route:cache`

> The *broadcast* type is relatively slow to register. If you don't need you should turn it off (Laravel disables it by default in a fresh project).



## Testing

``` bash
composer test
```



## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.



### SemVer

This library uses [SemVer 2.0.0](https://semver.org/) versioning. This means that changes to `X` indicate a breaking change: `0.0.X`, `0.X.y`, `X.y.z`. When this library changes to version 1.0.0, 2.0.0 and so forth it doesn't indicate that it's necessarily a notable release, it simply indicates that the changes were breaking.



## Treeware

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/code-distortion/laravel-auto-reg) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.



## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.



### Code of Conduct

Please see [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.



### Security

If you discover any security related issues, please email tim@code-distortion.net instead of using the issue tracker.



## Credits

- [Tim Chandler](https://github.com/code-distortion)



## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
