<?php

if(!defined('OneYear'))define('OneYear', 31104000);
if(!defined('OneMonth'))define('OneMonth', 2592000);
if(!defined('OneWeek'))define('OneWeek', 604800);
if(!defined('OneDay'))define('OneDay', 86400);
if(!defined('OneHour'))define('OneHour', 3600);
if(!defined('OneMinute'))define('OneMinute', 60);

if(!function_exists('get_domain_core')){
function get_domain_core()
       {
$known_zones=array('ru','ua','in','com','net','kh','org','kharkov','kharkiv','center');
$srv_name=$_SERVER["SERVER_NAME"];
$srv_arr=array_reverse(explode('.',$srv_name));
$arr_for_ret=array();
foreach ($srv_arr as $prt){
    if(in_array($prt,$known_zones))continue;
    $arr_for_ret[]=str_replace('-','_',$prt);
};
if(empty($arr_for_ret)){
	return 'unknown_domain';
} else {
	$prt_for_ret=implode('_',$arr_for_ret);
	if(strlen($prt_for_ret)>20){
		$prt_for_ret=substr($prt_for_ret,0,20);
	};
	return $prt_for_ret;	
};
return false;
       };  // The end of get_domain_core
};

if(!function_exists('is_img_url')){
function is_img_url($susp_img_url)
       {
if(substr($susp_img_url,-4)=='.jpg')return true;
if(substr($susp_img_url,-5)=='.jpeg')return true;
if(substr($susp_img_url,-4)=='.png')return true;
if(substr($susp_img_url,-4)=='.gif')return true;
return false;
       };  // The end of is_img_url
};

if(!function_exists('SecondsToTime')){
function SecondsToTime($seconds, $num_units=3)
       {
$time_descr = array(
            "years" => floor($seconds / OneYear),
            "months" => floor(($seconds%OneYear) / OneMonth),
            "weeks" => floor(($seconds%OneMonth) / OneWeek),
            "days" => floor(($seconds%OneWeek) / OneDay),
            "hours" => floor(($seconds%OneDay) / OneHour),
            "mins" => floor(($seconds%OneHour) / OneMinute),
            "secs" => floor($seconds%OneMinute),
            );

$russian_tax=array(
            "years" => 'лет',
            "months" => 'месяцев',
            "weeks" => 'недель',
            "days" => 'дней',
            "hours" => 'часов',
            "mins" => 'минут',
            "secs" => 'секунд',
            );

$res = "";
$counter = 0;

foreach ($time_descr as $k => $v) {
    if ($v) {
        $res.=$russian_tax[$k].' '.$v;
        $counter++;
        if($counter>=$num_units)
            break;
        elseif($counter)
            $res.=", ";
    };
};
$res=rtrim($res);
$res=rtrim($res,',');
return $res;
       };   // The end of SecondsToTime
};

if(!function_exists('verify_ip')){
function verify_ip($iplist=array())
       {
//           '176.102.32'
if(count($iplist)==0){
	$iplist=array(
           '80.77.*',
           '188.0.*',
           '46.164.*',
           '77.222.*',
           '195.114.*'
    );
};

$addr_parts=explode('.',$_SERVER['REMOTE_ADDR']);
// myvar_dump($addr_parts,'$addr_parts', true);
// myvar_dump($iplist,'$iplist', true);
if(!isset($addr_parts[2]))return false;
list($remote_part0,$remote_part1,$remote_part2)=$addr_parts;

foreach($iplist as $nth_ip) {
    $tmpl_parts=explode('.',$nth_ip);
    if(!isset($tmpl_parts[2]))return false;
    list($tmpl_part0,$tmpl_part1,$tmpl_part2)=$tmpl_parts;
    $log_expr0= ((intval($remote_part0)==intval($tmpl_part0)) || ($tmpl_part0=='*'));
    $log_expr1= ((intval($remote_part1)==intval($tmpl_part1)) || ($tmpl_part1=='*'));
    $log_expr2= ((intval($remote_part2)==intval($tmpl_part2)) || ($tmpl_part2=='*'));
    if( $log_expr0 && $log_expr1 && $log_expr2 )return true;
};
return false;
       }; // The end of verify_ip
};

