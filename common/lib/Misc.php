<?php
/***************************************************************************
 *
 * Misc.php : PHP A2Billing Functions for A2Billing
 * Written for PHP 4.x & PHP 5.X versions.
 *
 * A2Billing -- Asterisk billing solution.
 * Copyright (C) 2004, 2009 Belaid Arezqui <areski _atl_ gmail com>
 *
 * See http://www.asterisk2billing.org for more information about
 * the A2Billing project.
 * Please submit bug reports, patches, etc to <areski _atl_ gmail com>
 *
 * This software is released under the terms of the GNU Lesser General Public License v2.1
 * A copy of which is available from http://www.gnu.org/copyleft/lesser.html
 *
 ****************************************************************************/



/*
 * a2b_round: specific function to use the same precision everywhere
 */
function a2b_round($number){
	$PRECISION = 6;
	return round($number,$PRECISION);
}

function a2b_encrypt($text, $key)
  {
	$iv_size= mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256 , MCRYPT_MODE_ECB  );
	$iv = mcrypt_create_iv($iv_size);
	$temp = mcrypt_encrypt(MCRYPT_RIJNDAEL_256,md5($key),$text,MCRYPT_MODE_ECB,$iv);
	return $temp;
  } 

function a2b_decrypt($text, $key)
  {
  	$iv_size= mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256 , MCRYPT_MODE_ECB  );
  	$iv = mcrypt_create_iv($iv_size);
    $temp = mcrypt_decrypt(MCRYPT_RIJNDAEL_256,md5($key),$text,MCRYPT_MODE_ECB,$iv);
  	return $temp;
  }


/*
 * a2b_mail - function mail used in a2billing
 */
function a2b_mail ($to, $subject, $mail_content, $from = 'root@localhost', $fromname = '', $contenttype = 'multipart/alternative')
{
	$mail = new phpmailer();
	$mail -> From     = $from;
	$mail -> FromName = $fromname;
	//$mail -> IsSendmail();
	//$mail -> IsSMTP();
	$mail -> Subject = $subject;
	$mail -> Body    = nl2br($mail_content); //$HTML;
	$mail -> AltBody = $mail_content;  // Plain text body (for mail clients that cannot read 	HTML)
	// if ContentType = multipart/alternative -> HTML will be send
	$mail -> ContentType = $contenttype;
	$mail -> AddAddress ($to);
	$mail -> Send();
}


/*
 * get_currencies
 */
function get_currencies($handle = null)
{
	if (empty($handle)){
		$handle = DbConnect();
	}
	$instance_table = new Table();
	$QUERY =  "SELECT id, currency, name, value FROM cc_currencies ORDER BY id";
	$result = $instance_table -> SQLExec ($handle, $QUERY, 1, 300);

	if (is_array($result)){
		$num_cur = count($result);
		for ($i=0;$i<$num_cur;$i++){
			$currencies_list[$result[$i][1]] = array (1 => $result[$i][2], 2 => $result[$i][3]);
		}
	}

	if ((isset($currencies_list)) && (is_array($currencies_list)))	sort_currencies_list($currencies_list);

	return $currencies_list;
}

function getCurrenciesList(){
		$currencies_list = get_currencies();
		foreach($currencies_list as $key => $cur_value) {
			$currency_list[$key]  = array( $cur_value[1].' ('.$cur_value[2].')', $key);
		}
		return $currency_list;
	}


function getCurrenciesKeyList(){
		$currencies_list = get_currencies();
		foreach($currencies_list as $key => $cur_value) {
			$currency_list_key[$key][0] = $key;
		}
		return $currency_list_key;
	}

function getCurrenciesRateList(){
		$currencies_list = get_currencies();
		foreach($currencies_list as $key => $cur_value) {
			$currency_list_r[$key]  = array( $key, $cur_value[1]);
		}
		return $currency_list_r;
	}

/**
* Do Currency Conversion.
* @param $currencies_list the List of currencies.
* @param $amount the amount to be converted.
* @param $from_cur Source Currency
* @param $to_cur Destination Currecny
*/
function convert_currency ($currencies_list, $amount, $from_cur, $to_cur)
{
	if (!is_numeric($amount) || ($amount == 0)) {
		return 0;
	}
	if ($from_cur == $to_cur) {
		return $amount;
	}
	// EUR -> 1.19175 : MAD -> 0.10897
	// FROM -> 2 - TO -> 0.5 =>>>> multiply 4
	$mycur_tobase = $currencies_list[strtoupper($from_cur)][2];
	$mycur = $currencies_list[strtoupper($to_cur)][2];
	if ($mycur == 0) return 0;
	$amount = $amount * ($mycur_tobase / $mycur);
	// echo "\n \n AMOUNT CONVERTED IN NEW CURRENCY $to_cur -> VALUE =".$amount;
	return $amount;
}


/*
 * sort_currencies_list
 */
