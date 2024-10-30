<?php
interface mv_id_vcard_funcs
{
	public function uid();
	public function name();
	public function image_url();
	public function description();
	public function url();
	public static function is_id_valid($id);
}
interface mv_id_vcard_widget
{
	public static function factory($id);
	public static function get_widget(array $args);
	public static function affiliations_label();
	public static function id_format();
	public function affiliations();
	public function skills();
	public function stats();
}
interface mv_id_needs_admin
{
	public static function admin_fields();
}
interface mv_id_stat_funcs
{
	public function name();
	public function value();
}
class mv_id_stat implements mv_id_stat_funcs
{
	protected $name;
	protected $value;
	public function __construct($name,$value)
	{
		$this->name = $name;
		$this->value = $value;
	}
	public function name()
	{
		return $this->name;
	}
	public function value()
	{
		return $this->value;
	}
}
class mv_id_stats implements Countable, Iterator
{
	protected $stats = array();
	public function __construct(array $stats)
	{
		if(empty($stats) === false)
		{
			foreach($stats as $stat)
			{
				if($stat instanceof mv_id_stat_funcs)
				{
					$this->stats[$stat->name()] = $stat;
				}
			}
		}
	}
	public function stats()
	{
		return $this->stats;
	}
	public function __isset($name)
	{
		return isset($this->stats[$name]);
	}
	public function __get($name)
	{
		return $this->__isset($name) ? $this->stats[$name]->value() : null;
	}

/**
*	From the Countable interface
*/
	public function count(){
		return count($this->stats);
	}

/**
*	From the Iterator interface
*/
	public function current(){
		return current($this->stats);
	}
	public function next(){
		return next($this->stats);
	}
	public function key(){
		return key($this->stats);
	}
	public function valid(){
		return ($this->key() !== null);
	}
	public function rewind(){
		return reset($this->stats);
	}
}
interface mv_id_skill_funcs extends mv_id_stat_funcs
{
	public function url();
}
class mv_id_skill extends mv_id_stat implements mv_id_skill_funcs
{
	protected $url;
	public function __construct($name,$value,$url=null)
	{
		$this->name = $name;
		$this->value = $value;
		$this->url = $url;
	}
	public function url()
	{
		return $this->url;
	}
}
class mv_id_vcard_affiliation implements mv_id_vcard_funcs
{
	public function __construct($name,$url=null,$image=null,$description=null,$uid=null)
	{
		$this->uid = $uid;
		$this->name = $name;
		$this->image = $image;
		$this->description = $description;
		$this->url = $url;
	}
	public function uid()
	{
		return $this->uid;
	}
	public function name()
	{
		return $this->name;
	}
	public function img()
	{
		return $this->image;
	}
	public function description()
	{
		return $this->description;
	}
	public function url()
	{
		if(isset($this->url) === false && $this->url !== false)
		{
			$this->url = sprintf(constant(get_class($this) . '::sprintf_url'),$this->uid());
		}
		return $this->url;
	}
	public function image_url()
	{
		return sprintf(constant(get_class($this) . '::sprintf_img'),$this->img());
	}
	public static function is_id_valid($id)
	{
		return $id === null;
	}
}

abstract class mv_id_vcard implements mv_id_vcard_funcs, mv_id_vcard_widget
{
	protected $uid;
	protected $name;
	protected $image;
	protected $description;
	protected $affiliations;
	protected $skills;
	protected $stats;
	public function __construct($uid,$name,$image=null,$description=null,$url=null,mv_id_stats $stats=null,array $affiliations=null,array $skills=null)
	{
		$this->uid = $uid;
		$this->name = $name;
		$this->image = $image;
		$this->description = $description;
		$this->url = $url;
		if(empty($affiliations) === false)
		{
			foreach($affiliations as $k=>$affiliation)
			{
				if(($affiliation instanceof mv_id_vcard_affiliation) === false)
				{
					unset($affiliations[$k]);
				}
			}
			if(empty($affiliations) === false)
			{
				$this->affiliations = $affiliations;
			}
		}
		if(empty($skills) === false)
		{
			foreach($skills as $k=>$skill)
			{
				if(($skill instanceof mv_id_skill_funcs) === false)
				{
					unset($skills[$k]);
				}
			}
			if(empty($skills) === false)
			{
				$this->skills = $skills;
			}
		}
		if($stats instanceof mv_id_stats)
		{
			$this->stats = $stats;
		}
	}
	public function uid()
	{
		return $this->uid;
	}
	public function name()
	{
		return $this->name;
	}
	public function img()
	{
		return $this->image;
	}
	public function description()
	{
		return $this->description;
	}
	public function affiliations()
	{
		return $this->affiliations;
	}
	public function skills()
	{
		return $this->skills;
	}
	public function stats()
	{
		return $this->stats;
	}
	public function url()
	{
		if(isset($this->url) === false)
		{
			$this->url = sprintf(constant(get_class($this) . '::sprintf_url'),$this->uid());
		}
		return $this->url;
	}
	public function image_url()
	{
		return sprintf(constant(get_class($this) . '::sprintf_img'),$this->img());
	}
	public static function affiliations_label()
	{
		return null;
	}
	protected static function get_widgets($metaverse,array $args)
	{
		if(mv_id_plugin::nice_name($metaverse) === false)
		{
			return;
		}
		static $get_sql;
		if(isset($get_sql) === false)
		{
			$get_sql = 'SELECT cache FROM ' . mv_id_plugin::db_tablename() . ' WHERE metaverse = %s AND cache IS NOT NULL';
		}
		global $wpdb;
		$vcards = $wpdb->get_results($wpdb->prepare($get_sql,$metaverse));
		if(empty($vcards) === false)
		{
			extract($args);
			echo $before_widget,$before_title,htmlentities2(mv_id_plugin::nice_name($metaverse)),$after_title,"\n";
			$mv_id_hashes = array();
			foreach($vcards as $k=>$vcard)
			{
				if(isset($vcard->cache) === false)
				{
					continue;
				}
				$vcard->cache = unserialize($vcard->cache);
				$mv_id_hash = get_class($vcard->cache) . '::' . $vcard->cache->uid();
				if(in_array($mv_id_hash,$mv_id_hashes) === false)
				{
					$mv_id_hashes[] = $mv_id_hash;
					do_action('mv_id_plugin__output_vcard',$vcard->cache);
				}
			}
			echo $after_widget,"\n";
		}
	}
}

?>