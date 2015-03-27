# Wordpress-syslog log admin events in syslog

This is a fork of [Simple History 2](http://wordpress.org/extend/plugins/simple-history/)

## Define a criticity level to be logged

In your `wp-config.php` file, you can define a level of criticity to log. Every message under this level will be logged. Default : `'warning'`.

Levels are exactly the same as syslog levels :

* emergency
* alert
* critical
* error
* warning
* notice
* info
* debug

To define a criticity level, add this line in `wp-config.php` :
```
define('WP_SYSLOG_FACILITY_LEVEL', 'info');
```

## Actions beeing logged

You can filter logs by domains. They appear between brackets in the logs :

* [Auth]
* [Comments]
* [Core]
* [Export]
* [Media]
* [Menu]
* [Options]
* [Plugin]
* [Post]
* [Theme]

## The log message

A message appears in logs as :
```
[Domain] user (ip address) Action
```
Exemple :
```
[Auth] admin (127.0.0.1) Logged in
```