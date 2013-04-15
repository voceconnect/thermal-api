wp-json-api
===========

# WP JSON API v0.1
## Overview
### Versions
In order to support migration, the API plugin will support up to 2 versions of the API.  Once a 
version is more than 1 cycle old, it will no longer respond at it's API root unless configured
to do so.

### API Root
The URL root of the API will be the version number of the API prefixed by the site_url and the API_ROOT.  By default the API root is set to 'wp_api'.  For example, the v0.1 will have a root URL of:

	http://example.com/wp_api/v0.1/

## Resource Types
The following resources are available

* [Posts](#Posts)
* [Users](#Users)
* [Taxonomies](#Taxonomies)
* [Terms](#Terms)
* [Rewrite Rules](#Rewrite_Rules)


## Posts
<span id="Posts"></span>A post represents a single item of content.

### Methods
#### List

##### Request
    GET {api root}/posts
##### Parameters

<table>
	<thead>
		<tr>
			<th>Parameter</th>
			<th>Data Type</th>
			<th>Description</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td colspan="3">
			Date Filters
			</td>
		</tr>
		<tr>
			<td>m</td>
			<td>string</td>
			<td>
			A compressed datetime string in the format of 'YmdGis' that represents date/time range to filter posts to (.e.g 2012-01-01 13:59:59 is expressed as 20120101135959).  As most right most parts of the string are left off, the filter becomes less exact.<br />

Examples:

<ul>
<li>'m=2012' Only posts from 2012 will be returned; Equivalent to 'year=2012'</li>
<li>'m=201206'  Only posts from June 2012 will be returned; Equivalent to 'year=2012&monthnum=6'</li>
<li>'m=20120609'  Only posts from June 9th, 2012 will be returned; Equivalent to 'year=2012&monthnum=6&day=9'</li>
</ul>
</td>
		</tr>
		<tr>
			<td>year</td>
			<td>integer</td>
			<td>4 digit year (e.g. 2012)</td>
		</tr>
		<tr>
			<td>monthum</td>
			<td>integer</td>
			<td>Month number (from 1 to 12)</td>
		</tr>
		<tr>
			<td>w</td>
			<td>integer</td>
			<td>Week of the year (from 0 to 53)</td>
		</tr>
		<tr>
			<td>day</td>
			<td>integer</td>
			<td>Day of the month (from 0 to 31)</td>
		</tr>
		<tr>
			<td>hour</td>
			<td>integer</td>
			<td>Hour of the day in 24 hour format (from 0 to 23)</td>
		</tr>
		<tr>
			<td>minute</td>
			<td>integer</td>
			<td>Minute (from 0 to 59)</td>
		</tr>
		<tr>
			<td>second</td>
			<td>integer</td>
			<td>Second (from 0 to 59)</td>
		</tr>
		<tr>
			<td>before* </td>
			<td>string</td>
			<td>A parsable formatted date string.  Unless specified in the format used, 
			the result will be relative to the timezone of the site.</td>
		</tr>
		<tr>
			<td>after* </td>
			<td>string</td>
			<td>A parsable formatted date string.  Unless specified in the format used, 
			the result will be relative to the timezone of the site.</td>
		</tr>
		<tr>
			<td colspan="3">Search Filtering</td>
		</tr>
		<tr>
			<td>s</td>
			<td>string</td>
			<td>
				Search keyword or string, by default this searches against the title and post_content
				By default, the search expression is split into individual terms.
			</td>
		</tr>
		<tr>
			<td>exact</td>
			<td>boolean</td>
			<td>Default false.  If true, the search will omit the wildcard '%' wrapper, making it so 
			that at least one searched fields be an exact match.</td>
		</tr>
		<tr>
			<td>sentence</td>
			<td>boolean</td>
			<td>Default false.  If true, the search string will not be split up into individual 			tokens and the expression will be matched in its entirety.</td>
		</tr>
		<tr>
			<td colspan="3">Taxonomy Filters</td>
		</tr>
		<tr>
			<td>cat**</td>
			<td>array|integer</td>
			<td>The term_id of the category to include.  An array of IDs will also be accepted.  
			Negative ID's can be used to denote exclusion.</td>
		</tr>
		<tr>
			<td>category_name</td>
			<td>string</td>
			<td>The slug of a single category.</td>
		</tr>
		<tr>
			<td>tag</td>
			<td>string</td>
			<td>The slug of a single tag</td>
		</tr>
		<tr>
			<td>taxonomy*</td>
			<td>associative array</td>
			<td>An associative array where the key is the name of the taxonomy and the value is 
			an array of term IDs.  Post that exist in any of the terms will be included in the
			results.  Only public taxonomies will be recognized.</td>
		</tr>
		<tr>
			<td colspan="3">Pagination Filters</td>
		</tr>
		<tr>
			<td>paged</td>
			<td>integer</td>
			<td>A positive integer specifiying the page (or subset of results) to return.  This 				filter will automatically determine the offset to use based on the per_page
				and paged. Using this filter will cause include_found to be true.
			</td>
		</tr>
		<tr>
			<td>per_page*</td>
			<td>integer</td>
			<td>The maximum number of posts to return.  The value must range from 1 to 				MAX_POSTS_PER_PAGE.</td>
		</tr>
		<tr>
			<td>offset</td>
			<td>integer</td>
			<td>The number of posts to skip over before returning the result set.</td>
		</tr>
		<tr>
			<td colspan="3">Ordering Parameters</td>
		</tr>
		<tr>
			<td>orderby**</td>
			<td>array|string</td>
			<td>Sort the results by the given identifier.  Defaults to 'date'.  Supported values are:
				<ul>
					<li>'none' - No ordering will be applied.</li>
					<li>'ID' - The ID of the post.</li>
					<li>'author' - The value of the author ID.</li>
					<li>'title' - The title of the post.</li>
					<li>'name' - The slug/name of the post.</li>
					<li>'date' - (Default) Publish date of the post.</li>
					<li>'modified' - Last modified date of the post.</li>
					<li>'parent'- The ID of the post's parent</li>
					<li>'rand' - A random order, Note: due to caching, the order may not change on every request.</li>
					<li>'comment_count' - The number of comments the post has.</li>
					<li>'menu_order' - The set menu order for the post.</li>
					<li>'post__in' - Preserves the order supplied in the post__in filter.  This is ignored unless the post__in filter is supplied.</li>
				</ul>

Orderby will also accept an array of multiple identifiers.
			</td>
		</tr>
		<tr>
			<td>order</td>
			<td>string</td>
			<td>The order direction.  Options are 'ASC' and 'DESC'.  Default is 'DESC'</td>
		</tr>
		<tr>
			<td colspan="3">General Filters</td>
		</tr>
		<tr>
			<td>author_name</td>
			<td>string</td>
			<td>The user_nicename of the author.</td>
		</tr>
		<tr>
			<td>author**</td>
			<td>integer</td>
			<td>The ID of the authors to include.  An array of IDs will also be accepted.  Negative
			ID's can be used to denote exclusion.</td>
		</tr>
		<tr>
			<td>post__in</td>
			<td>array|integer</td>
			<td>An array of post ID's to include.</td>
		</tr>
		<tr>
			<td>p</td>
			<td>integer</td>
			<td>A single post ID</td>
		</tr>
		<tr>
			<td>name</td>
			<td>string</td>
			<td>The post_name or slug of the post</td>
		</tr>
		<tr>
			<td>pagename</td>
			<td>string</td>
			<td>The post_name or slug of the post.  Will cause the post_type filter to default 
			to 'page'</td>
		</tr>
		<tr>
			<td>attachment</td>
			<td>string</td>
			<td>The post_name or slug of the post.  Will cause the post_type filter to default
			to 'attachment'.</td>
		</tr>
		<tr>
			<td>attachment_id</td>
			<td>integer</td>
			<td>Synonym to 'p' filter.</td>
		</tr>
		<tr>
			<td>subpost</td>
			<td>string</td>
			<td>Synonym for 'attachment' filter. </td>
		</tr>
		<tr>
			<td>subpost_id</td>
			<td>integer</td>
			<td>Synonym for 'attachment_id' filter.</td>
		</tr>
		<tr>
			<td>post_type</td>
			<td>array|string</td>
			<td>The post types to be included in the result set.</td>
		</tr>
		<tr>
			<td>post_parent__in</td>
			<td>array|integer</td>
			<td>Array or single Post ID to pull child posts from.</td>
		</tr>
		<tr>
			<td colspan="3">
				Response Altering Parameters
			</td>
		</tr>
		<tr>
			<td>include_found*</td>
			<td>boolean</td>
			<td>Defaut to false.  When true, the response will include a found rows count.  There is some
			overhead in generating the total count so this should only be turned on when needed.  This is 
			automatically turned on if the 'paged' filter is used.</td>
		</tr>
	</tbody>
</table>



##### Response
	{
		'found': 40, //only provided if include_found == true
		"posts": [
			[Post Object],
			….
		]
	}



#### Single Entity

##### Request
    GET {api root}/posts/{id}

##### Response
	{
		"id" : 1234567,
		"id_str" : "1234567",
		"permalink": "http://example.com/posts/foobar/",
		"parent": 12345,
		"parent_str": "12345"
		"date": "2012-01-01T12:59:59+00:00",
		"modified": "2012-01-01T12:59:59+00:00",
		"status": "publish",
		"comment_status":"open",
		"comment_count": 99,
		"menu_order": 99,
		"title": "Lorem Ipsum Dolor!"
		"name": "loerm-ipsum-dolor"		
		"excerpt_raw": "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec sed lacus
			eros. Integer elementum urna.",
		"excerpt": "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec sed lacus 
			eros. Integer elementum urna.</p>",
		"content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed nec consequat
			nibh. Quisque in consectetur ligula. Praesent pretium massa vitae neque adipiscing vita
			cursus nulla congue. 
			
			<img src=\"http://example.com/wp-content/uploads/2012/03/foobar.jpg\" class=\"alignleft 
			size-medium wp-image-17115\" alt="Lorem ipsum doler set amut.\" />
			
			Cras aliquet ipsum non nisi accumsan tempor sollicitudin lacus interdum
			Donec in enim ut ligula dignissim tempor. Vivamus semper cursus mi, at molestie erat loborti
			ut. Pellentesque non mi vitae augue egestas vulputate et eu massa. Integer et sem orci
			Suspendisse at augue in ipsum convallis semper.

			[gallery ids=\"1,2,3,4\"]
			
			Nullam vitae libero eros, a fringilla erat. Suspendisse potenti. In dictum bibendum libero
			quis facilisis risus malesuada ac. Nulla ullamcorper est ac lectus feugiat scelerisque
			Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas
			Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpi
			egestas. Maecenas et nibh mauris.",
		"content_filtered": "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed nec consequat
			nibh. Quisque in consectetur ligula. Praesent pretium massa vitae neque adipiscing vita
			cursus nulla congue. Cras aliquet ipsum non nisi accumsan tempor sollicitudin lacus interdum
			Donec in enim ut ligula dignissim tempor. Vivamus semper cursus mi, at molestie erat loborti
			ut. Pellentesque non mi vitae augue egestas vulputate et eu massa. Integer et sem orci
			Suspendisse at augue in ipsum convallis semper.</p>

			<div class=\"gallery\">…</div>
			
			<p>Nullam vitae libero eros, a fringilla erat. Suspendisse potenti. In dictum bibendum libero
			quis facilisis risus malesuada ac. Nulla ullamcorper est ac lectus feugiat scelerisque
			Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas
			Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpi
			egestas. Maecenas et nibh mauris.</p>",
		"author": [User Object],
		"mime_type": "",
		"meta": {
		},
		"taxonomies": {
			category: [
				[Term Object]
				….
			],
			post_tag: [
				[Term Object]
				….
			],
			….
			
		},
		"media": {
			"featured_image": {
				"id": 1234,
				"id_str": 1234
				"alt_text": "Lorem ipsum doler set amut.",
				"mime_type": "image/jpg",
				"sizes": [
					{
						"name": "thumbnail",
						"width": 100,
						"height": 80,
						"url": "http://example.com/wp-content/uploads/2012/02/foobar-100x80.jpg"
					},
					….
				]
			},
			"images": [
				{
					"id": 1234,
					"id_str": 1234
					"alt_text": "Lorem ipsum doler set amut.",
					"mime_type": "image/jpg",
					"sizes": [
						{
							"name": "thumbnail",
							"width": 100,
							"height": 80,
							"url": "http://example.com/wp-content/uploads/2012/02/foobar-100x80.jpg"
						},
						….
					]
				}
				….
			]
			
		}
	}




##Users
<span id="Users"></span>A User represents a single author or user on the site.
### Methods
#### List

##### Request
    GET {api root}/users
##### Parameters

<table>
	<thead>
		<tr>
			<th>Parameter</th>
			<th>Data Type</th>
			<th>Description</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td colspan="3">Pagination Filters</td>
		</tr>
		<tr>
			<td>paged</td>
			<td>integer</td>
			<td>A positive integer specifiying the page (or subset of results) to return.  This 				filter will automatically determine the offset to use based on the per_page
				and paged. Using this filter will cause include_found to be true.
			</td>
		</tr>
		<tr>
			<td>per_page*</td>
			<td>integer</td>
			<td>The maximum number of posts to return.  The value must range from 1 to 				MAX_USERS_PER_PAGE.</td>
		</tr>
		<tr>
			<td>offset</td>
			<td>integer</td>
			<td>The number of posts to skip over before returning the result set.</td>
		</tr>
		<tr>
			<td colspan="3">Ordering Parameters</td>
		</tr>
		<tr>
			<td>orderby**</td>
			<td>string</td>
			<td>Sort the results by the given identifier.  Defaults to 'display_name'.  Supported values are:
				<ul>
					<li>'display_name' - Ordered by the display name of the user.</li>
					<li>'nicename' - The slug/nicename of the user.</li>
					<li>'post_count' - The number of posts the user has.</li>
				</ul>
			</td>
		</tr>
		<tr>
			<td>order</td>
			<td>string</td>
			<td>The order direction.  Options are 'ASC' and 'DESC'.  Default is 'DESC'</td>
		</tr>
		<tr>
			<td colspan="3">General Filters</td>
		</tr>
		<tr>
			<td>in</td>
			<td>array|integer</td>
			<td>An array of user ID's to include.</td>
		</tr>
		<tr>
			<td colspan="3">
				Response Altering Parameters
			</td>
		</tr>
		<tr>
			<td>include_found*</td>
			<td>boolean</td>
			<td>Defaut to false.  When true, the response will include a found rows count.  There is some
			overhead in generating the total count so this should only be turned on when needed.  This is 
			automatically turned on if the 'paged' filter is used.</td>
		</tr>
	</tbody>
</table>

##### Response
	{
		'found': 40, //only provided if include_found == true
		"users": [
			[User Object],
			….
		]
	}



#### Single Entity

##### Request
    GET {api root}/users/{id}

##### Response
	{
		"id" : 1234567,
		"id_str" : "1234567",
		"user_nicename": "john-doe",
		"display_name":"John Doe",
		"posts_url": "http://example.com/author/john-doe/",
		"user_url": "http://vocecomm.com",
		"avatar": [
			{
				"url":"http://1.gravatar.com/avatar/7a10459e7210f3bbaf2a75351255d9a3?s=64",
				"width":64,
				"height":64
			},
			….
		],
		"meta":{
		}
	}

##Taxonomies
<span id="Taxonomies"></span>Taxonomies represent the different types of classifications of content.  Only public taxonomies can be returned via the API.
### Methods
#### List

##### Request
    GET {api root}/taxonomies
##### Parameters

<table>
	<thead>
		<tr>
			<th>Parameter</th>
			<th>Data Type</th>
			<th>Description</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>in</td>
			<td>array|string</td>
			<td>An array of taxonomy names to include.</td>
		</tr>
		<tr>
			<td>post_type</td>
			<td>array|string</td>
			<td>An array of post_types to include taxonomies from.  Results will include any taxonomies with at least 1 of the given post_types included.</td>
		</tr>
	</tbody>
</table>

##### Response
	{
		"taxonomies": [
			[Taxonomy Object]
			….
		]
	}



#### Single Entity

##### Request
    GET {api root}/taxonomies/{name}

##### Response
	{
		"name": "category",
		"post_types": [
			"post",
			"attachment",
			….
		],
		"hierarchical": true,
		"query_var":"category",
		"labels": {
			"name": "Categories",
			"singular_name": "Category"
		},
		"meta":{
		}
	}
	

##Terms
<span id="Terms"></span>Terms are individual classifications within a taxonomy.
### Methods
#### List

##### Request
    GET {api root}/taxonomies/{name}/terms
##### Parameters

<table>
	<thead>
		<tr>
			<th>Parameter</th>
			<th>Data Type</th>
			<th>Description</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td colspan="3">Pagination Filters</td>
		</tr>
		<tr>
			<td>paged</td>
			<td>integer</td>
			<td>A positive integer specifiying the page (or subset of results) to return.  This 				filter will automatically determine the offset to use based on the per_page
				and paged. Using this filter will cause include_found to be true.
			</td>
		</tr>
		<tr>
			<td>per_page*</td>
			<td>integer</td>
			<td>The maximum number of posts to return.  The value must range from 1 to 				MAX_TERMS_PER_PAGE.</td>
		</tr>
		<tr>
			<td>offset</td>
			<td>integer</td>
			<td>The number of posts to skip over before returning the result set.</td>
		</tr>
		<tr>
			<td colspan="3">Ordering Parameters</td>
		</tr>
		<tr>
			<td>orderby**</td>
			<td>string</td>
			<td>Sort the results by the given identifier.  Defaults to 'name'.  Supported values are:
				<ul>
					<li>'name' - The user readable name of the term.</li>
					<li>'slug' - The slug of the term.</li>
					<li>'count' - The number of posts the term is connected to.</li>
				</ul>
			</td>
		</tr>
		<tr>
			<td>order</td>
			<td>string</td>
			<td>The order direction.  Options are 'ASC' and 'DESC'.  Default is 'DESC'</td>
		</tr>
		<tr>
			<td colspan="3">General Filters</td>
		</tr>
		<tr>
			<td>in</td>
			<td>array|integer</td>
			<td>An array of term ID's to include.</td>
		</tr>
		<tr>
			<td>slug_in</td>
			<td>array|string</td>
			<td>An array of term slugs to include.</td>
		</tr>
		<tr>
			<td>parent_in</td>
			<td>array|id</td>
			<td>Include the children of the provided term ID's.</td>
		</tr>
		<tr>
			<td>exclude_empty</td>
			<td>boolean</td>
			<td>If false, only terms with attached posts will be returned.  Default is true.</td>
		</tr>
		<tr>
			<td colspan="3">
				Response Altering Parameters
			</td>
		</tr>
		<tr>
			<td>include_found*</td>
			<td>boolean</td>
			<td>Defaut to false.  When true, the response will include a found rows count.  There is some
			overhead in generating the total count so this should only be turned on when needed.  This is 
			automatically turned on if the 'paged' filter is used.</td>
		</tr>
	</tbody>
</table>


##### Response
	{
		"found": 25,  //only provided if include_found == true
		"terms": [
			[Term Object]
			….
		]
	}



#### Single Entity

##### Request
    GET {api root}/taxonomies/{name}/terms/{term_id}

##### Response
	{
		"term_id": 123456,
		"term_id_str": "123456",
		"term_taxonomy_id": 123456789,
		"term_taxonomy_id_str": "123456789",
		"parent": 1234567,
		"parent_str": "1234567",
		"name": "Local News",
		"slug": "local-news",
		"taxonomy": "category",
		"description": "News reports from around Polk County",
		"post_count": 25,
		"post_count_padded": 33, //padded post count includes posts from child terms.  This only differs from post_count if the taxonomy is hierarchical
		"meta":{
		}
	}


##Rewrite Rules
<span id="Rewrite_Rules"></span>Rewrite Rules can be used to convert internal links in content into API requests.
### Methods
#### List

##### Request
    GET {api root}/rewrite_rules

##### Response
	{
		"base_url": "http://example.com/",
		"rewrite_rules": [
			{
				regex: "category/(.+?)/?$",
				"query_expression": "category_name=$1"
			}
			….
		]
	}


##Notes
	*    - This is a non-standard public_query_var.
	**   - By default WordPress asks for this query_var to be passed in as a comma 
			separated list.  This is replaced by an array in the API to better support 
			x-www-form-urlencoded submissions.
