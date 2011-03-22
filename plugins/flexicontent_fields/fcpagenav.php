<?php
/**
 * @version 1.0 $Id: fcpagenav.php 40 2010-02-09 00:08:23Z emmanuel.danan $
 * @package Joomla
 * @subpackage FLEXIcontent
 * @subpackage plugin.file
 * @copyright (C) 2009 Emmanuel Danan - www.vistamedia.fr
 * @license GNU/GPL v2
 *
 * FLEXIcontent is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

//jimport('joomla.plugin.plugin');
jimport('joomla.event.plugin');
require_once (JPATH_SITE.DS.'components'.DS.'com_flexicontent'.DS.'helpers'.DS.'route.php');

class plgFlexicontent_fieldsFcpagenav extends JPlugin
{
	function plgFlexicontent_fieldsFcpagenav( &$subject, $params )
	{
		parent::__construct( $subject, $params );
        JPlugin::loadLanguage('plg_flexicontent_fields_fcpagenav', JPATH_ADMINISTRATOR);
	}

	function onDisplayField(&$field, $item)
	{
		// execute the code only if the field type match the plugin type
		if($field->field_type != 'fcpagenav') return;
	}


	function onDisplayFieldValue(&$field, $item, $values=null, $prop='display')
	{
		// execute the code only if the field type match the plugin type
		if($field->field_type != 'fcpagenav') return;

		global $mainframe;
		
		// parameters shortcuts
		$load_css 			= $field->parameters->get('load_css', 1);
		$use_tooltip		= $field->parameters->get('use_tooltip', 1);
		$use_title			= $field->parameters->get('use_title', 0);
		$tooltip_title_next	= $field->parameters->get('tooltip_title_next', JText::_('FLEXI_FIELDS_PAGENAV_GOTONEXT'));
		$tooltip_title_prev	= $field->parameters->get('tooltip_title_prev', JText::_('FLEXI_FIELDS_PAGENAV_GOTOPREV'));
		$types_to_exclude	= $field->parameters->get('type_to_exclude', '');
		$prev_label			= $field->parameters->get('prev_label', JText::_('Prev'));
		$next_label			= $field->parameters->get('next_label', JText::_('Next'));

		$view		= JRequest::getCmd('view');
		$option		= JRequest::getCmd('option');
		$cid		= JRequest::getInt('cid');
		$id			= JRequest::getInt('id');
	
		if (($view == 'items') && ($option == 'com_flexicontent'))
		{

			$html 		= '';
			$db 		= & JFactory::getDBO();
			$user		= & JFactory::getUser();
			$document	= & JFactory::getDocument();
	
			$nullDate	= $db->getNullDate();
			$date		=& JFactory::getDate();
			$config 	= & JFactory::getConfig();
			$now 		= $date->toMySQL();
			$gid		= (int) $user->get('aid', 0);
	
			if ($use_tooltip)
				JHTML::_('behavior.tooltip');
			if ($load_css)
				$document->addStyleSheet('plugins/flexicontent_fields/fcpagenav/fcpagenav.css');	

			// get active category ordering
			$query 	= 'SELECT params FROM #__categories WHERE id = ' . ($cid ? $cid : $item->catid);
			$db->setQuery($query);
			$catparams = $db->loadResult();
			$cparams = new JParameter($catparams);
			
			// filter depending on permissions
			if (FLEXI_ACCESS) {
				$readperms = FAccess::checkUserElementsAccess($user->gmid, 'read');
				if (count(@$readperms['item']) > 0) {
					$andaccess = ' AND ( a.access <= '.$gid.' OR a.id IN ('.implode(",", $readperms['item']).') )';
				} else {
					$andaccess = ' AND a.access <= '.$gid;
				}
			} else {
				$andaccess = ' AND a.access <= '.$gid;
			}

			// Determine sort order
			$order_method = $cparams->get('orderby', '');
			
			switch ($order_method)
			{

				case 'date' :
					$orderby = 'a.created';
					break;
	
				case 'rdate' :
					$orderby = 'a.created DESC';
					break;
	
				case 'alpha' :
					$orderby = 'a.title';
					break;
	
				case 'ralpha' :
					$orderby = 'a.title DESC';
					break;
	
				case 'hits' :
					$orderby = 'a.hits';
					break;
	
				case 'rhits' :
					$orderby = 'a.hits DESC';
					break;
	
				case 'order' :
					$orderby = 'rel.ordering';
					break;
	
				default :
					$orderby = 'a.ordering';
					break;
					
			}
	
			$types		= is_array($types_to_exclude) ? implode(',', $types_to_exclude) : $types_to_exclude;

			$xwhere	=	' AND ( a.state = 1 OR a.state = -5 )' .
						' AND ( publish_up = '.$db->Quote($nullDate).' OR publish_up <= '.$db->Quote($now).' )' .
						' AND ( publish_down = '.$db->Quote($nullDate).' OR publish_down >= '.$db->Quote($now).' )' . 
						($types_to_exclude ? ' AND ie.type_id NOT IN (' . $types . ')' : '')
						;
	
			// array of articles in same category correctly ordered
			$query 	= 'SELECT a.id, a.title,'
					. ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug,'
					. ' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(":", cc.id, cc.alias) ELSE cc.id END as catslug'
					. ' FROM #__content AS a'
					. ' LEFT JOIN #__categories AS cc ON cc.id = a.catid'
					. ' LEFT JOIN #__flexicontent_cats_item_relations AS rel ON rel.itemid = a.id'
					. ' LEFT JOIN #__flexicontent_items_ext AS ie on ie.item_id = a.id'
					. ' WHERE a.catid = ' . ($cid ? $cid : (int) $item->catid)
					. $andaccess
					. $xwhere
					. ' GROUP BY a.id'
					. ' ORDER BY '. $orderby
					;
			$db->setQuery($query);
			$list = $db->loadObjectList('id');

			// this check needed if incorrect Itemid is given resulting in an incorrect result
			if ( !is_array($list) ) {
				$list = array();
			}	
			reset($list);
	
			// location of current content item in array list
			$location = array_search($item->id, array_keys($list));

			$rows = array_values($list);
			
			$field->prev = null;
			$field->prevtitle = null;
			$field->prevurl = null;
			$field->next = null;
			$field->nexttitle = null;
			$field->nexturl = null;
	
			if ($location -1 >= 0) 	{
				// the previous content item cannot be in the array position -1
				$field->prev = $rows[$location -1];
			}
	
			if (($location +1) < count($rows)) {
				// the next content item cannot be in an array position greater than the number of array postions
				$field->next = $rows[$location +1];
			}
		
			if ($field->prev) {
				$field->prevtitle = $field->prev->title;
				$field->prevurl = JRoute::_(FlexicontentHelperRoute::getItemRoute($field->prev->slug, $field->prev->catslug));
			} else {
				$field->prevtitle = '';
				$field->prevurl = '';
			}
	
			if ($field->next) {
				$field->nexttitle = $field->next->title;
				$field->nexturl = JRoute::_(FlexicontentHelperRoute::getItemRoute($field->next->slug, $field->next->catslug));
			} else {
				$field->nexttitle = '';
				$field->nexturl = '';
			}
	
			// output
			if ($field->prev || $field->next)
			{

				$html 	 = '<span class="pagination">';

				if ($field->prev)
				{
					$html .= '
					<span class="pagenav_prev' . ($use_tooltip ? ' hasTip' : '') . '"' . ($use_tooltip ? 'title="'.$tooltip_title_prev.'::'.$field->prevtitle.'"' : '') . '>
						<a href="'. $field->prevurl .'">' . ( $use_title ? $field->prevtitle : $prev_label ) . '</a>
					</span>'
					;
				}
			
				if ($field->next)
				{
					$html .= '
					<span class="pagenav_next' . ($use_tooltip ? ' hasTip' : '') . '"' . ($use_tooltip ? 'title="'.$tooltip_title_next.'::'.$field->nexttitle.':: "' : '') . '>
						<a href="'. $field->nexturl .'">' . ( $use_title ? $field->nexttitle : $next_label ) .'</a>
					</span>'
					;
				}

				$html 	.= '</span>';

			}
		}
		
		$field->{$prop} = $html;
	}

}