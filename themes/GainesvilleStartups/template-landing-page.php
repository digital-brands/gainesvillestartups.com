<?
/*
 Template Name: Landing Page
 */
?>
<?php get_header(); ?>
    <?php 
        gs_check_view_projects_submit();

        global $wp_embed;
        $intro_video = $wp_embed->run_shortcode( '[embed width="622" height="350"]http://vimeo.com/44633289[/embed]' ); ?>
        <div class='twelve columns offset-by-two'>
            <div id='intro-video'><?php echo $intro_video; ?></div>
        </div>

        <div class='sixteen columns'>
            <p id='main-tagline'>We help local businesses fund projects by turning to you, their customers, for help.
        </div>

        <div class='twelve columns offset-by-two'>
            <form id='view-projects' name='view-projects' method="post" action="">
                <input type='email' placeholder='enter your email (optional)' name='email' />
                <?php wp_nonce_field('view-projects-nonce', 'view-projects-nonce' ); ?>
                <button type='submit'>view projects</button>
            </form>
        </div>

            <section id='steps'>
                <div class="row">
                    <div class='one-third column'>
                        <div class='step'>
                            <h2>1. Check out the projects.</h2>
                            <p>There are a lot of cool things happening around town.  We introduce new projects every week of local businesses, works of art, and community service.</p>
                        </div><!-- end .step -->
                    </div>
                
                    <div class='one-third column'>
                        <div class='step'>
                            <h2>2. Help out.</h2>
                            <p>Not only does becoming a supporter help our local community, but you will also feel awesome about what you have done.  You'll be able to brag to your friends about how cool you are.</p>
                        </div><!-- end .step -->
                    </div>
    
                    <div class='one-third column'>
                        <div class='step'>
                            <h2>3. Reap the rewards!</h2>
                            <p>Of course, you aren't just doing this out of the kindness of your heart. Or maybe you are?  Either way you'll get some great and often unique perks from every project you support.</p>
                        </div><!-- end .step -->
                    </div>
                </div>
            </section>
<?php get_footer(); ?>       
