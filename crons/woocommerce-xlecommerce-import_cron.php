<?php
define( 'WP_USE_THEMES', false );

require( $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/wp-load.php' );

define('NODE_SMALL_IMAGE_PATH', 							'SmallImagePath');

define('NODE_MEDIUM_IMAGE_PATH', 							'MediumImagePath');

define('NODE_LARGE_IMAGE_PATH', 							'LargeImagePath');

define('NODE_CURRENCY_CODE', 								'CurrencyCode');

define('NODE_WEIGHT_UNIT', 									'WeightUnit');

define('NODE_CATEGORIES', 									'Categories');

define('NODE_CATEGORY_ID', 									'CategoryID');

define('NODE_CATEGORY_IDS', 								'CategoryIDs');

define('NODE_CATEGORY_NAME', 								'CategoryName');

define('NODE_CATEGORY_PARENT_ID', 							'CategoryParentID');

define('NODE_PRODUCT_OPTIONS', 								'ProductOptions');

define('NODE_PRODUCT_OPTION_ID', 							'ProductOptionID');

define('NODE_PRODUCT_OPTION_NAME', 							'ProductOptionName');

define('NODE_PRODUCT_OPTION_VALUES', 						'ProductOptionValues');

define('NODE_PRODUCT_OPTION_VALUE_ID', 						'ProductOptionValueID');

define('NODE_PRODUCT_OPTION_VALUE_NAME', 					'ProductOptionValueName');

define('NODE_PRODUCTS', 									'Products');

define('NODE_PRODUCT_NAME', 								'ProductName');

define('NODE_PRODUCT_DESCRIPTION', 							'ProductDescription');

define('NODE_PRODUCT_MODEL', 								'ProductModel');

define('NODE_PARENT_PRODUCT_MODEL', 						'ParentProductModel');

define('NODE_PRODUCT_QUANTITY', 							'ProductQuantity');

define('NODE_PRODUCT_MANUFACTURER', 						'ProductManufacturer');

define('DEFAULT_NODE_PRODUCT_PRICE', 						'WholesalePrice');

define('NODE_PRODUCT_UPC', 									'UPC_EAN');

define('NODE_MAP_PRICE', 									'MAPPrice');

define('NODE_PRODUCT_WEIGHT', 								'ProductWeight');

define('NODE_PRODUCT_SMALL_IMAGE', 							'ProductSmallImage');

define('NODE_PRODUCT_MEDIUM_IMAGE', 						'ProductMediumImage');

define('NODE_PRODUCT_LARGE_IMAGE', 							'ProductLargeImage');

define('NODE_PRODUCT_ATTRIBUTES', 							'ProductAttributes');

define('NODE_PRODUCT_OPTION_VALUE_PRICE', 					'ProductOptionValuePrice');

define('NODE_PRODUCT_SPECIFICATIONS', 						'ProductSpecifications');

define('PERMISSIBLE_FEEDS_LIMIT', 							'20');

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
define('XML_FEED_DEFAULT_PRICE_TYPE','MS');

if (XML_FEED_DEFAULT_PRICE_TYPE == 'MS')
    define('NODE_PRODUCT_PRICE', 'MSRPPrice');

elseif (XML_FEED_DEFAULT_PRICE_TYPE == 'UP')
    define('NODE_PRODUCT_PRICE', 'CostPrice');

elseif (XML_FEED_DEFAULT_PRICE_TYPE == 'MA')
    define('NODE_PRODUCT_PRICE', 'MAPPrice');
else
    define('NODE_PRODUCT_PRICE', 'WholesalePrice');

// get xle configuration data from database #start	
$xle_data = get_option( 'xle_settings' );
define('DEFAULT_MARKUP',									$xle_data['default_markup']);
define('ROUNDOFF_FLAG',										$xle_data['default_roundoff']);
define('FEED_DIRECTORY',									$xle_data['path_xlecommerce_feed_dir'].$xle_data['retailer_token'].'/');
// get xle configuration data from database #ends
	
global $categories;
	
function woocommerce_obn_import_image1($url){
	$attach_id = 0;
	$wp_upload_dir = wp_upload_dir();
	
	$filename = $wp_upload_dir['path'].'/'.sanitize_file_name(basename($url));
	
	if(@getimagesize($filename)){
		$url = $filename;
	}else{
		//Encode the URL
		$base = basename($url);
		$url = str_replace($base,urlencode($base),$url);
	}
	
	if($f = @file_get_contents($url)){
		file_put_contents($filename,$f);
		
		$wp_filetype = wp_check_filetype(basename($filename), null );
		
		$attachment = array(
			'guid' => $wp_upload_dir['url'] . '/' . basename( $filename ), 
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $filename, 37 );
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );
	}
	return $attach_id;
}
	
