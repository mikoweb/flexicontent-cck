<?php
/**
 * @version 1.5 stable $Id: default.php 1904 2014-05-20 12:21:09Z ggppdk $
 * @package Joomla
 * @subpackage FLEXIcontent
 * @copyright (C) 2009 Emmanuel Danan - www.vistamedia.fr
 * @license GNU/GPL v2
 *
 * FLEXIcontent is a derivative work of the excellent QuickFAQ component
 * @copyright (C) 2008 Christoph Lukes
 * see www.schlu.net for more information
 *
 * FLEXIcontent is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

defined('_JEXEC') or die('Restricted access');

$task_items = FLEXI_J16GE ? 'task=items.' : 'controller=items&task=';
$ctrl_items = FLEXI_J16GE ? 'items.' : '';
$tags_task = FLEXI_J16GE ? 'task=tags.' : 'controller=tags&task=';

// For tabsets/tabs ids (focusing, etc)
$tabSetCnt = -1;
$tabCnt = array();

$tags_displayed = $this->row->type_id && ( $this->perms['cantags'] || count(@$this->usedtags) ) ;

$close_btn = FLEXI_J30GE ? '<a class="close" data-dismiss="alert">&#215;</a>' : '<a class="fc-close" onclick="this.parentNode.parentNode.removeChild(this.parentNode);">&#215;</a>';
$alert_box = FLEXI_J30GE ? '<div %s class="alert alert-%s %s">'.$close_btn.'%s</div>' : '<div %s class="fc-mssg fc-%s %s">'.$close_btn.'%s</div>';
$btn_class = FLEXI_J30GE ? 'btn' : 'fc_button';

// add extra css/js for the edit form
if ($this->params->get('form_extra_css'))    $this->document->addStyleDeclaration($this->params->get('form_extra_css'));
if ($this->params->get('form_extra_css_be')) $this->document->addStyleDeclaration($this->params->get('form_extra_css_be'));
if ($this->params->get('form_extra_js'))     $this->document->addScriptDeclaration($this->params->get('form_extra_js'));
if ($this->params->get('form_extra_js_be'))  $this->document->addScriptDeclaration($this->params->get('form_extra_js_be'));

// Load JS tabber lib
$this->document->addScript( JURI::root().'components/com_flexicontent/assets/js/tabber-minimized.js' );
$this->document->addStyleSheet( JURI::root().'components/com_flexicontent/assets/css/tabber.css' );
$this->document->addScriptDeclaration(' document.write(\'<style type="text/css">.fctabber{display:none;}<\/style>\'); ');  // temporarily hide the tabbers until javascript runs

if ($this->perms['cantags'] || $this->perms['canversion']) {
	$this->document->addScript(JURI::root().'components/com_flexicontent/librairies/jquery-autocomplete/jquery.bgiframe.min.js');
	$this->document->addScript(JURI::root().'components/com_flexicontent/librairies/jquery-autocomplete/jquery.ajaxQueue.js');
	$this->document->addScript(JURI::root().'components/com_flexicontent/librairies/jquery-autocomplete/jquery.autocomplete.min.js');
	$this->document->addScript(JURI::root().'components/com_flexicontent/assets/js/jquery.pager.js');     // e.g. pagination for item versions
	$this->document->addScript(JURI::root().'components/com_flexicontent/assets/js/jquery.autogrow.js');  // e.g. autogrow version comment textarea

	$this->document->addStyleSheet(JURI::root().'components/com_flexicontent/librairies/jquery-autocomplete/jquery.autocomplete.css');
	$this->document->addScriptDeclaration("
		jQuery(document).ready(function () {
			jQuery('#input-tags').autocomplete('".JURI::base()."index.php?option=com_flexicontent&".$task_items."viewtags&format=raw&".(FLEXI_J30GE ? JSession::getFormToken() : JUtility::getToken())."=1', {
				width: 260,
				max: 100,
				matchContains: false,
				mustMatch: false,
				selectFirst: false,
				dataType: 'json',
				parse: function(data) {
					return jQuery.map(data, function(row) {
						return {
							data: row,
							value: row.name,
							result: row.name
						};
					});
				},
				formatItem: function(row) {
					return row.name;
				}
			}).result(function(e, row) {
				jQuery('#input-tags').attr('tagid',row.id);
				jQuery('#input-tags').attr('tagname',row.name);
				addToList(row.id, row.name);
			}).keydown(function(event) {
				if((event.keyCode==13)&&(jQuery('#input-tags').attr('tagid')=='0') ) {//press enter button
					addtag(0, jQuery('#input-tags').attr('value'));
					resetField();
					return false;
				}else if(event.keyCode==13) {
					resetField();
					return false;
				}
			});
			function resetField() {
				jQuery('#input-tags').attr('tagid',0);
				jQuery('#input-tags').attr('tagname','');
				jQuery('#input-tags').attr('value','');
			}
		});
		
		jQuery(document).ready(function() {
			// Initialize popup container
			var winwidth = jQuery( window ).width() - 80;
			var winheight= jQuery( window ).height() - 80;
			jQuery('#fc_modal_popup_container').dialog({
			   autoOpen: false,
			   width: winwidth,
			   height: winheight,
			   modal: true,
			   position: [40, 40]
			});
			
			// For the initially displayed versions page:  Add onclick event that opens compare in popup 
			jQuery('a.modal-versions').each(function(index, value) {
				jQuery(this).on('click', function() {
					var url = jQuery(this).attr('href');
					showDialog(url);
					return false;
				});
			});
			// Attach pagination for versions listing
			jQuery('#fc_pager').pager({ pagenumber: ".$this->current_page.", pagecount: ".$this->pagecount.", buttonClickCallback: PageClick });
		});
		
		// Load given URL in an open dialog
		function showDialog(url){
			jQuery('#fc_modal_popup_container').load(url);
			jQuery('#fc_modal_popup_container').dialog('open');         
		}
		
		PageClick = function(pageclickednumber) {
			jQuery.ajax({ url: 'index.php?option=com_flexicontent&".$task_items."getversionlist&id=".$this->row->id."&active=".$this->row->version."&".(FLEXI_J30GE ? JSession::getFormToken() : JUtility::getToken())."=1&format=raw&page='+pageclickednumber, context: jQuery('#result'), success: function(str){
				jQuery(this).html(\"<table width='100%' class='versionlist' cellpadding='0' cellspacing='0'>\\
				<tr>\\
					<th colspan='4'>".JText::_( 'FLEXI_VERSIONS_HISTORY',true )."</th>\\
				</tr>\\
				\"+str+\"\\
				</table>\");
				var JTooltips = new Tips($$('table.versionlist tr td a.hasTip'), { maxTitleChars: 50, fixed: false});
				
				// Attach click event to version compare links of the newly created page
				jQuery(this).find('a.modal-versions').each(function(index, value) {
					jQuery(this).on('click', function() {
						var url = jQuery(this).attr('href');
						showDialog(url);
						return false;
					});
				});
			}});
			
			// Reattach pagination inside the newly created page
			jQuery('#fc_pager').pager({ pagenumber: pageclickednumber, pagecount: ".$this->pagecount.", buttonClickCallback: PageClick });
		}
		
		jQuery(document).ready(function(){
			jQuery('#versioncomment').autogrow({
				minHeight: 26,
				maxHeight: 250,
				lineHeight: 12
			});
		})
		
	");
}

// version variables
$tags_fieldname = FLEXI_J16GE ? 'jform[tag][]' : 'tag[]';

$this->document->addScriptDeclaration("
	jQuery(document).ready(function(){
		var hits = new itemscreen('hits', {id:".($this->row->id ? $this->row->id : 0).", task:'".$ctrl_items."gethits'});
		hits.fetchscreen();
	
		var votes = new itemscreen('votes', {id:".($this->row->id ? $this->row->id : 0).", task:'".$ctrl_items."getvotes'});
		votes.fetchscreen();
	});

	function addToList(id, name) {
		obj = jQuery('#ultagbox');
		obj.append(\"<li class='tagitem'><span>\"+name+\"</span><input type='hidden' name='".$tags_fieldname."' value='\"+id+\"' /><a href='javascript:;' class='deletetag' onclick='javascript:deleteTag(this);' title='". JText::_( 'FLEXI_DELETE_TAG',true ) ."'></a></li>\");
	}
	function addtag(id, tagname) {
		if (id==null) id = 0;
	
		if(tagname == '') {
			alert('".JText::_( 'FLEXI_ENTER_TAG', true)."');
			return;
		}
	
		var tag = new itemscreen();
		tag.addtag( id, tagname, 'index.php?option=com_flexicontent&".$tags_task."addtag&format=raw&".(FLEXI_J30GE ? JSession::getFormToken() : JUtility::getToken())."=1');
	}

	function reseter(task, id, div){
		var res = new itemscreen();
		task = '".$ctrl_items."' + task;
		res.reseter( task, id, div, 'index.php?option=com_flexicontent&controller=items' );
	}
	function clickRestore(link) {
		if(confirm('".JText::_( 'FLEXI_CONFIRM_VERSION_RESTORE',true )."')) {
			location.href=link;
		}
		return false;
	}
	function deleteTag(obj) {
		var parent = obj.parentNode;
		parent.innerHTML = '';
		parent.parentNode.removeChild(parent);
	}
");


// Create info images
$infoimage    = JHTML::image ( 'administrator/components/com_flexicontent/assets/images/lightbulb.png', JText::_( 'FLEXI_NOTES' ) );
$revertimage  = JHTML::image ( 'administrator/components/com_flexicontent/assets/images/arrow_rotate_anticlockwise.png', JText::_( 'FLEXI_REVERT' ) );
$viewimage    = JHTML::image ( 'administrator/components/com_flexicontent/assets/images/magnifier.png', JText::_( 'FLEXI_VIEW' ) );
$commentimage = JHTML::image ( 'administrator/components/com_flexicontent/assets/images/comment.png', JText::_( 'FLEXI_COMMENT' ) );

// Create some variables
$itemlang = substr($this->row->language ,0,2);
if (isset($this->row->item_translations)) foreach ($this->row->item_translations as $t) if ($t->shortcode==$itemlang) {$itemlangname = $t->name; break;}
?>

<?php /* echo "Version: ". $this->row->version."<br/>\n"; */?>
<?php /* echo "id: ". $this->row->id."<br/>\n"; */?>
<?php /* echo "type_id: ". @$this->row->type_id."<br/>\n"; */?>