if(!function_exists('get_dateselect')){
function get_dateselect($time_to_select=1430000000,$this_date_id='auto',
                        $day_lab='Выберите день',
                        $month_lab='Месяц',
                        $year_lab='Год',
                        $hour_lab='Часы',
                        $min_lab='Минуты',
                        $sec_lab='Секунды',
                        $show_time=true)
       {
    //  Процедура выдает HTML-код для выбора даты
//  $time_to_select - время, которое надо отметить в выходном тексте опцией 'selected'

global $full_mon;

if($this_date_id=='auto'){
    $this_date_id=uniqid();
};

$item_before='<div class="date_select_item">'.chr(10).'<label for="';
$item_after='</div>'.chr(10);
$endoflab='</label>'.chr(10);

$input_day=$item_before.'day_choice_'.$this_date_id.'">'.$day_lab.$endoflab.
            '<select name="day_choice_'.$this_date_id.
            '" id="day_choice_'.$this_date_id.'">'.chr(10);
for ($i=1;$i<=31;$i++){

    if (intval(date('j',$time_to_select))==$i){
        $input_day.='<option selected value="'.$i.'">'.$i.'</option>'.chr(10);
    } else {
        $input_day.='<option value="'.$i.'">'.$i.'</option>'.chr(10);
    };

};
$input_day.='</select>'.chr(10).$item_after;
//javascript:
$input_month=$item_before.'month_choice_'.$this_date_id.'">'.$month_lab.$endoflab.
    '<select onchange="adjust_datachoice(\''.$this_date_id.'\')"'.
    ' name="month_choice_'.$this_date_id.'" id="month_choice_'.$this_date_id.'">'.chr(10);
for ($i=1;$i<=12;$i++){
    if (intval(date('n',$time_to_select))==$i){
        $input_month.='<option selected value="'.$i.'">'.$full_mon[$i].'</option>'.chr(10);
    } else {
        $input_month.='<option value="'.$i.'">'.
            $full_mon[$i].'</option>'.chr(10);
    };
};
$input_month.='</select>'.chr(10).$item_after;

$input_year=$item_before.'year_choice_'.$this_date_id.'">'.
            $year_lab.$endoflab.'<select onchange="adjust_datachoice(\''.
            $this_date_id.'\')" name="year_choice_'.$this_date_id.'" id="year_choice_'.
            $this_date_id.'">'.chr(10);
$yearstart=intval(date('Y'))-5;
$yearend=$yearstart+17;
for ($i=$yearstart;$i<=$yearend;$i++){
    if (intval(date('Y',$time_to_select))==$i){
        $input_year.='<option selected value="'.$i.'">'.$i.'</option>'.chr(10);


    } else {
        $input_year.='<option value="'.$i.'">'.$i.'</option>'.chr(10);
    };

};
$input_year.='</select>'.chr(10).$item_after;

$date_select_txt=$input_day.$input_month.$input_year.chr(10);
//    myvar_dump($date_select_txt,'$date_select_txt');


if($show_time){

//    myvar_dump($time_to_select,'$time_to_select');
    $this_hours=intval(date('H',$time_to_select));

    $this_minutes=intval(date('i',$time_to_select));
//    myvar_dump($this_minutes,'$this_minutes__555_');
    $this_seconds=intval(date('s',$time_to_select));
//    myvar_dump($this_seconds,'$this_seconds__666_');

    $date_select_txt.=$item_before.'hours_choice_'.
                $this_date_id.'">'.$hour_lab.$endoflab.
        '<input id="hours_choice_'.$this_date_id.
            '" name="hours_choice_'.$this_date_id.'" type="number"'.
        ' min="0" max="23" value="'.$this_hours.'"> '.chr(10).$item_after.
        $item_before.'mins_choice_'.$this_date_id.'">'.$min_lab.$endoflab.
        '<input id="mins_choice_'.$this_date_id.'" name="mins_choice_'.$this_date_id.'" type="number"'.
        ' min="0" max="59" value="'.$this_minutes.'"> '.chr(10).$item_after.
        $item_before.'secs_choice_'.$this_date_id.'">'.$sec_lab.$endoflab.
        '<input id="secs_choice_'.$this_date_id.'" name="secs_choice_'.$this_date_id.'" type="number"'.
        ' min="0" max="59" value="'.$this_seconds.'">'.chr(10).$item_after;
};
return '<div class="date_selection">'.chr(10).$date_select_txt.'</div>'.chr(10);

//    $bbbbb=htmlspecialchars($input_year);
//    myvar_dump($bbbbb,'$bbbbb',true);
//    $aaaaa=intval(date('o',$time_to_select));
//    myvar_dump($aaaaa,'$aaaaa',true);
//    myvar_dump($i,'$i',true);

       };  // Окончание процедуры get_dateselect
};

