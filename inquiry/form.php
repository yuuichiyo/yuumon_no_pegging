<?php

# 送信先アドレス
$mailto = "yuuichiyo@gmail.com";
# 送信後画面からの戻り先
$toppage = "./inquiry.html";

#===========================================================
#  入力情報の受け取りと加工
#===========================================================
$name = $_POST["name"];
$email = $_POST["email"];
$comment = $_POST["comment"];

# 無効化
$name  = htmlentities($name,ENT_QUOTES, "UTF-8");
$email = htmlentities($email,ENT_QUOTES, "UTF-8");
$comment = htmlentities($comment,ENT_QUOTES, "UTF-8");

# 改行処理
$name = str_replace("\r\n", "", $name);
$email = str_replace("\r\n", "", $email);
$comment = str_replace("\r\n", "\t", $comment);
$comment = str_replace("\r", "\t", $comment);
$comment = str_replace("\n", "\t", $comment);

# 入力チェック
if ($name == "") { error("名前が未入力です"); }
if (!preg_match("/\w+@\w+/",$email)){ error("メールアドレスが不正です"); }
if ($comment == "") { error("コメントが未入力です"); }

# 分岐チェック
if ($_POST["mode"] == "post") { conf_form(); }
else if($_POST["mode"] == "send") { send_form(); }

#-----------------------------------------------------------
#  確認画面
#-----------------------------------------------------------
function conf_form(){
	global $name;
	global $email;
	global $comment;

	# テンプレート読み込み
	$conf = fopen("tmpl/conf.tmpl","r") or die;
	$size = filesize("tmpl/conf.tmpl");
	$data = fread($conf , $size);
	fclose($conf);

	# 文字置き換え
	$data = str_replace("!name!", $name, $data);
	$data = str_replace("!email!", $email, $data);
	$data = str_replace("!comment!", $comment, $data);

	# 表示
	echo $data;
	exit;
}

#-----------------------------------------------------------
#  エラー画面
#-----------------------------------------------------------
function error($msg){
	$error = fopen("tmpl/error.tmpl","r");
	$size = filesize("tmpl/error.tmpl");
	$data =  fread($error , $size);
	fclose($error);

	#文字置き換え
	$data = str_replace("!message!", $msg, $data);

	#表示
	echo $data;
	exit;
}

#-----------------------------------------------------------
#  CSV書込
#-----------------------------------------------------------
function send_form(){
	global $name;
	global $email;
	global $comment;

	$user_input = array($name,$email,$comment);
	mb_convert_variables("SJIS","UTF-8",$user_input);
	$fh = fopen("user.csv","a");
	flock($fh,LOCK_EX);
	fputcsv($fh, $user_input);
	flock($fh,LOCK_UN);
	fclose($fh);

	#メール送信
	send_mail();

	# テンプレート読み込み
	$conf = fopen("tmpl/send.tmpl","r") or die;
	$size = filesize("tmpl/send.tmpl");
	$data = fread($conf , $size);
	fclose($conf);

	#文字置き換え
	global $toppage;
	$data = str_replace("!top!", $toppage, $data);
	#表示
	echo $data;
	exit;
}

#-----------------------------------------------------------
#  メール送信
#-----------------------------------------------------------
function send_mail(){
	# 時間とIPアドレスの取得
	$date = date("Y/m/d H:i:s");
	$ip = getenv("REMOTE_ADDR");

	global $name;
	global $email;
	global $comment;

	# 本文
	$body = <<< _FORM_
	フォームメールより、次のとおり連絡がありました。

	日時 ： $date
	IP情報 ： $ip
	名前 ： $name
	メールアドレス ： $email
	コメント ： $comment
_FORM_;

	# 送信
	global $mailto;
	mb_language("japanese");
	mb_internal_encoding("UTF-8");
	$name_sendonly = "送信専用アドレス";
	$name_sendonly = mb_encode_mimeheader($name_sendonly);
	$mail_sendonly = "yuuichiyo@gmail.com";
	$mailfrom = "From:".$name_sendonly."<".$mail_sendonly.">";
	$subject = "フォームから連絡がありました";
	mb_send_mail($mailto,$subject,$body,$mailfrom);
}
