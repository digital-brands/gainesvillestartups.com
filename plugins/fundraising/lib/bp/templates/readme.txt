You can bundle template files in your plugin that are used to output screens.

By default these will be loaded when you call bp_core_load_template(), however if a user
copies a given template file into their active theme, they will override the templates in your plugin. That way users can style your templates for their own themes.

If you make changes to the templates in future versions you will need to let users know what
has changes, so they can update their templates accordingly.

Of course, templates are not required. You can just build screens in your plugin files with HTML.
However, this reduces flexbility for end users.

For more information, see the functions bp_wdf_load_template_filter() for how template locations
are filtered, and bp_wdf_screen_one() for how they are loaded for a screen (all in bp-wdf-core.php)

62863-1338483608

32536-1346051460-au