function sort_currencies_list(&$currencies_list)
{
	$first_array = array (strtoupper(BASE_CURRENCY), 'USD', 'EUR','GBP','AUD','HKD', 'JPY', 'NZD', 'SGD', 'TWD', 'PLN', 'SEK', 'DKK', 'CHF', 'COP', 'MXN', 'CLP');
	foreach ($first_array as $element_first_array){
		if (isset($currencies_list[$element_first_array])){
			$currencies_list2[$element_first_array]=$currencies_list[$element_first_array];
			unset($currencies_list[$element_first_array]);
		}
	}
	$currencies_list = array_merge($currencies_list2,$currencies_list);
}


/*
 * Write log into file
 */
function write_log($logfile, $output)
{
	// echo "<br>$output<br>";
	if (strlen($logfile) > 1){
		$string_log = "[".date("d/m/Y H:i:s")."]:[$output]\n";
		error_log ($string_log."\n", 3, $logfile);
	}
}


/*
 * function sanitize_data
 */
function sanitize_data($data)
{
	$lowerdata = strtolower ($data);
	$data = str_replace('--', '', $data);
	$data = str_replace("'", '', $data);
	$data = str_replace('=', '', $data);
	$data = str_replace(';', '', $data);
	//$lowerdata = str_replace('table', '', $lowerdata);
	//$lowerdata = str_replace(' or ', '', $data);
	if (!(strpos($lowerdata, ' or ')===FALSE)){ return false;}
	if (!(strpos($lowerdata, 'table')===FALSE)){ return false;}
	return $data;
}


/*
 * function getpost_ifset
 */
function getpost_ifset($test_vars)
{
	if (!is_array($test_vars)) {
		$test_vars = array($test_vars);
	}
	foreach($test_vars as $test_var) {
		if (isset($_POST[$test_var])) {
			global $$test_var;
			$$test_var = $_POST[$test_var];
			$$test_var = sanitize_data($$test_var);
			if($test_var =='username' || $test_var =='filterprefix' ){ 
				//rebuild the search parameter to filter character to format card number
				$filtered_char = array(" ", "-", "_","(",")", "+");
				$$test_var = str_replace($filtered_char, "", $$test_var);
				
			}
		} elseif (isset($_GET[$test_var])) {
			global $$test_var;
			$$test_var = $_GET[$test_var];
			$$test_var = sanitize_data($$test_var);
			//rebuild the search parameter to filter character to format card number
			if($test_var =='username' ||  $test_var =='filterprefix'){ 
				//rebuild the search parameter to filter character to format card number
				$filtered_char = array(" ", "-", "_" , "(" , ")" , "/" ,"\\" );
				$$test_var = str_replace($filtered_char, "", $$test_var);
			}
		}
	}
}


/*
 * function display_money
 */
function display_money($value, $currency = BASE_CURRENCY)
{
	echo number_format($value,2,'.',' ').' '.strtoupper($currency);
}


/*
 * function display_dateformat
 */
function display_dateformat($mydate)
{
	if (DB_TYPE == "mysql"){
		if (strlen($mydate)==14){
			// YYYY-MM-DD HH:MM:SS 20300331225242
			echo substr($mydate,0,4).'-'.substr($mydate,4,2).'-'.substr($mydate,6,2);
			echo ' '.substr($mydate,8,2).':'.substr($mydate,10,2).':'.substr($mydate,12,2);
			return;
		}
	}
	echo $mydate;
}

/*
 * function display_dateonly
 */
function display_dateonly($mydate)
{
	if (strlen($mydate) > 0 && $mydate != '0000-00-00') {
		echo date("m/d/Y", strtotime($mydate));
	}
}

/*
 * function res_display_dateformat
 */
function res_display_dateformat($mydate)
{
	if (DB_TYPE == "mysql"){
		if (strlen($mydate)==14){
			// YYYY-MM-DD HH:MM:SS 20300331225242
			$res= substr($mydate,0,4).'-'.substr($mydate,4,2).'-'.substr($mydate,6,2);
			$res.= ' '.substr($mydate,8,2).':'.substr($mydate,10,2).':'.substr($mydate,12,2);
			return $res;
		}
	}
	return $mydate;
}


function res_display_timeformat($mydate)
{
	if (DB_TYPE == "mysql") {
		if (strlen($mydate)==6) {
			// YYYY-MM-DD HH:MM:SS 20300331225242
			$res= substr($mydate,0,4).':'.substr($mydate,4,2).':'.substr($mydate,6,2);
			return $res;
		}
	}
	return $mydate;
}

/*
 * function display_minute
 */
function display_minute($sessiontime)
{
	global $resulttype;
	if ((!isset($resulttype)) || ($resulttype=="min")){
			$minutes = sprintf("%02d",intval($sessiontime/60)).":".sprintf("%02d",intval($sessiontime%60));
	}else{
			$minutes = $sessiontime;
	}
	echo $minutes;
}

function display_2dec($var)
{
	echo number_format($var,2);
}

