<?php
/*
Plugin Name: AmpedSense - AdSense Split Tester
Plugin URI: http://www.ampedsense.com/
Version: 4.68
Author: Ezoic
Author URI: http://www.ezoic.com
Description: Optimize your Adsense Revenue by split testing various formats and positions
*/

class AmpedSense
{
	public $settings = array();
	public $settings_dirty = false;
	public $chosen_recipe = null; // channelname, and ads arr
	public $widgetA_renderers = array();
	public $widgetB_renderers = array();
	public $widgetC_renderers = array();
	public $widgetD_renderers = array();
	public $widgetE_renderers = array();
	public $widgetF_renderers = array();
	public $shortcodeA_renderers = array();
	public $shortcodeB_renderers = array();
	public $shortcodeC_renderers = array();
	public $shortcodeD_renderers = array();
	public $shortcodeE_renderers = array();
	public $shortcodeF_renderers = array();
	public $ip = "";
	//public $googleclientid = ""; down v
	/*Settings arr:
	$settings['lastchange'] = ''
	$settings['adsensepublisherid'] = ''
	$settings['googlerefreshtoken'] = ''
	$settings['siteabbrev'] = ''
	$settings['installid'] = ''
	$settings['lcse_k'] = ''
	$settings['lcse_v'] = ''
	$settings['lcse_checked'] = (time)
	$settings['render'] = server / client
	$settings['segments'][0]
							['segmentname']
							['devices'] = dtp/dt/tp/d/t/p
							['criteria']
							['criteriaparam']
							['hide'] = 1 / ''
							['recipes'][0]
									['recipename']
									['channelname']
									['channelid']
									['whenstarted']
									['active'] = {1/0}
									['notes']
									['ads'][0]
											['custom'] = no / resp / html
											['adsize']
											['adtype']
											['adlocation']
											['adpadding']
											['admargin']
											['customcode']
											['color'] = default / custom
											['color_border'], ['color_bg'], ['color_link'], ['color_text'], ['color_url'],

	$_SESSION['as_googleaccesstoken']
	$_SESSION['as_googleaccesstokenexpires']
	$_SESSION['as_fromdate'][$i]
	*/

	public function __construct()
	{
		$this->init();
	}

	public function __destruct()
	{
		//doesn't work, must destruct manually at end of settings page
	}

	public function activate()
	{
		//delete previous version settings, if exist
		if(isset($this->settings['google_client_id'])) { unset($this->settings['google_client_id']); $this->settings_dirty = true; }
		if(isset($this->settings['google_client_secret'])) { unset($this->settings['google_client_secret']); $this->settings_dirty = true; }
		if(isset($this->settings['google_access_token'])) { unset($this->settings['google_access_token']); $this->settings_dirty = true; }
		if(isset($this->settings['adsense_account_id'])) { unset($this->settings['adsense_account_id']); $this->settings_dirty = true; }
		if(isset($this->settings['adsense_adclient_id'])) { unset($this->settings['adsense_adclient_id']); $this->settings_dirty = true; }
		if(isset($this->settings['ir_enabled'])) { unset($this->settings['ir_enabled']); $this->settings_dirty = true; }
		if(isset($this->settings['ir_googleclientid'])) { unset($this->settings['ir_googleclientid']); $this->settings_dirty = true; }
		if(isset($this->settings['ir_googleclientsecret'])) { unset($this->settings['ir_googleclientsecret']); $this->settings_dirty = true; }
		if(isset($this->settings['ir_googleaccesstoken'])) { unset($this->settings['ir_googleaccesstoken']); $this->settings_dirty = true; }
		if(isset($this->settings['ads'])) { unset($this->settings['ads']); $this->settings_dirty = true; }
		if(isset($this->settings['lastchange'])) { unset($this->settings['lastchange']); $this->settings_dirty = true; }

		//go through each segment that doesn't have devices set, if mobile then set as mobile device, otherwise set to all devices
		if(isset($this->settings['segments']))
		{
			foreach($this->settings['segments'] as $i=>$segment)
			{
				if(!isset($segment['devices']))
				{
					if($segment['criteria']=='mobile')
					{
						//match all pages and posts for tablet and phones
						$this->settings['segments'][$i]['devices'] = 'tp';
						$this->settings['segments'][$i]['criteria'] = 'default';
					}
					else
					{
						//no preference of device, set to all
						$this->settings['segments'][$i]['devices'] = 'dtp';
					}
					$this->settings_dirty = true;
				}
			}
		}

		//Save to db. Destructor doesn't work
		if($this->settings_dirty)
		{
			//save vars to db
			update_option('ampedsense_settings', $this->settings);
		}
	}

	public function deactivate()
	{
		//nothing to do, keep settings in case reactivate later
	}


	public function init()
	{
		//retrieve settings
		$this->settings = get_option('ampedsense_settings');

		//set defaults - these must be done here, as activation hook no longer called during update
		if(!isset($this->settings['segments']) || count($this->settings['segments'])==0)
		{
			//init default segment
			$this->settings['segments'];
			$newsegment = array();
			$newsegment['criteria'] = "default";
			$newsegment['segmentname'] = "All Traffic";
			$newsegment['devices'] = "dtp";

			$this->settings['segments'][] = $newsegment;
			$this->settings_dirty = true;
		}
		if(!isset($this->settings['siteabbrev']) || $this->settings['siteabbrev']=='')
		{
			$sitename = str_replace(" ","",get_bloginfo('name')); //get rid of whitespace
			$this->settings['siteabbrev'] = substr($sitename,0,3);
			$this->settings_dirty = true;
		}
		//if brand new user set render=client, otherwise leave as server
		if(!isset($this->settings['render']))
		{
			/*
			if(isset($this->settings['segments'])) $this->settings['render'] = 'server';
			else $this->settings['render'] = 'client';
			*/
			//temporarily leave default as server
			$this->settings['render'] = 'server';

			$this->settings_dirty = true;
		}

		$this->ip = $_SERVER['REMOTE_ADDR'];

		//admin hooks
		add_action('admin_init', array( $this, 'adminInit') );
		add_action('admin_menu', array( $this, 'make_settings_menu') );
		add_action('admin_enqueue_scripts', array( $this, 'loadAdminStylesAndScripts') );

		//sidebar widgets
		add_action('widgets_init', array( $this, 'registerSidebars') );

		//shortcodes
		add_shortcode( 'AmpedSenseShortcodeA' , array( $this, 'shortcodeA') );
		add_shortcode( 'AmpedSenseShortcodeB' , array( $this, 'shortcodeB') );
		add_shortcode( 'AmpedSenseShortcodeC' , array( $this, 'shortcodeC') );
		add_shortcode( 'AmpedSenseShortcodeD' , array( $this, 'shortcodeD') );
		add_shortcode( 'AmpedSenseShortcodeE' , array( $this, 'shortcodeE') );
		add_shortcode( 'AmpedSenseShortcodeF' , array( $this, 'shortcodeF') );

		//add hook to check page type later
		add_action('wp', array( $this, 'run'));

		//Save to db. Destructor doesn't work
		if($this->settings_dirty)
		{
			//save vars to db
			update_option('ampedsense_settings', $this->settings);
		}
	}

	public function adminInit()
	{
		//we save the report fromdates and google access token to session
		if( !session_id()) session_start();
	}

	public function registerSidebars()
	{
		register_widget( 'AmpedSenseSidebarA' );
		register_widget( 'AmpedSenseSidebarB' );
		register_widget( 'AmpedSenseSidebarC' );
		register_widget( 'AmpedSenseSidebarD' );
		register_widget( 'AmpedSenseSidebarE' );
		register_widget( 'AmpedSenseSidebarF' );
	}

	function clientContentInject($thecontent)
	{
		//for each spot in content, inject JS call


		//inside

		//split content into paragraph arr
		//called after wpautop, so split based on p tags
		$paragrapharr = preg_split('/<\/p>/', $thecontent, -1, PREG_SPLIT_NO_EMPTY);

		//figure out indexes
		$insertindex_P = 1; //after p1
		$insertindex_1 = round(count($paragrapharr)*.25); // 1/4 way down
		$insertindex_2 = round(count($paragrapharr)*.5);  // 1/2 way down
		$insertindex_3 = round(count($paragrapharr)*.75); // 3/4 way down

		//insert them
		$adddivendplaceholder = '#ADEND#';
		//adding +X to a few since we're adding more elements to the array, so must adjust for each time we do this
		array_splice($paragrapharr, $insertindex_P, 0, array("<script>AmpedSense.OptimizeAdSpot('PL'); AmpedSense.OptimizeAdSpot('PC'); AmpedSense.OptimizeAdSpot('PR');</script>".$adddivendplaceholder));
		array_splice($paragrapharr, $insertindex_1+1, 0, array("<script>AmpedSense.OptimizeAdSpot('1L'); AmpedSense.OptimizeAdSpot('1C'); AmpedSense.OptimizeAdSpot('1R');</script>".$adddivendplaceholder));
		array_splice($paragrapharr, $insertindex_2+2, 0, array("<script>AmpedSense.OptimizeAdSpot('2L'); AmpedSense.OptimizeAdSpot('2C'); AmpedSense.OptimizeAdSpot('2R');</script>".$adddivendplaceholder));
		array_splice($paragrapharr, $insertindex_3+3, 0, array("<script>AmpedSense.OptimizeAdSpot('3L'); AmpedSense.OptimizeAdSpot('3C'); AmpedSense.OptimizeAdSpot('3R');</script>".$adddivendplaceholder));

		//join it all back
		$thecontent = implode("</p>", $paragrapharr);
		$thecontent = str_replace($adddivendplaceholder."</p>",'',$thecontent); //remove extra closing p added by inserting our stuff
		$thecontent = str_replace($adddivendplaceholder,'',$thecontent); //if no content or no </p> at all, make sure we still remove ADEND

		//above, inside top, and below
		$thecontent = "<script>AmpedSense.OptimizeAdSpot('AP'); AmpedSense.OptimizeAdSpot('IL'); AmpedSense.OptimizeAdSpot('IR');</script>".
					$thecontent.
					"<script>AmpedSense.OptimizeAdSpot('BP')</script>";

		return $thecontent;
	}

