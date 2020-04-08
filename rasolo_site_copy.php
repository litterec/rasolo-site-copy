<?php
/*
Plugin Name: RaSolo site copy
Plugin URI: http://ra-solo.ru
Description: Плагин копирует данные сайта и выкладывает ссылки пользователю для бекапирования
Version: 1.1
Author: Andrew Galagan
Author URI: http://galagan.ra-solo.ru
License: GPL2
*/

/*  Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : eastern@ukr.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class RasoloSiteCopy{
	static private $RASOLO_COPIES_OPTIONS = 'rasolo_copies_option_name';
	static private $ALLOW_SIMPLE_USER_KEY = 'allow_simple_users';
	static private $CREATE_POST_KEY = 'create_site_copy';
	static private $DELETE_POST_KEY = 'delete_site_copy';
	static private $COPIES_LOCAL_PATH = '/wp-content/uploads/user_copies';

	private $copies_full_path;
	private $copies_local_path;
	private $copy_files_name;
	private $copy_mysql_name;
	private $copy_mysql_sql;
	private $data_file;
	private $files_full_name;
	private $hosting_provider;
	private $is_copy_files;
	private $is_copy_mysql;
	private $mysql_full_file;
	private $mysql_full_name;
	private $mysql_full_path;
	private $mysql_server;
	private $mysql_sql_full_path;
	private $copy_files_size;
	private $options;

	
	function __construct(){

	
        $this->options= array();
        $this->options[self::$ALLOW_SIMPLE_USER_KEY]= false;
		
		$rasolo_admin_options_data=get_option(self::$RASOLO_COPIES_OPTIONS);
		$rasolo_arr_options=@unserialize($rasolo_admin_options_data);
		if(is_array($rasolo_arr_options)){
			
			$required_options=array();
			$required_options[self::$ALLOW_SIMPLE_USER_KEY]=false;

			foreach($required_options as $optkey=>$optval){
				if(!empty($rasolo_arr_options[$optkey])){
					$this->options[$optkey]=$rasolo_arr_options[$optkey];
				};
			};
			
		};

		if(strpos($_SERVER['DOCUMENT_ROOT'],'httpdocs')!==false){
			$this->hosting_provider='mchost';
			$this->mysql_server='a111834.mysql.mchost.ru';
		} else if(strpos($_SERVER['DOCUMENT_ROOT'],'public_html')!==false){
			$this->hosting_provider='timeweb';
			$this->mysql_server='localhost';
		} else {
			$this->hosting_provider='citynet';
			$this->mysql_server='localhost';
		};
		$this->data_file=get_domain_core().'_files';
		$this->mysql_file=get_domain_core().'_mysql';

		$this->copies_local_path='/wp-content/uploads/user_copies';
		$this->copy_files_name=$this->data_file.'.tar.gz';
		$this->is_copy_files=false;
		$this->copy_mysql_sql=$this->mysql_file.'.sql';
		$this->copy_mysql_name=$this->mysql_file.'.zip';
		$this->is_copy_mysql=false;
		$this->copies_full_path=$_SERVER['DOCUMENT_ROOT'].$this->copies_local_path;
		$this->mysql_full_path=$_SERVER['DOCUMENT_ROOT'].$this->copies_local_path;
		$this->mysql_sql_full_path=$this->mysql_full_path.'/'.$this->copy_mysql_sql;
		$this->mysql_full_file=$this->mysql_full_path.'/'.$this->copy_mysql_name;
		$this->copies_full_file=$this->copies_full_path.'/'.$this->copy_files_name;
		$this->files_full_name=$this->copies_full_path.'/'.$this->copy_files_name;
		$this->mysql_full_name=$this->copies_full_path.'/'.$this->copy_mysql_name;

		$this->refresh_dir_info();

		add_action('admin_notices',array($this,'show_rasolo_logo'),9);
		
	}  // The end of __construct

	
	
	private function refresh_dir_info(){

		if(!file_exists($this->copies_full_path)){
			mkdir($this->copies_full_path);
		};

		$this->is_copy_mysql=false;
		$this->is_copy_files=false;
		
		$local_time=current_time( 'timestamp' );
		$time_diff=intval($local_time)-intval(time());

		$rrr_counter=0;

		if ($handle = opendir($this->copies_full_path)) {
			while (false !== ($nth_file = readdir($handle))) {

				if(substr($nth_file,0,strlen($this->data_file))==$this->data_file){

					$fl_size_b=intval(filesize($this->copies_full_path.'/'.$nth_file));
					if($fl_size_b<9999){
						$fl_size=strval($fl_size_b).' б';
					} else if ($fl_size_b<1024*9999){
						$fl_size=strval(round($fl_size_b/1024,1)).' Кб';
					} else {
						$fl_size=strval(round($fl_size_b/1024/1024,1)).' Мб';
					};

					$this->copy_files_size=$fl_size;
					$this->copy_files_time=$time_diff+
							intval(filemtime($this->copies_full_path.'/'.$nth_file));
					$this->copy_files_date=date('Y-m-d H:i:s',$this->copy_files_time);

					$this->is_copy_files=true;
				};
				if(substr($nth_file,0,strlen($this->data_file))==$this->mysql_file){

					$db_size_b=intval(filesize($this->copies_full_path.'/'.$nth_file));
					if($db_size_b<9999){
						$db_size=strval($db_size_b).' б';
					} else if ($db_size_b<1024*9999){
						$db_size=strval(round($db_size_b/1024,1)).' Кб';
					} else {
						$db_size=strval(round($db_size_b/1024/1024,1)).' Мб';
					};


					$this->copy_mysql_size=$db_size;
					$this->copy_mysql_time=$time_diff+
							intval(filemtime($this->copies_full_path.'/'.$nth_file));
					$this->copy_mysql_date=date('Y-m-d H:i:s',$this->copy_mysql_time);

					$this->is_copy_mysql=true;
				};

			};

			closedir($handle);
			
		};

	}     // The end of refresh_dir_info
	private function is_simple_user_allowed(){
			 return $this->options[self::$ALLOW_SIMPLE_USER_KEY];
	}
	public function process_post_data(){

		if(isset($_POST[self::$DELETE_POST_KEY])){
//		if(isset($_POST[self::$DELETE_POST_KEY]) && !$this->just_deleted){
//		if(isset($_POST[self::$DELETE_POST_KEY]) && !$this->is_session('just_deleted')){
			$was_existed=false;
			if($this->is_copy_files){
				$was_existed=true;
			};
			unlink ($this->files_full_name);

			if($this->is_copy_mysql){
				$was_existed=true;
			};				
			unlink ($this->mysql_full_name);

			
			rasolo_set_admin_message_01('Архив'.
					($was_existed?'ы успешно удалены!':'ов не было :('));
		
		};
	
		if(isset($_POST[self::$ALLOW_SIMPLE_USER_KEY.'_allow'])){
			$this->options[self::$ALLOW_SIMPLE_USER_KEY]=true;
			$this->write_options();
		};

		if(isset($_POST[self::$ALLOW_SIMPLE_USER_KEY.'_deny'])){		
			$this->options[self::$ALLOW_SIMPLE_USER_KEY]=false;
			$this->write_options();
		};
			
			
		if(isset($_POST[self::$CREATE_POST_KEY])){
			$was_existed=false;
			if($this->is_copy_files){
				$was_existed=true;
				unlink ($this->files_full_name);
			};
			if($this->is_copy_mysql){
				$was_existed=true;
				unlink ($this->mysql_full_name);
			};

			$db_password=preg_replace('/\$(.+)/', '\\\$$1', DB_PASSWORD);

			$cmd_arr=array(

				'mysql_create'=>'mysqldump --opt -u'.DB_USER.' -p'.$db_password.' -h'.
						(defined('DB_HOST')?DB_HOST:MysqlServer).
						' '.DB_NAME.' > '.
						$this->mysql_sql_full_path,

				'files_copy'=>'tar -cf '.$this->copies_full_file.' '.
				$_SERVER['DOCUMENT_ROOT'].' --exclude='.$this->copies_full_path
				.'/*.*',

				'mysql_zip'=>'zip -r '.$this->mysql_full_file.' '.
							$this->mysql_sql_full_path,

					);

			foreach ($cmd_arr as $cmd){
				@ob_start();
				system($cmd);
				$cmd_result = @ob_get_contents();
				@ob_end_clean();
			};

			if( file_exists($this->mysql_sql_full_path)){
				unlink($this->mysql_sql_full_path);
			};

			rasolo_set_admin_message_01('Бекап-файлы сайта и базы '.
					'данных успешно '.($was_existed?'обновлены':'созданы'));

		};
		$this->refresh_dir_info();
		
	}  // The end of process_post_data
	
	private function write_options(){
		$my_options_to_write=serialize($this->options);
		update_option(self::$RASOLO_COPIES_OPTIONS,$my_options_to_write);
	} // The end of write_options
	
	private function get_current_capability(){

		$minimum_capability='edit_others_posts';
		if($this->options[self::$ALLOW_SIMPLE_USER_KEY]){
			$minimum_capability='edit_posts';
		};
		return $minimum_capability;
				
	} // The end of get_current_capability

	private function verify_user_access(){
		$min_cpb=$this->get_current_capability();

		if(current_user_can($min_cpb)){
			return true;
		};
		return false;
	} // The end of verify_user_access

/*
		add_menu_page('The Ra-Solo backup control page',
							'Бекап (Ra-Solo)',
							'read',
							 __FILE__,
							'rasolositecopy_options_copies_page','dashicons-download');
*/
  

    public function get_menu_page_arguments()
    {
		
		$crnt_cpblt=$this->get_current_capability();
        return array(
            __( 'The Ra-Solo backup control page', 'rasolo_copy' ),
            __( 'Бекап (Ra-Solo)', 'rasolo_copy' ),
            $crnt_cpblt,
            'rasolo_copies_menu_page',
            array($this, 'admin_options_page'),
            'dashicons-id-alt'
        );
    }

	/* 
		add_options_page('The Ra-Solo options page',
							'Ra-Solo: бекап',
							$cur_cpb,
							 __FILE__,
							'rasolositecopy_options_copies_page');
	
	*/
    public function get_options_page_arguments()
    {
		
		$crnt_cpblt=$this->get_current_capability();
        return array(
            __( 'The Ra-Solo options page', 'rasolo_copy' ),
            __( 'Бекап (Ra-Solo)', 'rasolo_copy' ),
            $crnt_cpblt,
            'rasolo_copies_options_page',
            array($this, 'admin_options_page'),
            'dashicons-id-alt'
        );
    }

	public function admin_options_page(){
		if(!$this->verify_user_access())return;
		if ( ! defined( 'ABSPATH' ) ) {
			exit; // Exit if accessed directly.
		};
		
           ?><div class="wrap">
<h2 class="left_h1">Управление бекапом сайта</h2>
<legend class="space_medium">Состояние файлов бекапа:</legend>


<fieldset class="options" id="rasolo_copy_state">
<table class="form-table" id="backup_copy_data">
    <tr valign="top">
        <th scope="row">
            <label>
 Наименование
            </label>
        </th>
        <td>
            Имя файла
        </td>
        <td>
            Размер
        </td>
        <td>
            Создан
        </td>
        <td>
            Операции
        </td>

    </tr>
    <tr valign="top">
        <th scope="row">
            <label>
 Архив файлов<br>сайта
            </label>
        </th>
        <td>
<?php
		if($this->is_copy_files){
			echo $this->copy_files_name;
		} else {
			echo 'Архив файлов отсутствует';
		};
//$copy_files_date
?>
        </td>
        <td>
<?php
		if($this->is_copy_files){
			echo $this->copy_files_size;
		} else {
			echo 'Бекап-архив файлов сайта не был создан';
		};
//
?>

        </td>
        <td>
<?php
		if($this->is_copy_files){
			echo $this->copy_files_date;
		} else {
			echo 'Бекап-архив файлов не был создан';
		};
//
?>
        </td>
        <td>
            <?php
		if($this->is_copy_files){

    ?> <a href="<?php echo $this->copies_local_path.'/'.$this->copy_files_name;
            ?>">Скачать архив файлов сайта</a>
<?php

		} else {
			echo 'Операции с архивом файлов сейчас невозможны';
		};
//
?>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row">
            <label>Архив базы<br>данных сайта
             </label>
        </th>
        <td>
<?php
		if($this->is_copy_mysql){
			echo $this->copy_mysql_name;
		} else {
			echo 'Бекап-архив БД отсутствует';
		};
?>        </td>
        <td>
<?php
		if($this->is_copy_mysql){
			echo $this->copy_mysql_size;
		} else {
			echo 'Бекап-архив БД сайта не был создан';
		};
//
?>
        </td>
        <td>
<?php
		if($this->is_copy_mysql){
			echo $this->copy_mysql_date;
		} else {
			echo 'Бекап-архив БД не создан';
		};
//
?>
        </td>
        <td>
                        <?php
		if($this->is_copy_mysql){

    ?> <a href="<?php  echo $this->copies_local_path.'/'.$this->copy_mysql_name;
            ?>">Скачать архив БД сайта</a>
<?php

		} else {
			echo 'Операции с архивом БД сейчас невозможны';
		};
//
?>
        </td>

    </tr>
</table>
</fieldset>
<hr>
   <fieldset class="options" id="rasolo_make_copy">
<legend>Рекомендуется после создания свежих бекап-архивов сайта 
 скачать оба файла на локальный диск вашего ПК.</legend>
<legend>
<?php
		if($this->is_copy_files && $this->is_copy_mysql){
  ?><h4>Внимание! После
 нажатия кнопки &laquo;Обновить архивы&raquo; старые файлы архивов
 будут удалены без процедуры подтверждения!</h4>
<?php
		};

		if($this->is_copy_files
        || $this->is_copy_mysql){
        ?><p>Если вы не скачивали текущий бекап, выполните следующие пункты:</p>
<ol>
<li>Скачайте бекап-архив файлов сайта (первая ссылка вверху страницы).</li>
<li>Скачайте бекап-архив базы данных сайта (вторая ссылка вверху страницы).</li>
<li>Сгенерируйте свежие бекап-архивы файлов и базы данных, для чего нажмите на кнопку
    &laquo;Обновить архивы&raquo;.</li>
<li>Скачайте свежий архив файлов сайта (первая обновленная ссылка вверху страницы).</li>
<li>Скачайте свежий архив базы данных сайта 
 (вторая обновленная ссылка вверху страницы).</li>
</ol>
<?php
		};
    ?>
</legend>
<form method="post" class="space_medium">
<div class="rasolo_sitecopy_space">
<input class="button button-primary medium_left_margin rasolo_sitecopy_floatleft"
       name="create_site_copy" type="submit" value="<?php
		if($this->is_copy_files && $this->is_copy_mysql){
			echo 'Обновить архивы';
		} else {
			echo 'Создать архивы';
		};
?>" />
<?php
		if($this->is_copy_files && $this->is_copy_mysql){
?><input class="button button-primary medium_left_margin rasolo_sitecopy_floatright"
       name="delete_site_copy" type="submit" value="Удалить архивы" /></div>
<?php
		};
    ?>

</form>
</fieldset>
</div>
<?php
		if(current_user_can('create_users')){
	?>
<div class="rasolo_sitecopy_double_space">
</div>
<h2 class="left_h1">Управление доступом пользователей к архивам сайта</h2>
<div class="rasolo_sitecopy_space">
<h4>Разрешается ли неадминистративным пользователям управление копиями?</h4>

<?php 

			if($this->is_simple_user_allowed()){
				$msg_wedge='Запретить';
				$name_wedge='_deny';
			} else {
				$msg_wedge='Разрешить';
				$name_wedge='_allow';
			};
			$msg_wedge.=' копирование';
?>

<form method="post" class="space_medium">
<div class="rasolo_sitecopy_space">
<input id="allow_simple_users_input" 
 class="button button-primary medium_left_margin rasolo_sitecopy_floatleft"
 name="allow_simple_users<?php
			echo $name_wedge;	   
	   ?>" type="submit" value="<?php
			echo $msg_wedge;	   
	   ?>" />
</form>
</div>
	<?php
	
		};

	}   // The end of admin_options_page

	public function show_rasolo_logo(){
		?><h3 class="rs_right">&copy; &laquo;Ra-Solo&raquo;</h3>
		<?php
	}
	
}  // The end of class RasoloSiteCopy

require_once(dirname(__FILE__) . '/srv_functions.php');

if(is_admin()){
	add_action('after_setup_theme','rasolo_copies_init');
	
	if(!is_action_exists('admin_notices','rasolo_display_admin_messages_01')){
		add_action('admin_notices', 'rasolo_display_admin_messages_01');
	};
};

function rasolo_copies_init()
		{

global $rasolo_copies_data;
		
$rasolo_copies_data=New RasoloSiteCopy();
$rasolo_copies_data->process_post_data();

		};

add_action('admin_menu', 'rasolo_menu_init');
function rasolo_menu_init()
		{
global $rasolo_copies_data;
call_user_func_array('add_menu_page',
			$rasolo_copies_data->get_menu_page_arguments());

call_user_func_array('add_options_page',
			$rasolo_copies_data->get_options_page_arguments());
	}

if(is_admin()){
    add_action( 'admin_enqueue_scripts', 'add_rasolositecopy_admin_style' );
};
function add_rasolositecopy_admin_style() {
    wp_enqueue_style( 'rasolo-site-copy-admin-style', this_plugin_url().'admin_style.css', false );
};