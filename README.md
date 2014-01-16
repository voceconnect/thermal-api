# Thermal API 

[![Build Status](https://travis-ci.org/voceconnect/thermal-api.png?branch=master)](https://travis-ci.org/voceconnect/thermal-api)

Current API version: v1

## Overview
Thermal is the WordPress plugin that gives you the access and control to your content
from outside of the WordPress admin.  Thermal supports client-based decisions that, 
when combined with a responsive design framework, allows for a truly responsive 
application leveraging a WordPress content source.

### Versions
In order to support migration, the API plugin will support up to 2 versions of the API.  Once a 
version is more than 1 cycle old, it will no longer respond at it's API root unless configured
to do so.

### API Root
The URL root of the API will be the version number of the API prefixed by your
WordPress site URL and the `Voce\Thermal\API_ROOT` constant.  By default this
is set to `wp_api` but can be overridden by setting it in `wp-config.php`.

The current API version is v1 so the default URL root is:

	http://example.com/wp_api/v1/

## Resource Types
The following resources are available

* [Posts](#posts)
* [Users](#users)
* [Taxonomies](#taxonomies)
* [Terms](#terms)
* [Rewrite Rules](#rewrite_rules)
* [Media Items](#media_items)
* [Comments](#comments)


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
			<td class="shade" colspan="3">
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
			<td>before</td>
			<td>string</td>
			<td>A parsable formatted date string.  Unless specified in the format used, 
			the result will be relative to the timezone of the site.</td>
		</tr>
		<tr>
			<td>after</td>
			<td>string</td>
			<td>A parsable formatted date string.  Unless specified in the format used, 
			the result will be relative to the timezone of the site.</td>
		</tr>
		<tr>
			<td class="shade" colspan="3">Search Filtering</td>
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
			<td class="shade" colspan="3">Taxonomy Filters</td>
		</tr>
		<tr>
			<td>cat</td>
			<td>array|integer</td>
			<td>The term_id of the category to include.  An array of IDs will also be accepted.</td>
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
			<td>taxonomy</td>
			<td>associative array</td>
			<td>An associative array where the key is the name of the taxonomy and the value is 
			an array of term IDs.  Post that exist in any of the terms will be included in the
			results.  Only public taxonomies will be recognized.</td>
		</tr>
		<tr>
			<td class="shade" colspan="3">Pagination Filters</td>
		</tr>
		<tr>
			<td>paged</td>
			<td>integer</td>
			<td>A positive integer specifiying the page (or subset of results) to return.  This 				filter will automatically determine the offset to use based on the per_page
				and paged. Using this filter will cause include_found to be true.
			</td>
		</tr>
		<tr>
			<td>per_page</td>
			<td>integer</td>
			<td>The maximum number of posts to return.  The value must range from 1 to 				MAX_POSTS_PER_PAGE.</td>
		</tr>
		<tr>
			<td>offset</td>
			<td>integer</td>
			<td>The number of posts to skip over before returning the result set.</td>
		</tr>
		<tr>
			<td class="shade" colspan="3">Ordering Parameters</td>
		</tr>
		<tr>
			<td>orderby</td>
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
			<td class="shade" colspan="3">General Filters</td>
		</tr>
		<tr>
			<td>author_name</td>
			<td>string</td>
			<td>The user_nicename of the author.</td>
		</tr>
		<tr>
			<td>author</td>
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
			<td>post_status</td>
			<td>array|string</td>
			<td>Default to 'publish'.  The post statii to include in the result set.  Note that the statii
			passed in are run through capability checks for the current user.</td>
		</tr>
		<tr>
			<td>post_parent__in</td>
			<td>array|integer</td>
			<td>Array or single Post ID to pull child posts from.</td>
		</tr>
		<tr>
			<td class="shade" colspan="3">
				Response Altering Parameters
			</td>
		</tr>
		<tr>
			<td>include_found</td>
			<td>boolean</td>
			<td>Defaut to false.  When true, the response will include a found rows count.  There is some
			overhead in generating the total count so this should only be turned on when needed.  This is 
			automatically turned on if the 'paged' filter is used.</td>
		</tr>
		<tr>
			<td>callback</td>
			<td>string</td>
			<td>When set, the response will be wrapped in a JSONP callback.</td>
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
			<td class="shade" colspan="3">
				Response Altering Parameters
			</td>
		</tr>
		<tr>
			<td>callback</td>
			<td>string</td>
			<td>When set, the response will be wrapped in a JSONP callback.</td>
		</tr>
	</tbody>
</table>

##### Post JSON Schema
	{
        "title": "Post Object",
        "description": "A representation of a single post object",
        "type": "object",
        "id": "#post",
        "properties": {
            "author": {
                "description": "The user set as the author of the post.",
                "type": {
                    "$ref": "#user"
                },
                "required": true
            },
            "comment_count": {
                "description": "The number of comments for this post.",
                "type": "integer",
                "minimum": 0,
                "required": true
            },
            "comment_status": {
                "description": "The current status determining whether the post is accepting comments.",
                "enum": ["open", "closed"],
                "required": true
            },
            "content_display": {
                "description": "The content of the post after it has been run through the set 'the_content' filters.  Shortcodes are not expanded.",
                "type": "string",
                "required": true
            },
            "content": {
                "description": "The raw content of the post as it's stored in the database.",
                "type": "string",
                "required": true
            },
            "date": {
                "description": "The post's creation time in iso 8601 format.",
                "type": "string",
                "format": "date-time",
                "required": true
            },
            "excerpt_display": {
                "description": "The excerpt of the post after it has been run through the 'the_excerpt' filters.",
                "type": "string",
                "required": true
            },
            "excerpt": {
                "description": "The raw excerpt as it is stored in the database.",
                "type": "string",
                "required": true
            },
            "id_str": {
                "description": "The ID of the post represented as a string.",
                "type": "string",
                "required": true
            },
            "id": {
                "description": "The ID of the post",
                "type": "integer",
                "minimum": 1,
                "required": true
            },
            "type": {
                "description": "The post_type of the post",
                "type": "string",
                "required": true
            },
            "media": {
                "type": "array",
                "required": false,
                "items": {
                    "type": {
                        "$ref": "#mediaItem"
                    }
                }
            },
            "meta": {
                "description": "Additional data for the Post object.  Handling must be provided by other plugins to expand the provided meta beyond core properties.",
                "type": "object",
                "required": false,
                "default": {},
                "additionalProperties": {
                    "featuredImage": {
                        "description": "The ID of the image being referenced as the featured image.  The referenced image should be present in the media property.",
                        "type": "integer",
                        "minimum": 1
                    },
                    "gallery": {
                    	"description": "An array of objects that represent the galleries in the post content.",
                    	"type": "array",
                    	"required": false,
                    	"items": {
                    	 	"ids": {
                    	 		"description": "The IDs of the attachments to be used in the gallery.",
                    	 		"type": "array",
                    	 		"required": false
                    	 	},
                        	"orderby": {
                        		"description": "Specifies how to sort the display thumbnails.",
                        		"type": "array",
                        		"required": false
                        	},
                        	"order": {
                        		"description": "Specifies the sort order used to display thumbnails."
                        		"type": "string",
                        		"required": false
                        	},
                        	"in": {
                        		"description": "An array of IDs to only show the images from these attachments."
                        		"type": "array",
                        		"required": false
                        	},
                        	"exclude": {
                        		"description": "An array of IDs to not show the images from these attachments."
                        		"type": "array",
                        		"required": false
                        	},
                        	"id": {
                    	 		"description": "The ID of the post to be used in the gallery. Used for specifying other posts.",
                    	 		"type": "integer",
                    	 		"required": false
                    	 	}
                    	}
                    }
                }
            },
            "mime_type": {
                "description": "The mime type of the represented object",
                "type": "string",
                "required": true,
                "default": "text/html"
            },
            "modified": {
                "type": "string",
                "format": "date-time",
                "required": true
            },
            "name": {
                "description": "The name (slug) for the post, used in URLs.",
                "type": "string",
                "required": true
            },
            "parent_str": {
                "description": "The ID of the post's parent as a string, if it has one.",
                "type": "string",
                "required": false
            },
            "parent": {
                "description": "The ID of the post's parent as a string, if it has one.",
                "type": "integer",
                "required": false
            },
            "permalink": {
                "description": "The full permalink URL to the post.",
                "type": "string",
                "formate": "uri",
                "required": true
            },
            "status": {
                "description": "The status of the post.",
                "type": {
                    "enum": ["publish", "draft", "pending", "future", "trash"]
                },
                "required": true
            },
            "taxonomies": {
                "description": "Key/Value pairs of taxonomies that exist for the given post where the Key is the name of the taxonomy.",
                "type": "object",
                "required": false,
                "default": {},
                "additionalProperties": {
                    "category": {
                        "type": "array",
                        "items": {
                            "type": {
                                "$ref": "#term"
                            }
                        },
                        "required": false
                    },
                    "post_tag": {
                        "type": "array",
                        "items": {
                            "type": {
                                "$ref": "#term"
                            }
                        },
                        "required": false
                    }
                }
            },
            "title": {
                "description": "The title of the Post.",
                "type": "string",
                "required": true
            }
        }
    }
	
##### Example Post Response
	{
		"id" : 1234567,
		"id_str" : "1234567",
		"type" : "post",
		"permalink": "http://example.com/posts/foobar/",
		"parent": 12345,
		"parent_str": "12345",
		"date": "2012-01-01T12:59:59+00:00",
		"modified": "2012-01-01T12:59:59+00:00",
		"status": "publish",
		"comment_status":"open",
		"comment_count": 99,
		"menu_order": 99,
		"title": "Lorem Ipsum Dolor!",
		"name": "loerm-ipsum-dolor"	,
		"excerpt": "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec sed lacus eros. Integer elementum urna.",
		"excerpt_display": "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec sed lacus eros. Integer elementum urna.</p>",
		"content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed nec consequatnibh. Quisque in consectetur ligula. Praesent pretium massa vitae neque adipiscing vita cursus nulla congue.\n<img src=\"http://example.com/wp-content/uploads/2012/03/foobar.jpg\" class=\"alignleft  size-medium wp-image-17115\" alt=\"Lorem ipsum doler set amut.\" />\n Cras aliquet ipsum non nisi accumsan tempor sollicitudin lacus interdum Donec in enim ut ligula dignissim tempor. Vivamus semper cursus mi, at molestie erat lobortiut. Pellentesque non mi vitae augue egestas vulputate et eu massa. Integer et sem orci. Suspendisse at augue in ipsum convallis semper.\n\n[gallery ids=\"1,2,3,4\"]\n\nNullam vitae libero eros, a fringilla erat. Suspendisse potenti. In dictum bibendum liberoquis facilisis risus malesuada ac. Nulla ullamcorper est ac lectus feugiat scelerisque.  Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas",
		"content_display": "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed nec consequatnibh. Quisque in consectetur ligula. Praesent pretium massa vitae neque adipiscing vita cursus nulla congue.</p>\n<img src=\"http://example.com/wp-content/uploads/2012/03/foobar.jpg\" class=\"alignleft  size-medium wp-image-17115\" alt=\"Lorem ipsum doler set amut.\" />\n<p>Cras aliquet ipsum non nisi accumsan tempor sollicitudin lacus interdum Donec in enim ut ligula dignissim tempor. Vivamus semper cursus mi, at molestie erat lobortiut. Pellentesque non mi vitae augue egestas vulputate et eu massa. Integer et sem orci. Suspendisse at augue in ipsum convallis semper.</p>\n\n<div class=\"gallery\" id=\"gallery-1234\"></div>\n\n<p>Nullam vitae libero eros, a fringilla erat. Suspendisse potenti. In dictum bibendum liberoquis facilisis risus malesuada ac. Nulla ullamcorper est ac lectus feugiat scelerisque.  Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas</p>",
		"author": [User Object],
		"mime_type": "",
		"meta": {
			"featuredImage": 123456,
			"gallery" : [
				{
					"ids": [23],
					"orderby": [
						"menu_order",
						"ID"
					],
					"order": "ASC",
					….
				},
				….
			]
		},
		"taxonomies": {
			"category": [
				[Term Object],
				….
			],
			"post_tag": [
				[Term Object],
				….
			],
			….
		},
		"media": [
			{
				"type":
				"id": 123456,
				"id_str": ""123445",
				"altText": "Lorem ipsum doler set amut.",
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
			….
		]
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
			<td class="shade" colspan="3">Pagination Filters</td>
		</tr>
		<tr>
			<td>paged</td>
			<td>integer</td>
			<td>A positive integer specifiying the page (or subset of results) to return.  This 				filter will automatically determine the offset to use based on the per_page
				and paged. Using this filter will cause include_found to be true.
			</td>
		</tr>
		<tr>
			<td>per_page</td>
			<td>integer</td>
			<td>The maximum number of posts to return.  The value must range from 1 to 				MAX_USERS_PER_PAGE.</td>
		</tr>
		<tr>
			<td>offset</td>
			<td>integer</td>
			<td>The number of posts to skip over before returning the result set.</td>
		</tr>
		<tr>
			<td class="shade" colspan="3">Ordering Parameters</td>
		</tr>
		<tr>
			<td>orderby</td>
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
			<td class="shade" colspan="3">General Filters</td>
		</tr>
		<tr>
			<td>in</td>
			<td>array|integer</td>
			<td>An array of user ID's to include.</td>
		</tr>
		<tr>
			<td class="shade" colspan="3">
				Response Altering Parameters
			</td>
		</tr>
		<tr>
			<td>include_found</td>
			<td>boolean</td>
			<td>Defaut to false.  When true, the response will include a found rows count.  There is some
			overhead in generating the total count so this should only be turned on when needed.  This is 
			automatically turned on if the 'paged' filter is used.</td>
		</tr>
		<tr>
			<td>who</td>
			<td>string</td>
			<td>Filters to users based on a subset of roles.  Currently, only 'authors' is supported.</td>
		</tr>
		<tr>
			<td>callback</td>
			<td>string</td>
			<td>When set, the response will be wrapped in a JSONP callback.</td>
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
			<td class="shade" colspan="3">
				Response Altering Parameters
			</td>
		</tr>
		<tr>
			<td>callback</td>
			<td>string</td>
			<td>When set, the response will be wrapped in a JSONP callback.</td>
		</tr>
	</tbody>
</table>

##### User JSON Schema
	{
        "description": "Representation of a single sytem user or author.",
        "id": "#user",
        "type": "object",
        "properties": {
            "id_str": {
                "description": "The ID of the User object as a string.",
                "type": "integer",
                "required": true
            },
            "id": {
                "description": "The ID of the User object.",
                "type": "integer",
                "required": true
            },
            "nicename": {
                "description": "The user's slug, or url safe name.",
                "type": "string",
                "required": false
            },
            "display_name": {
                "description": "The user's name as shown publicly.",
                "type": "string",
                "required": false
            },
            "posts_url": {
                "description": "The URL to the user's posts.",
                "type": "string",
                "format": "uri",
                "required": false
            },
            "user_url": {
                "description": "The User's personal URL.",
                "type": "string",
                "required": false
            },
            "avatar": {
                "description": "An array of images/sizes available for the User's avatar.",
                "type": "array",
                "items": {
                    "description": "Image information for a User's Avatar.",
                    "type": "object",
                    "properties": {
                        "height": {
                            "description": "Height of the image in pixels.",
                            "type": "integer",
                            "required": true
                        },
                        "url": {
                            "description": "Full URL to the image resource.",
                            "type": "string",
                            "format": "uri",
                            "required": true
                        },
                        "width": {
                            "description": "Width of the image in pixels.",
                            "type": "integer",
                            "required": true
                        }
                    }
                }
            },
            "meta": {
                "description": "Extended User data.",
                "type": "object"
            }
        }

    }
                     

##### Example User Response
	{
		"id" : 1234567,
		"id_str" : "1234567",
		"nicename": "john-doe",
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
			"nickname": "Johnny",
			"first_name": "John",
			"last_name": "Doe",
			"description": "Lorem ipsum dolar set amet."
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
		<tr>
			<td>callback</td>
			<td>string</td>
			<td>When set, the response will be wrapped in a JSONP callback.</td>
		</tr>
		<tr>
			<td>callback</td>
			<td>string</td>
			<td>When set, the response will be wrapped in a JSONP callback.</td>
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
			<td class="shade" colspan="3">
				Response Altering Parameters
			</td>
		</tr>
		<tr>
			<td>callback</td>
			<td>string</td>
			<td>When set, the response will be wrapped in a JSONP callback.</td>
		</tr>
	</tbody>
</table>

##### Taxonomy JSON Schema
	{
        "id": "#taxonomy",
        "descrption": "A representation of a taxonomy.",
        "type": "object",
        "properties": {
            "name": {
                "description": "The name/unique identifier for the taxonomy.",
                "type": "string",
                "required": true
            },
            "post_type": {
                "description": "An array of post types the taxony is tied to.",
                "type": "array",
                "items": {
                    "description": "The post_type string.",
                    "type": "string"
                }
            },
            "hierarchical": {
                "description": "Indicates whether the taxonomy is hierarchical or allows parent/child relationships.",
                "type": "boolean",
                "default": false
            },
            "query_var": {
                "description": "The query_var tied to this taxonomy.  Useful when processing rewrite rules to determine the proper API query.",
                "type": "string",
                "required": false
            },
            "labels": {
                "description": "The user displayed name representing the taxonomy.",
                "type": "object",
                "properties": {
                    "name": {
                        "description": "The plural name of the taxonomy.",
                        "type": "string"
                    },
                    "singularName": {
                        "description": "The singular name of the taxonomy.",
                        "type": "string"
                    }
                }
            },
            "meta": {
                "description": "Extended Taxonomy data.",
                "type": "object"
            }
        }
    }

##### Example Taxonomy Response
	{
		"name": "category",
		"post_types": [
			"post",
			"attachment",
			….
		],
		"hierarchical": true,
		"queryVar":"category",
		"labels": {
			"name": "Categories",
			"singularName": "Category"
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
			<td class="shade" colspan="3">Pagination Filters</td>
		</tr>
		<tr>
			<td>paged</td>
			<td>integer</td>
			<td>A positive integer specifiying the page (or subset of results) to return.  This 				filter will automatically determine the offset to use based on the per_page
				and paged. Using this filter will cause include_found to be true.
			</td>
		</tr>
		<tr>
			<td>per_page</td>
			<td>integer</td>
			<td>The maximum number of posts to return.  The value must range from 1 to 				MAX_TERMS_PER_PAGE.</td>
		</tr>
		<tr>
			<td>offset</td>
			<td>integer</td>
			<td>The number of posts to skip over before returning the result set.</td>
		</tr>
		<tr>
			<td class="shade" colspan="3">Ordering Parameters</td>
		</tr>
		<tr>
			<td>orderby</td>
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
			<td class="shade" colspan="3">General Filters</td>
		</tr>
		<tr>
			<td>in</td>
			<td>array|integer</td>
			<td>An array of term ID's to include.</td>
		</tr>
		<tr>
			<td>slug</td>
			<td>string</td>
			<td>A term slug to include.</td>
		</tr>
		<tr>
			<td>parent</td>
			<td>id</td>
			<td>Include the children of the provided term ID.</td>
		</tr>
		<tr>
			<td>hide_empty</td>
			<td>boolean</td>
			<td>If true, only terms with attached posts will be returned.  Default is true.</td>
		</tr>
		<tr>
			<td>pad_counts</td>
			<td>boolean</td>
			<td>If true, count all of the children along with the term.  Default is false.</td>
		</tr>
		<tr>
			<td class="shade" colspan="3">
				Response Altering Parameters
			</td>
		</tr>
		<tr>
			<td>include_found</td>
			<td>boolean</td>
			<td>Defaut to false.  When true, the response will include a found rows count.  There is some
			overhead in generating the total count so this should only be turned on when needed.  This is 
			automatically turned on if the 'paged' filter is used.</td>
		</tr>
		<tr>
			<td>callback</td>
			<td>string</td>
			<td>When set, the response will be wrapped in a JSONP callback.</td>
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
			<td class="shade" colspan="3">
				Response Altering Parameters
			</td>
		</tr>
		<tr>
			<td>callback</td>
			<td>string</td>
			<td>When set, the response will be wrapped in a JSONP callback.</td>
		</tr>
	</tbody>
</table>

#### Term JSON Schema
	{
        "type": "object",
        "required": false,
        "properties": {
            "description": {
                "description": "A long text describing the term.",
                "type": "string",
                "required": false
            },
            "meta": {
                "description": "Extended Term data.",
                "type": "object",
            },
            "name": {
                "description": "The title/name of the term as displayed to users.",
                "type": "string",
                "required": false
            },
            "parent_str": {
                "description": "The ID of the parent term as a string, if exists.",
                "type": "string",
                "required": false
            },
            "parent": {
                "description": "The ID of the parent term, if exists.",
                "type": "number",
                "required": false
            },
            "post_count": {
                "description": "The distinct count of posts attached to this term.  If 'pad_count' is set to true, this will also include all posts attached to child terms.  This only includes posts of type 'post'.",
                "type": "number",
                "required": false
            },
            "slug": {
                "description": "The name (slug) of the term as used in URLs.",
                "type": "string",
                "required": false
            },
            "taxonomy": {
                "type": "string",
                "required": false
            },
            "id_str": {
                "description": "The ID of the term as a string.",
                "type": "string",
                "id": "http://jsonschema.net/term_id_str",
                "required": false
            },
            "id": {
                "description": "The ID of the term.",
                "type": "number",
                "id": "http://jsonschema.net/term_id",
                "required": false
            },
            "term_taxonomy_id_str": {
                "description": "The ID that uniquely represents this term/taxonomy as ing asterms are shared across multiple taxonomies.",
                "type": "string",
                "id": "http://jsonschema.net/term_taxonomy_id_str",
                "required": false
            },
            "term_taxonomy_id": {
                "description": "The ID that uniquely represents this term/taxonomy as terms are shared across multiple taxonomies.",
                "type": "number",
                "id": "http://jsonschema.net/term_taxonomy_id",
                "required": false
            }
        }
    }

##### Example Term Response
	{
		"id": 123456,
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
		"meta":{
		}
	}


##Rewrite Rules
<span id="Rewrite_Rules"></span>Rewrite Rules can be used to convert internal links in content into API requests.
### Methods
#### List

##### Request
    GET {api root}/rewrite_rules

#### Rewrite Rules JSON Schema
	{
        "id": "#rewrite_rules",
        "description": "Rewrite Rules represent the URL structure on the hosting API site.  Providing these to the client, allows the client to override internal links with sequential API requests.",
        "type": "object",
        "properties": {
            "base_url": {
                "description": "The root URL which all rewrite rules are based.",
                "type": "string",
                "format": "uri",
                "required": true
            },
            "rewrite_rules": {
                "type": "array",
                "items": {
                    "type": "object",
                    "properties": {
                        "query_expression": {
                            "description": "The format string used to build the resulting query parameters for the rewrite rules from the components matched from the regex.",
                            "type": "string",
                            "required": true
                        },
                        "regex": {
                            "description": "The regular expression used to break down the requested URL into components.",
                            "type": "string",
                            "format": "regex",
                            "required": true
                        }
                    }
                }


            }
        }
    }

##### Example Rewrite Rules Response
	{
		"base_url": "http://example.com/",
		"rewrite_rules": [
			{
				"regex": "category/(.+?)/?$",
				"query_expression": "category_name=$1"
			}
			….
		]
	}

## Media Items
<span id="media_items">Below are the schemas for media items.

### Base Media Items JSON Schema
    {
        "description": "Base media object",
        "id": "mediaItem",
        "type": "object",
        "properties": {
            "type": {
                "description": "The subclass of media object being represented.",
                "type": "string",
                "required": true
            }
        }

    }

### Internal Media Item Base JSON Schema
    {
        "description": "An internal media item hosted by this WP instance that is backed by a Post Object",
        "type": "object",
        "id": "#internalMediaItem",
        "extends": {
            "$ref": "#mediaItem"
        },
        "properties": {
            "idStr": {
                "description": "The ID of the Post object representing this media attachment as a string.",
                "type": "integer",
                "required": true
            },
            "id": {
                "description": "The ID of the Post object representing this media attachment.",
                "type": "integer",
                "required": true
            },
            "mimeType": {
                "description": "The mime type of the attched media object",
                "type": "string",
                "required": true
            },
        }
    }

### Internal Image Media Item JSON Schema
    {
        "description": "An internal image item hosted by this WP instance",
        "type": "object",
        "id": "#internalMediaItem",
        "extends": {
            "$ref": "#mediaItem"
        },
        "properties": {
            "altText": {
                "description": "The alternate text for the image.  Maps to post_meta key '_wp_attachment_image_alt'.",
                "type": "string",
                "required": false
            },
            "sizes": {
                "description": "Listing of available sizes of the image allowing the proper size to be used for the client device.",
                "type": "array",
                "required": true,
                "items": {
                    "type": "object",
                    "required": true,
                    "properties": {
                        "height": {
                            "description": "Height of the image in pixels.",
                            "type": "integer",
                            "required": true
                        },
                        "name": {
                            "description": "The identifier for the size that generated this size of the image.",
                            "type": "string",
                            "required": true
                        },
                        "url": {
                            "description": "Full URL to the image resource.",
                            "type": "string",
                            "format": "uri",
                            "required": true
                        },
                        "width": {
                            "description": "Width of the image in pixels.",
                            "type": "integer",
                            "required": true
                        }
                    }
                }


            }
        }
    }


## Comments
<span id="Comments"></span>A comment represents a user response to a post

### Methods
#### List

##### Request
    GET {api root}/comments

    GET {api root}/posts/{#post_id}/comments
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
			<td class="shade" colspan="3">
			Date Filters
			</td>
		</tr>
		<tr>
			<td>before </td>
			<td>string</td>
			<td>A parsable formatted date string.  Unless specified in the format used, 
			the result will be relative to the timezone of the site.</td>
		</tr>
		<tr>
			<td>after </td>
			<td>string</td>
			<td>A parsable formatted date string.  Unless specified in the format used, 
			the result will be relative to the timezone of the site.</td>
		</tr>
		<tr>
			<td class="shade" colspan="3">Search Filtering</td>
		</tr>
		<tr>
			<td>s</td>
			<td>string</td>
			<td>
				Search keyword or string, by default this searches against the author, author email, author url, author ip, and content.
				The search looks for a match to the entire search expression.
			</td>
		</tr>
		<tr>
			<td class="shade" colspan="3">Pagination Filters</td>
		</tr>
		<tr>
			<td>paged</td>
			<td>integer</td>
			<td>A positive integer specifiying the page (or subset of results) to return.  This filter will automatically determine the offset to use based on the per_page
				and paged arguments. Using this filter will cause include_found to be true.
			</td>
		</tr>
		<tr>
			<td>per_page</td>
			<td>integer</td>
			<td>The maximum number of posts to return.  The value must range from 1 to MAX_COMMENTS_PER_PAGE.</td>
		</tr>
		<tr>
			<td>offset</td>
			<td>integer</td>
			<td>The number of posts to skip over before returning the result set.</td>
		</tr>
		<tr>
			<td class="shade" colspan="3">Ordering Parameters</td>
		</tr>
		<tr>
			<td>orderby</td>
			<td>array|string</td>
			<td>Sort the results by the given identifier.  Defaults to 'date'.  Supported values are:
				<ul>
					<li>'comment_date_gmt' - (Default) The GMT date of the post.</li>
					<li>'comment_ID' - The ID of the post.</li>
					<li>'comment_author' - The value of the author ID.</li>
					<li>'comment_date' - The date of the comment..</li>
					<li>'comment_type' - The type of comment.</li>
					<li>'comment parent'- The ID of the comment's parent</li>
					<li>'comment_post_ID' - The ID of the post which the comment belongs.</li>
					<li>'user_id' - The ID of the user making the comments.</li>
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
			<td class="shade" colspan="3">General Filters</td>
		</tr>
		<tr>
			<td>in</td>
			<td>integer|array</td>
			<td>Array of Ids of comments to include.</td>
		</tr>
		<tr>
			<td>parent</td>
			<td>integer</td>
			<td>ID of the parent comment to pull from.</td>
		</tr>
		<tr>
			<td>post_id</td>
			<td>integer</td>
			<td>ID of post from which to pull comments.</td>
		</tr>
		<tr>
			<td>post_name</td>
			<td>string</td>
			<td>Slug/Name of the post from which to pull comments.</td>
		</tr>
		<tr>
			<td>type</td>
			<td>string</td>
			<td>The type of comments to return.  Default options: 'comment', 'pingback', 'trackback', 'pings' (returns trackbacks and pingbacks').</td>
		</tr>
		<tr>
			<td>status</td>
			<td>string</td>
			<td>The status of comments to return.  Default: 'approved'.</td>
		</tr>
		<tr>
			<td>user_id</td>
			<td>int</td>
			<td>User ID of commentor making the comments.</td>
		</tr>
		<tr>
			<td class="shade" colspan="3">
				Response Altering Parameters
			</td>
		</tr>
		<tr>
			<td>include_found</td>
			<td>boolean</td>
			<td>Defaut to false.  When true, the response will include a found rows count.  There is some
			overhead in generating the total count so this should only be turned on when needed.  This is 
			automatically turned on if the 'paged' filter is used.</td>
		</tr>
		<tr>
			<td>callback</td>
			<td>string</td>
			<td>When set, the response will be wrapped in a JSONP callback.</td>
		</tr>
	</tbody>
</table>



##### Response
	{
		'found': 40, //only provided if include_found == true
		"comments": [
			[Comment Object],
			….
		]
	}



#### Single Entity

##### Request
    GET {api root}/comments/{id}

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
			<td class="shade" colspan="3">
				Response Altering Parameters
			</td>
		</tr>
		<tr>
			<td>callback</td>
			<td>string</td>
			<td>When set, the response will be wrapped in a JSONP callback.</td>
		</tr>
	</tbody>
</table>

##### Comment JSON Schema
	{
        "title": "Comment Object",
        "description": "A representation of a single post object",
        "type": "object",
        "id": "#comment",
        "properties": {
            "author": {
                "description": "Display name of the author of the comment.",
                "type": "string",
                "required": true
            }
            "author_url": {
                "description": "URL set for the author of the comment.",
                "type": "string",
                "required": false
            }
            "date": {
                "description": "The comment's creation time in iso 8601 format.",
                "type": "string",
                "format": "date-time",
                "required": true
            },
            "content": {
                "description": "The raw comment content.",
                "type": "string",
                "required": true
            },
            "content_display": {
                "description": "Display formatted content of the comment.",
                "type": "string",
                "required": true
            },
            "user": {
                "description": "ID of the user making the comment",
                "type": "integer",
                "required": false
            },
						"user_id_str": {
                "description": "String version of the ID of the user making the comment",
                "type": "string",
                "required": false
            },
            "id_str": {
                "description": "The ID of the post represented as a string.",
                "type": "string",
                "required": true
            },
            "id": {
                "description": "The ID of the post",
                "type": "integer",
                "minimum": 1,
                "required": true
            },
            "type": {
                "description": "The type of comment.  Deafult enum: 'comment', 'pingback', 'trackback'",
                "type": "string",
                "required": true
            },
            "media": {
                "type": "array",
                "required": false,
                "items": {
                    "type": {
                        "$ref": "#mediaItem"
                    }
                }
            },
            "parent_str": {
                "description": "The ID of the comment's parent as a string, if it has one.",
                "type": "string",
                "required": false
            },
            "parent": {
                "description": "The ID of the comment's parent as a string, if it has one.",
                "type": "integer",
                "required": false
            },
            "status": {
                "description": "The status of the comment.",
                "type": {
                    "enum": ["approve", "pending", "spam", "trash"]
                },
                "required": true
            }
        }
    }
	
##### Example Comment Response
    {
        "id": 597,
        "id_str": "597",
        "type": "comment",
        "author": "John Doe",
        "author_url": "http://example.org",
        "parent": 0,
        "parent_str": "0",
        "date": "2013-06-11T18:39:46+00:00",
        "content": "This is my comment text",
        "status": "approve",
        "user": 1,
        "user_id_str": "1",
        "content_display": "<p>This is my comment text<\/p>\n",
        "avatar": [
            {
                "url": "http:\/\/1.gravatar.com\/avatar\/96614ec98aa0c0d2ee75796dced6df54?s=96&amp;d=http%3A%2F%2F1.gravatar.com%2Favatar%2Fad516503a11cd5ca435acc9bb6523536%3Fs%3D96&amp;r=G",
                "width": 96,
                "height": 96
            }
        ]
    }