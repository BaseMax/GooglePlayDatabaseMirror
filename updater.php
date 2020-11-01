<?php
/*
 * @Author: Max Base
 * @Repository: https://github.com/BaseMax/GooglePlayDatabaseMirror/new/main
 * @Date: 2020-10-29
*/
require "google-play.php";
// require "NetPHP.php";
require "phpedb.php";

ini_set('max_execution_time', 0);
set_time_limit(0);
echo "The time is " . date("h:i:sa");
// use \Datetime;

$db=new database();
$db->connect("localhost", "yefilm_site", '4f9S0c3VoCani8RL4f9S0c3VoCani8RL');
$db->db="yefilm_site";
$db->create_database($db->db, false);

class Film2Serial {

	// Why as a function? Because maybe i want to set a interface and extends...
	public function linkHome() {
		return "https://www.film2serial.ir/";
	}

	public function parsePage($page=1, $input=null) {
		if($input == null) {
    	   // print $this->linkHome()."page/" . $page."\n";
			$input=get($this->linkHome()."page/" . $page)[0];
		}
		preg_match_all('/<h3><span class=\"icon\"><\/span>(\s*|)<a href=\"(?<value>[^\"]+)\" rel=\"bookmark\"/i', $input, $links);
		if(isset($links["value"])) {
			$links=$links["value"];
			return $links;
		}
		return [];
	}
    
    function alt($text) {
        return trim(html_entity_decode(preg_replace('/([\-]{2,})/i', '-', preg_replace('/([\s\t\n]+)/i', '-', trim(strip_tags($text))))));
    }
    
	public function parsePost($link, $input=null) {
		global $db;
		if($input == null) {
			$input=get($link)[0];
		}
		preg_match('/>(\s*|)(?<value>[^\<]+)<\/a><\/h1>(\s*|)<div class="leftbox">/si', $input, $title);
		if(isset($title["value"])) {
			$title=$title["value"];
		}
		else {
			return [];
		}
		if($db->count("sld_post", ["title"=>$title]) >= 1) {
		    return [];
		}
		preg_match('/<div class="rightbox">(\s*|)<ul>(\s*|)<li><span class="icons daste"><\/span>(\s*|)(?<value>.*?)<\/li>/si', $input, $categories);
		if(isset($categories["value"])) {
			$categories=$categories["value"];
			$categories=strip_tags($categories);
			$categories=explode(" , ", $categories);
		}
		else {
			$categories=[];
		}
		preg_match('/<\/div>(\s*|)<div class="contents">(\s*|)(?<value>.*?)<\/div>(\s*|)<\/div>(\s*|)<div class="entry">/si', $input, $context);
		if(isset($context["value"])) {
			$context=$context["value"];
		}
		preg_match('/<meta name="description" content="(?<value>[^\"]+)"/si', $input, $keyword);
		if(isset($keyword["value"])) {
			$keyword=$keyword["value"];
		}
		else {
			$keyword=strip_tags($context);
		}
		$splitContext=preg_split('/<p([^\>]+|)><span id="more-([0-9]+)"><\/span><\/p>/i', $context);
		// print_r($splitContext);
		if(is_array($splitContext) and count($splitContext) >= 2) {
			$shortContext=$splitContext[0];
		}
		else {
		    $shortContext=$context;
		}

		preg_match('/<h2><span class="icon tags"><\/span> برچسب ها<\/h2>(\s*|)<\/div>(\s*|)<div class="contact">(\s*|)<h3>(?<value>.*?)<\/h3>(\s*|)<\/div>(\s*|)<\/div>/is', $input, $tag);
        // print_r($tag);
		if(isset($tag["value"])) {
			$tag=$tag["value"];
			preg_match_all('/rel="tag">(?<value>[^\<]+)<\/a>/si', $input, $tags);
            // print_r($tags);
			if(isset($tags["value"])) {
    			$tags=$tags["value"];
    			$tags=array_unique($tags);
    			$tags=array_values($tags);
			}
			else {
			    $tags=[];
			}
		}
		else {
		    $tag="";
		    $tags=[];
		}
// 		print_r($tags);
        $categoryFilter=[
          "انیمیشن"=>"1",
          "ایرانی"=>"2",
          "سریال"=>"3",
          "فیلم"=>"4",
          "اکشن"=>"5",
          "جنایی"=>"6",
          "درام"=>"7",
          "دوبله"=>"8",
          "ترسناک"=>"9",
          "هیجان انگیز"=>"10",
          "خارجی"=>"11",
          "زبان اصلی"=>"12",
          "کمدی"=>"13",
          "مستند"=>"14",
          "دوبله فارسی"=>"15",
          "خانوادگی"=>"16",
          "فانتزی"=>"17",
          "بیوگرافی"=>"18",
          "جنگی"=>"19",
          "رازآلود"=>"20",
          "تاریخی"=>"21",
          "موزیک"=>"22",
          "ورزشی"=>"23",
          "ماجرایی"=>"24",
          "علمی تخیلی"=>"25",
          "وسترن"=>"26",
          "حیات وحش"=>"27",
          "کلاسیک"=>"28",
          "قدیمی دوبله"=>"29",
          "موزیکال"=>"30",
        ];
        $now = new DateTime();
        $data=[
		    "date"=>$now->format('Y-m-d H:i:s'),
		    "xfields"=>"",
			"autor"=>"admin",
			"title"=>$title,
// 			"alt_name"=>preg_replace('/([\s\t\n]+)/i', '', preg_replace('/([-]{2,})/i', '-', $title)),
// 			"alt_name"=>preg_replace('/([\-]{2,})/i', '-', preg_replace('/([\s\t\n]+)/i', '', $title)),
			"alt_name"=>$this->alt($title),
			"short_story"=>$shortContext == null ? "" : $shortContext,
			"descr"=>trim(preg_replace('/([\s\t\n]+)/i', ' ', mb_substr(strip_tags($context), 0, 299, "utf-8"))),
// 			"descr"=>substr_replace(strip_tags($context), "...", 299),
			"full_story"=>$context == null ? "" : $context,
			"category"=>"",
			"keywords"=>$keyword,
			"approve"=>1,
		];
		$clauses=["title"=>$title];
		if($db->count("sld_post", $clauses) == 0) {
    		$postID=$db->insert("sld_post", $data);
		}
		else {
		    $post=$db->select("sld_post", $clauses, "", "id");
		    $db->update("sld_post", $clauses, $data);
		    $postID=$post["id"];
		}
		$_vals=[
		    "eid"=>null,
		    "news_id"=>$postID,
		    "news_read"=>0,
		    "allow_rate"=>1,
		    "rating"=>0,
		    "vote_num"=>0,
		    "votes"=>0,
		    "view_edit"=>0,
		    "disable_index"=>0,
		    "related_ids"=>'',
		    "access"=>'',
		    "editdate"=>0,
		    "editor"=>'',
		    "reason"=>'',
		    "user_id"=>1,
		    "disable_search"=>0,
		    "need_pass"=>0,
		    "allow_rss"=>1,
		    "allow_rss_turbo"=>1,
		    "allow_rss_dzen"=>1,
		];
		$clauses=["news_id"=>$postID];
		if($db->count("sld_post_extras", $clauses) == 0) {
	        $db->insert("sld_post_extras", $_vals);
		}

        // category
// 		print_r($categories);
// 		$_cats=[];
		foreach($categories as $i=>$category) {
		    $category=trim($category);
		    if(isset($categoryFilter[$category])) {
    	        $_cats[]=$categoryFilter[$category];
		    }
		}
// 			$clauses=["name"=>$category];
// 			if($db->count("sld_category", $clauses) == 0) {
// 			    $clauses["keywords"]="";
// 			    $clauses["fulldescr"]="";
// 			    $clauses["alt_name"]=trim($this->alt($clauses["name"]));
// 				$newID=$db->insert("sld_category", $clauses);
// 			}
// 			else {
// 				$find=$db->select("sld_category", $clauses, "", "id");
// 				if($find == null || $find == []) {
// 					unset($categories[$i]);
// 					continue;
// 				}
// 				else {
// 				    $newID=$find["id"];
// 				}
// 			}
// 			$db->delete("sld_post_extras_cats", ["news_id"=>$postID]);
// 	        $extcatID=$db->insert("sld_post_extras_cats", ["news_id"=>$postID, "cat_id"=>$newID]);
// 	        $_cats[]=$extcatID;
// 		}
// 		print "--------\n";
// 		print_r($_cats);
		if($_cats !=[]) {
		    $_cats=implode(",", $_cats);
    		$db->update("sld_post", ["id"=>$postID], ["category"=>$_cats]);
		}
		else {
    		$db->update("sld_post", ["id"=>$postID], ["category"=>""]);
		}
        
        // print_r($tags);
	    $clauses=["news_id"=>$postID];
	    $db->delete("sld_tags", $clauses);
		foreach($tags as $item) {
		    $clauses=["news_id"=>$postID, "tag"=>$item];
		  //  print_r($clauses);
		    if($db->count("sld_tags", $clauses) == 0) {
    		    $db->insert("sld_tags", $clauses);
		    }
		}
		if($tags != []) {
		    $stringTags=implode(",", $tags);
		    print $postID."\n";
		    $db->update("sld_post", ["id"=>$postID], ["tags"=>$stringTags]);
		}

		return $data;
	}

