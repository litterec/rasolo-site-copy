<?php
/*
Plugin Name: RaSolo site copy
Plugin URI: https://github.com/litterec/rasolo-site-copy
Description: Плагин копирует данные сайта и выкладывает ссылки пользователю для бекапирования
Version: 1.1
Author: Andrew Galagan
Author URI: http://ra-solo.com.ua
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

define( 'RS_SCOPY_WITH_CLASSES_FILE', __FILE__ );

if ( !class_exists( 'RasoloSiteCopy' ) ) {
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
	private $absent_plugins;
	private static $required_plugins=array(
        'rasolo-admin-messages/rasolo-admin-messages.php'=>'Rasolo Admin Messages',
        'rasolo-helper/rasolo-helper.php'=>'Rasolo Helper',
    );

	
	function __construct(){

        register_activation_hook( RS_SCOPY_WITH_CLASSES_FILE,  [ $this, 'manage_plugin_options' ] );

        $this->absent_plugins=[];
        if( !is_admin() ) {
             return;
        }
        add_action('admin_init', array($this,'this_init' ),90 );

        add_action( 'admin_enqueue_scripts', array($this,'admin_style'),90  );

//        add_action('admin_init', array($this,'check_pugins' ),99 );

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
        if(function_exists('get_domain_core')){
    		$this->data_file=get_domain_core().'_files';
	    	$this->mysql_file=get_domain_core().'_mysql';
        } else {
            $core_name=str_replace('.','_',$_SERVER["SERVER_NAME"]);
    		$this->data_file=$core_name.'_files';
	    	$this->mysql_file=$core_name.'_mysql';
        };

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

    public function manage_plugin_options(){

    }

    public function this_init() {
        load_plugin_textdomain( 'rasolo-site-copy', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );

//        $test= get_option( 'active_plugins', array() ) ;
//        myvar_dd($test,'$test_3523');

        foreach(self::$required_plugins as $nth_key=>$nth_plg){
            if(!is_plugin_active($nth_key)){
                $this->absent_plugins[]=$nth_plg;
            };
        }
        if(!empty($this->absent_plugins)){
            add_action( 'admin_notices', array($this,'absent_plugin_notice') );
        }

    }

	public function absent_plugin_notice(){
        if(count($this->absent_plugins)<1){
            return;
        }
        ?><div class="error"><p><?php
        if(1==count($this->absent_plugins)){
            _e('Sorry, but this plugin is required:','rasolo-site-copy');
            echo ' '.array_shift($this->absent_plugins);

        } else {
            _e('Sorry, but this plugins are required:','rasolo-site-copy');
            echo ' '.implode(', ',$this->absent_plugins);
        }
        ?>.</p></div>
            <?php

    } // The end of absent_plugin_notice

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

        if(class_exists('RasoloAdminMessages')){
            $rasolo_messages=new RasoloAdminMessages;
        };
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


            if(class_exists('RasoloAdminMessages')){
                if($was_existed){

                    $untranslatable_02='ru_RU'<>get_locale()?'Archives have been successfully deleted':'Архивы успешно удалены';

                    $rasolo_messages->set_message($untranslatable_02);

                } else {
                    $rasolo_messages->set_message(__('There were no archives on the server','rasolo-site-copy'));
                }
//                $rasolo_messages->set_message('Архив'.
//					($was_existed?'ы успешно удалены!':'ов не было :('));
            }


//			rasolo_set_admin_message_01('Архив'.
//					($was_existed?'ы успешно удалены!':'ов не было :('));
		
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

            if(class_exists('RasoloAdminMessages')){
                if($was_existed){
                    $rasolo_messages->set_message(__('Both site and database backup files have been successfully updated'));
                }else {
                    $rasolo_messages->set_message(__('Both site and database backup files have been successfully created'));
                }
//                $rasolo_messages->set_message('Бекап-файлы сайта и базы '.
//					'данных успешно '.($was_existed?'обновлены':'созданы'));
            }


//			rasolo_set_admin_message_01('Бекап-файлы сайта и базы '.
//					'данных успешно '.($was_existed?'обновлены':'созданы'));

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
            __( 'The Ra-Solo backup control page','rasolo-site-copy'),
            __( 'The backup files Ra-Solo','rasolo-site-copy'),
            $crnt_cpblt,
            'rasolo_copies_menu_page',
            array($this, 'admin_options_page')
        );
    }

    // , 'dashicons-id-alt'

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
        $untranslatable_01='ru_RU'<>get_locale()?'The Ra-Solo options page':'Страница установок Ra-Solo';
        return array(
            __( $untranslatable_01, 'rasolo-site-copy'),
            __( 'The Ra-Solo backup options', 'rasolo-site-copy' ),
            $crnt_cpblt,
            'rasolo_copies_options_page',
            array($this, 'admin_options_page')
        );
// ,'dashicons-id-alt'

    }

	public function admin_options_page(){
		if(!$this->verify_user_access())return;
		if ( ! defined( 'ABSPATH' ) ) {
			exit; // Exit if accessed directly.
		};
		
           ?><div class="wrap">
<h2 class="left_h1"><?php
    _e( 'Site backup management', 'rasolo-site-copy' );
    ?></h2>
<legend class="space_medium"><?php
    _e( 'Backup files status:', 'rasolo-site-copy' );
    ?></legend>


<fieldset class="options" id="rasolo_copy_state">
<table class="form-table" id="backup_copy_data">
    <tr valign="top">
        <th scope="row">
            <label><?php
    _e( 'Item', 'rasolo-site-copy' );
    ?></label>
        </th>
        <td><?php
    _e( 'File name', 'rasolo-site-copy' );
    ?></td>
        <td><?php
    _e( 'File size', 'rasolo-site-copy' );
    ?>
        </td>
        <td><?php
    _e( 'Created', 'rasolo-site-copy' );
    ?></td>
        <td><?php
    _e( 'Operations', 'rasolo-site-copy' );
    ?></td>

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
			_e('No file archive', 'rasolo-site-copy' );
		};
//$copy_files_date
?>
        </td>
        <td>
<?php
		if($this->is_copy_files){
			echo $this->copy_files_size;
		} else {
			_e('Backup archive of site files has not been created', 'rasolo-site-copy' );
		};
//
?>

        </td>
        <td>
<?php
		if($this->is_copy_files){
			echo $this->copy_files_date;
		} else {
			_e('Backup archive of site files has not been created', 'rasolo-site-copy' );
		};
//
?>
        </td>
        <td>
            <?php
		if($this->is_copy_files){

    ?> <a href="<?php echo $this->copies_local_path.'/'.$this->copy_files_name;
            ?>"><?php
			_e('Download the  site files archive', 'rasolo-site-copy' );
                ?></a>
<?php

		} else {
			_e('File archive operations are not possible', 'rasolo-site-copy' );
		};
//
?>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row">
            <label><?php
                _ex('The site','rasolo_copy_table_db_01', 'rasolo-site-copy');
                ?><br><?php
                _ex('database archive','rasolo_copy_table_db_02', 'rasolo-site-copy');
                ?></label>
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
           _e('The site database archive does not exist','rasolo-site-copy');
		};
//
?>
        </td>
        <td>
                        <?php
		if($this->is_copy_mysql){

    ?> <a href="<?php  echo $this->copies_local_path.'/'.$this->copy_mysql_name;
            ?>"><?php
    _e('Download site database archive','rasolo-site-copy');
    ?></a>
<?php

		} else {
           _e('Operations with the database archive are now impossible','rasolo-site-copy');

		};
//
?>
        </td>

    </tr>
</table>
</fieldset>
<hr>
   <fieldset class="options" id="rasolo_make_copy">
<legend><?php
    _e('We recommend you to download both files to the local drive after creating fresh backup files.','rasolo-site-copy');
    ?></legend>
<legend>
<?php
		if($this->is_copy_files && $this->is_copy_mysql){
  ?><h4><?php
    _e('Attention! After clicking &laquo;Refresh archives&raquo; button the old files will be deleted without confirmation!','rasolo-site-copy');
    ?></h4>
<?php
		};

		if($this->is_copy_files
        || $this->is_copy_mysql){
        ?><p><?php
    _e('If you have not downloaded the current backup please follow these steps:','rasolo-site-copy');
        ?></p>
<ol>
<li><?php
    _e('Download the backup archive of the files (the first link at the top of the page).','rasolo-site-copy');
            ?></li>
<li><?php
    _e('Download the backup archive of the database (the second link at the top of the page).','rasolo-site-copy');
        ?></li>
<li><?php
    _e('Generate fresh backup archives of files and databases, for which click on the “Update archive” button.','rasolo-site-copy');
        ?></li>
<li><?php
    _e('Download the fresh backup archive of the files (the first updated link at the top of the page).','rasolo-site-copy');
        ?></li>
<li><?php
    _e('Download the fresh backup archive of the database (the second updated link at the top of the page).','rasolo-site-copy');
        ?></li>
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
        	_e('Update archives','rasolo-site-copy');
		} else {
        	_e('Create archives','rasolo-site-copy');
		};
?>" />
<?php
		if($this->is_copy_files && $this->is_copy_mysql){
?><input class="button button-primary medium_left_margin rasolo_sitecopy_floatright"
       name="delete_site_copy" type="submit" value="<?php
    _e('Delete archives','rasolo-site-copy');
    ?>" /></div>
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
<h2 class="left_h1"><?php
    _e('User access to site archives','rasolo-site-copy');
        ?></h2>
<div class="rasolo_sitecopy_space">
<h4><?php
    _e('Allow non-administrative users to manage copies?','rasolo-site-copy');
        ?></h4>

<?php 

			if($this->is_simple_user_allowed()){
				$msg_wedge=__('Deny','rasolo-site-copy');
				$name_wedge='_deny';
			} else {
				$msg_wedge=__('Allow','rasolo-site-copy');
				$name_wedge='_allow';
			};
			$msg_wedge.=__(' file copying','rasolo-site-copy');
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

    public function admin_style() {
        wp_enqueue_style( 'rasolo-site-copy-admin-style', plugin_dir_url(__FILE__).'admin_style.css', false );
    }


       }  // The end of class RasoloSiteCopy
        }
//require_once(dirname(__FILE__) . '/srv_functions.php');

if(is_admin()){
	add_action('after_setup_theme','rasolo_copies_init');
};

function rasolo_copies_init()
		{

global $rasolo_copies_data;
		
$rasolo_copies_data=New RasoloSiteCopy();
$rasolo_copies_data->process_post_data();

		};

add_action('admin_menu', 'rasolo_site_copy_admin_menu_init');
function rasolo_site_copy_admin_menu_init()
		{
global $rasolo_copies_data;
call_user_func_array('add_menu_page',
			$rasolo_copies_data->get_menu_page_arguments());

call_user_func_array('add_options_page',
			$rasolo_copies_data->get_options_page_arguments());
	    }

