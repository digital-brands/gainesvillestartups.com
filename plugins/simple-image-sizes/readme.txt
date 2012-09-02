=== Simple Image Sizes ===
Contributors: Rahe
Donate link: http://www.beapi.fr/donate/
Tags: images, image, custom sizes, custom images, thumbnail regenerate, thumbnail, regenerate
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 2.3.1

== Description ==

This plugin allow create custom image sizes for your site. Override your theme sizes directly on the media option page.
You can regenerate all the sizes you have just created and choose which one you wanted to regenerate.
You can now get all the code to copy and paste to your function theme file.
Now you can use the generated sizes directly into your posts and insert images at the right size !
Now you choose if you want display the size in the post insert image.
Now you can regenerate the images one by one in the 'Medias' general pane.
Now you can regenerate the images by bulk action in the 'Medias' general pane.
Now you can regenerate the image sizes on single attachment edit page.

I have added a timer so when you regeneration your thumbnails, you can know approximately when the regeneration will be ended.
I have improved the php and javascript, you can know if the image have been regenerated or not or if there is an error and which one.

Contribute on https://github.com/Rahe/Simple-image-sizes

== Installation ==
 **PHP5 Required.**
 
1. Download, unzip and upload to your WordPress plugins directory
2. Activate the plugin within you WordPress Administration Backend
3. Go to Settings > Medias
4. Configure your new image sizes and regenerate the thumbnails !

== Screenshots ==

1. Settings page
2. Get PHP for the theme
3. Choose the sizes to regenerate
4. Regenerating image sizes

== Changelog ==
* 2.3.1
	* Add Ajax bulk actions on medias list
	* Add ajax thumbnail rebuild on single media
* 2.3
	* Add the custom size name in the attachment insertion
	* Exclude post_type wich do not support the post-thumbnail feature
* 2.2.5
	* Debug the regeneration buggy !
	* Complete the french translation
	* Security update for single regeneration, include the nonce this time :)
* 2.2.4
	* Add security nonces for every actions
	* Put the messages at the begining of the log
	* Add a select all checkbox Thank to cocola
	* Add the german translation thanks to glueckpress
	* Remove notice tnahks to christianwach
	* Remove useless and buggy for my scripting pointers
* 2.2.3
	* Do not force network usage
* 2.2.2
	* Debug js for the buttons
	* Remove console.log calls
* 2.2.1
	* Use buttonset for the checkboxes
	* Add Pointer for WordPress 3.3
	* Fix translation in French
	* Some medias queries for small windows ( change size of buttons and alignment )
* 2.2
	* Add new version of css aristo
	* Add some icons
	* Display button for saving changes only when changes detected
	* Display message when a size is modified but not saved and wanted to regenerate
	* Debug functionnality when regenerating only some sizes, metas not crushed
	* Add solo regenerating
	* Remove displaying for theme/not theme sizes
	* Use WordPress class for small inputs 
* 2.1
	* Add javascript timer
	* Improve javascript and more IE friendly
	* You can now choose if you want to display the image sizes in image insertion or not
	* Handle errors and messages
	* Remove some css useless rules
	* Fix bad translation for french
	* Remove accents in image sizes
	* Do not update size properties if there is an ajax query for an another size name
* 2.0.3
	* Resolve issue with theme sizes witch by default are displayed as not cropped. Thanks to momo360modena for the bug signalment.
* 2.0.2
	* Remove debug on php for javascript
	* Resolve issue with the different versions on jquery ( like in WP3.2 ) with attr return for checked components
* 2.0.1
	* Resolve javascript issue when clicking on delete button
	* Resolve issue of never unchecking crop button
* 2.0
	* Code refactoring
	* Update translations
	* Ajaxification of the process
		* Deleting by Ajax
		* Updating by Ajax
		* Adding by Ajax
	* Change UI
	* Change theme
	* Handle ajax errors
	* Handle not modified sizes, cropped
	* Handle same names
	* Sanitize the names
	* Customize jQuery ui
	* Customize jQuery ui theme
	* HTML5 Elements
	* CSS3 Animations
* 1.0.6
	* Minify javascript names
	* Change progressbar style
	* Add animations on progressbar
* 1.0.5
	* Only add css and js script in the media page to avoid any javascript error in other pages
	* Rectify css
	* Add function to get the code for the function.php file of the theme
	* Don't redefine the Wordpress basic size names
* 1.0.4
	* Fix the add_image_size issue ( height and width reversed )
* 1.0.3
	* Fix the plugin language
	* Add some translations
	* Externalise some css
	* Add sizes in the image adding to an article
	* Add setting link in the plugins list
	* Use admin_url instead of home_url
	* Add legend for colors
	* Some code refactoring
* 1.0.2
	* Fix the plugin license
* 1.0.1
	* Add POT file
	* Add french translation
* 1.0
	* First release
	* Thumbnail regenerate
	* Image size generation