	public function run()
	{
		if(isset($this->settings['adsensepublisherid']) && $this->settings['adsensepublisherid']!='')
		{

			if($this->settings['render']=='client')
			{
				//dump ads to js
				add_action('wp_head', array( $this, 'dumpAdstoJs') );

				//and inject js calls in all possible spots

				//content related
				add_filter('the_content',  array( $this, 'clientContentInject' ), 200); //priority after wpautop (should be 10, much higher just in case)

				//sidebar widgets handled by AmpedSenseSidebarA/B/C/D/E/F

				//shortcode handlers handled by $this->shortcodeA/B/C/D/E/F
			}
			else
			{
				//SERVER SIDE RENDERING

				//pick recipe!

				if(isset($_GET['as_preview']) && $_GET['as_preview']
						&& current_user_can('read')) //only allow preview via get on logged in users, otherwise may allow js injection
				{
					//preview!

					//create array of ads from parameters
					$previewads = array();
					for($i=1; $i<=$_GET['as_numads']; $i++)
					{
						if(isset($_GET['as_custom'][$i])) $previewad['custom'] = $this->cleanInput($_GET['as_custom'][$i]);
						if(isset($_GET['as_adsize'][$i])) $previewad['adsize'] = $this->cleanInput($_GET['as_adsize'][$i]);
						if(isset($_GET['as_adtype'][$i])) $previewad['adtype'] = $this->cleanInput($_GET['as_adtype'][$i]);
						if(isset($_GET['as_adlocation'][$i])) $previewad['adlocation'] = $this->cleanInput($_GET['as_adlocation'][$i]);
						if(isset($_GET['as_adpadding'][$i])) $previewad['adpadding'] = $this->cleanInput($_GET['as_adpadding'][$i]);
						if(isset($_GET['as_admargin'][$i])) $previewad['admargin'] = $this->cleanInput($_GET['as_admargin'][$i]);
						if(isset($_GET['as_color'][$i])) $previewad['color'] = $this->cleanInput($_GET['as_color'][$i]);
						if(isset($_GET['as_color_border'][$i])) $previewad['color_border'] = $this->cleanInput($_GET['as_color_border'][$i]);
						if(isset($_GET['as_color_bg'][$i])) $previewad['color_bg'] = $this->cleanInput($_GET['as_color_bg'][$i]);
						if(isset($_GET['as_color_link'][$i])) $previewad['color_link'] = $this->cleanInput($_GET['as_color_link'][$i]);
						if(isset($_GET['as_color_text'][$i])) $previewad['color_text'] = $this->cleanInput($_GET['as_color_text'][$i]);
						if(isset($_GET['as_color_url'][$i])) $previewad['color_url'] = $this->cleanInput($_GET['as_color_url'][$i]);

						//security risk, just show black box instead
						//if(isset($_GET['as_customcode'][$i])) $previewad['customcode'] = $this->cleanInput($_GET['as_customcode'][$i]);
						if(isset($_GET['as_custom'][$i]) && $_GET['as_custom'][$i]=='html') $previewad['customcode'] = "<div style='border:2px solid white; background:black; color:white'>CUSTOM CODE HERE<br/>Custom code cannot be previewed for security reasons.<br/>On live traffic this box will be replaced with your custom code.</div>";
						else if(isset($_GET['as_custom'][$i]) && $_GET['as_custom'][$i]=='resp') $previewad['customcode'] = "<div style='border:2px solid white; background:black; color:white'>RESPONSIVE AD UNIT HERE<br/>Responsive code cannot be previewed for security reasons.<br/>On live traffic this box will be replaced with your responsive ad unit.</div>";

						$previewads[] = $previewad;
					}
					$chosen_recipe['ads'] = $previewads;
					$chosen_recipe['channelid'] = "0";
				}
				else
				{
					//figure out what segment we're in

					//first need to know what device we're on
					require_once ( plugin_dir_path(__FILE__) . 'Mobile_Detect_ForAS.php' );
					$detect = new Mobile_Detect_ForAS; //renamed to prevent dupe classes
					$device = 'd'; //d = desktop, t = tablet, p = phone
					if( $detect->isTablet() ) $device = 't'; // Any tablet device.
					elseif ( $detect->isMobile() ) $device = 'p'; // Any mobile device (phones or tablets).
					//else must be desktop (default)

					$segmenti = 0;
					$matchedsegment = false;
					foreach($this->settings['segments'] as $i=>$segment)
					{
						$segmenti = $i;

						if(stristr($segment['devices'],$device)!='')
						{
							//correct device, now check page criteria

							if($segment['criteria']=="allpages")
							{
								if(is_page())
								{
									$matchedsegment = true;
									break;
								}
							}
							elseif($segment['criteria']=="allposts")
							{
								if(is_single())
								{
									$matchedsegment = true;
									break;
								}
							}
							elseif($segment['criteria']=="alllists")
							{
								if(is_category())
								{
									$matchedsegment = true;
									break;
								}
							}
							elseif($segment['criteria']=="homepage")
							{
								if(is_front_page())
								{
									$matchedsegment = true;
									break;
								}
							}
							elseif($segment['criteria']=="page" || $segment['criteria']=="post")
							{
								global $post; //the_ID() prints it out
								$id = $post->ID;
								if($id==$segment['criteriaparam'])
								{
									$matchedsegment = true;
									break;
								}
							}
							elseif($segment['criteria']=="list")
							{
								if(is_category($segment['criteriaparam']))
								{
									$matchedsegment = true;
									break;
								}
							}
							elseif($segment['criteria']=="category")
							{
								global $post; //the_ID() prints it out
								$id = $post->ID;
								$thispostcategories = get_the_category($id);
								if(!empty( $thispostcategories))
								{
									foreach($thispostcategories as $thispostcategory )
									{
										if($thispostcategory->cat_ID==$segment['criteriaparam'])
										{
											$matchedsegment = true;
											break 2;
										}
									}
								}
							}
							elseif($segment['criteria']=="default")
							{
								//all traffic matches this
								$matchedsegment = true;
								break;
							}
						}
					}
					if($matchedsegment)
					{
						//pick ad at random
						if(isset($this->settings['segments'][$segmenti]['recipes']) && count($this->settings['segments'][$segmenti]['recipes']))
						{
							//only from those that are active
							$activekeys = array();
							foreach($this->settings['segments'][$segmenti]['recipes'] as $key=>$recipe)
							{
								if($recipe['active'])
								{
									$activekeys[] = $key;
								}
							}
							$chosenindex = array_rand($activekeys);
							$chosenkey = $activekeys[$chosenindex];
							$chosen_recipe['ads'] = $this->settings['segments'][$segmenti]['recipes'][$chosenkey]['ads'];
							$chosen_recipe['channelid'] = $this->settings['segments'][$segmenti]['recipes'][$chosenkey]['channelid'];
						}
					}
					//else don't show any ads here
				}

				if(isset($chosen_recipe)) //may  not be set if no ads on this segment
				{
					//hook chosen recipe's ads for later
					foreach($chosen_recipe['ads'] as $ad)
					{
						$this->hook_ad($ad,$chosen_recipe['channelid']);
					}
				}
			}
		}
	}

	public function alwaysTrueForDeepCopy($a)
	{
		//must use this function since anonymous functions not available til PHP 5.3
		return true;
	}

