<?php
define( 'WP_USE_THEMES', false );

require( $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/wp-load.php' );

define('NODE_PRODUCTS', 									'Products');
define('NODE_PRODUCT_MODEL', 								'ProductModel');
define('NODE_PRODUCT_QUANTITY', 							'ProductQuantity');


// get xle configuration data from database #start	
$xle_data = get_option( 'xle_settings' );
define('FEED_DIRECTORY',									$xle_data['path_xlecommerce_feed_dir'].$xle_data['retailer_token'].'/');
// get xle configuration data from database #ends
	
function updateInventory(){
	
	global $wpdb;
	
	$dir = FEED_DIRECTORY;
	
	$feeds = array();
	
	$wpdb->get_results("truncate table temp_inventory_data", ARRAY_A);
	$wpdb->get_results("insert into temp_inventory_data (sku,post_id) select meta_value, post_id from wp_postmeta where meta_key='_sku'", ARRAY_A);
	
	if (is_dir($dir)) {
		
		if ($dh = opendir($dir)) {
			
			while (($file = readdir($dh)) !== false) {
				
				$pos = strpos($file, 'inventory_feed_');

				if ($pos !== false) {
					
					if (!count($feeds)) {

						$feeds[] = $dir . $file;

					} else {

						$index_to_move = -1;

						for ($i = 0; $i < count($feeds); $i++) {

							if (filemtime($feeds[$i]) > filemtime($dir . $file)) {

								$index_to_move = $i;

								break;
							}
						}

						if ($index_to_move == -1) {

							$feeds[] = $dir . $file;
						} else {

							for ($i = count($feeds) - 1; $i >= $index_to_move; $i--) {

								$feeds[$i + 1] = $feeds[$i];
							}

							$feeds[$index_to_move] = $dir . $file;
						}
					}
				}
			}
			closedir($dh);
		}
	}
	
	foreach ($feeds as $feed) {
		
		echo basename($feed)."\n";
		
		$xml = @simplexml_load_file($feed);
		
		if ($xml) {
			
			foreach ($xml->{NODE_PRODUCTS}->children() as $product) {
							
				$temp_prod_model = htmlspecialchars_decode((string) $product->{NODE_PRODUCT_MODEL});
	
				$temp_prod_qty = (string) $product->{NODE_PRODUCT_QUANTITY};
				
				if ((!empty($temp_prod_model)) && ($temp_prod_qty != '')) {
					$wpdb->get_results("update temp_inventory_data set quantity = '".$temp_prod_qty."',to_update = '1' WHERE sku='".$temp_prod_model."'", ARRAY_A);
				}
			}
			@unlink($feed);
		} else {
			@unlink($feed);
		}
	 }
	 
	 $wpdb->get_results("UPDATE wp_postmeta JOIN temp_inventory_data USING(post_id) SET wp_postmeta.meta_value = temp_inventory_data.quantity WHERE wp_postmeta.meta_key='_stock' and temp_inventory_data.to_update = '1'", ARRAY_A);
	 
	 $wpdb->get_results("UPDATE temp_inventory_data set to_update = '0'", ARRAY_A);
	 
}

	updateInventory();
	echo "\nInventory Imported\n";
?>