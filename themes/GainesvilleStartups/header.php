<!DOCTYPE html>
<html>
    <head>
        <script type="text/javascript" src="//use.typekit.net/ued2zbf.js"></script>
        <script type="text/javascript">try{Typekit.load();}catch(e){}</script> 
        <link rel="shortcut icon" href="<?php echo get_stylesheet_directory_uri() . '/images/favicon.ico'; ?>" />
        
        <title><?php
            wp_title('', $echo = true, 'left');
            echo( " | " . get_bloginfo('name') );
        ?></title>

        <?php wp_head(); ?>
    </head>

        <body <?php body_class(); ?>>
        <nav id='main-navigation'>
            <div class='container'>
                <div id='navbar' class='sixteen columns'>
                    <a id='logo' href='<?php echo get_home_url(); ?>'>
                        <span id='gainesville'>GAINESVILLE</span><span id='startups'>STARTUPS</span>
                    </a>

                    <div id='login' class='pull-right'>
                        <a href='#'>+ Create a Project</a>
                    </div>
                </div><!-- end #navbar -->
            </div><!-- end .container -->
        </nav><!-- end #main-navigation -->
        <div class='container'>
