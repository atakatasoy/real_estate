<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ConfigurateJWT extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'configurate:jwt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates/overrides the required JWT settings in the .env file. Be careful with this because once its been overrided, the tokens that are signed with the overrided one will be invalidated therefore all valid session will log of!';

    /**
     * File path to .env file
     * @var string
     */
    private $path;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->path = base_path('.env');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $key = bin2hex(random_bytes(32));

        $envFile = file_get_contents($this->path);
        if(strpos($envFile, "JWT_SECRET=") === false){
            $envFile .= "\r\nJWT_SECRET={$key}";
            $envFile .= "\r\nJWT_EXPIRY_IN_SECONDS=3600";
        }else{
            $endFile = str_replace("JWT_SECRET=".env('JWT_SECRET'), "JWT_SECRET=$key", $envFile);
        }

        return file_put_contents($this->path, $envFile);
    }
}
