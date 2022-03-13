<?php
/**
 * Plugin Name: Published By
 * Version:     1.0
 * Author:      Mazen Alaa
 * Description: Export orgin author of post to CSV.
**/

class ma_CSVExport
{    
    /**
    * Constructor
    */
    public function __construct()
    {
        // Hook admin menu
        add_action('admin_menu', array($this, 'ma_admin_menu'));
        
    }

    /**
    * Add extra menu item in admin dashboard
    * add_menu_page(): string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = ''
    */
    public function ma_admin_menu()
    {
        add_menu_page('Download Report', 'Download Report', 'manage_options', 'download_report', array($this, 'ma_download_report'));
    }

    /**
    * Download report
    */
    public function ma_download_report()
    {
        echo '<div class="wrap">';
        echo '<div id="icon-tools" class="icon32">
        </div>';

        echo '<h2>Download Report</h2>';

        // Create Html Form to date from user start and end Date
        $ma_form = '';
        $ma_form .= '<form method="post">';
        $ma_form .= '<h3>Start Date</h3>';
        $ma_form .= '<input type="date" name="start_date" required />';
        $ma_form .= '<br>';
        $ma_form .= '<h3>End Date</h3>';
        $ma_form .= '<input type="date" name="end_date" required />';
        $ma_form .= '<br>';
        $ma_form .= '<br>';
        $ma_form .= '<input type="submit" name="submit_date" />';
        $ma_form .= '</form>';

        echo $ma_form;

        if(isset($_POST['submit_date'])){
            
            // Get start and end Date submitted by user
            $ma_start_date = $_POST['start_date'];
            $ma_end_date = $_POST['end_date'];    
            
            // Print Start and end Date Submitted for notice
            echo '<br>';
            echo '<b> Start Date: '. $ma_start_date .'</b><br>';
            echo '<b> End Date: '. $ma_end_date .'</b>';
            echo '<h3>Download Report From Here</h3>';
            
            // Print report Download Link 
            $ma_csv = $this->ma_csvFile($ma_start_date, $ma_end_date);   
            echo $ma_csv;
           
            // Create Html Form to delete csv file
            echo '<h3> delete file</h3>';
            $ma_delete_form = '';
            $ma_delete_form .= '<form method="post">';
            $ma_delete_form .= '<input type="submit" name="submit_delete" />';
            $ma_delete_form .= '</form>';
            echo $ma_delete_form;          
        }

        // Make Sure user submit for delete file
        if(isset($_POST['submit_delete'])){
            $ma_path = wp_upload_dir();                
            if(unlink($ma_path['path']."/published_by.csv")) echo "delete successfully";
        }  
    }

    /**
    * Exporting data to CSV
    */
    public function ma_csvFile($ma_start_date, $ma_end_date) {
        
        $ma_path = wp_upload_dir();

        // Create CSV file
        $ma_outstream = fopen($ma_path['path']."/published_by.csv", "w");  
        
        // Create CSV Header
        $ma_columns = array('id', 'title', 'date', 'url', 'displayed author', 'published by');  
        fputcsv($ma_outstream, $ma_columns);  

        // Get day, month, year from start & end ate by Spliting date string
        $ma_start_date_array = explode('-', $ma_start_date);
        $ma_end_date_array = explode('-', $ma_end_date);    
        
        // WP Query
        $ma_args = array(
            'post_type' => 'post',
            'date_query' => array(
                array(
                    'after'     => array(
                        'year'  => (int)$ma_start_date_array[0],
                        'month' => (int)$ma_start_date_array[1],
                        'day'   => (int)$ma_start_date_array[2],
                    ),
                    'before'    => array(
                        'year'  => (int)$ma_end_date_array[0],
                        'month' => (int)$ma_end_date_array[1],
                        'day'   => (int)$ma_end_date_array[2],
                    ),
                    'inclusive' => true,
                ),
            ),
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        // Execute Query
        $ma_query = new WP_Query( $ma_args );
         
        if ( $ma_query->have_posts() ) {
         
            while ( $ma_query->have_posts() ) {
         
                $ma_query->the_post();
                
                // Get Author Id 
                $ma_author = get_the_author_meta();
                $ma_author_id = $ma_author->ID;
                
                $ma_publisher_id = '';
                
                // Get Publisher ID from Post Revisions
                $ma_post_revisions  = wp_get_post_revisions( get_the_ID() );
				$ma_latest_revision = array_shift( $ma_post_revisions );

                if ( $ma_latest_revision ) {
					$ma_rev = wp_get_post_revision( $ma_latest_revision );
					$ma_publisher_id = $ma_rev->post_author;
                }
                
                // Get Creator author name
                $ma_publisher_name = get_the_author_meta( 'display_name', $ma_publisher_id );
                
                // Get Displayed author name
                $ma_author_name = get_the_author_meta( 'display_name', $ma_author_id );
                
                // Append CSV Row 
                fputcsv( 
                    $ma_outstream, 
                    [ get_the_ID(), get_the_title(), get_the_date(), get_permalink(), $ma_author_name, $ma_publisher_name ] 
                );
            }       
        }
         
        // Reset the `$post` data to the current post in main query.
        wp_reset_postdata();
        
        // Close CSV
        fclose($ma_outstream); 

        // Make a link to download the file
        echo '<a href="'.$ma_path['url'].'/published_by.csv">Download</a>';  
    }
}

// Instantiate a singleton of this plugin
new ma_CSVExport();
?>