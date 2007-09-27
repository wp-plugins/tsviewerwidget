<?php
/*
Plugin Name: TS-Viewer Widget
Plugin URI: http://www.gehirnfurz.com/tsviewerwidget/
Description: Adds a sidebar widget to display Guido van Biemen's (aka MrGuide@NL) Teamspeak Display Preview Release 3 (GPL) and uses AjaxCore 1.2.2 from http://ajaxcore.sourceforge.net to refresh it.
Author: Marc Kemper, mkemper@marc-kemper.com
Author URI: http://gehirnfurz.com
Version: 1.1
Date: 2007, September, 26
*/

/* WIDGET CONSTANTS */
define('TSW_ABSOLUTEPATH', get_bloginfo('wpurl').'/wp-content/plugins/tsviewerwidget');
define('TSW_RELATIVEPATH', '/wp-content/plugins/tsviewerwidget');
define('TSW_WIDGETTITLE', 'TS-Viewer');
define('TSW_WIDGETVERSION', 'v1.1');
define('TSW_WIDGETNAME', 'TS-Viewer Widget');
define('TSW_WIDGETREFRESH', '30000');
define('TSW_CSSPATH', TSW_ABSOLUTEPATH.'/tsviewerwidget.css');



/* Guido van Biemen's (aka MrGuide@NL) Teamspeak Display Preview Release 3 (GPL) CONSTANTS */
define('TDPR3_RELATIVEPATH', 'teamspeakdisplay/teamspeakdisplay.php');
define('TDPR3_ABSOLUTEPATH', TSW_ABSOLUTEPATH.'/teamspeakdisplay/teamspeakdisplay.php');
define('TDPR3_SERVERDRESS','localhost');
define('TDPR3_UDPPORT',8767);
define('TDPR3QUERYPORT',51234);
define('TDPR3_LIMITCHANNEL','');
define('TDPR3_FORBIDDENNICKS','()[]{}');
define('TDPR3_SHOWPLAYERSTATUS','no');
define('TDPR3_MAXDISPLAYLENGTH','30');

