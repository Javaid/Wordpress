<?php
set_time_limit(0);
/**
 * Include the wordpress core
 */
require_once( '../wpfolder/wp-load.php' );

class WPConversion
{
    protected $con;
    protected $dbHost ; 
    protected $dbUser; 
    protected $dbName; 

    public function __construct() {
        $this->dbHost ="127.0.0.1"; 
        $this->dbUser = "root";
        $this->dbPassword = ""; 
        $this->dbName= "sales"; 
    }
  /**
   * Connect with Non wordpress database
   */
    protected function connectDB(){
         $this->con = mysqli_connect($this->dbHost, $this->dbUser,  $this->dbPassword,  $this->dbName);
    }
    
    protected function createAPost(){
        $rs  = mysqli_query($this->con,"SELECT *  FROM  listing limit 10") or die(mysqli_error());

        while($row   =  mysqli_fetch_array($rs,MYSQLI_ASSOC)){
        $div = ($row['s_org_price']==0)? 1:$row['s_org_price'];
        // Post Title
        $title = round(100-(($row['s_dis_price']/$div)*100)) . " % OFF on " .$row['s_title']. ' - '. $row['s_brand_name'];
        // Post Content    
        $my_post = array(
            'post_title'    => wp_strip_all_tags($title),
            'post_content'  => $row['s_description'],
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_category' => array(1)
        );

        // Create a new post
        $result = wp_insert_post( $my_post );
        // Add post meta data
        $this->addPostMetaData($result, $row);


        
    }// End of While

    }

    protected function addPostMetaData($result, $row){
    if ( $result && ! is_wp_error( $result ) ) {
            $post_id = $result;
            
            // Add Post Feature Image 
            $this->addFeatureImage($row['s_pic_1'], $post_id, $row['s_category'], $row['s_brand_name']);
            
            add_post_meta($post_id, 's_pic_1', $row['s_pic_1']);
            add_post_meta($post_id, 's_pic_2', $row['s_pic_2']);
            add_post_meta($post_id, 's_pic_3', $row['s_pic_3']);
            add_post_meta($post_id, 's_pic_4', $row['s_pic_4']);
            add_post_meta($post_id, 's_dis_price', $row['s_dis_price']);
            add_post_meta($post_id, 's_org_price', $row['s_org_price']);
            add_post_meta($post_id, 's_brand_name', $row['s_brand_name']);
            add_post_meta($post_id, 's_instock', $row['s_instock']);
            add_post_meta($post_id, 's_sku', $row['s_sku']);
            add_post_meta($post_id, 's_detail_link', $row['s_detail_link']);
        } // if post is inserted 

    } // Add post meta data

// Add Feature Image

protected function addFeatureImage($img_url, $post_id, $category_name, $brand_name)
{
 require_once( ABSPATH . 'wp-admin/includes/taxonomy.php' );


 $cat_id  = get_cat_ID( $brand_name );

        if($cat_id == 0){
             $new_cat_id  = wp_create_category( $brand_name);// wp_create_category( $brand_name );
            
            if($new_cat_id==0){
                $mix_catid = 4;
            }else{
                $catid = $new_cat_id;
            }
        }else{
            $catid = $cat_id;
        }

        switch($category_name){
            case "men":
                $post_categories = array(2, $catid);
                break;
            case "women":
              $post_categories = array(3, $catid);
              break;
                case "":
                $post_categories = array(4, $catid);
                break;
            default:{
                echo $mix_catid; 
                $mix_catid=($mix_catid=='')?4:$mix_catid;
                $post_categories = array($mix_catid,$catid);
            }
                
            
        }
     

         $filetype = wp_check_filetype( basename( $img_url ), null );
        // Add Featured Image to Post
        $image_url = $img_url;//'http://www.geotauaisay.com/wp-content/uploads/2016/09/14315996_1339649252730823_396816470_o-300x160.jpg'; // Define the image URL here
        $current_time = current_time('timestamp');
        $image_name = $current_time . '.'.$filetype['ext'];
        $upload_dir = wp_upload_dir(); // Set upload folder
        
        if(empty($image_url)) return;

        $image_data = file_get_contents($image_url); // Get image data
        $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
        $filename = basename($unique_file_name); // Create image file name

        // Check folder permission and define file location
        if (wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        // Create the image  file on the server
        file_put_contents($file, $image_data);

        // Check image file type
        $wp_filetype = wp_check_filetype($filename, null);

        // Set attachment data
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Create the attachment
        $attach_id = wp_insert_attachment($attachment, $file, $post_id);

        // Include image.php
       require_once( ABSPATH . 'wp-admin/includes/image.php' );
      
        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);

        // Assign metadata to attachment
        wp_update_attachment_metadata($attach_id, $attach_data);
        // And finally assign featured image to post
        set_post_thumbnail($post_id, $attach_id);
        wp_set_post_terms( $post_id, $post_categories, 'category',true );
        //wp_set_post_categories( $post_id, $post_categories, $append );
      // exit;
    }

}
