<?php get_header(); ?>
    <?php
        echo ( '<div class="eleven columns">' );
        while ( have_posts() ) : the_post();
            the_title( '<h1>', '</h1>' );
            the_content();
        endwhile;
        echo ( '</div>' );
    ?>
    <div class="five columns">
        <div id="sidebar">
            <?php dynamic_sidebar( 'single-sidebar' ); ?>
    
            <?php 
                $levels = gs_get_levels();
                if ( $levels ) {
                    foreach( $levels as $level ) {
                        echo ( "<li>" );
                            $level = maybe_unserialize($level);
                            foreach( $level as $index => $data ) {
                                echo ( "<a href='" .  get_permalink() . "pledge'>" );
                                    echo ( "<div class='level'>" );
                                        echo ( "<div class='value'>" . $wdf->format_currency('',$data['amount']) . "</div><!-- end .value -->" );
                                        echo ( "<div class='description'>" . $data['description'] . "</div><!-- end .description -->" );
                                    echo ( "</div><!-- end .level -->" );
                                echo ( "</a>" );
                            }
                        echo ( "</li>" );
                    }
                }
            ?>
        </div>
    </div>
<?php get_footer(); ?>       
