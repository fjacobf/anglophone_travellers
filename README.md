
# Documentation for Anglophone Travellers website

Website: [https://atp.fcsh.unl.pt/](https://atp.fcsh.unl.pt/)

API: [https://cetapsrepository.letras.up.pt/server/api](https://cetapsrepository.letras.up.pt/server/api)

You can use the [HAL Browser](https://cetapsrepository.letras.up.pt/server) to get a visual interface for the API and test queries.

# 1. Plugins

The only plugin used was ‘Code Snippets’ to add extra code to the website.

![](https://lh7-rt.googleusercontent.com/docsz/AD_4nXfJ5LWvcdVgcdnXYZe1TSCIA4apZzUpW-CG86S7Hr6uiMuur3pMd59qqEVQ1gKS3FHIiHSkAbon1uOay2hW_ESv4-e8p6AttCy3lgD2qiG_5qqPqI-IuTx8l1PsK5cCmDCZbzvdKx71XK0E05nJfKz6GO50?key=DCPWoPsXHp98fOpQP2N_hw)

# 2. Snippets
Snippets are pieces of code that we can add to wordpress and use it to influence our website. All the Snippets built for Anglophone Travellers are in PHP.

## 2.1 Getting authors size
This snippet gets the total number of authors in the repository. It sends a request to the endpoint:
	
	discover/browses/author/entries?scope=04efd651-8dba-4c42-a648-7e3cb24e6168

This returns all the authors who are present in the selected collection (scope query). From there we access the page part where we can get the totalElements number.

## 2.2 Getting images size

This snippet gets the total number of images in the database. It sends a request to the endpoint:
```
/discover/facets/itemtype?scope=04efd651-8dba-4c42-a648-7e3cb24e6168
```
This returns the itemtype facets, from that we access `_embedded.values[0].count` to get the total number of elements in the images.
## 2.3 Getting works size

This snippet gets the total number of works, of any type, present in the database. It sends a request to this endpoint:
```
/discover/search/objects?scope=04efd651-8dba-4c42-a648-7e3cb24e6168
```
This is the same endpoint we use to search the elements. From that we go to `_embedded.searchResult.page.totalElements`, that gets the total number of elements.

## 2.4 Publications Search Bar
This is the main snippet to filter and create the whole search bar. It is the most complicated one so to have a full understanding of how it works it is necessary to go through the whole document. Basically, it gets the works by sending a request to:

	/discover/search/objects?sort=dc.date.accessioned,DESC&scope=04efd651-8dba-4c42-a648-7e3cb24e6168&size=10&embed=thumbnail  

# 3. WordPress

WordPress is a ctm build in PHP. To create code that interacts with a WordPress website we need first to **call some specific WP methods** to set it the way that we need.

  

A method to **enable Jquery**:
```PHP
//Load jQuery
wp_enqueue_script('jquery');
```

We need a method to **route all AJAX request** to wordpress:

```php
//Define AJAX URL  
function  myplugin_ajaxurl() {  
  
echo  '<script type="text/javascript">  
var ajaxurl = "' . admin_url('admin-ajax.php') . '";  
</script>';  
}  
add_action('wp_head', 'myplugin_ajaxurl');
```
This method sets the variable `ajaxurl` that will be important later. It **only needs to be called once**, so it is only used on *getting authors size* snippet.

After defining a PHP function we need to call a method that **adds the function**. In the example below, we have a php function that opens a `<script>` tag to add javascript code (I know, it sounds kinda confusing) . After the whole Javascript code is written, another PHP tag is open so the method `add_action` can be called:

```php
<?php
function add_this_script_footer() { ?>
<script>  
	var filters = {authors: "", dateIssued: ""};  
	
	[...]
	  
</script>

<?php }  
add_action('wp_footer', 'add_this_script_footer');
```

# 4. JQuery

Now that we have all set up, we need to select the HTML elements in the page to get information and change them dynamically. To do that we will use Javascript. To select the elements we can't use the Javascript query selector so we will use Jquery instead.

#### To select a class with jQuery we do:
```javascript
jQuery(".publications-list")
```
or
```javascript
$(".publications-list")
```
The first method is the most used throughout the code as the second method only works on the function that loads the document. To learn more, see the JQuery [documentation](https://api.jquery.com/).

#### To add html to a selected element we do:
```javascript
jQuery(`.publication-list`).html(resultHTML);
``` 
  

# 5. Ajax

  

To make http requests and link actions from Javascript to PHP we use AJAX.

#### 5.1 Javascript send a request:

  ```javascript
function ajax_fetch_search_result(query, url, filters) {
		jQuery.ajax({
			  url: ajaxurl,
			  data: {
				  'action':'fetch_search_result',
				  'query' : {query: query, filters: filters, url: url}
			  },
			  success:function(data) {
				  var json = JSON.parse(data)
				  build_objects(json);
			  },
			  error: function(errorThrown){
				  window.alert(errorThrown);
			  }
		  });
	} //Gets what was written in the search bar, the url with pagination and the filters and calls build_objects with the http request result  
```

- In this example, we use **Jquery** to make the AJAX request. The url is the variable `ajaxurl` we set before when routing all the requests to wordpress.
- The data parameter includes the `action` we are connecting to. This is the PHP function we are going to send the request to and recieve information from (in this case the function `fetch_search_result`).
- The `query` parameter is the information we are sending. This information has to be a JSON.
- If the request in a **success** them the response will be included in a variable called `data` . This variable is returned in a string, the code them parses the information in JSON format.
- If the request is unsuccessful and has an **error** the code will "throw" the error within a window alert.

#### 5.2 PHP receives this request:
```php
function fetch_search_result() {
    if ( isset($_REQUEST) ) {
        $query = $_REQUEST['query']['query'];
        [...]
```
As we can see in the example, we verify if the request is set and then we access the query parameter to get the information we sent.
#### 5.3 PHP makes a request to the API:
```php
[...]
$arguments = array(
			'method' => 'GET',
		);
        
		$obras = wp_remote_retrieve_body(wp_remote_get($url_obras, $arguments));
		$response = $obras;

		if( is_wp_error($response) ) {
			$error_message = $response->get_error_message();
			return "Something went wrong: $error_message";
		}
[...]
```
- After the code modifies the url to bring the necessary information, it stores in `$url_obras`. 
- Then, the necessary arguments for the request (such as the method) are stored in `$arguments`. 
- The request is then made with the method `wp_remote_get()` which has two arguments: the url to be accessed and the arguments.
- From the response of this method we only want the body so we use the method `wp_remote_retrieve_body()`. In this case we wrap one function in the other so we can save some lines of code.
- Finally, the `is_wp_error($response)` tests to see if there was any error in the request.

#### 5.4 PHP returns the recieved AJAX request:
```php
[...]
		echo($response); 
    }
   die(); //you always die in the end of a function (dramatic, don´t you think?)
}
add_action( 'wp_ajax_fetch_search_result', 'fetch_search_result' );
add_action( 'wp_ajax_nopriv_fetch_search_result', 'fetch_search_result' );
```
- To answer the recieved request you simply `echo($response)`.
- Before closing the function you call the method `die();`.
- After the function you need to call the method `add_action()` twice. The only thing that changes is the first argument. In one you put `wp_ajax_` before the name of the function. In the other you put `wp_ajax_nopriv_`. The second argument is always the name of the function.

# 6. The code
After stablishing the technologies used and giving a brief explanation with examples on how they work, we can take a look in the code itself and see how it was implemented. The **first 3 snippets** (getting authors size, works size and image size) are pretty straightfoward, they send simple ajax requests with the already stablished endpoints and get the information. The **last snippet** (Publications Search Bar) gets more complex because of the filters, search bar and pagination so we are going to expand on this one.

## 6.1 Initial Steps
The first few lines of code set up what was already described in section **3 (Wordpress)**, so we are skipping that in order to not repeat it. 

## 6.2 The Javascript
```php
<?php
function add_this_script_footer(){ ?>
<script>
[...]
</script>
<?php }
add_action('wp_footer', 'add_this_script_footer');
```
Ironically, the Javascript code is fully written inside this php function called `add_this_script_footer()`. This function opens a `<script>` tag that contains all the Javascript we are going to write. After that, as instructed in **3 (Wordpress)**, we add the function to wordpress footer (wp_footer).

### 6.2.1 Initial Variables
```javascript
//The Javascript
function add_this_script_footer(){ ?>
<script>

	var filters = {authors: "", dateIssued: ""};
	var query = '';
	var urls = {
		url_obras: 'https://cetapsrepository.letras.up.pt/server/api/discover/search/objects?sort=dc.date.accessioned,DESC&scope=04efd651-8dba-4c42-a648-7e3cb24e6168&size=10&embed=thumbnail',
		url_authors: 'https://cetapsrepository.letras.up.pt/server/api/discover/facets/author?scope=04efd651-8dba-4c42-a648-7e3cb24e6168&size=5',
		url_subjects: 'https://cetapsrepository.letras.up.pt/server/api/discover/facets/subject?scope=04efd651-8dba-4c42-a648-7e3cb24e6168&size=5',
		url_has_content_in_original_bundle: 'https://cetapsrepository.letras.up.pt/server/api/discover/facets/has_content_in_original_bundle?scope=04efd651-8dba-4c42-a648-7e3cb24e6168&size=5',
		url_dateIssued: 'https://cetapsrepository.letras.up.pt/server/api/discover/facets/dateIssued?scope=04efd651-8dba-4c42-a648-7e3cb24e6168&size=5',
		url_itemtype: 'https://cetapsrepository.letras.up.pt/server/api/discover/facets/itemtype?scope=04efd651-8dba-4c42-a648-7e3cb24e6168&size=5'
		
	};
	
	var filter_buttons = {
		authors: [],
		subjects: [],
		has_content_in_original_bundle: [],
		itemtype: []
	};
```
Those are the initial variables necessary for our code.
- `var filters` stores the filters that were added to the search.
- `var query` is the variable that stores what was typed in the search bar. 
- `var urls` stores the search url and the facets urls used to filter the search.
- `var filter_buttons`stores the rendered buttons in the filters, helping with the filters pagination.

### 6.2.2 jQuery(document).ready()
```javascript
jQuery(document).ready(function($) {
	publications_search_overwrite();
	ajax_fetch_search_result(query, urls.url_obras);
	ajax_fetch_filters(urls);
	[...]
});
```
This is the main function that calls all the other functions. The code inside `jQuery(document).ready()` will only run once the page Document Object Model (DOM) is ready for JavaScript code to execute.
You can see that inside we call 3 functions that will be explained later. After that, still inside the document ready function, there are two actions for when buttons are clicked:
#### Search button
```javascript
$( "#search_button" ).click(function() {
	  query = $('#search_bar').val();
	  var start_date = jQuery('#start_date').val()
	  var end_date = jQuery('#end_date').val()
	  filters = {};
	  for (key in filter_buttons){
		  filter_buttons[key] = [];
	  }
	  if(start_date!="" && end_date!=""){
	  	filters['dateIssued'] = `%5B${start_date} TO ${end_date}%5D`
	  }
      ajax_fetch_search_result(query, urls.url_obras, filters);
	  ajax_fetch_filters(urls);
    });
```
This function is called when the **Search** button is clicked. Then, it proceeds with the following actions:
- Gets the value that was typed on the **search bar**. 
- Gets the **start** and **end date** typed as well.
- Resets the `filters` and `filter_buttons` variables to empty objects. In order to keep the structure of the object, the filter_buttons variable has to be reset with a loop through the keys.
- Tests if the dates were both typed and if so populates a `dateIssued` inside the filters object with the right formatting to add to the url.
- Calls two methods that were also called in the begging of the document ready function: `ajax_fetch_search_result` and `ajax_fetch_filters`.

#### Remove Filters button
```javascript
$("#remove_filters").click(function(){
		filters = {};
		for (key in filter_buttons){
		  filter_buttons[key] = [];
	  	}
		ajax_fetch_search_result(query, urls.url_obras, filters);
	  	ajax_fetch_filters(urls);
	})
```
This function is called when the **Remove Filters** button is clicked. Then, it proceeds with the following actions:
- Resets the `filters` and `filter_buttons` variables to empty objects.
- Calls `ajax_fetch_search_result` and `ajax_fetch_filters`.

### 6.2.3 publications_search_overwrite()
```javascript
function publications_search_overwrite() {
		var HTML = `
		<div style="width:30%;" class="sidebar">
			<div>
			//[...]
			<div style="display:flex; align-items:center; justify-content:center; 	flex-wrap: wrap;" class="publications-pagination"> Pagination </div>"
		</div>`
		
	jQuery('.publications-search').html(HTML);
	}
```
This function overwrite the HTML that was is in the wordpress page. As I didn´t have an easy way to edit the HTML written by the designer, I simply rewrote it and made a few modifications to better fit the css and the elements I needed to add.

### 6.2.4 ajax_fetch_search_result()
```javascript
function ajax_fetch_search_result(query, url, filters) {
	jQuery.ajax({
		  url: ajaxurl,
		  data: {
			  'action':'fetch_search_result',
			  'query' : {query: query, filters: filters, url: url}
		  },
		  success:function(data) {
			  var json = JSON.parse(data)
			  build_objects(json);
		  },
		  error: function(errorThrown){
			  window.alert(errorThrown);
		  }
	  });
} //Gets what was written in the search bar, the url with pagination and the filters and calls build_objects with the http request result
```
This function gets three parameters: **query**, **url** and **filters**. Query and filters are the same as the initial variables. The url passed is `urls.url_obras`. The function sends an ajax request with the parameters to `fetch_search_result`, the PHP method that will send the API request. It the parses the returned `data` value to a json object and calls the function `build_objects(json)`.
### 6.2.5 build_objects()
```javascript
function build_objects(json) {
		var objects = json._embedded.searchResult._embedded.objects;
		var current_page = json._embedded.searchResult.page.number;
		var totalpages = json._embedded.searchResult.page.totalPages;
		var totalElements = json._embedded.searchResult.page.totalElements;
		var url = json._links.self.href;
		//[...]
		jQuery(".publications-list").html(resultHTML);
		build_pagination(current_page, totalpages,url,filters);

  } //builds the html object with the works
```
This function gets the `objects` from the resulting json as well as some pagination information. It then iterates through the objects and creates the HTML in the `resultHTML` variable. It adds the resultHTML to the respective element in the wordpress page and calls the `build_pagination()` method with the page informations.

### 6.2.6 build_pagination()
```javascript
function build_pagination (current_page, totalpages,url,filters) {
		var paginationHTML = "";
		if(totalpages>1) {
			paginationHTML = '';
		//[...]
		jQuery(".publications-pagination").html(paginationHTML);
		
		//add the click event to change the pages
		jQuery(".pages").click(function() {
			page = jQuery(this).attr("value");
			url += `&size=10&page=${page}&embed=thumbnail`;
			ajax_fetch_search_result("",url,filters);
		})	
	}
```
This function recieves pages information as parameters, as well as the current url and the filters. It then build the necessary html and adds to the respective `.publications.pagination` element in the page. Finally, it sets the click action to the buttons created so they can change the page. It does that by basically calling the `ajax_fetch_search_result()` function again but with an alteration in the url page query to match the pagination.

### 6.2.7 ajax_fetch_filters()
```javascript
function ajax_fetch_filters(urls) {
jQuery.ajax({
         url: ajaxurl, // Since WP 2.8 ajaxurl is always defined and points to admin-ajax.php
         data: {
             'action':'fetch_filters', // This is our PHP function below
             'query' : {query: query, urls: urls}
         },
         success:function(data) {
		  var json = JSON.parse(data)
		  for (key in json) {
			  build_radio_button_filters(json[key], key);
		  }
		  

         },
         error: function(errorThrown){
             window.alert(errorThrown);
         }
     });
}
```
Similar to ajax_fetch_search_result(), it sends an ajax request to the `fetch_filters` PHP method. It then gets the data and parses in an json format. After that it iterate through the object and calls the function `build_radio_button_filters()` in each iteraction. This loop is necessary to build the buttons for each filter type.

### 6.2.8 build_radio_button_filters()
```javascript
function build_radio_button_filters(json, key) {
		filter_buttons[key] = filter_buttons[key].concat(json._embedded.values);
		var next;
		if(json._links.next){
			next = json._links.next.href;
		}
		//[...]
```
This is the most complex function in the code so we are going to break it in parts. In the begging we have the `filter_buttons[key]` variable being updated with the values in the json. That is necessary because when we click in <u>More</u>, those filters need to be re-rendered to add the other.

For example, if we have 5 authors in the Authors filter section and the <u>More</u> button. When the user clicks in the button, the new 5 authors are added to `filter_buttons['authors']` and then the 10 total author in the variable are rendered in the screen.

```javascript
//[...]
switch (key){
	case 'has_content_in_original_bundle':
		name = 'Has Files';
		break;
	case 'authors':
		name = 'Authors';
		break;
	case 'subjects':
		name = 'Subjects';
		break;
	case 'itemtype':
		name = 'Item Type';
		break;
	default:
		break;
}
//[...]
```
This switch case works to change what will be the name of the section added in the page according to the `key` recieved. 

**After that, the same process of iteration through `filter_buttons[key]`, building the HTML and adding it in the respective element in the page is repeated.**

```javascript
//[...]
jQuery(`input[type='radio'][name='${key}']`)
	.change(function(){
		if( jQuery(this).is(":checked") ){
			var val = jQuery(this).val();
			filters[key] = val;
			ajax_fetch_search_result(query, urls.url_obras, filters);
		}
	});
//[...]
```
This part adds the function called when a filter is selected. It gets the value of the radio button in the correspondent filter, updates the `filters[key]` variable with this values and calls `ajax_fetch_search_result()` again.

```javascript
//[...]
if(next){
	jQuery(`#see_more_${key}`).click(function(){
		var aux_obj = {};
		aux_obj[`url_${key}`] = next;
		ajax_fetch_filters(aux_obj);
	})
}
//[...]
```
Here, we verify if there is still more filters that were not rendered and atribute the function of the <u>More</u> button. This simply calls the `ajax_fetch_filters()` function with the other url of the next filters.

```javascript
	jQuery(`.label-wrap.${key}`).click(function(){
		jQuery(`.filter-options.${key}`).toggleClass("hide");
		jQuery(`.label-wrap.${key} i`).toggleClass(" dropdownArrow");
		jQuery(`.label-wrap.${key} i`).toggleClass("dropdownArrowRotated");
	 })
}
```
Finally, we use this jQuery method to create the 'drawer' effect we see in the filters.

## 6.3 The PHP

### 6.3.1 fetch_search_results()

```php
function fetch_search_result() {
    if ( isset($_REQUEST) ) {
        $query = $_REQUEST['query']['query'];
		$filters = $_REQUEST['query']['filters'];
		$url_obras = $_REQUEST['query']['url'];
		
		if($query != ''){
			$url_obras .= '&query='. $query;
		}

		if($filters['itemtype'] != ''){
			$url_obras .= '&f.itemtype=' . $filters['itemtype']. ',equals';
		}
		
		if($filters['authors'] != ''){
			$url_obras .= '&f.author=' . $filters['authors'] . ',equals';
		}
		
		if($filters['subjects'] != ''){
			$url_obras .= '&f.subject=' . $filters['subjects'] . ',equals';
		}
		
		
		if($filters['has_content_in_original_bundle'] != ''){
			$url_obras .= '&f.has_content_in_original_bundle=' . $filters['has_content_in_original_bundle'] . ',equals';
		}
		
		if($filters['dateIssued'] != ''){
			$url_obras .= '&f.dateIssued=' . $filters['dateIssued'] . ',equals';
		}
[...]
```
This PHP function gets the 3 variables passed by the `ajax_fetch_search_result()` and separate them in `$query`, `$filters` and `$url_obras`. It then goes through a sequence of various "ifs".

The first "if" tests if the `$query` variable has any value whithin it. If it has, it adds the `query=` parameters to the url that will be called in the API with the value in the variable.

The other "ifs" work to add the facets that works as filters in the url.

In the end, the function performs a call to the API with the `$url_obras` built. The API call works the same way as showed in section **5.3 PHP makes a request to the API**. The result is stored in `$response` that is returned.

### 6.3.2 fetch_filter_results()
```javascript
function fetch_filters() {
    if ( isset($_REQUEST) ) {
        $query = $_REQUEST['query']['query'];
		$url_authors = $_REQUEST['query']['urls']['url_authors'];
		$url_subjects = $_REQUEST['query']['urls']['url_subjects'];
		$url_itemtype = $_REQUEST['query']['urls']['url_itemtype'];
		$url_has_content_in_original_bundle = $_REQUEST['query']['urls']['url_has_content_in_original_bundle'];
		if($query != ''){
			$url_authors = $url_authors . '&query=' . $query;
			$url_subjects = $url_subjects . '&query=' . $query;
			$url_itemtype = $url_itemtype . '&query=' . $query;
			$url_has_content_in_original_bundle = $url_has_content_in_original_bundle . '&query=' . $query;
		}
//[...]
```
This function gets the query and all the urls and stores the in variables. It then modifies all the url variables to add the query (if it exists) as a parameter.

After that, it repeats the same process already expained in section 5.3 to fetch all the facets with their respective urls.
Finally, it builds the `$response` object with all the responses from the API calls and returns it.

# The API
![](https://lh7-rt.googleusercontent.com/docsz/AD_4nXcWDlaMAPz-YE9YiOPF86FEhShB_5-IOebPy7eUcRImc5jdYwb_wJQoMzyjyXqx2rpcYKiOC8K9YXZqjLZ5AopWNIft5Vr8WT2qqWeOl6q3Mc6kgNCZwHix9DbJuTXh_5sPFW-3OsT-YZiETIz9LxAlzTI8?key=DCPWoPsXHp98fOpQP2N_hw)

As we can see, the Cetaps repository has many collections with works inside of it. Each work is composed of a variety of metadata. A way to organize those works is using facets to filter those works by authors for example.
- In the API, we get the works with the url shown in section **2.4 Publications Search Bar**.
- To get the facets we use urls like the one in section **2.2 Getting Images Size**. The only alteration necessary is to change `/itemtype/` in the url to the facet of your choice.

### Parameters
- To get only the collection we need (in this case ATP collection) we need to set the `scope=` parameter.
- To apply facets in order to filter the works, we use parameters like `&f.author=`.
- To search for something whitin all the metadata of the works, we can use the parameter`&query=` .
- To sort by descendant dates we can use the parameter `sort=dc.date.accessioned,DESC`
- To control the pagination, ensuring the size of the page and which page we access we can use the parameters `&size=10&page=1`.
- To get the images associated with the works we can use the parameter `&embed=thumbnail`.

To give a complete example, if the user puts in the search bar the word "brasil" and filters by the author "Baillie, Marianne", the url used to communicate with the API will be something like this:
	`/discover/search/objects?scope=04efd651-8dba-4c42-a648-7e3cb24e6168&query='brasil'&f.author='Baillie, Marianne'`

by Felipe J. J. Ferreira | [https://github.com/fojacob](https://github.com/fojacob)
