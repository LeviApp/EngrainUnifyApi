<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Route::get('/', function() {
    if (request()->header('API-Key') !== "7d64ca3869544c469c3e7a586921ba37") {
        return response()->json(["message" => "You do not have the proper credentials. Please try again."], 401);
    }
    else {
    if (request()-> input("per-page")) {
        $pages = request()->input("per-page");
    }
    else {
        $pages = 100;
    }
    if (request()-> page) {
        $current = request() -> page;
    }
    else {
        $current = 1;
    }

    $units = Http::withHeaders([
        'API-Key' => '7d64ca3869544c469c3e7a586921ba37',
    ])->get("https://api.sightmap.com/v1/assets/1273/multifamily/units?per-page={$pages}&page={$current}");
        $pagination = $units["paging"];
        $pagination["total_pages"] = ceil(330/$pages);
        $pagination["total_count"] = 330;
        if ($current > $pagination["total_pages"]) {
            $prev = "per-page=100&page=4";
            $next = "per-page=100&page=1";
            $current = 1;
        }
        else {
            $prev = ($pagination['prev_url'] ? explode('?', $pagination['prev_url'])[1] : "per-page=100&page={$pagination["total_pages"]}");
            $next = ($pagination['next_url'] ? explode('?', $pagination['next_url'])[1] : "per-page=100&page=1");
        }
        $pagination["prev_url"] = ($prev ? "http://127.0.0.1:8000/hello?{$prev}" : null);
        $pagination["next_url"] = ($next ? "http://127.0.0.1:8000/hello?{$next}" : null);
        $area1units = array();
        $areagreaterthan1units = array();

    for ($i = 0; $i < count($units["data"]); $i++) {
        if ($units["data"][$i]["area"] == 1) {
            array_push($area1units, $units["data"][$i]);
        }

        else {
            array_push($areagreaterthan1units, $units["data"][$i]);
        }
    }
    $area1units = array_values($area1units);

    $areamoreunits = array_values($areagreaterthan1units);

    return ["pages" => $pagination, "area1units" => ["total_count" => count($area1units), "data" => $area1units], "areamoreunits" => ["total_count" => count($areamoreunits), "data" => $areamoreunits]];
}
    });