function display_2dec_percentage($var)
{
	if (isset($var))
	{
		echo number_format($var,2)."%";
	}else
	{
		echo "n/a";
	}
}

function display_percentage($var)
{
    if (isset($var)) {
		printf("%d%%",$var);
    } else {
		echo "n/a";
    }
}


function display_2bill($var, $currency = BASE_CURRENCY)
{
	global $currencies_list, $choose_currency;
	
	if (isset($choose_currency) && strlen($choose_currency)==3) 
		$currency=$choose_currency;
	if ( (!isset($currencies_list)) || (!is_array($currencies_list)) )
		$currencies_list = get_currencies();
	$var = $var / $currencies_list[strtoupper($currency)][2];
	echo number_format($var,3).' '.$currency;
}

function remove_prefix($phonenumber)
{
	if (substr($phonenumber,0,3) == "011") {
		echo substr($phonenumber,3);
		return 1;
	}
	echo $phonenumber;
}

/*
 * function linkonmonitorfile
 */
function linkonmonitorfile($value)
{
	$myfile = $value.".".MONITOR_FORMATFILE;
	$dl_full = MONITOR_PATH."/".$myfile;
	if (!file_exists($dl_full)) {
		return;
	}
	$myfile = base64_encode($myfile);
	echo "<a target=_blank href=\"call-log-customers.php?download=file&file=".$myfile."\">";
	echo '<img src="'.Images_Path.'/stock-mic.png" height="18" /></a>';
}

