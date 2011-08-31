<?php

/*
 * Written by sharkpp
 * 
 * ・ファイル形式メモ
 *
 * 入力補完機能
 * ・形式
 * -----ここから-----
 * Apple
 * Orange
 * Strawberry
 * -----ここまで-----
 *
 * キーワードヘルプ
 * ・形式
 *   単語[[,単語]...] /// 訳語<改行>
 *   ・キーワードは複数指定可能です（同義語）。半角カンマで区切って下さい
 *   ・'\n'で訳語に改行を入れられます
 *   ・一つの定義(行)は、10キロバイト程度までです
 *   ・一桁目が';'ならコメント行とみなします
 *
 * ・使い方
 *   1.公式(http://www.php.net/download-docs.php)より
 *     Many HTML files をダウンロードして解凍。
 *   2.$base_url に解凍した場所を指定。
 *   3.phpで実行するだけ。
 *
 */

//$base_url = "http://127.0.0.1/html/";
$base_url = ".\\html\\";

// URLの列挙
function enum_links($url, $prefix)
{
	$result = array();
	$base_url = dirname($url) . '/';
	$prefix_ = '"'.$prefix.'",'.strlen($prefix);

	$data = file_get_contents($url);
	if( 0 < preg_match_all('/<a href="([^"]+)">([^<]+)<\/a>/', $data, $m) ) {
		$result = array_filter($m[1]
					, create_function('$v', 'return !strncmp($v,'.$prefix_.');'));
		array_walk($result
			, create_function('&$v', '$v="'.$base_url.'".$v;'));
	}
	return $result;
}

function trim_multiline($text)
{
	return trim(preg_replace("/ +/", " ", str_replace("\r", "", str_replace("\n", "", html_entity_decode(strip_tags($text))))));
}

$kwdhlp = "";
$kwd = "";
$kwd2 = "";

// 公開日・著作権者
$data = file_get_contents($base_url . "index.html");
$pubdate = 0 < preg_match('/class="pubdate">(.+?)<\//', $data, $m)
			? $m[1]
			: '';
$copyright = 0 < preg_match('/<div class="copyright">(.+?)<\/div>/s', $data, $m)
				? trim_multiline(str_replace("&copy;", "(c)", $m[1]))
				: '';

$kwdhlp = <<<EOD
; PHP マニュアル {$pubdate}版より生成
;  {$copyright}

EOD;

// 定義済みのキーワード
$data = file_get_contents($base_url . "reserved.keywords.html");
preg_match_all('/class="(function|link)">(.+?)<\//', $data, $m);
array_walk($m[2]
	, create_function('&$v', '$v=str_replace("(", "", str_replace(")", "", $v));'));
$kwd .= implode("\r\n", $m[2]) . "\r\n";
// 定義済みの定数
$data = file_get_contents($base_url . "reserved.constants.html");
preg_match_all('/class="constant">(.+?)<\//', $data, $m);
$kwd2 .= implode("\r\n", $m[1]) . "\r\n";

// キーワードヘルプ・キーワード生成
$book_urls = enum_links($base_url . "funcref.html", "book");
$func_urls = array();
foreach($book_urls as $url) {
	$func_urls = array_merge($func_urls, enum_links($url, "function"));
}
foreach(array_unique($func_urls) as $url) {
	$data = file_get_contents($url);
	$refname= 0 < preg_match('/class="refname">(.*?)<\//s', $data, $m)
				? trim_multiline(mb_convert_encoding($m[1], 'SJIS', 'UTF-8'))
				: '';
	$syntax	= 0 < preg_match('/<div class="methodsynopsis dc-description">(.*?)<\/div>/s', $data, $m)
				? trim_multiline($m[1])
				: '';
	$desc	= 0 < preg_match('/<title>(.*?)<\/title>/s', $data, $m)
				? trim_multiline(mb_convert_encoding($m[1], 'SJIS', 'UTF-8'))
				: '';
//	if( empty($refname) || empty($syntax) ) {
//		echo $url . "\n";
//	}
	$kwdhlp .= $refname . " /// " . (empty($syntax) ? "" : $syntax . "\\n") . $desc . "\r\n";
	$kwd .= $refname . "\r\n";
}

//var_dump($func_urls);

file_put_contents("php.khp", $kwdhlp);
file_put_contents("php.kwd", $kwd);
file_put_contents("php2.kwd", $kwd2);

?>