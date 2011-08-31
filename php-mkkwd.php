<?php

/*
 * Written by sharkpp
 * 
 * �E�t�@�C���`������
 *
 * ���͕⊮�@�\
 * �E�`��
 * -----��������-----
 * Apple
 * Orange
 * Strawberry
 * -----�����܂�-----
 *
 * �L�[���[�h�w���v
 * �E�`��
 *   �P��[[,�P��]...] /// ���<���s>
 *   �E�L�[���[�h�͕����w��\�ł��i���`��j�B���p�J���}�ŋ�؂��ĉ�����
 *   �E'\n'�Ŗ��ɉ��s�������܂�
 *   �E��̒�`(�s)�́A10�L���o�C�g���x�܂łł�
 *   �E�ꌅ�ڂ�';'�Ȃ�R�����g�s�Ƃ݂Ȃ��܂�
 *
 * �E�g����
 *   1.����(http://www.php.net/download-docs.php)���
 *     Many HTML files ���_�E�����[�h���ĉ𓀁B
 *   2.$base_url �ɉ𓀂����ꏊ���w��B
 *   3.php�Ŏ��s���邾���B
 *
 */

//$base_url = "http://127.0.0.1/html/";
$base_url = ".\\html\\";

// URL�̗�
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

// ���J���E���쌠��
$data = file_get_contents($base_url . "index.html");
$pubdate = 0 < preg_match('/class="pubdate">(.+?)<\//', $data, $m)
			? $m[1]
			: '';
$copyright = 0 < preg_match('/<div class="copyright">(.+?)<\/div>/s', $data, $m)
				? trim_multiline(str_replace("&copy;", "(c)", $m[1]))
				: '';

$kwdhlp = <<<EOD
; PHP �}�j���A�� {$pubdate}�ł�萶��
;  {$copyright}

EOD;

// ��`�ς݂̃L�[���[�h
$data = file_get_contents($base_url . "reserved.keywords.html");
preg_match_all('/class="(function|link)">(.+?)<\//', $data, $m);
array_walk($m[2]
	, create_function('&$v', '$v=str_replace("(", "", str_replace(")", "", $v));'));
$kwd .= implode("\r\n", $m[2]) . "\r\n";
// ��`�ς݂̒萔
$data = file_get_contents($base_url . "reserved.constants.html");
preg_match_all('/class="constant">(.+?)<\//', $data, $m);
$kwd2 .= implode("\r\n", $m[1]) . "\r\n";

// �L�[���[�h�w���v�E�L�[���[�h����
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