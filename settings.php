<?php
//ensure admins only
if(!current_user_can('manage_options')) die("Nope");

global $amped_sense;

//If form submitted, handle action
$message = "";
if( !empty( $_POST ) || count($_GET)>1 ) {
	if(isset($_REQUEST['as_action'])) $message = $amped_sense->handle_action($_REQUEST['as_action']);
}

$amped_sense->ensureGoogleAccessToken();

//get all publisher id's has access to
$accounts = array();
$returnedjson = $amped_sense->getUrlContents("https://www.googleapis.com/adsense/v1.4/accounts?access_token=".urlencode($_SESSION['as_googleaccesstoken'])."&userIp=".$amped_sense->ip);
$apiresult = json_decode($returnedjson,true);
if(count($apiresult) && empty($apiresult["error"]))
{
	foreach($apiresult['items'] as $account)
	{
		$id = $account['id'];
		$name = $account['name'];
		$accounts[$id] = $name;
		
		//if first time, set default just in case they don't click save after this
		if(!isset($amped_sense->settings['adsensepublisherid']) || $amped_sense->settings['adsensepublisherid']=='')
		{
			$amped_sense->settings['adsensepublisherid'] = $id;
			
			//save immediately to db
			update_option('ampedsense_settings', $amped_sense->settings);
		}
	}
}
else
{
	echo "Error: $returnedjson";
}
?>

<div class='ampedsense'>
<h2>Settings</h2>
<?php $amped_sense->print_logo(); ?>
<?php if($message) echo "<div class='as_statusmessage'>$message</div>"; ?>

<form method='post' class='form-horizontal' id='theform'>
<?php wp_nonce_field('updatesettings'); ?>
<input type='hidden' name='as_action' value='updatesettings'>
<div class='form-group'>
	<label class='col-sm-2 control-label' for='as_adsensepublisherid'>AdSense Publisher</label>
	<div class='col-sm-6'>
		<select class='form-control' id='as_adsensepublisherid' name='as_adsensepublisherid' >
		<?php
		foreach($accounts as $accountid=>$accountname)
		{
			echo "<option value='$accountid'";
			if($amped_sense->settings['adsensepublisherid']==$accountid) echo " SELECTED='selected'";
			echo ">$accountname</option>";
		}
		?>
		</select>
		<a href="<?php echo $amped_sense->get_google_login_url(); ?>">Reauthenticate</a> with Google
	</div>
</div>
<div class='form-group'>
	<label class='col-sm-2 control-label' for='as_siteabbrev'>Site Abbreviation</label>
	<div class='col-sm-6'>
		<input type='text' class='form-control' id='as_siteabbrev' name='as_siteabbrev' maxlength='3' placeholder='Foo' value='<?php echo $amped_sense->settings['siteabbrev']; ?>'>
		3-letter abbreviation to be used as a prefix in Google's reports
	</div>
</div>
<div class='form-group'>
	<label class='col-sm-2 control-label' for='as_siteabbrev'>Render Mode</label>
	<div class='col-sm-6'>
		<label class='radio-inline'><input type='radio' name='as_render' id='as_render_client' value='client' <?php if($amped_sense->settings['render']=='client') echo "checked='checked'"; ?>>Client (cache-friendly)</label>
		<label class='radio-inline'><input type='radio' name='as_render' id='as_render_server' value='server' <?php if($amped_sense->settings['render']=='server') echo "checked='checked'"; ?>>Server (default)</label>
		<label class='radio-inline' style='padding-left: 5px'><a href='http://www.ampedsense.com/render-mode-client-vs-server' target='_blank'>Learn more</a></label>
	</div>
</div>
<div class='form-group'>
	<label class='col-sm-2 control-label' for='as_siteabbrev'>License Key</label>
	<div class='col-sm-6'>
		<input type='text' class='form-control' id='as_lcse_k' name='as_lcse_k' value='<?php if(isset($amped_sense->settings['lcse_k'])) echo $amped_sense->settings['lcse_k']; ?>'>
		If you have a <a href='http://www.ampedsense.com/premium/' target='_blank'>premium membership</a>, unlock expert features with your valid key
	</div>
</div>
<div class='form-group'>
	<div class="col-sm-offset-2 col-sm-6">
		<button type='submit' class='btn btn-primary'>Save Settings</button>
	</div>
</div>
</form>

<h3>Share your success!</h3>
<p>Have you increased your revenue? I love hearing success stories! Please <a href="http://www.ampedsense.com/contact/" target='blank'>tell me about it</a> - it gives me motivation to keep on doing this.</p>
<hr />
<h3>Need help?</h3>
<?php
if(isset($amped_sense->settings['lcse_v']) && $amped_sense->settings['lcse_v']=='p')
{
	?>
<p>As a premium member, you have access to personalized support! Simply email any help questions to support@ampedsense.com.
<br/><br/><b>Thank you!</b></p>
	<?php
}
else
{
	?>
<p>Help yourself to our collection of <a href="http://www.ampedsense.com/support/" target='_blank'>help articles</a>. Try getting community support at the <a href='http://www.ampedsense.com/forums/' target='_blank'>AmpedSense Forums</a>
<br/><br/><b>Premium users</b> can get personalized support for configuring their site. Learn about <a href='http://www.ampedsense.com/premium/' target='_blank'>upgrading to premium.</a></p>
	<?php
}
?>

<script>
//validation
jQuery('#theform').submit(function() {
	//adsensepublisherid
	if(jQuery('#as_adsensepublisherid').val()=="")
	{
		alert('Please enter your AdSense publisher ID');
		jQuery('#as_adsensepublisherid').focus();
		return false;
	}
	//siteabbrev
	if(jQuery('#as_siteabbrev').val()=="")
	{
		alert('Please enter the site abbreviation');
		jQuery('#as_siteabbrev').focus();
		return false;
	}
	if(jQuery('#as_siteabbrev').val().length>3)
	{
		alert('Site abbreviation must be 3 letters or less');
		jQuery('#as_siteabbrev').focus();
		return false;
	}
	return true;
});
</script>

</div>