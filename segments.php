<?php
//ensure admins only
if(!current_user_can('manage_options')) die("Nope");

global $amped_sense;

//If form submitted, handle action
$message = "";
if( !empty( $_POST ) || count($_GET)>1 ) {
	if(isset($_REQUEST['as_action'])) $message = $amped_sense->handle_action($_REQUEST['as_action']);
}

?>

<div class='ampedsense'>
<?php
if(isset($_GET['as_newsegment']) || isset($_GET['as_editsegment']))
{
	$whatarewedoing = "Create New";
	$editing = false;

	if(isset($_GET['as_editsegment']))
	{
		$editing = true;
		$whatarewedoing = "Editing";
		$segmenti = $_GET['as_segmentindex'];
		$currentsegment = $amped_sense->settings['segments'][$segmenti];
	}

	?>
<h2><?php echo $whatarewedoing; ?> Segment</h2>
<?php $amped_sense->print_logo(); ?>
<p>Set up segments if you want to have specific ads show (or not show) on particular pages. <a href="http://www.ampedsense.com/creating-segments" target="_blank">Learn how</a></p>

<form method='post' class='form-horizontal' id="theform" action="<?php echo admin_url('admin.php?page=ampedsense-segments') ?>">
<?php wp_nonce_field('addsegment'); ?>
<input type='hidden' name='as_action' value='addsegment'>
<?php if($editing && $segmenti>-1) echo "<input type='hidden' name='as_editingsegmenti' value='$segmenti'>"; ?>

<div class='form-group'>
	<label class='col-sm-2 control-label' for='as_devices'>Devices</label>
	<div class='col-sm-6'>
		<select name='as_devices' id='as_devices'>
			<?php
			$deviceoptions = array('dtp','dt','tp','d','t','p');
			foreach($deviceoptions as $option)
			{
				echo "<option value='$option' "; if($editing && $currentsegment['devices']==$option) echo " selected='selected'"; echo ">".$amped_sense->deviceCriteriaToString($option)."</option>";
			}
			?>
		</select>
	</div>
</div>
<div class='form-group'>
	<label class='col-sm-2 control-label' for='as_criteria'>Criteria</label>
	<div class='col-sm-6'>
		<select name='as_criteria' id='as_criteria'>
			<?php
			$criteriaoptions = array('default'=>'Every URL',
									'allpages'=>'All Pages',
									'allposts'=>'All Posts',
									'alllists'=>'All Category Lists',
									'homepage'=>'Home Page',
									'page'=>'Specific Page',
									'post'=>'Specific Post',
									'list'=>'Specific Category List',
									'category'=>'Posts in Category');
			foreach($criteriaoptions as $criteriakey=>$criteriaval)
			{
				echo "<option value='$criteriakey' "; if($editing && $currentsegment['criteria']==$criteriakey) echo " selected='selected'"; echo ">$criteriaval</option>";
			}
			?>
		</select>
	</div>
</div>
<div class='form-group as_criteriasetting_page'>
	<label class='col-sm-2 control-label' for='as_criteriaparam_page'>Page</label>
	<div class='col-sm-6'>
		<select name='as_criteriaparam_page' id='as_criteriaparam_page'>
			<?php
			$args = array(
				'post_type' => 'page',
				'post_status' => 'publish'
				//defaults to all pages
			); 
			$pages = get_pages($args);
			foreach($pages as $page)
			{
				echo "<option value='".$page->ID."' "; if($editing && $currentsegment['criteriaparam']==$page->ID) echo " selected='selected'"; echo ">".$page->post_title."</option>";
			}
			?>
		</select>
	</div>
</div>
<div class='form-group as_criteriasetting_post'>
	<label class='col-sm-2 control-label' for='as_criteriaparam_post'>Post</label>
	<div class='col-sm-6'>
		<select name='as_criteriaparam_post' id='as_criteriaparam_post'>
			<?php
			$args = array(
				'post_type' => 'post',
				'post_status' => 'publish',
				'posts_per_page' => 1000 //defaults to only 5, -1 means unlimited but is undocumented, works unless there's too many. Cap this anyway (a site with 9k pages errored out).
			); 
			$posts = get_posts($args);
			foreach($posts as $post)
			{
				echo "<option value='".$post->ID."'"; if($editing && $currentsegment['criteriaparam']==$post->ID) echo " selected='selected'"; echo ">".$post->post_title."</option>";
			}
			?>
		</select>
	</div>
</div>
<div class='form-group as_criteriasetting_category'>
	<label class='col-sm-2 control-label' for='as_criteriaparam_category'>Category</label>
	<div class='col-sm-6'>
		<select name='as_criteriaparam_category' id='as_criteriaparam_category'>
			<?php
			$args = array(
				'hide_empty' => 0
			); 
			$categories = get_categories( $args );
			foreach($categories as $cat)
			{
				echo "<option value='".$cat->cat_ID."'"; if($editing && $currentsegment['criteriaparam']==$cat->cat_ID) echo " selected='selected'"; echo ">".$cat->name."</option>";
			}
			?>
		</select>
	</div>
</div>
<div class='form-group'>
	<label class='col-sm-2 control-label' for='as_segmentname'>Segment Name</label>
	<div class='col-sm-6'>
		<input type='text' class='form-control' name='as_segmentname' id='as_segmentname' <?php if($editing) echo "value=\"$currentsegment[segmentname]\""; ?>>
	</div>
</div>
<div class='form-group'>
	<div class="col-sm-offset-2 col-sm-6">
		<button type='submit' class='btn btn-primary'>Save Segment</button>
	</div>
</div>
</form>

<script>
function toggleCriteriaSettings() {
	var val = jQuery('#as_criteria').val();

	//show/hide other fields
	if(val=='page')
	{
		jQuery('.as_criteriasetting_post').hide();
		jQuery('.as_criteriasetting_category').hide();
		jQuery('.as_criteriasetting_page').show();
	}
	else if(val=='post')
	{
		jQuery('.as_criteriasetting_page').hide();
		jQuery('.as_criteriasetting_category').hide();
		jQuery('.as_criteriasetting_post').show();
	}
	else if(val=='category' || val=='list')
	{
		jQuery('.as_criteriasetting_page').hide();
		jQuery('.as_criteriasetting_post').hide();
		jQuery('.as_criteriasetting_category').show();
	}
	else
	{
		jQuery('.as_criteriasetting_page').hide();
		jQuery('.as_criteriasetting_post').hide();
		jQuery('.as_criteriasetting_category').hide();
	}
}

function suggestSegmentName() {
	var val = jQuery('#as_criteria').val();
	
	//set name
	if(val=='default')
	{
		jQuery('#as_segmentname').val('Entire Site');
	}
	if(val=='allpages')
	{
		jQuery('#as_segmentname').val('All Pages');
	}
	else if(val=='allposts')
	{
		jQuery('#as_segmentname').val('All Posts');
	}
	else if(val=='alllists')
	{
		jQuery('#as_segmentname').val('All Lists');
	}
	else if(val=='homepage')
	{
		jQuery('#as_segmentname').val('Home Page');
	}
	else if(val=='page')
	{
		var pgname = jQuery('#as_criteriaparam_page option:selected').text();
		jQuery('#as_segmentname').val(pgname);
	}
	else if(val=='post')
	{
		var postname = jQuery('#as_criteriaparam_post option:selected').text();
		jQuery('#as_segmentname').val(postname);
	}
	else if(val=='list')
	{
		var catname = jQuery('#as_criteriaparam_category option:selected').text();
		jQuery('#as_segmentname').val(catname + ' List');
	}
	else if(val=='category')
	{
		var catname = jQuery('#as_criteriaparam_category option:selected').text();
		jQuery('#as_segmentname').val(catname + ' Posts');
	}
}
jQuery('#as_criteria, #as_criteriaparam_page, #as_criteriaparam_post, #as_criteriaparam_category').change(toggleCriteriaSettings);
jQuery('#as_criteria, #as_criteriaparam_page, #as_criteriaparam_post, #as_criteriaparam_category').change(suggestSegmentName);
toggleCriteriaSettings(); //call now to set on first page load
<?php 
if(!$editing) { //and suggest name if new segment
	?> suggestSegmentName(); <?php
} ?>

//validation
jQuery('#theform').submit(function() {
	//segmentname
	if(jQuery('#as_segmentname').val()=="")
	{
		alert('Please enter the segment name');
		jQuery('#as_segmentname').focus();
		return false;
	}
	return true;
});
</script>
	<?php
}
else
{
	?>
<h2>Segments</h2>
<?php $amped_sense->print_logo(); ?>
<h4>Segments are a way to separate your traffic into specific categories</h4>
<p>Need help? <a href="http://www.ampedsense.com/creating-segments" target="_blank">How to use segments</a></p>
<?php if($message) echo "<div class='as_statusmessage'>$message</div>"; ?>
<table class='table table-hover'>
	<tr><th></th><th>Name</th><th>Testing?</th><th>Devices</th><th>Criteria</th><th>Priority</th></tr>
	<?php
	if(count($amped_sense->settings['segments']))
	{
		echo "<form method='post'>";
		wp_nonce_field('reordersegments');
		echo "<input type='hidden' name='as_action' value='reordersegments'>";
		foreach($amped_sense->settings['segments'] as $i=>$seg)
		{
			$istesting = !(isset($seg['hide']) && $seg['hide']==1);
			echo "<tr>";
			echo "<td>";
			echo "<a href='".wp_nonce_url(admin_url('admin.php?page=ampedsense-segments')."&as_action=deletesegment&as_segmentindex=$i","deletesegment")."' title='Delete' onClick=\"return confirm('Are you sure you want to delete? All ad recipes associated with this segment will also be deleted.')\"><img src='".$amped_sense->get_admin_dir()."resources/delete.png' /></a> ";
			echo "<a href='".admin_url('admin.php?page=ampedsense-segments')."&as_editsegment=1&as_segmentindex=$i' title='Edit' onClick=\"return confirm('Editing a segment with active recipes could affect the accuracy of your results. Are you sure you want to edit?')\"><img src='".$amped_sense->get_admin_dir()."resources/edit.png' /></a> ";
			echo "</td>";
			echo "<td>$seg[segmentname]</td>";
			echo "<td><a href='".wp_nonce_url(admin_url('admin.php?page=ampedsense-segments')."&as_action=togglesegment&as_segmentindex=$i","togglesegment")."' title='Set to NO to hide segment stats'>".($istesting ? "YES" : "NO")."</a></td>";
			echo "<td>".$amped_sense->deviceCriteriaToString($seg['devices'])."</td>";
			echo "<td>$seg[criteria]";
			if(!empty($seg['criteriaparam'])) echo ": $seg[criteriaparam]";
			echo "</td>";
			echo "<td><input type='text' name='priority[$i]' value='".($i+1)."'/></td>";
			
			echo "</tr>";
		}
		echo "<tr><td colspan=4></td><td><button type='submit' class='btn btn-primary'>Reorder Segments</button></td></tr>";
		echo "</form>";
	}
	else
	{
		echo "<tr><td colspan=5>No segments. Add one below!</td></tr>";
	}
	?>
</table>
<br/>
<a href='<?php echo admin_url('admin.php?page=ampedsense-segments'); ?>&as_newsegment=1'>Create New Segment</a>
	<?php
}
?>
</div>