	function countPage($input=null) {
		if($input == null) {
			$input=get("https://www.film2serial.ir/")[0];
		}
		preg_match('/<a class=\"last\" href=\"https:\/\/www.film2serial.ir\/page\/(?<value>[0-9]+)\"/i', $input, $lastPage);
		// print_r($lastPage);
		if(isset($lastPage["value"])) {
			$lastPage=(int)$lastPage["value"];
			return $lastPage;
		}
	}
}
$service=new Film2Serial();

// Testing
// $post=$service->parsePost("https://www.film2serial.ir/1399/08/10/%d8%af%d8%a7%d9%86%d9%84%d9%88%d8%af-%d9%81%d8%b5%d9%84-%d8%a7%d9%88%d9%84-%d8%b3%d8%b1%db%8c%d8%a7%d9%84-dracula-2020-%d8%a8%d8%a7-%d8%af%d9%88%d8%a8%d9%84%d9%87-%d9%81%d8%a7%d8%b1%d8%b3%db%8c.html");
// exit();

$input=get($service->linkHome())[0];
$page=$service->countPage($input);
print "Page: ".$page."\n";
//$page=10;
//$page=1;
// $i=1;
// $i=$page/2;
$i=$page;
// $i=$page-30;
// $i=35;
// $i=180;
// $i=$page-150;
// $i=$page-220;
// $i=$page-250;
// $i=$page-440;
// $i=300;
// $i=400;
// for(;$i<=$page;$i++) {
for(;$i>=1;$i--) {
    if($i == 1) {
    	$links=$service->parsePage($i, $input);
    }
    else {
    	$links=$service->parsePage($i);
    }
// 	print_r($links);
	foreach($links as $link) {
		$post=$service->parsePost($link);
// 		print_r($post);
        print '.';
	}
	print '#';
}
print "\nDone.";
