<?php
//Load jQuery
wp_enqueue_script('jquery');

//The Javascript
function add_images_size_footer(){ ?>
<script>
	function ajax_fetch_images_size(){
		jQuery.ajax({
			  url: ajaxurl,
			  data: {
				  'action':'fetch_images_size',
				  'query' : query
			  },
			  success:function(data) {
				  var json = JSON.parse(data)
				  console.log(json)
				  jQuery('.images_numbers').html(json._embedded.values[0].count)

			  },
			  error: function(errorThrown){
				  window.alert(errorThrown);
			  }
		  });
	}

	jQuery(document).ready(function($) {
	ajax_fetch_images_size();
	
	});
</script>
<?php }
add_action('wp_footer', 'add_images_size_footer');

function fetch_images_size() {
    if ( isset($_REQUEST) ) {
        $query = $_REQUEST['query'];
		$url_images = "https://cetapsrepository.letras.up.pt/server/api/discover/facets/itemtype?scope=04efd651-8dba-4c42-a648-7e3cb24e6168";

		$arguments = array(
			'method' => 'GET',
		);
        
		$images = wp_remote_retrieve_body(wp_remote_get($url_images, $arguments));
		$response = $images;

		if( is_wp_error($response) ) {
			$error_message = $response->get_error_message();
			return "Something went wrong: $error_message";
		}

		echo($response); 
    }
   die();
}
add_action( 'wp_ajax_fetch_images_size', 'fetch_images_size' );
add_action( 'wp_ajax_nopriv_fetch_images_size', 'fetch_images_size' );