function woocommerce_obn_run_cats1($parent = 0, $parent_term_id = 0,$categories_name) {
	global $categories;
	
	if($parent_term_id != 0){
		if(!array_key_exists($parent_term_id,$categories)){
			return;
		}
	}
	
	$term = term_exists($categories_name, 'product_cat', $categories[$parent_term_id]); // array is returned if taxonomy is given
	
	if (!is_wp_error($category)) {
		if (((int) $term['term_id'] == 0) ) {
			
		   $term = wp_insert_term(
					$categories_name, // the term 
					'product_cat', // the taxonomy
					array(
						'parent' => $categories[$parent_term_id]
					)
			);
			
			$categories[$parent] = $term['term_id'];
			
			delete_option('product_cat_children'); // clear the cache
			
			add_woocommerce_term_meta($term['term_id'], 'order', '0');
			add_woocommerce_term_meta($term['term_id'], 'display_type', '');
			add_woocommerce_term_meta($term['term_id'], 'thumbnail_id', '0');
			add_woocommerce_term_meta($term['term_id'], 'obn_id', $parent);
		}
	}
}
	
function woocommerce_xlecommerce_submenu_page_callback1() {
	global $wpdb, $osc_categories,$categories;
	
	$categories = array();
	
	$dir = FEED_DIRECTORY;

	$feeds = array();

	if (is_dir($dir)) {

		if ($dh = opendir($dir)) {

			while (($file = readdir($dh)) !== false) {

				$pos = strpos($file, 'product_feed_');

				if ($pos !== false) {

					$feeds[] = $dir . $file;
				}
			}

			closedir($dh);
		}
	}
				
			
	// get all woocommerce category #start
	$terms = get_terms('product_cat',array('hide_empty' => 0));
	foreach ( $terms as $term ) {
		$o = get_woocommerce_term_meta($term->term_id,'obn_id',true);
		$categories[$o] = (int)$term->term_id;
	}
	// get all woocommerce category #ends
	
	
	if (file_exists($dir . 'OBN_categories.xml')) {
	
		$categories_xml = simplexml_load_file($dir . 'OBN_categories.xml');
	
		foreach ($categories_xml->children() as $category) {
	
			$temp_id = (string) $category->{NODE_CATEGORY_ID};
	
			$temp_name = htmlspecialchars_decode((string) $category->{NODE_CATEGORY_NAME});
	
			$temp_parent_id = (string) $category->{NODE_CATEGORY_PARENT_ID};
			
			if(!array_key_exists($temp_id,$categories)){
				woocommerce_obn_run_cats1($temp_id,$temp_parent_id,$temp_name);						
			}
		}
		@unlink($dir.'OBN_categories.xml');
	}
			
	// Get all categories by OSC cat ID
	$products_images = array();
			
	foreach ($feeds as $feed) {
					
					echo basename($feed)."\n";

					$xml = @simplexml_load_file($feed);

					if (!$xml) {
						@unlink($feed);
						continue;
					}
					
					
					$temp_id = '';

					$temp_name = '';
		
					$temp_option_id = '';
		
					$temp_value_id = '';
		
					$temp_prod_cat_ids = '';
		
					$temp_parent_id = '';
		
					$temp_prod_desc = '';
		
					$temp_prod_model = '';
		
					$temp_parent_prod_model = '';
		
					$temp_prod_qty = '';
		
					$temp_prod_price = '';
		
					$temp_prod_map = '';
		
					$temp_prod_weight = '';
		
					$temp_prod_image_small = '';
		
					$temp_prod_image_medium = '';
		
					$temp_prod_image_large = '';
		
					// Import the products
					foreach ($xml->{NODE_PRODUCTS}->children() as $product) {
							
							$products_status = (string) $product['status'];

							if ($products_status) {
								
								$temp_name = utf8_decode(htmlspecialchars_decode((string) $product->{NODE_PRODUCT_NAME}));
			
								$temp_prod_cat_ids = (string) $product->{NODE_CATEGORY_IDS};
			
								$temp_prod_desc = utf8_decode(htmlspecialchars_decode((string) $product->{NODE_PRODUCT_DESCRIPTION}));
			
								$temp_prod_model = htmlspecialchars_decode((string) $product->{NODE_PRODUCT_MODEL});
			
								$temp_parent_prod_model = htmlspecialchars_decode((string) $product->{NODE_PARENT_PRODUCT_MODEL});
			
								$temp_prod_qty = (string) $product->{NODE_PRODUCT_QUANTITY};
			
								$temp_prod_price = (string) $product->{NODE_PRODUCT_PRICE};
			
								if ($temp_prod_price <= 0) {
			
									$temp_prod_price = (string) $product->{DEFAULT_NODE_PRODUCT_PRICE};
								}
			
								$temp_prod_map = (string) $product->{NODE_MAP_PRICE};
			
								$temp_prod_weight = (string) $product->{NODE_PRODUCT_WEIGHT};
			
								$temp_prod_image_small = (string) $product->{NODE_PRODUCT_SMALL_IMAGE};
			
								$temp_prod_image_medium = (string) $product->{NODE_PRODUCT_MEDIUM_IMAGE};
			
								$temp_prod_image_large = (string) $product->{NODE_PRODUCT_LARGE_IMAGE};
			
								$temp_product_upc = (string) $product->{NODE_PRODUCT_UPC};
								
								$products_price = (ROUNDOFF_FLAG ? apply_roundoff(get_price_with_markup($temp_prod_price, DEFAULT_MARKUP)) : get_price_with_markup($temp_prod_price, DEFAULT_MARKUP));
										
								
								$check_product_query = "select post_id from wp_postmeta where meta_key = '_sku' and meta_value = '".$temp_prod_model."'";
								
								$existing_product = $wpdb->get_results($check_product_query,ARRAY_A);
								
							if(empty($existing_product)){ // insert product
								
								$product_id = wp_insert_post(array(
								  'post_title'    => $temp_name,
								  'post_content'  => $temp_prod_desc,
								  'post_status'   => 'publish',
								  'post_type' 	  => 'product',
								  'post_author'   => 1
								));
								
								wp_set_object_terms($product_id, 'simple', 'product_type');
								wp_set_object_terms($product_id, (int)$categories[$temp_prod_cat_ids], 'product_cat');
								update_post_meta($product_id, '_sku', $temp_prod_model);
								update_post_meta($product_id, '_regular_price', $products_price);
								update_post_meta($product_id, '_price', $products_price);
								update_post_meta($product_id, '_base_price', $temp_prod_price);
								update_post_meta($product_id, '_visibility', 'visible');
								update_post_meta($product_id, '_stock_status', '1');
								update_post_meta($product_id, '_manage_stock', '1');
								update_post_meta($product_id, '_weight', (int)$temp_prod_weight);
								update_post_meta($product_id, '_upc_ean', $temp_product_upc);
								update_post_meta($product_id, '_unit_cost', (!empty($product->CostPrice)) ? ((string) $product->CostPrice) : "0.00");
								update_post_meta($product_id, '_unit_cost_cur', $currency_code);
								update_post_meta($product_id, '_min_acceptable_price', $temp_prod_map);
								update_post_meta($product_id, '_unit_msrp', (!empty($product->MSRPPrice)) ? ((string) $product->MSRPPrice) : "0.00");
								update_post_meta($product_id, '_obn_product', "1");
								update_post_meta($product_id, '_stock', $temp_prod_qty);
								
								if($temp_prod_image_large != ''){
									$products_images[$product_id] = $url = "https://productdatahub.com/images/".$temp_prod_image_large;
								}
								
							}else{ // update product
								
								wp_update_post(array(
								  'post_title'    => $temp_name,
								  'post_content'  => $temp_prod_desc,
								  'post_status'   => 'publish',
								  'post_type' 	  => 'product',
								  'post_author'   => 1
								));
								
								$product_id = $existing_product[0]['post_id'];
								
								update_post_meta($product_id, '_regular_price', $products_price);
								update_post_meta($product_id, '_price', $products_price);
								update_post_meta($product_id, '_base_price', $temp_prod_price);
								update_post_meta($product_id, '_visibility', 'visible');
								update_post_meta($product_id, '_stock_status', '1');
								update_post_meta($product_id, '_manage_stock', '1');
								update_post_meta($product_id, '_weight', (int)$temp_prod_weight);
								update_post_meta($product_id, '_upc_ean', $temp_product_upc);
								update_post_meta($product_id, '_unit_cost', (!empty($product->CostPrice)) ? ((string) $product->CostPrice) : "0.00");
								update_post_meta($product_id, '_unit_cost_cur', $currency_code);
								update_post_meta($product_id, '_min_acceptable_price', $temp_prod_map);
								update_post_meta($product_id, '_unit_msrp', (!empty($product->MSRPPrice)) ? ((string) $product->MSRPPrice) : "0.00");
								
								if($temp_prod_image_large != ''){
									$products_images[$product_id] = $url = "https://productdatahub.com/images/".$temp_prod_image_large;
								}
								
							}
							
							// Handle attributes
						   
							
							if(!empty($product->Specifications)){
								$attrib_array = array();
								//wp_set_object_terms($product_id, 'variable', 'product_type');
								
								foreach ($product->Specifications->children() as $specification) {

									$temp_name  = (string) $specification->SpecificationName;

									$temp_value = (string) $specification->SpecificationValue;
									
									$slug = sanitize_title($temp_value);
									
									$attrib_array[$slug] = array(
											'name' 			=> $temp_name,
											'value' 		=> ltrim($attrib_array[$slug]['value'] . ' | ' .$temp_value, ' | '),
											'position' 		=> 0,
											'is_visible' 	=> 1,
											'is_variation' 	=> 1,
											'is_taxonomy' 	=> 0
									);
								}
								update_post_meta($product_id, '_product_attributes', $attrib_array);
							}
						}
					}
					
					@unlink($feed);
				}
				fetchRemoteImages1($products_images);
}
	
