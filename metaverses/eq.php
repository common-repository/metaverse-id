<?php
/*
Plugin Name: MV ID::EverQuest
Plugin URI: http://signpostmarv.name/mv-id/
Description: Display your EverQuest Identity. Requires <a href="http://signpostmarv.name/mv-id/">Metaverse ID</a>.
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
class mv_id_vcard_eq extends mv_id_vcard
{
	const plugin_nice_name = 'EverQuest';
	const plugin_metaverse = 'EQ';
	const sprintf_url = 'http://eqplayers.station.sony.com/character_profile.vm?characterId=%1$s';
	const sprintf_img = '%s';

	const sprintf_description = '%1$s is a level %2$u %3$s %4$s.';

	const regex_class = '/^(\w+)[\ ]{1,2}\((\d+)\)$/';

	public static function register_metaverse()
	{
		mv_id_plugin::register_metaverse(self::plugin_nice_name,self::plugin_metaverse,'mv_id_vcard_eq');
	}
	public static function id_format()
	{
		return 'Character ID';
	}
	public static function is_id_valid($id)
	{
		return (bool)preg_match('/^(\d+)$/',$id);
	}
	public static function affiliations_label()
	{
		return 'Guild';
	}
	public static function factory($id,$last_mod=false)
	{
		if(self::is_id_valid($id) === false)
		{
			return false;
		}
		else
		{
			$url = sprintf(self::sprintf_url,$id);
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
				$stats  = array();
				$skills = array();
				$guilds = array();
				$xpath = mv_id_plugin::XPath($doc,'//span[@class="innerContentTitleField"]');
				if($xpath instanceof DOMNodeList)
				{
					$name  = trim($xpath->item(0)->nodeValue);
					$level = self::xpath_data($id,$doc,'Level');
					if($level === false)
					{
						return false;
					}
					$class = self::xpath_data($id,$doc,'Class');
					if($class === false)
					{
						return false;
					}
					$race = self::xpath_data($id,$doc,'Race');
					if($race === false)
					{
						return false;
					}
					$xpath = mv_id_plugin::XPath($doc,'//td[text()="Guild"]');
					if($xpath instanceof DOMNodeList)
					{
						$xpath = $xpath->item(0)->nextSibling->nextSibling;
						$guild_name = $xpath->nodeValue;
						$guild_id = $xpath->firstChild->firstChild->attributes->getNamedItem('href')->nodeValue;
						$guild_id = substr($guild_id,strrpos($guild_id,'=') + 1);
						$guilds[] = new mv_id_vcard_affiliation($guild_name,sprintf('http://eqplayers.station.sony.com/guild_profile.vm?guildId=%1$s',$guild_id),sprintf('http://eqplayers.station.sony.com/guildbanner/?guildId=%1$s',$guild_id),null,$guild_id);
					}
					$xpath = mv_id_plugin::XPath($doc,'//table[@style="background-image: url(/images/paperdoll/slots.gif); background-repeat:no-repeat;"]');
					if($xpath instanceof DOMNodeList)
					{
						$image = $xpath->item(0)->parentNode->attributes->getNamedItem('style')->nodeValue;
						$image = substr($image,strpos($image,'(') + 1);
						$image = substr($image,0,strpos($image,'JPG') + 3);
					}
					else
					{
						mv_id_plugin::report_problem('Could not find image for EQ profile \'' . $id . '\'.');
						return false;
					}
					$description = sprintf(self::sprintf_description,$name,$level,$race,$class);
					return new self($id,$name,$image,$description,null,null,$guilds,null);
				}
				else
				{
					mv_id_plugin::report_problem('XPath query for character server name did not return an instance of DOMNodeList when attempting to fetch EQ2 profile \'' . $id . '\'.');
					return false;
				}
				return new self(sprintf('%s_of_%s',$name,$realm),$name,null,$description,$url,$stats,$guild ? array($guild) : null);
			}
			else
			{
				mv_id_plugin::report_problem('DOMDocument could not parse document for EQ2 profile \'' . (string)$id . '\'.');
				return false;
			}
		}
	}
	protected static function xpath_data($id,DOMDocument $doc,$data)
	{
		$xpath_q = sprintf('//td[text()="%1$s"]',$data);
		$xpath = mv_id_plugin::XPath($doc,$xpath_q);
		if($xpath instanceof DOMNodeList)
		{
			$xpath = $xpath->item(0)->nextSibling->nextSibling->firstChild->nextSibling;
			return $xpath->nodeValue;
		}
		else
		{
			mv_id_plugin::report_problem('XPath query for \'' . $data . '\' of EQ profile \'' . $id . '\' did not return an instance of DOMNodeList.');
			return false;
		}
	}
	public static function get_widget(array $args)
	{
		self::get_widgets(self::plugin_metaverse,$args);
	}
}
add_action('mv_id_plugin__register_metaverses','mv_id_vcard_eq::register_metaverse');
?>