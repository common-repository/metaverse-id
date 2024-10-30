<?php
/*
Plugin Name: MV ID::Star Trek Online
Plugin URI: http://signpostmarv.name/mv-id/
Description: Display your Star Trek Online Identity. Requires <a href="http://signpostmarv.name/mv-id/">Metaverse ID</a>.
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
class mv_id_vcard_star_trek_online extends mv_id_vcard{
	const plugin_nice_name = 'Star Trek Online';
	const plugin_metaverse = 'STO';
	const regex_id = '/^(\d+)$/';

	const sprintf_url = 'http://startrekonline.com/character_profiles/%1$u/view';

	const xpath_get_name = '//div[@id="profile_headshot"]/img';

	public static function register_metaverse()
	{
		mv_id_plugin::register_metaverse(self::plugin_nice_name,self::plugin_metaverse,'mv_id_vcard_star_trek_online');
	}
	public static function is_id_valid($id)
	{
		return (bool)preg_match(self::regex_id,$id);
	}
	public static function id_format()
	{
		return 'Your profile ID (an integer).';
	}
	public static function get_widget(array $args)
	{
		self::get_widgets(self::plugin_metaverse,$args);
	}
	public function image_url()
	{
		return $this->img();
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
		if(($doc instanceof DOMDocument) === false){
			mv_id_plugin::report_problem('Could not parse character profile for Star Trek Online character ID \'' . $id . '\'');
			return false;
		}else{
			$xpath = mv_id_plugin::XPath($doc, self::xpath_get_name);
			if(($xpath instanceof DOMNodeList) === false){
				mv_id_plugin::report_problem('Could not find Captain\'s name');
				return false;
			}else{
				$name = trim($xpath->item(0)->getAttribute('title'));
				$img = 'http://startrekonline.com' . $xpath->item(0)->getAttribute('src');
			}

			$xpath = mv_id_plugin::XPath($doc, '//div[@id="profile_bio_content"]');
			if(($xpath instanceof DOMNodeList) === false){
				mv_id_plugin::report_problem('Could not find captain\'s biography');
				return false;
			}else{
				$desc = trim($xpath->item(0)->nodeValue);
				if($desc === 'This character has no biography.'){
					$desc = null;
				}
			}
			$stats = array();
			$xpath = mv_id_plugin::XPath($doc, '//table[@class="profile_table"]/tr');
			if($xpath instanceof DOMNodeList){
				for($x=0;$x<$xpath->length;++$x){
					$stats[] = new mv_id_stat(str_replace(':','',$xpath->item($x)->childNodes->item(0)->nodeValue), $xpath->item($x)->childNodes->item(1)->nodeValue);
				}
			}
			$xpath = mv_id_plugin::XPath($doc, '//img[@id="profile_ship_image"]');
			if($xpath instanceof DOMNodeList){
				$stats[] = new mv_id_stat('Ship', $xpath->item(0)->getAttribute('title'));
			}
			$stats = new mv_id_stats($stats);
			return new self($id,$name,$img,$desc,null,$stats);
		}
	}
}
add_action('mv_id_plugin__register_metaverses','mv_id_vcard_star_trek_online::register_metaverse');