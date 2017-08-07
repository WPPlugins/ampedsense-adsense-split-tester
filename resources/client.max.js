//Minimize with https://closure-compiler.appspot.com/home , in SIMPLE mode

//segment settings and page info already dumped to AmpedSense object



//little helper func must be set first:

AmpedSense.QueryStringToObj = function()
{
    var params = {}, queries, temp, i, l;

    // Split into key/value pairs
    queries = window.location.search.split("&");

    // Convert the array of strings into an object
    for ( i = 0, l = queries.length; i < l; i++ ) {
        temp = queries[i].split('=');
        params[temp[0]] = temp[1];
    }

    return params;
};





//The real magic...

//set AmpedSense.recipe - either via rotating or previewing
if(window.location.search.indexOf('as_preview=1')==-1)
{
	//real traffic

	//determine segment
	AmpedSense.device = 'd';
	//width is super tricky for some reason, on mobile. window.innerWidth was returing 0 on my phone, yet clientWidth was returning way higher than it really is.
	// tried adjusting screen.width with devicePixelRatio (* and /), both weren't ideal
	// using media queries now, much better
	//test for it first, since doesn't exist on IE9 and below, or Opera Mini
	if (typeof window.matchMedia == 'function')
	{
		//taken from http://foundation.zurb.com/sites/docs/v/5.5.3/media-queries.html
		//max-width 640px, for phones
		if(window.matchMedia("only screen and (max-device-width: 640px)").matches) AmpedSense.device = 'p';
		//max-width 1024px, for tablets 
		else if(window.matchMedia("only screen and (max-device-width: 1024px)").matches) AmpedSense.device = 't';
	} //else assume old IE, thus desktop
	
	AmpedSense.segmenti = -1;
	for (var i in AmpedSense.segments)
	{
		if (AmpedSense.segments.hasOwnProperty(i)) //in case any of JS libraries adding members to all object prototypes
		{
			var segment = AmpedSense.segments[i];

			if(segment.devices.indexOf(AmpedSense.device)!= -1)
			{
				//matched device, now page criteria
				if(segment['criteria']=="allpages" && AmpedSense.is_page) { AmpedSense.segmenti = i; break; }
				if(segment['criteria']=="allposts" && AmpedSense.is_single) { AmpedSense.segmenti = i; break; }
				if(segment['criteria']=="alllists" && AmpedSense.is_category) { AmpedSense.segmenti = i; break; }
				if(segment['criteria']=="homepage" && AmpedSense.is_front_page) { AmpedSense.segmenti = i; break; }
				if((segment['criteria']=="page" || segment['criteria']=="post") && AmpedSense.post_ID == segment['criteriaparam']) { AmpedSense.segmenti = i; break; }
				if(segment['criteria']=="list" && AmpedSense.category_ID == segment['criteriaparam']) { AmpedSense.segmenti = i; break; }
				if(segment['criteria']=="category" && AmpedSense.post_category_IDs.length && AmpedSense.post_category_IDs.indexOf(parseInt(segment['criteriaparam']))!=-1) { AmpedSense.segmenti = i; break; } //criteriaparam is in string form. Fine when comparing single values to ints, but not when in an array
				if(segment['criteria']=="default") { AmpedSense.segmenti = i; break; }
			}
			
		}
	}
	
	//pick ad
	if(AmpedSense.segmenti!=-1)
	{
		var segment = AmpedSense.segments[AmpedSense.segmenti];
		var recipekeys = [];
		for (var j in segment.recipes)
		{
			if (segment.recipes.hasOwnProperty(j)) recipekeys.push(j);
		}
		var chosenrecipekey = recipekeys[Math.floor(Math.random() * recipekeys.length)];
		if(chosenrecipekey) AmpedSense.recipe = segment.recipes[chosenrecipekey];
		//console.log(AmpedSense.recipe);
	}
}
else
{
	//previewing with URL params
	AmpedSense.recipe = {};
	AmpedSense.recipe.ads = [];
	AmpedSense.recipe.channelid = "0";
	
	qsObj = AmpedSense.QueryStringToObj();
	
	//create array of ads from parameters
	var paramNames = ['custom','adsize','adtype','adlocation','adpadding','admargin','color','border_color','color_bg','color_link','color_text','color_url'];
	for(var i=1; i<=qsObj.as_numads; i++)
	{
		var newad = {};
		for(var j=0; j<=paramNames.length; j++)
		{
			//set up param names, since hard for JS to unserialize query string with array in it
			var paramName = paramNames[j];
			var qsParamName = 'as_'+paramName+'%5B'+i+'%5D';
			if(qsObj[qsParamName]) 
			{
				//came from post
				newad[paramName] = qsObj[qsParamName];
			}
			else
			{
				//else came from get
				qsParamName = 'as_'+paramName+'['+i+']';
				if(qsObj[qsParamName]) newad[paramName] = qsObj[qsParamName];
			}
			
			//extra handling if custom
			if(paramName == 'custom')
			{
				//security risk, just show black box instead
				if(qsObj[qsParamName]=='html') newad['customcode'] = "<div style='border:2px solid white; background:black; color:white'>CUSTOM CODE HERE<br/>Custom code cannot be previewed for security reasons.<br/>On live traffic this box will be replaced with your custom code.</div>";
				else if(qsObj[qsParamName]=='resp') newad['customcode'] = "<div style='border:2px solid white; background:black; color:white'>RESPONSIVE AD UNIT HERE<br/>Responsive code cannot be previewed for security reasons.<br/>On live traffic this box will be replaced with your responsive ad unit.</div>";
			}
		}
		
		AmpedSense.recipe.ads.push(newad);
	}
}



