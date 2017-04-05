<?php

namespace App\Http\Controllers;

use App\Models\Host;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HostsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    public function index($mode)
    {
        $cache_key = "hosts_".$mode;

        if (!Cache::has($cache_key)) {
            $hosts = new Host;
            if ($mode == "active") {
                $hosts = $hosts->where('active', 1);
            }
            Cache::put($cache_key, $hosts->get(), 5);
        }

        return Cache::get($cache_key);
    }

    public function host($id)
    {
        if (is_numeric($id)) {
            $host = Host::with('history')->find($id);
        } else {
            $host = Host::with('history')->where('key', $id)->first();
        }
        return $host;
    }

    public function map()
    {
        $cache_key = "map_active";

        if (!Cache::has($cache_key)) {
            $points = [];
            $hosts = Host::where('active', 1);
            foreach ($hosts->get() as $host) {
                $info = geoip_record_by_name($host->host);
                if ($info) {
                    $points[] = ['lat' => $info['latitude'], 'lng' => $info['longitude']];
                }
            }
            Cache::put($cache_key, $points, 5);
        }

        return Cache::get($cache_key);
    }
}