if(!function_exists('catch_date_data')){
function catch_date_data($date_id='auto')
       {
// This function scans the $_POST array and returns the first appropriate date value
//$cur_time=intval(time());

if(empty($date_id))return false;
$this_year=false;
$this_mnth=false;
$this_day=false;
$this_hours=false;
$this_mins=false;
$this_secs=false;
foreach($_POST as $nth_key=>$nth_post){

//    $key_substr=substr($nth_key,0,11);
//    $is_equal=substr($nth_key,0,11)=='month_choice_';
//    $is_equal2=$key_substr=='month_choice_';
    if(empty($nth_post))continue;
    if(!is_numeric($nth_post))continue;
    $int_nth_post=intval($nth_post);
    if($date_id=='auto'){
        if(substr($nth_key,0,12)=='year_choice_'){
              if($int_nth_post>1900 || $int_nth_post<3000){
                  $this_year=$int_nth_post;
                  if(!$this_year)$this_year=false;
              };
        } else if(substr($nth_key,0,13)=='month_choice_') {
              if($int_nth_post>0 || $int_nth_post<32){
                   $this_mnth=$int_nth_post;
                   if(!$this_mnth)$this_mnth=false;
              };
        } else if(substr($nth_key,0,11)=='day_choice_') {
              if($int_nth_post>0 || $int_nth_post<32){
                   $this_day=$int_nth_post;
                   if(!$this_day)$this_day=false;
              };
        } else if(substr($nth_key,0,13)=='hours_choice_') {
              if($int_nth_post>=0 || $int_nth_post<24){
                   $this_hours=$int_nth_post;
                   if(!$this_day)$this_day=false;
              };
        } else if(substr($nth_key,0,12)=='mins_choice_') {
              if($int_nth_post>=0 || $int_nth_post<60){
                   $this_mins=$int_nth_post;
                   if(!$this_day)$this_day=false;
              };
        } else if(substr($nth_key,0,11)=='secs_choice_') {
              if($int_nth_post>=0 || $int_nth_post<60){
                   $this_secs=$int_nth_post;
                   if(!$this_day)$this_day=false;
              };
        };
//        myvar_dump($nth_key,'$nth_key',0,1);
//        myvar_dump($key_substr,'$key_substr',0,1);
//        myvar_dump($is_equal,'$is_equal');
//        myvar_dump($is_equal2,'$is_equal2');

    } else {
        $date_id_len=strlen($date_id);

//        myvar_dump($nth_key,'--------------$nth_key');
//        myvar_dump($nth_post,'$nth_post');
//        myvar_dump($date_id_len,'$date_id_len');
//        $rrr1=substr($nth_key,0,11+$date_id_len);
//        $rrr2='mins_choice_'.$date_id;
//        myvar_dump($rrr1,'$rrr1');
//        myvar_dump($rrr2,'$rrr2');

        if(substr($nth_key,0,12+$date_id_len)=='year_choice_'.$date_id){
              if($int_nth_post>1900 || $int_nth_post<3000){
                  $this_year=$int_nth_post;
              };
        } else if(substr($nth_key,0,13+$date_id_len)=='month_choice_'.$date_id) {
              if($int_nth_post>0 || $int_nth_post<32){
                   $this_mnth=$int_nth_post;
              };
        } else if(substr($nth_key,0,11+$date_id_len)=='day_choice_'.$date_id) {
              if($int_nth_post>0 || $int_nth_post<32){
                   $this_day=$int_nth_post;
              };
        } else if(substr($nth_key,0,13+$date_id_len)=='hours_choice_'.$date_id) {
              if($int_nth_post>0 || $int_nth_post<32){
                   $this_hours=$int_nth_post;
              };
        } else if(substr($nth_key,0,12+$date_id_len)=='mins_choice_'.$date_id) {
              if($int_nth_post>0 || $int_nth_post<32){
                   $this_mins=$int_nth_post;
              };
        } else if(substr($nth_key,0,12+$date_id_len)=='secs_choice_'.$date_id) {
              if($int_nth_post>0 || $int_nth_post<32){
                   $this_secs=$int_nth_post;
              };
        };

    };

};
//myvar_dump($date_id,'$date_id');
//myvar_dump($this_hours,'$this_hours');
//myvar_dump($this_mins,'$this_mins');
//myvar_dump($this_secs,'$this_secs');
//myvar_dump($this_year,'$this_year');
//myvar_dump($this_mnth,'$this_mnth');
//myvar_dump($this_day,'$this_day');
if( empty($this_year) || empty($this_mnth) || empty($this_day)) return false;
$this_time = mktime($this_hours, $this_mins, $this_secs, $this_mnth, $this_day, $this_year);
//myvar_dump($this_time ,'$this_time___555__');
if($this_time !== false && $this_time <>-1 ){
    return intval($this_time);
} else {
    return false;
};

       };   // Окончание процедуры catch_date_data
};

