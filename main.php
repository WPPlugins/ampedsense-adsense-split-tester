<?php
//ensure admins only
if(!current_user_can('manage_options')) die("Nope");

global $amped_sense;

// if form submitted, handle action
$message = "";
if( !empty( $_POST ) || count($_GET)>1 )
{
	if(!empty($_REQUEST['as_action']))
	{
		$message = $amped_sense->handle_action($_REQUEST['as_action']);
	}
}
//note about caching
$cacheMessage = "";
$activePlugins = get_option( 'active_plugins' );
$cachePluginNames = array('cache', 'rocket', 'wordfence');
foreach($activePlugins as $activePluginPath)
{
	foreach($cachePluginNames as $cachePluginName)
	{
		if(stristr($activePluginPath,$cachePluginName))
		{
			if($amped_sense->settings['render'] == 'client')
			{
				$cacheMessage = "Looks like you may be using a caching plugin. Please remember to <b style='color:red'>reset your cache</b> after making changes to your ads. <a href='http://www.ampedsense.com/split-testing-and-caching-plugins/' target='_blank' style='color:white; text-decoration:underline'>Learn more.</a>";
			}
			else
			{
				$cacheMessage = "Looks like you may be using a caching plugin. Consider <b style='color:red'>switching to client-side</b> render mode for more equally distributed split testing. <a href='http://www.ampedsense.com/render-mode-client-vs-server/' target='_blank' style='color:white; text-decoration:underline'>Learn more.</a>";
			}
			break 2;
		}
    }
}
?>

<div class='ampedsense'>

