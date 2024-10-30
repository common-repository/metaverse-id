<?php
/*
Plugin Name: MV ID::Champions Online
Plugin URI: http://signpostmarv.name/mv-id/
Description: Display your Champions Online Identity. Requires <a href="http://signpostmarv.name/mv-id/">Metaverse ID</a>.
Version: 1.1
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
class mv_id_vcard_champions extends mv_id_vcard
{
	const plugin_nice_name = 'Champions Online';
	const plugin_metaverse = 'CO';
	const regex_id = '/^(\d+)$/';

	const sprintf_url = 'http://champions-online.com/character_profiles/%1$u';
	const sprintf_url_bio  = 'http://champions-online.com/character_profiles/%1$u/biography';
	const sprintf_url_img  = 'http://champions-online.com/%2$s';

	const xpath_get_name   = '//div[@id="profile_champ"]/div[@class="name"]';
	const xpath_get_level  = '//div[@id="profile_level_num"]/div';
	const xpath_get_xp     = '//div[@id="profile_level_num"]/span';
	const xpath_get_img    = '//img[@id="charPic"]';
	const xpath_get_bio    = '//div[@class="statsBioText"]';

	public static function register_metaverse()
	{
		mv_id_plugin::register_metaverse(self::plugin_nice_name,self::plugin_metaverse,'mv_id_vcard_champions');
	}
	public static function is_id_valid($id)
	{
		return (bool)preg_match(self::regex_id,$id);
	}
	public static function id_format()
	{
		return 'Your profile ID (an integer).';
	}
	public static function factory($id,$last_mod=false)
	{
		$curl_opts = array();
		if($last_mod !== false)
		{
			$curl_opts['headers'] = array(
				'If-Modified-Since'=>$last_mod,
			);
		}
		$url = sprintf(self::sprintf_url,$id);
		$data = mv_id_plugin::curl(
			$url,
			$curl_opts
		);
		$doc = mv_id_plugin::DOMDocument($data);
		if($doc instanceof DOMDocument)
		{
			$xpath = mv_id_plugin::XPath($doc,self::xpath_get_name);
			if($xpath instanceof DOMNodeList)
			{
				$name = trim($xpath->item(0)->nodeValue);
			}
			else
			{
				mv_id_plugin::report_problem('Could not get character name for Champions Online character ID \'' . $id . '\' (XPath query failed).');
				return false;
			}
			$xpath = mv_id_plugin::XPath($doc,self::xpath_get_level);
			if($xpath instanceof DOMNodeList)
			{
				$level = $xpath->item(0)->nodeValue;
			}
			else
			{
				mv_id_plugin::report_problem('Could not get character level for Champions Online character ID \'' . $id . '\' (XPath query failed).');
				return false;
			}
			$xpath = mv_id_plugin::XPath($doc,self::xpath_get_xp);
			if($xpath instanceof DOMNodeList)
			{
				$xp = $xpath->item(0)->nodeValue;
				$xp = strrev(implode(',',str_split(strrev($xp),3)));
			}
			else
			{
				mv_id_plugin::report_problem('Could not get character XP for Champions Online character ID \'' . $id . '\' (XPath query failed).');
				return false;
			}
			$xpath = mv_id_plugin::XPath($doc,self::xpath_get_img);
			if($xpath instanceof DOMNodeList)
			{
				$img = explode('-',$xpath->item(0)->getAttribute('src'));
				end($img);
				$img = sprintf(self::sprintf_url_img,$id,current($img));
			}
			else
			{
				mv_id_plugin::report_problem('Could not get character image for Champions Online character ID \'' . $id . '\' (XPath query failed).');
				return false;
			}
		}
		else
		{
			mv_id_plugin::report_problem('Could not get character data for Champions Online character ID \'' . $id . '\' (DOMDocument parsing failed).');
			return false;
		}
		$stats = new mv_id_stats(array(
			new mv_id_stat('Level',$level),
			new mv_id_stat('XP',$xp)
		));
		$url = sprintf(self::sprintf_url_bio,$id);
		$data = mv_id_plugin::curl(
			$url,
			$curl_opts
		);
		$doc = mv_id_plugin::DOMDocument($data);
		if($doc instanceof DOMDocument)
		{
			$xpath = mv_id_plugin::XPath($doc,self::xpath_get_bio);
			if($xpath instanceof DOMNodeList)
			{
				$desc = trim($xpath->item(0)->nodeValue);
				$desc = str_replace("\n\n","\r",$desc);
				$desc = str_replace("\n",' ',$desc);
				$desc = str_replace("\r","\n\n",$desc);
				if($desc == '')
				{
					$desc = null;
				}
			}
			else
			{
				mv_id_plugin::report_problem('Could not get character bio for Champions Online character ID \'' . $id . '\' (XPath query failed).');
				return false;
			}
		}
		else
		{
			mv_id_plugin::report_problem('Could not get character bio for Champions Online character ID \'' . $id . '\' (DOMDocument parsing failed).');
			return false;
		}
		return new self($id,$name,$img,$desc,null,$stats);
	}
	public static function get_widget(array $args)
	{
		self::get_widgets(self::plugin_metaverse,$args);
	}
	public function image_url()
	{
		return $this->img();
	}
}
add_action('mv_id_plugin__register_metaverses','mv_id_vcard_champions::register_metaverse');
?>