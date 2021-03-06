<?php

namespace App\Jobs;

use App\Facility;

use GuzzleHttp\Client;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateFacility implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $facility_data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($facility_data)
    {
        $this->facility_data = $facility_data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $base = \App\Synch::$base;
        $client = new Client(['base_uri' => $base]);
        $today = date('Y-m-d');

        $response = $client->request('post', 'facility', [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'facility_data' => json_encode($this->facility_data),
                'lab_id' => env('APP_LAB', null),
            ],
        ]);
    }
}
