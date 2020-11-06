<?php
/*
 * @Name:  Google Play Database Mirror
 * @Version: 0.3
 * @Author: Max Base
 * @Released under : https://github.com/BaseMax/GooglePlayDatabaseMirror/blob/master/LICENSE
 * @Repository: https://github.com/BaseMax/GooglePlayDatabaseMirror
 * @Date: 2020-10-29, 2020-11-06
*/

require "src/google-play.php";
require "src/phpedb.php";

$db=new database();
$db->connect("localhost", "phpcrawler", "******##!!!");
$db->db="phpcrawler";
$db->create_database($db->db,false);

function insertApp($app) {
    global $db;
    if($app == []) {
        return;
    }
    $images=$app["images"];
    unset($app["images"]);
    $clauses=["packageName"=>$app["packageName"]];
    print_r($app);
    if($db->count("app", $clauses) == 0) {
        $appID=$db->insert("app", $app);
    }
    else {
        $db->update("app", $clauses, $app);
        $app=$db->select("app", $clauses, "", "id");
        $appID=$app["id"];
    }
    if($images !== []) {
        foreach($images as $image) {
        $clauses=["appID"=>$appID, "image"=>$image];
            if($db->count("screen", $clauses) == 0) {
                $db->insert("screen", $clauses);
            }
        }
    }
}

function insertApps($apps) {
    global $google;
    foreach($apps as $app) {
        $app=$google->parseApplication($app);
        if($app == []) {
            continue;
        }
        insertApp($app);
    }
}

$google = new GooglePlay();

// $app=$google->parseApplication("com.vpn.free.hotspot.secure.vpnify");
// insertApp($app);

// $app=$google->parseApplication("com.bezapps.flowdiademo");
// insertApp($app);

/*
$apps=$google->parseSearch("Taxi");
insertApps($apps);
*/

// $apps=$google->parseCategory("TRAVEL");
// insertApps($apps);

/*
$alphas = range('A', 'Z');
foreach($alphas as $alpha) {
    $apps=$google->parseSearch($alpha);
    insertApps($apps);
    foreach($alphas as $alpha2) {
        $apps=$google->parseSearch($alpha.$alpha2);
        insertApps($apps);
    }
}
*/

$cats = $google->parseCategories();
foreach($cats as $cat) {
    $apps=$google->parseCategory($cat);
    insertApps($apps);
}

$alphas = range('A', 'Z');
foreach($alphas as $alpha) {
  $apps=$google->parseSearch($alpha);
  insertApps($apps);
  foreach($alphas as $alpha2) {
    $apps=$google->parseSearch($alpha.$alpha2);
    insertApps($apps);
    foreach($alphas as $alpha3) {
      $apps=$google->parseSearch($alpha.$alpha2.$alpha3);
      insertApps($apps);
    }
  }
}
