Log Artisan
===========

Access laravel logs through the Artisan Console.

This package is using [devdot/monolog-parser](https://github.com/devdot/monolog-parser) to parse logfiles that were created by Laravel.

## Installation

Add the package to your Laravel application:

```bash
composer require devdot/log-artisan
```

## Basic Usage

Take a look at the last few log entries using the console:

```bash
php artisan log:show
```

Example result:

![log-show](https://user-images.githubusercontent.com/3763567/223183564-a45a6a74-459c-4c5e-8169-e5d780940901.PNG)

Show the results in a compressed single line view using `--singleline` or `-s`:

![log-show-singleline](https://user-images.githubusercontent.com/3763567/223183791-2be7ea2c-2ad9-4cdb-9185-1d3bbebf161f.PNG)

Search through logs using `log:search`, with search terms that can be regex:

![log-search](https://user-images.githubusercontent.com/3763567/223183949-92c79a55-0faa-4b88-9555-cea0fc5a468b.PNG)

Clear all log files like this:

```bash
php artisan log:clear
```

## Documentation

### About Command

View details about the current logging configuration and status:

```bash
php artisan log:about
```

### Show Command

Show entries from the logs (this will merge all logfiles and sort by date).

```bash
php artisan log:show
```

Use these options to narrow the results:

| Option | Name | Description |
|--------|------|-------------|
|-c, --count | Count | Show this amount of entries, default is 10 |
|-l, --level | Log Level | Show only entries with this log level |
|--channel | Log Channel | Use this specified logging channel |
|--short | Short view | Only show short snippets |
|-s, --singleline | Single-line view | Show single-lined layout |
|--stacktrace | Stacktrace view | Show the full stacktrace |

Example (show full logged stacktraces for the latest 100 log entries with level DEBUG):

```bash
php artisan log:show -c100 --level=DEBUG --stacktrace
```

### Search Command

Search through the results with a given search term. The search term is treated as PHP regular expression, so make sure to escape any special characters like `.` or `*`.

```bash
php artisan log:search test
php artisan log:search "(test|regex \w+)"
```

Options are the same as with `log:show`.

### Clear Command

Clear a given logging channel. Use option `--all` to clear all configured channels.

```bash
php artisan log:clear single
php artisan log:clear --all
```

This command will write a new log entry to each cleared file.
