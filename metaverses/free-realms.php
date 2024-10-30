<?php
/*
Plugin Name: MV ID::Free Realms
Plugin URI: http://signpostmarv.name/mv-id/
Description: Display your Free Realms Identity. Requires <a href="http://signpostmarv.name/mv-id/">Metaverse ID</a>.
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
class mv_id_vcard_freerealms extends mv_id_vcard
{
	const sprintf_url = 'http://www.freerealms.com/character/profile.action#character/%1$s';
	const sprintf_img = 'http://www.freerealms.com/%1$s';
	const sprintf_scrape = 'http://www.freerealms.com/character/profile!json.action?characterId=%1$s';
	public static function register_metaverse()
	{
		mv_id_plugin::register_metaverse('Free Realms','freerealms','mv_id_vcard_freerealms');
	}
	public static function is_id_valid($id)
	{
		return is_integer($id) ? true : ctype_digit($id);
	}
	public static function id_format()
	{
		return 'Copy & paste the number from the end of your profile URL.';
	}
	public static function factory($id,$last_mod=false)
	{
		if(self::is_id_valid($id) === false)
		{
			return false;
		}
		$curl_opts = array();
		if($last_mod !== false)
		{
			$curl_opts['headers'] = array(
				'If-Modified-Since'=>$last_mod,
			);
		}
		$data = mv_id_plugin::curl(
			sprintf(self::sprintf_scrape,$id),
			$curl_opts
		);
		if($data === true)
		{
			return true;
		}
		else
		{
			$data = json_decode($data);
		}
		if(isset($data->characterList))
		{
			$uid = $namne = $img = $description = null;
			$found = false;
			foreach($data->characterList as $character)
			{
				if($character->charId === $id)
				{
					$found = true;
					break;
				}
			}
			if($found === true)
			{
				$uid = $character->charId;
				$name = trim($character->name);
				$img = $character->headshotUrl;
				$description = $character->name . ' is a ' . strtolower($character->gender) . ' ' . $character->race . '.';
				return new self($uid,$name,$img,$description);
			}
		}
		return null;
	}
	public static function get_widget(array $args)
	{
		self::get_widgets('freerealms',$args);
	}
}
add_action('mv_id_plugin__register_metaverses','mv_id_vcard_freerealms::register_metaverse');
?>