<div id="flexicontent" class="flexi_edit" >
<div id="fc_modal_popup_container"></div>

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data" >
<table width="100%"><tr>
	<td valign="top" style="width:auto; padding: 0px">

	<div class="fc_edit_container_full" style="margin:0px 0px 10px 0px !important;">
	<?php /*<fieldset class="basicfields_set">
		<legend>
			<?php echo JText::_( 'FLEXI_BASIC' ); ?>
		</legend>*/ ?>

			<?php
				$field = isset($this->fields['title']) ? $this->fields['title'] : false;
				if ($field) {
					$field_description = $field->description ? $field->description :
						JText::_(FLEXI_J16GE ? $this->form->getField('title')->__get('description') : 'TIPTITLEFIELD');
					$label_tooltip = 'class="hasTip flexi_label" title="'.'::'.htmlspecialchars($field_description, ENT_COMPAT, 'UTF-8').'"';
				} else {
					$label_tooltip = 'class="hasTip flexi_label"';
				}
			?>
			<label id="jform_title-lbl" for="jform_title" <?php echo $label_tooltip; ?> >
				<?php echo $field ? $field->label : JText::_( 'FLEXI_TITLE' ); ?>
			</label>
			<?php /*echo $this->form->getLabel('title');*/ ?>

			<div class="container_fcfield container_fcfield_id_1 container_fcfield_name_title" id="container_fcfield_1">
			<?php	if ( isset($this->row->item_translations) ) :?>

				<!-- tabber start -->
				<div class="fctabber" style=''>
					<div class="tabbertab" style="padding: 0px;" >
						<h3 class="tabberheading"> <?php echo '-'.$itemlangname.'-'; // $itemlang; ?> </h3>
						<?php echo $this->form->getInput('title');?>
					</div>
					<?php foreach ($this->row->item_translations as $t): ?>
						<?php if ($itemlang!=$t->shortcode && $t->shortcode!='*') : ?>
							<div class="tabbertab" style="padding: 0px;" >
								<h3 class="tabberheading"> <?php echo $t->name; // $t->shortcode; ?> </h3>
								<?php
								$ff_id = 'jfdata_'.$t->shortcode.'_title';
								$ff_name = 'jfdata['.$t->shortcode.'][title]';
								?>
								<input class="inputbox fc_form_title fcfield_textval" style='margin:0px;' type="text" id="<?php echo $ff_id; ?>" name="<?php echo $ff_name; ?>" value="<?php echo @$t->fields->title->value; ?>" size="36" maxlength="254" />
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
				<!-- tabber end -->

			<?php else : ?>
				<?php echo $this->form->getInput('title');?>
			<?php endif; ?>
			</div>

			<div class="fcclear"></div>
			<?php
				$field_description = JText::_(FLEXI_J16GE ? $this->form->getField('alias')->__get('description') : 'ALIASTIP');
				$label_tooltip = 'class="hasTip flexi_label" title="'.'::'.htmlspecialchars($field_description, ENT_COMPAT, 'UTF-8').'"';
			?>
			<label id="jform_alias-lbl" for="jform_alias" <?php echo $label_tooltip; ?> >
				<?php echo JText::_( 'FLEXI_ALIAS' ); ?>
			</label>

			<div class="container_fcfield container_fcfield_name_alias">
			<?php	if ( isset($this->row->item_translations) ) :?>

				<!-- tabber start -->
				<div class="fctabber" style=''>
					<div class="tabbertab" style="padding: 0px;" >
						<h3 class="tabberheading"> <?php echo '-'.$itemlangname.'-'; // $itemlang; ?> </h3>
						<?php echo $this->form->getInput('alias');?>
					</div>
					<?php foreach ($this->row->item_translations as $t): ?>
						<?php if ($itemlang!=$t->shortcode && $t->shortcode!='*') : ?>
							<div class="tabbertab" style="padding: 0px;" >
								<h3 class="tabberheading"> <?php echo $t->name; // $t->shortcode; ?> </h3>
								<?php
								$ff_id = 'jfdata_'.$t->shortcode.'_alias';
								$ff_name = 'jfdata['.$t->shortcode.'][alias]';
								?>
								<input class="inputbox fc_form_alias fcfield_textval" style='margin:0px;' type="text" id="<?php echo $ff_id; ?>" name="<?php echo $ff_name; ?>" value="<?php echo @$t->fields->alias->value; ?>" size="36" maxlength="254" />
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
				<!-- tabber end -->

			<?php else : ?>
				<?php echo $this->form->getInput('alias');?>
			<?php endif; ?>
			</div>

			<div class="fcclear"></div>
			<?php
				$field = isset($this->fields['document_type']) ? $this->fields['document_type'] : false;
				if ($field) {
					$field_description = $field->description ? $field->description :
						JText::_(FLEXI_J16GE ? $this->form->getField('type_id')->__get('description') : 'FLEXI_TYPE_DESC');
					$label_tooltip = 'class="hasTip flexi_label" title="'.'::'.htmlspecialchars($field_description, ENT_COMPAT, 'UTF-8').'"';
				} else {
					$label_tooltip = 'class="hasTip flexi_label"';
				}
			?>
			<label id="jform_type_id-lbl" for="jform_type_id" for_bck="jform_type_id" <?php echo $label_tooltip; ?> >
				<?php echo $field ? $field->label : JText::_( 'FLEXI_TYPE' ); ?>
			</label>
			<?php /*echo $this->form->getLabel('type_id');*/ ?>
				
			<div class="container_fcfield container_fcfield_id_8 container_fcfield_name_type" id="container_fcfield_8">
				<?php echo $this->lists['type']; ?>
				<?php //echo $this->form->getInput('type_id'); ?>
				<span class="editlinktip hasTip" style="display:inline-block;" title="<?php echo htmlspecialchars(JText::_( 'FLEXI_NOTES' ), ENT_COMPAT, 'UTF-8'); ?>::<?php echo htmlspecialchars(JText::_( 'FLEXI_TYPE_CHANGE_WARNING' ), ENT_COMPAT, 'UTF-8');?>">
					<?php echo $infoimage; ?>
				</span>
				<div id="fc-change-warning" class="fc-mssg fc-warning" style="display:none;"><?php echo JText::_( 'FLEXI_TAKE_CARE_CHANGING_FIELD_TYPE' ); ?></div>
			</div>

			<div class="fcclear"></div>
			<?php
				$field = isset($this->fields['state']) ? $this->fields['state'] : false;
				if ($field) {
					$field_description = $field->description ? $field->description :
						JText::_(FLEXI_J16GE ? $this->form->getField('state')->__get('description') : 'FLEXI_STATE_DESC');
					$label_tooltip = 'class="hasTip flexi_label" title="'.'::'.htmlspecialchars($field_description, ENT_COMPAT, 'UTF-8').'"';
				} else {
					$label_tooltip = 'class="hasTip flexi_label"';
				}
			?>
			<label id="jform_state-lbl" for="jform_state" <?php echo $label_tooltip; ?> >
				<?php echo $field ? $field->label : JText::_( 'FLEXI_STATE' ); ?>
			</label>
			<?php /*echo $this->form->getLabel('state');*/ ?>
			<?php
			if ( $this->perms['canpublish'] ) : ?>
				<div class="container_fcfield container_fcfield_id_10 container_fcfield_name_state fcdualline" id="container_fcfield_10" style="margin-right:4% !important;" >
					<?php echo $this->lists['state']; ?>
					<?php //echo $this->form->getInput('state'); ?>
					<span class="editlinktip hasTip" style="display:inline-block;" title="<?php echo htmlspecialchars(JText::_( 'FLEXI_NOTES' ), ENT_COMPAT, 'UTF-8'); ?>::<?php echo htmlspecialchars(JText::_( 'FLEXI_STATE_CHANGE_WARNING' ), ENT_COMPAT, 'UTF-8');?>">
						<?php echo $infoimage; ?>
					</span>
				</div>
			<?php else :
				echo $this->published;
				echo '<input type="hidden" name="jform[state]" id="jform_vstate" value="'.$this->row->state.'" />';
			endif;
			?>

		<?php if ( $this->perms['canpublish'] ) : ?>
			<?php if (!$this->params->get('auto_approve', 1)) : ?>
				<?php
					//echo "<br/>".$this->form->getLabel('vstate') . $this->form->getInput('vstate');
					$label_tooltip = 'class="hasTip flexi_label fcdualline" title="'.htmlspecialchars(JText::_( 'FLEXI_PUBLIC_DOCUMENT_CHANGES' ), ENT_COMPAT, 'UTF-8').'::'.htmlspecialchars(JText::_( 'FLEXI_PUBLIC_DOCUMENT_CHANGES_DESC' ), ENT_COMPAT, 'UTF-8').'"';
				?>
				<div style="float:left; width:50%; margin:0px; padding:0px;">
					<label id="jform_vstate-lbl" for="jform_vstate" <?php echo $label_tooltip; ?> >
						<?php echo JText::_( 'FLEXI_PUBLIC_DOCUMENT_CHANGES' ); ?>
					</label>
					<div class="container_fcfield container_fcfield_name_vstate fcdualline">
						<?php echo $this->lists['vstate']; ?>
					</div>
				</div>
			<?php else :
				echo '<input type="hidden" name="jform[vstate]" id="jform_vstate" value="2" />';
			endif;
		elseif (!$this->params->get('auto_approve', 1)) :
			// Enable approval if versioning disabled, this make sense,
			// since if use can edit item THEN item should be updated !!!
			$item_vstate = $this->params->get('use_versioning', 1) ? 1 : 2;
			echo '<input type="hidden" name="jform[vstate]" id="jform_vstate" value="'.$item_vstate.'" />';
		else :
			echo '<input type="hidden" name="jform[vstate]" id="jform_vstate" value="2" />';
		endif;
		?>
		
		<?php if ($this->subscribers) : ?>
			<div class="fcclear"></div>
			<?php
				$label_tooltip = 'class="hasTip flexi_label" title="'.'::'.htmlspecialchars(JText::_( 'FLEXI_NOTIFY_NOTES' ), ENT_COMPAT, 'UTF-8').'"';
			?>
			<label id="jform_notify-lbl" for="jform_notify" <?php echo $label_tooltip; ?> >
				<?php echo JText::_( 'FLEXI_NOTIFY_FAVOURING_USERS' ); ?>
			</label>
			<div class="container_fcfield container_fcfield_name_notify">
				<?php echo $this->lists['notify']; ?>
			</div>
		<?php endif; ?>
		
	<?php /*</fieldset>*/ ?>
	</div>


