<?php

namespace App\Console\Commands;

use App\Models\Host;
use Illuminate\Console\Command;

class Hosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hosts:fetch {hosts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch hosts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $collect_hosts = $this->argument('hosts');

        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request('GET', env('SIA_ADDRESS').'/hostdb/all');
            $response = json_decode($res->getBody(), true);
            foreach ($response['hosts'] as $host) {
                try {
                    $db_host = Host::firstOrNew(['key' => $host['publickey']['key']]);

                    $new = false;
                    if (empty($db_host->id)) {
                        $new = true;
                    }

                    $db_host->fill($host);
                    $db_host->algorithm = $host['publickey']['algorithm'];
                    $db_host->key = $host['publickey']['key'];

                    $last_scan = end($host['scanhistory']);

                    $hostname = explode(':', $host['netaddress']);
                    $hostname = array_slice($hostname, 0, -1);
                    $hostname = implode(':', $hostname);

                    $db_host->host = (filter_var($hostname, FILTER_VALIDATE_IP)) ? $hostname:gethostbyname($hostname);
                    if ($last_scan['success']) {
                        $db_host->last_seen = strtotime(explode('.', $last_scan['timestamp'])[0]."-04:00");
                        echo "last seen: ";
                        var_dump($last_scan, explode('.', $last_scan['timestamp'])[0]);
                    }
                    $db_host->active = $last_scan['success'];

                    $db_host->save();

                    if (!$collect_hosts || $new) {
                        $db_host->history()->create($host);
                        echo "History added".PHP_EOL;
                    }

                    echo "Saved {$db_host->netaddress}".PHP_EOL;
                } catch (Exception $e) {
                    echo "err host". $e->getMessage();
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