if(!function_exists('myvar_dump')){
function myvar_dump( $v,
                     $my_comment=' *** The variable test dump! ***',
                     $html_comment=false,
                     $hex_view=false )
       {
//           $html_comment - Where the dump should be in the html comment mode
if( (!isset($v)) || $v===null ){
  if($html_comment){
     echo '<!-- This variable ('.$my_comment.') does not exist!!! -->'.chr(10);
  } else {
     echo '<p>-- This variable('.$my_comment.') does not exist!!! ---</p>'.chr(10);
  };
  return false;
};
$trace = debug_backtrace();
$vLine = file( __FILE__ );
$varname = '*** The name of this variable is unknown ***';
if(isset($trace[0]['line'])){
    if (isset($vLine[ $trace[0]['line'] - 1 ])) {
        $fLine = $vLine[ $trace[0]['line'] - 1 ];
        preg_match( "#\\$(\w+)#", $fLine, $match );
        if(isset($match[1])){
            $varname=$match[1];
        } else {

            $var_find_success=false;
            foreach($GLOBALS as $var_key_name => $value) {
                if ($value === $v) {
                    $varname=$var_key_name;
                    $var_find_success=true;
                    break;
                };
            };
            if(!$var_find_success) $varname='*** Unknown variable name ***';
        };
    };
};
if($html_comment){
    echo '<!-- '.$my_comment.chr(10).$varname.':'.chr(10);
} else {
    echo '<p class="dump_header"><b>'.$varname.'</b>: '.$my_comment.'</p>'.chr(10);
};

echo ($html_comment?chr(10):'<pre>').chr(10);
var_dump($v);
if ($hex_view) {
    if (is_string($v)){
        if(strlen($v)<60){
            echo '<table class="table_hex"><tbody><tr><td><b>The hex value is</b>:</td>'.chr(10);
            $arr_v = str_split($v);
            foreach ($arr_v as $nth_byte) {
                echo '<td>'.bin2hex($nth_byte).'</td>'.chr(10);
            };
            echo '</tr><tr><td><b>The letters are</b>:&nbsp;</td>';
            foreach ($arr_v as $nth_byte) {
                echo '<td>'.$nth_byte.'</td>'. chr(10);
            };
            echo '</tr></tbody></table>' . chr(10);
        } else {
            echo '<p class="dump_hex"><b>The hex value is</b>: ';
            $arr_v = str_split($v);
            foreach ($arr_v as $nth_byte) {
                echo bin2hex($nth_byte) . ' ';
            };
            echo '</p>' . chr(10);
            echo '<p class="dump_hex"><b>The letters are</b>:&nbsp; ';
            foreach ($arr_v as $nth_byte) {
                echo $nth_byte . '&nbsp; ';
            };
            echo '</p>' . chr(10);
        };

    } else {
        echo '<h3>The hexadecimal view is impossible due to type of debugged variable!<h3>'.chr(10);
    };

};

echo ($html_comment?chr(10).' -->':'</pre>').chr(10);
       }; // The end of procedure myvar_dump
};



if(!function_exists('this_plugin_url')){
function this_plugin_url()
       {

$plugin_url=plugins_url( ' ', __FILE__ ) ;

//$plugin_url=trim($plugin_url);
$plugin_url=trim($plugin_url,'/ ');
$site_url=$_SERVER['SERVER_NAME'];
$str_parts=explode($site_url,$plugin_url);
if(isset($str_parts[1])){
    return $str_parts[1].'/';
} else {
    return $plugin_url;
};
       }; // The end of the function this_plugin_url
};

