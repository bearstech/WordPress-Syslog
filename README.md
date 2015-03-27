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
define('WP_SYSLOG_CRITICITY_LEVEL', 'info');
```

## Define a facility under wich the actions are logged

Available values are as the php doc, default to LOG_LOCAL0 :

* LOG_AUTH
* LOG_AUTHPRIV
* LOG_CRON
* LOG_DAEMON
* LOG_KERN
* LOG_LOCAL0 ... LOG_LOCAL7
* LOG_LPR
* LOG_MAIL
* LOG_NEWS
* LOG_SYSLOG
* LOG_USER
* LOG_UUCP

To define a facility, add this line in `wp-config.php` :
```
define('WP_SYSLOG_FACILITY', LOG_LOCAL1);
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