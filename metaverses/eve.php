<?php
/*
Plugin Name: MV ID::EVE Online
Plugin URI: http://signpostmarv.name/mv-id/
Description: Display your EVE Online Identity. Requires <a href="http://signpostmarv.name/mv-id/">Metaverse ID</a>.
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
class mv_id_vcard_eve extends mv_id_vcard implements mv_id_needs_admin
{
	const sprintf_url         = 'http://www.eveonline.com/character/skilltree.asp?characterID=%u';
	const sprintf_img         = '#';
	const sprintf_description = '%1$s is a %2$s %3$s %4$s.';
	public static function register_metaverse()
	{
		mv_id_plugin::register_metaverse('EVE Online','EVE','mv_id_vcard_eve');
	}
	public static function id_format()
	{
		return 'character ID';
	}
	public static function is_id_valid($id)
	{
		return (bool)preg_match('/^(\d+)$/',$id);
	}
	public static function affiliations_label()
	{
		return 'Corporation';
	}
	public static function get_widget(array $args)
	{
		self::get_widgets('EVE',$args);
	}
	public static function admin_fields()
	{
		static $fields = array(
			'userID'=>array(
				'name'  => 'User ID',
				'regex' => '/^(\d+)$/',
			),
			'apiKey'=>array(
				'name'  => 'API Key',
				'regex' => '/^([\w\d]+)$/',
			),
		);
		return $fields;
	}
	public static function factory($id,$last_mod=false)
	{
		if(self::is_id_valid($id) === false || ($config = get_option('mv-id::EVE')) === false)
		{
			if(self::is_id_valid($id) === false){
				mv_id_plugin::report_problem('EVE Online ID is invalid');
			}else{
				mv_id_plugin::report_problem('EVE API details missing');
			}
			return false;
		}
		else
		{
			$url = 'http://api.eve-online.com/char/CharacterSheet.xml.aspx';
			$config = unserialize($config);
			$config['characterID'] = $id;
			$curl_opts = array('user-agent' => 'This Is Not Firefox/3.0.10','method'=>'post','body'=>$config);
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
			if($data === true){
				return true;
			}
			if((($XML = mv_id_plugin::SimpleXML($data)) instanceof SimpleXMLElement) === false){
				mv_id_plugin::report_problem('Could not parse EVE API response as XML');
				return false;
			}
			else
			{
				if(mv_id_plugin::XPath($XML, '//error[@code="106"]') !== false){
					mv_id_plugin::report_problem('API Authentication failed, userID paramter was not passed to API');
					return false;
				}else if(mv_id_plugin::XPath($XML, '//error[@code="201"]') !== false){
					mv_id_plugin::report_problem('Could not fetch character sheet, character does not belong to account');
					return false;
				}

				$info['name']            = mv_id_plugin::XPath($XML,'//result/name');
				$info['gender']          = mv_id_plugin::XPath($XML,'//result/gender');
				$info['race']            = mv_id_plugin::XPath($XML,'//result/race');
				$info['bloodLine']       = mv_id_plugin::XPath($XML,'//result/bloodLine');
				foreach($info as $k=>$v){
					if($v){
						$info[$k] = trim((string)$v[0]);
					}else{
						mv_id_plugin::report_problem('EVE API response is missing required information');
						return false;
					}
				}
				$corp['name'] = mv_id_plugin::XPath($XML,'//result/corporationName');
				$corp['uid']  = mv_id_plugin::XPath($XML,'//result/corporationID');
				foreach($corp as $k=>$v)
				{
					if($v)
					{
						$corp[$k] = (string)$v[0];
					}
					else
					{
						$corp = null;
						break;
					}
				}
				if($corp)
				{
					$corp = new mv_id_vcard_affiliation($corp['name'],false,null,null,$corp['uid']);
				}
				$description = sprintf(self::sprintf_description,$info['name'],strtolower($info['gender']),$info['race'],$info['bloodLine']);
				return new self($id,$info['name'],null,$description,null,null,$corp ? array($corp) : $corp);
			}
		}
	}
}
add_action('mv_id_plugin__register_metaverses','mv_id_vcard_eve::register_metaverse');
?>