if(!function_exists('this_theme_url')){
function this_theme_url()
       {

$theme_url=get_template_directory_uri();
$site_url=$_SERVER['SERVER_NAME'];

$str_parts=explode($site_url,$theme_url);

if(isset($str_parts[1])){
    return $str_parts[1].'/';
} else {
    return $theme_url;
};

       }; // The end of the function this_theme_url
};

if(!function_exists('is_this_image_url')){
function is_this_image_url($may_be_url)
       {
$last4=substr($may_be_url,-4);
if($last4=='.jpg')return true;
if($last4=='.png')return true;
$last5=substr($may_be_url,-5);
if($last5=='.jpeg')return true;
return false;
       }; // The end of the is_this_image_url
}

if(!function_exists('clear_input_str')){
function clear_input_str($str)
       {
$str = trim($str);
$str = stripslashes($str);
$str = strip_tags($str);
$str = htmlspecialchars($str);
$str = esc_sql($str);
return $str;
       };   // The end of clear_input_str
}

if(!function_exists('rasolo_set_admin_message_01')){
function rasolo_set_admin_message_01($rasolo_msg_content,
                                    $rasolo_msg_mode='info',
                                    $dismiss=false)
       {
global $rasolo_messages_01;
//           die('rasolo_set_admin_message_01');
if(empty($rasolo_msg_content))return;
if(!isset($rasolo_messages_01))$rasolo_messages_01=array();
if(empty($rasolo_msg_mode))return;
$rasolo_messages_01[]=array(
    'msg_txt'=>$rasolo_msg_content,
    'is_dismiss'=>$dismiss,
    'msg_mode'=>$rasolo_msg_mode
);
       } // The end of rasolo_set_admin_message_01
};

if(!function_exists('rasolo_display_admin_messages_01')){
function rasolo_display_admin_messages_01()
       {
global $rasolo_messages_01;

if(!current_user_can('edit_others_posts') && 
	!current_user_can('view_woocommerce_reports') )return;
$msg_types=array(
    'error'=>'error',
    'warning'=>'warning',
    'success'=>'success',
    'info'=>'info',
);

if(!isset($rasolo_messages_01))$rasolo_messages_01=array();
foreach ($rasolo_messages_01 as $msg_key=>$nth_msg) {

//    myvar_dump($nth_msg,'$nth_msg');
//    die('$nth_msg');
    if(in_array($nth_msg['msg_mode'],array_flip($msg_types))){
        $msg_mode=$nth_msg['msg_mode'];
    } else {
        $msg_mode='info';
    };
    if(empty($nth_msg['msg_txt']))continue;

    ?>
    <div class="notice notice-<?php
        echo $msg_types[$msg_mode].($nth_msg['is_dismiss']?' is-dismissible':'');
        ?>">
        <p><strong><?php echo $nth_msg['msg_txt'] ?></strong></p><?php
        if($nth_msg['is_dismiss']){
          ?><button type="button" class="notice-dismiss">
            <span class="screen-reader-text">Убрать это сообщение.</span>
        </button><?php
        };
        ?>
    </div>
<?php
	unset($rasolo_messages_01[$msg_key]);
};
       }; // The end of rasolo_display_admin_messages
};

if(!function_exists('is_action_exists')){
function is_action_exists($action_in_quest,$callback_func_in_quest=false)
       {
// This function checks whether some action/filter exists
// You can verify the existance of at least one action without respect to callback procedure name
//     Just do not specify the second parameter for this
global $wp_filter;
if(empty($action_in_quest))return false;
if(!isset($wp_filter[$action_in_quest]))return false;
$callback_object=$wp_filter[$action_in_quest];
if(!is_object($callback_object))return false;
$callback_array=$callback_object->callbacks;
if(empty($callback_array))return false;
if($callback_func_in_quest===false)return count($callback_array);
$cleared_priority_callbacks=array();
foreach($callback_array as $nth_arr){
    $cleared_priority_callbacks=array_merge($cleared_priority_callbacks,$nth_arr);
};
if(array_key_exists($callback_func_in_quest,$cleared_priority_callbacks))return true;
return false;
       }; // The end of is_action_exists
};