	public function dumpAdstoJs()
	{
		//create streamlined version of settings for dump
		$settingsDump = array_filter($this->settings['segments'],array( $this, 'alwaysTrueForDeepCopy')); //deep copy array
		foreach($settingsDump as $segmenti=>$segment)
		{
			if(isset($segment['recipes']) && count($segment['recipes']))
			{
				foreach($segment['recipes'] as $recipei=>$recipe)
				{
					//remove if not active
					if(!$recipe['active'])
					{
						unset($settingsDump[$segmenti]['recipes'][$recipei]);
					}
				}
			}
		}
		//$jsondSegments = json_encode($settingsDump,JSON_FORCE_OBJECT);
		//above requires PHP 5.3 or greater, so let's do it the old way and support more people
		//can't make it entirely an array, as associative arrays are objects in JS, so at least let's make sure the first level of segment is object
		$jsondSegments = json_encode((object)$settingsDump);

		// get some info about this post
		global $post; //the_ID() prints it out
		$categoryIds = array();
		foreach(get_the_category($post->ID) as $category) $categoryIds[] = $category->cat_ID;
		global $wp_query;
		//keep newline below (for code formatting)
		?>

		<!-- Ad split testing with AmpedSense: http://www.ampedsense.com -->
		<script>
		var AmpedSense = {};
		AmpedSense.segments = <?php echo $jsondSegments; ?>;
		AmpedSense.adsensepublisherid = '<?php echo $this->settings['adsensepublisherid']; ?>';
		AmpedSense.is_page = <?php echo (is_page()) ? 'true' : 'false'; ?>;
		AmpedSense.is_single = <?php echo (is_single()) ? 'true' : 'false'; ?>;
		AmpedSense.is_category = <?php echo (is_category()) ? 'true' : 'false'; ?>;
		AmpedSense.is_front_page = <?php echo (is_front_page()) ? 'true' : 'false'; ?>;
		AmpedSense.post_ID = <?php echo $post->ID; ?>;
		AmpedSense.post_category_IDs = <?php echo json_encode($categoryIds); ?>;
		AmpedSense.category_ID = <?php echo (is_category()) ? $wp_query->get_queried_object_id() : '0'; ?>;
		</script>
		<!--<script src="<?php echo $this->get_admin_dir(); ?>resources/client.max.js"></script>-->
		<script>
AmpedSense.QueryStringToObj=function(){var a={},e,b,c,d;e=window.location.search.split("&");c=0;for(d=e.length;c<d;c++)b=e[c].split("="),a[b[0]]=b[1];return a};
if(-1==window.location.search.indexOf("as_preview=1")){AmpedSense.device="d";"function"==typeof window.matchMedia&&(window.matchMedia("only screen and (max-device-width: 640px)").matches?AmpedSense.device="p":window.matchMedia("only screen and (max-device-width: 1024px)").matches&&(AmpedSense.device="t"));AmpedSense.segmenti=-1;for(var i in AmpedSense.segments)if(AmpedSense.segments.hasOwnProperty(i)){var segment=AmpedSense.segments[i];if(-1!=segment.devices.indexOf(AmpedSense.device)){if("allpages"==
segment.criteria&&AmpedSense.is_page){AmpedSense.segmenti=i;break}if("allposts"==segment.criteria&&AmpedSense.is_single){AmpedSense.segmenti=i;break}if("alllists"==segment.criteria&&AmpedSense.is_category){AmpedSense.segmenti=i;break}if("homepage"==segment.criteria&&AmpedSense.is_front_page){AmpedSense.segmenti=i;break}if(("page"==segment.criteria||"post"==segment.criteria)&&AmpedSense.post_ID==segment.criteriaparam){AmpedSense.segmenti=i;break}if("list"==segment.criteria&&AmpedSense.category_ID==
segment.criteriaparam){AmpedSense.segmenti=i;break}if("category"==segment.criteria&&AmpedSense.post_category_IDs.length&&-1!=AmpedSense.post_category_IDs.indexOf(parseInt(segment.criteriaparam))){AmpedSense.segmenti=i;break}if("default"==segment.criteria){AmpedSense.segmenti=i;break}}}if(-1!=AmpedSense.segmenti){var segment=AmpedSense.segments[AmpedSense.segmenti],recipekeys=[],j;for(j in segment.recipes)segment.recipes.hasOwnProperty(j)&&recipekeys.push(j);var chosenrecipekey=recipekeys[Math.floor(Math.random()*
recipekeys.length)];chosenrecipekey&&(AmpedSense.recipe=segment.recipes[chosenrecipekey])}}else{AmpedSense.recipe={};AmpedSense.recipe.ads=[];AmpedSense.recipe.channelid="0";qsObj=AmpedSense.QueryStringToObj();var paramNames="custom adsize adtype adlocation adpadding admargin color border_color color_bg color_link color_text color_url".split(" ");for(i=1;i<=qsObj.as_numads;i++){var newad={};for(j=0;j<=paramNames.length;j++){var paramName=paramNames[j],qsParamName="as_"+paramName+"%5B"+i+"%5D";qsObj[qsParamName]?
newad[paramName]=qsObj[qsParamName]:(qsParamName="as_"+paramName+"["+i+"]",qsObj[qsParamName]&&(newad[paramName]=qsObj[qsParamName]));"custom"==paramName&&("html"==qsObj[qsParamName]?newad.customcode="<div style='border:2px solid white; background:black; color:white'>CUSTOM CODE HERE<br/>Custom code cannot be previewed for security reasons.<br/>On live traffic this box will be replaced with your custom code.</div>":"resp"==qsObj[qsParamName]&&(newad.customcode="<div style='border:2px solid white; background:black; color:white'>RESPONSIVE AD UNIT HERE<br/>Responsive code cannot be previewed for security reasons.<br/>On live traffic this box will be replaced with your responsive ad unit.</div>"))}AmpedSense.recipe.ads.push(newad)}}
AmpedSense.OptimizeAdSpot=function(a){if(AmpedSense.recipe)for(var e in AmpedSense.recipe.ads)if(AmpedSense.recipe.ads.hasOwnProperty(e)){var b=AmpedSense.recipe.ads[e];if(b.adlocation==a){var c=AmpedSense.RenderAd(b,AmpedSense.recipe.channelid),d=b.adpadding&&""!=b.adpadding?"padding: "+b.adpadding+"; ":"",b=b.admargin&&""!=b.admargin?"margin: "+b.admargin+"; ":"";"AP"==a||"PC"==a||"1C"==a||"2C"==a||"3C"==a||"BP"==a||"SA"==a||"SB"==a||"SC"==a||"SD"==a||"SE"==a||"SF"==a||"CA"==a||"CB"==a||"CC"==a||
"CD"==a||"CE"==a||"CF"==a?document.write("<div style='width:100%; text-align:center; "+d+b+"'>"+c+"</div>"):"IL"==a||"PL"==a||"1L"==a||"2L"==a||"3L"==a?document.write("<div style='float:left; "+d+b+"'>"+c+"</div>"):"IR"!=a&&"PR"!=a&&"1R"!=a&&"2R"!=a&&"3R"!=a||document.write("<div style='float:right; "+d+b+"'>"+c+"</div>")}}};
AmpedSense.RenderAd=function(a,e){var b="";if("resp"==a.custom||"html"==a.custom)b=a.customcode;else{var c=b=0,d="";"728x90"==a.adsize?(b=728,c=90,d="728x90_as"):"320x100"==a.adsize?(b=320,c=100,d="320x100_as"):"970x250"==a.adsize?(b=970,c=250,d="970x250_as"):"970x90"==a.adsize?(b=970,c=90,d="970x90_as"):"468x60"==a.adsize?(b=468,c=60,d="468x60_as"):"320x50"==a.adsize?(b=320,c=50,d="320x50_as"):"234x60"==a.adsize?(b=234,c=60,d="234x60_as"):"300x600"==a.adsize?(b=300,c=600,d="300x600_as"):"300x1050"==
a.adsize?(b=300,c=1050,d="300x1050_as"):"160x600"==a.adsize?(b=160,c=600,d="160x600_as"):"120x600"==a.adsize?(b=120,c=600,d="120x600_as"):"120x240"==a.adsize?(b=120,c=240,d="120x240_as"):"336x280"==a.adsize?(b=336,c=280,d="336x280_as"):"300x250"==a.adsize?(b=300,c=250,d="300x250_as"):"250x250"==a.adsize?(c=b=250,d="250x250_as"):"200x200"==a.adsize?(c=b=200,d="200x200_as"):"180x150"==a.adsize?(b=180,c=150,d="180x150_as"):"125x125"==a.adsize?(c=b=125,d="125x125_as"):"728x15"==a.adsize?(b=728,c=15,d=
"728x15_0ads_al"):"468x15"==a.adsize?(b=468,c=15,d="468x15_0ads_al"):"200x90"==a.adsize?(b=200,c=90,d="200x90_0ads_al"):"180x90"==a.adsize?(b=180,c=90,d="180x90_0ads_al"):"160x90"==a.adsize?(b=160,c=90,d="160x90_0ads_al"):"120x90"==a.adsize&&(b=120,c=90,d="120x90_0ads_al");var f="text_image";"T"==a.adtype?f="text":"I"==a.adtype&&(f="image");var g="";a.color&&"custom"==a.color&&(g="google_color_border = '"+a.border_color+"';google_color_bg = '"+a.color_bg+"';google_color_link = '"+a.color_link+"';google_color_text = '"+
a.color_text+"';google_color_url = '"+a.color_url+"';");b="<script type='text/javascript'>google_ad_client = '"+AmpedSense.adsensepublisherid+"';google_ad_width = "+b+";google_ad_height = "+c+";google_ad_format = '"+d+"';google_ad_type = '"+f+"';google_ad_channel = '"+e+"'; "+g+"\x3c/script><script type='text/javascript' src='//pagead2.googlesyndication.com/pagead/show_ads.js'>\x3c/script>"}return b};
		</script>
		<?php
	}


