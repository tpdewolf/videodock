<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use File;
use Mail;

class DebatesMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:debates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitors the debates for de Tweede Kamer';

    /**
     * The url to the api
     *
     * @var string
     */
    protected $url = 'https://api-test.livedebatapp.nl/agenda';

    /**
     * The emails of the persons to alert
     *
     * @var array
     */
    protected $emails = ['tpdewolf@gmail.com'];

    /**
     * The errors to report
     *
     * @var array
     */
    protected $errors = [];

    /**
     * The folder where the logs are located
     *
     * @var array
     */
    protected $folder = '/storage/logs/monitor/debate';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        // add basepath to folder
        $this->folder = base_path().$this->folder;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        
        // create log folder if doesnt exist 
        if (!File::exists($this->folder)) {
            File::makeDirectory($this->folder, 0755, true);
        }

        $response = file_get_contents($this->url);

        // check if response is valid JSON
        if ($this->isJSON($response)) {

            $response = json_decode($response);

            // check if debates is set
            if (isset($response->debates)) {

                // check if has any debates
                if ($response->debates) {
                    foreach ($response->debates as $debate) {
                        if (!$this->isToday($debate->startsAt)) {
                            $this->errors[] = "One of the debates wasn't today";
                            break;
                        } 
                    }
                } else {
                    $this->errors[] = "Debates were missing from the API response";
                }
                
            } else {
                $this->errors[] = "Debates were missing from the API response";
            }

        } else {
            $this->errors[] = "One of the debates wasn't today";
        }

        // send an email if there were any errors and log 
        if ($this->errors) {

            // if a logfile for today doesnt exist, send an email
            if (!$this->hasLogFile()) {
                Mail::raw("The following errors happened while monitoring the debates API:"."\n\n".implode("\n\n",$this->errors), function ($message) {

                    $message->from('api@videodock.nl', $name = 'VideoDock API Monitor');
                    foreach ($this->emails as $email) {
                        $message->to($email);
                    }
                    $message->subject("Debate Monitor: Something went terribly wrong");
                });
            }

            File::put($this->folder.'/log_'.date('Ymd_His').'.txt',implode("\n",$this->errors));

        } else {

            // if no errors but log was created, send email that problems were solved
            if ($this->hasLogFile()) {
                Mail::raw("All problems were solved", function ($message) {

                    $message->from('api@videodock.nl', $name = 'VideoDock API Monitor');
                    foreach ($this->emails as $email) {
                        $message->to($email);
                    }
                    $message->subject("Debate Monitor: Problems solved");
                });
            }

        }

    }

    /**
     * Checks if a logfile was created for today
     *
     * @return boolean
     */
    private function hasLogFile() 
    {
        return File::glob($this->folder.'/log_'.date('Ymd').'*.txt');
    }


    /**
     * Checks if a string is valid JSON
     *
     * @return boolean
     */
    private function isJSON($string) 
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    /**
     * Checks if a date is today
     *
     * @return boolean
     */
    private function isToday($date) 
    {
        // compare the date to todays date minus 6 hours since days start and end at 06:00
        return date('Y-m-d') === date('Y-m-d',strtotime($date.' - 6 hours'));
    }
}
