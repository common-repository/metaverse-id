<?php
/*
Plugin Name: MV ID::Second Life
Plugin URI: http://signpostmarv.name/mv-id/
Description: Display your Second Life Identity. Requires <a href="http://signpostmarv.name/mv-id/">Metaverse ID</a>.
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
class mv_id_vcard_agni_sl extends mv_id_vcard
{
	const regex_sl_id = '/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})$/S';
	const sprintf_url = 'http://world.secondlife.com/resident/%1$s';
	const sprintf_img = 'http://secondlife.com/app/image/%1$s/3';
	const sprintf_scrape = 'http://world.secondlife.com/resident/%1$s';
	const string_error_Resident_not_exist   = '<Error><Code>NoSuchKey</Code><Message>The specified key does not exist.</Message>';
	const string_error_aws_internal         = '<Error><Code>InternalError</Code><Message>We encountered an internal error. Please try again.</Message>';
	const string_error_service_unavailable  = '<html><body><b>Http/1.1 Service Unavailable</b></body> </html>';
	const string_cond_web_profile_blocked   = 'This resident has chosen to hide their profile from search';
	const xpath_get_name                    = '//h1[@class="resident"]';
	const xpath_get_rezday                  = '//span[@class="syscat"]';
	const xpath_get_description             = '//meta[@name="description"]';
	const regex_get_avatar                  = '/<img\ alt="profile\ image"\ src="http:\/\/secondlife\.com\/app\/image\/([\w\d\-]{36})\/1"\ class="parcelimg"\ \/>/S';
	const string_no_avatar                  = '<img alt="profile image" src="http://world.secondlife.com/images/blank.jpg" class="parcelimg" />';
	public static function register_metaverse()
	{
		mv_id_plugin::register_metaverse('Second Life','agni SL','mv_id_vcard_agni_sl');
	}
	public static function is_id_valid($id)
	{
		return (bool)preg_match(self::regex_sl_id,$id);
	}
	public static function id_format()
	{
		return 'Your avatar UUID.';
	}
	protected static function scrape($url,$last_mod=false)
	{
		$data = mv_id_plugin::curl(
			$url,
			$curl_opts
		);
		if($data === true)
		{
			return $data;
		}
		if(strpos($data,self::string_error_Resident_not_exist) !== false)
		{
			mv_id_plugin::report_problem('The Resident you specified does not appear to exist');
			return null;
		}
		else if(strpos($data,self::string_error_aws_internal) !== false)
		{
			mv_id_plugin::report_problem('Problem with AWS. Please try again later.');
			return null;
		}
		else if(strpos($data,self::string_error_service_unavailable) !== false)
		{
			mv_id_plugin::report_problem('AWS Unavailable. Please try again later.');
			return null;
		}
		else if(strpos($data,self::string_cond_web_profile_blocked) !== false)
		{
			mv_id_plugin::report_problem('The Resident you specified has their profile blocked from search');
			return null;
		}
		else
		{
			$doc = mv_id_plugin::DOMDocument($data);
			if(($doc instanceof DOMDocument) === false){
				mv_id_plugin::report_problem('Could not parse remote document for Second Life identity.');
				return false;
			}
			$xpath = mv_id_plugin::XPath($doc, self::xpath_get_name);
			if($xpath instanceof DOMNodeList){
				$name = trim($xpath->item(0)->nodeValue);
			}else{
				mv_id_plugin::report_problem('Could not find Resident name');
				return false;
			}
			$stats = array();
			$xpath = mv_id_plugin::XPath($doc, self::xpath_get_rezday);
			if($xpath instanceof DOMNodeList){
				$rezday = explode('-', substr(trim($xpath->item(0)->nextSibling->nodeValue),0,10));
				$stats[] = new mv_id_stat('bday', $rezday[0] . '-' . $rezday[2] . '-' . $rezday[1]);
			}else{
				mv_id_plugin::report_problem('Could not find rezday');
			}
			$image = null;
			if(strpos($data,self::string_no_avatar) !== false)
			{
				$image = '00000000-0000-0000-0000-000000000000';
			}
			else if(preg_match(self::regex_get_avatar,$data,$matches) === 1)
			{
				$image = $matches[1];
			}
			$xpath = mv_id_plugin::XPath($doc, self::xpath_get_description);
			$description = null;
			if($xpath instanceof DOMNodeList){
				$description = $xpath->item(0)->getAttribute('content');
				if(trim($description) === ''){
					$description = null;
				}
			}
			return array($name,$image,$description,$url,$stats);
		}
	}
	public static function factory($id,$last_mod=false)
	{
		$data = self::scrape(sprintf(self::sprintf_scrape,$id),$last_mod);
		if(isset($data))
		{
			if(is_array($data))
			{
				$stats = (isset($data[4]) && empty($data[4]) === false) ? new mv_id_stats($data[4]) : null;
				return new self($id,$data[0],$data[1],$data[2],$data[3],$stats);
			}
			else
			{
				return $data;
			}
		}
		else
		{
			return $data;
		}
	}
	public static function get_widget(array $args)
	{
		self::get_widgets('agni SL',$args);
	}
}
class mv_id_vcard_teen_sl extends mv_id_vcard_agni_sl
{
	const sprintf_url = 'http://world.secondlife.com/resident/%1$s';
	const sprintf_scrape = 'http://world.secondlife.com/resident/%1$s';
	public static function register_metaverse()
	{
		mv_id_plugin::register_metaverse('Teen SL','teen SL','mv_id_vcard_teen_sl');
	}
	public static function factory($id,$last_mod=false)
	{
		$data = self::scrape(sprintf(self::sprintf_scrape,$id),$last_mod);
		if(isset($data))
		{
			if(is_array($data))
			{
				$stats = (isset($data[4]) && empty($data[4]) === false) ? new mv_id_stats($data[4]) : null;
				return new self($id,$data[0],$data[1],$data[2],$data[3],$stats);
			}
			else
			{
				return $data;
			}
		}
		else
		{
			return $data;
		}
	}
	public static function get_widget(array $args)
	{
		self::get_widgets('teen SL',$args);
	}
}

function filter__mv_id_secondlife_display_name($hresume, mv_id_vcard $vcard){
	if($vcard instanceof mv_id_vcard_agni_sl){
		if(preg_match('/^(.+) \(([A-z\d]+\.[A-z\d]+)\)$/',$vcard->name(),$matches) === 1){
			$hresume = str_replace('>' . $vcard->name() . '</a>', '><span title="' . esc_attr($matches[2]) . '">' . esc_html($matches[1]) . '</span></a>', $hresume);
		}
	}
	return $hresume;
}
add_action('mv_id_plugin__register_metaverses','mv_id_vcard_agni_sl::register_metaverse');
add_action('mv_id_plugin__register_metaverses','mv_id_vcard_teen_sl::register_metaverse');

add_filter('post_output_mv_id_vcard', 'filter__mv_id_secondlife_display_name', 10, 2);
?>