	public function hook_ad($ad, $channelid)
	{
		//based on ad location, add hook to show ad

		//use class that passes along $ad var
		$renderer = new AmpedSenseRenderer();
		$renderer->ad = $ad;
		$renderer->publisherid = $this->settings['adsensepublisherid'];
		$renderer->channelid = $channelid;

		if($ad['adlocation'] == 'AP' ||
			$ad['adlocation'] == 'IL' ||
			$ad['adlocation'] == 'IR' ||
			$ad['adlocation'] == 'PL' ||
			$ad['adlocation'] == 'PC' ||
			$ad['adlocation'] == 'PR' ||
			$ad['adlocation'] == '1L' ||
			$ad['adlocation'] == '1C' ||
			$ad['adlocation'] == '1R' ||
			$ad['adlocation'] == '2L' ||
			$ad['adlocation'] == '2C' ||
			$ad['adlocation'] == '2R' ||
			$ad['adlocation'] == '3L' ||
			$ad['adlocation'] == '3C' ||
			$ad['adlocation'] == '3R' ||
			$ad['adlocation'] == 'BP')
		{
			//content related
			add_filter('the_content',  array( $renderer, 'inject_ad_in_content' ), 200); //priority after wpautop (should be 10, much higher just in case)
		}
		//sidebar widgets
		elseif($ad['adlocation'] == 'SA')
		{
			$this->widgetA_renderers[] = $renderer;
		}
		elseif($ad['adlocation'] == 'SB')
		{
			$this->widgetB_renderers[] = $renderer;
		}
		elseif($ad['adlocation'] == 'SC')
		{
			$this->widgetC_renderers[] = $renderer;
		}
		elseif($ad['adlocation'] == 'SD')
		{
			$this->widgetD_renderers[] = $renderer;
		}
		elseif($ad['adlocation'] == 'SE')
		{
			$this->widgetE_renderers[] = $renderer;
		}
		elseif($ad['adlocation'] == 'SF')
		{
			$this->widgetF_renderers[] = $renderer;
		}
		//shortcodes
		elseif($ad['adlocation'] == 'CA')
		{
			$this->shortcodeA_renderers[] = $renderer;
		}
		elseif($ad['adlocation'] == 'CB')
		{
			$this->shortcodeB_renderers[] = $renderer;
		}
		elseif($ad['adlocation'] == 'CC')
		{
			$this->shortcodeC_renderers[] = $renderer;
		}
		elseif($ad['adlocation'] == 'CD')
		{
			$this->shortcodeD_renderers[] = $renderer;
		}
		elseif($ad['adlocation'] == 'CE')
		{
			$this->shortcodeE_renderers[] = $renderer;
		}
		elseif($ad['adlocation'] == 'CF')
		{
			$this->shortcodeF_renderers[] = $renderer;
		}
	}

	public function shortcodeA()
	{
		if($this->settings['render']=='client') return "<script>AmpedSense.OptimizeAdSpot('CA')</script>";
		else return AmpedSenseRenderer::applyRenderers($this->shortcodeA_renderers);
	}
	public function shortcodeB()
	{
		if($this->settings['render']=='client') return "<script>AmpedSense.OptimizeAdSpot('CB')</script>";
		else return AmpedSenseRenderer::applyRenderers($this->shortcodeB_renderers);
	}
	public function shortcodeC()
	{
		if($this->settings['render']=='client') return "<script>AmpedSense.OptimizeAdSpot('CC')</script>";
		else return AmpedSenseRenderer::applyRenderers($this->shortcodeC_renderers);
	}
	public function shortcodeD()
	{
		if($this->settings['render']=='client') return "<script>AmpedSense.OptimizeAdSpot('CD')</script>";
		else return AmpedSenseRenderer::applyRenderers($this->shortcodeD_renderers);
	}
	public function shortcodeE()
	{
		if($this->settings['render']=='client') return "<script>AmpedSense.OptimizeAdSpot('CE')</script>";
		else return AmpedSenseRenderer::applyRenderers($this->shortcodeE_renderers);
	}
	public function shortcodeF()
	{
		if($this->settings['render']=='client') return "<script>AmpedSense.OptimizeAdSpot('CF')</script>";
		else return AmpedSenseRenderer::applyRenderers($this->shortcodeF_renderers);
	}


	public function make_settings_menu()
	{
		//this must be its own function
		add_menu_page( 'AmpedSense', 'AmpedSense', 'administrator', 'ampedsense-main', array( $this, 'make_main_page' ), $this->get_admin_dir()."resources/icon.png" );
		add_submenu_page( 'ampedsense-main', 'Segments', 'Segments', 'administrator', 'ampedsense-segments', array( $this, 'make_segments_page' ) );
		add_submenu_page( 'ampedsense-main', 'Settings', 'Settings', 'administrator', 'ampedsense-settings', array( $this, 'make_settings_page' ) );

		//replace first submenu 'AmpedSense' with 'Split Tests'
		global $submenu;
		if ( isset( $submenu['ampedsense-main'] ) )
			$submenu['ampedsense-main'][0][0] = __( 'Split Tests', 'ampedsense-main' );
	}

	public function loadAdminStylesAndScripts()
	{
		//only do this if an AS admin page! ?page=ampedsense-main
		if (isset($_GET['page']) && stristr($_GET['page'],'ampedsense'))
		{
			//enqueue js and css
			wp_enqueue_style("AmpedSense Custom BS", $this->get_admin_dir()."resources/aswrapped-bootstrap-3.0.3.css");
			wp_enqueue_style("AmpedSense Custom Style", $this->get_admin_dir()."resources/as_style.css");
			wp_enqueue_style("jQuery UI Smoothness", $this->get_admin_dir()."resources/jquery-ui-smoothness-1.10.4.css");
			wp_enqueue_script("jquery-ui-core");
			wp_enqueue_script("jquery-ui-datepicker");
			wp_enqueue_script("jquery-ui-dialog");
			//must mark the above as dependencies in below, otherwise jquery ui will be loaded in footer since it's registered in the footer by wp core
			wp_enqueue_script("jscolor", $this->get_admin_dir()."resources/jscolor.js",array("jquery-ui-core","jquery-ui-datepicker"));
		}
	}

	public function make_main_page()
	{
		include( plugin_dir_path(__FILE__) . 'main.php' );
	}

	public function make_segments_page()
	{
		include( plugin_dir_path(__FILE__) . 'segments.php' );
	}

	public function make_settings_page()
	{
		include( plugin_dir_path(__FILE__) . 'settings.php' );
	}