<?php
if(isset($_GET['as_newrecipe']) || isset($_GET['as_editrecipe']) || isset($_GET['as_copyrecipe']))
{
	$segmenti = $_GET['as_segmenti'];
	$maxads = 10;
	
	$editingrecipe = false;
	$whatarewedoing = "Create New";
	if(isset($_GET['as_editrecipe']))
	{
		$editingrecipe = true;
		$whatarewedoing = "Editing";
		$recipei = $_GET['as_recipei'];
		$currentrecipe = $amped_sense->settings['segments'][$segmenti]['recipes'][$recipei];
	}
	elseif(isset($_GET['as_copyrecipe']))
	{
		$editingrecipe = true;
		$whatarewedoing = "Duplicating";
		$recipei = $_GET['as_recipei'];
		$currentrecipe = $amped_sense->settings['segments'][$segmenti]['recipes'][$recipei];
		$recipei = -1; //don't pass recipei in form (must do after we retrieve recipe above
		//now that we have the recipe desired, let's set the segmenti to where they want it copied to
		$segmenti = $_GET['as_tosegmenti'];
		//make sure they rename
		$currentrecipe['recipename'] = "";
		$currentrecipe['channelname'] = "";
		$currentrecipe['channelid'] = 0;
	}
	
	?>
	<h2><?php echo $whatarewedoing; ?> Ad Recipe</h2>
	<?php $amped_sense->print_logo(); ?>
	<p>An <i><b>"ad recipe"</b></i> is a set of ads you want to test against another set of ads.<br/>For example, to see if 1 large ad is better than 2 small ads, create one recipe with 1 large ad, and another recipe with 2 small ads. <a href='http://www.ampedsense.com/creating-adsense-split-test-in-wordpress/' target='_blank'>More Info</a></p>
	<form method='post' class='form-horizontal' id="theform" action="<?php echo admin_url('admin.php?page=ampedsense-main') ?>">
	<?php wp_nonce_field('addrecipe'); ?>
	<input type='hidden' name='as_action' value='addrecipe'>
	<input type='hidden' name='as_segmenti' value='<?php echo $segmenti; ?>'>
	<?php if($editingrecipe && $recipei>-1) echo "<input type='hidden' name='as_editingrecipei' value='$recipei'>"; ?>
	<div class='form-group'>
		<label class='col-sm-2 control-label' for='as_numads'># Ads on page</label>
		<div class='col-sm-6'>
			<select name='as_numads' id='as_numads'>
				<?php
				for($i=1; $i<=$maxads; $i++)
				{
					echo "<option value='$i'"; if($editingrecipe && count($currentrecipe['ads'])==$i) echo " selected='selected'"; echo ">$i</option>";
				}
				?>
			</select>
			<br/>
			Please ensure you are familiar with <a href='https://support.google.com/adsense/answer/1346295?hl=en' target='_blank'>Google's ad placement policy</a>
			<div id='as_upgradedisclaimer' class='as_statusmessage' style='display:none'>Multiple ads per recipe is a premium feature of AmpedSense.<br/> <a href='http://www.ampedsense.com/premium/' target='_blank' style='color:blue'>Learn more about upgrading</a></div>
		</div>
	</div>
	<?php
	$j = (isset($amped_sense->settings['lcse_v']) && $amped_sense->settings['lcse_v']=='p') ? 10 : 1; 
	for($i=1; $i <= $j; $i++)
	{
		$offset = $i-1;
		$existingad = isset($currentrecipe['ads'][$offset]) ? $currentrecipe['ads'][$offset] : null;
		printAdOptions($i,$editingrecipe,$existingad);
	}
	?>
	
	<div class="form-group">
		<div class='col-sm-8'>
			<hr style='border-top:1px solid black'/>
		</div>
	</div>
	
	<div class='form-group'>
		<label class='col-sm-2 control-label' for='as_recipename'>Recipe Name</label>
		<div class='col-sm-6'>
			<input type='text' class='form-control' name='as_recipename' id='as_recipename' maxlength=20 <?php if($editingrecipe) echo "value=\"$currentrecipe[recipename]\""; ?>>
		</div>
	</div>
	
	<div class='form-group as_channelinfo'>
		<label class='col-sm-2 control-label' for='as_channelname'>Channel Name</label>
		<div class='col-sm-6'>
			<input type='text' class='form-control' name='as_channelname' id='as_channelname' maxlength=30 readonly="readonly" style="cursor:text" <?php if($editingrecipe) echo "value=\"$currentrecipe[channelname]\""; ?>>
			<br/>
			<a href='<?php echo $amped_sense->get_add_channel_url(); ?>' target='_blank'>Create a new custom channel</a> with the Channel Name above in AdSense.
		</div>
	</div>
	
	<div class='form-group as_channelinfo'>
		<label class='col-sm-2 control-label' for='as_channelcreatedcheck'>Channel Created?</label>
		<div class='col-sm-6'>
			<div class="checkbox">
				<label>
				<input type='checkbox' name='as_channelcreatedcheck' id='as_channelcreatedcheck' value='1' <?php if($editingrecipe && isset($currentrecipe['channelid']) && $currentrecipe['channelid']>0) echo "checked='checked'"; ?>>
				I have created a custom channel with this exact name in AdSense
				</label>
			</div>
		</div>
	</div>
	
	<div class='form-group'>
		<div class="col-sm-offset-2 col-sm-6">
			<button type='submit' id="createbutton" class='btn btn-primary'>Save Ad Recipe</button>
			<button type='submit' id="previewbutton" class='btn btn-default'>Preview</button>
		</div>
	</div>
	</form>
	<?php if($cacheMessage) echo "<div class='as_cachemessage'>$cacheMessage</div>"; ?>
	<script>
	//set form action based on button clicked
	var dovalidation = true;
	jQuery("#createbutton").click(function() {
		jQuery('#theform').attr('action', '<?php echo admin_url('admin.php?page=ampedsense-main'); ?>');
		//jQuery('#theform').attr('method', 'post');
		jQuery('#theform').attr('target', '_self');
		dovalidation = true;
	});
	jQuery("#previewbutton").click(function() {
		var previewurl = "<?php echo $amped_sense->get_segment_preview_url($_GET['as_segmenti']); ?>";
		var previewurl = previewurl + "?as_preview=1&" + jQuery("#theform *:visible").not('.as_customcode').serialize();
		jQuery('#theform').attr('action', previewurl);
		//jQuery('#theform').attr('method', 'get');
		jQuery('#theform').attr('target', 'aspreview');
		dovalidation = false;
	});
	//and auto set channel name
	jQuery("#as_recipename").keyup(setChannelName);
	jQuery("#as_recipename").change(setChannelName);
	function setChannelName()
	{
		//char count:        6                                 3                   1           20                     =  30
		var channelname = "Amped_<?php echo $amped_sense->settings['siteabbrev'];?>_"+jQuery("#as_recipename").val();
		//channelname = channelname.replace(" ","_"); //only replaces one
		channelname = channelname.split(" ").join("_");
		jQuery('#as_channelname').val(channelname);
	}
	
	function toggleChannelInfo()
	{
		var show = false;
		//hide channel info if all custom ads chosen
		for(var i=1; i<=<?php echo $maxads; ?>; i++)
		{
			//show if custom html or responsive
			if(jQuery('#as_customfalse'+i).is(':visible') && (jQuery('#as_customresp'+i).prop('checked') || jQuery('#as_customfalse'+i).prop('checked')))
			{
				show = true;
				break;
			}
		}
		if(show)
		{
			jQuery('.as_channelinfo').show();
		}
		else
		{
			jQuery('.as_channelinfo').hide();
		}
	}
	
	function revealAdOptions()
	{
		var numads = jQuery('#as_numads').val();
		if(numads> <?php echo (isset($amped_sense->settings['lcse_v']) && $amped_sense->settings['lcse_v']=='p') ? 10 : 1; ?>)
		{
			alert('This version of AmpedSense is limited to 1 ad per recipe');
			jQuery('#as_upgradedisclaimer').show();
			jQuery('#as_numads').val(1);
		}
		else if(numads)
		{
			for(var i=1; i<=<?php echo $maxads; ?>; i++)
			{
				var divid = "#adoptions"+i;
				if(i<=numads) jQuery(divid).show();
				else jQuery(divid).hide();
			}
			toggleChannelInfo();
		}
	}
	jQuery('#as_numads').change(revealAdOptions);
	revealAdOptions(); //call now to set on first page load
	
	//validation
	jQuery('#theform').submit(function(event) {
		if(dovalidation)
		{
			//recipename
			if(jQuery('#as_recipename').val()=="")
			{
				alert('Please enter the recipe name');
				jQuery('#as_recipename').focus();
				return false;
			}
			if(jQuery.inArray(jQuery('#as_recipename').val(),[<?php
				//list all current recipe names into a js array
				if(isset($amped_sense->settings['segments']))
				{
					foreach($amped_sense->settings['segments'] as $thissegmenti=>$segment)
					{
						if(isset($segment['recipes']))
						{
							foreach($segment['recipes'] as $thisrecipei=>$recipe)
							{
								if($editingrecipe)
								{
									//include all names except for the one we're editing
									if(!($thissegmenti==$segmenti && $thisrecipei==$recipei))
									{
										echo '"'.$recipe['recipename'].'",';
									}
								}
								else
								{
									//creating a new one, should have all recipes listed here
									echo '"'.$recipe['recipename'].'",';
								}
							}
						}
					}
				}
				?>
				])>-1) //returns the index (0 is success), so check for >-1
			{
				alert('Recipe name already in use, please choose another');
				jQuery('#as_recipename').focus();
				return false;
			}
			//custom channel check
			if(jQuery('#as_channelcreatedcheck').is(':visible') && !jQuery('#as_channelcreatedcheck').prop('checked'))
			{
				alert('Please confirm you have created a custom channel for this recipe');
				jQuery('#as_channelcreatedcheck').focus();
				return false;
			}
		}
		return true;
	});
	</script>
	<?php
}
else
{
	//main screen, list recipes and stats
	?>
	<h2>Welcome to AmpedSense!</h2>
	<?php $amped_sense->print_logo(); ?>
	<p>Congratulations on installing AmpedSense! You're about to run some kick-butt split tests to help you <b>increase your AdSense revenue</b>. Sweet!</p>
	<?php if($message) echo "<div class='as_statusmessage'>$message</div>"; ?>
	<?php if($cacheMessage) echo "<div class='as_cachemessage'>$cacheMessage</div>"; ?>
	
	<?php
	//warn if no access set
	if(!isset($amped_sense->settings['googlerefreshtoken']) || $amped_sense->settings['googlerefreshtoken']=='')
	{
		echo "<p style='color:red'>Login with Google so AmpedSense can retrieve your AdSense settings.<br/>Be sure to use a Google account that is approved for AdSense.</p><a href='".$amped_sense->get_google_login_url()."' class='button'>Retrieve AdSense settings</a><br/><br/><p>Access is read-only and will not change anything in your account. By authenticating, you agree to our <a href='http://www.ampedsense.com/terms/' target='_blank'>terms</a>. </p>";
	}
	else
	{
		$amped_sense->ensureGoogleAccessToken();

		//grab all data from google for each segment date
		$google_success = false;
		$apiresults = array(); //indexed by fromdate

		if(isset($_SESSION['as_googleaccesstoken']) && $_SESSION['as_googleaccesstoken']!="")
		{
			foreach($amped_sense->settings['segments'] as $i=>$segment)
			{
				//only if not hidden
				if(!(isset($segment['hide']) && $segment['hide']==1))
				{
					$fromdate = $amped_sense->get_ir_fromdate($i);
					if(!isset($apiresults[$fromdate]))
					{
						$start = date("Y-m-d",strtotime($fromdate));
						$end = date("Y-m-d");
						$dimension = "CUSTOM_CHANNEL_NAME";
						//don't use MATCHED_AD_REQUESTS, because link units count the next page only, and we should factor in unmatched ad requests too
						//don't use AD_REQUESTS, use PAGE_VIEWS - that's what the adsense portal reports, and it makes most sense
						$metricclause = "metric=PAGE_VIEWS&metric=CLICKS&metric=PAGE_VIEWS_CTR&metric=PAGE_VIEWS_RPM&metric=EARNINGS";
						$returnedjson = $amped_sense->getUrlContents("https://www.googleapis.com/adsense/v1.4/accounts/".urlencode($amped_sense->settings['adsensepublisherid'])."/reports/?access_token=".urlencode($_SESSION['as_googleaccesstoken'])."&userIp=".$amped_sense->ip."&startDate=$start&endDate=$end&useTimezoneReporting=true&dimension=$dimension&$metricclause");
						//echo $returnedjson;
						$apiresult = json_decode($returnedjson,true);
						if(count($apiresult) && empty($apiresult["error"]))
						{
							$google_success =  true;
							$apiresults[$fromdate] = $apiresult;
						}
						else
						{
							$google_success =  false;
							echo "Error: $returnedjson";
							break;
						}
						usleep(100000); //.1 second delay for quota mgmt
					}
				}
			}
		}

	
		//foreach segment, list out recipes and how they're doing
		foreach($amped_sense->settings['segments'] as $i=>$segment)
		{
			//only if not hidden
			if(!(isset($segment['hide']) && $segment['hide']==1))
			{
				$fromdate = $amped_sense->get_ir_fromdate($i);
				$statsheaderrow = "<tr><td colspan=5></td><td align='right'>Page Views</td><td align='right'>Clicks</td><td align='right'>CTR</td><td align='right'>RPM</td><td align='right'>Earnings</td></tr>";
				$irdate = "<input type='hidden' id='datetestinput$i' /> since <span id='datetestdisplay$i' style='cursor:pointer; text-decoration:underline'>$fromdate</span>";
				$script = "<script>
						jQuery('#datetestinput$i').datepicker({
							onSelect: function(d) { window.location='".admin_url('admin.php?page=ampedsense-main')."&as_action=setreportdate&as_segmenti=$i&as_fromdate='+d; },
							defaultDate: '$fromdate',
							dateFormat: 'mm/dd/yy'
						});
						jQuery('#datetestdisplay$i').click(function() {  jQuery('#datetestinput$i').datepicker( 'show' ); });
					</script>";
				
				echo "<h3>".$amped_sense->deviceCriteriaToString($segment['devices'])." - $segment[segmentname]</h3>";
				echo "<table class='table table-hover'>";
				echo "<tr><th></th><th>Recipe</th><th>Date Started</th><th>Status</th><th># Ads</th><th colspan=5 style='text-align:center'>Stats $irdate</th></tr>";
				echo $statsheaderrow;
				if(isset($segment['recipes']) && count($segment['recipes']))
				{
					foreach($segment['recipes'] as $j=>$recipe)
					{
						$previewurl = $amped_sense->get_segment_preview_url($i)."?".$amped_sense->get_recipe_preview_qs($recipe);
						$safenotes = (isset($recipe['notes'])) ? rawurlencode($recipe['notes']) : '';
						$notestitle = ($safenotes=='') ? 'Notes' : 'Notes: '.htmlentities($recipe['notes']);
						echo "<tr>";
						//actions
						echo "<td>";
						echo "<a href='".wp_nonce_url(admin_url('admin.php?page=ampedsense-main')."&as_action=deleterecipe&as_segmenti=$i&as_recipei=$j","deleterecipe")."' title='Delete' onClick=\"return confirm('Are you sure you want to delete?')\"><img src='".$amped_sense->get_admin_dir()."resources/delete.png' /></a> ";
						echo "<a href='".admin_url('admin.php?page=ampedsense-main')."&as_editrecipe=1&as_segmenti=$i&as_recipei=$j' title='Edit' onClick=\"return confirm('Editing a test after it has started could affect the accuracy of your results. Are you sure you want to edit?')\"><img src='".$amped_sense->get_admin_dir()."resources/edit.png' /></a> ";
						echo "<a href='#' onClick='return initClone($i,$j)' title='Duplicate'><img src='".$amped_sense->get_admin_dir()."resources/copy.png' /></a>";
						echo "<a href='#' onClick='return showNotes($i,$j,\"$safenotes\")' title='$notestitle'><img src='".$amped_sense->get_admin_dir()."resources/notes.png' /></a>";
						if($recipe['active']) echo "<a href='".wp_nonce_url(admin_url('admin.php?page=ampedsense-main')."&as_action=pauserecipe&as_segmenti=$i&as_recipei=$j",'pauserecipe')."' title='Pause'><img src='".$amped_sense->get_admin_dir()."resources/pause.png' /></a>";
						else echo "<a href='".wp_nonce_url(admin_url('admin.php?page=ampedsense-main')."&as_action=resumerecipe&as_segmenti=$i&as_recipei=$j","resumerecipe")."' title='Resume'><img src='".$amped_sense->get_admin_dir()."resources/resume.png' /></a>";
						echo "</td>";
						//name
						echo "<td><a href='".$previewurl."' title='Preview' target='_blank'>$recipe[recipename]</a></td>";
						//date
						echo "<td>".date("m/d/Y",$recipe['whenstarted'])."</td>";
						//status
						echo "<td>";
						if($recipe['active']) echo "<span style='color:green'>Active</span>";
						else echo "<span style='color:orange'>Paused</span>";
						echo "</td>";
						//ad count
						echo "<td>".count($recipe['ads'])."</td>";
						//show stats, or link to stats
						if($google_success)
						{
							if(isset($recipe['channelid']) && $recipe['channelid']>0)
							{
								//find corresponding report row
								$reportrow = null;
								if(isset($apiresults[$fromdate]['rows']) && count($apiresults[$fromdate]['rows']))
								{
									foreach($apiresults[$fromdate]['rows'] as $row)
									{
										if($row[0]==$recipe['channelname'])
										{
											$reportrow = $row;
											break;
										}
									}
								}
								
								
								if($reportrow==null)
								{
									echo "<td colspan=5>No data yet (may be delayed up to 1 hour)</td>";
								}
								else
								{
									echo "<td align='right'>$reportrow[1]</td>"; //views
									echo "<td align='right'>$reportrow[2]</td>"; //clicks
									echo "<td align='right'>".$amped_sense->toPercent($reportrow[3])."%</td>"; //ctr
									echo "<td align='right'>\$$reportrow[4]</td>"; //rpm
									echo "<td align='right'>\$$reportrow[5]</td>"; //earnings
								}
							}
							else
							{
								echo "<td colspan=5>Couldn't find channel - please add <a href='".$amped_sense->get_add_channel_url()."' target='_blank'>New Custom Channel</a> named \"$recipe[channelname]\". <br/> <a href='".admin_url('admin.php?page=ampedsense-main')."&as_action=lookupchannels'>Click when done</a></td>";
							}
						}
						else
						{
							echo "<td colspan=5>Google Error. Try again, <a href='".$amped_sense->get_channel_report_url()."' target='_blank'>view stats manually</a> or <a href='".$amped_sense->get_google_login_url()."'>reauthenticate</a></td>";
						}
					}
				}
				else
				{
					echo "<tr><td colspan=10>No ad recipes yet.</td></tr>";
				}
				echo "<tr><td colspan=10>Create <a href='".admin_url('admin.php?page=ampedsense-main')."&as_newrecipe=1&as_segmenti=$i'>new recipe</a></td></tr>";
				echo "</table><br/>";
				echo $script;
			}
		}
		echo '<p>Need help? <a href="http://www.ampedsense.com/creating-adsense-split-test-in-wordpress" target="_blank">Creating your first split test</a></p>';
	}
	//modal for segment selection on clones
	// this is taken outside of 'ampedsense' div, so must re-add class for bootstrap to take effect
	?>
	<div id="clonedialog" title="Segment?" class='ampedsense' style='display:none'>
		<p>Which segment would you like this recipe to be cloned into?</p>
		<form method='get' action='<?php echo admin_url('admin.php'); ?>'>
		<input type='hidden' name='page' value='ampedsense-main'>
		<input type='hidden' name='as_copyrecipe' value='1'>
		<input type='hidden' id='as_segmenti_clone' name='as_segmenti' value=''>
		<input type='hidden' id='as_recipei_clone' name='as_recipei' value=''>
		<select id='as_tosegmenti_clone' name='as_tosegmenti'>
		<?php
		//foreach segment, list out recipes and how they're doing
		foreach($amped_sense->settings['segments'] as $i=>$segment)
		{
			//only if not hidden
			if(!(isset($segment['hide']) && $segment['hide']==1))
			{
				echo "<option value='$i'>".$amped_sense->deviceCriteriaToString($segment['devices'])." - $segment[segmentname]</option>";
			}
		}
		?>
		</select>
		<br/><br/>
		<button type='submit' style='float:right' class='btn btn-primary'>Duplicate recipe</button>
		</form>
	</div>
	<script>
	function initClone(segmenti,recipei)
	{
		jQuery('#as_segmenti_clone').val(segmenti);
		jQuery('#as_recipei_clone').val(recipei);
		jQuery('#as_tosegmenti_clone').val(segmenti); //init to same segment
		jQuery("#clonedialog").dialog();
		return false;
	}
	</script>
	<?php
	//modal for recipe notes
	// this is taken outside of 'ampedsense' div, so must re-add class for bootstrap to take effect
	?>
	<div id="notesdialog" title="Recipe Notes" class='ampedsense' style='display:none'>
		<p>Recipe Notes:</p>
		<form method='post' action='<?php echo admin_url('admin.php?page=ampedsense-main'); ?>'>
		<input type='hidden' name='as_action' value='updatenotes'>
		<?php wp_nonce_field('updatenotes'); ?>
		<input type='hidden' id='as_segmenti_notes' name='as_segmenti' value=''>
		<input type='hidden' id='as_recipei_notes' name='as_recipei' value=''>
		<textarea id='as_recipenotes' name='as_recipenotes' rows=10 cols=55>
		</textarea>
		<br/><br/>
		<button type='submit' style='float:right' class='btn btn-primary'>Save notes</button>
		</form>
	</div>
	<script>
	function showNotes(segmenti,recipei,notes)
	{
		jQuery('#as_segmenti_notes').val(segmenti);
		jQuery('#as_recipei_notes').val(recipei);
		jQuery('#as_recipenotes').val(decodeURIComponent(notes)); 
		jQuery("#notesdialog").dialog({width:'500px'});
		return false;
	}
	</script>
	<?php
}