AmpedSense.OptimizeAdSpot = function(location)
{
	if(AmpedSense.recipe)
	{
		for(var k in AmpedSense.recipe.ads)
		{
			if (AmpedSense.recipe.ads.hasOwnProperty(k))
			{
				var ad = AmpedSense.recipe.ads[k];
				if(ad.adlocation==location)
				{
					//get what's needed to render ad
					var adhtml = AmpedSense.RenderAd(ad,AmpedSense.recipe.channelid);
					var padding = (ad.adpadding && ad.adpadding!="") ? "padding: "+ad.adpadding+"; " : "";
					var margin = (ad.admargin && ad.admargin!="") ? "margin: "+ad.admargin+"; " : "";
					
					//now write ad, along with alignment div
					if(location=='AP' || location=='PC' || location=='1C' || location=='2C' || location=='3C' || location=='BP' || location=='SA' || location=='SB' || location=='SC' || location=='SD' || location=='SE' || location=='SF' || location=='CA' || location=='CB' || location=='CC' || location=='CD' || location=='CE' || location=='CF') document.write("<div style='width:100%; text-align:center; " + padding + margin + "'>" + adhtml + "</div>");
					else if(location=='IL' || location=='PL' || location=='1L' || location=='2L' || location=='3L') document.write("<div style='float:left; " + padding + margin + "'>" + adhtml + "</div>");
					else if(location=='IR' || location=='PR' || location=='1R' || location=='2R' || location=='3R') document.write("<div style='float:right; " + padding + margin + "'>" + adhtml + "</div>");
				}
			}
		}
	}
};

AmpedSense.RenderAd = function(ad, channelid)
{
	var adhtml = "";
	if(ad.custom=='resp' || ad.custom=='html')
	{
		adhtml = ad.customcode;
	}
	else
	{
		//generate dynamic adsense ad
		var width = 0;
		var height = 0;
		var format = '';
		//in order excluding recommended
		if(ad.adsize=="728x90") { width = 728; height = 90; format = "728x90_as"; }
		else if(ad.adsize=="320x100") { width = 320; height = 100; format = "320x100_as"; }
		else if(ad.adsize=="970x250") { width = 970; height = 250; format = "970x250_as"; }
		else if(ad.adsize=="970x90") { width = 970; height = 90; format = "970x90_as"; }
		else if(ad.adsize=="468x60") { width = 468; height = 60; format = "468x60_as"; }
		else if(ad.adsize=="320x50") { width = 320; height = 50; format = "320x50_as"; }
		else if(ad.adsize=="234x60") { width = 234; height = 60; format = "234x60_as"; }
		else if(ad.adsize=="300x600") { width = 300; height = 600; format = "300x600_as"; }
		else if(ad.adsize=="300x1050") { width = 300; height = 1050; format = "300x1050_as"; }
		else if(ad.adsize=="160x600") { width = 160; height = 600; format = "160x600_as"; }
		else if(ad.adsize=="120x600") { width = 120; height = 600; format = "120x600_as"; }
		else if(ad.adsize=="120x240") { width = 120; height = 240; format = "120x240_as"; }
		else if(ad.adsize=="336x280") { width = 336; height = 280; format = "336x280_as"; }
		else if(ad.adsize=="300x250") { width = 300; height = 250; format = "300x250_as"; }
		else if(ad.adsize=="250x250") { width = 250; height = 250; format = "250x250_as"; }
		else if(ad.adsize=="200x200") { width = 200; height = 200; format = "200x200_as"; }
		else if(ad.adsize=="180x150") { width = 180; height = 150; format = "180x150_as"; }
		else if(ad.adsize=="125x125") { width = 125; height = 125; format = "125x125_as"; }
		else if(ad.adsize=="728x15") { width = 728; height = 15; format = "728x15_0ads_al"; }
		else if(ad.adsize=="468x15") { width = 468; height = 15; format = "468x15_0ads_al"; }
		else if(ad.adsize=="200x90") { width = 200; height = 90; format = "200x90_0ads_al"; }
		else if(ad.adsize=="180x90") { width = 180; height = 90; format = "180x90_0ads_al"; }
		else if(ad.adsize=="160x90") { width = 160; height = 90; format = "160x90_0ads_al"; }
		else if(ad.adsize=="120x90") { width = 120; height = 90; format = "120x90_0ads_al"; }
		
		var type = 'text_image';
		if(ad.adtype=='T') type = 'text';
		else if(ad.adtype=='I') type = 'image';
		
		var colorsettings = "";
		if(ad.color && ad.color=='custom')
		{
			colorsettings =
				"google_color_border = '"+ad.border_color+"';" +
				"google_color_bg = '"+ad.color_bg+"';" +
				"google_color_link = '"+ad.color_link+"';" +
				"google_color_text = '"+ad.color_text+"';" +
				"google_color_url = '"+ad.color_url+"';";
		}
		
		//clientid and channelid have already been confirmed set
		adhtml = "<scr"+"ipt type='text/javascript'>" +
			"google_ad_client = '"+AmpedSense.adsensepublisherid+"';" +
			"google_ad_width = "+width+";" +
			"google_ad_height = "+height+";" +
			"google_ad_format = '"+format+"';" +
			"google_ad_type = '"+type+"';" +
			"google_ad_channel = '"+channelid+"'; " +
			colorsettings +
			"</scr"+"ipt><scr"+"ipt type='text/javascript' src='//pagead2.googlesyndication.com/pagead/show_ads.js'></scr"+"ipt>";
	}
	return adhtml;
};