	public function handle_action($action)
	{
		$toreturn = "";

		if($action=='setauth')
		{
			//convert param into usable vars
			$param = base64_decode($_GET['as_p']);
			list($at,$rt,$exp,$installid) = explode("|||",$param);
			if($at!='' && $rt!='' && $exp!='')
			{
				//set refresh token
				$this->settings['googlerefreshtoken'] = $rt;
				$this->settings_dirty = true;

				//access token should also have been passed
				$_SESSION['as_googleaccesstoken'] = $at;
				$_SESSION['as_googleaccesstokenexpires'] = time() + $exp;

				//save installid
				if($installid!='' && is_numeric($installid))
				{
					$this->settings['installid'] = $installid;
					//setting already dirty
				}

				$toreturn = "Successfully authenticated! Verify Publisher ID below, or manage <a href='".admin_url('admin.php?page=ampedsense-main')."'>split tests</a>";
			}
			else
			{
				$toreturn = "Hmmm, couldn't authenticate. Try again? [$param]";
			}
		}
		elseif($action=='updatesettings')
		{
			//validate nonce
			check_admin_referer($action);

			//set vars
			$this->settings['adsensepublisherid'] = $this->cleanInput($_POST['as_adsensepublisherid']);
			$this->settings['siteabbrev'] = $this->cleanInput($_POST['as_siteabbrev']);
			$this->settings['render'] = $this->cleanInput($_POST['as_render']);
			$this->settings['lcse_k'] = $this->cleanInput($_POST['as_lcse_k']);

			//check
			if($this->settings['lcse_k']!='')
			{
				if(preg_match('/^[a-z0-9]+[a-z]+\d{7}$/i',$this->settings['lcse_k'])) { $this->settings['lcse_v'] = 'p'; $toreturn = "THANK YOU FOR BEING A PREMIUM MEMBER. FEATURES UNLOCKED!"; }
				else { $this->settings['lcse_v'] = ''; }
				$this->settings['lcse_checked'] = time();
			}
			else $this->settings['lcse_v'] = '';

			$this->settings_dirty = true;
			if($toreturn=='') $toreturn = "Settings Saved."; //only show this boring message if not upgrading
		}
		elseif($action=='addsegment')
		{
			//validate nonce
			check_admin_referer($action);

			$newsegment = array();
			$newsegment['devices'] = $_POST['as_devices'];
			$newsegment['criteria'] = $_POST['as_criteria'];

			if($_POST['as_criteria']=='page') $newsegment['criteriaparam'] = $_POST['as_criteriaparam_page'];
			elseif($_POST['as_criteria']=='post') $newsegment['criteriaparam'] = $_POST['as_criteriaparam_post'];
			elseif($_POST['as_criteria']=='category' || $_POST['as_criteria']=='list') $newsegment['criteriaparam'] = $_POST['as_criteriaparam_category'];

			$newsegment['segmentname'] = $this->cleanInput($_POST['as_segmentname']);
			//by default, enable showing stats
			$newsegment['hide'] = false;

			$editing = (isset($_POST['as_editingsegmenti']) && $_POST['as_editingsegmenti']!='') ? true : false;
			//add new or edit?
			if($editing)
			{
				$editingsegmenti = $_POST['as_editingsegmenti'];
				//make sure to preserve recipes
				$previousrecipes = $this->settings['segments'][$editingsegmenti]['recipes'];
				$this->settings['segments'][$editingsegmenti] = $newsegment;
				$this->settings['segments'][$editingsegmenti]['recipes'] = $previousrecipes;
				$toreturn = "Segment Updated.";
			}
			else
			{
				//add to beginning of list
				array_unshift($this->settings['segments'], $newsegment);
				$toreturn = "Segment Created.";
			}

			$this->settings_dirty = true;
		}
		elseif($action=='reordersegments')
		{
			//validate nonce
			check_admin_referer($action);

			$orderedsegmentsarr = array();
			$wouldoverwritearr = array();
			foreach($_POST['priority'] as $key=>$val)
			{
				$i = $val-1;
				//make sure we don't overwrite
				if(array_key_exists($i,$orderedsegmentsarr)) $wouldoverwritearr[] = $this->settings['segments'][$key];
				else $orderedsegmentsarr[$i] = $this->settings['segments'][$key];
			}
			foreach($wouldoverwritearr as $savedseg) $orderedsegmentsarr[] = $savedseg;
			ksort($orderedsegmentsarr); //even though indexes are in order, may still need to be sorted
			$this->settings['segments'] = $orderedsegmentsarr;
			$this->settings_dirty = true;
			$toreturn = "Segment Order Saved.";
		}
		elseif($action=='deletesegment')
		{
			//validate nonce
			check_admin_referer($action);

			$key = $_GET['as_segmentindex'];
			unset($this->settings['segments'][$key]);
			ksort($this->settings['segments']); //even though indexes are in order, may still need to be sorted
			$this->settings_dirty = true;
			$toreturn = "Segment Deleted.";
		}
		elseif($action=='togglesegment')
		{
			//validate nonce
			check_admin_referer($action);

			$key = $_GET['as_segmentindex'];
			if(!isset($this->settings['segments'][$key]['hide']))
			{
				//init setting
				$this->settings['segments'][$key]['hide'] = 1;
			}
			else
			{
				//toggle
				$this->settings['segments'][$key]['hide'] = !$this->settings['segments'][$key]['hide'];
			}
			$this->settings_dirty = true;
			$toreturn = "Segment Toggled.";
		}
		elseif($action=='addrecipe')
		{
			//validate nonce
			check_admin_referer($action);

			//just add it to the settings var
			$editing = (isset($_POST['as_editingrecipei']) && $_POST['as_editingrecipei']!='') ? true : false;
			$newrecipe['recipename'] = $this->cleanInput($_POST['as_recipename']);
			$newrecipe['channelname'] = $this->cleanInput($_POST['as_channelname']);
			$newrecipe['whenstarted'] = time();
			$newrecipe['ads'] = array();
			for($i=1; $i<=$_POST['as_numads']; $i++)
			{
				$newad = array();
				if($_POST['as_custom'][$i]=="resp" || $_POST['as_custom'][$i]=="html")
				{
					$newad['custom'] = $_POST['as_custom'][$i];
					$newad['customcode'] = $this->cleanInput($_POST['as_customcode'][$i]);
				}
				else
				{
					$newad['custom'] = "no";
					$newad['adsize'] = $_POST['as_adsize'][$i];
					$newad['adtype'] = $_POST['as_adtype'][$i];
				}
				$newad['adlocation'] = $_POST['as_adlocation'][$i];
				$newad['adpadding'] = $this->cleanInput($_POST['as_adpadding'][$i]);
				$newad['admargin'] = $this->cleanInput($_POST['as_admargin'][$i]);
				if($_POST['as_color'][$i]=="custom")
				{
					$newad['color'] = "custom";
					$newad['color_border'] = $this->cleanInput($_POST['as_color_border'][$i]);
					$newad['color_bg'] = $this->cleanInput($_POST['as_color_bg'][$i]);
					$newad['color_link'] = $this->cleanInput($_POST['as_color_link'][$i]);
					$newad['color_text'] = $this->cleanInput($_POST['as_color_text'][$i]);
					$newad['color_url'] = $this->cleanInput($_POST['as_color_url'][$i]);
				}
				else
				{
					$newad['color'] = "default";
				}

				$newrecipe['ads'][] = $newad;
			}

			$segmenti = $_POST['as_segmenti'];
			//add new or edit?
			if($editing)
			{
				$editingrecipei = $_POST['as_editingrecipei'];
				$newrecipe['active'] = $this->settings['segments'][$segmenti]['recipes'][$editingrecipei]['active'];
				$this->settings['segments'][$segmenti]['recipes'][$editingrecipei] = $newrecipe;
				$toreturn = "Ad Recipe Updated.";
			}
			else
			{
				$newrecipe['active'] = true;
				$this->settings['segments'][$segmenti]['recipes'][] = $newrecipe;
				$toreturn = "Ad Recipe Created.";
			}

			//automatically try and find id for new channel
			$this->lookup_channels();

			$this->settings_dirty = true;
		}

		elseif($action=='deleterecipe')
		{
			//validate nonce
			check_admin_referer($action);

			$segmenti = $_GET['as_segmenti'];
			$recipei = $_GET['as_recipei'];

			//remove recipe from array
			unset($this->settings['segments'][$segmenti]['recipes'][$recipei]);

			$this->settings_dirty = true;
			$toreturn = "Ad Recipe Deleted.";
		}

		elseif($action=='pauserecipe')
		{
			//validate nonce
			check_admin_referer($action);

			$segmenti = $_GET['as_segmenti'];
			$recipei = $_GET['as_recipei'];

			//just update active flag
			$this->settings['segments'][$segmenti]['recipes'][$recipei]['active'] = 0;

			$this->settings_dirty = true;
			$toreturn = "Ad Recipe Paused.";
		}
		elseif($action=='resumerecipe')
		{
			//validate nonce
			check_admin_referer($action);

			$segmenti = $_GET['as_segmenti'];
			$recipei = $_GET['as_recipei'];

			//just update active flag and restart whenstarted
			$this->settings['segments'][$segmenti]['recipes'][$recipei]['active'] = 1;
			$this->settings['segments'][$segmenti]['recipes'][$recipei]['whenstarted'] = time();

			$this->settings_dirty = true;
			$toreturn = "Ad Recipe Resumed.";
		}
		elseif($action=='updatenotes')
		{
			//validate nonce
			check_admin_referer($action);

			$segmenti = $_POST['as_segmenti'];
			$recipei = $_POST['as_recipei'];
			$notes = $_POST['as_recipenotes'];
			//echo "S/R:".$segmenti.$recipei; exit;
			//just update notes
			$this->settings['segments'][$segmenti]['recipes'][$recipei]['notes'] = $notes;

			$this->settings_dirty = true;
			$toreturn = "Notes updated.";
		}
		elseif($action=='setreportdate')
		{
			//just set session var
			$i = $_GET['as_segmenti'];
			$_SESSION['as_fromdate'][$i] = $_GET['as_fromdate'];
		}
		elseif($action=='lookupchannels')
		{
			//manually get channelids
			$this->lookup_channels();
		}


		//Save to db. Destructor doesn't work
		if($this->settings_dirty)
		{
			//save vars to db
			update_option('ampedsense_settings', $this->settings);
		}

		return $toreturn;
	}

	public function get_admin_dir()
	{
		return plugin_dir_url(__FILE__);
	}

	public function print_logo()
	{
		?>
		<a href="http://www.ampedsense.com" target="_blank"><img src="<?php echo $this->get_admin_dir(); ?>resources/logo.png" style="float:right"/></a>
		<div style="clear:both"></div>
		<?php
	}
	public function get_channel_report_url()
	{
		return "https://www.google.com/adsense/app#viewreports/ag=channel";
	}
	public function get_add_channel_url()
	{
		return "https://www.google.com/adsense/app#myads-springboard/product=SELF_SERVICE_CONTENT_ADS&view=CHANNELS";
	}

	public function get_google_login_url()
	{
		$google_client_id = "769187834256-9n3ih7f1049u92lq5n3u68qn8j33rqk8.apps.googleusercontent.com";
		$redirect_uri = urlencode("http://www.ampedsense.com/api/wporg-googlehandler.php");
		$real_redirect_uri = admin_url('admin.php?page=ampedsense-settings');
		$scope = urlencode("https://www.googleapis.com/auth/adsense.readonly");

		//set state
		$state = "";
		if(!isset($this->settings['installid']))
		{
			//first time authenticating
			//pass contact so can get install id and init training
			$state = get_option('admin_email')."|||".$real_redirect_uri;
		}
		else
		{
			$state = $this->settings['installid']."|||".$real_redirect_uri;
		}
		$encodedstate = base64_encode($state);

		return "https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=$google_client_id&redirect_uri=$redirect_uri&scope=$scope&access_type=offline&approval_prompt=force&state=$encodedstate";  //force so that it always returns refresh token
	}

