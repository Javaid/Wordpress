<?php
ini_set("default_socket_timeout", 1800); // Default time 60 second
/** Make sure that the WordPress bootstrap has run before continuing. */
require('../wp-load.php');
include_once('../wp-admin/includes/image.php');
$objWP = new create_wp_post();

class create_wp_post
{

    var $connection;
    var $db_host;
    var $db_username;
    var $db_password;
    var $db_name;

    function __construct()
    {


        $this->db_host = 'localhost';
        $this->db_username = "root";
        $this->db_password = '';
        $this->db_name = 'javed_sales';
        // Connect with database
        $this->connection = $this->dbConnection();
        $this->get_listing();
    }
    /**
     * Connect with MySQLi database
     */
    function dbConnection()
    {
        $is_connected = false;
        $is_connected  =  mysqli_connect($this->db_host, $this->db_username, $this->db_password, $this->db_name) or die(mysqli_error());
        if ($is_connected) {
            return $is_connected;
        }
    }
    /**
     * Custom table query
     */
    function get_listing()
    {
        $sql = "select * from listing limit 1";
        $result  = mysqli_query($this->connection, $sql) or die(mysqli_error($this->connection));
        while ($row = mysqli_fetch_array($result)) {
            echo "<pre>";
            print_r($row);

            $this->post(
                $row['s_title'],
                $row['s_description'],
                $row['s_pic_1'],
                $row['s_pic_2'],
                $row['s_pic_3'],
                $row['s_org_price'],
                $row['s_dis_price'],
                $row['s_sku'],
                $row['s_brand_name'],
                $row['s_instock'],
                $row['s_disclaimer'],
                $row['s_delivery'],
                $row['s_detail_link']
            );
        }
    }
    /**
     * Create a WP Post
     */
    function post(
        $post_title,
        $s_description,
        $s_pic_1,
        $s_pic_2,
        $s_pic_3,
        $s_org_price,
        $s_dis_price,
        $s_sku,
        $s_brand_name,
        $s_instock,
        $s_disclaimer,
        $s_delivery,
        $s_detail_link

    ) {
        $post_title = trim(addslashes(strip_tags($post_title)));
        $s_description = trim(addslashes(strip_tags($s_description, '<br /><p><h1><h2><span>')));
        // insert the post and set the category


        global $user_ID, $wpdb;

        $query = $wpdb->prepare(
            'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title = %s ',
            $post_title
        );

        $wpdb->query($query);
        echo "<hr>";

        if ($wpdb->num_rows) {
            $post_id = $wpdb->get_var($query);
            echo "Post ID: " . $post_id;
            //  $meta = get_post_meta($post_id, 'times', TRUE);
            //   $meta++;
            //   update_post_meta($post_id, 'times', $meta);
        } else {
            $new_post = array(
                'post_title' => $post_title,
                'post_content' => $s_description,
                'post_status' => 'publish',
                'post_date' => date('Y-m-d H:i:s'),
                'post_author' => 1,
                'post_type' => 'post',
                'post_category' => array(0)
            );

            $post_id = wp_insert_post($new_post);

            if (!empty($s_pic_1))         add_post_meta($post_id, 's_pic_1', $s_pic_1);
            if (!empty($s_pic_2))         add_post_meta($post_id, 's_pic_2', $s_pic_2);
            if (!empty($s_pic_3))         add_post_meta($post_id, 's_pic_3', $s_pic_3);

            if (!empty($s_org_price))     add_post_meta($post_id, 's_org_price', $s_org_price);
            if (!empty($s_dis_price))     add_post_meta($post_id, 's_dis_price', $s_dis_price);

            if (!empty($s_sku))           add_post_meta($post_id, 's_sku', $s_sku);
            if (!empty($s_brand_name))    add_post_meta($post_id, 's_brand_name', $s_brand_name);
            if (!empty($s_instock))       add_post_meta($post_id, 's_instock', $s_instock);
            if (!empty($s_disclaimer))    add_post_meta($post_id, 's_disclaimer', $s_disclaimer);
            if (!empty($s_delivery))      add_post_meta($post_id, 's_delivery', $s_delivery);
            if (!empty($s_detail_link))   add_post_meta($post_id, 's_detail_link', $s_detail_link);

            if (!is_wp_error($post_id)) {
                $this->add_post_thumbnail($s_pic_1, $post_id);
            }
        }
    }

    function add_post_thumbnail($image_url, $post_id)
    {


        $image_dir = 'images';

        $info  = pathinfo($image_url);
        echo "<pre>";
        $new_image = ($info['basename']);

        copy($image_url, './images/' . $new_image);

        $upload = wp_upload_bits($new_image, null, file_get_contents('./images/' . $new_image, FILE_USE_INCLUDE_PATH));
        print_r($upload);

        // check and return file type
        $imageFile = $upload['file'];
        $wpFileType = wp_check_filetype($imageFile, null);
        // Attachment attributes for file
        $attachment = array(
            'post_mime_type' => $wpFileType['type'],  // file type
            'post_title' => sanitize_file_name($imageFile),  // sanitize and use image name as file name
            'post_content' => '',  // could use the image description here as the content
            'post_status' => 'inherit'
        );
        // insert and return attachment id
        $attachmentId = wp_insert_attachment($attachment, $imageFile, $post_id);
        // insert and return attachment metadata
        $attachmentData = wp_generate_attachment_metadata($attachmentId, $imageFile);
        // update and return attachment metadata
        wp_update_attachment_metadata($attachmentId, $attachmentData);
        // finally, associate attachment id to post id
        $success = set_post_thumbnail($post_id, $attachmentId);
        update_post_meta($post_id, '_thumbnail_id', $attachmentId);
        // was featured image associated with post?
        if ($success) {
           
            return true;
        }
    }
}
