# Wordpress-syslog log admin events in syslog

This a fork of [Simple History 2](http://wordpress.org/extend/plugins/simple-history/)

# Plugin API

Developers can easily log their own things using a simple API:

```php
<?php
// Add events to the log
SimpleLogger()->info("This is a message sent to the log");

// Add events of different severity
SimpleLogger()->info("User admin edited page 'About our company'");
SimpleLogger()->warning("User 'Jessie' deleted user 'Kim'");
SimpleLogger()->debug("Ok, cron job is running!");

```

You will find more examples in the [examples.php](https://github.com/bonny/WordPress-Simple-History/blob/master/examples/examples.php) file.