function linktocustomer($value)
{
	$handle = DbConnect();
	$inst_table = new Table("cc_card", "id");
	$FG_TABLE_CLAUSE = "username = '$value'";
	$list_customer = $inst_table -> Get_list ($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	$id = $list_customer[0][0];
    if($id > 0) {
    	echo "<a href=\"A2B_entity_card.php?form_action=ask-edit&id=$id\">$value</a>";
    } else {
    	echo $value;
    }
}

function linktocustomer_id($id)
{	
	$handle = DbConnect();
	$inst_table = new Table("cc_card", "username");
	$FG_TABLE_CLAUSE = "id = '$id'";
	$list_customer = $inst_table -> Get_list ($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	$value = $list_customer[0][0];
    if($id > 0) {
    	echo "<a href=\"A2B_entity_card.php?form_action=ask-edit&id=$id\">$value</a>";
    } else {
    	echo $value;
    }
}

function infocustomer_id($id)
{	
	$handle = DbConnect();
	$inst_table = new Table("cc_card", "username,firstname,lastname");
	$FG_TABLE_CLAUSE = "id = '$id'";
	$list_customer = $inst_table -> Get_list ($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	if(is_array($list_customer))$value = $list_customer[0][1]." ".$list_customer[0][2]." (".$list_customer[0][0].")";
	else $value="";
    if($id > 0) {
    	echo "<a href=\"A2B_card_info.php?id=$id\">$value</a>";
    } else {
    	echo $value;
    }
}

function nameofadmin($id)
{	
	$handle = DbConnect();
	$inst_table = new Table("cc_ui_authen", "login,name");
	$FG_TABLE_CLAUSE = "id = '$id'";
	$list_admin = $inst_table -> Get_list ($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	if(is_array($list_admin))$value = $list_admin[0][1]." (".$list_admin[0][0].")";
	else $value="";
    echo $value;
}

function nameofcustomer_id($id)
{	
	$handle = DbConnect();
	$inst_table = new Table("cc_card", "username,firstname,lastname");
	$FG_TABLE_CLAUSE = "id = '$id'";
	$list_customer = $inst_table -> Get_list ($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	if(is_array($list_customer))$value = $list_customer[0][1]." ".$list_customer[0][2]." (".$list_customer[0][0].")";
	else $value="";
    echo $value;
}


function linktoagent($id)
{
	$handle = DbConnect();
	$inst_table = new Table("cc_agent", "login,firstname,lastname");
	$FG_TABLE_CLAUSE = "id = '$id'";
	$list_agent = $inst_table -> Get_list ($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	if(is_array($list_agent))$value = $list_agent[0][1]." ".$list_agent[0][2]." (".$list_agent[0][0].")";
	else $value="";
    if($id > 0) {
    	echo "<a href=\"A2B_entity_agent.php?form_action=ask-edit&id=$id\">$value</a>";
    } else {
    	echo $value;
    }
}

function nameofagent($id)
{
	$handle = DbConnect();
	$inst_table = new Table("cc_agent", "login,firstname,lastname");
	$FG_TABLE_CLAUSE = "id = '$id'";
	$list_agent = $inst_table -> Get_list ($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	if(is_array($list_agent))$value = $list_agent[0][1]." ".$list_agent[0][2]." ( login: ".$list_agent[0][0].")";
	else $value="";
    echo $value;
}


/*
 * function MDP_STRING
 */
function MDP_STRING($chrs = LEN_CARDNUMBER)
{
	$pwd = "";
	mt_srand ((double) microtime() * 1000000);
	while (strlen($pwd)<$chrs)
	{
		$chr = chr(mt_rand (0,255));
		if (eregi("^[0-9a-z]$", $chr))
		$pwd = $pwd.$chr;
	};
	return strtolower($pwd);
}

function MDP_NUMERIC($chrs = LEN_CARDNUMBER)
{
	$pwd = "";
	mt_srand ((double) microtime() * 1000000);
	while (strlen($pwd)<$chrs)
	{
		$chr = mt_rand (0,9);
		if (eregi("^[0-9]$", $chr))
		$pwd = $pwd.$chr;
	};
	return strtolower($pwd);
}


function MDP($chrs = LEN_CARDNUMBER)
{
	$pwd = "";
	mt_srand ((double) microtime() * 1000000);
	while (strlen($pwd)<$chrs)
	{
		$chr = chr(mt_rand (0,255));
		if (eregi("^[0-9]$", $chr))
		$pwd = $pwd.$chr;
	};
	return $pwd;
}


function gen_card($table = "cc_card", $len = LEN_CARDNUMBER, $field="username")
{
	$DBHandle_max  = DbConnect();
	for ($k=0;$k<=200;$k++) {
		$card_gen = MDP($len);
		if ($k==200){ echo "ERROR : Impossible to generate a $field not yet used!<br>Perhaps check the LEN_CARDNUMBER (value:".LEN_CARDNUMBER.")";exit();}

		$query = "SELECT ".$field." FROM ".$table." where ".$field."='$card_gen'";
		$resmax = $DBHandle_max -> Execute($query);
		$numrow = 0;
		if ($resmax)
			$numrow = $resmax -> RecordCount( );

		if ($numrow!=0) continue;
		return $card_gen;
	}
}


function gen_card_with_alias($table = "cc_card", $api=0, $length_cardnumber=LEN_CARDNUMBER, $DBHandle = null)
{

	if (!$DBHandle) {
		$DBHandle  = DbConnect();
	}
	
	for ($k=0;$k<=200;$k++){
		$card_gen = MDP($length_cardnumber);
		$alias_gen = MDP(LEN_ALIASNUMBER);
		if ($k==200){
			if ($api){
				global $mail_content, $email_alarm, $logfile;
				mail($email_alarm, "ALARM : API (gen_card_with_alias - CODE_ERROR 8)", $mail_content);
				error_log ("[" . date("Y/m/d G:i:s", mktime()) . "] "."[gen_card_with_alias] - CODE_ERROR 8"."\n", 3, $logfile);
				echo("500 Internal server error");
				exit();
			}else{
				echo "ERROR : Impossible to generate a Cardnumber & Aliasnumber not yet used!<br>Perhaps check the LEN_CARDNUMBER  (value:".LEN_CARDNUMBER.") & LEN_ALIASNUMBER (value:".LEN_ALIASNUMBER.")";
				exit();
			}
		}

		$query = "SELECT username FROM ".$table." where username='$card_gen' OR useralias='$alias_gen'";
		$numrow = 0;
		$resmax = $DBHandle -> Execute($query);
		if ($resmax)
			$numrow = $resmax -> RecordCount( );

		if ($numrow!=0) continue;
		$arr_val [0] = $card_gen;
		$arr_val [1] = $alias_gen;
		return $arr_val;
	}
}

//Get productID and all parameter and retrieve info for card creation into cc_ecommerce_product
function get_productinfo($DBHandle, $instance_table, $productid, $email_alarm, $mail_content, $logfile)
{
	global $FG_DEBUG;
	$QUERY = 'SELECT
				product_name, creationdate, description, expirationdate, enableexpire, expiredays, credit, tariff, id_didgroup, activated, simultaccess, currency,
				typepaid, creditlimit, language, runservice, sip_friend, iax_friend, cc_ecommerce_product.mailtype, fromemail, fromname, subject, messagetext,
				messagehtml
			  FROM cc_ecommerce_product, cc_templatemail
			  WHERE cc_ecommerce_product.mailtype=cc_templatemail.mailtype AND id='.$productid;


	$result = $instance_table -> SQLExec ($DBHandle, $QUERY);
	if ($FG_DEBUG>0){ echo "<br><b>$QUERY</b><br>"; print_r ($result); echo "<hr><br>"; }


	if( !is_array($result)){
		if ($FG_DEBUG > 0) echo ("get_productinfo ERROR");
		mail($email_alarm, "ALARM : API (CODE_ERROR get_productinfo)", $mail_content);
		error_log ("[" . date("Y/m/d G:i:s", mktime()) . "] "."CODE_ERROR get_productinfo"."\n", 3, $logfile);
		echo("500 Internal server error");
		exit();
	}

	return $result[0];

}


// *********************************
//  ONLY USER BY THE OLD FRAME WORK
// *********************************

$lang['strfirst']='&lt;&lt; First';
$lang['strprev']='&lt; Prev';
$lang['strnext']='Next &gt;';
$lang['strlast']='Last &gt;&gt;';

/**
* Do multi-page navigation.  Displays the prev, next and page options.
* @param $page the page currently viewed
* @param $pages the maximum number of pages
* @param $url the url to refer to with the page number inserted
* @param $max_width the number of pages to make available at any one time (default = 20)
*/
function printPages($page, $pages, $url, $max_width = 20)
{
	global $lang;

	$window = 8;

	if ($page < 0 || $page > $pages) return;
	if ($pages < 0) return;
	if ($max_width <= 0) return;

	if ($pages > 1) {
		//echo "<center><p>\n";
		if ($page != 1) {
			$temp = str_replace('%s', 1-1, $url);
			echo "<a class=\"pagenav\" href=\"{$temp}\">{$lang['strfirst']}</a>\n";
			$temp = str_replace('%s', $page - 1-1, $url);
			echo "<a class=\"pagenav\" href=\"{$temp}\">{$lang['strprev']}</a>\n";
		}

		if ($page <= $window) {
			$min_page = 1;
			$max_page = min(2 * $window, $pages);
		}
		elseif ($page > $window && $pages >= $page + $window) {
			$min_page = ($page - $window) + 1;
			$max_page = $page + $window;
		}
		else {
			$min_page = ($page - (2 * $window - ($pages - $page))) + 1;
			$max_page = $pages;
		}

		// Make sure min_page is always at least 1
		// and max_page is never greater than $pages
		$min_page = max($min_page, 1);
		$max_page = min($max_page, $pages);

		for ($i = $min_page; $i <= $max_page; $i++) {
			$temp = str_replace('%s', $i-1, $url);
			if ($i != $page) echo "<a class=\"pagenav\" href=\"{$temp}\">$i</a>\n";
			else echo "$i\n";
		}
		if ($page != $pages) {
			$temp = str_replace('%s', $page + 1-1, $url);
			echo "<a class=\"pagenav\" href=\"{$temp}\">{$lang['strnext']}</a>\n";
			$temp = str_replace('%s', $pages-1, $url);
			echo "<a class=\"pagenav\" href=\"{$temp}\">{$lang['strlast']}</a>\n";
		}
	}
}

/**
* Validate the Uploaded Files.  Return the error string if any.
* @param $the_file the file to validate
* @param $the_file_type the file type
*/
function validate_upload($the_file, $the_file_type)
{
	$registered_types = array(
						"application/x-gzip-compressed"         => ".tar.gz, .tgz",
						"application/x-zip-compressed"          => ".zip",
						"application/x-tar"                     => ".tar",
						"text/plain"                            => ".html, .php, .txt, .inc (etc)",
						"image/bmp"                             => ".bmp, .ico",
						"image/gif"                             => ".gif",
						"image/pjpeg"                           => ".jpg, .jpeg",
						"image/jpeg"                            => ".jpg, .jpeg",
						"image/png"                             => ".png",
						"application/x-shockwave-flash"         => ".swf",
						"application/msword"                    => ".doc",
						"application/vnd.ms-excel"              => ".xls",
						"application/octet-stream"              => ".exe, .fla (etc)",
						"text/x-comma-separated-values"			=> ".csv",
						"text/comma-separated-values"			=> ".csv",
						"text/csv"								=> ".csv"
						); # these are only a few examples, you can find many more!

	$allowed_types = array("text/plain", "text/x-comma-separated-values", "text/comma-separated-values", "text/csv", "application/vnd.ms-excel");


	$start_error = "\n<b>ERROR:</b>\n<ul>";
	$error = "";
	if ($the_file=="") {
		$error .= "\n<li>".gettext("File size is greater than allowed limit.")."\n<ul>";
	} else {
        if ($the_file == "none") {
                $error .= "\n<li>".gettext("You did not upload anything!")."</li>";
        } elseif ($_FILES['the_file']['size'] == 0) {
        	$error .= "\n<li>".gettext("Failed to upload the file, The file you uploaded may not exist on disk.")."!</li>";
        } else {
 			if (!in_array($the_file_type,$allowed_types)) {
 				$error .= "\n<li>".gettext("file type is not allowed").': '.$the_file_type."\n<ul>";
                while ($type = current($allowed_types))
                {
                    $error .= "\n<li>" . $registered_types[$type] . " (" . $type . ")</li>";
                	next($allowed_types);
                }
                $error .= "\n</ul>";
            }
        }
	}
	if ($error) {
		$error = $start_error . $error . "\n</ul>";
        return $error;
    } else {
    	return false;
    }

} # END validate_upload


function securitykey ($key, $data)
{
	// RFC 2104 HMAC implementation for php.
	// Creates an md5 HMAC.
	// Eliminates the need to install mhash to compute a HMAC
	// Hacked by Lance Rushing

	$b = 64; // byte length for md5
	if (strlen($key) > $b) {
		$key = pack("H*",md5($key));
	}
	$key  = str_pad($key, $b, chr(0x00));
	$ipad = str_pad('', $b, chr(0x36));
	$opad = str_pad('', $b, chr(0x5c));
	$k_ipad = $key ^ $ipad ;
	$k_opad = $key ^ $opad;

	return md5($k_opad  . pack("H*",md5($k_ipad . $data)));
}

/*
	Function to show GMT DateTime.
*/

function get_timezones($handle = null)
{
	if (empty($handle)){
		$handle = DbConnect();
	}
	$instance_table = new Table();
	$QUERY =  "SELECT id, gmttime, gmtzone from cc_timezone order by id";
	$result = $instance_table -> SQLExec ($handle, $QUERY);

	if (is_array($result)) {
		$num_cur = count($result);
		for ($i=0;$i<$num_cur;$i++) {
			$timezone_list[$result[$i][0]] = array (1 => $result[$i][1], 2 => $result[$i][2]);
		}
	}

	return $timezone_list;
}


function display_GMT($currDate, $number, $fulldate = 1)
{
	$date_time_array = getdate(strtotime($currDate));
    $hours = $date_time_array['hours'];
    $minutes = $date_time_array['minutes'];
    $seconds = $date_time_array['seconds'];
    $month = $date_time_array['mon'];
    $day = $date_time_array['mday'];
    $year = $date_time_array['year'];
    $timestamp = mktime($hours, $minutes, $seconds, $month, $day, $year);
	
	if ($number < 0) { 
		$timestamp = $timestamp -($number);
	} else {
		$timestamp = $timestamp +($number);
	}

	if($fulldate == 1) {
		$gmdate = gmdate("m/d/Y H:i:s", $timestamp);
	} else {
		$gmdate = gmdate("m/d/Y", $timestamp);
	}
	return $gmdate;
}


function check_translated($id, $languages)
{
	if (empty($handle)){
		$handle = DbConnect();
	}
	$instance_table = new Table();

	$QUERY =  "SELECT id from cc_templatemail where id = $id and id_language = '$languages'";
	$result = $instance_table -> SQLExec ($handle, $QUERY);
	if (is_array($result)){
		if(count($result) > 0)
			return true;
		else
			return false;
	}else{
		return false;
	}

}

function update_translation($id, $languages, $subject, $mailtext)
{
	if (empty($handle)){
		$handle = DbConnect();
	}
	$instance_table = new Table();
	$param_update = "subject = '$subject', messagetext = '$mailtext'";
	$clause = "id = $id and id_language = '$languages'";
	$func_table = 'cc_templatemail';
	$update = $instance_table -> Update_table ($handle, $param_update, $clause, $func_table);
	return $update;
}

function insert_translation($id, $languages, $subject, $mailtext)
{
	if (empty($handle)) {
		$handle = DbConnect();
	}
	$instance_table = new Table();
	$fromemail = '';
	$fromname = '';
	$mailtype = '';
	$QUERY =  "SELECT fromemail,fromname,mailtype from cc_templatemail where id = $id and id_language = 'en'";
	$result = $instance_table -> SQLExec ($handle, $QUERY);
	if (is_array($result)) {
		if(count($result) > 0){
			$fromemail = $result[0][0];
			$fromname = $result[0][1];
			$mailtype = $result[0][2];
		}
	}

	$value = "$id, '$languages', '$subject', '$mailtext', '$mailtype','$fromemail','$fromname'";
	$func_fields = "id,id_language,subject,messagetext,mailtype,fromemail,fromname";
	$func_table = 'cc_templatemail';
	$id_name = "";
	$inserted = $instance_table -> Add_table ($handle, $value, $func_fields, $func_table, $id_name);
	return $inserted;
}

function mailtemplate_latest_id()
{
	if (empty($handle)){
		$handle = DbConnect();
	}
	$instance_table = new Table();

	$QUERY =  "SELECT max(id) as latest_id from cc_templatemail where id_language = 'en'";
	$result = $instance_table -> SQLExec ($handle, $QUERY);
	$result[0][0] = $result[0][0] + 1;
	return $result[0][0];

}

function get_db_languages($handle = null)
{
	if (empty($handle)){
		$handle = DbConnect();
	}
	$instance_table = new Table();
	$QUERY =  "SELECT code, name from cc_iso639 order by code";
	$result = $instance_table -> SQLExec ($handle, $QUERY);

	return $result;
}

/*
 * Function use to archive data and call records
 * Insert in cc_call_archive and cc_card_archive on seletion criteria
 * Delete from cc_call and cc_card
 * Used in
 * 1. A2Billing_UI/Public/A2B_data_archving.php
 * 2. A2Billing_UI/Public/A2B_call_archiving.php
 */

function archive_data($condition, $entity = "")
{
	if(empty($condition)){
		return 0;
		exit;
	}
	$handle = DbConnect();
	$instance_table = new Table();
	if(!empty($entity)) {
		if($entity == "card") {
			$func_fields = "id, creationdate, firstusedate, expirationdate, enableexpire, expiredays, username, useralias, uipass, credit, tariff, id_didgroup, activated, status, lastname, firstname, address, city, state, country, zipcode, phone, email, fax, inuse, simultaccess, currency, lastuse, nbused, typepaid, creditlimit, voipcall, sip_buddy, iax_buddy, language, redial, runservice, nbservice, id_campaign, num_trials_done, vat, servicelastrun, initialbalance, invoiceday, autorefill, loginkey, mac_addr, id_timezone, tag, voicemail_permitted, voicemail_activated, last_notification, email_notification, notify_email, credit_notification, id_group, company_name, company_website, VAT_RN, traffic, traffic_target, discount, restriction";
			$value="SELECT $func_fields FROM cc_card $condition";
			$func_table = 'cc_card_archive';
			$id_name = "";
			$subquery = true;
			$result = $instance_table -> Add_table ($handle, $value, $func_fields, $func_table, $id_name,$subquery);
			$fun_table = "cc_card";
			if (strpos($condition,'WHERE') > 0){
		        $condition = str_replace("WHERE", "", $condition);
			}

			$result = $instance_table -> Delete_table ($handle, $condition, $fun_table);
		} else if($entity == "call") {
			$value = "SELECT id, sessionid,uniqueid,card_id,nasipaddress,starttime,stoptime,sessiontime,calledstation,terminatecause,calledrate,sessionbill,id_tariffgroup,id_tariffplan,id_ratecard,id_trunk,sipiax,src,id_did,buyrate,buycost,id_card_package_offer,real_sessiontime FROM cc_call $condition";
			$func_fields = "id, sessionid,uniqueid,card_id,nasipaddress,starttime,stoptime,sessiontime,calledstation,terminatecause,calledrate,sessionbill,id_tariffgroup,id_tariffplan,id_ratecard,id_trunk,sipiax,src,id_did,buyrate,buycost,id_card_package_offer,real_sessiontime";
			$func_table = 'cc_call_archive';
			$id_name = "";
			$subquery = true;
			$result = $instance_table -> Add_table ($handle, $value, $func_fields, $func_table, $id_name,$subquery);
			if (strpos($condition,'WHERE') > 0){
		        $condition = str_replace("WHERE", "", $condition);
			}
			$fun_table = "cc_call";
			$result = $instance_table -> Delete_table ($handle, $condition, $fun_table);
		}
	}
	return 1;
}

/*
 * Function use to define exact sql statement for
 * different criteria selection
 */
function do_field($sql,$fld,$dbfld)
{
	$fldtype = $fld.'type';
	global $$fld;
	global $$fldtype;
	if ($$fld){
			if (strpos($sql,'WHERE') > 0){
					$sql = "$sql AND ";
			}else{
					$sql = "$sql WHERE ";
			}
			$sql = "$sql $dbfld";
			if (isset ($$fldtype)){
					switch ($$fldtype) {
						case 1:	$sql = "$sql='".$$fld."'";  break;
						case 2: $sql = "$sql LIKE '".$$fld."%'";  break;
						case 3: $sql = "$sql LIKE '%".$$fld."%'";  break;
						case 4: $sql = "$sql LIKE '%".$$fld."'";
					}
			}else{ $sql = "$sql LIKE '%".$$fld."%'"; }
	}
	return $sql;
}


// Update currency exchange rate list from finance.yahoo.com.
// To work around yahoo truncating to 4 decimal places before
// doing a division, leading to >10% errors with weak base_currency,
// we always request in a strong currency and convert ourselves.
// We use ounces of silver,  as if silver ever devalues significantly
// we'll all be pretty much boned anyway,  wouldn't you say?
function currencies_update_yahoo ($DBHandle, $instance_table)
{
	global $FG_DEBUG;
	$strong_currency = 'XAG';
	$url = "http://download.finance.yahoo.com/d/quotes.csv?s=";
	$return = "";

	$QUERY =  "SELECT id,currency,basecurrency FROM cc_currencies ORDER BY id";
	$old_currencies = $instance_table -> SQLExec ($DBHandle, $QUERY);

	// we will retrieve a .CSV file e.g. USD to EUR and USD to CAD with a URL like:
	// http://download.finance.yahoo.com/d/quotes.csv?s=USDEUR=X+USDCAD=X&f=l1
	if (is_array($old_currencies)) {
		$num_cur = count($old_currencies);
		if ($FG_DEBUG >= 1) $return .= basename(__FILE__).' line:'.__LINE__."[CURRENCIES TO UPDATE = $num_cur]\n";
		for ($i = 0; $i < $num_cur; $i++) {
			if ($FG_DEBUG >= 1) $return .= $old_currencies[$i][0].' - '.$old_currencies[$i][1].' - '.$old_currencies[$i][2]."\n";
			// Finish and add termination ?
			if ($i+1 == $num_cur) {
				$url .= $strong_currency.$old_currencies[$i][1]."=X&f=l1";
			} else {
				$url .= $strong_currency.$old_currencies[$i][1]."=X+";
			}

			// Save the index of base_currency when we find it
			if (strcasecmp(BASE_CURRENCY, $old_currencies[$i][1]) == 0) {
				$index_base_currency = $i;
			}
		}

		// Check we found the index of base_currency
		if (!isset($index_base_currency)) {
			return gettext("Can't find our base_currency in cc_currencies.").' '.gettext('Currency update ABORTED.');
		}

		// Call wget to download the URL to the .CVS file
		exec("wget '".$url."' -O /tmp/currencies.cvs  2>&1", $output);
		if ($FG_DEBUG >= 1) $return .= "wget '".$url."' -O /tmp/currencies.cvs\n".$output;

		// get the file with the currencies to update the database
		$currencies = file("/tmp/currencies.cvs");

		// trim off any leading/trailing comments/headers that may have been added
		$i = 0;
		while (!is_numeric(trim($currencies[$i]))) {
			$i++;
		}
		$currencies = array_slice($currencies,$i,$num_cur);

		// do some simple checks to try to verify we've received exactly one
		// valid response for each currency we requested
		$num_res = count($currencies);
		if ($num_res < $num_cur) {
			return gettext("The CSV file doesn't contain all the currencies we requested.").' '.gettext('Currency update ABORTED.');
		}
		for ($i = 0; $i < $num_cur; $i++) {
			if (!is_numeric(trim($currencies[$i]))) {
				return gettext("At least one of the entries in the CSV file isn't a number.").' '.gettext('Currency update ABORTED.');
			}
		}

		// Find base_currency's value in $strong_currency to help avoid Yahoo's
		// early truncation,  and therefore win back a lot of accuracy
		$base_value = $currencies[$index_base_currency];

		// Check our base_currency will still fund our addiction to tea and biscuits
		if (round($base_value,5) < 0.00001) {
			return gettext('Our base_currency seems to be worthless.').' '.gettext('Currency update ABORTED.');
		}

		// update each row we originally retrieved from cc_currencies
		$i = 0;
		foreach ($currencies as $currency){

			$currency = trim($currency);

			if ($currency != 0) {
				$currency = $base_value / $currency;
			}

			//  extremely weak currencies are assigned the smallest value the schema permits
			if (round($currency, 5) < 0.00001) {
				$currency = '0.00001';
			}

			// if the currency is base_currency then set to exactly 1.00000
			if ($i == $index_base_currency) $currency = 1;

			$QUERY = "UPDATE cc_currencies SET value='$currency'";

			// if we've changed base_currency,  update each SQL row to reflect this
			if (BASE_CURRENCY != $old_currencies[$i][2]) {
				$QUERY .= ", basecurrency='".BASE_CURRENCY."'";
			}

			$QUERY .= " , lastupdate = CURRENT_TIMESTAMP WHERE id ='".$old_currencies[$i][0]."'";
			$result = $instance_table -> SQLExec ($DBHandle, $QUERY, 0);
			if ($FG_DEBUG >= 1) $return .= "$QUERY -> [$result]\n";

			$i++;
		}
		$return .= gettext('Success! All currencies are now updated.');
	}
	return $return;
}



/*
 * arguments - function to handle arguments in CLI script
 */
function arguments($argv)
{
    $_ARG = array();
    array_shift($argv); //skip argv[0] !
    foreach ($argv as $arg) {
      if (ereg('--([^=]+)=(.*)',$arg,$reg)) {
        $_ARG[$reg[1]] = $reg[2];
      } elseif(ereg('--([^=]+)',$arg,$reg)){
      	$_ARG[$reg[1]] = true;
      } elseif(ereg('^-([a-zA-Z0-9])',$arg,$reg)) {
            $_ARG[$reg[1]] = true;
      } else {
            $_ARG['input'][]=$arg;
      }
    }
  return $_ARG;
}



function generate_invoice_reference()
{
	$handle = DbConnect();
	$year = date("Y");
	$invoice_conf_table = new Table('cc_invoice_conf','value');
	$conf_clause = "key_val = 'count_$year'";
	$result = $invoice_conf_table -> Get_list($handle, $conf_clause, 0);
	if(is_array($result) && !empty($result[0][0])){
		//update count
		$count =$result[0][0];
		if(!is_numeric($count)) $count=0;
		$count++;
		$param_update_conf = "value ='".$count."'";
		$clause_update_conf = "key_val = 'count_$year'";
		$invoice_conf_table -> Update_table ($handle, $param_update_conf, $clause_update_conf, $func_table = null);
	}else{
		//insert newcount
		$count=1;
		$QUERY= "INSERT INTO cc_invoice_conf (key_val ,value) VALUES ( 'count_$year', '1');";
		$invoice_conf_table -> SQLExec($handle,$QUERY);
	}
	$reference = $year.sprintf("%08d",$count);
	return $reference;
}