	public function ensureGoogleAccessToken()
	{
		//have refresh token, let's get an access token if we need it
		if(!isset($_SESSION['as_googleaccesstoken']) || $_SESSION['as_googleaccesstoken']=='' || (isset($_SESSION['as_googleaccesstokenexpires']) && time() > $_SESSION['as_googleaccesstokenexpires']))
		{
			//use proxy so we never expose our secret
			$installid = isset($this->settings['installid']) ? $this->settings['installid'] : 0;
			$returnedas = $this->getUrlContents("http://www.ampedsense.com/api/wporg-googlehandler.php?installid=$installid&rt=".urlencode($this->settings['googlerefreshtoken']));
			if(stristr($returnedas,"Error")!="" || stristr($returnedas,"|||")=="") //shouldn't have error, need delimiter
			{
				echo "Error: $returnedas";
			}
			else
			{
				list($accesstoken,$expirein) = explode("|||",$returnedas);
				$_SESSION['as_googleaccesstoken'] = $accesstoken;
				$_SESSION['as_googleaccesstokenexpires'] = time() + $expirein;
			}
		}
	}

	public function get_segment_preview_url($segmenti)
	{
		$criteria = isset($this->settings['segments'][$segmenti]['criteria']) ? $this->settings['segments'][$segmenti]['criteria'] : null;
		$criteriaparam = isset($this->settings['segments'][$segmenti]['criteriaparam']) ? $this->settings['segments'][$segmenti]['criteriaparam'] : null;
		$nonefoundurl = "http://www.ampedsense.com/empty-segment";
		if($criteria=="post")
		{
			//get specific post url
			return get_permalink($criteriaparam);
		}
		elseif($criteria=="page")
		{
			//get specific page url
			return get_permalink($criteriaparam);
		}
		elseif($criteria=="list")
		{
			//get specific list url
			return get_category_link($criteriaparam);
		}
		elseif($criteria=="category")
		{
			//get any post in specified category
			$recent_posts = wp_get_recent_posts( array( 'numberposts' => '1', 'post_type' => 'post', 'post_status' => 'publish', 'category' => $criteriaparam  ));
			if(count($recent_posts)) return get_permalink($recent_posts[0]["ID"]);
			else return $nonefoundurl;
		}
		elseif($criteria=="homepage")
		{
			//home url
			return get_home_url();
		}
		elseif($criteria=="allposts")
		{
			//get any post
			$recent_posts = wp_get_recent_posts( array( 'numberposts' => '1', 'post_type' => 'post', 'post_status' => 'publish'  ));
			if(count($recent_posts)) return get_permalink($recent_posts[0]["ID"]);
			else return $nonefoundurl;
		}
		elseif($criteria=="allpages")
		{
			//get any page
			$recent_posts = wp_get_recent_posts( array( 'numberposts' => '1', 'post_type' => 'page', 'post_status' => 'publish'  ));
			if(count($recent_posts)) return get_permalink($recent_posts[0]["ID"]);
			else return $nonefoundurl;
		}
		elseif($criteria=="alllists")
		{
			//get any category
			$categories = get_categories();
			//for some reason, array it was returning didn't always start at 0 index
			$keys = array_keys($categories);
			$firstkey = $keys[0];
			if(count($categories)) return get_category_link($categories[$firstkey]->term_id);
			else return $nonefoundurl;
		}
		else // default (all) segment
		{
			//return post or page
			$recent_posts = wp_get_recent_posts( array( 'numberposts' => '1', 'post_type' => 'post', 'post_status' => 'publish'  ));
			if(count($recent_posts)) return get_permalink($recent_posts[0]["ID"]);
			else
			{
				$recent_posts = wp_get_recent_posts( array( 'numberposts' => '1', 'post_type' => 'page', 'post_status' => 'publish'  ));
				if(count($recent_posts)) return get_permalink($recent_posts[0]["ID"]);
				else return $nonefoundurl;
			}
		}
	}

	public function get_recipe_preview_qs($recipe)
	{
		//as_preview=1&as_numads=1&as_custom%5B1%5D=0&as_adsize%5B1%5D=300x250&as_adtype%5B1%5D=TI&as_adlocation%5B1%5D=AP&as_recipename=&as_channelname=&as_channelid=
		$count = count($recipe['ads']);
		$qs = "as_preview=1&as_numads=$count";
		foreach($recipe['ads'] as $key=>$ad)
		{
			$i = $key+1;
			if(isset($ad['custom'])) $qs .= "&as_custom[$i]=".$ad['custom'];
			if(isset($ad['adsize'])) $qs .= "&as_adsize[$i]=".$ad['adsize'];
			if(isset($ad['adtype'])) $qs .= "&as_adtype[$i]=".$ad['adtype'];
			if(isset($ad['adlocation'])) $qs .= "&as_adlocation[$i]=".$ad['adlocation'];
			if(isset($ad['adpadding'])) $qs .= "&as_adpadding[$i]=".urlencode($ad['adpadding']);
			if(isset($ad['admargin'])) $qs .= "&as_admargin[$i]=".urlencode($ad['admargin']);
			//don't put html into query string, for security
			//if(isset($ad['customcode'])) $qs .= "&as_customcode[$i]=".urlencode($ad['customcode']);
			if(isset($ad['color'])) $qs .= "&as_color[$i]=".urlencode($ad['color']);
			if(isset($ad['color_border'])) $qs .= "&as_color_border[$i]=".urlencode($ad['color_border']);
			if(isset($ad['color_bg'])) $qs .= "&as_color_bg[$i]=".urlencode($ad['color_bg']);
			if(isset($ad['color_link'])) $qs .= "&as_color_link[$i]=".urlencode($ad['color_link']);
			if(isset($ad['color_text'])) $qs .= "&as_color_text[$i]=".urlencode($ad['color_text']);
			if(isset($ad['color_url'])) $qs .= "&as_color_url[$i]=".urlencode($ad['color_url']);
		}
		return $qs;
	}

	public function get_ir_fromdate($segmenti)
	{
		if(isset($_SESSION['as_fromdate'][$segmenti]) && $_SESSION['as_fromdate'][$segmenti]!="")
		{
			return $_SESSION['as_fromdate'][$segmenti];
		}
		else
		{
			//go through each recipe, pick latest date (for best split testing results, plus makes default report screen faster
			$latest = 0; //beginning of time
			if(isset($this->settings['segments'][$segmenti]['recipes']) && count($this->settings['segments'][$segmenti]['recipes']))
			{
				foreach($this->settings['segments'][$segmenti]['recipes'] as $recipe)
				{
					//only do those that are active
					if($recipe['active'])
					{
						if($recipe['whenstarted']>$latest)
						{
							$latest = $recipe['whenstarted'];
						}
					}
				}
			}
			//if no active recipes, just show today's date
			if($latest==0) $latest = time();

			return date("m/d/Y",$latest);
		}
		return "XX/XX/XX";
	}