function fetchRemoteImages1($image_array){
	
	foreach($image_array as $product_id => $url ){
		
		$attach_id = 0;
		
		$attach_id = woocommerce_obn_import_image1($url);
		
		if($attach_id > 0){
			set_post_thumbnail($product_id, $attach_id);
		}
		
	}
}
	
function apply_roundoff($price_value) {

	$pos = strpos($price_value, '.');

	if ($pos === false) {
		$response = ($price_value - 1) . '.99';
	} else {

		$response = $price_value;

		$value_parts = explode('.', $response);

		if (strlen($value_parts[1]) > 2) {

			$value_parts[1] = substr($value_parts[1], 0, 2);
		}

		$response = $value_parts[0] . '.' . ($value_parts[1] + (99 - $value_parts[1]));
	}

	return $response;
}

function get_price_with_markup($base_price, $markup) {

        if (empty($markup)) {

            return $base_price;
        } else {

            $markup_figure = $markup; // holds markup figure

            $markup_in_percent = 0; //a check if markup is in percentage

            $markup_in_negative = 0; //a check if markup is negative



            $markup_in_margin = 0;

            if (substr($markup_figure, -1) == '%') { // if markup in percentage
                $markup_in_percent = 1; //update percentage check

                $markup_figure = substr($markup_figure, 0, -1); //modify markup figure by removing percentage
            } elseif (stripos($markup_figure, 'Margin') !== false) { // if markup in percentage
                $markup_in_margin = 1; //update percentage check

                $markup_figure = substr($markup_figure, 0, strpos($markup_figure, "%")); //modify markup figure by removing percentage
            }

            if (substr($markup_figure, 0, 1) == '-') {// if negative value exists
                $markup_in_negative = 1; //update negetive check

                $markup_figure = substr($markup_figure, 1); //modify markup figure by removing minus
            }

            if ($markup_in_margin) {

                if ($markup_in_negative)
                    $price = $base_price * (1 / (1 + ($markup_figure / 100) ) );
                else
                    $price = $base_price * (1 / (1 - ($markup_figure / 100) ) );

                return $price;
            } else {

                return ($markup_in_negative ? ($base_price - ($markup_in_percent ? (($base_price * $markup_figure) / 100) : $markup_figure)) : ($base_price + ($markup_in_percent ? (($base_price * $markup_figure) / 100) : $markup_figure)));
            }
        }
    }

	woocommerce_xlecommerce_submenu_page_callback1();
	echo "\nCategories and Products Imported\n";
?>