function printAdOptions($i,$editingad,$currentad)
{
	?>
	<div id='adoptions<?php echo $i; ?>'>
		<div class="form-group">
			<div class='col-sm-8'>
				<hr style='border-top:1px solid black'/>
			</div>
		</div>
		<div class="form-group">
			<label class='col-sm-2 control-label'>Ad <?php echo $i; ?></label>
			<div class='col-sm-6'>
				<label class='radio-inline'><input type='radio' name='as_custom[<?php echo $i; ?>]' id='as_customfalse<?php echo $i; ?>' value='no' <?php if(!$editingad || ($editingad && !isset($currentad)) || ($editingad && $currentad['custom']=='no')) echo "checked='checked'"; ?>>AdSense</label>
				<label class='radio-inline'><input type='radio' name='as_custom[<?php echo $i; ?>]' id='as_customresp<?php echo $i; ?>' value='resp' <?php if($editingad && $currentad['custom']=='resp') echo "checked='checked'"; ?>>Responsive</label>
				<label class='radio-inline'><input type='radio' name='as_custom[<?php echo $i; ?>]' id='as_customhtml<?php echo $i; ?>' value='html' <?php if($editingad && $currentad['custom']=='html') echo "checked='checked'"; ?>>Custom HTML</label>
				
			</div>
		</div>
		
		<div class='form-group adsense<?php echo $i; ?>'>
			<label class='col-sm-2 control-label' for='as_adsize<?php echo $i; ?>'>Ad Size</label>
			<div class='col-sm-6'>
				<select name='as_adsize[<?php echo $i; ?>]' id='as_adsize<?php echo $i; ?>' class='form-control'>
					<optgroup label='Recommended'>
						<option value='728x90' <?php if($editingad && isset($currentad) && $currentad['adsize']=='728x90') echo "selected='selected'"; ?>>728 x 90 - Leaderboard</option>
						<option value='336x280' <?php if($editingad && isset($currentad) && $currentad['adsize']=='336x280') echo "selected='selected'"; ?>>336 x 280 - Large Rectangle</option>
						<option value='320x100' <?php if($editingad && isset($currentad) && $currentad['adsize']=='320x100') echo "selected='selected'"; ?>>320 x 100 - Large Mobile Banner</option>
						<option value='300x600' <?php if($editingad && isset($currentad) && $currentad['adsize']=='300x600') echo "selected='selected'"; ?>>300 x 600 - Large Skyscraper</option>
						<option value='300x250' <?php if($editingad && isset($currentad) && $currentad['adsize']=='300x250') echo "selected='selected'"; ?>>300 x 250 - Medium Rectangle</option>
						<option value='resp' <?php if($editingad && isset($currentad) && $currentad['adsize']=='resp') echo "selected='selected'"; ?>>Auto - Responsive</option>
					</optgroup>
					<optgroup label='Horizontal'>
						<option value='728x90' <?php if($editingad && isset($currentad) && $currentad['adsize']=='728x90') echo "selected='selected'"; ?>>728 x 90 - Leaderboard</option>
						<option value='320x100' <?php if($editingad && isset($currentad) && $currentad['adsize']=='320x100') echo "selected='selected'"; ?>>320 x 100 - Large Mobile Banner</option>
						<option value='970x250' <?php if($editingad && isset($currentad) && $currentad['adsize']=='970x250') echo "selected='selected'"; ?>>970 x 250 - Billboard</option>
						<option value='970x90' <?php if($editingad && isset($currentad) && $currentad['adsize']=='970x90') echo "selected='selected'"; ?>>970 x 90 - Large Leaderboard</option>
						<option value='468x60' <?php if($editingad && isset($currentad) && $currentad['adsize']=='468x60') echo "selected='selected'"; ?>>468 x 60 - Banner</option>
						<option value='320x50' <?php if($editingad && isset($currentad) && $currentad['adsize']=='320x50') echo "selected='selected'"; ?>>320 x 50 - Mobile Banner</option>
						<option value='234x60' <?php if($editingad && isset($currentad) && $currentad['adsize']=='234x60') echo "selected='selected'"; ?>>234 x 60 - Half Banner</option>
					</optgroup>
					<optgroup label='Vertical'>
						<option value='300x600' <?php if($editingad && isset($currentad) && $currentad['adsize']=='300x600') echo "selected='selected'"; ?>>300 x 600 - Large Skyscraper</option>
						<option value='300x1050' <?php if($editingad && isset($currentad) && $currentad['adsize']=='300x1050') echo "selected='selected'"; ?>>300 x 1050 - Portrait</option>
						<option value='160x600' <?php if($editingad && isset($currentad) && $currentad['adsize']=='160x600') echo "selected='selected'"; ?>>160 x 600 - Wide Skyscraper</option>
						<option value='120x600' <?php if($editingad && isset($currentad) && $currentad['adsize']=='120x600') echo "selected='selected'"; ?>>120 x 600 - Skyscraper</option>
						<option value='120x240' <?php if($editingad && isset($currentad) && $currentad['adsize']=='120x240') echo "selected='selected'"; ?>>120 x 240 - Vertical Banner</option>
					</optgroup>
					<optgroup label='Rectangular'>
						<option value='336x280' <?php if($editingad && isset($currentad) && $currentad['adsize']=='336x280') echo "selected='selected'"; ?>>336 x 280 - Large Rectangle</option>
						<option value='300x250' <?php if($editingad && isset($currentad) && $currentad['adsize']=='300x250') echo "selected='selected'"; ?>>300 x 250 - Medium Rectangle</option>
						<option value='250x250' <?php if($editingad && isset($currentad) && $currentad['adsize']=='250x250') echo "selected='selected'"; ?>>250 x 250 - Square</option>
						<option value='200x200' <?php if($editingad && isset($currentad) && $currentad['adsize']=='200x200') echo "selected='selected'"; ?>>200 x 200 - Small Square</option>
						<option value='180x150' <?php if($editingad && isset($currentad) && $currentad['adsize']=='180x150') echo "selected='selected'"; ?>>180 x 150 - Small Rectangle</option>
						<option value='125x125' <?php if($editingad && isset($currentad) && $currentad['adsize']=='125x125') echo "selected='selected'"; ?>>125 x 125 - Button</option>
					</optgroup>
					<optgroup label='Responsive'>
						<option value='resp' <?php if($editingad && isset($currentad) && $currentad['adsize']=='resp') echo "selected='selected'"; ?>>Auto - Responsive</option>
					</optgroup>
					<optgroup label='Link units - Related Topics List'>
						<option value='728x15' <?php if($editingad && isset($currentad) && $currentad['adsize']=='728x15') echo "selected='selected'"; ?>>728 x 15 - Horizontal Large (Link Unit)</option>
						<option value='468x15' <?php if($editingad && isset($currentad) && $currentad['adsize']=='468x15') echo "selected='selected'"; ?>>468 x 15 - Horizontal Medium (Link Unit)</option>
						<option value='200x90' <?php if($editingad && isset($currentad) && $currentad['adsize']=='200x90') echo "selected='selected'"; ?>>200 x 90 - Vertical X-Large (Link Unit)</option>
						<option value='180x90' <?php if($editingad && isset($currentad) && $currentad['adsize']=='180x90') echo "selected='selected'"; ?>>180 x 90 - Vertical Large (Link Unit)</option>
						<option value='160x90' <?php if($editingad && isset($currentad) && $currentad['adsize']=='160x90') echo "selected='selected'"; ?>>160 x 90 - Vertical Medium (Link Unit)</option>
						<option value='120x90' <?php if($editingad && isset($currentad) && $currentad['adsize']=='120x90') echo "selected='selected'"; ?>>120 x 90 - Vertical Small (Link Unit)</option>
					</optgroup>
				</select>
				See example sizes:
				<a href='https://support.google.com/adsense/answer/185665?hl=en&ref_topic=29561' target='_blank'>Text Ads</a> | 
				<a href='https://support.google.com/adsense/answer/185666?hl=en&ref_topic=29561' target='_blank'>Display Ads</a> | 
				<a href='https://support.google.com/adsense/answer/185679?hl=en&ref_topic=29561' target='_blank'>Link Units</a>
			</div>
		</div>

		<div class='form-group adsense<?php echo $i; ?>' id='as_adtyperow<?php echo $i; ?>'>
			<label class='col-sm-2 control-label' for='as_adtype<?php echo $i; ?>'>Ad Type</label>
			<div class='col-sm-6'>
				<select name='as_adtype[<?php echo $i; ?>]' id='as_adtype<?php echo $i; ?>' class='form-control'>
					<option value='TI' <?php if($editingad && isset($currentad) && $currentad['adtype']=='TI') echo "selected='selected'"; ?>>Text and image ads</option>
					<option value='T' <?php if($editingad && isset($currentad) && $currentad['adtype']=='T') echo "selected='selected'"; ?>>Text ads only</option>
					<option value='I' <?php if($editingad && isset($currentad) && $currentad['adtype']=='I') echo "selected='selected'"; ?>>Image ads only</option>
				</select>
			</div>
		</div>
		
		<div class='form-group custom<?php echo $i; ?>' id='as_customcode<?php echo $i; ?>' style='display:none'>
			<label class='col-sm-2 control-label' for='as_customcode<?php echo $i; ?>'>Code Snippet</label>
			<div class='col-sm-6'>
				<div id='responsivenote<?php echo $i; ?>' style='display:none'>Google requires you to create the responsive code manually.<br/>1) Create a <a href='https://www.google.com/adsense/app#main/myads-viewall-adunits/product=SELF_SERVICE_CONTENT_ADS' target='_blank'>new ad unit in AdSense</a> with the 'Responsive' size.<br/>2) Assign the custom channel named at the bottom of this page (must name recipe first) to that ad.<br/>3) Paste the code from Google into the box below:</div>
				<textarea class="form-control as_customcode" rows="3" name='as_customcode[<?php echo $i; ?>]' id='as_customcode<?php echo $i; ?>'><?php if($editingad && $currentad['custom']!='no') echo $currentad['customcode']; ?></textarea>
			</div>
		</div>
		
		<div class='form-group'>
			<label class='col-sm-2 control-label' for='as_adlocation<?php echo $i; ?>'>Ad Location</label>
			<div class='col-sm-6'>
				<div id='responsivelocationnote<?php echo $i; ?>' style='display:none'>Responsive ads fill up the container they are inside, so they cannot be 'left' or 'right' justified. Be sure to choose 'center' if inside the post.</div>
				<select name='as_adlocation[<?php echo $i; ?>]' id='as_adlocation<?php echo $i; ?>' class='form-control'>
					<option value='AP' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='AP') echo "selected='selected'"; ?>>Above post</option>
					<option value='IL' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='IL') echo "selected='selected'"; ?>>Inside post (top, left)</option>
					<option value='IR' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='IR') echo "selected='selected'"; ?>>Inside post (top, right)</option>
					<option value='PL' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='PL') echo "selected='selected'"; ?>>Inside post (after 1st paragraph, left)</option>
					<option value='PC' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='PC') echo "selected='selected'"; ?>>Inside post (after 1st paragraph, center)</option>
					<option value='PR' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='PR') echo "selected='selected'"; ?>>Inside post (after 1st paragraph, right)</option>
					<option value='1L' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='1L') echo "selected='selected'"; ?>>Inside post (1/4 down, left)</option>
					<option value='1C' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='1C') echo "selected='selected'"; ?>>Inside post (1/4 down, center)</option>
					<option value='1R' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='1R') echo "selected='selected'"; ?>>Inside post (1/4 down, right)</option>
					<option value='2L' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='2L') echo "selected='selected'"; ?>>Inside post (1/2 down, left)</option>
					<option value='2C' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='2C') echo "selected='selected'"; ?>>Inside post (1/2 down, center)</option>
					<option value='2R' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='2R') echo "selected='selected'"; ?>>Inside post (1/2 down, right)</option>
					<option value='3L' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='3L') echo "selected='selected'"; ?>>Inside post (3/4 down, left)</option>
					<option value='3C' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='3C') echo "selected='selected'"; ?>>Inside post (3/4 down, center)</option>
					<option value='3R' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='3R') echo "selected='selected'"; ?>>Inside post (3/4 down, right)</option>
					<option value='BP' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='BP') echo "selected='selected'"; ?>>Below post</option>
					<option value='SA' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='SA') echo "selected='selected'"; ?>>Sidebar position A</option>
					<option value='SB' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='SB') echo "selected='selected'"; ?>>Sidebar position B</option>
					<option value='SC' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='SC') echo "selected='selected'"; ?>>Sidebar position C</option>
					<option value='SD' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='SD') echo "selected='selected'"; ?>>Sidebar position D</option>
					<option value='SE' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='SE') echo "selected='selected'"; ?>>Sidebar position E</option>
					<option value='SF' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='SF') echo "selected='selected'"; ?>>Sidebar position F</option>
					<option value='CA' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='CA') echo "selected='selected'"; ?>>Shortcode position A</option>
					<option value='CB' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='CB') echo "selected='selected'"; ?>>Shortcode position B</option>
					<option value='CC' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='CC') echo "selected='selected'"; ?>>Shortcode position C</option>
					<option value='CD' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='CD') echo "selected='selected'"; ?>>Shortcode position D</option>
					<option value='CE' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='CE') echo "selected='selected'"; ?>>Shortcode position E</option>
					<option value='CF' <?php if($editingad && isset($currentad) && $currentad['adlocation']=='CF') echo "selected='selected'"; ?>>Shortcode position F</option>
				</select>
				<div id='sidebarwarning<?php echo $i; ?>' style='display:none'>Note: If placing on a sidebar, be sure you've <a href='http://www.ampedsense.com/placing-adsense-ads-wordpress-sidebar/' target='_blank'>set up the AmpedSense Sidebar Widget</a></div>
				<div id='shortcodewarning<?php echo $i; ?>' style='display:none'>Note: If placing via a shortcode, learn <a href='http://www.ampedsense.com/how-to-place-ads-specifically-with-a-short-code/' target='_blank'>how to use shortcodes in AmpedSense</a></div>
			</div>
		</div>
		
		<div class='form-group'>
			<label class='col-sm-2 control-label' for='as_adpadding<?php echo $i; ?>'>Padding & Margin</label>
			<div class='col-sm-3'>
				<input type='text' name='as_adpadding[<?php echo $i; ?>]' id='as_adpadding<?php echo $i; ?>' class='form-control' <?php if($editingad && isset($currentad)) echo "value='$currentad[adpadding]'"; ?>>
				Ex: '5px', or '10px 2px 5px 2px'
			</div>
			<div class='col-sm-3'>
				<input type='text' name='as_admargin[<?php echo $i; ?>]' id='as_admargin<?php echo $i; ?>' class='form-control' <?php if($editingad && isset($currentad)) echo "value='$currentad[admargin]'"; ?>>
			</div>
		</div>
		
		<div class="form-group adsense<?php echo $i; ?>">
			<label class='col-sm-2 control-label'>Ad Color</label>
			<div class='col-sm-6'>
				<label class='radio-inline'><input type='radio' name='as_color[<?php echo $i; ?>]' id='as_colordefault<?php echo $i; ?>' value='default' <?php if(!$editingad || ($editingad && !isset($currentad)) || ($editingad && !isset($currentad['color'])) || ($editingad && $currentad['color']=='default')) echo "checked='checked'"; ?>>Default</label>
				<label class='radio-inline'><input type='radio' name='as_color[<?php echo $i; ?>]' id='as_colorcustom<?php echo $i; ?>' value='custom' <?php if($editingad && $currentad['color']=='custom') echo "checked='checked'"; ?>>Custom</label>
			</div>
		</div>		
		
		<div class='form-group adsense<?php echo $i; ?> as_adcustomcolorrow<?php echo $i; ?>' style='display:none'>
			<label class='col-sm-2 control-label' for='as_color_border<?php echo $i; ?>'>Border Color</label>
			<div class='col-sm-6'>
				<input type='text' name='as_color_border[<?php echo $i; ?>]' id='as_color_border<?php echo $i; ?>' class='form-control color' maxlength=6 <?php if($editingad && isset($currentad) && isset($currentad['color_border'])) echo "value='$currentad[color_border]'"; else echo "value='FFFFFF'"; ?>>
			</div>
		</div>
		
		<div class='form-group adsense<?php echo $i; ?> as_adcustomcolorrow<?php echo $i; ?>' style='display:none'>
			<label class='col-sm-2 control-label' for='as_color_bg<?php echo $i; ?>'>Background Color</label>
			<div class='col-sm-6'>
				<input type='text' name='as_color_bg[<?php echo $i; ?>]' id='as_color_bg<?php echo $i; ?>' class='form-control color' maxlength=6 <?php if($editingad && isset($currentad) && isset($currentad['color_bg'])) echo "value='$currentad[color_bg]'"; else echo "value='FFFFFF'"; ?>>
			</div>
		</div>
		
		<div class='form-group adsense<?php echo $i; ?> as_adcustomcolorrow<?php echo $i; ?>' style='display:none'>
			<label class='col-sm-2 control-label' for='as_color_link<?php echo $i; ?>'>Link Color</label>
			<div class='col-sm-6'>
				<input type='text' name='as_color_link[<?php echo $i; ?>]' id='as_color_link<?php echo $i; ?>' class='form-control color' maxlength=6 <?php if($editingad && isset($currentad) && isset($currentad['color_link'])) echo "value='$currentad[color_link]'"; else echo "value='1E0FBE'"; ?>>
			</div>
		</div>
		
		<div class='form-group adsense<?php echo $i; ?> as_adcustomcolorrow<?php echo $i; ?>' style='display:none'>
			<label class='col-sm-2 control-label' for='as_color_text<?php echo $i; ?>'>Text Color</label>
			<div class='col-sm-6'>
				<input type='text' name='as_color_text[<?php echo $i; ?>]' id='as_color_text<?php echo $i; ?>' class='form-control color' maxlength=6 <?php if($editingad && isset($currentad) && isset($currentad['color_text'])) echo "value='$currentad[color_text]'"; else echo "value='373737'"; ?>>
			</div>
		</div>
		
		<div class='form-group adsense<?php echo $i; ?> as_adcustomcolorrow<?php echo $i; ?>' style='display:none'>
			<label class='col-sm-2 control-label' for='as_color_url<?php echo $i; ?>'>URL Color</label>
			<div class='col-sm-6'>
				<input type='text' name='as_color_url[<?php echo $i; ?>]' id='as_color_url<?php echo $i; ?>' class='form-control color' maxlength=6 <?php if($editingad && isset($currentad) && isset($currentad['color_url'])) echo "value='$currentad[color_url]'"; else echo "value='006621'"; ?>>
			</div>
		</div>
		
		<script>
		
		//show/hide custom code or adsense fields
		jQuery('#as_customfalse<?php echo $i; ?>, #as_customresp<?php echo $i; ?>, #as_customhtml<?php echo $i; ?>').change(toggleCustomAndChannelInfo<?php echo $i; ?>);
		function toggleCustom<?php echo $i; ?>()
		{
			if(jQuery('#as_customfalse<?php echo $i; ?>').attr('checked'))
			{
				jQuery('.custom<?php echo $i; ?>').hide();
				jQuery('#responsivenote<?php echo $i; ?>, #responsivelocationnote<?php echo $i; ?>').hide();
				jQuery('.adsense<?php echo $i; ?>').show();
				toggleAdColor<?php echo $i; ?>();
			}
			else if(jQuery('#as_customresp<?php echo $i; ?>').attr('checked'))
			{
				jQuery('.adsense<?php echo $i; ?>').hide();
				jQuery('.custom<?php echo $i; ?>').show();
				jQuery('#responsivenote<?php echo $i; ?>, #responsivelocationnote<?php echo $i; ?>').show();
			}
			else if(jQuery('#as_customhtml<?php echo $i; ?>').attr('checked'))
			{
				jQuery('.adsense<?php echo $i; ?>').hide();
				jQuery('#responsivenote<?php echo $i; ?>, #responsivelocationnote<?php echo $i; ?>').hide();
				jQuery('.custom<?php echo $i; ?>').show();
			}
		}
		//need separate function to call on change, vs on first load if editing
		function toggleCustomAndChannelInfo<?php echo $i; ?>()
		{
			toggleCustom<?php echo $i; ?>();
			toggleChannelInfo();
		}
		//call immediately if editing
		<?php if($editingad && isset($currentad)) echo "toggleCustom$i();"; ?>
		
		
		//show/hide sidebar assistance, and suggest padding
		jQuery('#as_adlocation<?php echo $i; ?>').change(suggestPadding<?php echo $i; ?>);
		function suggestPadding<?php echo $i; ?>() {
			var val = jQuery('#as_adlocation<?php echo $i; ?>').val();
			var padding = "10px"; //default
			if(val=='SA' || val=='SB' || val=='SC' || val=='SD' || val=='SE' || val=='SF')
			{
				jQuery('#shortcodewarning<?php echo $i; ?>').hide();
				jQuery('#sidebarwarning<?php echo $i; ?>').show();
			}
			else if(val=='CA' || val=='CB' || val=='CC' || val=='CD' || val=='CE' || val=='CF')
			{
				jQuery('#sidebarwarning<?php echo $i; ?>').hide();
				jQuery('#shortcodewarning<?php echo $i; ?>').show();
			}
			else
			{
				jQuery('#sidebarwarning<?php echo $i; ?>').hide();
				jQuery('#shortcodewarning<?php echo $i; ?>').hide();
				
				if(val=='AP') padding = "0px 0px 10px 0px";
				if(val=='IR') padding = "0px 0px 10px 10px";
				if(val=='IL') padding = "0px 10px 10px 0px";
				if(val=='BP') padding = "10px 0px 0px 0px";
				
			}
			jQuery('#as_adpadding<?php echo $i; ?>').val(padding);
		}
		//don't run now if editing
		<?php if(!$editingad || !isset($currentad)) echo "suggestPadding$i();"; ?>
		
		//hide ad type if link unit chosen and auto-select 'Responsive' type if chosen from size drop down
		jQuery('#as_adsize<?php echo $i; ?>').change(toggleAdType<?php echo $i; ?>);
		function toggleAdType<?php echo $i; ?>() {
			//only do if AdSense selected, otherwise shows the Ad Type if responsive on page load
			if(jQuery('#as_customfalse<?php echo $i; ?>').attr('checked'))
			{
				var val = jQuery('#as_adsize<?php echo $i; ?>').val();
				if(val=='728x15' || val=='468x15' || val=='200x90' || val=='180x90' || val=='160x90' || val=='120x90')
				{
					jQuery('#as_adtyperow<?php echo $i; ?>').hide();
				}
				else if(val=='resp')
				{
					jQuery('#as_customresp<?php echo $i; ?>').click();
					//revert ad size to default
					jQuery('#as_adsize<?php echo $i; ?>').val('728x90');
				}
				else
				{
					jQuery('#as_adtyperow<?php echo $i; ?>').show();
				}
			}
		}
		//call immediately if editing
		<?php if($editingad && isset($currentad)) echo "toggleAdType$i();"; ?>
		
		//show/hide custom color
		jQuery('#as_colordefault<?php echo $i; ?>, #as_colorcustom<?php echo $i; ?>').change(toggleAdColor<?php echo $i; ?>);
		function toggleAdColor<?php echo $i; ?>() {
			if(jQuery('#as_colorcustom<?php echo $i; ?>').attr('checked'))
			{
				jQuery('.as_adcustomcolorrow<?php echo $i; ?>').show();
			}
			else
			{
				jQuery('.as_adcustomcolorrow<?php echo $i; ?>').hide();
			}
		}
		//call immediately if editing
		<?php if($editingad && isset($currentad)) echo "toggleAdColor$i();"; ?>
		
		</script>

	</div>
	<?php
}


?>


</div>