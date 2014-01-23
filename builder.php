
<?php 	
/***
	This purpose of this script is to gather all the product information for all active shops on the Client Live site.
	It will then output a CVS accourding to the Kneon Spec.
	All paremeters will be hard coded as globals for this script 	
	***/

	/*** Client Stage Urls  ***/
	$catUrl = '';
	$prodUrl = ''; 	

	/***  Global Defaults ***/ 	

	$totalProductsFound;
	$totalCategoryProducts;
	$productDetailArray = array();
	$columHeader = array( 		"RETAILER_NAME","RETAILER_URL","LAST_UPDATED","PRODUCT_NAME","KEYWORDS","DESCRIPTION","SKU","MANUFACTURER","UPC","PRICE","SALE_PRICE","PRODUCT_URL","IMAGE_URL","INSTOCK_QUANTITY" 	);


	array_push($productDetailArray, $columHeader);

	$csvOutputFile = 'kneon.csv';
	$botName = "Kneon Builder: ";
	$shopCategoryID = array(
		'HANDBAGS' =--> 'id',
		'WALLETS + SMALL LEATHER GOODS'=> 'id',
		'SHOES' => 'id',
		'JEWELRY + WATCHES' => 'id',
		'SUNGLASSES' => 'id',
		'OUTERWEAR'=> 'id',
		'Dresses' => 'id',
		'Tops' => 'id',
		'Skirts + Pants' => 'id',
		'Accessories' => 'id'
		);

$welcomeArt = '
_ _
| |  / )
| | / / ____ ____ ___ ____
| |< < | _ \ / _ ) _ \| _ \
| | \ \| | | ( (/ / |_| | |_
|_|  |_| \_)_| |_|\____)___/
                           csv builder '; 


function checkClientServices(){
/*** Check if PHP has access to http and can reach Client site ***/ 
	$domain = 'www.Client.com';
	$connected = @fsockopen($domain, 80); 
	if ($connected){ 
		$connectionState = true; fclose($connected); 
	}else{ 
		$connectionState = false; 
	} return $connectionState;
}

function startKneonBuilder(){ 
/***
This is the main engine.
If it can connect it will check the Shop Category Web Service for all the products currently available and build them into the product array with all
needed information we have available After completely a category, it will update the user and continue After completing all categories, 
it will make a CSV file and export it to the file system based on it's path 
***/ 

	global $welcomeArt, $shopCategoryID, $totalCategoryProducts, $totalProductsFound, $csvOutputFile, $botName, $productDetailArray; 
	if(checkClientServices()){ 
		echo $welcomeArt; 
		echo "\n";
		echo $botName. "Connecting to Company Webservices\n"; 
		date_default_timezone_set('America/New_York'); 
		$time_start = microtime(true); 
		foreach ($shopCategoryID as $key => $value) {
 		$totalCategoryProducts = 0;
 		setProductArrayById($value);
 		$time_end = microtime(true);
 		$execution_time = ($time_end - $time_start);
 		echo $botName.$totalCategoryProducts.' total styles in '.$key.' ('.round($execution_time,2)." sec)\n";
 	}
	echo $botName.$totalProductsFound." products added to CSV \n";

 	$fp = fopen($csvOutputFile, 'w');
 	foreach ($productDetailArray as $prod) {
 		fputcsv($fp, $prod);
 	}
 	fclose($fp);

 	echo $botName.'CSV create here: '.$csvOutputFile."\n";
 	$time_end = microtime(true);
 	$execution_time = ($time_end - $time_start)/60;
 	echo $botName.round($execution_time,2).'(min) to complete'."\n";

 }else{
 	echo $botName."!-------- Error --------! \n Could not Connect to Company Webservices";
 }
}

function setProductArrayById($id){
 /***
 We consume the category listing details and build the detail array
 When we have the detail array set, we add it to the productDetailArray
 ***/
	 global $catUrl, $productDetailArray;
	 $url = $catUrl.$id;
	 $xml = simplexml_load_file($url);
	 if(sizeof($productDetailArray) >0){
	 	$tempArray = getProductDetailArrayByXml($xml);
	 	foreach ($tempArray as $key => $item) {
	 		array_push($productDetailArray,$item);
	 	}
	 }else{
	 	$productDetailArray = getProductDetailArrayByXml($xml);
	 }
}

function getProductDescriptionById($id){
 /***
 The product description is not in the category page, so we have to check it against the detail page
 We take the style id and get the the entire product information and then scrape the description from it.
 ***/
	 global $prodUrl;
	 $url = $prodUrl.$id;
	 $xml = simplexml_load_file($url);
	 $des = strval($xml->longDescription);
	 return $des;
}

function getProductDetailArrayByXml($xml){
 /***
 The category XML has most of the information we need to build the CSV, so we scrape what we can grab
 The details we can't get, we grab from the product info web service
 We then add up the counters and return the new array
 ***/
 global $totalProductsFound, $totalCategoryProducts;
 $details = array();
 $styles = $xml->styles->style;
 foreach ($styles as $key => $style) {

 	$ccode= strtolower($style->color->attributes()->code);
 	$ccode= str_replace('/','',$ccode);
 	$number = strtolower($style->attributes()->number);
 	$buyable = strtoupper(strval($style->color->attributes()->buyable));
 	$price = strtolower($style->color->attributes()->price);
 	$tempArray = array();

 	$tempArray['RETAILER_NAME']= "Client Name";
 	$tempArray['RETAILER_URL']= "http://www.client.com";
 	$tempArray['LAST_UPDATED']= date("c");
 	$tempArray['PRODUCT_NAME']= strval($style->shortDescription);
 	$tempArray['KEYWORDS']= "keywords";
 	$tempArray['DESCRIPTION']= getProductDescriptionById($number);
 	$tempArray['SKU']= "";
 	$tempArray['MANUFACTURER']= "Reed Krakoff";
 	$tempArray['UPC']= $number.'_'.$ccode;
 	$tempArray['PRICE']= str_replace('$','',$price);
 	$tempArray['SALE_PRICE']= "";
 	$tempArray['PRODUCT_URL']= "http://www.client.com/#view=product-detail&styleNumber=".$number."&colorCode=".$ccode;
 	$tempArray['IMAGE_URL']= 'http://client-cdn/'.$number.'_'.$ccode.'_a0?$rkpd_557x557$';
 	$tempArray['INSTOCK_QUANTITY'] = $buyable;
 	array_push($details, $tempArray);
 	$totalProductsFound++;
 	$totalCategoryProducts++;
 }
 return $details;
}

/*** Start Builder ***/
startKneonBuilder();
?>
