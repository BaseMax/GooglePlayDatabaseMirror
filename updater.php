<?php
/*
 * @Author: Max Base
 * @Repository: https://github.com/BaseMax/GooglePlayDatabaseMirror/new/main
 * @Date: 2020-10-29
*/
require "google-play.php";
require "phpedb.php";

$db=new database();
$db->connect("localhost", "***", "***");
$db->db="***";
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