/* AjaxCore 1.2.2 CONSTANTS --> http://ajaxcore.sourceforge.net/ <-- */
/**
 *						AjaxCore 1.2.2
 *				http://ajaxcore.sourceforge.net/
 *
 *  AjaxCore is a PHP framework that aims the ease development of rich 
 *  AJAX applications, using Prototype's JavaScript standard library.
 *  
 *  Copyright 2007 Mauro Niewolski (niewolski@users.sourceforge.net)
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
define('AJAX_RELATIVEPATH', 'ajaxcore/AjaxCore.class.php');
define('AJAX_ABOLUTEPATH', TSW_ABSOLUTEPATH.'/ajaxcore/AjaxCore.class.php');
define('AJAX_JSPROTOPATH', TSW_ABSOLUTEPATH.'/ajaxcore/prototype.js');
define('AJAX_JSCOREPATH', TSW_ABSOLUTEPATH.'/ajaxcore/AjaxCore.js');
define('AJAX_ELEMENTID', 'teamspeakdisplay');

require_once(AJAX_RELATIVEPATH);
class AjaxTSW extends AjaxCore
{
	function AjaxTSW()
	{	
		$this->setup();
		parent::AjaxCore();
	}
	
	function setup()
	{
		$this->setCurrentFile("tsviewerwidget.php");
		$this->setPlaceHolder(AJAX_ELEMENTID);
	}
		
	function Displayteamspeak()
	{			
		require(TDPR3_RELATIVEPATH);
		$wp_options = get_option('widget_tsviewer');

		$tsw_hosturl		= (!empty($wp_options['tsw_hosturl']))			?($wp_options['tsw_hosturl'])		:(TSW_ABSOLUTEPATH);
		$tsw_servername		= (!empty($wp_options['tsw_servername']))		?($wp_options['tsw_servername'])	:(TDPR3_SERVERDRESS);
		$tsw_udpport		= (!empty($wp_options['tsw_udpport']))			?($wp_options['tsw_udpport'])		:(TDPR3_UDPPORT);
		$tsw_queryport		= (!empty($wp_options['tsw_queryport']))		?($wp_options['tsw_queryport'])		:(TDPR3QUERYPORT);
		$tsw_limitchannel	= (!empty($wp_options['tsw_limitchannel']))		?($wp_options['tsw_limitchannel'])	:(TDPR3_LIMITCHANNEL);
		$tsw_forbiddennicks	= (!empty($wp_options['tsw_forbiddennicks']))	?($wp_options['tsw_forbiddennicks']):(TDPR3_FORBIDDENNICKS);
		$tsw_playerstatus	= (!empty($wp_options['tsw_playerstatus']))		?($wp_options['tsw_playerstatus'])	:(TDPR3_SHOWPLAYERSTATUS);
		$tsw_maxlength		= (!empty($wp_options['tsw_maxlength']))		?($wp_options['tsw_maxlength'])		:(TDPR3_MAXDISPLAYLENGTH);

		$ts_settings = array();
		$tsw_settings['teamspeak_host_url']		= $tsw_hosturl;
		$tsw_settings['serveraddress']			= $tsw_servername;
		$tsw_settings['serverudpport']			= $tsw_udpport;
		$tsw_settings['serverqueryport']		= $tsw_queryport;
		$tsw_settings['limitchannel']			= $tsw_limitchannel;
		$tsw_settings['forbiddennicknamechars']	= $tsw_forbiddennicks;
		$tsw_settings['showplayerstatus']		= (strtolower($tsw_playerstatus)=='no')?(false):(true);
		$tsw_settings['maxdisplaylength']		= (intval($tsw_maxlength)>0)?(intval($tsw_maxlength)):(TDPR3_MAXDISPLAYLENGTH);

		return $teamspeakDisplay->displayTeamspeakEx($tsw_settings);
	}		
} 

new AjaxTSW();
$ajax=new AjaxTSW();

add_action('wp_head', 'tsviewer_wp_head');
function tsviewer_wp_head()
{	global $ajax;
	$wp_options = get_option('widget_tsviewer');
	$tsw_refresh = (!empty($wp_options['tsw_refresh']))?($wp_options['tsw_refresh']):(TSW_WIDGETREFRESH);

	echo '<link rel="stylesheet" href="'.TSW_CSSPATH.'" type="text/css" media="screen">';
	echo '<script type="text/javascript" src="'.AJAX_JSPROTOPATH.'"></script>';
    	echo '<script type="text/javascript" src="'.AJAX_JSCOREPATH.'"></script>';
	echo $ajax->getJSCode(); 
	echo $ajax->onLoad("Displayteamspeak","","bindPeriodicalTimer",$tsw_refresh); 
}

function widget_tsviewer_init() {
 
	if ( !function_exists('register_sidebar_widget') )
		return;

	function widget_tsviewer($args) {
		global $ajax;

		extract($args);
		$wp_options = get_option('widget_tsviewer');
		$tsw_title			= (!empty($wp_options['tsw_title']))			?($wp_options['tsw_title'])			:(TSW_WIDGETTITLE);

		
		echo $before_widget . $before_title . $tsw_title . $after_title;
		echo $ajax->Displayteamspeak();
		echo $after_widget;		
	}

	function widget_tsviewer_control() {
		
		$wp_options = get_option('widget_tsviewer');

		if ( !is_array($wp_options) )
			$wp_options = array('tsw_title'			=> TSW_WIDGETTITLE, 
							 'tsw_hosturl'			=> TSW_ABSOLUTEPATH,
							 'tsw_servername'		=> TDPR3_SERVERDRESS,
							 'tsw_udpport'			=> TDPR3_UDPPORT,
							 'tsw_queryport'		=> TDPR3QUERYPORT,
							 'tsw_limitchannel'		=> TDPR3_LIMITCHANNEL,
							 'tsw_forbiddennicks'	=> TDPR3_FORBIDDENNICKS,
							 'tsw_playerstatus	'	=> TDPR3_SHOWPLAYERSTATUS,
							 'tsw_maxlength'		=> TDPR3_MAXDISPLAYLENGTH,
							 'tsw_refresh	'		=> TSW_WIDGETREFRESH
							);

		if ( $_POST['tsviewer-submit'] ) {
			$wp_options['tsw_title']			= strip_tags(stripslashes($_POST['tsviewer-title']));
			$wp_options['tsw_hosturl']			= strip_tags(stripslashes($_POST['tsviewer-hosturl']));
			$wp_options['tsw_servername']		= strip_tags(stripslashes($_POST['tsviewer-servername']));
			$wp_options['tsw_udpport']			= strip_tags(stripslashes($_POST['tsviewer-udpport']));
			$wp_options['tsw_queryport']		= strip_tags(stripslashes($_POST['tsviewer-queryport']));
			$wp_options['tsw_limitchannel']		= strip_tags(stripslashes($_POST['tsviewer-limitchannel']));
			$wp_options['tsw_forbiddennicks']	= strip_tags(stripslashes($_POST['tsviewer-forbiddennicks']));	
			$wp_options['tsw_playerstatus']		= strtolower(strip_tags(stripslashes($_POST['tsviewer-playerstatus'])));
			$wp_options['tsw_maxlength']		= strip_tags(stripslashes($_POST['tsviewer-maxlength']));
			$wp_options['tsw_refresh']			= strip_tags(stripslashes($_POST['tsviewer-refresh']));
			update_option('widget_tsviewer', $wp_options);
		}

		$tsw_title			= htmlentities($wp_options['tsw_title'], ENT_QUOTES);
		$tsw_hosturl		= htmlentities($wp_options['tsw_hosturl'], ENT_QUOTES);
		$tsw_servername		= htmlentities($wp_options['tsw_servername'], ENT_QUOTES);
		$tsw_udpport		= htmlentities($wp_options['tsw_udpport'], ENT_QUOTES);
		$tsw_queryport		= htmlentities($wp_options['tsw_queryport'], ENT_QUOTES);
		$tsw_limitchannel	= htmlentities($wp_options['tsw_limitchannel'], ENT_QUOTES);
		$tsw_forbiddennicks	= htmlentities($wp_options['tsw_forbiddennicks'], ENT_QUOTES);
		$tsw_playerstatus	= htmlentities($wp_options['tsw_playerstatus'], ENT_QUOTES);
		$tsw_maxlength		= htmlentities($wp_options['tsw_maxlength'], ENT_QUOTES);
		$tsw_refresh		= htmlentities($wp_options['tsw_refresh'], ENT_QUOTES);

		$tsw_title			= (empty($tsw_title))			?(TSW_WIDGETTITLE)			:($tsw_title);
		$tsw_hosturl		= (empty($tsw_hosturl))			?(TSW_ABSOLUTEPATH)			:($tsw_hosturl);
		$tsw_servername		= (empty($tsw_servername))		?(TDPR3_SERVERDRESS)		:($tsw_servername);
		$tsw_udpport		= (empty($tsw_udpport))			?(TDPR3_UDPPORT)			:($tsw_udpport);
		$tsw_queryport		= (empty($tsw_queryport))		?(TDPR3QUERYPORT)			:($tsw_queryport);
		$tsw_limitchannel	= (empty($tsw_limitchannel))	?(TDPR3_LIMITCHANNEL)		:($tsw_limitchannel);
		$tsw_forbiddennicks	= (empty($tsw_forbiddennicks))	?(TDPR3_FORBIDDENNICKS)		:($tsw_forbiddennicks);
		$tsw_playerstatus	= (empty($tsw_playerstatus))	?(TDPR3_SHOWPLAYERSTATUS)	:($tsw_playerstatus);
		$tsw_maxlength		= (empty($tsw_maxlength))		?(TDPR3_MAXDISPLAYLENGTH)	:($tsw_maxlength);
		$tsw_refresh		= (empty($tsw_refresh))			?(TSW_WIDGETREFRESH)		:($tsw_refresh);

		echo '<p style="text-align:right;">
				<label for="tsviewer-title">' . __('Title:') . ' 
				<input style="width: 200px;" id="tsviewer-title" name="tsviewer-title" type="text" value="'.$tsw_title.'" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="tsviewer-hosturl">' . __('Hosturl:') . ' 
				<input style="width: 200px;" id="tsviewer-hosturl" name="tsviewer-hosturl" type="text" value="'.$tsw_hosturl.'" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="tsviewer-servername">' . __('Servername:') . ' 
				<input style="width: 200px;" id="tsviewer-servername" name="tsviewer-servername" type="text" value="'.$tsw_servername.'" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="tsviewer-udpport">' . __('Udpport:') . ' 
				<input style="width: 200px;" id="tsviewer-udpport" name="tsviewer-udpport" type="text" value="'.$tsw_udpport.'" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="tsviewer-queryport">' . __('Queryport:') . ' 
				<input style="width: 200px;" id="tsviewer-queryport" name="tsviewer-queryport" type="text" value="'.$tsw_queryport.'" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="tsviewer-limitchannel">' . __('This Cannel only:') . ' 
				<input style="width: 200px;" id="tsviewer-limitchannel" name="tsviewer-limitchannel" type="text" value="'.$tsw_limitchannel.'" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="tsviewer-fobiddennicks">' . __('Forbiddennicks:') . ' 
				<input style="width: 200px;" id="tsviewer-forbiddennicks" name="tsviewer-forbiddennicks" type="text" value="'.$tsw_forbiddennicks.'" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="tsviewer-playerstatus">' . __('Show Status (yes/no):') . ' 
				<input style="width: 200px;" id="tsviewer-playerstatus" name="tsviewer-playerstatus" type="text" value="'.$tsw_playerstatus.'" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="tsviewer-maxlength">' . __('Displaylength:') . ' 
				<input style="width: 200px;" id="tsviewer-maxlength" name="tsviewer-maxlength" type="text" value="'.$tsw_maxlength.'" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="tsviewer-refresh">' . __('Refresh (ms):') . ' 
				<input style="width: 200px;" id="tsviewer-refresh" name="tsviewer-refresh" type="text" value="'.$tsw_refresh.'" />
				</label></p>';	
		echo '<input type="hidden" id="tsviewer-submit" name="tsviewer-submit" value="1" />';
	}

	register_sidebar_widget(array(TSW_WIDGETNAME.' '.TSW_WIDGETVERSION, 'widgets'), 'widget_tsviewer');

	register_widget_control(array(TSW_WIDGETNAME.' '.TSW_WIDGETVERSION, 'widgets'), 'widget_tsviewer_control', 350, 350);
}

add_action('widgets_init', 'widget_tsviewer_init');

?>

