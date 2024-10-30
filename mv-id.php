<?php
/*
Plugin Name: Metaverse ID
Plugin URI: http://signpostmarv.name/mv-id/
Description: Display your identity from around the metaverse!
Version: 1.2.8
Author: SignpostMarv Martin
Author URI: http://signpostmarv.name/
 Copyright 2009 - 2012 SignpostMarv Martin  (email : mv-id.wp@signpostmarv.name)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
require_once('abstracts.php');
require_once('linkify.php');
class mv_id_plugin
{
	const shortcode = 'mv-id';
	protected static $metaverse_classes = array();
	protected static $supported_mvs = array();
	protected static $problems = array();
	protected static $mv_ids = array();
	public static function report_problem($problem)
	{
		self::$problems[] = $problem;
	}
	public static function show_problems()
	{
		if(empty(self::$problems) === false)
		{
?>
		<div><h3>Problems with MV-ID</h3>
		<ol class="mv-id problems"><?php
			foreach(self::$problems as $problem)
			{
?>
			<li><?php echo htmlentities2((string)$problem); ?></li><?php
			}
?>
		</ol></div>
<?php
		}
	}
	public static function DOMDocument($data)
	{
		$doc = new DOMDocument;
		if($doc instanceof DOMDocument && @$doc->loadHTML($data) !== false)
		{
			return $doc;
		}
		else
		{
			return false;
		}
	}
	public static function SimpleXML($data)
	{
		$XML = @simplexml_load_string($data);
		if($XML instanceof SimpleXMLElement)
		{
			return $XML;
		}
		else
		{
			return false;
		}
	}
	public static function XPath($node,$query,$allowZeroLength=false)
	{
		if($node instanceof DOMDocument)
		{
			$xpath = new DOMXPath($node);
			if($xpath instanceof DOMXPath)
			{
				$result = $xpath->evaluate($query);
				if($result === false)
				{
					return false;
				}
				else if(($result->length == 0 && $allowZeroLength) || $result->length >= 1)
				{
					return $result;
				}
				else
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}
		elseif($node instanceof SimpleXMLElement)
		{
			$result = $node->xpath($query);
			if(empty($result))
			{
				return false;
			}
			else
			{
				return $result;
			}
		}
		else
		{
			return false;
		}
	}
	public static function curl($url,array $curl_opts=null)
	{
		if(get_option('mv-id::use::HTTP API') === 'wordpress')
		{
			if(isset($curl_opts['method']) === false || $curl_opts['method'] === 'get')
			{
				$resp = wp_remote_get($url,$curl_opts);
			}
			else
			{
				$resp = wp_remote_post($url,$curl_opts);
			}
			if(is_wp_error($resp))
			{
				return null;
			}
			if(isset($curl_opts,$curl_opts['headers'],$curl_opts['headers']['If-Modified-Since']))
			{
				$header = wp_remote_retrieve_header($resp,'last-modified');
				if(isset($header) && strtotime($header) <= $curl_opts['headers']['If-Modified-Since'])
				{
					return true;
				}
			}
			else
			{
				return wp_remote_retrieve_body($resp);
			}
		}
		else
		{
			$ch = curl_init($url);
			if(empty($curl_opts))
			{
				$curl_opts = array();
			}
			else
			{
				$_curl_opts = array();
				if(isset($curl_opts['headers']) === true){
					if(isset($curl_opts['headers']['If-Modified-Since']) === true){
						$_curl_opts[CURLOPT_TIMEVALUE] = $curl_opts['headers']['If-Modified-Since'];
					}
				}
				if(isset($curl_opts['method']) && $curl_opts['method'] === 'post'){
					$_curl_opts[CURLOPT_POST] = true;
					if(isset($curl_opts['body']) === true){
						$_curl_opts[CURLOPT_POSTFIELDS] = $curl_opts['body'];
					}
				}
				if(isset($curl_opts['user-agent']) === true){
					$_curl_opts[CURLOPT_USERAGENT] = $curl_opts['user-agent'];
				}
				$curl_opts = $_curl_opts;
			}
			if(isset($curl_opts[CURLOPT_SSL_VERIFYPEER]) === false)
			{
				$curl_opts[CURLOPT_SSL_VERIFYPEER] = false;
			}
			if(isset($curl_opts[CURLOPT_RETURNTRANSFER]) === false)
			{
				$curl_opts[CURLOPT_RETURNTRANSFER] = true;
			}
			$no_hack_needed = (ini_get('safe_mode') !== '1' && ini_get('open_basedir') == false);
			if($no_hack_needed){
				$curl_opts[CURLOPT_FOLLOWLOCATION] = true;
			}else{
				$curl_opts[CURLOPT_FOLLOWLOCATION] = false;
				$curl_opts[CURLOPT_HEADER] = true;
			}
			if(isset($curl_opts[CURLOPT_TIMEVALUE]) !== false)
			{
				$curl_opts[CURLOPT_FILETIME] = true;
			}
			curl_setopt_array($ch,$curl_opts);
			$data = curl_exec($ch);
			if($no_hack_needed === false){
				$redirects = 5;
				while($redirects>0){
					if(($pos = strpos($data,'Location: http')) !== false){
						$pos = strpos($data,'http',$pos);
						$url = substr($data,$pos,strpos($data,"\r\n",$pos) - $pos);
						$ch = curl_init($url);
						curl_setopt_array($ch,$curl_opts);
						$data = curl_exec($ch);
						--$redirects;
					}else{
						$redirects = 0;
					}
				}
				$data = trim(substr($data,strpos($data,"\r\n\r\n")));
			}
			if(isset($curl_opts[CURLOPT_TIMEVALUE]) && curl_getinfo($ch,CURLINFO_FILETIME) !== -1 && ($curl_opts[CURLOPT_TIMEVALUE] <= curl_getinfo($ch,CURLINFO_FILETIME)))
			{
				return false;
			}
			curl_close($ch);
			return $data;
		}
	}
	protected static function wpdb()
	{
		global $wpdb;
		return $wpdb;
	}
	public static function bday_label(mv_id_vcard_widget $vcard)
	{
		switch(get_class($vcard))
		{
			case 'mv_id_vcard_agni_sl':
			case 'mv_id_vcard_teen_sl':
				return 'Rezday';
			break;
			default:
				return 'Created';
			break;
		}
	}
	public static function db_tablename()
	{
		return self::wpdb()->prefix . 'mv_id';
	}
	protected static function install()
	{
		$structure = 'CREATE TABLE IF NOT EXISTS ' . self::db_tablename() . ' (
`user_id` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT "1",
`metaverse` CHAR( 32 ) NOT NULL ,
`id` CHAR( 255 ) NOT NULL ,
`last_mod` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
`cache` BLOB NULL DEFAULT NULL ,
PRIMARY KEY ( `user_id`,`metaverse` , `id` )
)';
		self::wpdb()->query($structure);
		self::upgrade();
	}
	protected static function upgrade()
	{
		$alter_sql = 'ALTER TABLE ' . self::db_tablename() . ' ADD `user_id` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT "1" FIRST, DROP PRIMARY KEY, ADD PRIMARY KEY ( `user_id`, `metaverse`, `id`)';
		$check_sql = 'SHOW COLUMNS FROM ' . self::db_tablename();
		$schema = self::wpdb()->get_results($check_sql);
		if(empty($schema) === false)
		{
			$fields = array();
			foreach($schema as $field)
			{
				$fields[] = $field->Field;
			}
			unset($schema);
			if(in_array('user_id',$fields) === false)
			{
				self::wpdb()->query($alter_sql);
			}
			unset($fields);
		}
	}
	protected static function uninstall()
	{
		self::wpdb()->query('DROP TABLE IF EXISTS ' . self::db_tablename());
	}
	public static function activate()
	{
		self::install();
		wp_clear_scheduled_hook('mv_id_plugin__regenerate_cache');
//		wp_schedule_event(time(),'hourly','mv_id_plugin__regenerate_cache');
	}
	public static function deactivate()
	{
		self::uninstall();
		wp_clear_scheduled_hook('mv_id_plugin__regenerate_cache');
		remove_shortcode(self::shortcode);
	}
	public static function register_metaverses()
	{
		do_action('mv_id_plugin__register_metaverses');
		wp_register_script('MV-ID',untrailingslashit(trailingslashit(trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/'. dirname( plugin_basename(__FILE__) )) . 'mv-id.js'),array('jquery'));
		add_action('admin_print_scripts','mv_id_plugin::print_scripts');
		add_shortcode(self::shortcode,'mv_id_plugin::shortcode');
	}
	public static function delete_user($user_ID)
	{
		static $delete_sql;
		if(isset($delete_sql) === false)
		{
			$delete_sql = 'DELETE FROM ' . self::db_tablename() . ' WHERE user_id = %s';
		}
		self::wpdb()->query(self::wpdb()->prepare($delete_sql,$user_ID));
	}
	public static function profile_update($user_ID)
	{
		$user_info = get_userdata($user_ID);
		if(isset($user_info->user_level) === false)
		{
			self::delete_user($user_ID);
		}
	}
	public static function cron()
	{
		$mv_ids = self::get_all_mv_ids(true);
		if(isset($mv_ids) && is_array($mv_ids) && count($mv_ids) > 0)
		{
			foreach($mv_ids as $id)
			{
				self::refresh($id->metaverse,$id->id);
			}
		}
	}
	protected static function refresh($metaverse,$id,$ignore_last_mod=false)
	{
		$vcard = call_user_func_array(self::$metaverse_classes[$metaverse] . '::factory',array($id,$ignore_last_mod ? false : self::get_mv_id_last_mod($metaverse,$id)));
		if(isset($vcard) && ($vcard instanceof mv_id_vcard_widget))
		{
			self::cache($metaverse,$id,$vcard);
		}
		else if(isset($vcard) && $vcard === true)
		{
			static $tweak_sql;
			if(isset($tweak_sql) === false)
			{
				$tweak_sql = 'UPDATE ' . self::db_tablename() . ' SET last_mod=NOW() WHERE metaverse = %s AND id = %s';
			}
			self::wpdb()->query(self::wpdb()->prepare($tweak_sql,$metaverse,$id));
		}
	}
	public static function table_exists()
	{
		$tables = self::wpdb()->get_results(self::wpdb()->prepare('SHOW TABLES LIKE %s',self::db_tablename()),ARRAY_N);
		if(empty($tables) === false)
		{
			foreach($tables as $table)
			{
				list($table) = $table;
				if($table == self::db_tablename())
				{
					return true;
				}
			}
		}
		return false;
	}
	public static function delete($metaverse,$id)
	{
		global $user_ID;
		get_currentuserinfo();
		static $delete_sql;
		if(isset($delete_sql) === false)
		{
			$delete_sql = 'DELETE FROM ' . self::db_tablename() . ' WHERE user_ID = %s AND metaverse = %s AND id = %s';
		}
		self::wpdb()->query(self::wpdb()->prepare($delete_sql,$user_ID,$metaverse,$id));
	}
	public static function add($metaverse,$id)
	{
		global $user_ID;
		global $user_level;
		get_currentuserinfo();
		if(self::nice_name($metaverse) !== false && self::is_id_valid($metaverse,$id) === true && $user_ID !== '' && $user_level >= 1)
		{
			static $add_sql;
			if(isset($add_sql) === false)
			{
				$add_sql =
'INSERT INTO ' . self::db_tablename() . ' (user_id,metaverse,id) VALUES(%s,%s,%s)
ON DUPLICATE KEY UPDATE
	cache=NULL';
			}
			self::wpdb()->query(self::wpdb()->prepare($add_sql,$user_ID,$metaverse,$id));
		}
	}
	public static function cache($metaverse,$id,mv_id_vcard $vcard) // do not call before add
	{
		global $user_ID;
		if(self::nice_name($metaverse) !== false && self::is_id_valid($metaverse,$id) === true)
		{
			static $cache_sql;
			if(isset($cache_sql) === false)
			{
				$cache_sql = 'UPDATE ' . self::db_tablename() . ' SET cache = %s,last_mod=NOW() WHERE metaverse = %s AND id = %s';
			}
			return self::wpdb()->query(self::wpdb()->prepare($cache_sql,serialize($vcard),$metaverse,$id));
		}
		else
		{
			return false;
		}
	}
	public static function get($metaverse,$id)
	{
		static $get_sql;
		if(isset($get_sql) === false)
		{
			$get_sql = 'SELECT cache FROM ' . self::db_tablename() . ' WHERE metaverse=%s AND id=%s AND cache IS NOT NULL LIMIT 1';
		}
		$cache = self::wpdb()->get_var(self::wpdb()->prepare($get_sql,$metaverse,$id));
		if(empty($cache) === false)
		{
			return unserialize($cache);
		}
		else
		{
			return false;
		}
	}
	public static function get_all($metaverse)
	{
		static $get_sql;
		if(isset($get_sql) === false)
		{
			$get_sql = 'SELECT cache FROM ' . self::db_tablename() . ' WHERE metaverse=%s AND cache IS NOT NULL';
		}
		$cache = self::wpdb()->get_results(self::wpdb()->prepare($get_sql,$metaverse),ARRAY_N);
		if(is_array($cache) === false)
		{
			return array();
		}
		foreach($cache as $k=>$v)
		{
			if(empty($v) === false)
			{
				$cache[$k] = unserialize($v[0]);
				if(($cache[$k] instanceof mv_id_vcard_widget) === false)
				{
					unset($cache[$k]);
				}
			}
			else
			{
				unset($cache[$k]);
			}
		}
		return $cache;
	}
	public static function get_mv_id_last_mod($metaverse,$id,$force=false)
	{
		static $get_sql;
		if(isset($get_sql) === false)
		{
			$get_sql = 'SELECT last_mod FROM ' . self::db_tablename() . ' WHERE metaverse=%s AND id=%s AND cache IS NOT NULL ORDER BY last_mod DESC LIMIT 1';
		}
		$last_mod = self::wpdb()->get_var(self::wpdb()->prepare($get_sql,$metaverse,$id));
		if(empty($last_mod))
		{
			return false;
		}
		else
		{
			return strtotime($last_mod);
		}
	}
	public static function get_all_mv_ids($force=false)
	{
		static $get_sql;
		static $mv_ids;
		if(isset($get_sql) === false)
		{
			$get_sql = 'SELECT metaverse,id,user_id FROM ' . self::db_tablename();
		}
		if(empty($mv_ids) || $force == true)
		{
			$mv_ids = self::wpdb()->get_results($get_sql);
		}
		return $mv_ids;
	}
	public static function get_all_mv_ids_and_cache($force=false)
	{
		global $user_ID;
		get_currentuserinfo();
		static $get_sql;
		static $user_sql = ' WHERE user_id = %s';
		if(isset($get_sql) === false)
		{
			$get_sql = 'SELECT metaverse,id,cache FROM ' . self::db_tablename();
		}
		if(isset(self::$mv_ids[$user_ID]) === false || $force == true)
		{
			if($user_ID === '')
			{
				self::$mv_ids[$user_ID] = self::wpdb()->get_results($get_sql);
			}
			else
			{
				self::$mv_ids[$user_ID] = self::wpdb()->get_results(self::wpdb()->prepare($get_sql . $user_sql,$user_ID));
			}
			foreach(self::$mv_ids[$user_ID] as $k=>$v)
			{
				if(empty($v->cache) === false)
				{
					self::$mv_ids[$user_ID][$k]->cache = unserialize($v->cache);
				}
			}
		}
		return self::$mv_ids[$user_ID];
	}
	public static function admin_get_all_mv_ids_and_cache($force=false)
	{
		global $user_level;
		get_currentuserinfo();
		if($user_level < 10)
		{
			mv_id_plugin::report_problem('User does not have sufficient permissions to nab all IDs.');
			return false;
		}
		static $get_sql;
		if(isset($get_sql) === false)
		{
			$get_sql = 'SELECT user_id,metaverse,id,cache FROM ' . self::db_tablename();
		}
		$mv_ids = self::wpdb()->get_results($get_sql);
		foreach($mv_ids as $k=>$row)
		{
			if(isset(self::$mv_ids[$row->user_id]) === false || $force == true)
			{
				if(empty($row->cache) === false)
				{
					$row->cache = unserialize($row->cache);
				}
				unset($row->user_id);
				self::$mv_ids[$row->user_id][] = $row;
			}
			unset($mv_ids[$k]);
		}
		return self::$mv_ids;
	}
	public static function get_uncached_mv_ids($force=false,$all_users=false)
	{
		static $mv_ids = array();
		static $get_sql;
		$_zomg_user_ID = '';
		if($all_users === false)
		{
			global $user_ID;
			get_currentuserinfo();
			$_zomg_user_ID = $user_ID;
			static $user_sql = ' AND user_id = %s';
		}
		if(isset($get_sql) === false)
		{
			$get_sql = 'SELECT metaverse,id FROM ' . self::db_tablename() . ' WHERE cache IS NULL';
		}
		if(isset($mv_ids[$_zomg_user_ID]) === false || $force === true)
		{
			if($_zomg_user_ID === '' || $all_users === true)
			{
				$mv_ids[$_zomg_user_ID] = self::wpdb()->get_results($get_sql);
			}
			else
			{
				$mv_ids[$_zomg_user_ID] = self::wpdb()->get_results(self::wpdb()->prepare($get_sql . $user_sql,$_zomg_user_ID));
			}
		}
		return $mv_ids[$_zomg_user_ID];
	}
	public static function register_metaverse($nice_name,$metaverse,$class)
	{
		self::$metaverse_classes[$metaverse] = $class;
		self::$supported_mvs[$nice_name] = $metaverse;
	}
	public static function registered_metaverses()
	{
		static $sorted = false;
		if($sorted === false)
		{
			asort(self::$metaverse_classes);
			$sorted = true;
		}
		return self::$metaverse_classes;
	}
	public static function nice_name($metaverse)
	{
		return in_array($metaverse,self::$supported_mvs) ? array_search($metaverse,self::$supported_mvs) : false;
	}
	public static function metaverse($nice_name)
	{
		return isset(self::$supported_mvs[$nice_name]) ? self::$supported_mvs[$nice_name] : false;
	}
	public static function is_id_valid($metaverse,$id)
	{
		return (
			isset(self::$metaverse_classes[$metaverse]) === true &&
			call_user_func_array(self::$metaverse_classes[$metaverse] . '::is_id_valid',array($id)) === true
		);
	}
	public static function mv_needs_admin($metaverse=null)
	{
		static $mvs;
		if(isset($mvs) === false)
		{
			$mvs = array();
			foreach(self::$metaverse_classes as $metaverse=>$class)
			{
				if(in_array('mv_id_needs_admin',class_implements($class,false)))
				{
					$mvs[$metaverse] = $class;
				}
			}
			unset($metaverse);
		}
		return isset($metaverse) ? ( isset($mvs[$metaverse]) ? $mvs[$metaverse] : false ) : $mvs;
	}
	public static function admin_actions()
	{
		global $user_level;
		get_currentuserinfo();
		if($user_level >= 1)
		{
			add_submenu_page('profile.php', 'Metaverse ID', 'Metaverse ID', 'read', 'mv-id', 'mv_id_plugin::user_ids');
		}
		add_options_page('Metaverse ID','Metaverse ID',1,'mv-id','mv_id_plugin::admin');
		add_filter('plugin_action_links', 'mv_id_plugin::plugin_actions', 10, 2);
	}
	public static function plugin_actions($links, $file) {
		static $this_plugin;
		if(isset($this_plugin) === false)
		{
			$this_plugin = plugin_basename(__FILE__);
		}
		if($file === $this_plugin)
		{
			$settings_link = '<a href="profile.php?page=mv-id">Manage</a>';
			$links[] = $settings_link;
		}
		return $links;
	}
	public static function shortcode($atts)
	{
		extract(shortcode_atts(array(
			'mv' => '',
			'id' => '',
			'h'=>'',
		),$atts));
		$h = strtolower($h);
		if(is_numeric($h))
		{
			$h = (integer)$h;
			if($h <= 0 || $h > 6)
			{
				$h = false;
			}
		}
		if($id !== '')
		{
			$vcards = array(mv_id_plugin::get($mv,$id));
		}
		else
		{
			$vcards = mv_id_plugin::get_all($mv);
		}
		foreach($vcards as $k=>$vcard)
		{
			if(($vcard instanceof mv_id_vcard_widget) === false)
			{
				unset($vcards[$k]);
			}
		}
		if(empty($vcards))
		{
			return;
		}
		else
		{
			$doc = '';
			if($h)
			{
				$doc .= sprintf('<h%1$u>%2$s</h%1$u>',$h,htmlentities2(self::nice_name($mv))) . "\n";
			}
			foreach($vcards as $vcard)
			{
				$doc .= mv_id_plugin_widgets::build($vcard);
			}
			return $doc;
		}
	}
	public static function javascript()
	{
		$ids = $mv_ids = $mv_id_formats = $mv_id_nice_names = array();
		foreach(self::registered_metaverses() as $mv_id=>$mv_class)
		{
			$mv_ids[] = $mv_id;
			$mv_id_formats[] = call_user_func(self::$metaverse_classes[$mv_id] . '::id_format');
			$mv_id_nice_names[$mv_id] = mv_id_plugin::nice_name($mv_id);
		}
		foreach(self::get_all_mv_ids() as $mv_id)
		{
			if(isset($ids[$mv_id->metaverse]) === false)
			{
				$ids[$mv_id->metaverse] = array();
			}
			$ids[$mv_id->metaverse][] = array('user'=>$mv_id->user_id,'id'=>$mv_id->id);
		}
?>
<script type="text/javascript">/*<![CDATA[*/
mv_id_plugin.metaverses = <?php echo json_encode($mv_ids);?>;
mv_id_plugin.ids        = <?php echo json_encode($ids);?>;
mv_id_plugin.formats    = <?php echo json_encode($mv_id_formats);?>;
mv_id_plugin.nice_names = <?php echo json_encode($mv_id_nice_names);?>;
/*]]>*/</script>
<?php
	}
	public static function print_scripts()
	{
		wp_enqueue_script('MV-ID');
	}
	public static function widgets_init()
	{
		return register_widget("mv_id_plugin_widget");
	}
	protected static function mv_id_trs($mv_ids)
	{
		if(count($mv_ids) > 0)
		{
?>
		<table class="hreview">
			<caption>Current IDs</caption>
			<tr>
				<th>Delete</th>
				<th>Update</th>
				<th>Metaverse ID</th>
				<th>Preview</th>
			</tr>
<?php
			foreach($mv_ids as $id)
			{
				if(self::nice_name($id->metaverse) !== false)
				{
					$vcard = $id->cache;
?>
			<tr>
				<td><input type="checkbox" name="delete[]" value="<?php echo esc_attr($id->metaverse),'::',esc_attr($id->id); ?>" title="Delete '<?php echo esc_attr($id->id); ?>' ?" /></td>
				<td><input type="checkbox" name="update[]" value="<?php echo esc_attr($id->metaverse),'::',esc_attr($id->id); ?>" title="Update '<?php echo esc_attr($id->id); ?>' ?" <?php if($vcard === NULL){ ?>checked="checked"<?php } ?> /></td>
				<td><?php echo self::nice_name($id->metaverse); ?><br /><strong><?php echo $id->id; ?></strong><br />Shortcode:<code>[<?php echo htmlentities2(mv_id_plugin::shortcode);?> mv='<?php echo esc_attr($id->metaverse) ?>' id='<?php echo esc_attr($id->id); ?>']</code></td>
				<td><?php
				if($vcard instanceof mv_id_vcard_widget)
				{
					do_action('mv_id_plugin__output_vcard',$vcard);
				}
				else if($id->cache === NULL)
				{
	?>Profile is not yet cached.<?php
				}
				else
				{
	?>No Preview Available, possibly a problem fetching or caching the profile.<?php
				}
	?></td>
			</tr>
	<?php
				}
			}
?>
		</table>
<?php
		}
	}
	protected static function del_add_update()
	{
		if(isset($_POST['delete']))
		{
			foreach($_POST['delete'] as $delete_this)
			{
				if(!isset($_POST['update']) || !in_array($delete_this, $_POST['update'])){
					list($metaverse,$id) = explode('::',$delete_this);
					self::delete($metaverse,$id);
					if(isset($_POST['add'],$_POST['add'][$metaverse],$_POST['add'][$metaverse][$id]))
					{
						unset($_POST['add'][$metaverse][$id]);
					}
				}
			}
		}
		if(isset($_POST['add']))
		{
			foreach($_POST['add'] as $v)
			{
				self::add($v['metaverse'],$v['id']);
			}
		}
		if(isset($_POST['update']))
		{
			foreach($_POST['update'] as $update_this)
			{
				list($metaverse,$id) = explode('::',$update_this);
				self::refresh($metaverse,$id,isset($_POST['delete']) && in_array($update_this, $_POST['delete']));
			}
		}
	}
	public static function user_ids()
	{
		self::del_add_update();
		$mv_ids = self::get_all_mv_ids_and_cache();
?>
	<h2>Your Metaverse IDs</h2>
<?php
	if(count(self::registered_metaverses()) < 1)
	{
?>
		<p>There are no Metaverses available to use.</p>
<?php
		return;
	}
	self::show_problems();
?>
	<form action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" method="post">
<?php
			self::mv_id_trs($mv_ids);
?>
		<div id="add-mv-ids">
			<h3>Add ID</h3>
			<ol>
				<li><select name="add[0][metaverse]">
<?php
		foreach(self::registered_metaverses() as $mv_id=>$mv_class)
		{
?>
				<option value="<?php echo esc_attr($mv_id); ?>" title="<?php echo esc_attr(call_user_func(self::$metaverse_classes[$mv_id] . '::id_format')); ?>"><?php echo htmlentities2(mv_id_plugin::nice_name($mv_id)); ?></option>

<?php
		}
?>
				</select> <input name="add[0][id]" type="text" maxlength="255" /></li>
			</ol>
			<script type="text/javascript">/*<![CDATA[*/
var mv_id_plugin__id_div = document.getElementById('add-mv-ids');
var mv_id_plugin__num_entries = 1;
var a = document.createElement('a');
a.href = 'javascript:mv_id_plugin.add_more()';
a.appendChild(document.createTextNode('Add More IDs'));
mv_id_plugin__id_div.appendChild(a);
			/*]]>*/</script>
		</div>
		<p>
			<input type="submit" name="Submit" value="Update/Delete" />
		</p>
	</form>
<?php
	}
	public static function admin()
	{
		self::del_add_update();
		self::show_problems();
		$http_api2use_label = 'mv-id::use::HTTP_API';
		$http_api2use = get_option($http_api2use_label);
		if(isset($_POST) && empty($_POST) === false)
		{
			$needs_admin = self::mv_needs_admin();
			foreach(array_keys($_POST) as $metaverse)
			{
				if($metaverse === $http_api2use_label)
				{
					continue;
				}
				if(isset($needs_admin[$metaverse]) === false)
				{
					unset($_POST[$metaverse]);
				}
				else
				{
					$admin_fields = call_user_func($needs_admin[$metaverse] . '::admin_fields');
					foreach($_POST[$metaverse] as $field=>$value)
					{
						if(in_array($field,array_keys($admin_fields)) === false)
						{
							unset($_POST[$metaverse][$field]);
						}
						else
						{
							if(preg_match($admin_fields[$field]['regex'],$value) !== 1)
							{
								unset($_POST[$metaverse]);
							}
						}
					}
				}
			}
			if(empty($_POST))
			{
				unset($_POST);
			}
			else
			{
				if(isset($_POST[$http_api2use_label]) === true)
				{
					if($http_api2use !== false)
					{
						update_option($http_api2use_label,$_POST[$http_api2use_label]);
					}
					else
					{
						add_option($http_api2use_label,$_POST[$http_api2use_label],'','no');
					}
					$http_api2use = get_option($http_api2use_label);
					unset($_POST[$http_api2use_label]);
				}
				foreach($_POST as $metaverse=>$config)
				{
					$option_label = 'mv-id::' . $metaverse;
					$value = serialize($config);
					if(get_option($option_label))
					{
						update_option($option_label,$value);
					}
					else
					{
						add_option($option_label,$value,'','no');
					}
				}
			}
		}
?>
	<h2>Metaverse ID Admin</h2>
	<form action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" method="post">
		<fieldset>
			<legend>HTTP API</legend>
			<ol>
				<li title="uses PHP cURL extension"><label for="http-api-mv-id">MV-ID Custom </label> <input id="http-api-mv-id" type="radio" name="mv-id::use::HTTP_API" value="mv-id"<?php if($http_api2use === 'mv-id' || $http_api2use === false){ ?> checked="checked"<?php } ?> /></li>
				<li><label for="http-api-wp">WordPress </label> <input id="http-api-wp" type="radio" name="mv-id::use::HTTP_API" value="wordpress"<?php if($http_api2use === 'wordpress'){ ?> checked="checked"<?php } ?> /></li>
			</ol>
		</fieldset>
<?php
		$mvs_needing_admin = self::mv_needs_admin();
		if(empty($mvs_needing_admin) === false)
		{
			foreach($mvs_needing_admin as $metaverse=>$class)
			{
				$fields = call_user_func($class . '::admin_fields');
				$option = get_option('mv-id::' . $metaverse);
				if($option){
					$option = maybe_unserialize($option);
				}
?>
		<fieldset>
			<legend><?php echo htmlentities2(self::nice_name($metaverse));?></legend>
			<ol>
<?php
				foreach($fields as $id=>$field)
				{
					$inputid = str_replace(' ','_',$metaverse) . '-' . $id;
?>				<li><label for="<?php echo esc_attr($inputid); ?>"><?php echo htmlentities2($field['name']);?></label> <input id="<?php echo esc_attr($inputid); ?>" name="<?php echo esc_attr($metaverse . '[' . $id . ']'); ?>" <?php if($option){ echo 'value="',esc_attr($option[$id]),'"';} ?> /></li>
<?php
				}
?>
			</ol>
		</fieldset>
<?php
			}
		}
		$mv_ids = array();
		$all = self::admin_get_all_mv_ids_and_cache();
		if(empty($all) === false)
		{
?>
	<h2>Metaverse IDs Registered to users.</h2>
<?php
			foreach($all as $_mv_ids)
			{
				$mv_ids = array_merge($mv_ids,$_mv_ids);
			}
			self::mv_id_trs($mv_ids);
		}
?>
		<input type="submit" value="Configure" />
	</form>
<?php }
}
class mv_id_plugin_widgets
{
	public static function name($metaverse,$id)
	{
		return 'Metaverse ID: ' . mv_id_plugin::nice_name($metaverse) . ' (' . $id . ')';
	}
	protected static function current_metaverses()
	{
		if(mv_id_plugin::table_exists() === false)
		{
			return array();
		}
		static $metaverses;
		global $wpdb;
		if(isset($metaverses) === false)
		{
			$metaverses = array();
			$get_sql = 'SELECT DISTINCT metaverse FROM ' . mv_id_plugin::db_tablename() . ' WHERE cache IS NOT NULL';
			$_metaverses = $wpdb->get_results($get_sql);
			foreach($_metaverses as $metaverse)
			{
				$metaverses[] = $metaverse->metaverse;
			}
			unset($_metaverses,$metaverse);
		}
		return $metaverses;
	}
	public static function register()
	{
		$classes = mv_id_plugin::registered_metaverses();
		foreach(self::current_metaverses() as $metaverse)
		{
			if(isset($classes[$metaverse]) === true)
			{
				register_sidebar_widget('Metaverse ID: ' . mv_id_plugin::nice_name($metaverse),$classes[$metaverse] . '::widget');
			}
		}
	}
	public static function build(mv_id_vcard $vcard)
	{
		ob_start();
		echo
			str_repeat("\t",5),'<div class="hresume">',"\n",
			str_repeat("\t",6),'<address class="contact vcard">',"\n",
			str_repeat("\t",7),'<a class="url fn" rel="me" href="',esc_url($vcard->url()),'">',htmlentities2($vcard->name()),'</a><br />',"\n",
			str_repeat("\t",7),'<span class="uid" style="display:none;">',htmlentities2($vcard->uid()),'</span>',"\n";
		if($vcard->img() !== null)
		{
			echo str_repeat("\t",7),'<img class="photo" src="',esc_url($vcard->image_url()),'" alt="',htmlentities2($vcard->name()),'" />',"\n";
		}
		echo str_repeat("\t",6),'</address>',"\n";
		if(isset($vcard->stats()->bday))
		{
			echo str_repeat("\t",6),'<div class="vevent account-creation"><span class="summary"><span style="display: none;">',
				htmlentities2($vcard->name()),'\'s',(mv_id_plugin::bday_label($vcard) === 'Created' ? ' account' : ' '),'</span>',
				htmlentities2(mv_id_plugin::bday_label($vcard)),'</span>: <abbr class="dtstart" title="',
				esc_attr($vcard->stats()->bday),'">',htmlentities2(date('jS M, Y',strtotime($vcard->stats()->bday))),'</abbr></div>',"\n";
		}
		if($vcard->description() !== null)
		{
			echo str_repeat("\t",6),'<p class="summary">',str_replace("\n","<br />\n",apply_filters('mv_id_linkify',htmlentities2($vcard->description()),null,array('me'))),'</p>',"\n";
		}
		if($vcard->stats() instanceof mv_id_stats && $vcard->stats()->count() > 0)
		{
			echo str_repeat("\t",6),'<ul>',"\n";
			foreach($vcard->stats() as $stat)
			{
				if($stat->name() === 'bday')
				{
					continue;
				}
				else
				{
					echo str_repeat("\t",7),'<li class="stat"><span class="type">',htmlentities2($stat->name()),'</span>',': <span class="value">',htmlentities2($stat->value()),'</span></li>',"\n";
				}
			}
			echo str_repeat("\t",6),'</ul>',"\n";
		}
		if(is_array($vcard->skills()))
		{
			echo str_repeat("\t",6),'<ul>',"\n";
			foreach($vcard->skills() as $skill)
			{
				echo str_repeat("\t",7),'<li>',"\n";
				if(is_string($skill->url()))
				{
					echo '<a class="skill" rel="tag" href="',esc_url($skill->url()),'">';
				}
				else
				{
					echo '<span class="skill">';
				}
				echo htmlentities2($skill->name());
				if(is_string($skill->url()))
				{
					echo '</a>: ';
				}
				else
				{
					echo '</span>: ';
				}
				echo htmlentities2($skill->value()),'</li>',"\n";
			}
			echo str_repeat("\t",6),'</ul>',"\n";
		}
		if(is_string(call_user_func(get_class($vcard) . '::affiliations_label')) && is_array($vcard->affiliations()))
		{
			echo
				str_repeat("\t",6),'<strong>',htmlentities2(call_user_func(get_class($vcard) . '::affiliations_label')),'</strong>',"\n",
				str_repeat("\t",7),'<ul>',"\n";
				foreach($vcard->affiliations() as $affiliation)
				{
					echo str_repeat("\t",8),'<li class="affiliation vcard"><span class="fn org">';
					if( $affiliation->url() !== false)
					{
						echo '<a class="url" href="',esc_url($affiliation->url()),'">';
					}
					echo htmlentities($affiliation->name(),ENT_QUOTES,'UTF-8');
					if($affiliation->url() !== false)
					{
						echo '</a>';
					}
					echo '</span>';
					if($affiliation->img() !== null && $affiliation->img() !== false)
					{
						echo '<br /><img class="photo" src="',esc_attr($affiliation->img()),'" />';
					}
					echo '</li>',"\n";
				}
			echo str_repeat("\t",6),'</ul>',"\n";
		}
		echo str_repeat("\t",5),'</div>',"\n";
		$hresume = ob_get_contents();
		ob_end_clean();
		return apply_filters('post_output_mv_id_vcard',$hresume,$vcard);
	}
	public static function output(mv_id_vcard $vcard)
	{
		echo self::build($vcard);
	}
}
class mv_id_plugin_widget extends WP_Widget {
	public function __construct( $id_base = false, $widget_options = array(), $control_options = array() ) {
		parent::__construct($id_base,'Metaverse ID',$widget_options,$control_options);
	}
    public function widget($args, $instance) {
		$vcard = mv_id_plugin::get($instance['metaverse'],$instance['id']);
		if(($vcard instanceof mv_id_vcard_widget) === false)
		{
			return;
		}
        extract( $args );
		echo $before_widget,$before_title,$instance['title'],$after_title,mv_id_plugin_widgets::output($vcard),$after_widget;
    }
	public function form($instance)
	{
		if(empty($instance) === false)
		{
			$vcard = mv_id_plugin::get($instance['metaverse'],$instance['id']);
?>
		<em>Current</em>
		<?php if(($vcard instanceof mv_id_vcard_widget) === false)
			{?>
		<p><?php echo wp_specialchars($instance['metaverse']),'<br />',"\n",wp_specialchars($instance['id']); ?></p>
<?php
			}
			else
			{
				?> <hr /> <?php
				mv_id_plugin_widgets::output($vcard);
				?> <hr /> <?php
			}
		}
?>
		<p><select id="<?php echo esc_attr($this->get_field_id('metaverse')); ?>" name="<?php echo esc_attr($this->get_field_name('metaverse')); ?>"></select></p>
		<p><select id="<?php echo esc_attr($this->get_field_id('id')); ?>" name="<?php echo esc_attr($this->get_field_name('id')); ?>"></select></p>
		<script type="text/javascript">/*<![CDATA[*/
mv_id_plugin.populate_select_mv(<?php echo json_encode(esc_js($this->get_field_id('metaverse'))); ?>,<?php echo json_encode(esc_js($this->get_field_id('id'))),',',json_encode($instance); ?>);
		/*]]>*/</script>
<?php
	}
	function update($new, $old) {
		$this->name = $new_instance['metaverse'] . '::' . $new_instance['id'];
		return $new;
	}
}
require_once('metaverses/second-life.php');
require_once('metaverses/free-realms.php');
require_once('metaverses/wow.php');
require_once('metaverses/lotro.php');
require_once('metaverses/eve.php');
require_once('metaverses/pq.php');
require_once('metaverses/champions-online.php');
require_once('metaverses/star-trek-online.php');
require_once('metaverses/eq.php');
require_once('metaverses/eq2.php');
register_activation_hook(__FILE__,'mv_id_plugin::activate');
register_deactivation_hook(__FILE__,'mv_id_plugin::deactivate');
add_action('widgets_init', 'mv_id_plugin::widgets_init');
add_action('mv_id_plugin__regenerate_cache','mv_id_plugin::cron');
add_action('mv_id_plugin__output_vcard','mv_id_plugin_widgets::output');
add_action('admin_menu','mv_id_plugin::admin_actions');
add_action('plugins_loaded','mv_id_plugin::register_metaverses');
add_action('widgets_init','mv_id_plugin_widgets::register');
add_action('delete_user','mv_id_plugin::delete_user');
add_action('profile_update','mv_id_plugin::profile_update');
add_action('admin_head','mv_id_plugin::javascript');
?>