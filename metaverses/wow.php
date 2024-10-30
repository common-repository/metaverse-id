<?php
/*
Plugin Name: MV ID::World of Warcraft
Plugin URI: http://signpostmarv.name/mv-id/
Description: Display your WoW Identity. Requires <a href="http://signpostmarv.name/mv-id/">Metaverse ID</a>.
Version: 1.0
Author: SignpostMarv Martin
Author URI: http://signpostmarv.name/
 Copyright 2009 SignpostMarv Martin  (email : mv-id.wp@signpostmarv.name)
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
if(class_exists('mv_id_vcard') === false)
{
	return;
}
abstract class mv_id_vcard_wow extends mv_id_vcard
{
	const sprintf_url = '#';
	const sprintf_img = '%s';

	protected static function classes($id){
		static $classes;
		if(isset($classes) === false){
			$_classes = json_decode('{"classes":[{"id":3,"mask":4,"powerType":"focus","name":"Hunter"},{"id":4,"mask":8,"powerType":"energy","name":"Rogue"},{"id":1,"mask":1,"powerType":"rage","name":"Warrior"},{"id":2,"mask":2,"powerType":"mana","name":"Paladin"},{"id":7,"mask":64,"powerType":"mana","name":"Shaman"},{"id":8,"mask":128,"powerType":"mana","name":"Mage"},{"id":5,"mask":16,"powerType":"mana","name":"Priest"},{"id":6,"mask":32,"powerType":"runic-power","name":"Death Knight"},{"id":11,"mask":1024,"powerType":"mana","name":"Druid"},{"id":9,"mask":256,"powerType":"mana","name":"Warlock"}]}')->classes;
			$__classes = array();
			foreach($_classes as $_class){
				$__classes[$_class->id] = $_class;
			}
			$classes = $__classes;
			unset($__classes, $_classes, $_class);
		}
		return $classes[$id];
	}

	protected static function races($id){
		static $races;
		if(isset($races) === false){
			$_races = json_decode('{"races":[{"id":11,"mask":1024,"side":"alliance","name":"Draenei"},{"id":1,"mask":1,"side":"alliance","name":"Human"},{"id":5,"mask":16,"side":"horde","name":"Undead"},{"id":7,"mask":64,"side":"alliance","name":"Gnome"},{"id":8,"mask":128,"side":"horde","name":"Troll"},{"id":2,"mask":2,"side":"horde","name":"Orc"},{"id":3,"mask":4,"side":"alliance","name":"Dwarf"},{"id":4,"mask":8,"side":"alliance","name":"Night Elf"},{"id":10,"mask":512,"side":"horde","name":"Blood Elf"},{"id":22,"mask":2097152,"side":"alliance","name":"Worgen"},{"id":6,"mask":32,"side":"horde","name":"Tauren"},{"id":9,"mask":256,"side":"horde","name":"Goblin"}]}')->races;
			$__races = array();
			foreach($_races as $_race){
				$__races[$_race->id] = $_race;
			}
			$races = $__races;
			unset($__races, $_races, $_race);
		}
		return $races[$id];
	}

	const json_classes = '{"classes":[{"id":3,"mask":4,"powerType":"focus","name":"Hunter"},{"id":4,"mask":8,"powerType":"energy","name":"Rogue"},{"id":1,"mask":1,"powerType":"rage","name":"Warrior"},{"id":2,"mask":2,"powerType":"mana","name":"Paladin"},{"id":7,"mask":64,"powerType":"mana","name":"Shaman"},{"id":8,"mask":128,"powerType":"mana","name":"Mage"},{"id":5,"mask":16,"powerType":"mana","name":"Priest"},{"id":6,"mask":32,"powerType":"runic-power","name":"Death Knight"},{"id":11,"mask":1024,"powerType":"mana","name":"Druid"},{"id":9,"mask":256,"powerType":"mana","name":"Warlock"}]}';

	public static function id_format()
	{
		return '\'Realm Username\', e.g. \'Alonsus Axilo\'';
	}
	public static function is_id_valid($id)
	{
		return (bool)preg_match('/^(\w+)\ (\w+)$/',$id);
	}
	public static function affiliations_label()
	{
		return 'Guild';
	}
	protected static function scrape($url,$last_mod=false)
	{
		if($last_mod !== false)
		{
			$curl_opts['headers'] = array(
				'If-Modified-Since'=>$last_mod,
			);
		}
		$curl_opts['headers'][CURLOPT_HTTPHEADER] = array(
			'Accept:application/json'
		);
		$data = mv_id_plugin::curl(
			$url,
			$curl_opts
		);
		if($data === true)
		{
			return true;
		}
		$data = json_decode($data);
		if(is_object($data) === false)
		{
			return false;
		}
		else
		{
			$title = '%s';
			foreach($data->titles as $_title){
				if(isset($_title->selected) && $_title->selected === true){
					$title = $_title->name;
					break;
				}
			}
			$name = sprintf($title, $data->name);
			$description = $data->name . ' is a level ' . $data->level . ' ' . ($data->gender === 1 ? 'female' : 'male') . ' ' . static::races($data->race)->name . ' ' . static::classes($data->class)->name . ', and can be found on the ' . $data->realm . ' realm.';
			$url = sprintf('http://battle.net/wow/en/character/%1$s/%2$s/simple', $data->realm, $data->name);
			$skills = array(
				new mv_id_skill('Achievement Points',(int)$data->achievementPoints,'http://www.wowpedia.org/Achievement')
			);
			if(isset($data->professions) === true){
				if(isset($data->professions->primary) === true){
					foreach($data->professions->primary as $profession){
						$skills[] = new mv_id_skill($profession->name, $profession->rank, sprintf('http://battle.net/wow/profession/%s', $profession->name));
					}
				}
				if(isset($data->professions->secondary) === true){
					foreach($data->professions->secondary as $profession){
						$skills[] = new mv_id_skill($profession->name, $profession->rank, sprintf('http://battle.net/wow/profession/%s', $profession->name));
					}
				}
			}
			$guild = null;
			if(isset($data->guild) === true){
				$guild = array(new mv_id_vcard_affiliation($data->guild->name, sprintf('http://battle.net/wow/en/guild/%1$s/%2$s/', $data->realm, $data->guild->name)));
			}
			return array($name, $description, $url, $data->thumbnail, $guild, count($skills) > 1 ? $skills : null);
		}
	}
}
class mv_id_vcard_wow_eu extends mv_id_vcard_wow
{
	public static function register_metaverse()
	{
		mv_id_plugin::register_metaverse('WoW Europe','WoW EU','mv_id_vcard_wow_eu');
	}
	public static function factory($id,$last_mod=false)
	{
		if(self::is_id_valid($id) === false)
		{
			return false;
		}
		else
		{
			list($realm,$name) = explode(' ',$id);
			$data = self::scrape(sprintf('http://eu.battle.net/api/wow/character/%1$s/%2$s?fields=guild,professions,titles',$realm,$name),$last_mod);
			$name = trim($name);
			if(is_array($data))
			{
				list($name,$description,$url, $thumbnail, $guild, $skills) = $data;
				$image = 'http://eu.battle.net/static-render/eu/' . $thumbnail;
				return new self($id,$name,$image,$description,$url,null,$guild,$skills);
			}
			else
			{
				return false;
			}
		}
	}
	public static function get_widget(array $args)
	{
		self::get_widgets('WoW EU',$args);
	}
}
class mv_id_vcard_wow_us extends mv_id_vcard_wow
{
	public static function register_metaverse()
	{
		mv_id_plugin::register_metaverse('WoW US','WoW US','mv_id_vcard_wow_us');
	}
	public static function factory($id)
	{
		if(self::is_id_valid($id) === false)
		{
			return false;
		}
		else
		{
			list($realm,$name) = explode(' ',$id);
			$data = self::scrape(sprintf('http://us.battle.net/api/wow/character/%1$s/%2$s?fields=guild,professions,titles',$realm,$name),$last_mod);
			$name = trim($name);
			if(is_array($data))
			{
				list($name,$description,$url, $thumbnail, $guild, $skills) = $data;
				$image = 'http://us.battle.net/static-render/us/' . $thumbnail;
				return new self($id,$name,$image,$description,$url,null,$guild,$skills);
			}
			else
			{
				return false;
			}
		}
	}
	public static function get_widget(array $args)
	{
		self::get_widgets('WoW US',$args);
	}
}
add_action('mv_id_plugin__register_metaverses','mv_id_vcard_wow_eu::register_metaverse');
add_action('mv_id_plugin__register_metaverses','mv_id_vcard_wow_us::register_metaverse');
?>