# Coding assignment for VideoDock

Run 'composer install' to install dependencies

The Monitor class is located at app/Console/Commands/DebatesMonitor.php

## To run the command with Laravel scheduler:

1. Edit crontab (terminal: 'crontab -e')
2. Add following to crontab '* * * * * php {project_folder}/artisan schedule:run >> /dev/null 2>&1'

## To run from system cron

1. Edit crontab (terminal: 'crontab -e')
2. Add following to crontab '0 * * * 1,2,3,4 php {project_folder}/artisan monitor:debates >> /dev/null 2>&1' (only runs on Mon, Tue, Wen, Thu)

## To test

1. cd to project folder
2. terminal: 'php artisan monitor:debates'

