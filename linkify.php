<?php
// This file is based on http://svn.wp-plugins.org/sem-autolink-uri/trunk/sem-autolink-uri.php (version 1.6.1, SVN revision 126988)
// The original file was made available under the GPL v2
// The original author was "Denis de Bernardy"
// The original plugin URI was "http://www.semiologic.com/software/publishing/autolink-uri/"
// The original Author URI was "http://www.getsemiologic.com"
// The original description was "Automatically wrap unhyperlinked uri with html anchors."
// The code has been refactored for PHP5, and has been turned into a filter, from the original dual action setup.
class mv_id_linkify
{
	protected static $escaped_anchors = array();
	protected static $class;
	protected static $rel;
	protected static $rev;
	protected static function escape_attr_array(array & $foo)
	{
		foreach($foo as $k=>$v)
		{
			if(is_string($v) === false)
			{
				unset($foo[$k]);
				continue;
			}
			$foo[$k] = attribute_escape($v);
		}
	}
	public static function filter($buffer,array $class=null,array $rel=null,array $rev=null)
	{
		if( @ini_get('pcre.backtrack_limit') < 250000 )
		{
			@ini_set('pcre.backtrack_limit', 250000);
		}
		self::$escaped_anchors = array();
		if(isset($class))
		{
			self::escape_attr_array($class);
		}
		if(isset($rel))
		{
			self::escape_attr_array($rel);
		}
		if(isset($rev))
		{
			self::escape_attr_array($rev);
		}
		self::$class = empty($class) ? null : implode(' ',$class);
		self::$rel   = empty($rel)   ? null : implode(' ',$rel);
		self::$rev   = empty($rev)   ? null : implode(' ',$rev);
		# escape scripts
		$buffer = preg_replace_callback(
			"/
			<\s*script				# script tag
				(?:\s[^>]*)?		# optional attributes
				>
			.*						# script code
			<\s*\/\s*script\s*>		# end of script tag
			/isUx",
			'mv_id_linkify::escape_anchors',
			$buffer
			);

		# escape objects
		$buffer = preg_replace_callback(
			"/
			<\s*object				# object tag
				(?:\s[^>]*)?		# optional attributes
				>
			.*						# object code
			<\s*\/\s*object\s*>		# end of object tag
			/isUx",
			'mv_id_linkify::escape_anchors',
			$buffer
			);

		# escape existing anchors
		$buffer = preg_replace_callback(
			"/
			<\s*a					# ancher tag
				(?:\s[^>]*)?		# optional attributes
				\s*href\s*=\s*		# href=...
				(?:
					\"[^\"]*\"		# double quoted link
				|
					'[^']*'			# single quoted link
				|
					[^'\"]\S*		# none-quoted link
				)
				(?:\s[^>]*)?		# optional attributes
				\s*>
			.*						# link text
			<\s*\/\s*a\s*>			# end of anchor tag
			/isUx",
			'mv_id_linkify::escape_anchors',
			$buffer
			);

		# escape uri within tags
		$buffer = preg_replace_callback(
			"/
			<[^>]*
				(?:
					(?:			# link starting with a scheme
						http(?:s)?
					|
						ftp
					)
					:\/\/
				|
					www\.		# link starting with no scheme
				)
				[^>]*>
			/isUx",
			'mv_id_linkify::escape_anchors',
			$buffer
			);
		# add anchors to unanchored links
		$buffer = preg_replace_callback(
			"/
			\b									# word boundary
			(
				(?:								# link starting with a scheme
					http(?:s)?
				|
					ftp
				)
				:\/\/
			|
				www\.							# link starting with no scheme
			)
			(
				(								# domain
					localhost
				|
					[0-9a-zA-Z_\-]+
					(?:\.[0-9a-zA-Z_\-]+)+
				)
				(?:								# maybe a subdirectory
					\/
					[0-9a-zA-Z~_\-+\.\/,&;]*
				)?
				(?:								# maybe some parameters
					\?[0-9a-zA-Z~_\-+\.\/,&;=]+
				)?
				(?:								# maybe an id
					\#[0-9a-zA-Z~_\-+\.\/,&;]+
				)?
			)
			/imsx",
			'mv_id_linkify::add_links',
			$buffer
			);

		# unescape anchors
		$buffer = self::unescape_anchors($buffer);

		return $buffer;
	}

	#
	# sem_autolink_uri_escape_anchors()
	#

	protected static function escape_anchors($input)
	{

	#	echo '<pre>';
	#	var_dump($input);
	#	echo '</pre>';

		$anchor_id = '--escaped_anchor:' . md5($input[0]) . '--';
		self::$escaped_anchors[$anchor_id] = $input[0];

		return $anchor_id;
	} # end sem_autolink_uri_escape_anchors()

	#
	# sem_autolink_uri_unescape_anchors()
	#

	protected static function unescape_anchors($input)
	{
		$find = array();
		$replace = array();

		foreach (self::$escaped_anchors as $key => $val )
		{
			$find[] = $key;
			$replace[] = $val;
		}

		return str_replace($find, $replace, $input);
	} # end sem_autolink_uri_unescape_anchors()


	#
	# sem_autolink_uri_add_links()
	#

	protected static function add_links($input)
	{
		$attributes = '';
		$attributes .= is_string(self::$class) ? (' class="' . self::$class . '"') : '';
		$attributes .= is_string(self::$rel) ? (' rel="' . self::$rel . '"') : '';
		$attributes .= is_string(self::$rev) ? (' rev="' . self::$rev . '"') : '';
		if ( strtolower($input[1]) == 'www.' )
		{
			return '<a'
				. $attributes
				. ' href="http://' . $input[0] . '"'
				. '>'
				. $input[0]
				. '</a>';
		}
		else
		{
			return '<a'
				. $attributes
				. ' href="' . $input[0] . '"'
				. '>'
				. $input[0]
				. '</a>';
		}
	} # end sem_autolink_uri_add_links()
}

#
# sem_autolink_uri()
#
add_filter('mv_id_linkify','mv_id_linkify::filter',20,3);
?>