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
    // THIS WILL GRAB THE API KEY ENV VARIABLE
    $security = env('APP_SEC');

    // THIS WILL CHECK IF THE API KEY IS BEING SENT IN THE HEADER
    if (request()->header('API-Key') !== $security) {
        return response()->json(["message" => "You do not have the proper credentials. Please try again."], 401);
    }
    else {

    // THIS WILL CHECK IF THERE IS A per-page PARAMETER, OTHERWISE A DEFAULT WILL BE SET
    if (request()-> input("per-page")) {
        $pages = request()->input("per-page");
    }
    else {
        $pages = 100;
    }

    // THIS WILL CHECK IF THERE IS A page PARAMETER, OTHERWISE A DEFAULT WILL BE SET
    if (request()-> page) {
        $current = request() -> page;
    }
    else {
        $current = 1;
    }

    // THIS WILL GRAB DATA FROM SITEMAP API  IF THERE IS A per-page PARAMETER, OTHERWISE A DEFAULT WILL BE SET
    $units = Http::withHeaders([
        'API-Key' => $security,
    ])->get("https://api.sightmap.com/v1/assets/1273/multifamily/units?per-page={$pages}&page={$current}");
        $pagination = $units["paging"];

    // THIS WILL ADD TWO PROPERTIES TO THE PAGES DATA OBJECT
        $pagination["total_pages"] = ceil(330/$pages);
        $pagination["total_count"] = 330;

    // THIS WILL CHECK IF THE CURRENT PAGE PARAMENTER IS OUTSIDE THE TOTAL PAGE COUNT
    // AND WILL RETURN PREVIOUS AND NEXT URLS THAT MATCH THE FIRST AND LAST WORKING URLS
        if ($current > $pagination["total_pages"]) {
            $prev = "per-page=100&page=4";
            $next = "per-page=100&page=1";
            $current = 1;
        }
        else {
            $prev = ($pagination['prev_url'] ? explode('?', $pagination['prev_url'])[1] : "per-page=100&page={$pagination["total_pages"]}");
            $next = ($pagination['next_url'] ? explode('?', $pagination['next_url'])[1] : "per-page=100&page=1");
        }

    // THIS WOULD SET THE PREVIOUS OR NEXT URLS TO NULL IF THE CURRENT PAGE IS THE FIRST OR LAST PAGE
        $pagination["prev_url"] = ($prev ? "https://engrain-unify.herokuapp.com/?{$prev}" : null);
        $pagination["next_url"] = ($next ? "https://engrain-unify.herokuapp.com/?{$next}" : null);
        $area1units = array();
        $areamoreunits = array();

    // THIS WILL CHECK IF THE UNIT HAS AN AREA OF 1 OR NOT
    // TWO DATA GROUPS WILL BE SPLIT OUT FOR THE API 

    for ($i = 0; $i < count($units["data"]); $i++) {
        if ($units["data"][$i]["area"] == 1) {
            array_push($area1units, $units["data"][$i]);
        }

        else {
            array_push($areamoreunits, $units["data"][$i]);
        }
    }

    $area1units = array_values($area1units);
    $areamoreunits = array_values($areamoreunits);

    // THIS WILL RETURN A JSON OBJECT WITH PAGE INFORMATION AND TWO DATA SETS
    // ONE WILL BE UNITS WITH AN AREA OF 1 AND ANOTHER WILL BE UNITS OF AREA GREATER
    // THAN ONE  
    return ["pages" => $pagination, "area1units" => ["total_count" => count($area1units), "data" => $area1units], "areamoreunits" => ["total_count" => count($areamoreunits), "data" => $areamoreunits]];
}
    });