	public function lookup_channels()
	{
		//don't need to see if any channelids are missing, they always will be on edit or on new, or whenever this function is called

		//lookup custom channel ids if some are missing / new
		if(isset($_SESSION['as_googleaccesstoken']) && $_SESSION['as_googleaccesstoken']!="")
		{

			//first need ad client ids
			$adclientids = array();
			$returnedjson = $this->getUrlContents("https://www.googleapis.com/adsense/v1.4/accounts/".urlencode($this->settings['adsensepublisherid'])."/adclients/?access_token=".urlencode($_SESSION['as_googleaccesstoken'])."&userIp=".$this->ip);
			$apiresult = json_decode($returnedjson,true);
			if(count($apiresult) && empty($apiresult["error"]))
			{
				//could be multiple, save them
				if(count($apiresult['items']))
				{
					//like ca-pub-89204534XXXXXXXX and ca-mb-pub-XXXXXXXXXX
					foreach($apiresult['items'] as $item)
					{
						$adclientids[] = $item['id'];
					}
				}
			}
			else echo "Error: $returnedjson";

			if(count($adclientids))
			{
				//now get channels for each adclientid
				$customchannels = array();
				foreach($adclientids as $adclientid)
				{
					echo "Retrieving custom channels...";
					$returnedjson = $this->getUrlContents("https://www.googleapis.com/adsense/v1.4/accounts/".urlencode($this->settings['adsensepublisherid'])."/adclients/".urlencode($adclientid)."/customchannels/?access_token=".urlencode($_SESSION['as_googleaccesstoken'])."&userIp=".$this->ip);
					$apiresult = json_decode($returnedjson,true);
					if(count($apiresult) && empty($apiresult["error"]))
					{
						//will be multiple, save them
						if(isset($apiresult['items']) && count($apiresult['items']))
						{
							//like    "kind": "adsense#customChannel","id": "ca-pub-892040000:00000009999","code": "000000009999", "name": "XXXXXXXXXXXX"
							foreach($apiresult['items'] as $item)
							{
								$name = $item['name'];
								$customchannels[$name] = $item['code'];
							}
						}
					}
					else echo "Error: $returnedjson";

					usleep(100000); //.1 second delay for quota mgmt
				}

				//now have list of ALL custom channels, see which we need to set
				if(count($customchannels))
				{
					foreach($this->settings['segments'] as $segmenti=>$segment)
					{
						if(isset($segment["recipes"]) && count($segment["recipes"]))
						{
							foreach($segment["recipes"] as $recipei=>$recipe)
							{
								if(!isset($recipe["channelid"]) || $recipe["channelid"]=="")
								{
									foreach($customchannels as $name=>$code)
									{
										if($recipe['channelname']==$name)
										{
											$this->settings['segments'][$segmenti]['recipes'][$recipei]['channelid'] = $code;
											$this->settings_dirty = true;
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	public function deviceCriteriaToString($devices)
	{
		//d=desktop, t=tablet, p=phone
		if($devices=='dtp') return "All Devices";
		if($devices=='dt') return "Desktops & Tablets";
		if($devices=='tp') return "Tablets & Phones";
		if($devices=='d') return "Desktops";
		if($devices=='t') return "Tablets";
		if($devices=='p') return "Phones";
		return "Unknown";
	}



	//Utility functions

	public function toPercent($num)
	{
		return sprintf("%01.2f", ($num*100));
	}

	public function cleanInput( $string )
	{
		if(!isset($string)) return "";

		$string = trim($string);

		if(true) { //wordpress automatically escapes quotes, regardless of if get_magic_quotes_gpc() is on
			return stripslashes($string);
		} else {
			return $string;
		}
	}

	public function getUrlContents($url)
	{
		//abstract out retrieving url, since file_get_contents vs curl may not be supported depending on host
		$response = wp_remote_get( $url, array('timeout' => 60) ); //large sites take long time for Google to compute
		return wp_remote_retrieve_body($response);
	}

	public $googleclientid = "832329400699-sp4npe7175l2td6gcc7ahou1h8sjav3l.apps.googleusercontent.com";
}

//need to make seperate class so can associate ad with callback
class AmpedSenseRenderer
{
	public $ad = null;
	public $publisherid = "";
	public $channelid = "";
	public $watermark = "<!-- Ad split testing with AmpedSense: http://www.ampedsense.com -->";

	public function inject_ad_in_content($thecontent)
	{
		$adhtml = $this->render_ad();
		$padding = (isset($this->ad['adpadding']) && $this->ad['adpadding']!="") ? "padding: ".$this->ad['adpadding']."; " : "";
		$margin = (isset($this->ad['admargin']) && $this->ad['admargin']!="") ? "margin: ".$this->ad['admargin']."; " : "";

		if($this->ad['adlocation']=="AP")
		{
			//above post
			$thecontent = $this->watermark."<div style='width:100%; text-align:center; $padding $margin'>".$adhtml."</div>".$thecontent;
		}
		elseif($this->ad['adlocation']=="IL")
		{
			//inline left
			$thecontent = $this->watermark."<div style='float:left; $padding $margin'>".$adhtml."</div>".$thecontent;
		}
		elseif($this->ad['adlocation']=="IR")
		{
			//inline right
			$thecontent = $this->watermark."<div style='float:right; $padding $margin'>".$adhtml."</div>".$thecontent;
		}
		elseif($this->ad['adlocation']=="PL")
		{
			//after 1st paragraph, left
			$addiv = $this->watermark."<div style='float:left; $padding $margin'>".$adhtml."</div>";
			$thecontent = $this->inject_at_distance($thecontent,$addiv,"P");
		}
		elseif($this->ad['adlocation']=="PC")
		{
			//after 1st paragraph, center
			$addiv = $this->watermark."<div style='width:100%; text-align:center; $padding $margin'>".$adhtml."</div>";
			$thecontent = $this->inject_at_distance($thecontent,$addiv,"P");
		}
		elseif($this->ad['adlocation']=="PR")
		{
			//after 1st paragraph, right
			$addiv = $this->watermark."<div style='float:right; $padding $margin'>".$adhtml."</div>";
			$thecontent = $this->inject_at_distance($thecontent,$addiv,"P");
		}
		elseif($this->ad['adlocation']=="1L")
		{
			//after 1/4 of content, left
			$addiv = $this->watermark."<div style='float:left; $padding $margin'>".$adhtml."</div>";
			$thecontent = $this->inject_at_distance($thecontent,$addiv,"1");
		}
		elseif($this->ad['adlocation']=="1C")
		{
			//after 1/4 of content, center
			$addiv = $this->watermark."<div style='width:100%; text-align:center; $padding $margin'>".$adhtml."</div>";
			$thecontent = $this->inject_at_distance($thecontent,$addiv,"1");
		}
		elseif($this->ad['adlocation']=="1R")
		{
			//after 1/4 of content, right
			$addiv = $this->watermark."<div style='float:right; $padding $margin'>".$adhtml."</div>";
			$thecontent = $this->inject_at_distance($thecontent,$addiv,"1");
		}
		elseif($this->ad['adlocation']=="2L")
		{
			//after 1/2 of content, left
			$addiv = $this->watermark."<div style='float:left; $padding $margin'>".$adhtml."</div>";
			$thecontent = $this->inject_at_distance($thecontent,$addiv,"2");
		}
		elseif($this->ad['adlocation']=="2C")
		{
			//after 1/2 of content, center
			$addiv = $this->watermark."<div style='width:100%; text-align:center; $padding $margin'>".$adhtml."</div>";
			$thecontent = $this->inject_at_distance($thecontent,$addiv,"2");
		}
		elseif($this->ad['adlocation']=="2R")
		{
			//after 1/2 of content, right
			$addiv = $this->watermark."<div style='float:right; $padding $margin'>".$adhtml."</div>";
			$thecontent = $this->inject_at_distance($thecontent,$addiv,"2");
		}
		elseif($this->ad['adlocation']=="3L")
		{
			//after 3/4 of content, left
			$addiv = $this->watermark."<div style='float:left; $padding $margin'>".$adhtml."</div>";
			$thecontent = $this->inject_at_distance($thecontent,$addiv,"3");
		}
		elseif($this->ad['adlocation']=="3C")
		{
			//after 3/4 of content, center
			$addiv = $this->watermark."<div style='width:100%; text-align:center; $padding $margin'>".$adhtml."</div>";
			$thecontent = $this->inject_at_distance($thecontent,$addiv,"3");
		}
		elseif($this->ad['adlocation']=="3R")
		{
			//after 3/4 of content, right
			$addiv = $this->watermark."<div style='float:right; $padding $margin'>".$adhtml."</div>";
			$thecontent = $this->inject_at_distance($thecontent,$addiv,"3");
		}
		elseif($this->ad['adlocation']=="BP")
		{
			//below post
			$thecontent = $thecontent.$this->watermark."<div style='width:100%; text-align:center; $padding $margin'>".$adhtml."</div>";
		}

		//return since filter
		return $thecontent;
	}

	public function inject_at_distance($thecontent,$addiv,$distance)
	{
		//$distance = P/1/2/3
		//split content into paragraph arr
		//called after wpautop, so split based on p tags
		$paragrapharr = preg_split('/<\/p>/', $thecontent, -1, PREG_SPLIT_NO_EMPTY);

		//figure out index
		$insertindex = 0;
		if($distance=="P")
		{
			//after p1
			$insertindex = 1;
		}
		elseif($distance=="1")
		{
			// 1/4 way down
			$insertindex = round(count($paragrapharr)*.25);
		}
		elseif($distance=="2")
		{
			// 1/2 way down
			$insertindex = round(count($paragrapharr)*.5);
		}
		elseif($distance=="3")
		{
			// 3/4 way down
			$insertindex = round(count($paragrapharr)*.75);
		}

		//insert it
		$adddivendplaceholder = '#ADEND#';
		array_splice($paragrapharr, $insertindex, 0, array($addiv.$adddivendplaceholder));

		//join it all back
		$final = implode("</p>", $paragrapharr);
		$final = str_replace($adddivendplaceholder."</p>",'',$final); //remove extra closing p added by inserting our stuff
		$final = str_replace($adddivendplaceholder,'',$final); //if no content or no </p> at all, make sure we still remove ADEND
		return $final;
	}

	public function render_ad()
	{
		//return ad snippet
		if($this->ad['custom']=="resp" || $this->ad['custom']=="html")
		{
			//wordpress will auto paragraph newlines on all html content (won't do it if within <script>, so strip out newlines
			//return str_replace(array('\n','\r'),"",$this->ad['customcode']);
			return trim(preg_replace('/\s+/', ' ', $this->ad['customcode']));
		}

		elseif($this->ad['custom']=="script")
		{
			//THIS IS DEPRECATED as of v4.0 - was too confusing to have both options, just do HTML always
			// Can't edit or create new recipes with type 'script', but still support it on existing sites
			return "<script>".$this->ad['customcode']."</script>";
		}
		else
		{
			//generate dynamic adsense ad
			$width = 0;
			$height = 0;
			$format = '';
			//in order excluding recommended
			if($this->ad['adsize']=="728x90") { $width = 728; $height = 90; $format = "728x90_as"; }
			elseif($this->ad['adsize']=="320x100") { $width = 320; $height = 100; $format = "320x100_as"; }
			elseif($this->ad['adsize']=="970x250") { $width = 970; $height = 250; $format = "970x250_as"; }
			elseif($this->ad['adsize']=="970x90") { $width = 970; $height = 90; $format = "970x90_as"; }
			elseif($this->ad['adsize']=="468x60") { $width = 468; $height = 60; $format = "468x60_as"; }
			elseif($this->ad['adsize']=="320x50") { $width = 320; $height = 50; $format = "320x50_as"; }
			elseif($this->ad['adsize']=="234x60") { $width = 234; $height = 60; $format = "234x60_as"; }
			elseif($this->ad['adsize']=="300x600") { $width = 300; $height = 600; $format = "300x600_as"; }
			elseif($this->ad['adsize']=="300x1050") { $width = 300; $height = 1050; $format = "300x1050_as"; }
			elseif($this->ad['adsize']=="160x600") { $width = 160; $height = 600; $format = "160x600_as"; }
			elseif($this->ad['adsize']=="120x600") { $width = 120; $height = 600; $format = "120x600_as"; }
			elseif($this->ad['adsize']=="120x240") { $width = 120; $height = 240; $format = "120x240_as"; }
			elseif($this->ad['adsize']=="336x280") { $width = 336; $height = 280; $format = "336x280_as"; }
			elseif($this->ad['adsize']=="300x250") { $width = 300; $height = 250; $format = "300x250_as"; }
			elseif($this->ad['adsize']=="250x250") { $width = 250; $height = 250; $format = "250x250_as"; }
			elseif($this->ad['adsize']=="200x200") { $width = 200; $height = 200; $format = "200x200_as"; }
			elseif($this->ad['adsize']=="180x150") { $width = 180; $height = 150; $format = "180x150_as"; }
			elseif($this->ad['adsize']=="125x125") { $width = 125; $height = 125; $format = "125x125_as"; }
			elseif($this->ad['adsize']=="728x15") { $width = 728; $height = 15; $format = "728x15_0ads_al"; }
			elseif($this->ad['adsize']=="468x15") { $width = 468; $height = 15; $format = "468x15_0ads_al"; }
			elseif($this->ad['adsize']=="200x90") { $width = 200; $height = 90; $format = "200x90_0ads_al"; }
			elseif($this->ad['adsize']=="180x90") { $width = 180; $height = 90; $format = "180x90_0ads_al"; }
			elseif($this->ad['adsize']=="160x90") { $width = 160; $height = 90; $format = "160x90_0ads_al"; }
			elseif($this->ad['adsize']=="120x90") { $width = 120; $height = 90; $format = "120x90_0ads_al"; }

			$type = 'text_image';
			if($this->ad['adtype']=='T') $type = 'text';
			elseif($this->ad['adtype']=='I') $type = 'image';

			$colorsettings = "";
			if(isset($this->ad['color']) && $this->ad['color']=='custom')
			{
				//dont put more newlines in here, wordpress puts <p>
				$colorsettings = "
				google_color_border = '".$this->ad['color_border']."';
				google_color_bg = '".$this->ad['color_bg']."';
				google_color_link = '".$this->ad['color_link']."';
				google_color_text = '".$this->ad['color_text']."';
				google_color_url = '".$this->ad['color_url']."';";
			}

			//clientid and channelid have already been confirmed set
			return "<script type='text/javascript'>
				google_ad_client = '".$this->publisherid."';
				google_ad_width = ".$width.";
				google_ad_height = ".$height.";
				google_ad_format = '".$format."';
				google_ad_type = '".$type."';
				google_ad_channel = '".$this->channelid."'; $colorsettings
				</script><script type='text/javascript' src='//pagead2.googlesyndication.com/pagead/show_ads.js'></script>";
		}
	}

	//static
	public function applyRenderers($rendererarr)
	{
		$buffer = "";
		if(count($rendererarr))
		{
			foreach($rendererarr as $renderer)
			{
				$adhtml = $renderer->render_ad();
				$padding = (isset($renderer->ad['adpadding']) && $renderer->ad['adpadding']!="") ? "padding: ".$renderer->ad['adpadding']."; " : "";
				$margin = (isset($renderer->ad['admargin']) && $renderer->ad['admargin']!="") ? "margin: ".$renderer->ad['admargin']."; " : "";
				$buffer .= $renderer->watermark."<div style='width:100%; text-align:center; $padding $margin'>".$adhtml."</div>";
			}
		}
		return $buffer;
	}
}

/////////////// WIDGETS ////////////////////

class AmpedSenseSidebarA extends WP_Widget
{
	public function __construct()
	{
		// widget actual processes
		parent::__construct(
			'AmpedSenseSidebarA', // Base ID
			'AmpedSense Sidebar A', // Name
			array( 'description' => __( 'A container for split testing AdSense on a sidebar', 'text_domain' ), ) // Args
		);
	}

	public function widget( $args, $instance )
	{
		// outputs the content of the widget
		global $amped_sense;
		if($amped_sense->settings['render']=='client') echo "<script>AmpedSense.OptimizeAdSpot('SA')</script>";
		else echo AmpedSenseRenderer::applyRenderers($amped_sense->widgetA_renderers);
	}
	//don't need form() or update() since no options
}

class AmpedSenseSidebarB extends WP_Widget
{
	public function __construct()
	{
		// widget actual processes
		parent::__construct(
			'AmpedSenseSidebarB', // Base ID
			'AmpedSense Sidebar B', // Name
			array( 'description' => __( 'A container for split testing AdSense on a sidebar', 'text_domain' ), ) // Args
		);
	}

	public function widget( $args, $instance )
	{
		// outputs the content of the widget
		global $amped_sense;
		if($amped_sense->settings['render']=='client') echo "<script>AmpedSense.OptimizeAdSpot('SB')</script>";
		else echo AmpedSenseRenderer::applyRenderers($amped_sense->widgetB_renderers);
	}
	//don't need form() or update() since no options
}

class AmpedSenseSidebarC extends WP_Widget
{
	public function __construct()
	{
		// widget actual processes
		parent::__construct(
			'AmpedSenseSidebarC', // Base ID
			'AmpedSense Sidebar C', // Name
			array( 'description' => __( 'A container for split testing AdSense on a sidebar', 'text_domain' ), ) // Args
		);
	}

	public function widget( $args, $instance )
	{
		// outputs the content of the widget
		global $amped_sense;
		if($amped_sense->settings['render']=='client') echo "<script>AmpedSense.OptimizeAdSpot('SC')</script>";
		else echo AmpedSenseRenderer::applyRenderers($amped_sense->widgetC_renderers);
	}
	//don't need form() or update() since no options
}

class AmpedSenseSidebarD extends WP_Widget
{
	public function __construct()
	{
		// widget actual processes
		parent::__construct(
			'AmpedSenseSidebarD', // Base ID
			'AmpedSense Sidebar D', // Name
			array( 'description' => __( 'A container for split testing AdSense on a sidebar', 'text_domain' ), ) // Args
		);
	}

	public function widget( $args, $instance )
	{
		// outputs the content of the widget
		global $amped_sense;
		if($amped_sense->settings['render']=='client') echo "<script>AmpedSense.OptimizeAdSpot('SD')</script>";
		else echo AmpedSenseRenderer::applyRenderers($amped_sense->widgetD_renderers);
	}
	//don't need form() or update() since no options
}

class AmpedSenseSidebarE extends WP_Widget
{
	public function __construct()
	{
		// widget actual processes
		parent::__construct(
			'AmpedSenseSidebarE', // Base ID
			'AmpedSense Sidebar E', // Name
			array( 'description' => __( 'A container for split testing AdSense on a sidebar', 'text_domain' ), ) // Args
		);
	}

	public function widget( $args, $instance )
	{
		// outputs the content of the widget
		global $amped_sense;
		if($amped_sense->settings['render']=='client') echo "<script>AmpedSense.OptimizeAdSpot('SE')</script>";
		else echo AmpedSenseRenderer::applyRenderers($amped_sense->widgetE_renderers);
	}
	//don't need form() or update() since no options
}

class AmpedSenseSidebarF extends WP_Widget
{
	public function __construct()
	{
		// widget actual processes
		parent::__construct(
			'AmpedSenseSidebarF', // Base ID
			'AmpedSense Sidebar F', // Name
			array( 'description' => __( 'A container for split testing AdSense on a sidebar', 'text_domain' ), ) // Args
		);
	}

	public function widget( $args, $instance )
	{
		// outputs the content of the widget
		global $amped_sense;
		if($amped_sense->settings['render']=='client') echo "<script>AmpedSense.OptimizeAdSpot('SF')</script>";
		else echo AmpedSenseRenderer::applyRenderers($amped_sense->widgetF_renderers);
	}
	//don't need form() or update() since no options
}

//////////////////////////
// MAIN //////////////////
//////////////////////////


//create instance
$amped_sense = new AmpedSense();

//register activation hooks
register_activation_hook( __FILE__ , array( $amped_sense, 'activate' ) );
register_deactivation_hook( __FILE__ , array( $amped_sense, 'deactivate' ) );



?>
