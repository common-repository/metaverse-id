<?php
/*
Plugin Name: MV ID::EverQuest II
Plugin URI: http://signpostmarv.name/mv-id/
Description: Display your EverQuest II Identity. Requires <a href="http://signpostmarv.name/mv-id/">Metaverse ID</a>.
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
class mv_id_vcard_eq2 extends mv_id_vcard
{
	const plugin_nice_name = 'EverQuest II';
	const plugin_metaverse = 'EQ2';
	const sprintf_url = 'http://eq2players.station.sony.com/characters/character_profile.vm?characterId=%1$u';
	const sprintf_img = '%s';

	const regex_class = '/^(\w+)[\ ]{1,2}\((\d+)\)$/';

	public static function register_metaverse()
	{
		mv_id_plugin::register_metaverse(self::plugin_nice_name,self::plugin_metaverse,'mv_id_vcard_eq2');
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
				$stats = array();
				$skills = array();
				$xpath = mv_id_plugin::XPath($doc,'//a[starts-with(@href,"/servers/server_profile.vm")]');
				if($xpath instanceof DOMNodeList)
				{
					$server = trim($xpath->item(0)->nodeValue);
					$bday = self::xpath_data($id,$doc,'Created',true);
					if($bday === false)
					{
						return false;
					}
					$stats[] = new mv_id_stat('bday',trim($bday));
					$race = self::xpath_data($id,$doc,'Race');
					if($race === false)
					{
						return false;
					}
					$race = trim($race);
					$adventure_class = self::xpath_data($id,$doc,'Adventure Class',2);
					if($adventure_class === false)
					{
						return false;
					}
					else if(preg_match(self::regex_class,trim($adventure_class),$matches) !== 1)
					{
						mv_id_plugin::report_problem('Could not extract class level from Adventure class for EQ2 profile \'' . $id . '\'.');
						return false;
					}
					list($adventure_class,$adventure_type,$adventure_level) = $matches;
					$skills[] = new mv_id_skill($adventure_type,(int)$adventure_level,sprintf('http://eq2.wikia.com/wiki/%s',$adventure_type));
					$artisan_class = self::xpath_data($id,$doc,'Artisan Class');
					if($artisan_class === false)
					{
						return false;
					}
					else if(preg_match(self::regex_class,trim($artisan_class),$matches) !== 1)
					{
						mv_id_plugin::report_problem('Could not extract class level from Artisan class for EQ2 profile \'' . $id . '\'.');
						return false;
					}
					list($artisan_class,$artisan_type,$artisan_level) = $matches;
					$skills[] = new mv_id_skill($artisan_type,(int)$artisan_level,sprintf('http://eq2.wikia.com/wiki/%s',$artisan_type));
					$secondary_tradeskill = self::xpath_data($id,$doc,'Secondary Tradeskill');
					if($secondary_tradeskill === false)
					{
						return false;
					}
					$secondary_tradeskill = trim($secondary_tradeskill);
					$stats[] = new mv_id_stat('Secondary Tradeskill',$secondary_tradeskill);
					$city_alignment = self::xpath_data($id,$doc,'City Alignment');
					if($city_alignment === false)
					{
						return false;
					}
					$city_alignment = trim($city_alignment);
					$stats[] = new mv_id_stat('City Alignment',$city_alignment);
					$xpath = mv_id_plugin::XPath($doc,'//a[starts-with(@href,"/guilds/guild_profile.vm")]');
					$guild = null;
					if($xpath instanceof DOMNodeList)
					{
						$guild_name = trim($xpath->item(0)->nodeValue);
						if(strlen($guild_name) > 0)
						{
							$guild_id = $xpath->item(0)->attributes->getNamedItem('href')->nodeValue;
							$guild_id = substr($guild_id,strrpos($guild_id,'=') + 1);
							$guild = array(new mv_id_vcard_affiliation($guild_name,sprintf('http://eq2players.station.sony.com/guilds/guild_profile.vm?guildId=%1$u',$guild_id),null,null,$guild_id));
						}
					}
					$xpath = mv_id_plugin::XPath($doc,'//span[@class="boxSubHeader"]');
					if($xpath instanceof DOMNodeList)
					{
						$title = null;
						$name = $xpath->item(0)->nodeValue;
						$name = str_split($name);
						foreach($name as $k=>$v)
						{
							if(ord($v) === 194)
							{
								$name[$k] = ' ';
							}
							else if(ord($v) === 160)
							{
								$name[$k] = '';
							}
						}
						$name = implode('',$name);
						if(strpos($name,',') !== false)
						{
							$name = explode(',',$name);
							$title = trim($name[1]);
							$name = $name[0];
						}
						$name = trim($name);
					}
					else
					{
						mv_id_plugin::report_problem('Could not find name for EQ2 profile \'' . $id . '\'.');
						return false;
					}
					$xpath = mv_id_plugin::XPath($doc,'//img[starts-with(@src,"http://eq2images.station.sony.com/")]');
					if($xpath instanceof DOMNodeList)
					{
						$image = $xpath->item(0)->attributes->getNamedItem('src')->nodeValue;
					}
					else
					{
						mv_id_plugin::report_problem('Could not find image for EQ2 profile \'' . $id . '\'.');
						return false;
					}
					if(isset($title))
					{
						$description_q = '%1$s, %2$s is a level %4$u %3$s %5$s who is allied with %6$s.';
					}
					else
					{
						$description_q = '%1$s is a level %4$u %3$s %5$s who is allied with %6$s.';
					}
					$description = sprintf($description_q,$name,$title,$adventure_type,$adventure_level,$race,$city_alignment);
					$stats = new mv_id_stats($stats);
					return new self($id,$name,$image,$description,null,$stats,$guild,$skills);
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
	protected static function xpath_data($id,DOMDocument $doc,$data,$nobr=false)
	{
		if($nobr !== false)
		{
			$nobr = (integer)$nobr;
			$_nobr = substr(str_repeat('nobr/',$nobr),0,-1);
			$xpath_q = sprintf('//%2$s[text()="%1$s"]',$data,$_nobr);
		}
		else
		{
			$xpath_q = sprintf('//td[text()="%1$s"]',$data);
		}
		$xpath = mv_id_plugin::XPath($doc,$xpath_q);
		if($xpath instanceof DOMNodeList)
		{
			if($nobr)
			{
				$xpath = $xpath->item(0);
				while($nobr > 0)
				{
					$xpath = $xpath->parentNode;
					--$nobr;
				}
				while($xpath->nextSibling->nodeName !== 'td')
				{
					$xpath = $xpath->nextSibling;
				}
				return $xpath->nextSibling->nodeValue;
			}
			else
			{
				$xpath = $xpath->item(0);
				while($xpath->nextSibling->nodeName !== 'td')
				{
					$xpath = $xpath->nextSibling;
				}
				return $xpath->nextSibling->nodeValue;
			}
		}
		else
		{
			mv_id_plugin::report_problem('XPath query for \'' . $data . '\' of EQ2 profile \'' . $id . '\' did not return an instance of DOMNodeList.');
			return false;
		}
	}
	public static function get_widget(array $args)
	{
		self::get_widgets(self::plugin_metaverse,$args);
	}
}
add_action('mv_id_plugin__register_metaverses','mv_id_vcard_eq2::register_metaverse');
?>