<?php
// *****************
// MAIN TABSET START
// *****************
$tabSetCnt++;
$tabCnt[$tabSetCnt] = 0;
?>

<!-- tabber start -->
<div class='fctabber fields_tabset' id='fcform_tabset_<?php echo $tabSetCnt; ?>' >
	<div class='tabbertab' id='fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>' >
		<h3 class="tabberheading"> <?php echo JText::_( 'FLEXI_BASIC' ); ?> </h3>
		
		<?php $fset_lbl = $tags_displayed ? 'FLEXI_CATEGORIES_TAGS' : 'FLEXI_CATEGORIES';?>
		
		<div class="fcclear"></div>
		<fieldset class="basicfields_set" id="fcform_categories_tags_container">
			<legend>
				<?php echo JText::_( $fset_lbl ); ?>
			</legend>
			
			<label id="jform_catid-lbl" for="jform_catid" for_bck="jform_catid" class="flexi_label">
				<?php echo JText::_( 'FLEXI_CATEGORIES_MAIN' ); ?>
			</label>
			<div class="container_fcfield container_fcfield_name_catid">
				<?php echo $this->lists['catid']; ?>
				<span class="editlinktip hasTip" style="display:inline-block;" title="<?php echo htmlspecialchars(JText::_ ( 'FLEXI_NOTES' ), ENT_COMPAT, 'UTF-8'); ?>::<?php echo htmlspecialchars(JText::_ ( 'FLEXI_CATEGORIES_NOTES' ), ENT_COMPAT, 'UTF-8');?>">
				<?php echo $infoimage; ?>
				</span>
			</div>
			
			<?php if (1) : /* secondary categories always available in backend */ ?>
				
				<div class="fcclear"></div>
				<label id="jform_cid-lbl" for="jform_cid" for_bck="jform_cid" class="flexi_label">
					<?php echo JText::_( 'FLEXI_CATEGORIES' ); ?>
				</label>
				<div class="container_fcfield container_fcfield_name_catid">
					<?php echo $this->lists['cid']; ?>
				</div>
				
			<?php endif; ?>

			<?php if ( !empty($this->lists['featured_cid']) ) : ?>
				<div class="fcclear"></div>
				<label id="jform_featured_cid-lbl" for="jform_featured_cid" for_bck="jform_featured_cid" class="flexi_label">
					<?php echo JText::_( 'FLEXI_FEATURED_CATEGORIES' ); ?>
				</label>
				<div class="container_fcfield container_fcfield_name_featured_cid">
					<?php echo $this->lists['featured_cid']; ?>
				</div>
			<?php endif; ?>


			<div class="fcclear"></div>
			<span class="flexi_label">
				<?php echo $this->form->getLabel('featured'); ?>
				<br/><small><?php echo JText::_( 'FLEXI_JOOMLA_FEATURED_VIEW' ); ?></small>
			</span>
			<div class="container_fcfield container_fcfield_name_featured">
				<?php echo $this->lists['featured']; ?>
				<?php //echo $this->form->getInput('featured');?>
			</div>


			<?php if (1) : /* tags always available in backend */ ?>
				<?php
					$field = isset($this->fields['tags']) ? $this->fields['tags'] : false;
					if ($field) {
						$label_tooltip = $field->description ? 'class="hasTip flexi_label" title="'.'::'.htmlspecialchars($field->description, ENT_COMPAT, 'UTF-8').'"' : 'class="flexi_label"';
					} else {
						$label_tooltip = 'class="hasTip flexi_label"';
					}
				?>
				<div class="fcclear"></div>
				<label id="jform_tag-lbl" for="jform_tag" <?php echo $label_tooltip; ?> >
					<?php echo $field ? $field->label : JText::_( 'FLEXI_TAGS' ); ?>
				</label>
				<div class="container_fcfield container_fcfield_name_tags">
					
					<div class="qf_tagbox" id="qf_tagbox">
						<ul id="ultagbox">
						<?php
							$nused = count($this->usedtags);
							for( $i = 0, $nused; $i < $nused; $i++ ) {
								$tag = $this->usedtags[$i];
								if ( $this->perms['cantags'] ) {
									echo '<li class="tagitem"><span>'.$tag->name.'</span>';
									echo '<input type="hidden" name="jform[tag][]" value="'.$tag->tid.'" /><a href="javascript:;" class="deletetag" onclick="javascript:deleteTag(this);" align="right" title="'.JText::_('FLEXI_DELETE_TAG').'"></a></li>';
								} else {
									echo '<li class="tagitem plain"><span>'.$tag->name.'</span>';
									echo '<input type="hidden" name="jform[tag][]" value="'.$tag->tid.'" /></li>';
								}
							}
						?>
						</ul>
					</div>

					<?php if ( $this->perms['cantags'] ) : ?>
					<div class="fcclear"></div>
					<div id="tags">
						<label for="input-tags">
							<?php echo JText::_( 'FLEXI_ADD_TAG' ); ?>
						</label>
						<input type="text" id="input-tags" name="tagname" tagid='0' tagname='' />
						<span id='input_new_tag' ></span>
						<span class="editlinktip hasTip" style="display:inline-block;" title="<?php echo htmlspecialchars(JText::_( 'FLEXI_NOTES' ), ENT_COMPAT, 'UTF-8'); ?>::<?php echo htmlspecialchars(JText::_( 'FLEXI_TAG_EDDITING_FULL' ), ENT_COMPAT, 'UTF-8');?>">
							<?php echo $infoimage; ?>
						</span>
					</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</fieldset>


	<?php if (FLEXI_FISH || FLEXI_J16GE) : ?>
		
		<div class="fcclear"></div>
		<fieldset class="basicfields_set" id="fcform_language_container">
			<legend>
				<?php echo JText::_( 'FLEXI_LANGUAGE' ); ?>
			</legend>
			
			<span class="flexi_label">
				<?php echo $this->form->getLabel('language'); ?>
			</span>
			
			<div class="container_fcfield container_fcfield_name_language">
				<?php echo $this->lists['languages']; ?>
			</div>

			<?php if ( $this->params->get('enable_translation_groups') ) : ?>

				<div class="fcclear"></div>
				<?php
					$label_tooltip = 'class="hasTip flexi_label" title="'.'::'.htmlspecialchars(JText::_( 'FLEXI_ORIGINAL_CONTENT_ITEM_DESC' ), ENT_COMPAT, 'UTF-8').'"';
				?>
				<label id="jform_lang_parent_id-lbl" for="jform_lang_parent_id" <?php echo $label_tooltip; ?> >
					<?php echo JText::_( 'FLEXI_ORIGINAL_CONTENT_ITEM' );?>
				</label>
				
				<div class="container_fcfield container_fcfield_name_originalitem">
				<?php if ( $this->row->id  && (substr(flexicontent_html::getSiteDefaultLang(), 0,2) == substr($this->row->language, 0,2) || $this->row->language=='*') ) : ?>
					<br/><small><?php echo JText::_( $this->row->language=='*' ? 'FLEXI_ORIGINAL_CONTENT_ALL_LANGS' : 'FLEXI_ORIGINAL_TRANSLATION_CONTENT' );?></small>
					<input type="hidden" name="jform[lang_parent_id]" id="jform_lang_parent_id" value="<?php echo $this->row->id; ?>" />
				<?php else : ?>
					<?php
					if (1) { // currently selecting associated item, is always allowed in backend
						$jAp= JFactory::getApplication();
						$option = JRequest::getVar('option');
						$jAp->setUserState( $option.'.itemelement.langparent_item', 1 );
						$jAp->setUserState( $option.'.itemelement.type_id', $this->row->type_id);
						$jAp->setUserState( $option.'.itemelement.created_by', $this->row->created_by);
						//echo '<small>'.JText::_( 'FLEXI_ORIGINAL_CONTENT_IGNORED_IF_DEFAULT_LANG' ).'</small><br/>';
						echo $this->form->getInput('lang_parent_id');
					?>
						<span class="editlinktip hasTip" style="display:inline-block;" title="<?php echo htmlspecialchars(JText::_( 'FLEXI_NOTES' ), ENT_COMPAT, 'UTF-8'); ?>::<?php echo htmlspecialchars(JText::_( 'FLEXI_ORIGINAL_CONTENT_IGNORED_IF_DEFAULT_LANG' ), ENT_COMPAT, 'UTF-8');?>">
							<?php echo $infoimage; ?>
						</span>
					<?php
					} else {
						echo JText::_( 'FLEXI_ORIGINAL_CONTENT_ALREADY_SET' );
					}
					?>
				<?php endif; ?>
				</div>

