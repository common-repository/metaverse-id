<?php
/*
Plugin Name: MV ID::Lord of the Rings Online
Plugin URI: http://signpostmarv.name/mv-id/
Description: Display your LOTRO Identity. Requires <a href="http://signpostmarv.name/mv-id/">Metaverse ID</a>.
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
class mv_id_vcard_lotro extends mv_id_vcard
{
	const sprintf_url = '#';
	const sprintf_img = '%s';
	const sprintf_description = '\'%1$s of %2$s\' is a level %3$u %6$s %4$s who hails from %5$s.';
	const sprintf_kinship_url = 'http://my.lotro.com/kinship-%s-%s/';
	public static function register_metaverse()
	{
		mv_id_plugin::register_metaverse('Lord of the Rings Online','lotro','mv_id_vcard_lotro');
	}
	public static function id_format()
	{
		return '\'Username of Realm\', e.g. \'Foo of Bar\'';
	}
	public static function is_id_valid($id)
	{
		return (bool)preg_match('/^(\w+)\ (of|\-) (\w+)$/',$id);
	}
	protected static function format_image_url($sprintf_url,$genderId,$raceId,$classId,$level)
	{
		return sprintf($sprintf_url,(($level == 80) ? '80' : 'default'),$genderId,$raceId,$classId);
	}
	public static function affiliations_label()
	{
		return 'Kinship';
	}
	public static function factory($id,$last_mod=false)
	{
		if(self::is_id_valid($id) === false)
		{
			return false;
		}
		else
		{
			list($name,$realm) = explode(' of ',$id);
			$url = sprintf('http://my.lotro.com/character/%s/%s/',strtolower($realm),strtolower($name));
			$name = trim($name);
			$curl_opts = array();
			if($last_mod !== false)
			{
				$curl_opts['headers'] = array(
					'If-Modified-Since'=>$last_mod,
				);
			}
			$data = mv_id_plugin::curl(
				$url,
				$curl_opts
			);
			if($data === true)
			{
				return true;
			}
			$doc = mv_id_plugin::DOMDocument($data);
			if($doc instanceof DOMDocument)
			{
				unset($data);
				$xpath = mv_id_plugin::XPath($doc,'./head/title');
				if($xpath instanceof DOMNodeList)
				{
					list($name,$realm) = explode(' - ',trim($xpath->item(0)->nodeValue));
				}
				else
				{
					return false;
				}
				$xpath = mv_id_plugin::XPath($doc,'//td[@id="pprofile_avatar"]/div[@class="avatar"]/img[@class="avatar"]');
				if($xpath instanceof DOMNodeList)
				{
					$image = $xpath->item(0)->getAttribute('src');
				}
				else
				{
					return false;
				}
				$xpath = mv_id_plugin::XPath($doc,'//div[@id="char_race"]');
				if($xpath instanceof DOMNodeList)
				{
					$race = $xpath->item(0)->nodeValue;
					if($race === 'Race of Man')
					{
						$race = 'Human';
					}
				}
				else
				{
					return false;
				}
				$xpath = mv_id_plugin::XPath($doc,'//div[@id="char_nat"]');
				if($xpath instanceof DOMNodeList)
				{
					$nat = $xpath->item(0)->nodeValue;
				}
				else
				{
					return false;
				}
				$xpath = mv_id_plugin::XPath($doc,'//div[@id="char_class"]');
				if($xpath instanceof DOMNodeList)
				{
					$class = $xpath->item(0)->nodeValue;
				}
				else
				{
					return false;
				}
				$xpath = mv_id_plugin::XPath($doc,'//div[@id="char_level"]');
				if($xpath instanceof DOMNodeList)
				{
					$level = $xpath->item(0)->nodeValue;
				}
				else
				{
					return false;
				}
				$xpath = mv_id_plugin::XPath($doc,'//a[starts-with(@href,"http://my.lotro.com/kinship-elendilmir")]',true);
				if($xpath instanceof DOMNodeList)
				{
					if($xpath->length !== 1)
					{
						$kinship = false;
					}
					else
					{
						$kinship = $xpath->item(0)->nodeValue;
					}
				}
				else
				{
					return false;
				}
				if($kinship !== false)
				{
					$kinship = new mv_id_vcard_affiliation($kinship,sprintf(self::sprintf_kinship_url,$realm,str_replace(' ','_',strtolower($kinship))));
				}
				$description = sprintf(self::sprintf_description,$name,$realm,$level,$class,$nat,$race);
				return new self(sprintf('%s_of_%s',$name,$realm),$name,$image,$description,$url,null,$kinship ? array($kinship) : null);
			}
			else
			{
				return false;
			}
		}
	}
	public static function get_widget(array $args)
	{
		self::get_widgets('lotro',$args);
	}
}
add_action('mv_id_plugin__register_metaverses','mv_id_vcard_lotro::register_metaverse');
?>