<?php
//include('development_tmp.php');
?>

				<div class="fcclear"></div>
				<label id="langassocs-lbl" for="langassocs" class="flexi_label" >
					<?php echo JText::_( 'FLEXI_ASSOC_TRANSLATIONS' );?>
				</label>
				
				<div class="container_fcfield container_fcfield_name_langassocs">
				<?php
				if ( !empty($this->lang_assocs) )
				{
					$row_modified = 0;
					foreach($this->lang_assocs as $assoc_item) {
						if ($assoc_item->id == $this->row->lang_parent_id) {
							$row_modified = strtotime($assoc_item->modified);
							if (!$row_modified)  $row_modified = strtotime($assoc_item->created);
						}
					}
					
					foreach($this->lang_assocs as $assoc_item)
					{
						if ($assoc_item->id==$this->row->id) continue;
						
						$_link  = 'index.php?option=com_flexicontent&'.$task_items.'edit&cid='. $assoc_item->id;
						$_title = htmlspecialchars(JText::_( 'FLEXI_EDIT_ASSOC_TRANSLATION' ), ENT_COMPAT, 'UTF-8').':: ['. $assoc_item->lang .'] '. htmlspecialchars($assoc_item->title, ENT_COMPAT, 'UTF-8');
						echo "<a class='fc_assoc_translation editlinktip hasTip' target='_blank' href='".$_link."' title='".$_title."' >";
						//echo $assoc_item->id;
						if ( !empty($assoc_item->lang) && !empty($this->langs->{$assoc_item->lang}->imgsrc) ) {
							echo ' <img src="'.$this->langs->{$assoc_item->lang}->imgsrc.'" alt="'.$assoc_item->lang.'" />';
						} else if( !empty($assoc_item->lang) ) {
							echo $assoc_item->lang=='*' ? JText::_("All") : $assoc_item->lang;
						}
						
						$assoc_modified = strtotime($assoc_item->modified);
						if (!$assoc_modified)  $assoc_modified = strtotime($assoc_item->created);
						if ( $assoc_modified < $row_modified ) echo "(!)";
						echo "</a>";
					}
				}
				?>
				</div>
			<?php endif; /* IF enable_translation_groups */ ?>
			
		</fieldset>
	<?php endif; /* IF language */ ?>


	<?php if ( $this->perms['canright'] ) : ?>
		<?php
		$this->document->addScriptDeclaration("
			window.addEvent('domready', function() {
			var slideaccess = new Fx.Slide('tabacces');
			var slidenoaccess = new Fx.Slide('notabacces');
			slideaccess.hide();
				$$('fieldset.flexiaccess legend').addEvent('click', function(ev) {
					slideaccess.toggle();
					slidenoaccess.toggle();
					});
				});
			");
		?>
		
		<fieldset class="flexiaccess">
			<legend><?php echo JText::_( 'FLEXI_RIGHTS_MANAGEMENT' ); ?></legend>
			<div id="tabacces">
				<div id="access"><?php echo $this->form->getInput('rules'); ?></div>
			</div>
			<div id="notabacces">
			<?php echo JText::_( 'FLEXI_RIGHTS_MANAGEMENT_DESC' ); ?>
			</div>
		</fieldset>

	<?php endif; ?>

	</div> <!-- end tab -->



<?php
$type_lbl = $this->row->type_id ? JText::_( 'FLEXI_ITEM_TYPE' ) . ' : ' . $this->typesselected->name : JText::_( 'FLEXI_TYPE_NOT_DEFINED' );
?>
<?php if ($this->fields && $this->row->type_id) : ?>
	
	<div class='tabbertab' id='fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>' >
		<h3 class="tabberheading"> <?php echo $type_lbl; ?> </h3>
		
		<?php
			$this->document->addScriptDeclaration("
				jQuery(document).ready(function() {
					jQuery('#jform_type_id').change(function() {
						if (jQuery('#jform_type_id').val() != '".$this->row->type_id."')
							jQuery('#fc-change-warning').css('display', 'block');
						else
							jQuery('#fc-change-warning').css('display', 'none');
					});
				});
			");
		?>
		
		<div class="fc_edit_container_full">
			
			<?php
			$hidden = array('fcloadmodule', 'fcpagenav', 'toolbar');
			$noplugin = '<div class="fc-mssg fc-warning">'. JText::_( 'FLEXI_PLEASE_PUBLISH_PLUGIN' ) .'</div>';
			$row_k = 0;
			foreach ($this->fields as $field)
			{
				// SKIP backend hidden fields from this listing
				if (
					($field->iscore && $field->field_type!='maintext')  ||
					$field->parameters->get('backend_hidden')  ||
					(in_array($field->field_type, $hidden) && empty($field->html)) ||
					in_array($field->formhidden, array(2,3))
				) continue;
				
				// check to SKIP (hide) field e.g. description field ('maintext'), alias field etc
				if ( $this->tparams->get('hide_'.$field->field_type) ) continue;
				
				$not_in_tabs = "";
				if ($field->field_type=='groupmarker') {
					echo $field->html;
					continue;
				} else if ($field->field_type=='coreprops') {
					// not used in backend (yet?)
					continue;
				}
				
				// Decide label classes, tooltip, etc
				$lbl_class = 'flexi_label';
				$lbl_title = '';
				// field has tooltip
				$edithelp = $field->edithelp ? $field->edithelp : 1;
				if ( $field->description && ($edithelp==1 || $edithelp==2) ) {
					 $lbl_class .= ' hasTip'.($edithelp==2 ? ' fc_tooltip_icon_bg' : '');
					 $lbl_title = '::'.htmlspecialchars($field->description, ENT_COMPAT, 'UTF-8');
				}
				// field is required
				$required = $field->parameters->get('required', 0 );
				if ($required)  $lbl_class .= ' required';
				
				// Some fields may force a container width ?
				$row_k = 1 - $row_k;
				$width = $field->parameters->get('container_width', '' );
				$width = !$width ? '' : 'width:' .$width. ($width != (int)$width ? 'px' : '');
				$container_class = "fcfield_row".$row_k." container_fcfield container_fcfield_id_".$field->id." container_fcfield_name_".$field->name;
				?>
				
				<div class='fcclear'></div>
				<label for="<?php echo (FLEXI_J16GE ? 'custom_' : '').$field->name;?>" for_bck="<?php echo (FLEXI_J16GE ? 'custom_' : '').$field->name;?>" class="<?php echo $lbl_class;?>" title="<?php echo $lbl_title;?>" >
					<?php echo $field->label; ?>
				</label>
				
				<div style="<?php echo $width; ?>;" class="<?php echo $container_class;?>" id="container_fcfield_<?php echo $field->id;?>">
					<?php echo ($field->description && $edithelp==3) ? '<div class="fc_mini_note_box">'.$field->description.'</div>' : ''; ?>
					
				<?php // CASE 1: CORE 'description' FIELD with multi-tabbed editing of joomfish (J1.5) or falang (J2.5+)
					if ($field->field_type=='maintext' && isset($this->row->item_translations) ) : ?>
					
					<!-- tabber start -->
					<div class="fctabber" style=''>
						<div class="tabbertab" style="padding: 0px;" >
							<h3 class="tabberheading"> <?php echo '- '.$itemlangname.' -'; // $t->name; ?> </h3>
							<?php
								$field_tab_labels = & $field->tab_labels;
								$field_html       = & $field->html;
								echo !is_array($field_html) ? $field_html : flexicontent_html::createFieldTabber( $field_html, $field_tab_labels, "");
							?>
						</div>
						<?php foreach ($this->row->item_translations as $t): ?>
							<?php if ($itemlang!=$t->shortcode && $t->shortcode!='*') : ?>
								<div class="tabbertab" style="padding: 0px;" >
									<h3 class="tabberheading"> <?php echo $t->name; // $t->shortcode; ?> </h3>
									<?php
									$field_tab_labels = & $t->fields->text->tab_labels;
									$field_html       = & $t->fields->text->html;
									echo !is_array($field_html) ? $field_html : flexicontent_html::createFieldTabber( $field_html, $field_tab_labels, "");
									?>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
					<!-- tabber end -->
					
				<?php elseif ( !is_array($field->html) ) : /* CASE 2: NORMAL FIELD non-tabbed */ ?>
					
					<?php echo isset($field->html) ? $field->html : $noplugin; ?>
					
				<?php else : /* MULTI-TABBED FIELD e.g textarea, description */ ?>
					
					<!-- tabber start -->
					<div class="fctabber">
					<?php foreach ($field->html as $i => $fldhtml): ?>
						<?php
							// Hide field when it has no label, and skip creating tab
							$not_in_tabs .= !isset($field->tab_labels[$i]) ? "<div style='display:none!important'>".$field->html[$i]."</div>" : "";
							if (!isset($field->tab_labels[$i]))	continue;
						?>
								
						<div class="tabbertab">
							<h3 class="tabberheading"> <?php echo $field->tab_labels[$i]; // Current TAB LABEL ?> </h3>
							<?php
								echo $not_in_tabs;      // Output hidden fields (no tab created), by placing them inside the next appearing tab
								$not_in_tabs = "";      // Clear the hidden fields variable
								echo $field->html[$i];  // Current TAB CONTENTS
							?>
						</div>
								
					<?php endforeach; ?>
					</div>
					<!-- tabber end -->
					<?php echo $not_in_tabs;      // Output ENDING hidden fields, by placing them outside the tabbing area ?>
							
				<?php endif; /* END MULTI-TABBED FIELD */ ?>
				
				</div>
				
			<?php
			}
			?>
			
		</div>

	</div> <!-- end tab -->
	
<?php else : /* NO TYPE SELECTED */ ?>

	<div class='tabbertab' id='fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>' >
		<h3 class="tabberheading"> <?php echo $type_lbl; ?> </h3>
		
		<div class="fc_edit_container_full">
			<?php if ($this->row->id == 0) : ?>
				<input name="jform[type_id_not_set]" value="1" type="hidden" />
				<div class="fc-mssg fc-note"><?php echo JText::_( 'FLEXI_CHOOSE_ITEM_TYPE' ); ?></div>
			<?php else : ?>
				<div class="fc-mssg fc-warning"><?php echo JText::_( 'FLEXI_NO_FIELDS_TO_TYPE' ); ?></div>
			<?php	endif; ?>
		</div>
		
	</div> <!-- end tab -->
	
<?php	endif; ?>


	<div class='tabbertab' id='fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>' >
		<h3 class="tabberheading"> <?php echo JText::_('FLEXI_PUBLISHING'); ?> </h3>
		
		<?php
		$hide_style = $this->perms['canparams'] ? '' : 'visibility:hidden;';
		/*if (isset($fieldSet->description) && trim($fieldSet->description)) :
			echo '<p class="tip">'.$this->escape(JText::_($fieldSet->description)).'</p>';
		endif;*/
		?>
		
		<fieldset class="flexi_params fc_edit_container_full" style="<?php echo $hide_style; ?>" >
			<div class='fc_mini_note_box'>
			<?php
				// Dates displayed in the item form, are in user timezone for J2.5, and in site's default timezone for J1.5
				$site_zone = JFactory::getApplication()->getCfg('offset');
				$user_zone = JFactory::getUser()->getParam('timezone', $site_zone);
				if (FLEXI_J16GE) {
					$tz = new DateTimeZone( $user_zone );
					$tz_offset = $tz->getOffset(new JDate()) / 3600;
				} else {
					$tz_offset = $site_zone;
				}
				$tz_info =  $tz_offset > 0 ? ' UTC +' . $tz_offset : ' UTC ' . $tz_offset;
				if (FLEXI_J16GE) $tz_info .= ' ('.$user_zone.')';
				echo JText::sprintf( FLEXI_J16GE ? 'FLEXI_DATES_IN_USER_TIMEZONE_NOTE' : 'FLEXI_DATES_IN_SITE_TIMEZONE_NOTE', '<br/>', $tz_info );
			?>
			</div>
			
			
			<?php /*if ($this->perms['isSuperAdmin']) :*/ ?>
			<div class="fcclear"></div><?php echo $this->form->getLabel('created_by'); ?>
			<div class="container_fcfield"><?php echo $this->form->getInput('created_by'); ?></div>
			<?php /*endif;*/ ?>
			
			<?php if ($this->perms['editcreationdate']) : ?>
			<div class="fcclear"></div><?php echo $this->form->getLabel('created'); ?>
			<div class="container_fcfield"><?php echo $this->form->getInput('created'); ?></div>
			<?php endif; ?>
			
			<div class="fcclear"></div><?php echo $this->form->getLabel('created_by_alias'); ?>
			<div class="container_fcfield"><?php echo $this->form->getInput('created_by_alias'); ?></div>
			
			<div class="fcclear"></div><?php echo $this->form->getLabel('publish_up'); ?>
			<div class="container_fcfield"><?php echo $this->form->getInput('publish_up'); ?></div>
			
			<div class="fcclear"></div><?php echo $this->form->getLabel('publish_down'); ?>
			<div class="container_fcfield"><?php echo $this->form->getInput('publish_down'); ?></div>
			
			<div class="fcclear"></div><?php echo $this->form->getLabel('access'); ?>
			<?php if ($this->perms['canacclvl']) :?>
				<div class="container_fcfield"><?php echo $this->form->getInput('access'); ?></div>
			<?php else :?>
				<div class="container_fcfield"><span class="label"><?php echo $this->row->access_level; ?></span></div>
			<?php endif; ?>

		</fieldset>
		
	</div> <!-- end tab -->
	
	
	
	<div class='tabbertab' id='fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>' >
		<h3 class="tabberheading"> <?php echo JText::_('FLEXI_META_SEO'); ?> </h3>
		
		<?php
		//echo $this->form->getLabel('metadesc');
		//echo $this->form->getInput('metadesc');
		//echo $this->form->getLabel('metakey');
		//echo $this->form->getInput('metakey');
		?>
		
		<fieldset class="panelform params_set">
			<legend>
				<?php echo JText::_( 'FLEXI_META' ); ?>
			</legend>
			
			<div class="fcclear"></div>
			<?php echo $this->form->getLabel('metadesc'); ?>
			
			<div class="container_fcfield">
				
				<?php	if ( isset($this->row->item_translations) ) : ?>
					
					<!-- tabber start -->
					<div class="fctabber" style='display:inline-block;'>
						<div class="tabbertab" style="padding: 0px;" >
							<h3 class="tabberheading"> <?php echo '-'.$itemlang.'-'; // $t->name; ?> </h3>
							<?php echo $this->form->getInput('metadesc');?>
						</div>
						<?php foreach ($this->row->item_translations as $t): ?>
							<?php if ($itemlang!=$t->shortcode && $t->shortcode!='*') : ?>
								<div class="tabbertab" style="padding: 0px;" >
									<h3 class="tabberheading"> <?php echo $t->shortcode; // $t->name; ?> </h3>
									<?php
									$ff_id = 'jfdata_'.$t->shortcode.'_metadesc';
									$ff_name = 'jfdata['.$t->shortcode.'][metadesc]';
									?>
									<textarea id="<?php echo $ff_id; ?>" class="inputbox" rows="3" cols="46" name="<?php echo $ff_name; ?>"><?php echo @$t->fields->metadesc->value; ?></textarea>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
					<!-- tabber end -->
				
				<?php else : ?>
					<?php echo $this->form->getInput('metadesc'); ?>
				<?php endif; ?>
				
			</div>
			
			<div class="fcclear"></div>
			<?php echo $this->form->getLabel('metakey'); ?>
			
			<div class="container_fcfield">
				<?php	if ( isset($this->row->item_translations) ) :?>
					
					<!-- tabber start -->
					<div class="fctabber" style='display:inline-block;'>
						<div class="tabbertab" style="padding: 0px;" >
							<h3 class="tabberheading"> <?php echo '-'.$itemlang.'-'; // $t->name; ?> </h3>
							<?php echo $this->form->getInput('metakey');?>
						</div>
						<?php foreach ($this->row->item_translations as $t): ?>
							<?php if ($itemlang!=$t->shortcode && $t->shortcode!='*') : ?>
								<div class="tabbertab" style="padding: 0px;" >
									<h3 class="tabberheading"> <?php echo $t->shortcode; // $t->name; ?> </h3>
									<?php
									$ff_id = 'jfdata_'.$t->shortcode.'_metakey';
									$ff_name = 'jfdata['.$t->shortcode.'][metakey]';
									?>
									<textarea id="<?php echo $ff_id; ?>" class="inputbox" rows="3" cols="46" name="<?php echo $ff_name; ?>"><?php echo @$t->fields->metakey->value; ?></textarea>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
					<!-- tabber end -->
					
				<?php else : ?>
					<?php echo $this->form->getInput('metakey'); ?>
				<?php endif; ?>
				
				</div>

			<?php foreach($this->form->getGroup('metadata') as $field): ?>
				<div class="fcclear"></div>
				<?php if ($field->hidden): ?>
					<span style="visibility:hidden !important;">
						<?php echo $field->input; ?>
					</span>
				<?php else: ?>
					<?php echo $field->label; ?>
					<div class="container_fcfield">
						<?php echo $field->input;?>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</fieldset>
		
		<fieldset class="panelform params_set">
			<legend>
				<?php echo JText::_( 'FLEXI_SEO' ); ?>
			</legend>
			
			<?php foreach ($this->form->getFieldset('params-seoconf') as $field) : ?>
				<div class="fcclear"></div>
				<?php echo $field->label; ?>
				<div class="container_fcfield">
					<?php echo $field->input;?>
				</div>
			<?php endforeach; ?>
			
		</fieldset>
		
	</div> <!-- end tab -->
	
	
	<div class='tabbertab' id='fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>' >
		<h3 class="tabberheading"> <?php echo JText::_('FLEXI_DISPLAYING'); ?> </h3>
		
		<?php //echo JHtml::_('sliders.start','plugin-sliders-'.$this->row->id, array('useCookie'=>1)); ?>

		<?php
			$fieldSets = $this->form->getFieldsets('attribs');
			foreach ($fieldSets as $name => $fieldSet) :
				if ( $name=='themes' || $name=='params-seoconf'  || $name=='images' ||  $name=='urls' ) continue;

				//$label = !empty($fieldSet->label) ? $fieldSet->label : 'FLEXI_'.$name.'_FIELDSET_LABEL';
				//echo JHtml::_('sliders.panel', JText::_($label), $name.'-options');
				?>
				<fieldset class="flexi_params panelform">
					<?php foreach ($this->form->getFieldset($name) as $field) : ?>
						<div class="fcclear"></div>
						<?php echo $field->label; ?>
						<?php if (strlen(trim($field->input))) :?>
							<div class="container_fcfield">
								<?php echo $field->input; ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</fieldset>
		<?php endforeach; ?>

		<?php	//echo JHtml::_('sliders.end'); ?>
		
	</div> <!-- end tab -->
	
	
	<?php 
	// *********************
	// JOOMLA IMAGE/URLS TAB
	// *********************
	if (JComponentHelper::getParams('com_content')->get('show_urls_images_backend', 0) ) : ?>
		<div class='tabbertab' id='fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>' >
			<h3 class="tabberheading"> <?php echo JText::_('FLEXI_COMPATIBILITY'); ?> </h3>
			
			<?php
			$fields_grps_compatibility = array('images', 'urls');
			foreach ($fields_grps_compatibility as $name => $fields_grp_name) :
			?>
			
			<fieldset class="flexi_params fc_edit_container_full">
				<?php foreach ($this->form->getGroup($fields_grp_name) as $field) : ?>
					<div class="fcclear"></div>
					<?php if ($field->hidden): ?>
						<span style="visibility:hidden !important;">
							<?php echo $field->input; ?>
						</span>
					<?php else: ?>
						<?php echo $field->label; ?>
						<div class="container_fcfield">
							<?php echo $field->input;?>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</fieldset>
			
			<?php endforeach; ?>
			
		</div>
	<?php endif;
	?>
	
	
	
	<div class='tabbertab' id='fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>' >
		<h3 class="tabberheading"> <?php echo JText::_('FLEXI_TEMPLATE'); ?> </h3>
		
		<fieldset class="fc_edit_container_full">
			<div class="fc-note fc-mssg-inline" style="margin: 8px 0px!important;">
			<?php
				echo JText::_( 'FLEXI_PARAMETERS_LAYOUT_EXPLANATION' ) ;
				$type_default_layout = $this->tparams->get('ilayout');
			?>
				<br/><br/>
				<ol style="margin:0 0 0 16px; padding:0;">
					<li style="margin:0; padding:0;"> Select TEMPLATE layout </li>
					<li style="margin:0; padding:0;"> Open slider with TEMPLATE (layout) PARAMETERS </li>
				</ol>
				<br/>
				<b>NOTE:</b> Common method for -displaying- fields is by <b>editing the template layout</b> in template manager and placing the fields into <b>template positions</b>
			</div>
			
			<?php foreach($this->form->getFieldset('themes') as $field): ?>
				<div class="fcclear"></div>
				<?php if ($field->hidden): ?>
					<span style="visibility:hidden !important;">
						<?php echo $field->input; ?>
					</span>
				<?php else: ?>
					<?php echo $field->label; ?>
					<div class="container_fcfield">
						<?php echo $field->input;?>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>

			<div class="fcclear"></div>
			<span class="fc-success fc-mssg-inline" id='__content_type_default_layout__'>
				<?php echo JText::sprintf( 'FLEXI_USING_CONTENT_TYPE_LAYOUT', $type_default_layout ); ?>
				<?php echo "<br/><br/>". JText::_( 'FLEXI_RECOMMEND_CONTENT_TYPE_LAYOUT' ); ?>
			</span>
			<div class="fcclear"></div>
			
			<?php
			echo JHtml::_('sliders.start','theme-sliders-'.$this->form->getValue("id"), array('useCookie'=>1));
			$groupname = 'attribs';  // Field Group name this is for name of <fields name="..." >
			
			foreach ($this->tmpls as $tmplname => $tmpl) :
				$fieldSets = $tmpl->params->getFieldsets($groupname);
				foreach ($fieldSets as $fsname => $fieldSet) :
					$label = !empty($fieldSet->label) ? $fieldSet->label : JText::_( 'FLEXI_PARAMETERS_THEMES_SPECIFIC' ) . ' : ' . $tmpl->name;
					echo JHtml::_('sliders.panel',JText::_($label), $tmpl->name.'-'.$fsname.'-options');
					if (isset($fieldSet->description) && trim($fieldSet->description)) :
						echo '<p class="tip">'.$this->escape(JText::_($fieldSet->description)).'</p>';
					endif;
					?>
					<fieldset class="panelform">
						<?php foreach ($tmpl->params->getFieldset($fsname) as $field) :
							$fieldname =  $field->__get('fieldname');
							$value = $tmpl->params->getValue($fieldname, $groupname, $this->row->itemparams->get($fieldname));
							echo $tmpl->params->getLabel($fieldname, $groupname);
							echo
								str_replace('jform_attribs_', 'jform_layouts_'.$tmpl->name.'_', 
									str_replace('[attribs]', '[layouts]['.$tmpl->name.']',
										$tmpl->params->getInput($fieldname, $groupname, $value)
									)
								);
						endforeach; ?>
					</fieldset>
				<?php endforeach; //fieldSets ?>
			<?php endforeach; //tmpls ?>
			
			<?php echo JHtml::_('sliders.end'); ?>
		</fieldset>
		
	</div> <!-- end tab -->

<?php
// ***************
// MAIN TABSET END
// ***************
?>
</div> <!-- end of tab set -->
				
				
			</td>
			<td valign="top" width="380px" style="padding: 7px 0 0 5px">

		<?php
		// used to hide "Reset Hits" when hits = 0
		if ( !$this->row->hits ) {
			$visibility = 'style="display: none; visibility: hidden;"';
		} else {
			$visibility = '';
		}

		if ( !$this->row->score ) {
			$visibility2 = 'style="display: none; visibility: hidden;"';
		} else {
			$visibility2 = '';
		}

		?>
		<table width="100%" style="border: 1px dashed silver; padding: 5px; margin-bottom: 10px;">
		<?php
		if ( $this->row->id ) {
		?>
		<tr>
			<td>
				<strong><?php echo JText::_( 'FLEXI_ITEM_ID' ); ?>:</strong>
			</td>
			<td>
				<?php echo $this->row->id; ?>
			</td>
		</tr>
		<?php
		}
		?>
		<tr>
			<td>
				<?php
					$field = isset($this->fields['state']) ? $this->fields['state'] : false;
					if ($field) {
						$label_tooltip = $field->description ? 'class="hasTip" title="'.'::'.htmlspecialchars($field->description, ENT_COMPAT, 'UTF-8').'"' : '';
					} else {
						$label_tooltip = '';
					}
				?>
				<strong <?php echo $label_tooltip; ?>><?php echo $field ? $field->label : JText::_( 'FLEXI_STATE' ); ?></strong>
			</td>
			<td>
				<?php echo $this->published;?>
			</td>
		</tr>
		<tr>
			<td>
				<?php
					$field = isset($this->fields['hits']) ? $this->fields['hits'] : false;
					if ($field) {
						$label_tooltip = $field->description ? 'class="hasTip" title="'.'::'.htmlspecialchars($field->description, ENT_COMPAT, 'UTF-8').'"' : '';
					} else {
						$label_tooltip = '';
					}
				?>
				<strong <?php echo $label_tooltip; ?>><?php echo $field ? $field->label : JText::_( 'FLEXI_HITS' ); ?></strong>
			</td>
			<td>
				<div id="hits" style="float:left;"></div> &nbsp;
				<span <?php echo $visibility; ?>>
					<input name="reset_hits" type="button" class="button" value="<?php echo JText::_( 'FLEXI_RESET' ); ?>" onclick="reseter('<?php echo $ctrl_items; ?>resethits', '<?php echo $this->row->id; ?>', 'hits')" />
				</span>
			</td>
		</tr>
		<tr>
			<td>
				<?php
					$field = isset($this->fields['voting']) ? $this->fields['voting'] : false;
					if ($field) {
						$label_tooltip = $field->description ? 'class="hasTip" title="'.'::'.htmlspecialchars($field->description, ENT_COMPAT, 'UTF-8').'"' : '';
					} else {
						$label_tooltip = '';
					}
				?>
				<strong <?php echo $label_tooltip; ?>><?php echo $field ? $field->label : JText::_( 'FLEXI_SCORE' ); ?></strong>
			</td>
			<td>
				<div id="votes" style="float:left;"></div> &nbsp;
				<span <?php echo $visibility2; ?>>
					<input name="reset_votes" type="button" class="button" value="<?php echo JText::_( 'FLEXI_RESET' ); ?>" onclick="reseter('<?php echo $ctrl_items; ?>resetvotes', '<?php echo $this->row->id; ?>', 'votes')" />
				</span>
			</td>
		</tr>
		<tr>
			<td>
				<?php
					$field = isset($this->fields['modified']) ? $this->fields['modified'] : false;
					if ($field) {
						$label_tooltip = $field->description ? 'class="hasTip" title="'.'::'.htmlspecialchars($field->description, ENT_COMPAT, 'UTF-8').'"' : '';
					} else {
						$label_tooltip = '';
					}
				?>
				<strong <?php echo $label_tooltip; ?>><?php echo $field ? $field->label : JText::_( 'FLEXI_REVISED' ); ?></strong>
			</td>
			<td>
				<?php echo $this->row->last_version;?> <?php echo JText::_( 'FLEXI_TIMES' ); ?>
			</td>
		</tr>
		<tr>
			<td>
				<strong><?php echo JText::_( 'FLEXI_FRONTEND_ACTIVE_VERSION' ); ?></strong>
			</td>
			<td>
				#<?php echo $this->row->current_version;?>
			</td>
		</tr>
		<tr>
			<td>
				<strong><?php echo JText::_( 'FLEXI_FORM_LOADED_VERSION' ); ?></strong>
			</td>
			<td>
				#<?php echo $this->row->version;?>
			</td>
		</tr>
		<tr>
			<td>
				<?php
					$field = isset($this->fields['created']) ? $this->fields['created'] : false;
					if ($field) {
						$label_tooltip = $field->description ? 'class="hasTip" title="'.'::'.htmlspecialchars($field->description, ENT_COMPAT, 'UTF-8').'"' : '';
					} else {
						$label_tooltip = '';
					}
				?>
				<strong <?php echo $label_tooltip; ?>><?php echo $field ? $field->label : JText::_( 'FLEXI_CREATED' ); ?></strong>
			</td>
			<td>
				<?php
				if ( $this->row->created == $this->nullDate ) {
					echo JText::_( 'FLEXI_NEW_ITEM' );
				} else {
					echo JHTML::_('date',  $this->row->created,  JText::_( 'DATE_FORMAT_LC2' ) );
				}
				?>
			</td>
		</tr>
		<tr>
			<td>
				<?php
					$field = isset($this->fields['modified']) ? $this->fields['modified'] : false;
					if ($field) {
						$label_tooltip = $field->description ? 'class="hasTip" title="'.'::'.htmlspecialchars($field->description, ENT_COMPAT, 'UTF-8').'"' : '';
					} else {
						$label_tooltip = '';
					}
				?>
				<strong <?php echo $label_tooltip; ?>><?php echo $field ? $field->label : JText::_( 'FLEXI_MODIFIED' ); ?></strong>
			</td>
			<td>
				<?php
					if ( $this->row->modified == $this->nullDate ) {
						echo JText::_( 'FLEXI_NOT_MODIFIED' );
					} else {
						echo JHTML::_('date',  $this->row->modified, JText::_( 'DATE_FORMAT_LC2' ));
					}
				?>
			</td>
		</tr>
		</table>

	<?php if ($this->params->get('use_versioning', 1)) : ?>
		<table width="100%" style="border: 1px dashed silver; padding: 5px; margin-bottom: 10px;">
			<tr>
				<th style="border-bottom: 1px dotted silver; padding-bottom: 3px;" colspan="4"><?php echo JText::_( 'FLEXI_VERSION_COMMENT' ); ?></th>
			</tr>
			<tr>
				<td><textarea name="jform[versioncomment]" id="versioncomment" style="width: 300px; height: 30px; line-height:100%" rows="5" cols="32"></textarea></td>
			</tr>
		</table>
		
		<?php if ( $this->perms['canversion'] ) : ?>
		<div id="result" >
		<table width="100%" style="border: 1px dashed silver; padding: 5px; margin-bottom: 5px;" cellpadding="0" cellspacing="0">
			<tr>
				<th style="border-bottom: 1px dotted silver; padding: 2px 0 6px 0;" colspan="4"><?php echo JText::_( 'FLEXI_VERSIONS_HISTORY' ); ?></th>
			</tr>
			<?php if ($this->row->id == 0) : ?>
			<tr>
				<td class="versions-first" colspan="4"><?php echo JText::_( 'FLEXI_NEW_ARTICLE' ); ?></td>
			</tr>
			<?php
			else :
			$date_format = (($date_format = JText::_( 'FLEXI_DATE_FORMAT_FLEXI_VERSIONS_J16GE' )) == 'FLEXI_DATE_FORMAT_FLEXI_VERSIONS_J16GE') ? "d/M H:i" : $date_format;
			foreach ($this->versions as $version) :
				$class = ($version->nr == $this->row->version) ? ' class="active-version"' : '';
				if ((int)$version->nr > 0) :
			?>
			<tr<?php echo $class; ?>>
				<td class="versions"><span style="padding: 0 5px 0 0;"><?php echo '#' . $version->nr; ?></span></td>
				<td class="versions"><span style="padding: 0 5px 0 0;"><?php echo JHTML::_('date', (($version->nr == 1) ? $this->row->created : $version->date), $date_format ); ?></span></td>
				<td class="versions"><span style="padding: 0 5px 0 0;"><?php echo ($version->nr == 1) ? flexicontent_html::striptagsandcut($this->row->creator, 25) : flexicontent_html::striptagsandcut($version->modifier, 25); ?></span></td>
				<td class="versions" align="center"><a href="javascript:;" class="hasTip" title="Comment::<?php echo htmlspecialchars($version->comment, ENT_COMPAT, 'UTF-8');?>"><?php echo $commentimage;?></a><?php
				if((int)$version->nr==(int)$this->row->current_version) { ?>
					<a onclick="javascript:return clickRestore('index.php?option=com_flexicontent&view=item&<?php echo $task_items;?>edit&cid=<?php echo $this->row->id;?>&version=<?php echo $version->nr; ?>');" href="#"><?php echo JText::_( 'FLEXI_CURRENT' ); ?></a>
				<?php }else{
				?>
					<a class="modal-versions"
						href="index.php?option=com_flexicontent&view=itemcompare&cid=<?php echo $this->row->id; ?>&version=<?php echo $version->nr; ?>&tmpl=component"
						title="<?php echo JText::_( 'FLEXI_COMPARE_WITH_CURRENT_VERSION' ); ?>"
					>
						<?php echo $viewimage; ?>
					</a>
					<a onclick="javascript:return clickRestore('index.php?option=com_flexicontent&task=items.edit&cid=<?php echo $this->row->id; ?>&version=<?php echo $version->nr; ?>&<?php echo (FLEXI_J30GE ? JSession::getFormToken() : JUtility::getToken());?>=1');"
						href="javascript:;"
						title="<?php echo JText::sprintf( 'FLEXI_REVERT_TO_THIS_VERSION', $version->nr ); ?>"
					>
						<?php echo $revertimage; ?>
					</a>
				<?php }?></td>
			</tr>
			<?php
				endif;
			endforeach;
			endif; ?>
		</table>
		</div>
		<div id="fc_pager"></div>
		<div class="clear"></div>
		<?php endif; ?>
	<?php endif; ?>
	
		</td>
	</tr>
</table>
<?php echo JHTML::_( 'form.token' ); ?>
<input type="hidden" name="option" value="com_flexicontent" />
<input type="hidden" name="jform[id]" value="<?php echo $this->row->id; ?>" />
<input type="hidden" name="controller" value="items" />
<input type="hidden" name="view" value="item" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="unique_tmp_itemid" value="<?php echo JRequest::getVar( 'unique_tmp_itemid' );?>" />
<?php echo $this->form->getInput('hits'); ?>
</form>

</div>

<?php
//keep session alive while editing
JHTML::_('behavior.keepalive');
?>
